<?php
// Smoke test qua HTTP thật: so mỗi URL trong tests/smoke/urls.txt với baseline.
//
//   php tools/smoke.php                       # ẩn danh, so với baseline-anon.json
//   php tools/smoke.php --cookie=<HTSSOSESSID> # đăng nhập, chỉ GET, so với baseline-auth.json
//   thêm --update để ghi lại baseline thay vì so
//   --base=http://localhost/baonangsuat để đổi base URL
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$opts = getopt('', ['cookie:', 'base:', 'update']);
$cookie = $opts['cookie'] ?? '';
$base = rtrim($opts['base'] ?? 'http://localhost/baonangsuat', '/');
$update = isset($opts['update']);
$mode = $cookie !== '' ? 'auth' : 'anon';

$root = dirname(__DIR__);
$baselineFile = $root . '/tests/smoke/baseline-' . $mode . '.json';

// Header ổn định cần so; header thay đổi theo thời gian (Date, Set-Cookie...) bỏ qua.
$keptHeaders = [
    'content-type', 'location', 'cache-control', 'x-frame-options', 'x-content-type-options',
    'x-xss-protection', 'content-security-policy', 'referrer-policy', 'permissions-policy',
    'strict-transport-security', 'access-control-allow-origin', 'access-control-allow-credentials',
    'access-control-allow-methods', 'access-control-allow-headers',
];

$requests = [];
foreach (file($root . '/tests/smoke/urls.txt', FILE_IGNORE_NEW_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
        continue;
    }
    [$method, $path] = preg_split('/\s+/', $line, 2);
    if ($mode === 'auth' && $method !== 'GET') {
        continue;
    }
    $requests[] = [$method, $path];
}

function smokeRequest(string $url, string $method, string $cookie, bool $isApi): array
{
    $headers = [];
    $ch = curl_init($url);
    $reqHeaders = [];
    if ($isApi) {
        $reqHeaders[] = 'Origin: http://localhost:3000';
    }
    if ($method !== 'GET') {
        $reqHeaders[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $reqHeaders,
        CURLOPT_HEADERFUNCTION => function ($ch, $h) use (&$headers) {
            $parts = explode(':', $h, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))][] = trim($parts[1]);
            }
            return strlen($h);
        },
    ]);
    if ($cookie !== '') {
        curl_setopt($ch, CURLOPT_COOKIE, 'HTSSOSESSID=' . $cookie);
    }
    $body = curl_exec($ch);
    if ($body === false) {
        fwrite(STDERR, "curl lỗi $method $url: " . curl_error($ch) . "\n");
        exit(2);
    }
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return [$status, $headers, $body];
}

function bodySignature(string $contentType, string $body, int $status)
{
    if (str_contains($contentType, 'application/json')) {
        $decoded = json_decode($body, true);
        if ($decoded === null) {
            return ['invalid_json' => sha1($body)];
        }
        // Token CSRF ngẫu nhiên theo phiên: chỉ so định dạng, không so giá trị.
        // generated_at của kết quả lũy kế fallback tính lại mỗi request.
        array_walk_recursive($decoded, function (&$v, $k) {
            if ($k === 'generated_at') {
                $v = '<timestamp>';
            } elseif (is_string($v) && preg_match('/^[0-9a-f]{64}$/', $v)) {
                $v = '<hex64>';
            }
        });
        return $decoded;
    }
    if (str_contains($contentType, 'text/html')) {
        // HTML trang chứa CSRF token và giờ server nên chỉ lấy <title>.
        if (preg_match('#<title>(.*?)</title>#is', $body, $m)) {
            return ['html_title' => trim($m[1])];
        }
        return ['html' => $status >= 400 ? 'error-page' : 'no-title'];
    }
    return ['sha1' => sha1($body)];
}

$results = [];
foreach ($requests as [$method, $path]) {
    $isApi = str_starts_with($path, '/api/');
    [$status, $headers, $body] = smokeRequest($base . $path, $method, $cookie, $isApi);
    $kept = [];
    foreach ($keptHeaders as $name) {
        if (isset($headers[$name])) {
            $kept[$name] = implode(', ', $headers[$name]);
        }
    }
    $contentType = $kept['content-type'] ?? '';
    $results["$method $path"] = [
        'status' => $status,
        'headers' => $kept,
        'body' => bodySignature($contentType, $body, $status),
    ];
}

if ($update) {
    file_put_contents(
        $baselineFile,
        json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
    );
    echo "Đã ghi baseline $mode: " . count($results) . " URL → $baselineFile\n";
    exit(0);
}

if (!file_exists($baselineFile)) {
    fwrite(STDERR, "Chưa có baseline $baselineFile — chạy với --update trước.\n");
    exit(2);
}
$baseline = json_decode(file_get_contents($baselineFile), true);

$diffs = [];
foreach (array_unique(array_merge(array_keys($baseline), array_keys($results))) as $key) {
    $old = $baseline[$key] ?? null;
    $new = $results[$key] ?? null;
    if ($old === null) {
        $diffs[] = "+ $key (không có trong baseline)";
        continue;
    }
    if ($new === null) {
        $diffs[] = "- $key (không còn trong urls.txt)";
        continue;
    }
    foreach (['status', 'headers', 'body'] as $field) {
        if ($old[$field] != $new[$field]) {
            $diffs[] = "~ $key [$field]\n    baseline: "
                . json_encode($old[$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . "\n    hiện tại: "
                . json_encode($new[$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
}

if ($diffs === []) {
    echo "OK smoke $mode: " . count($results) . " URL khớp baseline\n";
    exit(0);
}
echo implode("\n", $diffs) . "\n";
echo "FAIL smoke $mode: " . count($diffs) . " khác biệt / " . count($results) . " URL\n";
exit(1);
