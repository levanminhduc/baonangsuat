<?php

// Front controller duy nhất. `.htaccess` gốc đưa `/api/...` và `/` về đây; URL không đổi (ADR 0001).
// Chỉ nạp autoload trước: preflight OPTIONS của API trả về khi chưa mở phiên, như router cũ.
require_once __DIR__ . '/../bootstrap/autoload.php';

use App\Http\Cors;
use App\Http\Request;
use App\Http\Router;

$bnsRequest = Request::fromGlobals();

if (!$bnsRequest->isApi()) {
    // `/` và `/index.php`: trang chọn LINE (tự nạp bootstrap/app.php).
    require BNS_ROOT . '/views/index.php';

    return;
}

Cors::send($bnsRequest);
if ($bnsRequest->method() === 'OPTIONS') {
    exit(0);
}

require_once BNS_ROOT . '/bootstrap/app.php';

$bnsRouter = new Router(require BNS_ROOT . '/config/routes.php');
if ($bnsRouter->owns($bnsRequest)) {
    $bnsRouter->dispatch($bnsRequest)->send();

    return;
}

// Nhóm API chưa chuyển sang Router.
require BNS_ROOT . '/app/Legacy/api-router.php';
