<?php

namespace App\Services\SubartSoLuong;

use App\Support\Database;

class ManualSubartSource implements SubartSoLuongSource
{
    public function nguon(): string
    {
        return 'manual';
    }

    public function laEditable(): bool
    {
        return true;
    }

    public function getSoLuong(string $poNumber, string $styleCode): array
    {
        $db = Database::getNangSuat();
        $stmt = mysqli_prepare(
            $db,
            'SELECT p.sub_art_code, p.so_luong
             FROM po_subart_so_luong p
             JOIN ma_hang mh ON mh.id = p.ma_hang_id
             WHERE p.po_number = ? AND UPPER(TRIM(mh.ma_hang)) = ?',
        );
        $styleCode = strtoupper(trim($styleCode));
        mysqli_stmt_bind_param($stmt, 'ss', $poNumber, $styleCode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $items[$row['sub_art_code']] = intval($row['so_luong']);
        }
        mysqli_stmt_close($stmt);

        return ['available' => true, 'items' => $items];
    }
}
