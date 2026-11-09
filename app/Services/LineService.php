<?php
require_once __DIR__ . '/../../config/Database.php';

class LineService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getNangSuat();
    }
    
    public function getList() {
        $result = mysqli_query($this->db, "SELECT id, ma_line, ten_line, is_active FROM line ORDER BY ma_line");
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    public function get($id) {
        $stmt = mysqli_prepare($this->db, "SELECT id, ma_line, ten_line, is_active FROM line WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $line = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $line;
    }
    
    public function create($ma_line, $ten_line) {
        $ma_line = strtoupper(trim($ma_line));
        $ten_line = trim($ten_line);
        
        if (empty($ma_line) || empty($ten_line)) {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'];
        }
        
        $checkStmt = mysqli_prepare($this->db, "SELECT id FROM line WHERE ma_line = ?");
        mysqli_stmt_bind_param($checkStmt, "s", $ma_line);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Mã LINE đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);
        
        $stmt = mysqli_prepare($this->db, "INSERT INTO line (ma_line, ten_line, is_active) VALUES (?, ?, 1)");
        mysqli_stmt_bind_param($stmt, "ss", $ma_line, $ten_line);
        
        if (mysqli_stmt_execute($stmt)) {
            $newId = mysqli_insert_id($this->db);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Tạo LINE thành công', 'id' => $newId];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi tạo LINE: ' . mysqli_error($this->db)];
    }
    
    public function update($id, $ma_line, $ten_line, $is_active) {
        $id = intval($id);
        $ma_line = strtoupper(trim($ma_line));
        $ten_line = trim($ten_line);
        $is_active = intval($is_active);
        
        if (empty($ma_line) || empty($ten_line)) {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'];
        }
        
        $checkStmt = mysqli_prepare($this->db, "SELECT id FROM line WHERE ma_line = ? AND id != ?");
        mysqli_stmt_bind_param($checkStmt, "si", $ma_line, $id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Mã LINE đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);
        
        $stmt = mysqli_prepare($this->db, "UPDATE line SET ma_line = ?, ten_line = ?, is_active = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssii", $ma_line, $ten_line, $is_active, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Cập nhật LINE thành công'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật LINE'];
    }
    
    public function delete($id) {
        $id = intval($id);
        
        $checkStmt = mysqli_prepare($this->db, "SELECT COUNT(*) as cnt FROM user_line WHERE line_id = ?");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $row = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);
        
        if ($row['cnt'] > 0) {
            return ['success' => false, 'message' => 'Không thể xóa LINE đang có user mapping'];
        }
        
        $checkStmt2 = mysqli_prepare($this->db, "SELECT COUNT(*) as cnt FROM bao_cao_nang_suat WHERE line_id = ?");
        mysqli_stmt_bind_param($checkStmt2, "i", $id);
        mysqli_stmt_execute($checkStmt2);
        $checkResult2 = mysqli_stmt_get_result($checkStmt2);
        $row2 = mysqli_fetch_assoc($checkResult2);
        mysqli_stmt_close($checkStmt2);
        
        if ($row2['cnt'] > 0) {
            return ['success' => false, 'message' => 'Không thể xóa LINE đã có báo cáo'];
        }
        
        $stmt = mysqli_prepare($this->db, "DELETE FROM line WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Xóa LINE thành công'];
            }
            return ['success' => false, 'message' => 'Không tìm thấy LINE'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi xóa LINE'];
    }
}
