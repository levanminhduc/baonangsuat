<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\JsonResponse;
use App\Http\Request;

final class RequireLogin implements MiddlewareInterface
{
    public function check(Request $request, array $arguments): ?JsonResponse
    {
        if (Auth::isLoggedIn()) {
            return null;
        }

        return JsonResponse::error('Chưa đăng nhập', 401);
    }
}
