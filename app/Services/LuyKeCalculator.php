<?php

namespace App\Services;

class LuyKeCalculator
{
    public function calculateChiTieuLuyKe($baoCao)
    {
        $chiTieuLuyKe = [];
        $ctns = intval($baoCao['ctns']);
        $tongPhut = intval($baoCao['tong_phut_hieu_dung']);

        if ($tongPhut <= 0 || $ctns <= 0) {
            foreach ($baoCao['moc_gio_list'] as $moc) {
                $chiTieuLuyKe[$moc['id']] = 0;
            }
            return $chiTieuLuyKe;
        }

        $lastMocId = null;
        foreach ($baoCao['moc_gio_list'] as $moc) {
            $phutLuyKe = intval($moc['so_phut_hieu_dung_luy_ke']);
            $chiTieu = round($ctns * $phutLuyKe / $tongPhut);
            $chiTieuLuyKe[$moc['id']] = $chiTieu;
            $lastMocId = $moc['id'];
        }

        if ($lastMocId !== null) {
            $chiTieuLuyKe[$lastMocId] = $ctns;
        }

        return $chiTieuLuyKe;
    }

    public function calculateLuyKeThucTe($baoCao)
    {
        $luyKeThucTe = [];
        $congDoanThanhPhamId = null;

        foreach ($baoCao['routing'] as $cd) {
            if ($cd['la_cong_doan_tinh_luy_ke'] == 1) {
                $congDoanThanhPhamId = $cd['cong_doan_id'];
                break;
            }
        }

        if (!$congDoanThanhPhamId) {
            foreach ($baoCao['moc_gio_list'] as $moc) {
                $luyKeThucTe[$moc['id']] = 0;
            }
            return $luyKeThucTe;
        }

        $cumulativeSum = 0;
        foreach ($baoCao['moc_gio_list'] as $moc) {
            $key = $congDoanThanhPhamId . '_' . $moc['id'];
            $entry = $baoCao['entries'][$key] ?? null;

            if ($entry && $entry['kieu_nhap'] === 'luy_ke') {
                $luyKeThucTe[$moc['id']] = intval($entry['so_luong']);
            } else {
                $soLuong = $entry ? intval($entry['so_luong']) : 0;
                $cumulativeSum += $soLuong;
                $luyKeThucTe[$moc['id']] = $cumulativeSum;
            }
        }

        return $luyKeThucTe;
    }

    public static function buildKetQua(array $baoCao, string $now): array
    {
        $ctns = intval($baoCao['ctns']);
        $tongPhut = intval($baoCao['tong_phut_hieu_dung']);
        $ctGio = floatval($baoCao['ct_gio']);

        $mocGioList = $baoCao['moc_gio_list'];
        $lastMoc = count($mocGioList) > 0 ? $mocGioList[count($mocGioList) - 1] : null;
        $soPhutHieuDungLuyKeCuoi = $lastMoc ? intval($lastMoc['so_phut_hieu_dung_luy_ke']) : 0;

        $chiTieuLuyKeTong = 0;
        if ($tongPhut > 0 && $ctns > 0) {
            $chiTieuLuyKeTong = round($ctns * $soPhutHieuDungLuyKeCuoi / $tongPhut);
        }

        $luyKeThucTeTong = 0;
        $congDoanDetails = [];

        foreach ($baoCao['routing'] as $cd) {
            $congDoanId = $cd['cong_doan_id'];
            $laCongDoanTinhLuyKe = intval($cd['la_cong_doan_tinh_luy_ke']);

            $luyKeCongDoan = 0;
            if ($lastMoc) {
                $key = $congDoanId . '_' . $lastMoc['id'];
                $entry = $baoCao['entries'][$key] ?? null;
                if ($entry) {
                    $luyKeCongDoan = intval($entry['so_luong']);
                }
            }

            $chiTieuCongDoan = $chiTieuLuyKeTong;

            $trangThai = 'na';
            if ($chiTieuCongDoan > 0) {
                $trangThai = $luyKeCongDoan >= $chiTieuCongDoan ? 'dat' : 'chua_dat';
            }

            if ($laCongDoanTinhLuyKe === 1) {
                $luyKeThucTeTong = $luyKeCongDoan;
            }

            $congDoanDetails[] = [
                'cong_doan_id' => $congDoanId,
                'cong_doan_ten' => $cd['ten_cong_doan'],
                'la_cong_doan_tinh_luy_ke' => $laCongDoanTinhLuyKe,
                'luy_ke_thuc_te' => $luyKeCongDoan,
                'chi_tieu_luy_ke' => $chiTieuCongDoan,
                'trang_thai' => $trangThai,
            ];
        }

        $trangThaiTong = 'na';
        if ($chiTieuLuyKeTong > 0) {
            $trangThaiTong = $luyKeThucTeTong >= $chiTieuLuyKeTong ? 'dat' : 'chua_dat';
        }


        return [
            'version' => 1,
            'generated_at' => $now,
            'source' => [
                'bao_cao_id' => intval($baoCao['id'] ?? 0),
                'ngay' => $baoCao['ngay_bao_cao'],
                'line_id' => intval($baoCao['line_id']),
                'ca_id' => intval($baoCao['ca_id']),
                'ma_hang_id' => intval($baoCao['ma_hang_id']),
            ],
            'inputs' => [
                'so_lao_dong' => intval($baoCao['so_lao_dong']),
                'ctns' => $ctns,
                'tong_phut_hieu_dung' => $tongPhut,
            ],
            'tong_hop' => [
                'ct_gio' => $ctGio,
                'luy_ke_thuc_te' => $luyKeThucTeTong,
                'chi_tieu_luy_ke' => $chiTieuLuyKeTong,
                'trang_thai' => $trangThaiTong,
            ],
            'cong_doan' => $congDoanDetails,
        ];
    }
}
