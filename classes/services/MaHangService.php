<?php
require_once __DIR__ . '/../../config/Database.php';

class MaHangService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getNangSuat();
    }
    
    public function getList() {
        $result = mysqli_query($this->db, "SELECT id, ma_hang, ten_hang, is_active FROM ma_hang ORDER BY ma_hang");
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    public function get($id) {
        $stmt = mysqli_prepare($this->db, "SELECT id, ma_hang, ten_hang, is_active FROM ma_hang WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $maHang = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $maHang;
    }
    
    public function create($ma_hang, $ten_hang) {
        $ma_hang = strtoupper(trim($ma_hang));
        $ten_hang = trim($ten_hang);
        
        if (empty($ma_hang) || empty($ten_hang)) {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'];
        }
        
        $checkStmt = mysqli_prepare($this->db, "SELECT id FROM ma_hang WHERE ma_hang = ?");
        mysqli_stmt_bind_param($checkStmt, "s", $ma_hang);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Mã hàng đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);
        
        $stmt = mysqli_prepare($this->db, "INSERT INTO ma_hang (ma_hang, ten_hang, is_active) VALUES (?, ?, 1)");
        mysqli_stmt_bind_param($stmt, "ss", $ma_hang, $ten_hang);
        
        if (mysqli_stmt_execute($stmt)) {
            $newId = mysqli_insert_id($this->db);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Tạo mã hàng thành công', 'id' => $newId];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi tạo mã hàng: ' . mysqli_error($this->db)];
    }
    
    public function update($id, $ma_hang, $ten_hang, $is_active) {
        $id = intval($id);
        $ma_hang = strtoupper(trim($ma_hang));
        $ten_hang = trim($ten_hang);
        $is_active = intval($is_active);
        
        if (empty($ma_hang) || empty($ten_hang)) {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'];
        }
        
        $checkStmt = mysqli_prepare($this->db, "SELECT id FROM ma_hang WHERE ma_hang = ? AND id != ?");
        mysqli_stmt_bind_param($checkStmt, "si", $ma_hang, $id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Mã hàng đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);
        
        $stmt = mysqli_prepare($this->db, "UPDATE ma_hang SET ma_hang = ?, ten_hang = ?, is_active = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssii", $ma_hang, $ten_hang, $is_active, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Cập nhật mã hàng thành công'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật mã hàng'];
    }
    
    public function delete($id) {
        $id = intval($id);
        
        $checkStmt = mysqli_prepare($this->db, "SELECT COUNT(*) as cnt FROM ma_hang_cong_doan WHERE ma_hang_id = ?");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $row = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);
        
        if ($row['cnt'] > 0) {
            return ['success' => false, 'message' => 'Không thể xóa mã hàng đang có routing'];
        }
        
        $checkStmt2 = mysqli_prepare($this->db, "SELECT COUNT(*) as cnt FROM bao_cao_nang_suat WHERE ma_hang_id = ?");
        mysqli_stmt_bind_param($checkStmt2, "i", $id);
        mysqli_stmt_execute($checkStmt2);
        $checkResult2 = mysqli_stmt_get_result($checkStmt2);
        $row2 = mysqli_fetch_assoc($checkResult2);
        mysqli_stmt_close($checkStmt2);
        
        if ($row2['cnt'] > 0) {
            return ['success' => false, 'message' => 'Không thể xóa mã hàng đã có báo cáo'];
        }
        
        $stmt = mysqli_prepare($this->db, "DELETE FROM ma_hang WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Xóa mã hàng thành công'];
            }
            return ['success' => false, 'message' => 'Không tìm thấy mã hàng'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi xóa mã hàng'];
    }
}
