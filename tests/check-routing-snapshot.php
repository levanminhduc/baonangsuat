<?php
require_once __DIR__ . '/../config/Database.php';

$db = Database::getNangSuat();

echo "=== KIỂM TRA BÁO CÁO KHÔNG CÓ ROUTING_SNAPSHOT ===\n\n";

$query1 = "
SELECT 
    trang_thai,
    COUNT(*) as tong,
    SUM(CASE WHEN routing_snapshot IS NOT NULL THEN 1 ELSE 0 END) as co_snapshot,
    SUM(CASE WHEN routing_snapshot IS NULL THEN 1 ELSE 0 END) as khong_co_snapshot
FROM bao_cao_nang_suat
GROUP BY trang_thai
ORDER BY FIELD(trang_thai, 'draft', 'submitted', 'approved', 'locked', 'completed')
";

$result1 = mysqli_query($db, $query1);

echo "1. THỐNG KÊ THEO TRẠNG THÁI:\n";
echo str_repeat("-", 70) . "\n";
printf("%-15s | %-8s | %-12s | %-18s\n", "Trạng thái", "Tổng", "Có snapshot", "Không có snapshot");
echo str_repeat("-", 70) . "\n";

$totalAll = 0;
$totalWithSnapshot = 0;
$totalWithoutSnapshot = 0;

while ($row = mysqli_fetch_assoc($result1)) {
    printf("%-15s | %-8d | %-12d | %-18d\n", 
        $row['trang_thai'], 
        $row['tong'], 
        $row['co_snapshot'], 
        $row['khong_co_snapshot']
    );
    $totalAll += $row['tong'];
    $totalWithSnapshot += $row['co_snapshot'];
    $totalWithoutSnapshot += $row['khong_co_snapshot'];
}

echo str_repeat("-", 70) . "\n";
printf("%-15s | %-8d | %-12d | %-18d\n", "TỔNG CỘNG", $totalAll, $totalWithSnapshot, $totalWithoutSnapshot);
echo "\n";

$query2 = "
SELECT 
    bc.id,
    bc.ngay_bao_cao,
    bc.trang_thai,
    mh.ma_hang,
    l.ten_line,
    c.ten_ca,
    CASE WHEN bc.routing_snapshot IS NOT NULL THEN 'Có' ELSE 'KHÔNG' END as co_snapshot
FROM bao_cao_nang_suat bc
JOIN ma_hang mh ON mh.id = bc.ma_hang_id
JOIN line l ON l.id = bc.line_id
JOIN ca_lam c ON c.id = bc.ca_id
WHERE bc.routing_snapshot IS NULL
ORDER BY bc.trang_thai DESC, bc.ngay_bao_cao DESC
";

$result2 = mysqli_query($db, $query2);

echo "2. CHI TIẾT BÁO CÁO KHÔNG CÓ ROUTING_SNAPSHOT (CÓ RỦI RO):\n";
echo str_repeat("-", 100) . "\n";
printf("%-5s | %-12s | %-12s | %-10s | %-15s | %-15s\n", 
    "ID", "Ngày", "Trạng thái", "Mã hàng", "Line", "Ca");
echo str_repeat("-", 100) . "\n";

$count = 0;
while ($row = mysqli_fetch_assoc($result2)) {
    $riskIcon = in_array($row['trang_thai'], ['submitted', 'approved', 'locked', 'completed']) ? '⚠️' : '';
    printf("%-5d | %-12s | %-12s | %-10s | %-15s | %-15s %s\n", 
        $row['id'],
        $row['ngay_bao_cao'],
        $row['trang_thai'],
        $row['ma_hang'],
        substr($row['ten_line'], 0, 15),
        substr($row['ten_ca'], 0, 15),
        $riskIcon
    );
    $count++;
}

if ($count === 0) {
    echo "Không có báo cáo nào thiếu routing_snapshot!\n";
}

echo str_repeat("-", 100) . "\n";
echo "Tổng: $count báo cáo không có routing_snapshot\n\n";

$query3 = "
SELECT 
    bc.trang_thai,
    COUNT(*) as so_luong
FROM bao_cao_nang_suat bc
WHERE bc.routing_snapshot IS NULL
  AND bc.trang_thai IN ('submitted', 'approved', 'locked', 'completed')
GROUP BY bc.trang_thai
";

$result3 = mysqli_query($db, $query3);

echo "3. BÁO CÁO ĐÃ CHỐT NHƯNG KHÔNG CÓ SNAPSHOT (RỦI RO CAO):\n";
echo str_repeat("-", 50) . "\n";

$highRiskCount = 0;
while ($row = mysqli_fetch_assoc($result3)) {
    echo "   - {$row['trang_thai']}: {$row['so_luong']} báo cáo\n";
    $highRiskCount += $row['so_luong'];
}

if ($highRiskCount === 0) {
    echo "   Không có báo cáo đã chốt nào thiếu routing_snapshot!\n";
} else {
    echo "\n   ⚠️ CẢNH BÁO: $highRiskCount báo cáo đã chốt có thể bị ảnh hưởng khi import routing mới!\n";
}

echo "\n";
echo "=== KẾT LUẬN ===\n";
echo "- Báo cáo CÓ routing_snapshot: Được bảo vệ, không bị ảnh hưởng khi import\n";
echo "- Báo cáo KHÔNG CÓ routing_snapshot: Sẽ sử dụng routing hiện tại từ database\n";
echo "  → Nếu import thay đổi routing, các báo cáo này sẽ hiển thị routing mới\n";
