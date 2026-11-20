<?php

namespace App\Services\SubartSoLuong;

use App\Support\Database;

class SubartSoLuongFactory
{
    public static function make(): SubartSoLuongSource
    {
        return self::fromConfig(Database::getOptionalConfig('subart_so_luong_source'));
    }

    // Chỉ có nguồn 'manual'; thêm 'datchi' khi datchi có import SL theo subart.
    public static function fromConfig($value): SubartSoLuongSource
    {
        return new ManualSubartSource();
    }
}
