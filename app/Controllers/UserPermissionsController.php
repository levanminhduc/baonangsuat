<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Support\Database;

// Chỉ admin (middleware nhóm `role:admin`). `bulk` chỉ đọc nên không đòi CSRF (như router cũ).
class UserPermissionsController
{
    public function bulk(Request $request)
    {
        $userIds = $request->jsonBody()['userIds'] ?? [];

        if (!is_array($userIds)) {
            return new JsonResponse(['success' => false, 'error' => 'userIds must be an array'], 400);
        }

        return new JsonResponse(['success' => true, 'data' => Auth::getAllUsersPermissions($userIds)]);
    }

    public function show(Request $request)
    {
        $userId = intval($request->param('id'));
        $permissions = Auth::getUserPermissions($userId);

        return new JsonResponse(['success' => true, 'data' => ['nguoi_dung_id' => $userId, 'permissions' => $permissions]]);
    }

    public function grant(Request $request)
    {
        $mysqli = Database::getMysqli();
        $input = $request->jsonBody();
        $userId = intval($input['nguoi_dung_id'] ?? 0);
        $permissionKey = $input['quyen'] ?? '';

        if ($userId <= 0 || empty($permissionKey)) {
            return JsonResponse::error('Thiếu thông tin nguoi_dung_id hoặc quyen', 400);
        }

        $stmt = mysqli_prepare($mysqli, 'SELECT id FROM user WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (!mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);

            return JsonResponse::error('User không tồn tại', 404);
        }
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($mysqli, 'SELECT id FROM user_permissions WHERE nguoi_dung_id = ? AND quyen = ?');
        mysqli_stmt_bind_param($stmt, 'is', $userId, $permissionKey);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);

            return new JsonResponse(['success' => true, 'message' => 'Permission đã tồn tại']);
        }
        mysqli_stmt_close($stmt);

        $nguoiTao = Auth::getSession()['ma_nv'];
        $stmt = mysqli_prepare($mysqli, 'INSERT INTO user_permissions (nguoi_dung_id, quyen, nguoi_tao) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iss', $userId, $permissionKey, $nguoiTao);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            return new JsonResponse(['success' => true, 'message' => 'Đã cấp quyền thành công']);
        }
        mysqli_stmt_close($stmt);

        return JsonResponse::error('Không thể cấp quyền', 500);
    }

    public function revoke(Request $request)
    {
        $mysqli = Database::getMysqli();
        $userId = intval($request->param('id'));
        $permissionKey = $request->param('quyen');

        $stmt = mysqli_prepare($mysqli, 'DELETE FROM user_permissions WHERE nguoi_dung_id = ? AND quyen = ?');
        mysqli_stmt_bind_param($stmt, 'is', $userId, $permissionKey);

        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected > 0) {
                return new JsonResponse(['success' => true, 'message' => 'Đã thu hồi quyền thành công']);
            }

            return new JsonResponse(['success' => true, 'message' => 'Permission không tồn tại']);
        }
        mysqli_stmt_close($stmt);

        return JsonResponse::error('Không thể thu hồi quyền', 500);
    }
}
