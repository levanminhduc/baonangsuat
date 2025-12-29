<?php
require_once __DIR__ . '/../config/Database.php';

class AdminService {
    private $db;
    private $dbMysqli;
    private $dbNhanSu;
    
    public function __construct() {
        $this->db = Database::getNangSuat();
        $this->dbMysqli = Database::getMysqli();
        $this->dbNhanSu = Database::getNhanSu();
    }
    
    public function getAllUsers() {
        $result = mysqli_query($this->dbMysqli, "SELECT id, name, role FROM user ORDER BY name");
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    public function getUsersWithInfo() {
        $users = $this->getAllUsers();
        
        if (empty($users)) {
            return [];
        }
        
        $maNvList = array_map(function($u) { return strtoupper(trim($u['name'])); }, $users);
        $placeholders = str_repeat('?,', count($maNvList) - 1) . '?';
        
        $stmt = mysqli_prepare($this->dbNhanSu,
            "SELECT UPPER(ma_nv) as ma_nv, ho_ten FROM nhan_vien WHERE UPPER(ma_nv) IN ($placeholders)"
        );
        
        $types = str_repeat('s', count($maNvList));
        mysqli_stmt_bind_param($stmt, $types, ...$maNvList);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $nhanVienMap = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $nhanVienMap[$row['ma_nv']] = $row['ho_ten'];
        }
        mysqli_stmt_close($stmt);
        
        foreach ($users as &$user) {
            $maNvUpper = strtoupper(trim($user['name']));
            $user['ho_ten'] = $nhanVienMap[$maNvUpper] ?? null;
        }
        
        return $users;
    }
    
    public function getUserLineListWithInfo() {
        $sql = "SELECT ul.ma_nv, ul.line_id, l.ma_line, l.ten_line
                FROM user_line ul
                JOIN line l ON l.id = ul.line_id
                ORDER BY ul.ma_nv, l.ma_line";
        $result = mysqli_query($this->db, $sql);
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        
        if (empty($list)) {
            return [];
        }
        
        $maNvList = array_unique(array_map(function($ul) { return strtoupper(trim($ul['ma_nv'])); }, $list));
        $maNvArray = array_values($maNvList);
        
        if (!empty($maNvArray)) {
            $placeholders = str_repeat('?,', count($maNvArray) - 1) . '?';
            $stmt = mysqli_prepare($this->dbNhanSu,
                "SELECT UPPER(ma_nv) as ma_nv, ho_ten FROM nhan_vien WHERE UPPER(ma_nv) IN ($placeholders)"
            );
            $types = str_repeat('s', count($maNvArray));
            mysqli_stmt_bind_param($stmt, $types, ...$maNvArray);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $nhanVienMap = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $nhanVienMap[$row['ma_nv']] = $row['ho_ten'];
            }
            mysqli_stmt_close($stmt);
            
            foreach ($list as &$ul) {
                $maNvUpper = strtoupper(trim($ul['ma_nv']));
                $ul['ho_ten'] = $nhanVienMap[$maNvUpper] ?? null;
            }
        }
        
        return $list;
    }
    
    public function getLineList() {
        $result = mysqli_query($this->db, "SELECT id, ma_line, ten_line, is_active FROM line ORDER BY ma_line");
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    public function getUserLineList() {
        $sql = "SELECT ul.ma_nv, ul.line_id, l.ma_line, l.ten_line 
                FROM user_line ul 
                JOIN line l ON l.id = ul.line_id 
                ORDER BY ul.ma_nv, l.ma_line";
        $result = mysqli_query($this->db, $sql);
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    public function addUserLine($ma_nv, $line_id) {
        $ma_nv = strtoupper(trim($ma_nv));
        $line_id = intval($line_id);
        
        if (empty($ma_nv) || $line_id <= 0) {
            return ['success' => false, 'message' => 'Dữ liệu không hợp lệ'];
        }
        
        $checkStmt = mysqli_prepare($this->db, "SELECT 1 FROM user_line WHERE ma_nv = ? AND line_id = ?");
        mysqli_stmt_bind_param($checkStmt, "si", $ma_nv, $line_id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Mapping đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);
        
        $stmt = mysqli_prepare($this->db, "INSERT INTO user_line (ma_nv, line_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $ma_nv, $line_id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Thêm mapping thành công'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi thêm mapping: ' . mysqli_error($this->db)];
    }
    
    public function removeUserLine($ma_nv, $line_id) {
        $ma_nv = strtoupper(trim($ma_nv));
        $line_id = intval($line_id);
        
        $stmt = mysqli_prepare($this->db, "DELETE FROM user_line WHERE ma_nv = ? AND line_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $ma_nv, $line_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Xóa mapping thành công'];
            }
            return ['success' => false, 'message' => 'Không tìm thấy mapping'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi xóa mapping'];
    }
    
    public function createLine($ma_line, $ten_line) {
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
    
    public function updateLine($id, $ma_line, $ten_line, $is_active) {
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
    
    public function deleteLine($id) {
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
    
    public function getLine($id) {
        $stmt = mysqli_prepare($this->db, "SELECT id, ma_line, ten_line, is_active FROM line WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $line = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $line;
    }
    
    public function getMaHangList() {
        $result = mysqli_query($this->db, "SELECT id, ma_hang, ten_hang, is_active FROM ma_hang ORDER BY ma_hang");
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    public function getMaHang($id) {
        $stmt = mysqli_prepare($this->db, "SELECT id, ma_hang, ten_hang, is_active FROM ma_hang WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $maHang = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $maHang;
    }
    
    public function createMaHang($ma_hang, $ten_hang) {
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
    
    public function updateMaHang($id, $ma_hang, $ten_hang, $is_active) {
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
    
    public function deleteMaHang($id) {
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
    
    public function getCongDoanList() {
        $result = mysqli_query($this->db, "SELECT id, ma_cong_doan, ten_cong_doan, is_active, la_cong_doan_thanh_pham FROM cong_doan ORDER BY ma_cong_doan");
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    public function getCongDoan($id) {
        $stmt = mysqli_prepare($this->db, "SELECT id, ma_cong_doan, ten_cong_doan, is_active, la_cong_doan_thanh_pham FROM cong_doan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $congDoan = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $congDoan;
    }
    
    public function createCongDoan($ma_cong_doan, $ten_cong_doan, $la_cong_doan_thanh_pham = 0) {
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
    
    public function updateCongDoan($id, $ma_cong_doan, $ten_cong_doan, $is_active, $la_cong_doan_thanh_pham = 0) {
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
    
    public function deleteCongDoan($id) {
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
    
    public function getRoutingList($ma_hang_id) {
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
    
    public function getRouting($id) {
        $stmt = mysqli_prepare($this->db, "SELECT * FROM ma_hang_cong_doan WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $routing = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $routing;
    }
    
    public function addRouting($ma_hang_id, $cong_doan_id, $thu_tu, $bat_buoc = 1, $la_cong_doan_tinh_luy_ke = 0, $line_id = null, $ghi_chu = '') {
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
        
        $checkStmt = mysqli_prepare($this->db, "SELECT id FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND cong_doan_id = ? AND (line_id = ? OR (line_id IS NULL AND ? IS NULL))");
        mysqli_stmt_bind_param($checkStmt, "iiii", $ma_hang_id, $cong_doan_id, $line_id, $line_id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Routing này đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);
        
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
    
    public function updateRouting($id, $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id = null, $ghi_chu = '') {
        $id = intval($id);
        $thu_tu = intval($thu_tu);
        $bat_buoc = intval($bat_buoc);
        $la_cong_doan_tinh_luy_ke = intval($la_cong_doan_tinh_luy_ke);
        $line_id = $line_id ? intval($line_id) : null;
        $ghi_chu = trim($ghi_chu);
        
        $stmt = mysqli_prepare($this->db, "UPDATE ma_hang_cong_doan SET thu_tu = ?, bat_buoc = ?, la_cong_doan_tinh_luy_ke = ?, line_id = ?, ghi_chu = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "iiiisi", $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id, $ghi_chu, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Cập nhật routing thành công'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật routing'];
    }
    
    public function removeRouting($id) {
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
