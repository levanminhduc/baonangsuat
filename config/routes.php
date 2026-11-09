<?php

use App\Controllers\AuthController;
use App\Controllers\BaoCaoController;
use App\Controllers\BaoCaoHistoryController;
use App\Controllers\BieuDoController;
use App\Controllers\ContextController;
use App\Controllers\CsrfTokenController;
use App\Controllers\DanhMucController;
use App\Controllers\ImportController;
use App\Controllers\ImportHistoryController;
use App\Controllers\MocGioSetsController;

// Bảng route API (ADR 0001: URL giữ nguyên, khớp METHOD + đường dẫn). Xem App\Http\Router.
//
// Middleware: auth (đăng nhập), line (đã chọn LINE), role:a,b, csrf (header X-CSRF-Token),
// report (admin/được tạo mọi LINE → đăng nhập, còn lại → LINE), history, import.
$writer = 'role:to_truong,quan_doc,admin';
$endpointNotFound = [404, 'Endpoint không hợp lệ'];
$methodNotAllowed = [405, 'Method not allowed'];

return [
    // `/api/<group>` có ở đây thì Router lo hết; nhóm chưa chuyển chạy qua app/Legacy/api-router.php.
    // middleware nhóm chạy trước khi khớp route; not_found / method_not_allowed giữ message cũ của handler.
    'groups' => [
        'csrf-token' => [],
        'auth' => ['not_found' => [404, 'Action không hợp lệ']],
        'context' => [],
        'bao-cao' => [
            'middleware' => ['report'],
            'not_found' => $endpointNotFound,
            'method_not_allowed' => $endpointNotFound,
        ],
        'danh-muc' => [
            'middleware' => ['auth'],
            'not_found' => [404, 'Danh mục không hợp lệ'],
        ],
        'moc-gio-sets' => [
            'middleware' => ['role:admin'],
            'not_found' => $endpointNotFound,
            'method_not_allowed' => $endpointNotFound,
        ],
        'bao-cao-history' => [
            'middleware' => ['history'],
            'not_found' => $methodNotAllowed,
        ],
        'import' => [
            'middleware' => ['import'],
            'not_found' => $endpointNotFound,
            'method_not_allowed' => $endpointNotFound,
        ],
        'import-history' => [
            'middleware' => ['role:admin'],
            'not_found' => $methodNotAllowed,
        ],
        'bieu-do' => [
            'middleware' => ['auth'],
            'not_found' => $endpointNotFound,
            'method_not_allowed' => $endpointNotFound,
        ],
    ],

    // [METHODS ('GET|POST' hoặc '*'), path, Controller::class, method, [middleware]]
    'routes' => [
        ['GET', '/api/csrf-token', CsrfTokenController::class, 'show'],

        ['POST', '/api/auth/select-line', AuthController::class, 'selectLine', ['auth', 'csrf']],
        // Router cũ không kiểm method cho auth/session.
        ['*', '/api/auth/session', AuthController::class, 'session', ['auth']],

        ['GET', '/api/context', ContextController::class, 'show', ['report']],

        ['GET', '/api/bao-cao', BaoCaoController::class, 'index'],
        ['POST', '/api/bao-cao', BaoCaoController::class, 'store', ['csrf']],
        ['GET', '/api/bao-cao/{id:\d+}', BaoCaoController::class, 'show'],
        ['DELETE', '/api/bao-cao/{id:\d+}', BaoCaoController::class, 'destroy', ['role:admin', 'csrf']],
        ['GET', '/api/bao-cao/{id:\d+}/routing', BaoCaoController::class, 'routing'],
        ['PUT', '/api/bao-cao/{id:\d+}/entries', BaoCaoController::class, 'updateEntries', [$writer, 'csrf']],
        ['PUT', '/api/bao-cao/{id:\d+}/header', BaoCaoController::class, 'updateHeader', [$writer, 'csrf']],
        ['POST', '/api/bao-cao/{id:\d+}/submit', BaoCaoController::class, 'submit', [$writer, 'csrf']],
        ['POST', '/api/bao-cao/{id:\d+}/approve', BaoCaoController::class, 'approve', ['role:quan_doc,admin', 'csrf']],
        ['POST', '/api/bao-cao/{id:\d+}/unlock', BaoCaoController::class, 'unlock', ['role:admin', 'csrf']],
        ['POST', '/api/bao-cao/{id:\d+}/complete', BaoCaoController::class, 'complete', [$writer, 'csrf']],
        ['POST', '/api/bao-cao/{id:\d+}/reopen', BaoCaoController::class, 'reopen', ['role:admin', 'csrf']],

        ['GET', '/api/danh-muc/ca', DanhMucController::class, 'ca'],
        ['GET', '/api/danh-muc/ma-hang', DanhMucController::class, 'maHang'],
        ['GET', '/api/danh-muc/moc-gio', DanhMucController::class, 'mocGio'],
        ['GET', '/api/danh-muc/routing', DanhMucController::class, 'routing'],

        ['GET', '/api/moc-gio-sets', MocGioSetsController::class, 'index'],
        ['POST', '/api/moc-gio-sets', MocGioSetsController::class, 'store', ['csrf']],
        ['POST', '/api/moc-gio-sets/copy', MocGioSetsController::class, 'copy', ['csrf']],
        ['GET', '/api/moc-gio-sets/unassigned-lines', MocGioSetsController::class, 'unassignedLines'],
        ['GET', '/api/moc-gio-sets/resolve', MocGioSetsController::class, 'resolve'],
        ['GET', '/api/moc-gio-sets/{id:\d+}', MocGioSetsController::class, 'show'],
        ['PUT', '/api/moc-gio-sets/{id:\d+}', MocGioSetsController::class, 'update', ['csrf']],
        ['DELETE', '/api/moc-gio-sets/{id:\d+}', MocGioSetsController::class, 'destroy', ['csrf']],
        ['GET', '/api/moc-gio-sets/{id:\d+}/lines', MocGioSetsController::class, 'lines'],
        ['POST', '/api/moc-gio-sets/{id:\d+}/lines', MocGioSetsController::class, 'assignLines', ['csrf']],
        ['DELETE', '/api/moc-gio-sets/{id:\d+}/lines', MocGioSetsController::class, 'unassignLines', ['csrf']],

        ['GET', '/api/bao-cao-history', BaoCaoHistoryController::class, 'index'],
        ['GET', '/api/bao-cao-history/{id:\d+}', BaoCaoHistoryController::class, 'show'],

        ['POST', '/api/import/preview', ImportController::class, 'preview', ['csrf']],
        ['POST', '/api/import/confirm', ImportController::class, 'confirm', ['csrf']],

        ['GET', '/api/import-history', ImportHistoryController::class, 'index'],
        ['GET', '/api/import-history/{id:\d+}', ImportHistoryController::class, 'show'],

        ['GET', '/api/bieu-do/so-sanh', BieuDoController::class, 'soSanh'],
        ['GET', '/api/bieu-do/ma-hang-list', BieuDoController::class, 'maHangList'],
        ['GET', '/api/bieu-do/lines', BieuDoController::class, 'lines'],
        ['GET', '/api/bieu-do/ca-list', BieuDoController::class, 'caList'],
        ['GET', '/api/bieu-do/so-sanh-chi-tiet', BieuDoController::class, 'soSanhChiTiet'],
        ['GET', '/api/bieu-do/so-sanh-matrix', BieuDoController::class, 'soSanhMatrix'],
    ],
];
