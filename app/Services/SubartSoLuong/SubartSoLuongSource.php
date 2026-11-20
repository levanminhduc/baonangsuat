<?php

namespace App\Services\SubartSoLuong;

// Nguồn SL PO – Subart. Hiện nhập tay (MySQL); sau này thêm nguồn datchi chỉ đọc.
interface SubartSoLuongSource
{
    public function nguon(): string;

    public function laEditable(): bool;

    // ['available' => bool, 'items' => [sub_art_code => so_luong]]
    public function getSoLuong(string $poNumber, string $styleCode): array;
}
