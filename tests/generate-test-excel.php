<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('MH9001');
$sheet1->setCellValue('C2', 'MH: 9001');
$sheet1->setCellValue('C5', 'Cắt vải');
$sheet1->setCellValue('C6', 'May thân trước');
$sheet1->setCellValue('C7', 'May thân sau');
$sheet1->setCellValue('C8', 'Ráp sườn');
$sheet1->setCellValue('C9', 'May cổ');

$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('MH9002');
$sheet2->setCellValue('C2', 'MH: 9002');
$sheet2->setCellValue('C5', 'Cắt vải');
$sheet2->setCellValue('C6', 'May túi');
$sheet2->setCellValue('C7', 'Đóng gói');

$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('SheetLoi');
$sheet3->setCellValue('C2', 'Không có mã hàng');
$sheet3->setCellValue('C5', 'Công đoạn 1');
$sheet3->setCellValue('C6', 'Công đoạn 2');

$writer = new Xlsx($spreadsheet);
$outputPath = __DIR__ . '/import-test.xlsx';
$writer->save($outputPath);

echo "File created: $outputPath\n";
echo "Sheets: " . $spreadsheet->getSheetCount() . "\n";
