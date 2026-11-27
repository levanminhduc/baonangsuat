<?php
// Khoá cấu trúc FE theo spec F-018 (mục 3, AC1-AC5): một API client, một utils, không global, không script inline.

function feJsFiles(): array
{
    $root = BNS_ROOT . '/public/assets/js';
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $path = str_replace('\\', '/', $file->getPathname());
        if ($file->getExtension() === 'js' && !str_contains($path, '/public/assets/js/vendor/')) {
            $files[] = $path;
        }
    }
    sort($files);
    return $files;
}

function feRel(string $path): string
{
    return ltrim(substr($path, strlen(str_replace('\\', '/', BNS_ROOT))), '/');
}

function feMatchLines(string $pattern, ?callable $skipFile = null): array
{
    $found = [];
    foreach (feJsFiles() as $path) {
        if ($skipFile !== null && $skipFile(feRel($path))) {
            continue;
        }
        foreach (file($path) as $i => $line) {
            if (preg_match($pattern, $line) === 1) {
                $found[] = feRel($path) . ':' . ($i + 1);
            }
        }
    }
    return $found;
}

test('fe_structure_ac1_fetch_only_in_api_client', function () {
    $found = feMatchLines('/\bfetch\(/', fn (string $rel) => $rel === 'public/assets/js/modules/api.js');
    assertSame([], $found, 'fetch( chỉ được nằm trong modules/api.js');
});

test('fe_structure_ac1_admin_api_removed', function () {
    assertSame(false, file_exists(BNS_ROOT . '/public/assets/js/modules/admin-api.js'), 'admin-api.js còn tồn tại');
    assertSame([], feMatchLines('/admin-api/'), 'còn tham chiếu admin-api');
});

test('fe_structure_ac2_admin_utils_removed', function () {
    assertSame(false, file_exists(BNS_ROOT . '/public/assets/js/modules/admin-utils.js'), 'admin-utils.js còn tồn tại');
    assertSame([], feMatchLines('/admin-utils/'), 'còn tham chiếu admin-utils');
});

test('fe_structure_ac2_no_native_confirm_or_alert', function () {
    assertSame([], feMatchLines('/\b(confirm|alert)\(/'));
});

test('fe_structure_ac2_shared_helpers_defined_once_in_utils', function () {
    $names = ['showLoading', 'hideLoading', 'showToast', 'closeModal', 'showConfirmModal'];
    $defs = array_fill_keys($names, []);
    foreach (feJsFiles() as $path) {
        foreach (file($path) as $i => $line) {
            foreach ($names as $name) {
                $isFunction = preg_match('/function ' . $name . '\b/', $line) === 1;
                $isMethod = $name === 'showConfirmModal' && preg_match('/^\s+showConfirmModal\([^)]*\)\s*\{/', $line) === 1;
                if ($isFunction || $isMethod) {
                    $defs[$name][] = feRel($path) . ':' . ($i + 1);
                }
            }
        }
    }
    $bad = [];
    foreach ($defs as $name => $where) {
        $inUtils = count($where) === 1 && str_starts_with($where[0], 'public/assets/js/modules/utils.js:');
        if (!$inUtils) {
            $bad[$name] = $where;
        }
    }
    assertSame([], $bad, 'mỗi hàm đúng 1 định nghĩa, trong modules/utils.js');
});

test('fe_structure_ac3_no_window_assignment', function () {
    assertSame([], feMatchLines('/window\.[A-Za-z_]+\s*=/'));
});

test('fe_structure_ac3_no_data_fn_whitelist_or_window_index', function () {
    assertSame([], feMatchLines('/DATA_FN_ALLOWED|window\[/'));
});

test('fe_structure_ac4_every_script_tag_has_src_or_json_type', function () {
    $found = [];
    foreach (['views', 'components'] as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BNS_ROOT . '/' . $dir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            // Thẻ mở có thể trải nhiều dòng và chứa khối echo của PHP.
            preg_match_all('/<script\b(?:<\?.*?\?>|[^>])*>/is', $content, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[0] as [$tag, $offset]) {
                if (preg_match('/\bsrc\s*=|type\s*=\s*["\']application\/json["\']/i', $tag) !== 1) {
                    $line = substr_count($content, "\n", 0, $offset) + 1;
                    $found[] = feRel(str_replace('\\', '/', $file->getPathname())) . ':' . $line;
                }
            }
        }
    }
    assertSame([], $found, 'script inline thực thi');
});

test('fe_structure_ac4_csp_script_src_without_unsafe_inline', function () {
    $content = file_get_contents(BNS_ROOT . '/app/Http/Middleware/SecurityHeaders.php');
    $pos = strpos($content, 'script-src');
    assertSame(true, $pos !== false, 'không thấy script-src');
    $end = strpos($content, ';', $pos);
    $segment = substr($content, $pos, $end === false ? null : $end - $pos);
    assertSame(false, str_contains($segment, "'unsafe-inline'"), "script-src còn 'unsafe-inline': " . $segment);
});

test('fe_structure_ac5_no_js_file_over_500_lines', function () {
    $found = [];
    foreach (feJsFiles() as $path) {
        $lines = count(file($path));
        if ($lines > 500) {
            $found[] = feRel($path) . ' (' . $lines . ')';
        }
    }
    assertSame([], $found);
});

test('fe_structure_js_files_pass_node_check', function () {
    $node = trim((string) shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where node 2>NUL' : 'command -v node 2>/dev/null'));
    if ($node === '') {
        skip('không có node trong PATH');
    }
    $failed = [];
    foreach (feJsFiles() as $path) {
        $output = [];
        exec('node --check ' . escapeshellarg($path) . ' 2>&1', $output, $code);
        if ($code !== 0) {
            $failed[] = feRel($path) . ': ' . ($output[0] ?? '');
        }
    }
    assertSame([], $failed);
});
