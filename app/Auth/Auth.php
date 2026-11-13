<?php

namespace App\Auth;

use App\Support\Database;
use Exception;
use Hoatho\SharedAuth\EnvConfig;
use Hoatho\SharedAuth\Guard;
use Hoatho\SharedAuth\SessionBootstrap;
use Hoatho\SharedAuth\SessionContract;

class Auth
{
    private const SESSION_LINE_KEY = 'bns_line';

    private static $booted = false;
    private static $ctx = null;

    /**
     * Dựng bối cảnh danh tính (role, nhân viên, LINE) từ DB, một lần/request.
     * Chỉ LINE đã chọn (bns_line) nằm trong session; phần còn lại giữ trong bộ nhớ request.
     */
    private static function boot()
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        self::$ctx = null;

        if (!SessionContract::isLoggedIn()) {
            return;
        }
        $userId = SessionContract::userId();

        $mysqli = Database::getMysqli();
        $stmt = mysqli_prepare($mysqli, 'SELECT id, name, role FROM user WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) {
            unset($_SESSION[self::SESSION_LINE_KEY]);
            return;
        }

        $ma_nv = strtoupper(trim($user['name']));
        $nhanVienInfo = self::getNhanVienInfo($ma_nv);
        $lines = self::getUserLines(
            $ma_nv,
            $nhanVienInfo['phong_ban_ma'] ?? null,
            $nhanVienInfo['phong_ban_ten'] ?? null,
        )['lines'];
        $role = (string)($user['role'] ?? '');

        $line = LineSelection::reconcile(
            $_SESSION[self::SESSION_LINE_KEY] ?? null,
            (int)$user['id'],
            $lines,
        );
        if ($line === null) {
            unset($_SESSION[self::SESSION_LINE_KEY]);
        } else {
            $_SESSION[self::SESSION_LINE_KEY] = $line;
        }

        self::$ctx = [
            'user_id' => (int)$user['id'],
            'ma_nv' => $ma_nv,
            'vai_tro' => $role,
            'ho_ten' => ($nhanVienInfo['ho_ten'] ?? '') !== '' ? $nhanVienInfo['ho_ten'] : $ma_nv,
            'phong_ban_ma' => $nhanVienInfo['phong_ban_ma'] ?? null,
            'phong_ban_ten' => $nhanVienInfo['phong_ban_ten'] ?? null,
            'lines' => $lines,
            'line' => $line,
        ];
    }

    public static function loginUrl($returnPath = '/baonangsuat/index.php')
    {
        return EnvConfig::loginUrl() . '?redirect=' . rawurlencode($returnPath);
    }

    public static function logoutUrl()
    {
        return EnvConfig::logoutUrl('/baonangsuat/index.php');
    }

    /**
     * Dùng cho trang HTML: chưa đăng nhập thì Guard redirect sang login chung.
     * Phiên chung có user nhưng user không còn trong bảng user thì huỷ phiên chung,
     * tránh lặp vô hạn giữa trang này và authV2.
     */
    public static function requirePageLogin()
    {
        (new Guard())->requireLogin();
        if (!self::isLoggedIn()) {
            SessionBootstrap::destroy();
            header('Location: ' . self::loginUrl(), true, 302);
            exit;
        }
    }

    private static function getNhanVienInfo($ma_nv)
    {
        try {
            $nhanSu = Database::getNhanSu();
            $stmt = mysqli_prepare(
                $nhanSu,
                'SELECT nv.ho_ten, nv.phong_ban_ma, pb.ten as phong_ban_ten 
                 FROM nhan_vien nv 
                 LEFT JOIN phong_ban pb ON pb.ma = nv.phong_ban_ma 
                 WHERE UPPER(nv.ma_nv) = ?',
            );
            $ma_nv_upper = strtoupper(trim($ma_nv));
            mysqli_stmt_bind_param($stmt, 's', $ma_nv_upper);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $info = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $info ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    private static function getUserLines($ma_nv, $phong_ban_ma, $phong_ban_ten = null)
    {
        $nangSuat = Database::getNangSuat();
        $lines = [];

        $stmt = mysqli_prepare(
            $nangSuat,
            'SELECT l.id, l.ma_line, l.ten_line 
             FROM user_line ul 
             JOIN line l ON l.id = ul.line_id 
             WHERE UPPER(ul.ma_nv) = ? AND l.is_active = 1',
        );
        $ma_nv_upper = strtoupper(trim($ma_nv));
        mysqli_stmt_bind_param($stmt, 's', $ma_nv_upper);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $lines[] = $row;
        }
        mysqli_stmt_close($stmt);

        if (count($lines) === 0 && $phong_ban_ma) {
            $phong_ban_ma = (string) $phong_ban_ma;
            $ten = strtoupper(trim((string) $phong_ban_ten));
            $stmt = mysqli_prepare(
                $nangSuat,
                'SELECT l.id, l.ma_line, l.ten_line
                 FROM line l
                 WHERE l.is_active = 1
                   AND (l.ma_line = ? OR l.id IN (SELECT line_id FROM phong_ban_line WHERE phong_ban_ma = ?))
                 ORDER BY l.ma_line',
            );
            mysqli_stmt_bind_param($stmt, 'ss', $ten, $phong_ban_ma);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $lines[] = $row;
            }
            mysqli_stmt_close($stmt);
        }

        return ['lines' => $lines];
    }

    public static function selectLine($line_id)
    {
        self::boot();
        if (self::$ctx === null) {
            return ['success' => false, 'message' => 'Chưa đăng nhập'];
        }

        $line = LineSelection::pick((int)$line_id, self::$ctx['user_id'], self::$ctx['lines']);
        if ($line === null) {
            return ['success' => false, 'message' => 'LINE không hợp lệ'];
        }

        $_SESSION[self::SESSION_LINE_KEY] = $line;
        self::$ctx['line'] = $line;

        return ['success' => true, 'message' => 'Đã chọn LINE thành công'];
    }

    public static function isLoggedIn()
    {
        self::boot();
        return self::$ctx !== null;
    }

    public static function hasLine()
    {
        self::boot();
        return self::$ctx !== null && self::$ctx['line'] !== null;
    }

    public static function allowedLines()
    {
        self::boot();
        return self::$ctx['lines'] ?? [];
    }

    public static function getSession()
    {
        self::boot();
        $ctx = self::$ctx;
        return [
            'user_id' => $ctx['user_id'] ?? null,
            'ma_nv' => $ctx['ma_nv'] ?? null,
            'ho_ten' => $ctx['ho_ten'] ?? null,
            'vai_tro' => $ctx['vai_tro'] ?? null,
            'phong_ban_ma' => $ctx['phong_ban_ma'] ?? null,
            'phong_ban_ten' => $ctx['phong_ban_ten'] ?? null,
            'line_id' => $ctx['line']['id'] ?? null,
            'line_ma' => $ctx['line']['ma_line'] ?? null,
            'line_ten' => $ctx['line']['ten_line'] ?? null,
        ];
    }

    public static function checkRole($allowedRoles)
    {
        self::boot();
        $currentRole = self::$ctx['vai_tro'] ?? '';
        if (is_string($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        return in_array($currentRole, $allowedRoles);
    }

    public static function getDefaultPage()
    {
        if (!self::isLoggedIn()) {
            return 'index.php';
        }

        if (self::checkRole(['admin'])) {
            return 'admin.php';
        }

        if (self::hasLine()) {
            return 'nhap-nang-suat.php';
        }

        if (count(self::allowedLines()) >= 1) {
            return 'index.php';
        }

        return 'no-line.php';
    }

    public static function hasPermission($userId, $permissionKey)
    {
        if (self::checkRole(['admin'])) {
            return true;
        }

        try {
            $mysqli = Database::getMysqli();
            $stmt = mysqli_prepare(
                $mysqli,
                'SELECT id FROM user_permissions WHERE nguoi_dung_id = ? AND quyen = ?',
            );
            if (!$stmt) {
                return false;
            }
            mysqli_stmt_bind_param($stmt, 'is', $userId, $permissionKey);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            return $row !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getUserPermissions($userId)
    {
        try {
            $mysqli = Database::getMysqli();
            $stmt = mysqli_prepare(
                $mysqli,
                'SELECT quyen FROM user_permissions WHERE nguoi_dung_id = ?',
            );
            if (!$stmt) {
                return [];
            }
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $permissions = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $permissions[] = $row['quyen'];
            }
            mysqli_stmt_close($stmt);

            return $permissions;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get permissions for multiple users in a single query
     * @param array $userIds Array of user IDs
     * @return array Array of user permissions grouped by userId
     */
    public static function getAllUsersPermissions(array $userIds)
    {
        if (empty($userIds)) {
            return [];
        }

        try {
            $mysqli = Database::getMysqli();

            // Sanitize user IDs
            $userIds = array_filter(array_map('intval', $userIds));
            if (empty($userIds)) {
                return [];
            }

            // Build placeholders for IN clause
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';

            $stmt = mysqli_prepare(
                $mysqli,
                "SELECT nguoi_dung_id, quyen FROM user_permissions WHERE nguoi_dung_id IN ($placeholders)",
            );

            // Bind all user IDs
            $types = str_repeat('i', count($userIds));
            mysqli_stmt_bind_param($stmt, $types, ...$userIds);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // Group permissions by user
            $permissionsMap = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $userId = (int)$row['nguoi_dung_id'];
                if (!isset($permissionsMap[$userId])) {
                    $permissionsMap[$userId] = [];
                }
                $permissionsMap[$userId][] = $row['quyen'];
            }
            mysqli_stmt_close($stmt);

            // Format result: include all requested users (even those with no permissions)
            $resultArray = [];
            foreach ($userIds as $userId) {
                $resultArray[] = [
                    'userId' => $userId,
                    'permissions' => $permissionsMap[$userId] ?? [],
                ];
            }

            return $resultArray;
        } catch (Exception $e) {
            error_log('Error in getAllUsersPermissions: ' . $e->getMessage());
            return [];
        }
    }

    public static function canViewHistory($userId = null)
    {
        if (self::checkRole(['admin'])) {
            return true;
        }

        if ($userId === null) {
            $userId = self::$ctx['user_id'] ?? null;
        }

        if ($userId === null) {
            return false;
        }

        return self::hasPermission($userId, 'can_view_history');
    }

    public static function canCreateReport($userId = null)
    {
        if (self::checkRole(['admin'])) {
            return true;
        }

        if (!self::isLoggedIn()) {
            return false;
        }

        if (self::canCreateReportForAnyLine($userId)) {
            return true;
        }

        if (!self::hasLine()) {
            return false;
        }

        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }

        if ($userId === null) {
            return false;
        }

        return self::hasPermission($userId, 'tao_bao_cao');
    }

    public static function canCreateReportForAnyLine($userId = null)
    {
        if (self::checkRole(['admin'])) {
            return true;
        }

        if (!self::isLoggedIn()) {
            return false;
        }

        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }

        if ($userId === null) {
            return false;
        }

        return self::hasPermission($userId, 'tao_bao_cao_cho_line');
    }

    public static function canImport($userId = null)
    {
        if (self::checkRole(['admin'])) {
            return true;
        }

        if (!self::isLoggedIn()) {
            return false;
        }

        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }

        if ($userId === null) {
            return false;
        }

        return self::hasPermission($userId, 'import_ma_hang_cong_doan');
    }

    public static function canAccessAdminPanel()
    {
        if (!self::checkRole(['admin'])) {
            return false;
        }

        $ma_nv = strtoupper(trim(self::getSession()['ma_nv'] ?? ''));
        if (empty($ma_nv)) {
            return false;
        }

        $configFile = 'C:/xampp/config/admin_whitelist.php';

        if (!file_exists($configFile)) {
            return true;
        }

        $config = require $configFile;
        $whitelist = $config['ADMIN_WHITELIST'] ?? [];

        if (empty($whitelist)) {
            return true;
        }

        $whitelist = array_map(function ($item) {
            return strtoupper(trim($item));
        }, $whitelist);

        return in_array($ma_nv, $whitelist);
    }
}
