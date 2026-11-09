<?php

use App\Controllers\AdminController;
use App\Controllers\AdminPageController;
use App\Controllers\AuthController;
use App\Controllers\BaoCaoController;
use App\Controllers\BaoCaoHistoryController;
use App\Controllers\BieuDoController;
use App\Controllers\BieuDoPageController;
use App\Controllers\ContextController;
use App\Controllers\CsrfTokenController;
use App\Controllers\DanhMucController;
use App\Controllers\HuongDanPageController;
use App\Controllers\ImportController;
use App\Controllers\ImportHistoryController;
use App\Controllers\IndexPageController;
use App\Controllers\MocGioSetsController;
use App\Controllers\NhapNangSuatPageController;
use App\Controllers\NoLinePageController;
use App\Controllers\UserPermissionsController;

// Bảng route API (ADR 0001: URL giữ nguyên, khớp METHOD + đường dẫn). Xem App\Http\Router.
//
// Middleware: auth (đăng nhập), line (đã chọn LINE), role:a,b, csrf (header X-CSRF-Token),
// report (admin/được tạo mọi LINE → đăng nhập, còn lại → LINE), history, import.
$writer = 'role:to_truong,quan_doc,admin';
$endpointNotFound = [404, 'Endpoint không hợp lệ'];
$methodNotAllowed = [405, 'Method not allowed'];

return [
    // Trang HTML: URL `*.php` giữ nguyên (ADR 0001). Mọi trang cần đăng nhập; method nào cũng nhận như trang cũ.
    'pages' => [
        'middleware' => ['security-headers', 'auth'],
        'routes' => [
            ['*', '/', IndexPageController::class, 'show'],
            ['*', '/index.php', IndexPageController::class, 'show'],
            ['*', '/nhap-nang-suat.php', NhapNangSuatPageController::class, 'show'],
            ['*', '/admin.php', AdminPageController::class, 'show'],
            ['*', '/bieu-do.php', BieuDoPageController::class, 'show'],
            ['*', '/huong-dan.php', HuongDanPageController::class, 'show'],
            ['*', '/no-line.php', NoLinePageController::class, 'show'],
        ],
    ],

    // `/api/<group>` không có ở đây → 404 'API không tồn tại'.
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
        'admin' => [
            'middleware' => ['role:admin'],
            'not_found' => $endpointNotFound,
            'method_not_allowed' => $endpointNotFound,
        ],
        'user-permissions' => [
            'middleware' => ['role:admin'],
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

        ['GET', '/api/admin/lines', AdminController::class, 'lines'],
        ['POST', '/api/admin/lines', AdminController::class, 'createLine', ['csrf']],
        ['GET', '/api/admin/lines/{id:\d+}', AdminController::class, 'line'],
        ['PUT', '/api/admin/lines/{id:\d+}', AdminController::class, 'updateLine', ['csrf']],
        ['DELETE', '/api/admin/lines/{id:\d+}', AdminController::class, 'deleteLine', ['csrf']],
        ['GET', '/api/admin/users', AdminController::class, 'users'],
        ['GET', '/api/admin/user-lines', AdminController::class, 'userLines'],
        ['POST', '/api/admin/user-lines', AdminController::class, 'addUserLine', ['csrf']],
        ['DELETE', '/api/admin/user-lines', AdminController::class, 'removeUserLine', ['csrf']],
        ['GET', '/api/admin/ma-hang', AdminController::class, 'maHangList'],
        ['POST', '/api/admin/ma-hang', AdminController::class, 'createMaHang', ['csrf']],
        ['GET', '/api/admin/ma-hang/{id:\d+}', AdminController::class, 'maHang'],
        ['PUT', '/api/admin/ma-hang/{id:\d+}', AdminController::class, 'updateMaHang', ['csrf']],
        ['DELETE', '/api/admin/ma-hang/{id:\d+}', AdminController::class, 'deleteMaHang', ['csrf']],
        ['GET', '/api/admin/cong-doan', AdminController::class, 'congDoanList'],
        ['POST', '/api/admin/cong-doan', AdminController::class, 'createCongDoan', ['csrf']],
        ['GET', '/api/admin/cong-doan/{id:\d+}', AdminController::class, 'congDoan'],
        ['PUT', '/api/admin/cong-doan/{id:\d+}', AdminController::class, 'updateCongDoan', ['csrf']],
        ['DELETE', '/api/admin/cong-doan/{id:\d+}', AdminController::class, 'deleteCongDoan', ['csrf']],
        ['GET', '/api/admin/routing', AdminController::class, 'routingList'],
        ['POST', '/api/admin/routing', AdminController::class, 'addRouting', ['csrf']],
        ['GET', '/api/admin/routing/{id:\d+}', AdminController::class, 'routing'],
        ['PUT', '/api/admin/routing/{id:\d+}', AdminController::class, 'updateRouting', ['csrf']],
        ['DELETE', '/api/admin/routing/{id:\d+}', AdminController::class, 'removeRouting', ['csrf']],
        ['GET', '/api/admin/moc-gio/ca-list', AdminController::class, 'mocGioCaList'],
        ['POST', '/api/admin/moc-gio/copy-default', AdminController::class, 'copyMocGioDefault', ['csrf']],
        ['GET', '/api/admin/moc-gio', AdminController::class, 'mocGioList'],
        ['POST', '/api/admin/moc-gio', AdminController::class, 'createMocGio', ['csrf']],
        ['GET', '/api/admin/moc-gio/{id:\d+}', AdminController::class, 'mocGio'],
        ['PUT', '/api/admin/moc-gio/{id:\d+}', AdminController::class, 'updateMocGio', ['csrf']],
        ['DELETE', '/api/admin/moc-gio/{id:\d+}', AdminController::class, 'deleteMocGio', ['csrf']],
        ['POST', '/api/admin/bao-cao/bulk-create', AdminController::class, 'bulkCreateBaoCao', ['csrf']],
        // Tài nguyên admin không tồn tại: router cũ trả 'Resource không hợp lệ' thay vì 'Endpoint không hợp lệ'.
        ['*', '/api/admin', AdminController::class, 'unknownResource'],
        ['*', '/api/admin/{resource:(?!(?:lines|users|user-lines|ma-hang|cong-doan|routing|moc-gio|bao-cao)$).+}', AdminController::class, 'unknownResource'],

        ['POST', '/api/user-permissions/bulk', UserPermissionsController::class, 'bulk'],
        ['GET', '/api/user-permissions/{id:\d+}', UserPermissionsController::class, 'show'],
        ['POST', '/api/user-permissions', UserPermissionsController::class, 'grant', ['csrf']],
        ['DELETE', '/api/user-permissions/{id:\d+}/{quyen}', UserPermissionsController::class, 'revoke', ['csrf']],
    ],
];
