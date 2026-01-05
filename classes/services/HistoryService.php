<?php
require_once __DIR__ . '/../../config/Database.php';

class HistoryService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getNangSuat();
    }
    
    public function getReportList($filters = []) {
        $sql = "SELECT bc.id, bc.ngay_bao_cao, bc.so_lao_dong, bc.ctns, bc.ctns as chi_tieu, bc.ct_gio,
                       bc.tong_phut_hieu_dung, bc.trang_thai,
                       l.id as line_id, l.ma_line, l.ten_line,
                       c.ma_ca, c.ten_ca,
                       mh.ma_hang, mh.ten_hang,
                       bc.tao_boi, bc.tao_luc,
                       COALESCE((
                           SELECT SUM(nl.so_luong)
                           FROM nhap_lieu_nang_suat nl
                           JOIN ma_hang_cong_doan mhcd ON mhcd.cong_doan_id = nl.cong_doan_id
                               AND mhcd.ma_hang_id = bc.ma_hang_id
                           WHERE nl.bao_cao_id = bc.id
                               AND mhcd.la_cong_doan_tinh_luy_ke = 1
                       ), 0) as thuc_te
                FROM bao_cao_nang_suat bc
                JOIN line l ON l.id = bc.line_id
                JOIN ca_lam c ON c.id = bc.ca_id
                JOIN ma_hang mh ON mh.id = bc.ma_hang_id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['line_id'])) {
            $sql .= " AND bc.line_id = ?";
            $params[] = intval($filters['line_id']);
            $types .= "i";
        }
        
        if (!empty($filters['ngay_tu'])) {
            $sql .= " AND bc.ngay_bao_cao >= ?";
            $params[] = $filters['ngay_tu'];
            $types .= "s";
        }
        
        if (!empty($filters['ngay_den'])) {
            $sql .= " AND bc.ngay_bao_cao <= ?";
            $params[] = $filters['ngay_den'];
            $types .= "s";
        }
        
        if (!empty($filters['ca_id'])) {
            $sql .= " AND bc.ca_id = ?";
            $params[] = intval($filters['ca_id']);
            $types .= "i";
        }
        
        if (!empty($filters['ma_hang_id'])) {
            $sql .= " AND bc.ma_hang_id = ?";
            $params[] = intval($filters['ma_hang_id']);
            $types .= "i";
        }
        
        if (!empty($filters['trang_thai'])) {
            $sql .= " AND bc.trang_thai = ?";
            $params[] = $filters['trang_thai'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY bc.ngay_bao_cao DESC, bc.id DESC";
        
        $page = isset($filters['page']) ? max(1, intval($filters['page'])) : 1;
        $pageSize = isset($filters['page_size']) ? min(100, max(1, intval($filters['page_size']))) : 20;
        $offset = ($page - 1) * $pageSize;
        
        $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as subquery";
        
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $pageSize;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = mysqli_prepare($this->db, $sql);
        if (!empty($types)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        mysqli_stmt_close($stmt);
        
        $total = $this->countTotal($countSql, array_slice($params, 0, -2), substr($types, 0, -2));
        
        return [
            'data' => $list,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_pages' => ceil($total / $pageSize)
            ]
        ];
    }
    
    private function countTotal($countSql, $params, $types) {
        $baseSql = str_replace("SELECT COUNT(*) as total FROM (", "", $countSql);
        $baseSql = substr($baseSql, 0, -13);
        
        $countQuery = "SELECT COUNT(*) as total FROM bao_cao_nang_suat bc
                       JOIN line l ON l.id = bc.line_id
                       JOIN ca_lam c ON c.id = bc.ca_id
                       JOIN ma_hang mh ON mh.id = bc.ma_hang_id
                       WHERE 1=1";
        
        $conditions = "";
        $paramIndex = 0;
        
        if (strpos($baseSql, "bc.line_id = ?") !== false) {
            $conditions .= " AND bc.line_id = ?";
        }
        if (strpos($baseSql, "bc.ngay_bao_cao >= ?") !== false) {
            $conditions .= " AND bc.ngay_bao_cao >= ?";
        }
        if (strpos($baseSql, "bc.ngay_bao_cao <= ?") !== false) {
            $conditions .= " AND bc.ngay_bao_cao <= ?";
        }
        if (strpos($baseSql, "bc.ca_id = ?") !== false) {
            $conditions .= " AND bc.ca_id = ?";
        }
        if (strpos($baseSql, "bc.ma_hang_id = ?") !== false) {
            $conditions .= " AND bc.ma_hang_id = ?";
        }
        if (strpos($baseSql, "bc.trang_thai = ?") !== false) {
            $conditions .= " AND bc.trang_thai = ?";
        }
        
        $countQuery .= $conditions;
        
        $stmt = mysqli_prepare($this->db, $countQuery);
        if (!empty($types) && !empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return intval($row['total'] ?? 0);
    }
    
    public function getReportDetail($reportId) {
        $stmt = mysqli_prepare($this->db,
            "SELECT bc.*,
                    l.ma_line, l.ten_line,
                    c.ma_ca, c.ten_ca,
                    mh.ma_hang, mh.ten_hang
             FROM bao_cao_nang_suat bc
             JOIN line l ON l.id = bc.line_id
             JOIN ca_lam c ON c.id = bc.ca_id
             JOIN ma_hang mh ON mh.id = bc.ma_hang_id
             WHERE bc.id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $reportId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$baoCao) {
            return null;
        }
        
        $baoCao['routing'] = $this->getRouting($baoCao['ma_hang_id'], $baoCao['line_id']);
        $baoCao['moc_gio_list'] = $this->getMocGioList($baoCao['ca_id'], $baoCao['line_id']);
        $baoCao['entries'] = $this->getEntries($reportId);
        $baoCao['chi_tieu_luy_ke'] = $this->calculateChiTieuLuyKe($baoCao);
        
        if (!empty($baoCao['ket_qua_luy_ke'])) {
            $baoCao['ket_qua_luy_ke'] = json_decode($baoCao['ket_qua_luy_ke'], true);
            $baoCao['ket_qua_luy_ke_is_fallback'] = 0;
        } else {
            $baoCao['ket_qua_luy_ke'] = $this->calculateKetQuaLuyKeFallback($baoCao);
            $baoCao['ket_qua_luy_ke_is_fallback'] = 1;
            error_log("HistoryService fallback: bao_cao_id=$reportId, reason=missing_ket_qua_luy_ke");
        }
        
        return $baoCao;
    }
    
    private function calculateChiTieuLuyKe($baoCao) {
        $ctGio = floatval($baoCao['ct_gio'] ?? 0);
        $chiTieuLuyKe = [];
        
        foreach ($baoCao['moc_gio_list'] as $mocGio) {
            $soPhutHieuDungLuyKe = intval($mocGio['so_phut_hieu_dung_luy_ke'] ?? 0);
            $chiTieu = round($ctGio * $soPhutHieuDungLuyKe / 60);
            $chiTieuLuyKe[$mocGio['id']] = $chiTieu;
        }
        
        return $chiTieuLuyKe;
    }
    
    private function calculateKetQuaLuyKeFallback($baoCao) {
        $ctns = intval($baoCao['ctns']);
        $tongPhut = intval($baoCao['tong_phut_hieu_dung']);
        $ctGio = floatval($baoCao['ct_gio']);
        
        $mocGioList = $baoCao['moc_gio_list'];
        $lastMoc = count($mocGioList) > 0 ? $mocGioList[count($mocGioList) - 1] : null;
        $soPhutHieuDungLuyKeCuoi = $lastMoc ? intval($lastMoc['so_phut_hieu_dung_luy_ke']) : 0;
        
        $chiTieuLuyKeTong = 0;
        if ($tongPhut > 0 && $ctns > 0) {
            $chiTieuLuyKeTong = round($ctns * $soPhutHieuDungLuyKeCuoi / $tongPhut);
        }
        
        $luyKeThucTeTong = 0;
        $congDoanDetails = [];
        
        foreach ($baoCao['routing'] as $cd) {
            $congDoanId = $cd['cong_doan_id'];
            $laCongDoanTinhLuyKe = intval($cd['la_cong_doan_tinh_luy_ke']);
            
            $luyKeCongDoan = 0;
            if ($lastMoc) {
                $key = $congDoanId . '_' . $lastMoc['id'];
                $entry = $baoCao['entries'][$key] ?? null;
                if ($entry) {
                    $luyKeCongDoan = intval($entry['so_luong']);
                }
            }
            
            $chiTieuCongDoan = $chiTieuLuyKeTong;
            
            $trangThai = 'na';
            if ($chiTieuCongDoan > 0) {
                $trangThai = $luyKeCongDoan >= $chiTieuCongDoan ? 'dat' : 'chua_dat';
            }
            
            if ($laCongDoanTinhLuyKe === 1) {
                $luyKeThucTeTong = $luyKeCongDoan;
            }
            
            $congDoanDetails[] = [
                'cong_doan_id' => $congDoanId,
                'cong_doan_ten' => $cd['ten_cong_doan'],
                'la_cong_doan_tinh_luy_ke' => $laCongDoanTinhLuyKe,
                'luy_ke_thuc_te' => $luyKeCongDoan,
                'chi_tieu_luy_ke' => $chiTieuCongDoan,
                'trang_thai' => $trangThai
            ];
        }
        
        $trangThaiTong = 'na';
        if ($chiTieuLuyKeTong > 0) {
            $trangThaiTong = $luyKeThucTeTong >= $chiTieuLuyKeTong ? 'dat' : 'chua_dat';
        }
        
        return [
            'version' => 1,
            'generated_at' => date('c'),
            'source' => [
                'bao_cao_id' => intval($baoCao['id']),
                'ngay' => $baoCao['ngay_bao_cao'],
                'line_id' => intval($baoCao['line_id']),
                'ca_id' => intval($baoCao['ca_id']),
                'ma_hang_id' => intval($baoCao['ma_hang_id'])
            ],
            'inputs' => [
                'so_lao_dong' => intval($baoCao['so_lao_dong']),
                'ctns' => $ctns,
                'tong_phut_hieu_dung' => $tongPhut
            ],
            'tong_hop' => [
                'ct_gio' => $ctGio,
                'luy_ke_thuc_te' => $luyKeThucTeTong,
                'chi_tieu_luy_ke' => $chiTieuLuyKeTong,
                'trang_thai' => $trangThaiTong
            ],
            'cong_doan' => $congDoanDetails
        ];
    }
    
    private function getRouting($ma_hang_id, $line_id = null) {
        $sql = "SELECT 
                    mhd.id, mhd.cong_doan_id, cd.ma_cong_doan, cd.ten_cong_doan, 
                    mhd.thu_tu, mhd.bat_buoc, mhd.la_cong_doan_tinh_luy_ke, mhd.ghi_chu
                FROM ma_hang_cong_doan mhd
                JOIN cong_doan cd ON cd.id = mhd.cong_doan_id
                WHERE mhd.ma_hang_id = ?
                  AND (mhd.line_id = ? OR mhd.line_id IS NULL)
                  AND (mhd.hieu_luc_tu IS NULL OR mhd.hieu_luc_tu <= CURDATE())
                  AND (mhd.hieu_luc_den IS NULL OR mhd.hieu_luc_den >= CURDATE())
                  AND cd.is_active = 1
                ORDER BY 
                    CASE WHEN mhd.line_id = ? THEN 0 ELSE 1 END,
                    mhd.thu_tu";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $ma_hang_id, $line_id, $line_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $list = [];
        $seenCongDoan = [];
        while ($row = mysqli_fetch_assoc($result)) {
            if (!isset($seenCongDoan[$row['cong_doan_id']])) {
                $list[] = $row;
                $seenCongDoan[$row['cong_doan_id']] = true;
            }
        }
        mysqli_stmt_close($stmt);
        return $list;
    }
    
    private function getMocGioList($ca_id, $line_id = null) {
        if ($line_id !== null) {
            $stmt = mysqli_prepare($this->db,
                "SELECT id, gio, thu_tu, so_phut_hieu_dung_luy_ke
                 FROM moc_gio
                 WHERE ca_id = ? AND line_id = ? AND is_active = 1
                 ORDER BY thu_tu"
            );
            mysqli_stmt_bind_param($stmt, "ii", $ca_id, $line_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $list = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $list[] = $row;
            }
            mysqli_stmt_close($stmt);
            
            if (count($list) > 0) {
                return $list;
            }
        }
        
        $stmt = mysqli_prepare($this->db,
            "SELECT id, gio, thu_tu, so_phut_hieu_dung_luy_ke
             FROM moc_gio
             WHERE ca_id = ? AND line_id IS NULL AND is_active = 1
             ORDER BY thu_tu"
        );
        mysqli_stmt_bind_param($stmt, "i", $ca_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        mysqli_stmt_close($stmt);
        
        return $list;
    }
    
    private function getEntries($bao_cao_id) {
        $stmt = mysqli_prepare($this->db, 
            "SELECT nl.id, nl.cong_doan_id, nl.moc_gio_id, nl.so_luong, nl.kieu_nhap, nl.ghi_chu,
                    cd.ma_cong_doan, cd.ten_cong_doan,
                    mg.gio as moc_gio
             FROM nhap_lieu_nang_suat nl
             JOIN cong_doan cd ON cd.id = nl.cong_doan_id
             JOIN moc_gio mg ON mg.id = nl.moc_gio_id
             WHERE nl.bao_cao_id = ?
             ORDER BY cd.id, mg.thu_tu"
        );
        mysqli_stmt_bind_param($stmt, "i", $bao_cao_id);
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
    
    public function getReportLineId($reportId) {
        $stmt = mysqli_prepare($this->db, "SELECT line_id FROM bao_cao_nang_suat WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $reportId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row ? intval($row['line_id']) : null;
    }
}
