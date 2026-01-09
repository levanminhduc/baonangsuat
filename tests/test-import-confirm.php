<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/services/ImportService.php';
require_once __DIR__ . '/../config/Database.php';

$db = Database::getNangSuat();

echo "=== Database state BEFORE import ===\n\n";

echo "Ma hang:\n";
$result = mysqli_query($db, "SELECT id, ma_hang, ten_hang FROM ma_hang ORDER BY id DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($result)) {
    echo "  [{$row['id']}] {$row['ma_hang']} - {$row['ten_hang']}\n";
}

echo "\nCong doan:\n";
$result = mysqli_query($db, "SELECT id, ma_cong_doan, ten_cong_doan FROM cong_doan ORDER BY id DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($result)) {
    echo "  [{$row['id']}] {$row['ma_cong_doan']} - {$row['ten_cong_doan']}\n";
}

$maHangList = [
    [
        'ma_hang' => '9001',
        'ten_hang' => 'Mã hàng 9001',
        'cong_doan_list' => [
            ['thu_tu' => 1, 'ten_cong_doan' => 'Cắt vải', 'ma_cong_doan' => 'CD006', 'existing_id' => null],
            ['thu_tu' => 2, 'ten_cong_doan' => 'May thân trước', 'ma_cong_doan' => 'CD007', 'existing_id' => null],
            ['thu_tu' => 3, 'ten_cong_doan' => 'May thân sau', 'ma_cong_doan' => 'CD008', 'existing_id' => null],
            ['thu_tu' => 4, 'ten_cong_doan' => 'Ráp sườn', 'ma_cong_doan' => 'CD009', 'existing_id' => null],
            ['thu_tu' => 5, 'ten_cong_doan' => 'May cổ', 'ma_cong_doan' => 'CD010', 'existing_id' => null]
        ]
    ],
    [
        'ma_hang' => '9002',
        'ten_hang' => 'Mã hàng 9002',
        'cong_doan_list' => [
            ['thu_tu' => 1, 'ten_cong_doan' => 'Cắt vải', 'ma_cong_doan' => 'CD006', 'existing_id' => null],
            ['thu_tu' => 2, 'ten_cong_doan' => 'May túi', 'ma_cong_doan' => 'CD011', 'existing_id' => null],
            ['thu_tu' => 3, 'ten_cong_doan' => 'Đóng gói', 'ma_cong_doan' => 'CD04', 'existing_id' => 4]
        ]
    ]
];

echo "\n=== Testing ImportService::confirm() ===\n\n";

$importService = new ImportService();
$result = $importService->confirm($maHangList);

echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
echo "Message: " . $result['message'] . "\n\n";

echo "=== STATS ===\n";
print_r($result['stats']);

echo "\n=== Database state AFTER import ===\n\n";

echo "Ma hang:\n";
$dbResult = mysqli_query($db, "SELECT id, ma_hang, ten_hang FROM ma_hang ORDER BY id DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($dbResult)) {
    echo "  [{$row['id']}] {$row['ma_hang']} - {$row['ten_hang']}\n";
}

echo "\nCong doan:\n";
$dbResult = mysqli_query($db, "SELECT id, ma_cong_doan, ten_cong_doan FROM cong_doan ORDER BY id DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($dbResult)) {
    echo "  [{$row['id']}] {$row['ma_cong_doan']} - {$row['ten_cong_doan']}\n";
}

echo "\nRouting for MH 9001:\n";
$dbResult = mysqli_query($db, "
    SELECT mhcd.thu_tu, cd.ma_cong_doan, cd.ten_cong_doan, mh.ma_hang
    FROM ma_hang_cong_doan mhcd
    JOIN cong_doan cd ON mhcd.cong_doan_id = cd.id
    JOIN ma_hang mh ON mhcd.ma_hang_id = mh.id
    WHERE mh.ma_hang = '9001'
    ORDER BY mhcd.thu_tu
");
while ($row = mysqli_fetch_assoc($dbResult)) {
    echo "  {$row['thu_tu']}. {$row['ma_cong_doan']} - {$row['ten_cong_doan']}\n";
}

echo "\nRouting for MH 9002:\n";
$dbResult = mysqli_query($db, "
    SELECT mhcd.thu_tu, cd.ma_cong_doan, cd.ten_cong_doan, mh.ma_hang
    FROM ma_hang_cong_doan mhcd
    JOIN cong_doan cd ON mhcd.cong_doan_id = cd.id
    JOIN ma_hang mh ON mhcd.ma_hang_id = mh.id
    WHERE mh.ma_hang = '9002'
    ORDER BY mhcd.thu_tu
");
while ($row = mysqli_fetch_assoc($dbResult)) {
    echo "  {$row['thu_tu']}. {$row['ma_cong_doan']} - {$row['ten_cong_doan']}\n";
}

echo "\n=== JSON OUTPUT ===\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
