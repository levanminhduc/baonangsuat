<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;

// Như requireLine() cũ: kiểm đăng nhập trước, rồi mới kiểm đã chọn LINE.
final class RequireLine implements MiddlewareInterface
{
    public function check(Request $request, array $arguments): ?JsonResponse
    {
        $denied = (new RequireLogin())->check($request, []);
        if ($denied !== null) {
            return $denied;
        }
        if (Auth::hasLine()) {
            return null;
        }

        return JsonResponse::error('Chưa chọn LINE', 403);
    }
}
