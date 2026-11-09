<?php

use App\Controllers\AuthController;
use App\Controllers\ContextController;
use App\Controllers\CsrfTokenController;

// Bảng route API (ADR 0001: URL giữ nguyên, khớp METHOD + đường dẫn).
// [METHODS ('GET|POST' hoặc '*'), path, Controller::class, method, [middleware]]; route khai trước được ưu tiên.
// Middleware: auth (đăng nhập), line (đã chọn LINE), role:a,b, csrf (header X-CSRF-Token).
return [
    // `/api/<group>` có trong danh sách thì Router lo hết (kể cả 404/405);
    // nhóm chưa chuyển vẫn chạy qua app/Legacy/api-router.php.
    'groups' => ['csrf-token', 'auth', 'context'],

    'routes' => [
        ['GET', '/api/csrf-token', CsrfTokenController::class, 'show'],

        ['POST', '/api/auth/select-line', AuthController::class, 'selectLine', ['auth', 'csrf']],
        // Router cũ không kiểm method cho auth/session.
        ['*', '/api/auth/session', AuthController::class, 'session', ['auth']],
        ['*', '/api/auth', AuthController::class, 'unknownAction'],
        ['*', '/api/auth/{action}', AuthController::class, 'unknownAction'],

        ['GET', '/api/context', ContextController::class, 'show'],
    ],
];
