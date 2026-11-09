<?php
// Runner tối thiểu: php tests/php/run.php [filter]
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../bootstrap/app.php';

$GLOBALS['__tests'] = [];

function test(string $name, callable $fn): void
{
    $GLOBALS['__tests'][] = [$name, $fn];
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

$filter = $argv[1] ?? '';
foreach (glob(__DIR__ . '/*_test.php') as $file) {
    if ($filter === '' || str_contains(basename($file), $filter)) {
        require $file;
    }
}

$pass = 0;
$failures = [];
foreach ($GLOBALS['__tests'] as [$name, $fn]) {
    try {
        $fn();
        $pass++;
        echo '.';
    } catch (Throwable $e) {
        $failures[] = [$name, $e->getMessage()];
        echo 'F';
    }
}

echo "\n";
foreach ($failures as [$name, $message]) {
    echo "\nFAIL  $name\n    $message\n";
}
echo "\n$pass pass, " . count($failures) . " fail (" . count($GLOBALS['__tests']) . " test)\n";
exit($failures ? 1 : 0);
