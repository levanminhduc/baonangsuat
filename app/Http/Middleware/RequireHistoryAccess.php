<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;

// Lịch sử báo cáo: đăng nhập → (không phải admin) cần quyền xem lịch sử → cần LINE,
// trừ người được tạo báo cáo cho mọi LINE.
final class RequireHistoryAccess implements MiddlewareInterface
{
    public function check(Request $request, array $arguments): ?JsonResponse
    {
        $denied = (new RequireLogin())->check($request, []);
        if ($denied !== null) {
            return $denied;
        }

        $isAdmin = Auth::checkRole(['admin']);
        if (!$isAdmin && !Auth::canViewHistory()) {
            return JsonResponse::error('Không có quyền xem lịch sử', 403);
        }
        if (!$isAdmin && !Auth::canCreateReportForAnyLine()) {
            return (new RequireLine())->check($request, []);
        }

        return null;
    }
}
