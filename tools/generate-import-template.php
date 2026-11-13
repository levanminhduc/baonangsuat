<?php

// Sinh file mẫu import công đoạn: php tools/generate-import-template.php
// Theo đúng bảng in tại chuyền (A1:H22): MH ở C1 ("MH: XXXX", 4 chữ số), công đoạn từ C4 xuống
// tới dòng trống đầu tiên. ImportService đọc MỌI sheet nên hướng dẫn để ở cột J, không tách sheet.

require_once __DIR__ . '/../bootstrap/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$congDoanList = [
    'NHẬN HÀNG', 'QUAY LƯNG', 'QUAY TÚI DAO', 'QUAY TÚI HỘP', 'MÍ TÚI TREO', 'LƯỢT TÚI GỐI',
    'HC ĐÁY TRƯỚC', 'DIỄU GỐI THÂN TRƯỚC', 'QUAY DK THÂN SAU', 'LƯỢT DK THÂN SAU', 'DIỄU GỐI THÂN SAU',
    'VS SƯỜN NGOÀI', 'DÁN TÚI HỘP', 'BỌ SƯỜN', 'TRA LƯNG', 'MÍ DIỄU SƯỜN TRONG', 'DÁN PAS LƯNG',
    'MAY LAI', 'KCS THÀNH PHẨM',
];

$notes = [
    'HƯỚNG DẪN IMPORT',
    '1. Mỗi sheet = 1 mã hàng. Tên sheet tuỳ ý.',
    '2. Ô C1: mã hàng dạng "MH: XXXX" (4 chữ số).',
    '3. Từ ô C4 trở xuống: tên công đoạn theo đúng thứ tự, không để dòng trống xen giữa.',
    '4. Hệ thống chỉ đọc bảng đầu tiên (tới dòng trống đầu tiên); bảng copy để in phía dưới được bỏ qua.',
    '5. Mọi sheet đều được đọc: xoá sheet không dùng.',
    '6. Công đoạn trùng tên (không phân biệt hoa thường) sẽ dùng lại mã cũ.',
    '7. Thay dữ liệu mẫu bằng dữ liệu thật trước khi import.',
];

$spreadsheet = new Spreadsheet();
$spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(12);

$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('1234');

$lastRow = 3 + count($congDoanList);

// Đầu bảng (dòng 1-3)
$sheet->setCellValue('A1', 'LINE: ….-LĐ:…..');
$sheet->setCellValue('C1', 'MH: 1234');
$sheet->setCellValue('D1', 'CTNS:');
$sheet->setCellValue('E1', 280);
$sheet->setCellValue('F1', 'CT/GIỜ:');
$sheet->setCellValue('G1', '=E1/8.5');
$sheet->setCellValue('H1', 'LŨY KẾ');
$sheet->setCellValue('A2', 'Date:');
$sheet->setCellValue('B2', 'STT');
$sheet->setCellValue('C2', 'TÊN CÔNG ĐOẠN');
$sheet->setCellValue('A3', 'Ghi chú');
foreach (['D' => ['9h', 1.5], 'E' => ['11h', 3.5], 'F' => ['14h30', 6], 'G' => ['17h00', 8.5]] as $col => [$label, $hours]) {
    $sheet->setCellValue($col . '2', $label);
    $sheet->setCellValue($col . '3', '=G1*' . $hours);
}
$sheet->mergeCells('H1:H3');
$sheet->mergeCells('B2:B3');
$sheet->mergeCells('C2:C3');

foreach ($congDoanList as $i => $ten) {
    $row = 4 + $i;
    $sheet->setCellValue('B' . $row, $i + 1);
    $sheet->setCellValue('C' . $row, $ten);
}

$sheet->getStyle('A1:H3')->getFont()->setBold(true);
$sheet->getStyle('A1:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A1:H' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle('E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('G1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('D2:G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('D3:G3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('D3:G3')->getNumberFormat()->setFormatCode('0');
$sheet->getStyle('G1')->getNumberFormat()->setFormatCode('0');
$sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

foreach (['A' => 15.11, 'B' => 4.89, 'C' => 22.33, 'D' => 11.33, 'E' => 11.33, 'F' => 11.33, 'G' => 11.33, 'H' => 16.66, 'I' => 4, 'J' => 90] as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}
for ($row = 1; $row <= $lastRow; $row++) {
    $sheet->getRowDimension($row)->setRowHeight(19.35);
}

// Hướng dẫn ngoài vùng in
foreach ($notes as $i => $note) {
    $sheet->setCellValue('J' . (1 + $i), $note);
}
$sheet->getStyle('J1')->getFont()->setBold(true)->getColor()->setRGB('C00000');
$sheet->getStyle('J2:J' . count($notes))->getFont()->setItalic(true)->getColor()->setRGB('808080');

$sheet->getPageSetup()->setPrintArea('A1:H' . $lastRow);

$outDir = __DIR__ . '/../public/assets/templates';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}
$outPath = $outDir . '/mau-import-cong-doan.xlsx';
(new Xlsx($spreadsheet))->save($outPath);

echo "Đã tạo: $outPath\n";
