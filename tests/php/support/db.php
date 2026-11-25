<?php

use App\Support\Database;

// Trả kết nối DB test đã nạp schema + seed, bảng báo cáo rỗng.
// Không đặt BNS_DB_CONFIG → skip. DB không mang đuôi _test → fail ngay, không chạy câu ghi nào.
function bnsTestDb(): mysqli
{
    static $ready = false;

    if (!getenv('BNS_DB_CONFIG')) {
        skip('chưa đặt BNS_DB_CONFIG (DB test)');
    }

    $db = Database::getNangSuat();
    $name = (string) mysqli_fetch_row(mysqli_query($db, 'SELECT DATABASE()'))[0];
    if (!str_ends_with($name, '_test')) {
        throw new RuntimeException("Từ chối chạy test tích hợp trên DB '$name' (tên phải kết thúc _test)");
    }

    if (!$ready) {
        bnsTestDbRebuild($db);
        $ready = true;
    }

    mysqli_query($db, 'DELETE FROM bao_cao_nang_suat');

    return $db;
}

function bnsTestDbRebuild(mysqli $db): void
{
    mysqli_query($db, 'SET FOREIGN_KEY_CHECKS = 0');
    $tables = mysqli_query($db, 'SHOW TABLES');
    while ($row = mysqli_fetch_row($tables)) {
        mysqli_query($db, 'DROP TABLE `' . $row[0] . '`');
    }
    mysqli_query($db, 'SET FOREIGN_KEY_CHECKS = 1');

    $seed = "
        INSERT INTO line (id, ma_line, ten_line) VALUES (1, 'T01', 'Tổ 1'), (2, 'T02', 'Tổ 2');
        INSERT INTO ma_hang (id, ma_hang, ten_hang) VALUES (1, 'MH1', 'Mã hàng 1');
        INSERT INTO cong_doan (id, ma_cong_doan, ten_cong_doan) VALUES (1, 'CD1', 'May'), (2, 'CD2', 'Thành phẩm');
        INSERT INTO ma_hang_cong_doan (ma_hang_id, line_id, cong_doan_id, thu_tu, la_cong_doan_tinh_luy_ke)
        VALUES (1, NULL, 1, 1, 0), (1, NULL, 2, 2, 1);
    ";
    mysqli_multi_query($db, file_get_contents(BNS_ROOT . '/database/schema.sql') . $seed);
    do {
        if ($result = mysqli_store_result($db)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($db) && mysqli_next_result($db));

    if (mysqli_errno($db)) {
        throw new RuntimeException('Nạp schema test lỗi: ' . mysqli_error($db));
    }
}

function bnsTestRow(mysqli $db, string $sql): ?array
{
    return mysqli_fetch_assoc(mysqli_query($db, $sql));
}
