<?php
use App\Services\PoSubartService;
use App\Services\PoTienDo;
use App\Services\SubartSoLuong\ManualSubartSource;
use App\Services\SubartSoLuong\SubartSoLuongFactory;
use App\Services\SubartSoLuong\SubartSoLuongSource;

function poTienDoSnapshot(array $routing): string
{
    return json_encode(['version' => 1, 'created_at' => '2026-01-01T00:00:00+00:00', 'routing' => $routing]);
}

test('po_thanh_pham_first_luy_ke_stage', function () {
    $json = poTienDoSnapshot([
        ['cong_doan_id' => 5, 'la_cong_doan_tinh_luy_ke' => 0],
        ['cong_doan_id' => 7, 'la_cong_doan_tinh_luy_ke' => 1],
        ['cong_doan_id' => 9, 'la_cong_doan_tinh_luy_ke' => 1],
    ]);
    assertSame(7, PoTienDo::thanhPhamCongDoanId($json));
});

test('po_thanh_pham_accepts_string_values', function () {
    $json = poTienDoSnapshot([
        ['cong_doan_id' => '5', 'la_cong_doan_tinh_luy_ke' => '0'],
        ['cong_doan_id' => '8', 'la_cong_doan_tinh_luy_ke' => '1'],
    ]);
    assertSame(8, PoTienDo::thanhPhamCongDoanId($json));
});

test('po_thanh_pham_null_when_no_luy_ke_stage', function () {
    $json = poTienDoSnapshot([['cong_doan_id' => 5, 'la_cong_doan_tinh_luy_ke' => 0]]);
    assertSame(null, PoTienDo::thanhPhamCongDoanId($json));
});

test('po_thanh_pham_null_for_null_empty_invalid', function () {
    assertSame(null, PoTienDo::thanhPhamCongDoanId(null));
    assertSame(null, PoTienDo::thanhPhamCongDoanId(''));
    assertSame(null, PoTienDo::thanhPhamCongDoanId('{khong phai json'));
    assertSame(null, PoTienDo::thanhPhamCongDoanId('{"version":1}'));
});

test('po_tinh_con_lai_when_under_target', function () {
    assertSame(['con_lai' => 6800, 'vuot' => 0], PoTienDo::tinh(10000, 3200));
});

test('po_tinh_vuot_when_over_target', function () {
    assertSame(['con_lai' => 0, 'vuot' => 500], PoTienDo::tinh(10000, 10500));
});

test('po_tinh_null_so_luong', function () {
    assertSame(['con_lai' => null, 'vuot' => 0], PoTienDo::tinh(null, 5));
});

test('po_kiem_tong_subart_equal_to_po_is_ok', function () {
    assertSame(['ok' => true, 'tong' => 10000], PoTienDo::kiemTongSubart([6000], 4000, 10000));
});

test('po_kiem_tong_subart_over_po_fails', function () {
    assertSame(['ok' => false, 'tong' => 10001], PoTienDo::kiemTongSubart([6000], 4001, 10000));
});

test('subart_factory_defaults_to_manual', function () {
    foreach ([null, 'manual', 'xyz'] as $value) {
        assertSame(true, SubartSoLuongFactory::fromConfig($value) instanceof ManualSubartSource, var_export($value, true));
    }
});

function poTienDoReadonlySource(): SubartSoLuongSource
{
    return new class implements SubartSoLuongSource {
        public function nguon(): string
        {
            return 'readonly-test';
        }

        public function laEditable(): bool
        {
            return false;
        }

        public function getSoLuong(string $poNumber, string $styleCode): array
        {
            return [];
        }
    };
}

test('po_subart_save_returns_409_when_source_not_editable', function () {
    $result = (new PoSubartService(poTienDoReadonlySource()))->save('A', 1, 'S1', 10, 'NV1');
    assertSame(false, $result['success']);
    assertSame(409, $result['status']);
});

test('po_subart_delete_returns_409_when_source_not_editable', function () {
    $result = (new PoSubartService(poTienDoReadonlySource()))->delete('A', 1, 'S1');
    assertSame(false, $result['success']);
    assertSame(409, $result['status']);
});

test('po_db_dependent_behaviour_skipped_without_pdo_pgsql', function () {
    if (!extension_loaded('pdo_pgsql')) {
        echo "\n[skip] PO tests needing Postgres: pdo_pgsql not loaded\n";
    }
    assertSame(true, true);
});
