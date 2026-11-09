<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;

// Phạm vi báo cáo (context, bao-cao): admin hoặc người được tạo báo cáo cho mọi LINE chỉ cần
// đăng nhập; còn lại phải đã chọn LINE.
final class RequireReportScope implements MiddlewareInterface
{
    public function check(Request $request, array $arguments): ?JsonResponse
    {
        $guard = (Auth::checkRole(['admin']) || Auth::canCreateReportForAnyLine())
            ? new RequireLogin()
            : new RequireLine();

        return $guard->check($request, []);
    }
}
