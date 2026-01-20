<?php
require_once __DIR__ . '/../../config/Database.php';

class CongDoanService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getNangSuat();
    }
    
    public function getList() {
        $result = mysqli_query($this->db, "SELECT id, ma_cong_doan, ten_cong_doan, is_active, la_cong_doan_thanh_pham FROM cong_doan ORDER BY ma_cong_doan");
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    public function get($id) {
        $stmt = mysqli_prepare($this->db, "SELECT id, ma_cong_doan, ten_cong_doan, is_active, la_cong_doan_thanh_pham FROM cong_doan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $congDoan = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $congDoan;
    }
    
    public function create($ma_cong_doan, $ten_cong_doan, $la_cong_doan_thanh_pham = 0) {
        $ma_cong_doan = strtoupper(trim($ma_cong_doan));
        $ten_cong_doan = trim($ten_cong_doan);
        $la_cong_doan_thanh_pham = intval($la_cong_doan_thanh_pham);
        
        if (empty($ma_cong_doan) || empty($ten_cong_doan)) {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'];
        }
        
        $checkStmt = mysqli_prepare($this->db, "SELECT id FROM cong_doan WHERE ma_cong_doan = ?");
        mysqli_stmt_bind_param($checkStmt, "s", $ma_cong_doan);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Mã công đoạn đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);
        
        $stmt = mysqli_prepare($this->db, "INSERT INTO cong_doan (ma_cong_doan, ten_cong_doan, is_active, la_cong_doan_thanh_pham) VALUES (?, ?, 1, ?)");
        mysqli_stmt_bind_param($stmt, "ssi", $ma_cong_doan, $ten_cong_doan, $la_cong_doan_thanh_pham);
        
        if (mysqli_stmt_execute($stmt)) {
            $newId = mysqli_insert_id($this->db);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Tạo công đoạn thành công', 'id' => $newId];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi tạo công đoạn: ' . mysqli_error($this->db)];
    }
    
    public function update($id, $ma_cong_doan, $ten_cong_doan, $is_active, $la_cong_doan_thanh_pham = 0) {
        $id = intval($id);
        $ma_cong_doan = strtoupper(trim($ma_cong_doan));
        $ten_cong_doan = trim($ten_cong_doan);
        $is_active = intval($is_active);
        $la_cong_doan_thanh_pham = intval($la_cong_doan_thanh_pham);
        
        if (empty($ma_cong_doan) || empty($ten_cong_doan)) {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'];
        }
        
        $checkStmt = mysqli_prepare($this->db, "SELECT id FROM cong_doan WHERE ma_cong_doan = ? AND id != ?");
        mysqli_stmt_bind_param($checkStmt, "si", $ma_cong_doan, $id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Mã công đoạn đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);
        
        $stmt = mysqli_prepare($this->db, "UPDATE cong_doan SET ma_cong_doan = ?, ten_cong_doan = ?, is_active = ?, la_cong_doan_thanh_pham = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssiii", $ma_cong_doan, $ten_cong_doan, $is_active, $la_cong_doan_thanh_pham, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Cập nhật công đoạn thành công'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật công đoạn'];
    }
    
    public function delete($id) {
        $id = intval($id);
        
        $checkStmt = mysqli_prepare($this->db, "SELECT COUNT(*) as cnt FROM ma_hang_cong_doan WHERE cong_doan_id = ?");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $row = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);
        
        if ($row['cnt'] > 0) {
            return ['success' => false, 'message' => 'Không thể xóa công đoạn đang có routing'];
        }
        
        $nhapLieuStmt = mysqli_prepare($this->db, "SELECT COUNT(*) as cnt FROM nhap_lieu_nang_suat WHERE cong_doan_id = ?");
        mysqli_stmt_bind_param($nhapLieuStmt, "i", $id);
        mysqli_stmt_execute($nhapLieuStmt);
        $nhapLieuResult = mysqli_stmt_get_result($nhapLieuStmt);
        $nhapLieuRow = mysqli_fetch_assoc($nhapLieuResult);
        mysqli_stmt_close($nhapLieuStmt);
        
        if ($nhapLieuRow['cnt'] > 0) {
            return ['success' => false, 'message' => 'Không thể xóa công đoạn đang có dữ liệu nhập liệu năng suất'];
        }
        
        $ketQuaLuyKeStmt = mysqli_prepare($this->db, "SELECT ket_qua_luy_ke FROM bao_cao_nang_suat WHERE ket_qua_luy_ke IS NOT NULL");
        mysqli_stmt_execute($ketQuaLuyKeStmt);
        $ketQuaResult = mysqli_stmt_get_result($ketQuaLuyKeStmt);
        $hasReference = false;
        while ($ketQuaRow = mysqli_fetch_assoc($ketQuaResult)) {
            $ketQuaLuyKe = json_decode($ketQuaRow['ket_qua_luy_ke'], true);
            if (is_array($ketQuaLuyKe)) {
                foreach ($ketQuaLuyKe as $entry) {
                    if (isset($entry['cong_doan_id']) && intval($entry['cong_doan_id']) === $id) {
                        $hasReference = true;
                        break 2;
                    }
                }
            }
        }
        mysqli_stmt_close($ketQuaLuyKeStmt);
        
        if ($hasReference) {
            return ['success' => false, 'message' => 'Không thể xóa công đoạn đang được tham chiếu trong kết quả lũy kế của báo cáo'];
        }
        
        $stmt = mysqli_prepare($this->db, "DELETE FROM cong_doan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Xóa công đoạn thành công'];
            }
            return ['success' => false, 'message' => 'Không tìm thấy công đoạn'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi xóa công đoạn'];
    }
}
