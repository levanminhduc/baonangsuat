<?php
require_once __DIR__ . '/../config/Database.php';

$db = Database::getNangSuat();

echo "=== DATABASE VERIFICATION ===\n\n";

echo "--- MA_HANG ---\n";
$result = mysqli_query($db, "SELECT id, ma_hang, ten_hang FROM ma_hang WHERE ma_hang IN ('9001', '9002')");
while ($row = mysqli_fetch_assoc($result)) {
    echo "  [{$row['id']}] {$row['ma_hang']} - {$row['ten_hang']}\n";
}

echo "\n--- CONG_DOAN (imported) ---\n";
$result = mysqli_query($db, "SELECT id, ma_cong_doan, ten_cong_doan FROM cong_doan WHERE ma_cong_doan LIKE 'CD0%' ORDER BY id");
while ($row = mysqli_fetch_assoc($result)) {
    echo "  [{$row['id']}] {$row['ma_cong_doan']} - {$row['ten_cong_doan']}\n";
}

echo "\n--- ROUTING for 9001 ---\n";
$result = mysqli_query($db, "
    SELECT mhcd.thu_tu, cd.ma_cong_doan, cd.ten_cong_doan, mh.ma_hang
    FROM ma_hang_cong_doan mhcd
    JOIN cong_doan cd ON mhcd.cong_doan_id = cd.id
    JOIN ma_hang mh ON mhcd.ma_hang_id = mh.id
    WHERE mh.ma_hang = '9001'
    ORDER BY mhcd.thu_tu
");
while ($row = mysqli_fetch_assoc($result)) {
    echo "  {$row['thu_tu']}. {$row['ma_cong_doan']} - {$row['ten_cong_doan']}\n";
}

echo "\n--- ROUTING for 9002 ---\n";
$result = mysqli_query($db, "
    SELECT mhcd.thu_tu, cd.ma_cong_doan, cd.ten_cong_doan, mh.ma_hang
    FROM ma_hang_cong_doan mhcd
    JOIN cong_doan cd ON mhcd.cong_doan_id = cd.id
    JOIN ma_hang mh ON mhcd.ma_hang_id = mh.id
    WHERE mh.ma_hang = '9002'
    ORDER BY mhcd.thu_tu
");
while ($row = mysqli_fetch_assoc($result)) {
    echo "  {$row['thu_tu']}. {$row['ma_cong_doan']} - {$row['ten_cong_doan']}\n";
}

echo "\n--- STATISTICS ---\n";
$result = mysqli_query($db, "SELECT COUNT(*) as cnt FROM ma_hang WHERE ma_hang IN ('9001', '9002')");
$row = mysqli_fetch_assoc($result);
echo "  Mã hàng 9001/9002: {$row['cnt']}\n";

$result = mysqli_query($db, "SELECT COUNT(*) as cnt FROM cong_doan WHERE ma_cong_doan LIKE 'CD0%'");
$row = mysqli_fetch_assoc($result);
echo "  Công đoạn CD0xx: {$row['cnt']}\n";

$result = mysqli_query($db, "
    SELECT COUNT(*) as cnt FROM ma_hang_cong_doan mhcd
    JOIN ma_hang mh ON mhcd.ma_hang_id = mh.id
    WHERE mh.ma_hang IN ('9001', '9002')
");
$row = mysqli_fetch_assoc($result);
echo "  Routing cho 9001/9002: {$row['cnt']}\n";

echo "\n=== VERIFICATION COMPLETE ===\n";
