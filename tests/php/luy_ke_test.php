<?php
use App\Services\LuyKeCalculator;

function luyKeService(): LuyKeCalculator
{
    return new LuyKeCalculator();
}

function luyKeMocList(): array
{
    return [
        ['id' => 10, 'so_phut_hieu_dung_luy_ke' => 60],
        ['id' => 11, 'so_phut_hieu_dung_luy_ke' => 120],
        ['id' => 12, 'so_phut_hieu_dung_luy_ke' => 240],
    ];
}

function luyKeBaoCao(array $over = []): array
{
    return array_merge([
        'ctns' => 100,
        'tong_phut_hieu_dung' => 240,
        'ct_gio' => 12.5,
        'ngay_bao_cao' => '2026-01-05',
        'line_id' => 3,
        'ca_id' => 1,
        'ma_hang_id' => 7,
        'so_lao_dong' => 20,
        'moc_gio_list' => luyKeMocList(),
        'routing' => [
            ['cong_doan_id' => 1, 'ten_cong_doan' => 'Cắt', 'la_cong_doan_tinh_luy_ke' => 0],
            ['cong_doan_id' => 2, 'ten_cong_doan' => 'Đóng gói', 'la_cong_doan_tinh_luy_ke' => 1],
        ],
        'entries' => [],
    ], $over);
}

function luyKeEntry(int $soLuong, string $kieuNhap = 'luy_ke'): array
{
    return ['so_luong' => $soLuong, 'kieu_nhap' => $kieuNhap];
}

test('luy_ke_chi_tieu_proportional_to_minutes', function () {
    $r = luyKeService()->calculateChiTieuLuyKe(luyKeBaoCao());
    assertSame([10 => 25.0, 11 => 50.0, 12 => 100], $r);
});

test('luy_ke_chi_tieu_last_slot_equals_ctns_even_when_rounding_differs', function () {
    $r = luyKeService()->calculateChiTieuLuyKe(luyKeBaoCao([
        'ctns' => 101,
        'tong_phut_hieu_dung' => 300,
    ]));
    assertSame(101, $r[12]);
    assertSame(20.0, $r[10]);
});

test('luy_ke_chi_tieu_rounds_half_up', function () {
    $r = luyKeService()->calculateChiTieuLuyKe(luyKeBaoCao(['ctns' => 5, 'tong_phut_hieu_dung' => 240]));
    assertSame(1.0, $r[10]);
    assertSame(3.0, $r[11]);
});

test('luy_ke_chi_tieu_zero_when_ctns_not_positive', function () {
    $r = luyKeService()->calculateChiTieuLuyKe(luyKeBaoCao(['ctns' => 0]));
    assertSame([10 => 0, 11 => 0, 12 => 0], $r);
});

test('luy_ke_chi_tieu_zero_when_total_minutes_not_positive', function () {
    $r = luyKeService()->calculateChiTieuLuyKe(luyKeBaoCao(['tong_phut_hieu_dung' => 0]));
    assertSame([10 => 0, 11 => 0, 12 => 0], $r);
});

test('luy_ke_chi_tieu_accepts_string_numbers', function () {
    $r = luyKeService()->calculateChiTieuLuyKe(luyKeBaoCao([
        'ctns' => '100',
        'tong_phut_hieu_dung' => '240',
        'moc_gio_list' => [
            ['id' => '10', 'so_phut_hieu_dung_luy_ke' => '120'],
            ['id' => '11', 'so_phut_hieu_dung_luy_ke' => '240'],
        ],
    ]));
    assertSame([10 => 50.0, 11 => 100], $r);
});

test('luy_ke_chi_tieu_empty_moc_list_is_empty', function () {
    assertSame([], luyKeService()->calculateChiTieuLuyKe(luyKeBaoCao(['moc_gio_list' => []])));
    assertSame([], luyKeService()->calculateChiTieuLuyKe(luyKeBaoCao(['moc_gio_list' => [], 'ctns' => 0])));
});

test('luy_ke_thuc_te_uses_only_luy_ke_stage_value_at_each_slot', function () {
    $r = luyKeService()->calculateLuyKeThucTe(luyKeBaoCao([
        'entries' => [
            '1_10' => luyKeEntry(999),
            '1_11' => luyKeEntry(999),
            '2_10' => luyKeEntry(30),
            '2_11' => luyKeEntry(55),
            '2_12' => luyKeEntry(80),
        ],
    ]));
    assertSame([10 => 30, 11 => 55, 12 => 80], $r);
});

test('luy_ke_thuc_te_cell_is_cumulative_not_summed', function () {
    $r = luyKeService()->calculateLuyKeThucTe(luyKeBaoCao([
        'entries' => ['2_10' => luyKeEntry(30), '2_11' => luyKeEntry(55)],
    ]));
    assertSame(55, $r[11]);
    assertSame(0, $r[12]);
});

test('luy_ke_thuc_te_picks_first_luy_ke_stage', function () {
    $r = luyKeService()->calculateLuyKeThucTe(luyKeBaoCao([
        'routing' => [
            ['cong_doan_id' => 1, 'la_cong_doan_tinh_luy_ke' => 0],
            ['cong_doan_id' => 2, 'la_cong_doan_tinh_luy_ke' => 1],
            ['cong_doan_id' => 3, 'la_cong_doan_tinh_luy_ke' => 1],
        ],
        'entries' => ['2_10' => luyKeEntry(5), '3_10' => luyKeEntry(9)],
    ]));
    assertSame(5, $r[10]);
});

test('luy_ke_thuc_te_accepts_string_flag_and_ids', function () {
    $r = luyKeService()->calculateLuyKeThucTe(luyKeBaoCao([
        'routing' => [['cong_doan_id' => '2', 'la_cong_doan_tinh_luy_ke' => '1']],
        'moc_gio_list' => [['id' => '10', 'so_phut_hieu_dung_luy_ke' => 60]],
        'entries' => ['2_10' => ['so_luong' => '42', 'kieu_nhap' => 'luy_ke']],
    ]));
    assertSame([10 => 42], $r);
});

test('luy_ke_thuc_te_zero_when_no_luy_ke_stage', function () {
    $r = luyKeService()->calculateLuyKeThucTe(luyKeBaoCao([
        'routing' => [['cong_doan_id' => 1, 'la_cong_doan_tinh_luy_ke' => 0]],
        'entries' => ['1_10' => luyKeEntry(50)],
    ]));
    assertSame([10 => 0, 11 => 0, 12 => 0], $r);
});

test('luy_ke_thuc_te_empty_routing_and_empty_moc', function () {
    assertSame([10 => 0, 11 => 0, 12 => 0], luyKeService()->calculateLuyKeThucTe(luyKeBaoCao(['routing' => []])));
    assertSame([], luyKeService()->calculateLuyKeThucTe(luyKeBaoCao(['moc_gio_list' => []])));
});

test('luy_ke_thuc_te_missing_entry_counts_zero', function () {
    $r = luyKeService()->calculateLuyKeThucTe(luyKeBaoCao(['entries' => ['2_11' => luyKeEntry(40)]]));
    assertSame([10 => 0, 11 => 40, 12 => 0], $r);
});

test('luy_ke_thuc_te_non_luy_ke_entries_accumulate', function () {
    $r = luyKeService()->calculateLuyKeThucTe(luyKeBaoCao([
        'entries' => [
            '2_10' => luyKeEntry(10, 'tang_them'),
            '2_11' => luyKeEntry(15, 'tang_them'),
            '2_12' => luyKeEntry(5, 'tang_them'),
        ],
    ]));
    assertSame([10 => 10, 11 => 25, 12 => 30], $r);
});

test('luy_ke_ket_qua_golden_shape', function () {
    if (!class_exists('App\Services\LuyKeCalculator')) {
        assertSame(true, false, 'LuyKeCalculator chưa có');
        return;
    }
    $baoCao = luyKeBaoCao(['entries' => [
        '1_12' => luyKeEntry(80),
        '2_12' => luyKeEntry(90),
    ]]);
    $r = \App\Services\LuyKeCalculator::buildKetQua($baoCao, '2026-01-05T10:00:00+07:00');
    assertSame([
        'version' => 1,
        'generated_at' => '2026-01-05T10:00:00+07:00',
        'source' => [
            'bao_cao_id' => 0,
            'ngay' => '2026-01-05',
            'line_id' => 3,
            'ca_id' => 1,
            'ma_hang_id' => 7,
        ],
        'inputs' => ['so_lao_dong' => 20, 'ctns' => 100, 'tong_phut_hieu_dung' => 240],
        'tong_hop' => [
            'ct_gio' => 12.5,
            'luy_ke_thuc_te' => 90,
            'chi_tieu_luy_ke' => 100.0,
            'trang_thai' => 'chua_dat',
        ],
        'cong_doan' => [
            [
                'cong_doan_id' => 1,
                'cong_doan_ten' => 'Cắt',
                'la_cong_doan_tinh_luy_ke' => 0,
                'luy_ke_thuc_te' => 80,
                'chi_tieu_luy_ke' => 100.0,
                'trang_thai' => 'chua_dat',
            ],
            [
                'cong_doan_id' => 2,
                'cong_doan_ten' => 'Đóng gói',
                'la_cong_doan_tinh_luy_ke' => 1,
                'luy_ke_thuc_te' => 90,
                'chi_tieu_luy_ke' => 100.0,
                'trang_thai' => 'chua_dat',
            ],
        ],
    ], $r);
});

test('luy_ke_ket_qua_golden_dat_and_na', function () {
    if (!class_exists('App\Services\LuyKeCalculator')) {
        assertSame(true, false, 'LuyKeCalculator chưa có');
        return;
    }
    $dat = \App\Services\LuyKeCalculator::buildKetQua(
        luyKeBaoCao(['ctns' => 90, 'entries' => ['2_12' => luyKeEntry(90)]]),
        'T',
    );
    assertSame('dat', $dat['tong_hop']['trang_thai']);
    assertSame(90, $dat['tong_hop']['luy_ke_thuc_te']);
    assertSame('dat', $dat['cong_doan'][1]['trang_thai']);
    assertSame('chua_dat', $dat['cong_doan'][0]['trang_thai']);

    $na = \App\Services\LuyKeCalculator::buildKetQua(luyKeBaoCao(['ctns' => 0, 'moc_gio_list' => []]), 'T');
    assertSame(0, $na['tong_hop']['chi_tieu_luy_ke']);
    assertSame('na', $na['tong_hop']['trang_thai']);
    assertSame('na', $na['cong_doan'][0]['trang_thai']);
    assertSame(0, $na['cong_doan'][1]['luy_ke_thuc_te']);
});
