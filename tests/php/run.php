<?php
// Runner tối thiểu: php tests/php/run.php [filter]
// Test tích hợp DB chỉ chạy khi BNS_DB_CONFIG trỏ config có DB tên *_test (xem support/db.php).
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../bootstrap/app.php';

final class SkipTest extends RuntimeException
{
}

$GLOBALS['__tests'] = [];

function test(string $name, callable $fn): void
{
    $GLOBALS['__tests'][] = [$name, $fn];
}

function skip(string $reason): void
{
    throw new SkipTest($reason);
}

function assertSame($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            ($msg !== '' ? $msg . ' — ' : '')
            . 'mong ' . json_encode($expected, JSON_UNESCAPED_UNICODE)
            . ', nhận ' . json_encode($actual, JSON_UNESCAPED_UNICODE)
        );
    }
}

foreach (glob(__DIR__ . '/support/*.php') as $file) {
    require_once $file;
}

$filter = $argv[1] ?? '';
foreach (glob(__DIR__ . '/*_test.php') as $file) {
    if ($filter === '' || str_contains(basename($file), $filter)) {
        require $file;
    }
}

$pass = 0;
$skipped = [];
$failures = [];
foreach ($GLOBALS['__tests'] as [$name, $fn]) {
    try {
        $fn();
        $pass++;
        echo '.';
    } catch (SkipTest $e) {
        $skipped[] = [$name, $e->getMessage()];
        echo 'S';
    } catch (Throwable $e) {
        $failures[] = [$name, $e->getMessage()];
        echo 'F';
    }
}

echo "\n";
foreach ($failures as [$name, $message]) {
    echo "\nFAIL  $name\n    $message\n";
}
if ($skipped) {
    echo "\nSKIP  " . count($skipped) . ' test: ' . $skipped[0][1] . "\n";
}
echo "\n$pass pass, " . count($failures) . ' fail, ' . count($skipped) . ' skip (' . count($GLOBALS['__tests']) . " test)\n";
exit($failures ? 1 : 0);
