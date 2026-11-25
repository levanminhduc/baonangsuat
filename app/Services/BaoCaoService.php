<?php

namespace App\Services;

use App\Support\Database;
use Exception;
use mysqli_sql_exception;

class BaoCaoService
{
    private const MSG_BAO_CAO_TRUNG = 'Báo cáo đã tồn tại cho ngày/ca/mã hàng/PO/subart này';

    private $db;

    public function __construct(
        private DanhMucLookup $danhMuc,
        private LuyKeCalculator $luyKe,
        private BaoCaoPoService $po,
    ) {
        $this->db = Database::getNangSuat();
    }

    private function daCoBaoCao($ngay_bao_cao, $line_id, $ca_id, $ma_hang_id, $po_number, $sub_art_code, $excludeId = 0)
    {
        $po = (string)$po_number;
        $sub = (string)$sub_art_code;
        $checkStmt = mysqli_prepare(
            $this->db,
            "SELECT id FROM bao_cao_nang_suat
             WHERE ngay_bao_cao = ? AND line_id = ? AND ca_id = ? AND ma_hang_id = ?
               AND IFNULL(po_number, '') = ? AND IFNULL(sub_art_code, '') = ? AND id <> ?",
        );
        mysqli_stmt_bind_param($checkStmt, 'siiissi', $ngay_bao_cao, $line_id, $ca_id, $ma_hang_id, $po, $sub, $excludeId);
        mysqli_stmt_execute($checkStmt);
        $found = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt)) !== null;
        mysqli_stmt_close($checkStmt);
        return $found;
    }

    public function createBaoCao($data, $ma_nv)
    {
        $ngay_bao_cao = $data['ngay_bao_cao'];
        $line_id = $data['line_id'];
        $ca_id = $data['ca_id'];
        $ma_hang_id = $data['ma_hang_id'];
        $so_lao_dong = intval($data['so_lao_dong'] ?? 0);
        $ctns = intval($data['ctns'] ?? 0);
        $ghi_chu = $data['ghi_chu'] ?? '';

        $poSubart = $this->po->chuanHoaPoSubart($ma_hang_id, $data['po_number'] ?? null, $data['sub_art_code'] ?? null);
        if (!$poSubart['success']) {
            return $poSubart;
        }
        $po_number = $poSubart['po_number'];
        $sub_art_code = $poSubart['sub_art_code'];

        if ($this->daCoBaoCao($ngay_bao_cao, $line_id, $ca_id, $ma_hang_id, $po_number, $sub_art_code)) {
            return ['success' => false, 'message' => self::MSG_BAO_CAO_TRUNG];
        }

        $mocGioResult = $this->danhMuc->getMocGioList($ca_id, $line_id);
        $mocGioList = $mocGioResult['data'];
        $tong_phut_hieu_dung = 0;
        if (count($mocGioList) > 0) {
            $lastMoc = $mocGioList[count($mocGioList) - 1];
            $tong_phut_hieu_dung = intval($lastMoc['so_phut_hieu_dung_luy_ke']);
        }

        $ct_gio = 0;
        if ($tong_phut_hieu_dung > 0 && $ctns > 0) {
            $ct_gio = round($ctns / ($tong_phut_hieu_dung / 60), 2);
        }

        $routing = $this->danhMuc->getRouting($ma_hang_id, $line_id);
        $routingSnapshot = json_encode([
            'version' => 1,
            'created_at' => date('c'),
            'routing' => $routing,
        ], JSON_UNESCAPED_UNICODE);

        $stmt = mysqli_prepare(
            $this->db,
            'INSERT INTO bao_cao_nang_suat
             (ngay_bao_cao, line_id, ca_id, ma_hang_id, so_lao_dong, ctns, ct_gio, tong_phut_hieu_dung, ghi_chu, tao_boi, routing_snapshot, po_number, sub_art_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        );
        mysqli_stmt_bind_param(
            $stmt,
            'siiiiidisssss',
            $ngay_bao_cao,
            $line_id,
            $ca_id,
            $ma_hang_id,
            $so_lao_dong,
            $ctns,
            $ct_gio,
            $tong_phut_hieu_dung,
            $ghi_chu,
            $ma_nv,
            $routingSnapshot,
            $po_number,
            $sub_art_code,
        );

        try {
            $ok = mysqli_stmt_execute($stmt);
        } catch (mysqli_sql_exception $e) {
            mysqli_stmt_close($stmt);
            // Hai request song song qua được check trùng thì UNIQUE chặn.
            if (intval($e->getCode()) === 1062) {
                return ['success' => false, 'message' => self::MSG_BAO_CAO_TRUNG];
            }
            throw $e;
        }
        if (!$ok) {
            $errno = mysqli_errno($this->db);
            mysqli_stmt_close($stmt);
            if ($errno === 1062) {
                return ['success' => false, 'message' => self::MSG_BAO_CAO_TRUNG];
            }
            return ['success' => false, 'message' => 'Lỗi tạo báo cáo: ' . mysqli_error($this->db)];
        }

        $bao_cao_id = mysqli_insert_id($this->db);
        mysqli_stmt_close($stmt);

        $this->preGenerateEntries($bao_cao_id, $routing, $mocGioList, $ma_nv);

        return [
            'success' => true,
            'message' => 'Tạo báo cáo thành công',
            'bao_cao_id' => $bao_cao_id,
        ];
    }

    public function preGenerateEntries($bao_cao_id, $routing, $mocGioList, $ma_nv)
    {
        $stmt = mysqli_prepare(
            $this->db,
            'INSERT INTO nhap_lieu_nang_suat (bao_cao_id, cong_doan_id, moc_gio_id, so_luong, nhap_boi)
             VALUES (?, ?, ?, 0, ?)',
        );

        foreach ($routing as $cd) {
            foreach ($mocGioList as $moc) {
                mysqli_stmt_bind_param($stmt, 'iiis', $bao_cao_id, $cd['cong_doan_id'], $moc['id'], $ma_nv);
                mysqli_stmt_execute($stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }

    public function getBaoCao($bao_cao_id)
    {
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT bc.*, l.ma_line, l.ten_line, c.ma_ca, c.ten_ca, mh.ma_hang, mh.ten_hang
             FROM bao_cao_nang_suat bc
             JOIN line l ON l.id = bc.line_id
             JOIN ca_lam c ON c.id = bc.ca_id
             JOIN ma_hang mh ON mh.id = bc.ma_hang_id
             WHERE bc.id = ?',
        );
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$baoCao) {
            return null;
        }

        if (!empty($baoCao['routing_snapshot'])) {
            $snapshot = json_decode($baoCao['routing_snapshot'], true);
            $baoCao['routing'] = $snapshot['routing'] ?? [];
            $baoCao['routing_is_snapshot'] = true;
        } else {
            $baoCao['routing'] = $this->danhMuc->getRouting($baoCao['ma_hang_id'], $baoCao['line_id']);
            $baoCao['routing_is_snapshot'] = false;
        }
        $mocGioResult = $this->danhMuc->getMocGioList($baoCao['ca_id'], $baoCao['line_id']);
        $baoCao['moc_gio_list'] = $mocGioResult['data'];
        $baoCao['moc_gio_is_fallback'] = $mocGioResult['is_fallback'];
        $baoCao['entries'] = $this->getEntries($bao_cao_id);
        $baoCao['chi_tieu_luy_ke'] = $this->luyKe->calculateChiTieuLuyKe($baoCao);
        $baoCao['luy_ke_thuc_te'] = $this->luyKe->calculateLuyKeThucTe($baoCao);
        $baoCao['po'] = $this->po->getKhoiPo($baoCao);

        return $baoCao;
    }

    public function getEntries($bao_cao_id)
    {
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT id, cong_doan_id, moc_gio_id, so_luong, kieu_nhap, ghi_chu
             FROM nhap_lieu_nang_suat
             WHERE bao_cao_id = ?',
        );
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $entries = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $key = $row['cong_doan_id'] . '_' . $row['moc_gio_id'];
            $entries[$key] = $row;
        }
        mysqli_stmt_close($stmt);
        return $entries;
    }
    public function updateEntries($bao_cao_id, $entries, $version, $ma_nv)
    {
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT version, trang_thai, line_id FROM bao_cao_nang_suat WHERE id = ?',
        );
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$baoCao) {
            return ['success' => false, 'message' => 'Báo cáo không tồn tại'];
        }

        if ($baoCao['version'] != $version) {
            return ['success' => false, 'message' => 'Dữ liệu đã được cập nhật bởi người khác. Vui lòng tải lại trang.'];
        }

        if (in_array($baoCao['trang_thai'], ['submitted', 'approved', 'locked', 'completed'])) {
            return ['success' => false, 'message' => 'Báo cáo đã chốt, không thể sửa'];
        }

        mysqli_begin_transaction($this->db);

        try {
            $updateStmt = mysqli_prepare(
                $this->db,
                'UPDATE nhap_lieu_nang_suat SET so_luong = ?, nhap_boi = ?, nhap_luc = NOW() 
                 WHERE bao_cao_id = ? AND cong_doan_id = ? AND moc_gio_id = ?',
            );

            foreach ($entries as $entry) {
                $so_luong = max(0, intval($entry['so_luong']));
                $cong_doan_id = intval($entry['cong_doan_id']);
                $moc_gio_id = intval($entry['moc_gio_id']);

                mysqli_stmt_bind_param(
                    $updateStmt,
                    'isiii',
                    $so_luong,
                    $ma_nv,
                    $bao_cao_id,
                    $cong_doan_id,
                    $moc_gio_id,
                );
                mysqli_stmt_execute($updateStmt);
            }
            mysqli_stmt_close($updateStmt);

            $newVersion = intval($version) + 1;
            $versionStmt = mysqli_prepare(
                $this->db,
                'UPDATE bao_cao_nang_suat SET version = ? WHERE id = ?',
            );
            mysqli_stmt_bind_param($versionStmt, 'ii', $newVersion, $bao_cao_id);
            mysqli_stmt_execute($versionStmt);
            mysqli_stmt_close($versionStmt);

            mysqli_commit($this->db);

            return [
                'success' => true,
                'message' => 'Cập nhật thành công',
                'new_version' => $newVersion,
            ];
        } catch (Exception $e) {
            mysqli_rollback($this->db);
            return ['success' => false, 'message' => 'Lỗi cập nhật: ' . $e->getMessage()];
        }
    }

    public function updateHeader($bao_cao_id, $data, $version)
    {
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT version, trang_thai, ca_id, line_id, ma_hang_id, ngay_bao_cao, po_number, sub_art_code FROM bao_cao_nang_suat WHERE id = ?',
        );
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$baoCao) {
            return ['success' => false, 'message' => 'Báo cáo không tồn tại'];
        }

        if ($baoCao['version'] != $version) {
            return ['success' => false, 'message' => 'Dữ liệu đã được cập nhật. Vui lòng tải lại trang.'];
        }

        if (in_array($baoCao['trang_thai'], ['approved', 'locked', 'completed'])) {
            return ['success' => false, 'message' => 'Báo cáo đã duyệt/khóa/hoàn tất, không thể sửa'];
        }

        $so_lao_dong = intval($data['so_lao_dong'] ?? 0);
        $ctns = intval($data['ctns'] ?? 0);
        $ghi_chu = $data['ghi_chu'] ?? '';

        // Không có khoá po_number/sub_art_code trong body thì giữ nguyên hai cột.
        $po_number = $baoCao['po_number'];
        $sub_art_code = $baoCao['sub_art_code'];
        if (array_key_exists('po_number', $data) || array_key_exists('sub_art_code', $data)) {
            $poSubart = $this->po->chuanHoaPoSubart(
                $baoCao['ma_hang_id'],
                array_key_exists('po_number', $data) ? $data['po_number'] : $po_number,
                array_key_exists('sub_art_code', $data) ? $data['sub_art_code'] : $sub_art_code,
            );
            if (!$poSubart['success']) {
                return $poSubart;
            }
            $po_number = $poSubart['po_number'];
            $sub_art_code = $poSubart['sub_art_code'];
            if ($this->daCoBaoCao($baoCao['ngay_bao_cao'], $baoCao['line_id'], $baoCao['ca_id'], $baoCao['ma_hang_id'], $po_number, $sub_art_code, intval($bao_cao_id))) {
                return ['success' => false, 'message' => self::MSG_BAO_CAO_TRUNG];
            }
        }

        $mocGioResult = $this->danhMuc->getMocGioList($baoCao['ca_id'], $baoCao['line_id']);
        $mocGioList = $mocGioResult['data'];
        $tong_phut_hieu_dung = 0;
        if (count($mocGioList) > 0) {
            $lastMoc = $mocGioList[count($mocGioList) - 1];
            $tong_phut_hieu_dung = intval($lastMoc['so_phut_hieu_dung_luy_ke']);
        }

        $ct_gio = 0;
        if ($tong_phut_hieu_dung > 0 && $ctns > 0) {
            $ct_gio = round($ctns / ($tong_phut_hieu_dung / 60), 2);
        }

        $newVersion = intval($version) + 1;

        $updateStmt = mysqli_prepare(
            $this->db,
            'UPDATE bao_cao_nang_suat
             SET so_lao_dong = ?, ctns = ?, ct_gio = ?, tong_phut_hieu_dung = ?, ghi_chu = ?, po_number = ?, sub_art_code = ?, version = ?
             WHERE id = ?',
        );
        mysqli_stmt_bind_param(
            $updateStmt,
            'iidisssii',
            $so_lao_dong,
            $ctns,
            $ct_gio,
            $tong_phut_hieu_dung,
            $ghi_chu,
            $po_number,
            $sub_art_code,
            $newVersion,
            $bao_cao_id,
        );

        try {
            $ok = mysqli_stmt_execute($updateStmt);
        } catch (mysqli_sql_exception $e) {
            mysqli_stmt_close($updateStmt);
            if (intval($e->getCode()) === 1062) {
                return ['success' => false, 'message' => self::MSG_BAO_CAO_TRUNG];
            }
            throw $e;
        }
        if ($ok) {
            mysqli_stmt_close($updateStmt);
            return [
                'success' => true,
                'message' => 'Cập nhật header thành công',
                'ct_gio' => $ct_gio,
                'new_version' => $newVersion,
            ];
        }

        mysqli_stmt_close($updateStmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật header'];
    }

    public function getBaoCaoList($line_id, $filters = [])
    {
        $includeCompleted = isset($filters['include_completed']) ? (bool)$filters['include_completed'] : false;

        $sql = 'SELECT bc.id, bc.ngay_bao_cao, bc.so_lao_dong, bc.ctns, bc.ct_gio, bc.trang_thai,
                       l.ma_line, c.ma_ca, mh.ma_hang
                FROM bao_cao_nang_suat bc
                JOIN line l ON l.id = bc.line_id
                JOIN ca_lam c ON c.id = bc.ca_id
                JOIN ma_hang mh ON mh.id = bc.ma_hang_id
                WHERE bc.line_id = ?';

        $params = [$line_id];
        $types = 'i';

        if (!$includeCompleted) {
            $sql .= " AND bc.trang_thai != 'completed'";
        }

        if (!empty($filters['ngay_tu'])) {
            $sql .= ' AND bc.ngay_bao_cao >= ?';
            $params[] = $filters['ngay_tu'];
            $types .= 's';
        }

        if (!empty($filters['ngay_den'])) {
            $sql .= ' AND bc.ngay_bao_cao <= ?';
            $params[] = $filters['ngay_den'];
            $types .= 's';
        }

        if (!empty($filters['ca_id'])) {
            $sql .= ' AND bc.ca_id = ?';
            $params[] = $filters['ca_id'];
            $types .= 'i';
        }

        if (!empty($filters['ma_hang_id'])) {
            $sql .= ' AND bc.ma_hang_id = ?';
            $params[] = $filters['ma_hang_id'];
            $types .= 'i';
        }

        $sql .= ' ORDER BY bc.tao_luc ASC';

        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        mysqli_stmt_close($stmt);

        return $list;
    }
}
