<?php

// Chỗ nạp duy nhất: mọi file thực thi (trang, API, test, tool) require file này trước tiên.
// Không đặt timezone ở đây: hiện chỉ navbar đặt Asia/Bangkok cho trang, API dùng mặc định
// của php.ini — đổi chỗ này là đổi hành vi.

if (defined('BNS_BOOTSTRAPPED')) {
    return;
}
define('BNS_BOOTSTRAPPED', true);
define('BNS_ROOT', dirname(__DIR__));

require BNS_ROOT . '/vendor/autoload.php';

// authV2 nằm cạnh dự án (C:/laragon/www/authV2 hoặc C:/xampp/htdocs/authV2).
require_once BNS_ROOT . '/../authV2/bootstrap.php';
