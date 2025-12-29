<?php
session_start();
require_once __DIR__ . '/../config/Database.php';

class Auth {
    public static function login($username, $password) {
        $mysqli = Database::getMysqli();
        
        $username = trim(strtoupper($username));
        $stmt = mysqli_prepare($mysqli, "SELECT id, name, password, role FROM user WHERE UPPER(name) = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Tài khoản không tồn tại'];
        }
        
        if (!password_verify($password, $user['password']) && $user['password'] !== $password) {
            return ['success' => false, 'message' => 'Mật khẩu không đúng'];
        }
        
        $ma_nv = $user['name'];
        $nhanVienInfo = self::getNhanVienInfo($ma_nv);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['ma_nv'] = $ma_nv;
        $_SESSION['vai_tro'] = $user['role'];
        $_SESSION['ho_ten'] = $nhanVienInfo['ho_ten'] ?? $ma_nv;
        $_SESSION['phong_ban_ma'] = $nhanVienInfo['phong_ban_ma'] ?? null;
        $_SESSION['phong_ban_ten'] = $nhanVienInfo['phong_ban_ten'] ?? null;
        
        $lineResult = self::getUserLines($ma_nv, $nhanVienInfo['phong_ban_ma'] ?? null);
        
        if (count($lineResult['lines']) === 0) {
            return [
                'success' => true, 
                'message' => 'Đăng nhập thành công nhưng chưa được phân LINE',
                'need_select_line' => false,
                'no_line' => true
            ];
        }
        
        if (count($lineResult['lines']) === 1) {
            $_SESSION['line_id'] = $lineResult['lines'][0]['id'];
            $_SESSION['line_ma'] = $lineResult['lines'][0]['ma_line'];
            $_SESSION['line_ten'] = $lineResult['lines'][0]['ten_line'];
            return ['success' => true, 'message' => 'Đăng nhập thành công'];
        }
        
        return [
            'success' => true,
            'message' => 'Vui lòng chọn LINE',
            'need_select_line' => true,
            'lines' => $lineResult['lines']
        ];
    }
    
    private static function getNhanVienInfo($ma_nv) {
        try {
            $nhanSu = Database::getNhanSu();
            $stmt = mysqli_prepare($nhanSu, 
                "SELECT nv.ho_ten, nv.phong_ban_ma, pb.ten as phong_ban_ten 
                 FROM nhan_vien nv 
                 LEFT JOIN phong_ban pb ON pb.ma = nv.phong_ban_ma 
                 WHERE UPPER(nv.ma_nv) = ?"
            );
            $ma_nv_upper = strtoupper(trim($ma_nv));
            mysqli_stmt_bind_param($stmt, "s", $ma_nv_upper);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $info = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $info ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    private static function getUserLines($ma_nv, $phong_ban_ma) {
        $nangSuat = Database::getNangSuat();
        $lines = [];
        
        $stmt = mysqli_prepare($nangSuat, 
            "SELECT l.id, l.ma_line, l.ten_line 
             FROM user_line ul 
             JOIN line l ON l.id = ul.line_id 
             WHERE UPPER(ul.ma_nv) = ? AND l.is_active = 1"
        );
        $ma_nv_upper = strtoupper(trim($ma_nv));
        mysqli_stmt_bind_param($stmt, "s", $ma_nv_upper);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $lines[] = $row;
        }
        mysqli_stmt_close($stmt);
        
        if (count($lines) === 0 && $phong_ban_ma) {
            $stmt = mysqli_prepare($nangSuat, 
                "SELECT l.id, l.ma_line, l.ten_line 
                 FROM phong_ban_line pbl 
                 JOIN line l ON l.id = pbl.line_id 
                 WHERE pbl.phong_ban_ma = ? AND l.is_active = 1"
            );
            mysqli_stmt_bind_param($stmt, "s", $phong_ban_ma);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $lines[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
        
        return ['lines' => $lines];
    }
    
    public static function selectLine($line_id) {
        if (!isset($_SESSION['ma_nv'])) {
            return ['success' => false, 'message' => 'Chưa đăng nhập'];
        }
        
        $nangSuat = Database::getNangSuat();
        $stmt = mysqli_prepare($nangSuat, 
            "SELECT id, ma_line, ten_line FROM line WHERE id = ? AND is_active = 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $line_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $line = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$line) {
            return ['success' => false, 'message' => 'LINE không hợp lệ'];
        }
        
        $_SESSION['line_id'] = $line['id'];
        $_SESSION['line_ma'] = $line['ma_line'];
        $_SESSION['line_ten'] = $line['ten_line'];
        
        return ['success' => true, 'message' => 'Đã chọn LINE thành công'];
    }
    
    public static function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Đã đăng xuất'];
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function hasLine() {
        return isset($_SESSION['line_id']);
    }
    
    public static function getSession() {
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'ma_nv' => $_SESSION['ma_nv'] ?? null,
            'ho_ten' => $_SESSION['ho_ten'] ?? null,
            'vai_tro' => $_SESSION['vai_tro'] ?? null,
            'phong_ban_ma' => $_SESSION['phong_ban_ma'] ?? null,
            'phong_ban_ten' => $_SESSION['phong_ban_ten'] ?? null,
            'line_id' => $_SESSION['line_id'] ?? null,
            'line_ma' => $_SESSION['line_ma'] ?? null,
            'line_ten' => $_SESSION['line_ten'] ?? null
        ];
    }
    
    public static function checkRole($allowedRoles) {
        $currentRole = $_SESSION['vai_tro'] ?? '';
        if (is_string($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        return in_array($currentRole, $allowedRoles);
    }
}
