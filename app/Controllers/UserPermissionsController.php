<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\UserPermissionService;

// Chỉ admin (middleware nhóm `role:admin`). `bulk` chỉ đọc nên không đòi CSRF (như router cũ).
class UserPermissionsController
{
    public function __construct(private UserPermissionService $permissions)
    {
    }

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
        $input = $request->jsonBody();
        $userId = intval($input['nguoi_dung_id'] ?? 0);
        $permissionKey = is_string($input['quyen'] ?? null) ? $input['quyen'] : '';

        if ($userId <= 0 || empty($permissionKey)) {
            return JsonResponse::error('Thiếu thông tin nguoi_dung_id hoặc quyen', 400);
        }

        if (!$this->permissions->userExists($userId)) {
            return JsonResponse::error('User không tồn tại', 404);
        }

        if ($this->permissions->hasPermission($userId, $permissionKey)) {
            return new JsonResponse(['success' => true, 'message' => 'Permission đã tồn tại']);
        }

        if ($this->permissions->grant($userId, $permissionKey, Auth::getSession()['ma_nv'])) {
            return new JsonResponse(['success' => true, 'message' => 'Đã cấp quyền thành công']);
        }

        return JsonResponse::error('Không thể cấp quyền', 500);
    }

    public function revoke(Request $request)
    {
        $affected = $this->permissions->revoke(intval($request->param('id')), $request->param('quyen'));

        if ($affected === null) {
            return JsonResponse::error('Không thể thu hồi quyền', 500);
        }
        if ($affected > 0) {
            return new JsonResponse(['success' => true, 'message' => 'Đã thu hồi quyền thành công']);
        }

        return new JsonResponse(['success' => true, 'message' => 'Permission không tồn tại']);
    }
}
