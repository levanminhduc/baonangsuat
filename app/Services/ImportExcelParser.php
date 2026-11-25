<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportExcelParser
{
    public function parseExcel($filePath)
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheets = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = $sheet->getTitle();
            // Mẫu in hiện tại: MH ở C1, công đoạn từ C4, các bảng copy để in cách nhau 1 dòng trống
            // nên dừng ở dòng trống đầu tiên. Mẫu cũ: MH ở C2, công đoạn từ C5, cho phép tối đa 4 dòng trống.
            $maHang = $this->extractMaHang($sheet, 'C1');
            $headerRow = 1;
            if ($maHang !== null) {
                $congDoanList = $this->extractCongDoanList($sheet, 4, 1);
            } else {
                $maHang = $this->extractMaHang($sheet, 'C2');
                $headerRow = 2;
                $congDoanList = $this->extractCongDoanList($sheet, 5, 5);
            }

            $sheets[] = [
                'sheet_name' => $sheetName,
                'ma_hang' => $maHang,
                'cong_doan_list' => $congDoanList,
                'dinh_muc_list' => $maHang !== null ? $this->extractDinhMucList($sheet, $headerRow, $maHang) : [],
            ];
        }

        return $sheets;
    }

    private function extractMaHang($sheet, $cell)
    {
        $cellValue = $sheet->getCell($cell)->getValue();

        if (empty($cellValue)) {
            return null;
        }

        $value = trim((string)$cellValue);

        if (preg_match('/^MH:\s*(\d{4})$/i', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/MH:\s*(\d{4})/i', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^\d{4}$/', $value)) {
            return $value;
        }

        return null;
    }

    // Dòng đầu bảng in có nhiều bảng đặt cạnh nhau, mỗi bảng 1 LINE:
    // "LINE: 1.-LĐ:.37…" | trống | "MH: 6940" | "CTNS:" | 280. Lấy LĐ + CTNS từng bảng cùng mã hàng.
    private function extractDinhMucList($sheet, $row, $maHang)
    {
        $list = [];
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn($row));

        for ($col = 1; $col <= $maxCol; $col++) {
            $text = trim((string)$sheet->getCell([$col, $row])->getValue());
            if (!preg_match('/LINE\s*:\s*([^-]*?)[\s.…]*-/iu', $text, $lineMatch)) {
                continue;
            }

            $lineLabel = trim($lineMatch[1]);
            $mhCell = Coordinate::stringFromColumnIndex($col + 2) . $row;
            if ($lineLabel === '' || isset($list[$lineLabel]) || $this->extractMaHang($sheet, $mhCell) !== $maHang) {
                continue;
            }

            $soLaoDong = preg_match('/LĐ\s*:\D*(\d+)/u', $text, $ldMatch) ? intval($ldMatch[1]) : 0;
            $ctnsValue = $sheet->getCell([$col + 4, $row])->getCalculatedValue();
            $ctns = is_numeric($ctnsValue) ? (int)round($ctnsValue) : 0;

            if ($soLaoDong > 0 || $ctns > 0) {
                $list[$lineLabel] = [
                    'line_label' => $lineLabel,
                    'so_lao_dong' => $soLaoDong,
                    'ctns' => $ctns,
                ];
            }
        }

        return array_values($list);
    }

    private function extractCongDoanList($sheet, $row, $maxEmptyRows)
    {
        $congDoanList = [];
        $emptyRowCount = 0;

        while ($emptyRowCount < $maxEmptyRows) {
            $cellValue = $sheet->getCell('C' . $row)->getValue();

            if (empty($cellValue) || trim((string)$cellValue) === '') {
                $emptyRowCount++;
                $row++;
                continue;
            }

            $emptyRowCount = 0;
            $tenCongDoan = trim((string)$cellValue);

            if (!empty($tenCongDoan)) {
                $congDoanList[] = $tenCongDoan;
            }

            $row++;

            if ($row > 200) {
                break;
            }
        }

        return $congDoanList;
    }

    public function normalizeText($text)
    {
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
    }

    public function formatMaCongDoan($number)
    {
        return 'CD' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}
