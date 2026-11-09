<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;

final class RequireImportAccess implements MiddlewareInterface
{
    public function check(Request $request, array $arguments): ?JsonResponse
    {
        $denied = (new RequireLogin())->check($request, []);
        if ($denied !== null) {
            return $denied;
        }
        if (Auth::canImport()) {
            return null;
        }

        return JsonResponse::error('Không có quyền import mã hàng và công đoạn', 403);
    }
}
