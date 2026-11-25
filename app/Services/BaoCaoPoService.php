<?php

namespace App\Services;

use App\Services\SubartSoLuong\SubartSoLuongFactory;
use App\Support\Database;
use Throwable;

class BaoCaoPoService
{
    private $db;

    public function __construct(private DatchiPoService $datchi, private DanhMucLookup $danhMuc)
    {
        $this->db = Database::getNangSuat();
    }

    // Chuẩn hoá + kiểm po_number/sub_art_code với datchi; lỗi trả kèm 'status' để controller đặt mã HTTP.
    public function chuanHoaPoSubart($ma_hang_id, $po_number, $sub_art_code)
    {
        $loi = function ($status, $message) {
            return ['success' => false, 'status' => $status, 'message' => $message];
        };
        if (($po_number !== null && !is_string($po_number)) || ($sub_art_code !== null && !is_string($sub_art_code))) {
            return $loi(422, 'PO hoặc subart không hợp lệ');
        }
        $po_number = trim((string)$po_number);
        $sub_art_code = trim((string)$sub_art_code);
        if ($po_number === '' && $sub_art_code === '') {
            return ['success' => true, 'po_number' => null, 'sub_art_code' => null];
        }
        if ($po_number === '') {
            return $loi(422, 'Chọn subart thì phải chọn PO');
        }
        if (strlen($po_number) > 50 || strlen($sub_art_code) > 100) {
            return $loi(422, 'PO hoặc subart không hợp lệ');
        }

        $stmt = mysqli_prepare($this->db, 'SELECT ma_hang FROM ma_hang WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $ma_hang_id);
        mysqli_stmt_execute($stmt);
        $maHang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$maHang) {
            return $loi(422, 'Mã hàng không tồn tại');
        }

        $datchi = $this->datchi;
        $po = $datchi->getPoQuantity($po_number, $maHang['ma_hang']);
        if (!$po['available']) {
            return $loi(503, 'Không kết nối được dữ liệu PO');
        }
        if ($po['quantity'] === null) {
            return $loi(422, 'PO ' . $po_number . ' không có mã hàng ' . $maHang['ma_hang']);
        }

        if ($sub_art_code === '') {
            return ['success' => true, 'po_number' => $po_number, 'sub_art_code' => null];
        }
        $subArts = $datchi->getSubArtsForStyle($maHang['ma_hang']);
        if (!$subArts['available']) {
            return $loi(503, 'Không kết nối được dữ liệu PO');
        }
        foreach ($subArts['items'] as $code) {
            if (strcasecmp($code, $sub_art_code) === 0) {
                return ['success' => true, 'po_number' => $po_number, 'sub_art_code' => $code];
            }
        }
        return $loi(422, 'Subart ' . $sub_art_code . ' không thuộc mã hàng ' . $maHang['ma_hang']);
    }

    // Khối PO/subart của báo cáo: SL + lũy kế thành phẩm của các báo cáo KHÁC (FE cộng báo cáo đang mở).
    public function getKhoiPo($baoCao)
    {
        $po_number = trim((string)($baoCao['po_number'] ?? ''));
        if ($po_number === '') {
            return null;
        }
        $sub_art_code = trim((string)($baoCao['sub_art_code'] ?? ''));

        // Datchi lỗi không được làm hỏng GET báo cáo.
        $available = false;
        $soLuongPo = null;
        try {
            $r = $this->datchi->getPoQuantity($po_number, $baoCao['ma_hang']);
            $available = (bool)$r['available'];
            $soLuongPo = $available ? $r['quantity'] : null;
        } catch (Throwable $e) {
        }

        $luyKePo = 0;
        $luyKeSub = 0;
        $khac = [];
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT id, line_id, routing_snapshot, sub_art_code
             FROM bao_cao_nang_suat WHERE po_number = ? AND ma_hang_id = ? AND id <> ?',
        );
        $maHangId = intval($baoCao['ma_hang_id']);
        $selfId = intval($baoCao['id']);
        mysqli_stmt_bind_param($stmt, 'sii', $po_number, $maHangId, $selfId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $khac[intval($row['id'])] = $row;
        }
        mysqli_stmt_close($stmt);

        if ($khac) {
            // Công đoạn thành phẩm theo snapshot từng báo cáo; thiếu thì lấy routing hiện tại của LINE.
            $congDoanTheoLine = [];
            $congDoan = [];
            foreach ($khac as $id => $row) {
                $cdId = PoTienDo::thanhPhamCongDoanId($row['routing_snapshot']);
                if ($cdId === null) {
                    $lineId = intval($row['line_id']);
                    if (!array_key_exists($lineId, $congDoanTheoLine)) {
                        $congDoanTheoLine[$lineId] = null;
                        foreach ($this->danhMuc->getRouting($maHangId, $lineId) as $cd) {
                            if (intval($cd['la_cong_doan_tinh_luy_ke']) === 1) {
                                $congDoanTheoLine[$lineId] = intval($cd['cong_doan_id']);
                                break;
                            }
                        }
                    }
                    $cdId = $congDoanTheoLine[$lineId];
                }
                $congDoan[$id] = $cdId;
            }

            // Ô nhập là lũy kế tại mốc → thành phẩm của báo cáo = số ở mốc muộn nhất đã nhập (như grid rowTotal).
            $ids = implode(',', array_map('intval', array_keys($khac)));
            $result = mysqli_query(
                $this->db,
                "SELECT nl.bao_cao_id, nl.cong_doan_id, nl.so_luong
                 FROM nhap_lieu_nang_suat nl
                 JOIN moc_gio mg ON mg.id = nl.moc_gio_id
                 WHERE nl.bao_cao_id IN ($ids) AND nl.so_luong > 0
                 ORDER BY nl.bao_cao_id, mg.thu_tu, mg.gio",
            );
            $thanhPham = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $id = intval($row['bao_cao_id']);
                if ($congDoan[$id] !== null && $congDoan[$id] === intval($row['cong_doan_id'])) {
                    $thanhPham[$id] = intval($row['so_luong']);
                }
            }
            foreach ($thanhPham as $id => $sl) {
                $luyKePo += $sl;
                if ($sub_art_code !== '' && strcasecmp((string)$khac[$id]['sub_art_code'], $sub_art_code) === 0) {
                    $luyKeSub += $sl;
                }
            }
        }

        $subArt = null;
        if ($sub_art_code !== '') {
            $soLuongSub = null;
            try {
                $items = SubartSoLuongFactory::make()->getSoLuong($po_number, $baoCao['ma_hang'])['items'] ?? [];
                foreach ($items as $code => $sl) {
                    if (strcasecmp((string)$code, $sub_art_code) === 0) {
                        $soLuongSub = intval($sl);
                        break;
                    }
                }
            } catch (Throwable $e) {
            }
            $subArt = [
                'sub_art_code' => $sub_art_code,
                'so_luong' => $soLuongSub,
                'luy_ke_bao_cao_khac' => $luyKeSub,
            ];
        }

        return [
            'po_number' => $po_number,
            'available' => $available,
            'so_luong_po' => $soLuongPo,
            'luy_ke_bao_cao_khac' => $luyKePo,
            'sub_art' => $subArt,
        ];
    }
}
