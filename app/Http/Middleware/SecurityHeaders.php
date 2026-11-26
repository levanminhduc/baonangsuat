<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\ResponseInterface;

// Header bảo mật cho trang HTML (trước đây là includes/security-headers.php ở đầu mỗi trang).
final class SecurityHeaders implements MiddlewareInterface
{
    public function check(Request $request, array $arguments): ?ResponseInterface
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:;");

        return null;
    }
}
