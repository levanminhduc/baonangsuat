<?php

// Front controller duy nhất. `.htaccess` gốc đưa `/`, các trang `*.php` và `/api/...` về đây;
// URL không đổi (ADR 0001). Trang và API đều đi qua bảng route config/routes.php.
// Chỉ nạp autoload trước: preflight OPTIONS của API trả về khi chưa mở phiên, như router cũ.
require_once __DIR__ . '/../bootstrap/autoload.php';

use App\Http\Cors;
use App\Http\Request;
use App\Http\Router;

$bnsRequest = Request::fromGlobals();

if ($bnsRequest->isApi()) {
    Cors::send($bnsRequest);
    if ($bnsRequest->method() === 'OPTIONS') {
        exit(0);
    }
}

require_once BNS_ROOT . '/bootstrap/app.php';

$bnsRouter = new Router(require BNS_ROOT . '/config/routes.php');
$bnsRouter->dispatch($bnsRequest)->send();
