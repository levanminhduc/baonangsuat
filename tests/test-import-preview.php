<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/services/ImportService.php';

$testFile = __DIR__ . '/import-test.xlsx';

if (!file_exists($testFile)) {
    die("Test file not found: $testFile\n");
}

echo "=== Testing ImportService::preview() ===\n\n";

$importService = new ImportService();
$result = $importService->preview($testFile);

echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
echo "Message: " . $result['message'] . "\n\n";

echo "=== STATS ===\n";
print_r($result['stats']);

echo "\n=== DATA ===\n";
foreach ($result['data'] as $item) {
    echo "Sheet: {$item['sheet_name']}\n";
    echo "  Ma hang: {$item['ma_hang']} (is_new: " . ($item['is_new'] ? 'YES' : 'NO') . ")\n";
    echo "  Cong doan: " . count($item['cong_doan_list']) . " items\n";
    foreach ($item['cong_doan_list'] as $cd) {
        echo "    - {$cd['thu_tu']}. {$cd['ten_cong_doan']} ({$cd['ma_cong_doan']}) " . 
             ($cd['is_new'] ? '[NEW]' : "[ID:{$cd['existing_id']}]") . "\n";
    }
    echo "\n";
}

echo "=== ERRORS ===\n";
if (empty($result['errors'])) {
    echo "No errors\n";
} else {
    foreach ($result['errors'] as $error) {
        echo "- Sheet '{$error['sheet_name']}': {$error['message']} (Code: {$error['error_code']})\n";
    }
}

echo "\n=== JSON OUTPUT ===\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
