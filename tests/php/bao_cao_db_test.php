<?php

use App\Services\BaoCaoService;
use App\Services\BaoCaoWorkflowService;
use App\Support\Container;

// Khoá hành vi hiện tại của báo cáo trên DB test (seed: 2 công đoạn × 4 mốc giờ, công đoạn 2 tính lũy kế).

function bnsTaoBaoCaoTest(BaoCaoService $service, array $override = []): array
{
    return $service->createBaoCao($override + [
        'ngay_bao_cao' => '2026-01-05',
        'line_id' => 1,
        'ca_id' => 1,
        'ma_hang_id' => 1,
        'so_lao_dong' => 30,
        'ctns' => 400,
    ], 'NV001');
}

test('db_create_pre_generates_routing_x_moc_gio_entries', function () {
    $db = bnsTestDb();
    $result = bnsTaoBaoCaoTest((new Container())->get(BaoCaoService::class));

    assertSame(true, $result['success'], $result['message'] ?? '');
    $id = intval($result['bao_cao_id']);
    $row = bnsTestRow($db, "SELECT COUNT(*) c, SUM(so_luong) s FROM nhap_lieu_nang_suat WHERE bao_cao_id = $id");
    assertSame('8', (string) $row['c']);
    assertSame('0', (string) $row['s']);

    $baoCao = bnsTestRow($db, "SELECT version, trang_thai, tong_phut_hieu_dung, ct_gio, routing_snapshot FROM bao_cao_nang_suat WHERE id = $id");
    assertSame('1', (string) $baoCao['version']);
    assertSame('draft', $baoCao['trang_thai']);
    assertSame('510', (string) $baoCao['tong_phut_hieu_dung']);
    assertSame(round(400 / (510 / 60), 2), (float) $baoCao['ct_gio']);
    $snapshot = json_decode($baoCao['routing_snapshot'], true);
    assertSame(1, $snapshot['version']);
    assertSame(2, count($snapshot['routing']));
});

test('db_create_rejects_duplicate_report', function () {
    bnsTestDb();
    $service = (new Container())->get(BaoCaoService::class);
    bnsTaoBaoCaoTest($service);
    $again = bnsTaoBaoCaoTest($service);

    assertSame(false, $again['success']);
    assertSame('Báo cáo đã tồn tại cho ngày/ca/mã hàng/PO/subart này', $again['message']);
    assertSame(true, bnsTaoBaoCaoTest($service, ['line_id' => 2])['success'], 'LINE khác thì được tạo');
});

test('db_update_entries_bumps_version_and_rejects_stale_version', function () {
    $db = bnsTestDb();
    $service = (new Container())->get(BaoCaoService::class);
    $id = intval(bnsTaoBaoCaoTest($service)['bao_cao_id']);
    $mocId = intval(bnsTestRow($db, 'SELECT id FROM moc_gio ORDER BY thu_tu LIMIT 1')['id']);
    $entries = [['cong_doan_id' => 2, 'moc_gio_id' => $mocId, 'so_luong' => 55]];

    $ok = $service->updateEntries($id, $entries, 1, 'NV001');
    assertSame(true, $ok['success']);
    assertSame(2, $ok['new_version']);

    $stale = $service->updateEntries($id, [['cong_doan_id' => 2, 'moc_gio_id' => $mocId, 'so_luong' => 99]], 1, 'NV002');
    assertSame(false, $stale['success']);
    assertSame('Dữ liệu đã được cập nhật bởi người khác. Vui lòng tải lại trang.', $stale['message']);

    $row = bnsTestRow($db, "SELECT so_luong FROM nhap_lieu_nang_suat WHERE bao_cao_id = $id AND cong_doan_id = 2 AND moc_gio_id = $mocId");
    assertSame('55', (string) $row['so_luong']);
    assertSame('2', (string) bnsTestRow($db, "SELECT version FROM bao_cao_nang_suat WHERE id = $id")['version']);
});

test('db_update_entries_clamps_negative_to_zero', function () {
    $db = bnsTestDb();
    $service = (new Container())->get(BaoCaoService::class);
    $id = intval(bnsTaoBaoCaoTest($service)['bao_cao_id']);
    $mocId = intval(bnsTestRow($db, 'SELECT id FROM moc_gio ORDER BY thu_tu LIMIT 1')['id']);

    $service->updateEntries($id, [['cong_doan_id' => 1, 'moc_gio_id' => $mocId, 'so_luong' => -5]], 1, 'NV001');
    $row = bnsTestRow($db, "SELECT so_luong FROM nhap_lieu_nang_suat WHERE bao_cao_id = $id AND cong_doan_id = 1 AND moc_gio_id = $mocId");
    assertSame('0', (string) $row['so_luong']);
});

test('db_update_header_bumps_version_and_rejects_stale_version', function () {
    $db = bnsTestDb();
    $service = (new Container())->get(BaoCaoService::class);
    $id = intval(bnsTaoBaoCaoTest($service)['bao_cao_id']);

    $ok = $service->updateHeader($id, ['so_lao_dong' => 32, 'ctns' => 510], 1);
    assertSame(true, $ok['success'], $ok['message'] ?? '');
    assertSame(2, $ok['new_version']);
    assertSame(60.0, (float) $ok['ct_gio']);

    $stale = $service->updateHeader($id, ['so_lao_dong' => 1, 'ctns' => 1], 1);
    assertSame(false, $stale['success']);
    assertSame('Dữ liệu đã được cập nhật. Vui lòng tải lại trang.', $stale['message']);

    $row = bnsTestRow($db, "SELECT so_lao_dong, ctns, version FROM bao_cao_nang_suat WHERE id = $id");
    assertSame(['32', '510', '2'], [(string) $row['so_lao_dong'], (string) $row['ctns'], (string) $row['version']]);
});

test('db_submit_stores_ket_qua_luy_ke_and_locks_entries', function () {
    $db = bnsTestDb();
    $workflow = (new Container())->get(BaoCaoWorkflowService::class);
    $service = (new Container())->get(BaoCaoService::class);
    $id = intval(bnsTaoBaoCaoTest($service)['bao_cao_id']);
    $mocIds = [];
    $result = mysqli_query($db, 'SELECT id FROM moc_gio ORDER BY thu_tu');
    while ($row = mysqli_fetch_assoc($result)) {
        $mocIds[] = intval($row['id']);
    }
    $service->updateEntries($id, [['cong_doan_id' => 2, 'moc_gio_id' => $mocIds[3], 'so_luong' => 380]], 1, 'NV001');

    $submit = $workflow->submitBaoCao($id, 'NV001');
    assertSame(true, $submit['success'], $submit['message'] ?? '');

    $row = bnsTestRow($db, "SELECT trang_thai, ket_qua_luy_ke FROM bao_cao_nang_suat WHERE id = $id");
    assertSame('submitted', $row['trang_thai']);
    $ketQua = json_decode($row['ket_qua_luy_ke'], true);
    // Cột JSON của MySQL sắp lại thứ tự khoá.
    $keys = array_keys($ketQua);
    sort($keys);
    assertSame(['cong_doan', 'generated_at', 'inputs', 'source', 'tong_hop', 'version'], $keys);
    assertSame($id, $ketQua['source']['bao_cao_id']);

    $after = $service->updateEntries($id, [['cong_doan_id' => 2, 'moc_gio_id' => $mocIds[3], 'so_luong' => 1]], 2, 'NV001');
    assertSame(false, $after['success']);
    assertSame('Báo cáo đã chốt, không thể sửa', $after['message']);

    $again = $workflow->submitBaoCao($id, 'NV001');
    assertSame('Trạng thái hiện tại không cho phép thao tác này', $again['message']);
});
