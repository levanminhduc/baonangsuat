<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use Throwable;

// Chép từ nhansuhtdb, khác một chỗ: view của baonangsuat chuyển gần như nguyên văn từ trang cũ
// nên dùng biến rời ($session, $isAdmin...) → extract dữ liệu thay cho một biến $du_lieu.
final class View
{
    public static function render(string $template, array $data = []): string
    {
        $path = BNS_ROOT . '/views/' . $template . '.php';
        if (!is_file($path)) {
            throw new RuntimeException('Không tìm thấy template views/' . $template . '.php');
        }

        $level = ob_get_level();
        ob_start();

        try {
            (static function (string $__path, array $__data): void {
                extract($__data);
                include $__path;
            })($path, $data);
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return (string) ob_get_clean();
    }
}
