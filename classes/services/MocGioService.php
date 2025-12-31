<?php
require_once __DIR__ . '/../../config/Database.php';

class MocGioService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getNangSuat();
    }
    
    public function getList($ca_id = null, $line_id = null) {
        $sql = "SELECT mg.id, mg.ca_id, mg.line_id, mg.gio, mg.thu_tu, 
                       mg.so_phut_hieu_dung_luy_ke, mg.is_active,
                       c.ma_ca, c.ten_ca, l.ma_line, l.ten_line
                FROM moc_gio mg
                JOIN ca_lam c ON c.id = mg.ca_id
                LEFT JOIN line l ON l.id = mg.line_id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if ($ca_id !== null) {
            $sql .= " AND mg.ca_id = ?";
            $params[] = $ca_id;
            $types .= "i";
        }
        
        if ($line_id === 'default') {
            $sql .= " AND mg.line_id IS NULL";
        } elseif ($line_id !== null && $line_id !== '') {
            $sql .= " AND mg.line_id = ?";
            $params[] = intval($line_id);
            $types .= "i";
        }
        
        $sql .= " ORDER BY mg.ca_id, COALESCE(mg.line_id, 0), mg.thu_tu";
        
        if (count($params) > 0) {
            $stmt = mysqli_prepare($this->db, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($this->db, $sql);
        }
        
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['is_default'] = $row['line_id'] === null ? 1 : 0;
            $list[] = $row;
        }
        
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
        
        return $list;
    }
    
    public function get($id) {
        $stmt = mysqli_prepare($this->db, 
            "SELECT mg.*, c.ma_ca, c.ten_ca, l.ma_line, l.ten_line
             FROM moc_gio mg
             JOIN ca_lam c ON c.id = mg.ca_id
             LEFT JOIN line l ON l.id = mg.line_id
             WHERE mg.id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $mocGio = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $mocGio;
    }
    
    public function create($ca_id, $line_id, $gio, $thu_tu, $so_phut_hieu_dung_luy_ke) {
        $ca_id = intval($ca_id);
        $line_id = ($line_id === null || $line_id === '') ? null : intval($line_id);
        $gio = trim($gio);
        $thu_tu = intval($thu_tu);
        $so_phut_hieu_dung_luy_ke = intval($so_phut_hieu_dung_luy_ke);
        
        if (empty($gio)) {
            return ['success' => false, 'message' => 'Vui lòng nhập giờ'];
        }
        
        $checkStmt = mysqli_prepare($this->db,
            "SELECT id FROM moc_gio WHERE ca_id = ? AND (line_id = ? OR (line_id IS NULL AND ? IS NULL)) AND gio = ?"
        );
        mysqli_stmt_bind_param($checkStmt, "iiis", $ca_id, $line_id, $line_id, $gio);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Mốc giờ này đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);
        
        if ($line_id === null) {
            $stmt = mysqli_prepare($this->db,
                "INSERT INTO moc_gio (ca_id, line_id, gio, thu_tu, so_phut_hieu_dung_luy_ke, is_active)
                 VALUES (?, NULL, ?, ?, ?, 1)"
            );
            mysqli_stmt_bind_param($stmt, "isii", $ca_id, $gio, $thu_tu, $so_phut_hieu_dung_luy_ke);
        } else {
            $stmt = mysqli_prepare($this->db,
                "INSERT INTO moc_gio (ca_id, line_id, gio, thu_tu, so_phut_hieu_dung_luy_ke, is_active)
                 VALUES (?, ?, ?, ?, ?, 1)"
            );
            mysqli_stmt_bind_param($stmt, "iisii", $ca_id, $line_id, $gio, $thu_tu, $so_phut_hieu_dung_luy_ke);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($this->db);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Tạo mốc giờ thành công', 'id' => $id];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi tạo mốc giờ: ' . mysqli_error($this->db)];
    }
    
    public function update($id, $gio, $thu_tu, $so_phut_hieu_dung_luy_ke, $is_active) {
        $id = intval($id);
        $gio = trim($gio);
        $thu_tu = intval($thu_tu);
        $so_phut_hieu_dung_luy_ke = intval($so_phut_hieu_dung_luy_ke);
        $is_active = intval($is_active);
        
        if (empty($gio)) {
            return ['success' => false, 'message' => 'Vui lòng nhập giờ'];
        }
        
        $existing = $this->get($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Mốc giờ không tồn tại'];
        }
        
        $stmt = mysqli_prepare($this->db,
            "UPDATE moc_gio SET gio = ?, thu_tu = ?, so_phut_hieu_dung_luy_ke = ?, is_active = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "siiii", $gio, $thu_tu, $so_phut_hieu_dung_luy_ke, $is_active, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Cập nhật mốc giờ thành công'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật mốc giờ'];
    }
    
    public function delete($id) {
        $id = intval($id);
        
        $existing = $this->get($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Mốc giờ không tồn tại'];
        }
        
        if ($existing['line_id'] === null) {
            return ['success' => false, 'message' => 'Không thể xóa mốc giờ mặc định. Chỉ có thể xóa mốc giờ riêng của LINE.'];
        }
        
        $checkStmt = mysqli_prepare($this->db,
            "SELECT COUNT(*) as cnt FROM nhap_lieu_nang_suat WHERE moc_gio_id = ?"
        );
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $countRow = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);
        
        if ($countRow['cnt'] > 0) {
            return ['success' => false, 'message' => 'Không thể xóa mốc giờ đã có dữ liệu nhập liệu'];
        }
        
        $stmt = mysqli_prepare($this->db, "DELETE FROM moc_gio WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Xóa mốc giờ thành công'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi xóa mốc giờ'];
    }
    
    public function copyDefaultToLine($ca_id, $line_id) {
        $ca_id = intval($ca_id);
        $line_id = intval($line_id);
        
        if ($line_id <= 0) {
            return ['success' => false, 'message' => 'LINE không hợp lệ'];
        }
        
        $checkStmt = mysqli_prepare($this->db,
            "SELECT COUNT(*) as cnt FROM moc_gio WHERE ca_id = ? AND line_id = ?"
        );
        mysqli_stmt_bind_param($checkStmt, "ii", $ca_id, $line_id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $countRow = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);
        
        if ($countRow['cnt'] > 0) {
            return ['success' => false, 'message' => 'LINE này đã có mốc giờ riêng cho ca này'];
        }
        
        $defaultMocGio = [];
        $defaultStmt = mysqli_prepare($this->db,
            "SELECT gio, thu_tu, so_phut_hieu_dung_luy_ke FROM moc_gio WHERE ca_id = ? AND line_id IS NULL AND is_active = 1 ORDER BY thu_tu"
        );
        mysqli_stmt_bind_param($defaultStmt, "i", $ca_id);
        mysqli_stmt_execute($defaultStmt);
        $defaultResult = mysqli_stmt_get_result($defaultStmt);
        while ($row = mysqli_fetch_assoc($defaultResult)) {
            $defaultMocGio[] = $row;
        }
        mysqli_stmt_close($defaultStmt);
        
        if (count($defaultMocGio) === 0) {
            return ['success' => false, 'message' => 'Không có mốc giờ mặc định cho ca này'];
        }
        
        $insertStmt = mysqli_prepare($this->db,
            "INSERT INTO moc_gio (ca_id, line_id, gio, thu_tu, so_phut_hieu_dung_luy_ke, is_active)
             VALUES (?, ?, ?, ?, ?, 1)"
        );
        
        $insertedCount = 0;
        foreach ($defaultMocGio as $moc) {
            mysqli_stmt_bind_param($insertStmt, "iisii", 
                $ca_id, $line_id, $moc['gio'], $moc['thu_tu'], $moc['so_phut_hieu_dung_luy_ke']
            );
            if (mysqli_stmt_execute($insertStmt)) {
                $insertedCount++;
            }
        }
        mysqli_stmt_close($insertStmt);
        
        return [
            'success' => true, 
            'message' => "Đã copy {$insertedCount} mốc giờ sang LINE"
        ];
    }
    
    public function getCaList() {
        $result = mysqli_query($this->db,
            "SELECT id, ma_ca, ten_ca FROM ca_lam WHERE is_active = 1 ORDER BY id"
        );
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    public function getListBySetId($set_id) {
        $set_id = intval($set_id);
        
        $stmt = mysqli_prepare($this->db,
            "SELECT mg.id, mg.ca_id, mg.set_id, mg.gio, mg.thu_tu,
                    mg.so_phut_hieu_dung_luy_ke, mg.is_active,
                    c.ma_ca, c.ten_ca, mgs.ten_set
             FROM moc_gio mg
             JOIN ca_lam c ON c.id = mg.ca_id
             LEFT JOIN moc_gio_set mgs ON mgs.id = mg.set_id
             WHERE mg.set_id = ?
             ORDER BY mg.thu_tu"
        );
        mysqli_stmt_bind_param($stmt, "i", $set_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $list;
    }
    
    public function createWithSetId($ca_id, $set_id, $gio, $thu_tu, $so_phut_hieu_dung_luy_ke) {
        $ca_id = intval($ca_id);
        $set_id = intval($set_id);
        $gio = trim($gio);
        $thu_tu = intval($thu_tu);
        $so_phut_hieu_dung_luy_ke = intval($so_phut_hieu_dung_luy_ke);
        
        if (empty($gio)) {
            return ['success' => false, 'message' => 'Vui lòng nhập giờ'];
        }
        
        $checkStmt = mysqli_prepare($this->db,
            "SELECT id FROM moc_gio WHERE set_id = ? AND gio = ?"
        );
        mysqli_stmt_bind_param($checkStmt, "is", $set_id, $gio);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Mốc giờ này đã tồn tại trong preset'];
        }
        mysqli_stmt_close($checkStmt);
        
        $stmt = mysqli_prepare($this->db,
            "INSERT INTO moc_gio (ca_id, set_id, gio, thu_tu, so_phut_hieu_dung_luy_ke, is_active)
             VALUES (?, ?, ?, ?, ?, 1)"
        );
        mysqli_stmt_bind_param($stmt, "iisii", $ca_id, $set_id, $gio, $thu_tu, $so_phut_hieu_dung_luy_ke);
        
        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($this->db);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Tạo mốc giờ thành công', 'id' => $id];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi tạo mốc giờ: ' . mysqli_error($this->db)];
    }
    
    public function updateSetId($id, $set_id) {
        $id = intval($id);
        $set_id = ($set_id === null || $set_id === '') ? null : intval($set_id);
        
        $existing = $this->get($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Mốc giờ không tồn tại'];
        }
        
        if ($set_id === null) {
            $stmt = mysqli_prepare($this->db, "UPDATE moc_gio SET set_id = NULL WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
        } else {
            $stmt = mysqli_prepare($this->db, "UPDATE moc_gio SET set_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $set_id, $id);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Cập nhật set_id thành công'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật set_id'];
    }
}
