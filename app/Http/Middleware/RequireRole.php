<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;

// `role:admin,quan_ly` — như requireRole() cũ: kiểm đăng nhập trước, vai trò lấy từ Auth
// (dựng lại từ DB mỗi request), không đọc thẳng $_SESSION.
final class RequireRole implements MiddlewareInterface
{
    public function check(Request $request, array $arguments): ?JsonResponse
    {
        $denied = (new RequireLogin())->check($request, []);
        if ($denied !== null) {
            return $denied;
        }
        if (Auth::checkRole($arguments)) {
            return null;
        }

        return JsonResponse::error('Không có quyền thực hiện', 403);
    }
}
