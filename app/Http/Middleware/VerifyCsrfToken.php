<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\CsrfToken;
use App\Http\JsonResponse;
use App\Http\Request;

// Chỉ nhận token qua header X-CSRF-Token như router cũ (nhansuhtdb còn nhận trong body — không chép).
final class VerifyCsrfToken implements MiddlewareInterface
{
    public function check(Request $request, array $arguments): ?JsonResponse
    {
        $token = $request->header('X-CSRF-Token');
        if ($token !== '' && CsrfToken::validate($token)) {
            return null;
        }

        return JsonResponse::error('CSRF token không hợp lệ', 403, ['csrf_error' => true]);
    }
}
