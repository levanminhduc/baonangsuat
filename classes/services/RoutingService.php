<?php
require_once __DIR__ . '/../../config/Database.php';

class RoutingService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getNangSuat();
    }
    
    public function getList($ma_hang_id) {
        $ma_hang_id = intval($ma_hang_id);
        
        $sql = "SELECT mhcd.id, mhcd.ma_hang_id, mhcd.line_id, mhcd.cong_doan_id, mhcd.thu_tu,
                       mhcd.bat_buoc, mhcd.la_cong_doan_tinh_luy_ke, mhcd.hieu_luc_tu, mhcd.hieu_luc_den, mhcd.ghi_chu,
                       cd.ma_cong_doan, cd.ten_cong_doan, l.ma_line
                FROM ma_hang_cong_doan mhcd
                JOIN cong_doan cd ON cd.id = mhcd.cong_doan_id
                LEFT JOIN line l ON l.id = mhcd.line_id
                WHERE mhcd.ma_hang_id = ?
                ORDER BY mhcd.thu_tu";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $ma_hang_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $list;
    }
    
    public function get($id) {
        $stmt = mysqli_prepare($this->db, "SELECT * FROM ma_hang_cong_doan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $routing = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $routing;
    }
    
    public function add($ma_hang_id, $cong_doan_id, $thu_tu, $bat_buoc = 1, $la_cong_doan_tinh_luy_ke = 0, $line_id = null, $ghi_chu = '') {
        $ma_hang_id = intval($ma_hang_id);
        $cong_doan_id = intval($cong_doan_id);
        $thu_tu = intval($thu_tu);
        $bat_buoc = intval($bat_buoc);
        $la_cong_doan_tinh_luy_ke = intval($la_cong_doan_tinh_luy_ke);
        $line_id = $line_id ? intval($line_id) : null;
        $ghi_chu = trim($ghi_chu);
        
        if ($ma_hang_id <= 0 || $cong_doan_id <= 0) {
            return ['success' => false, 'message' => 'Dữ liệu không hợp lệ'];
        }
        
        $checkStmt = mysqli_prepare($this->db, "SELECT id FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND cong_doan_id = ?");
        mysqli_stmt_bind_param($checkStmt, "ii", $ma_hang_id, $cong_doan_id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Routing này đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);
        
        $thuTuStmt = mysqli_prepare($this->db, "SELECT id FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND (line_id = ? OR (line_id IS NULL AND ? IS NULL)) AND thu_tu = ?");
        $nullLineId = null;
        mysqli_stmt_bind_param($thuTuStmt, "iiii", $ma_hang_id, $line_id, $line_id, $thu_tu);
        mysqli_stmt_execute($thuTuStmt);
        $thuTuResult = mysqli_stmt_get_result($thuTuStmt);
        if (mysqli_fetch_assoc($thuTuResult)) {
            mysqli_stmt_close($thuTuStmt);
            return ['success' => false, 'message' => 'Thứ tự công đoạn đã tồn tại trong line này'];
        }
        mysqli_stmt_close($thuTuStmt);
        
        $stmt = mysqli_prepare($this->db, "INSERT INTO ma_hang_cong_doan (ma_hang_id, cong_doan_id, thu_tu, bat_buoc, la_cong_doan_tinh_luy_ke, line_id, ghi_chu) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiiiiis", $ma_hang_id, $cong_doan_id, $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id, $ghi_chu);
        
        if (mysqli_stmt_execute($stmt)) {
            $newId = mysqli_insert_id($this->db);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Thêm routing thành công', 'id' => $newId];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi thêm routing: ' . mysqli_error($this->db)];
    }
    
    public function update($id, $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id = null, $ghi_chu = '', $expectedVersion = null) {
        $id = intval($id);
        $thu_tu = intval($thu_tu);
        $bat_buoc = intval($bat_buoc);
        $la_cong_doan_tinh_luy_ke = intval($la_cong_doan_tinh_luy_ke);
        $line_id = $line_id ? intval($line_id) : null;
        $ghi_chu = trim($ghi_chu);
        
        $existingStmt = mysqli_prepare($this->db, "SELECT id, version FROM ma_hang_cong_doan WHERE id = ?");
        mysqli_stmt_bind_param($existingStmt, "i", $id);
        mysqli_stmt_execute($existingStmt);
        $existingResult = mysqli_stmt_get_result($existingStmt);
        $existing = mysqli_fetch_assoc($existingResult);
        mysqli_stmt_close($existingStmt);
        
        if (!$existing) {
            return ['success' => false, 'message' => 'Không tìm thấy routing'];
        }
        
        if ($expectedVersion !== null && intval($existing['version']) !== $expectedVersion) {
            return ['success' => false, 'message' => 'Dữ liệu đã được thay đổi bởi người khác. Vui lòng tải lại trang.', 'error_code' => 'VERSION_CONFLICT'];
        }
        
        $ma_hang_id = intval($existing['ma_hang_id'] ?? 0);
        if ($ma_hang_id === 0) {
            $maHangStmt = mysqli_prepare($this->db, "SELECT ma_hang_id FROM ma_hang_cong_doan WHERE id = ?");
            mysqli_stmt_bind_param($maHangStmt, "i", $id);
            mysqli_stmt_execute($maHangStmt);
            $maHangResult = mysqli_stmt_get_result($maHangStmt);
            $maHangRow = mysqli_fetch_assoc($maHangResult);
            mysqli_stmt_close($maHangStmt);
            $ma_hang_id = intval($maHangRow['ma_hang_id']);
        }
        
        $thuTuStmt = mysqli_prepare($this->db, "SELECT id FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND (line_id = ? OR (line_id IS NULL AND ? IS NULL)) AND thu_tu = ? AND id != ?");
        mysqli_stmt_bind_param($thuTuStmt, "iiiii", $ma_hang_id, $line_id, $line_id, $thu_tu, $id);
        mysqli_stmt_execute($thuTuStmt);
        $thuTuResult = mysqli_stmt_get_result($thuTuStmt);
        if (mysqli_fetch_assoc($thuTuResult)) {
            mysqli_stmt_close($thuTuStmt);
            return ['success' => false, 'message' => 'Thứ tự công đoạn đã tồn tại trong line này'];
        }
        mysqli_stmt_close($thuTuStmt);
        
        $newVersion = intval($existing['version']) + 1;
        $stmt = mysqli_prepare($this->db, "UPDATE ma_hang_cong_doan SET thu_tu = ?, bat_buoc = ?, la_cong_doan_tinh_luy_ke = ?, line_id = ?, ghi_chu = ?, version = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "iiiisii", $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id, $ghi_chu, $newVersion, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Cập nhật routing thành công'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật routing'];
    }
    
    public function remove($id) {
        $id = intval($id);
        
        $stmt = mysqli_prepare($this->db, "DELETE FROM ma_hang_cong_doan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Xóa routing thành công'];
            }
            return ['success' => false, 'message' => 'Không tìm thấy routing'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi xóa routing'];
    }
}
