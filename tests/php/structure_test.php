<?php
// Khoá cây thư mục sau đợt refactor (spec .scratch/folder-structure, ADR 0001).

test('structure_no_php_file_at_repo_root', function () {
    $found = [];
    foreach (scandir(BNS_ROOT) as $name) {
        // `.php-cs-fixer.dist.php` là dotfile cấu hình, .htaccess chặn nên không chạy qua web.
        if ($name[0] !== '.' && str_ends_with($name, '.php')) {
            $found[] = $name;
        }
    }
    assertSame([], $found, 'trang mới khai route + PageController + views/, không đặt file ở gốc');
});

test('structure_public_only_front_controller_and_assets', function () {
    $names = array_values(array_diff(scandir(BNS_ROOT . '/public'), ['.', '..']));
    assertSame(['assets', 'img', 'index.php'], $names);
});

test('structure_every_route_points_to_existing_method', function () {
    $config = require BNS_ROOT . '/config/routes.php';
    $missing = [];
    foreach (array_merge($config['routes'], $config['pages']['routes']) as $route) {
        [, $path, $class, $method] = $route;
        if (!class_exists($class) || !method_exists($class, $method)) {
            $missing[] = $path . ' → ' . $class . '::' . $method;
        }
    }
    assertSame([], $missing);
});

test('structure_every_route_middleware_is_registered', function () {
    $config = require BNS_ROOT . '/config/routes.php';
    $names = $config['pages']['middleware'];
    foreach ($config['groups'] as $group) {
        $names = array_merge($names, $group['middleware'] ?? []);
    }
    foreach (array_merge($config['routes'], $config['pages']['routes']) as $route) {
        $names = array_merge($names, $route[4] ?? []);
    }
    $unknown = [];
    foreach (array_unique($names) as $name) {
        $key = explode(':', $name, 2)[0];
        if (!isset(App\Http\Router::MIDDLEWARE[$key])) {
            $unknown[] = $name;
        }
    }
    assertSame([], $unknown);
});

test('structure_app_has_no_manual_class_require', function () {
    $found = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BNS_ROOT . '/app', FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        // Class nạp qua Composer PSR-4; `require $configFile` / `include $__path` (biến) không tính.
        foreach (file($file->getPathname()) as $i => $line) {
            if (preg_match('/\b(require|include)(_once)?\b[^;$]*[\'"][^\'"]*\.php[\'"]/', $line) === 1) {
                $found[] = basename($file->getPathname()) . ':' . ($i + 1);
            }
        }
    }
    assertSame([], $found);
});
