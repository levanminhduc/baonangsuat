<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Request;
use RuntimeException;
use Throwable;

// Chép từ nhansuhtdb, khác một chỗ: view của baonangsuat chuyển gần như nguyên văn từ trang cũ
// nên dùng biến rời ($session, $isAdmin...) → extract dữ liệu thay cho một biến $du_lieu.
final class View
{
    public static function render(string $template, array $data = []): string
    {
        return self::capture($template, $data)[0];
    }

    // Trang dùng khung chung: view chỉ chứa phần thân và khai `$page = [...]` (title, navTitle, navActive,
    // bodyClass, styles, scripts, modules...) — layout đọc mảng đó để dựng <head>, navbar và thẻ script.
    public static function page(string $template, array $data = [], string $layout = 'app'): string
    {
        [$content, $page] = self::capture($template, $data);

        return self::capture('layouts/' . $layout, $data + [
            'content' => $content,
            'page' => $page,
            'basePath' => Request::basePath((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')),
        ])[0];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>} [HTML, mảng $page view khai]
     */
    private static function capture(string $template, array $data): array
    {
        $path = BNS_ROOT . '/views/' . $template . '.php';
        if (!is_file($path)) {
            throw new RuntimeException('Không tìm thấy template views/' . $template . '.php');
        }

        $level = ob_get_level();
        ob_start();

        try {
            $page = (static function (string $__path, array $__data): array {
                extract($__data);
                include $__path;

                return isset($page) && is_array($page) ? $page : [];
            })($path, $data);
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return [(string) ob_get_clean(), $page];
    }
}
