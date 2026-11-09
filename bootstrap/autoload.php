<?php

// Autoload + hằng đường dẫn, KHÔNG mở phiên. Chỉ front controller require file này trực tiếp
// (preflight OPTIONS của API phải trả về trước khi mở phiên, như router cũ); mọi file khác
// require bootstrap/app.php.

if (defined('BNS_ROOT')) {
    return;
}
define('BNS_ROOT', dirname(__DIR__));

require BNS_ROOT . '/vendor/autoload.php';
