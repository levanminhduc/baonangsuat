<?php
use App\Services\ImportExcelParser;

function importParseFixture(string $name): array
{
    return (new ImportExcelParser())->parseExcel(__DIR__ . '/../fixtures/' . $name);
}

test('import_parser_old_template_reads_ma_hang_and_cong_doan_per_sheet', function () {
    $sheets = importParseFixture('import-test.xlsx');
    assertSame(3, count($sheets));
    assertSame('MH9001', $sheets[0]['sheet_name']);
    assertSame('9001', $sheets[0]['ma_hang']);
    assertSame(['Cắt vải', 'May thân trước', 'May thân sau', 'Ráp sườn', 'May cổ'], $sheets[0]['cong_doan_list']);
    assertSame('9002', $sheets[1]['ma_hang']);
    assertSame(['Cắt vải', 'May túi', 'Đóng gói'], $sheets[1]['cong_doan_list']);
});

test('import_parser_old_template_has_no_dinh_muc', function () {
    foreach (importParseFixture('import-test.xlsx') as $sheet) {
        assertSame([], $sheet['dinh_muc_list']);
    }
});

test('import_parser_sheet_without_ma_hang_yields_null', function () {
    $sheet = importParseFixture('import-test.xlsx')[2];
    assertSame('SheetLoi', $sheet['sheet_name']);
    assertSame(null, $sheet['ma_hang']);
    assertSame(['Công đoạn 1', 'Công đoạn 2'], $sheet['cong_doan_list']);
});

test('import_parser_print_template_reads_cong_doan_and_dinh_muc', function () {
    $sheets = importParseFixture('6940.xlsx');
    assertSame(1, count($sheets));
    assertSame('6940', $sheets[0]['ma_hang']);
    assertSame(19, count($sheets[0]['cong_doan_list']));
    assertSame('NHẬN HÀNG', $sheets[0]['cong_doan_list'][0]);
    assertSame('KCS THÀNH PHẨM', $sheets[0]['cong_doan_list'][18]);
    assertSame([
        ['line_label' => '1', 'so_lao_dong' => 37, 'ctns' => 280],
        ['line_label' => '6', 'so_lao_dong' => 35, 'ctns' => 250],
        ['line_label' => '1+6', 'so_lao_dong' => 71, 'ctns' => 550],
    ], $sheets[0]['dinh_muc_list']);
});
