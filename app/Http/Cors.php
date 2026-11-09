<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Header chung cho mọi response nhóm `/api`, gửi trước khi mở phiên.
 *
 * Giá trị là giá trị client THỰC NHẬN trước refactor: router cũ đặt ACAO theo allowlist
 * localhost, nhưng `api/.htaccess` (`Header set`) ghi đè thành `*` và ACAH thành `Content-Type`.
 *
 * RỦI RO (giữ nguyên có chủ đích, xử lý riêng): `Access-Control-Allow-Origin: *` cho mọi origin.
 * Trình duyệt không gửi cookie kèm khi ACAO là `*` nên chưa lộ phiên, nhưng cấu hình này lỏng.
 * Trên production, giá trị cũ còn phụ thuộc mod_headers có bật hay không (khối .htaccess bọc
 * IfModule) — kiểm tra headers_module trước khi deploy.
 */
final class Cors
{
    private const ALLOWED_ORIGINS = [
        'http://localhost',
        'http://127.0.0.1',
        'https://localhost',
        'https://127.0.0.1',
    ];

    public static function allowsCredentials(string $origin): bool
    {
        return in_array($origin, self::ALLOWED_ORIGINS, true)
            || str_starts_with($origin, 'http://localhost:')
            || str_starts_with($origin, 'http://127.0.0.1:');
    }

    public static function send(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (self::allowsCredentials($request->header('Origin'))) {
            header('Access-Control-Allow-Credentials: true');
        }
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }
}
