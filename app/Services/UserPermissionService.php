<?php

namespace App\Services;

use App\Support\Database;

class UserPermissionService
{
    private $mysqli;

    public function __construct()
    {
        $this->mysqli = Database::getMysqli();
    }

    public function userExists(int $userId): bool
    {
        $stmt = mysqli_prepare($this->mysqli, 'SELECT id FROM user WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $exists = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        return $exists;
    }

    public function hasPermission(int $userId, string $permissionKey): bool
    {
        $stmt = mysqli_prepare($this->mysqli, 'SELECT id FROM user_permissions WHERE nguoi_dung_id = ? AND quyen = ?');
        mysqli_stmt_bind_param($stmt, 'is', $userId, $permissionKey);
        mysqli_stmt_execute($stmt);
        $exists = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        return $exists;
    }

    public function grant(int $userId, string $permissionKey, string $nguoiTao): bool
    {
        $stmt = mysqli_prepare($this->mysqli, 'INSERT INTO user_permissions (nguoi_dung_id, quyen, nguoi_tao) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iss', $userId, $permissionKey, $nguoiTao);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    // null = lỗi SQL; ngược lại là số dòng bị xoá
    public function revoke(int $userId, ?string $permissionKey): ?int
    {
        $stmt = mysqli_prepare($this->mysqli, 'DELETE FROM user_permissions WHERE nguoi_dung_id = ? AND quyen = ?');
        mysqli_stmt_bind_param($stmt, 'is', $userId, $permissionKey);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            return null;
        }
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $affected;
    }
}
