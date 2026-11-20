<?php

namespace App\Services;

class PoTienDo
{
    public static function thanhPhamCongDoanId($routingSnapshotJson)
    {
        if (!is_string($routingSnapshotJson) || $routingSnapshotJson === '') {
            return null;
        }
        $snapshot = json_decode($routingSnapshotJson, true);
        if (!is_array($snapshot) || !isset($snapshot['routing']) || !is_array($snapshot['routing'])) {
            return null;
        }
        foreach ($snapshot['routing'] as $cd) {
            if (is_array($cd) && intval($cd['la_cong_doan_tinh_luy_ke'] ?? 0) === 1 && isset($cd['cong_doan_id'])) {
                return intval($cd['cong_doan_id']);
            }
        }
        return null;
    }

    public static function tinh($soLuong, $luyKe)
    {
        $luyKe = intval($luyKe);
        if ($soLuong === null) {
            return ['con_lai' => null, 'vuot' => 0];
        }
        $soLuong = intval($soLuong);
        return [
            'con_lai' => max(0, $soLuong - $luyKe),
            'vuot' => max(0, $luyKe - $soLuong),
        ];
    }

    public static function kiemTongSubart(array $soLuongKhac, $soLuongMoi, $soLuongPo)
    {
        $tong = intval($soLuongMoi);
        foreach ($soLuongKhac as $sl) {
            $tong += intval($sl);
        }
        return ['ok' => $tong <= intval($soLuongPo), 'tong' => $tong];
    }
}
