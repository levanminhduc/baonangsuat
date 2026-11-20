<?php

namespace App\Services;

use App\Services\SubartSoLuong\SubartSoLuongFactory;
use App\Services\SubartSoLuong\SubartSoLuongSource;
use App\Support\Database;
use mysqli_sql_exception;

class PoSubartService
{
    private $source;
    private $datchi;
    private $db;

    public function __construct(?SubartSoLuongSource $source = null, ?DatchiPoService $datchi = null)
    {
        $this->source = $source ?? SubartSoLuongFactory::make();
        $this->datchi = $datchi ?? new DatchiPoService();
    }

    private function db()
    {
        if ($this->db === null) {
            $this->db = Database::getNangSuat();
        }
        return $this->db;
    }

    private static function loi($status, $message)
    {
        return ['success' => false, 'status' => $status, 'message' => $message];
    }

    private function getMaHang($maHangId)
    {
        $stmt = mysqli_prepare($this->db(), 'SELECT id, ma_hang FROM ma_hang WHERE id = ?');
        $maHangId = intval($maHangId);
        mysqli_stmt_bind_param($stmt, 'i', $maHangId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    // Danh mục cho modal tạo báo cáo: PO chứa mã hàng.
    public function danhMucPo($maHangId)
    {
        $maHang = $this->getMaHang($maHangId);
        if (!$maHang) {
            return self::loi(404, 'Mã hàng không tồn tại');
        }

        return ['success' => true, 'data' => $this->datchi->getPoListForStyle($maHang['ma_hang'])];
    }

    // Danh mục cho modal tạo báo cáo: subart của mã hàng + SL từ nguồn hiện tại.
    public function danhMucSubart($poNumber, $maHangId)
    {
        $poNumber = trim((string)$poNumber);
        $maHang = $this->getMaHang($maHangId);
        if (!$maHang) {
            return self::loi(404, 'Mã hàng không tồn tại');
        }

        $subArts = $this->datchi->getSubArtsForStyle($maHang['ma_hang']);
        $soLuongMap = $poNumber === '' ? [] : ($this->source->getSoLuong($poNumber, $maHang['ma_hang'])['items'] ?? []);
        $items = [];
        foreach ($subArts['items'] as $code) {
            $items[] = ['sub_art_code' => $code, 'so_luong' => $soLuongMap[$code] ?? null];
        }

        return [
            'success' => true,
            'data' => [
                'available' => $subArts['available'],
                'nguon' => $this->source->nguon(),
                'items' => $items,
            ],
        ];
    }

    public function list($poNumber, $maHangId)
    {
        $poNumber = trim((string)$poNumber);
        if ($poNumber === '') {
            return self::loi(422, 'Thiếu PO');
        }
        $maHang = $this->getMaHang($maHangId);
        if (!$maHang) {
            return self::loi(404, 'Mã hàng không tồn tại');
        }

        $po = $this->datchi->getPoQuantity($poNumber, $maHang['ma_hang']);
        $subArts = $this->datchi->getSubArtsForStyle($maHang['ma_hang']);
        $soLuong = $this->source->getSoLuong($poNumber, $maHang['ma_hang']);
        $soLuongMap = $soLuong['items'] ?? [];

        $meta = [];
        if ($this->source->nguon() === 'manual') {
            $stmt = mysqli_prepare(
                $this->db(),
                'SELECT sub_art_code, updated_by, updated_at FROM po_subart_so_luong WHERE po_number = ? AND ma_hang_id = ?',
            );
            $id = intval($maHang['id']);
            mysqli_stmt_bind_param($stmt, 'si', $poNumber, $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $meta[$row['sub_art_code']] = $row;
            }
            mysqli_stmt_close($stmt);
        }

        $items = [];
        $daCo = [];
        foreach ($subArts['items'] as $code) {
            $items[] = $this->item($code, $soLuongMap, $meta, false);
            $daCo[strtoupper($code)] = true;
        }
        // SL đã nhập cho subart không còn trong datchi: vẫn hiện để admin xoá.
        foreach ($soLuongMap as $code => $sl) {
            if (!isset($daCo[strtoupper($code)])) {
                $items[] = $this->item($code, $soLuongMap, $meta, true);
            }
        }

        return [
            'success' => true,
            'data' => [
                'nguon' => $this->source->nguon(),
                'editable' => $this->source->laEditable(),
                'available' => $po['available'] && $subArts['available'],
                'so_luong_po' => $po['quantity'],
                'tong_da_phan_bo' => array_sum($soLuongMap),
                'items' => $items,
            ],
        ];
    }

    private function item($code, array $soLuongMap, array $meta, $ngoaiDanhSach)
    {
        return [
            'sub_art_code' => $code,
            'so_luong' => $soLuongMap[$code] ?? null,
            'updated_by' => $meta[$code]['updated_by'] ?? null,
            'updated_at' => $meta[$code]['updated_at'] ?? null,
            'ngoai_danh_sach' => $ngoaiDanhSach,
        ];
    }

    public function save($poNumber, $maHangId, $subArtCode, $soLuong, $ma_nv)
    {
        if (!$this->source->laEditable()) {
            return self::loi(409, 'SL subart đang lấy từ ' . $this->source->nguon() . ', không sửa tay được');
        }
        $poNumber = trim((string)$poNumber);
        $subArtCode = trim((string)$subArtCode);
        if ($poNumber === '' || strlen($poNumber) > 50 || $subArtCode === '' || strlen($subArtCode) > 100) {
            return self::loi(422, 'PO hoặc subart không hợp lệ');
        }
        if (is_int($soLuong)) {
            $soLuong = (string)$soLuong;
        }
        if (!is_string($soLuong) || !preg_match('/^\d{1,9}$/', trim($soLuong))) {
            return self::loi(422, 'Số lượng phải là số nguyên không âm');
        }
        $soLuong = intval(trim($soLuong));

        $maHang = $this->getMaHang($maHangId);
        if (!$maHang) {
            return self::loi(404, 'Mã hàng không tồn tại');
        }

        $po = $this->datchi->getPoQuantity($poNumber, $maHang['ma_hang']);
        $subArts = $this->datchi->getSubArtsForStyle($maHang['ma_hang']);
        if (!$po['available'] || !$subArts['available']) {
            return self::loi(503, 'Không kết nối được dữ liệu PO');
        }
        if ($po['quantity'] === null) {
            return self::loi(422, 'PO ' . $poNumber . ' không có mã hàng ' . $maHang['ma_hang']);
        }
        $codeChuan = null;
        foreach ($subArts['items'] as $code) {
            if (strcasecmp($code, $subArtCode) === 0) {
                $codeChuan = $code;
                break;
            }
        }
        if ($codeChuan === null) {
            return self::loi(422, 'Subart ' . $subArtCode . ' không thuộc mã hàng ' . $maHang['ma_hang']);
        }

        $db = $this->db();
        $maHangIdInt = intval($maHang['id']);
        $ma_nv = strtoupper(trim((string)$ma_nv));
        try {
            mysqli_begin_transaction($db);
            // Khoá các dòng cùng (PO, mã hàng) để hai lần lưu song song không vượt tổng.
            $stmt = mysqli_prepare(
                $db,
                'SELECT sub_art_code, so_luong FROM po_subart_so_luong WHERE po_number = ? AND ma_hang_id = ? FOR UPDATE',
            );
            mysqli_stmt_bind_param($stmt, 'si', $poNumber, $maHangIdInt);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $khac = [];
            while ($row = mysqli_fetch_assoc($result)) {
                if (strcasecmp($row['sub_art_code'], $codeChuan) !== 0) {
                    $khac[] = intval($row['so_luong']);
                }
            }
            mysqli_stmt_close($stmt);

            $kiem = PoTienDo::kiemTongSubart($khac, $soLuong, $po['quantity']);
            if (!$kiem['ok']) {
                mysqli_rollback($db);
                return self::loi(422, sprintf(
                    'Tổng SL subart (%s) vượt SL PO – mã hàng (%s)',
                    number_format($kiem['tong'], 0, ',', '.'),
                    number_format($po['quantity'], 0, ',', '.'),
                ));
            }

            $stmt = mysqli_prepare(
                $db,
                'INSERT INTO po_subart_so_luong (po_number, ma_hang_id, sub_art_code, so_luong, updated_by)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE sub_art_code = VALUES(sub_art_code), so_luong = VALUES(so_luong), updated_by = VALUES(updated_by)',
            );
            mysqli_stmt_bind_param($stmt, 'sisis', $poNumber, $maHangIdInt, $codeChuan, $soLuong, $ma_nv);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            mysqli_commit($db);
        } catch (mysqli_sql_exception $e) {
            mysqli_rollback($db);
            if (in_array(intval($e->getCode()), [1213, 1205], true)) {
                return self::loi(409, 'Có người đang lưu cùng PO, vui lòng thử lại');
            }
            throw $e;
        }

        return ['success' => true, 'message' => 'Đã lưu SL subart'];
    }

    public function delete($poNumber, $maHangId, $subArtCode)
    {
        if (!$this->source->laEditable()) {
            return self::loi(409, 'SL subart đang lấy từ ' . $this->source->nguon() . ', không sửa tay được');
        }
        $poNumber = trim((string)$poNumber);
        $subArtCode = trim((string)$subArtCode);
        $maHangIdInt = intval($maHangId);
        $stmt = mysqli_prepare(
            $this->db(),
            'DELETE FROM po_subart_so_luong WHERE po_number = ? AND ma_hang_id = ? AND sub_art_code = ?',
        );
        mysqli_stmt_bind_param($stmt, 'sis', $poNumber, $maHangIdInt, $subArtCode);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        if ($affected < 1) {
            return self::loi(404, 'Không có SL subart này');
        }
        return ['success' => true, 'message' => 'Đã xoá SL subart'];
    }
}
