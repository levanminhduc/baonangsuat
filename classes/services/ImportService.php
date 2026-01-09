<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getNangSuat();
    }
    
    public function preview($filePath) {
        $sheets = $this->parseExcel($filePath);
        
        $data = [];
        $errors = [];
        $stats = [
            'total_sheets' => count($sheets),
            'total_ma_hang_new' => 0,
            'total_ma_hang_existing' => 0,
            'total_cong_doan_new' => 0,
            'total_cong_doan_existing' => 0,
            'total_routing_new' => 0
        ];
        
        $newCongDoanCounter = $this->getNextMaCongDoanNumber();
        $newCongDoanMap = [];
        
        foreach ($sheets as $sheetData) {
            $sheetName = $sheetData['sheet_name'];
            $maHang = $sheetData['ma_hang'];
            $congDoanNames = $sheetData['cong_doan_list'];
            
            if (empty($maHang)) {
                $errors[] = [
                    'sheet_name' => $sheetName,
                    'cell' => 'C2',
                    'error_code' => 'INVALID_MA_HANG_FORMAT',
                    'message' => "Không tìm thấy mã hàng trong ô C2. Định dạng cần: 'MH: XXXX'"
                ];
                continue;
            }
            
            if (empty($congDoanNames)) {
                $errors[] = [
                    'sheet_name' => $sheetName,
                    'cell' => 'C5:C100',
                    'error_code' => 'EMPTY_CONG_DOAN_LIST',
                    'message' => 'Không tìm thấy công đoạn nào từ C5 trở đi'
                ];
                continue;
            }
            
            $existingMaHang = $this->findMaHangByCode($maHang);
            $isNewMaHang = ($existingMaHang === null);
            
            $reportStats = null;
            $hasWarning = false;
            $warningMessage = '';
            
            if ($isNewMaHang) {
                $stats['total_ma_hang_new']++;
            } else {
                $stats['total_ma_hang_existing']++;
                $reportStats = $this->checkExistingReports(intval($existingMaHang['id']));
                if ($reportStats['locked_reports'] > 0) {
                    $hasWarning = true;
                    $warningMessage = "Mã hàng {$maHang} có {$reportStats['locked_reports']} báo cáo đã chốt. Import routing mới có thể ảnh hưởng hiển thị báo cáo cũ nếu không có routing snapshot.";
                }
            }
            
            $congDoanList = [];
            $thuTu = 1;
            
            foreach ($congDoanNames as $tenCongDoan) {
                $normalizedName = $this->normalizeText($tenCongDoan);
                $upperName = mb_strtoupper($normalizedName, 'UTF-8');
                
                $existingCongDoan = $this->findCongDoanByName($tenCongDoan);
                $isNewCongDoan = ($existingCongDoan === null);
                
                if ($isNewCongDoan) {
                    if (isset($newCongDoanMap[$upperName])) {
                        $maCongDoan = $newCongDoanMap[$upperName];
                    } else {
                        $maCongDoan = $this->formatMaCongDoan($newCongDoanCounter);
                        $newCongDoanMap[$upperName] = $maCongDoan;
                        $newCongDoanCounter++;
                        $stats['total_cong_doan_new']++;
                    }
                    
                    $congDoanList[] = [
                        'thu_tu' => $thuTu,
                        'ten_cong_doan' => $normalizedName,
                        'ma_cong_doan' => $maCongDoan,
                        'is_new' => true,
                        'existing_id' => null
                    ];
                } else {
                    $stats['total_cong_doan_existing']++;
                    $congDoanList[] = [
                        'thu_tu' => $thuTu,
                        'ten_cong_doan' => $normalizedName,
                        'ma_cong_doan' => $existingCongDoan['ma_cong_doan'],
                        'is_new' => false,
                        'existing_id' => intval($existingCongDoan['id'])
                    ];
                }
                
                $stats['total_routing_new']++;
                $thuTu++;
            }
            
            $data[] = [
                'sheet_name' => $sheetName,
                'ma_hang' => $maHang,
                'ten_hang' => 'Mã hàng ' . $maHang,
                'is_new' => $isNewMaHang,
                'existing_id' => $isNewMaHang ? null : intval($existingMaHang['id']),
                'report_stats' => $reportStats,
                'has_warning' => $hasWarning,
                'warning_message' => $warningMessage,
                'cong_doan_list' => $congDoanList
            ];
        }
        
        $message = empty($errors) ? 'Phân tích file thành công' : 'Phân tích file hoàn tất với một số lỗi';
        
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'stats' => $stats,
            'errors' => $errors
        ];
    }
    
    public function confirm($maHangList) {
        if (empty($maHangList)) {
            return ['success' => false, 'message' => 'Danh sách mã hàng không được rỗng', 'error_code' => 'VALIDATION_FAILED'];
        }
        
        $stats = [
            'ma_hang_created' => 0,
            'ma_hang_updated' => 0,
            'cong_doan_created' => 0,
            'routing_created' => 0,
            'routing_deleted' => 0
        ];
        
        mysqli_begin_transaction($this->db);
        
        try {
            $createdCongDoanMap = [];
            
            foreach ($maHangList as $maHangData) {
                $maHang = strtoupper(trim($maHangData['ma_hang'] ?? ''));
                $tenHang = trim($maHangData['ten_hang'] ?? '');
                $congDoanList = $maHangData['cong_doan_list'] ?? [];
                
                if (empty($maHang)) {
                    throw new Exception('Mã hàng không được rỗng');
                }
                
                $existingMaHang = $this->findMaHangByCode($maHang);
                $isExistingMaHang = ($existingMaHang !== null);
                
                if (!$isExistingMaHang) {
                    $stmt = mysqli_prepare($this->db, "INSERT INTO ma_hang (ma_hang, ten_hang, is_active) VALUES (?, ?, 1)");
                    mysqli_stmt_bind_param($stmt, "ss", $maHang, $tenHang);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Lỗi tạo mã hàng: ' . mysqli_error($this->db));
                    }
                    $maHangId = mysqli_insert_id($this->db);
                    mysqli_stmt_close($stmt);
                    $stats['ma_hang_created']++;
                } else {
                    $maHangId = intval($existingMaHang['id']);
                    $stats['ma_hang_updated']++;
                }
                
                $hieuLucTu = date('Y-m-d');
                
                $routingDataList = [];
                
                foreach ($congDoanList as $congDoanData) {
                    $thuTu = intval($congDoanData['thu_tu'] ?? 1);
                    $tenCongDoan = trim($congDoanData['ten_cong_doan'] ?? '');
                    $maCongDoan = strtoupper(trim($congDoanData['ma_cong_doan'] ?? ''));
                    $existingId = $congDoanData['existing_id'] ?? null;
                    
                    if (empty($tenCongDoan)) {
                        throw new Exception('Tên công đoạn không được rỗng');
                    }
                    
                    $congDoanId = null;
                    
                    if ($existingId !== null && $existingId > 0) {
                        $congDoanId = intval($existingId);
                    } else {
                        $upperName = mb_strtoupper($this->normalizeText($tenCongDoan), 'UTF-8');
                        
                        if (isset($createdCongDoanMap[$upperName])) {
                            $congDoanId = $createdCongDoanMap[$upperName];
                        } else {
                            $checkExisting = $this->findCongDoanByName($tenCongDoan);
                            if ($checkExisting !== null) {
                                $congDoanId = intval($checkExisting['id']);
                            } else {
                                if (empty($maCongDoan)) {
                                    $maCongDoan = $this->generateMaCongDoan();
                                }
                                
                                $stmt = mysqli_prepare($this->db, "INSERT INTO cong_doan (ma_cong_doan, ten_cong_doan, is_active, la_cong_doan_thanh_pham) VALUES (?, ?, 1, 0)");
                                mysqli_stmt_bind_param($stmt, "ss", $maCongDoan, $tenCongDoan);
                                if (!mysqli_stmt_execute($stmt)) {
                                    throw new Exception('Lỗi tạo công đoạn: ' . mysqli_error($this->db));
                                }
                                $congDoanId = mysqli_insert_id($this->db);
                                mysqli_stmt_close($stmt);
                                
                                $createdCongDoanMap[$upperName] = $congDoanId;
                                $stats['cong_doan_created']++;
                            }
                        }
                    }
                    
                    $routingDataList[] = [
                        'cong_doan_id' => $congDoanId,
                        'thu_tu' => $thuTu
                    ];
                }
                
                if ($isExistingMaHang && !empty($routingDataList)) {
                    $newCongDoanIds = array_column($routingDataList, 'cong_doan_id');
                    $idsString = implode(',', array_map('intval', $newCongDoanIds));
                    $deleteQuery = "DELETE FROM ma_hang_cong_doan
                                    WHERE ma_hang_id = " . intval($maHangId) . "
                                    AND line_id IS NULL
                                    AND cong_doan_id NOT IN ($idsString)";
                    mysqli_query($this->db, $deleteQuery);
                    $stats['routing_deleted'] += mysqli_affected_rows($this->db);
                }
                
                foreach ($routingDataList as $routingData) {
                    $congDoanId = $routingData['cong_doan_id'];
                    $thuTu = $routingData['thu_tu'];
                    
                    $checkRoutingStmt = mysqli_prepare($this->db, "SELECT id, thu_tu FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND cong_doan_id = ? AND line_id IS NULL");
                    mysqli_stmt_bind_param($checkRoutingStmt, "ii", $maHangId, $congDoanId);
                    mysqli_stmt_execute($checkRoutingStmt);
                    $checkResult = mysqli_stmt_get_result($checkRoutingStmt);
                    $existingRouting = mysqli_fetch_assoc($checkResult);
                    mysqli_stmt_close($checkRoutingStmt);
                    
                    if ($existingRouting === null) {
                        $stmt = mysqli_prepare($this->db, "INSERT INTO ma_hang_cong_doan (ma_hang_id, cong_doan_id, thu_tu, bat_buoc, la_cong_doan_tinh_luy_ke, line_id, hieu_luc_tu) VALUES (?, ?, ?, 1, 0, NULL, ?)");
                        mysqli_stmt_bind_param($stmt, "iiis", $maHangId, $congDoanId, $thuTu, $hieuLucTu);
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception('Lỗi tạo routing: ' . mysqli_error($this->db));
                        }
                        mysqli_stmt_close($stmt);
                        $stats['routing_created']++;
                    } else {
                        if (intval($existingRouting['thu_tu']) !== $thuTu) {
                            $updateStmt = mysqli_prepare($this->db, "UPDATE ma_hang_cong_doan SET thu_tu = ? WHERE id = ?");
                            $routingId = intval($existingRouting['id']);
                            mysqli_stmt_bind_param($updateStmt, "ii", $thuTu, $routingId);
                            mysqli_stmt_execute($updateStmt);
                            mysqli_stmt_close($updateStmt);
                        }
                    }
                }
            }
            
            mysqli_commit($this->db);
            
            return [
                'success' => true,
                'message' => 'Import thành công',
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            mysqli_rollback($this->db);
            return [
                'success' => false,
                'message' => 'Lỗi khi import: ' . $e->getMessage(),
                'error_code' => 'IMPORT_FAILED'
            ];
        }
    }
    
    public function generateMaCongDoan() {
        $nextNumber = $this->getNextMaCongDoanNumber();
        return $this->formatMaCongDoan($nextNumber);
    }
    
    public function findCongDoanByName($name) {
        $normalized = $this->normalizeText($name);
        $upperName = mb_strtoupper($normalized, 'UTF-8');
        
        $stmt = mysqli_prepare($this->db, "SELECT id, ma_cong_doan, ten_cong_doan FROM cong_doan WHERE UPPER(TRIM(ten_cong_doan)) = ?");
        mysqli_stmt_bind_param($stmt, "s", $upperName);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $congDoan = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $congDoan;
    }
    
    private function parseExcel($filePath) {
        $spreadsheet = IOFactory::load($filePath);
        $sheets = [];
        
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = $sheet->getTitle();
            $maHang = $this->extractMaHang($sheet);
            $congDoanList = $this->extractCongDoanList($sheet);
            
            $sheets[] = [
                'sheet_name' => $sheetName,
                'ma_hang' => $maHang,
                'cong_doan_list' => $congDoanList
            ];
        }
        
        return $sheets;
    }
    
    private function extractMaHang($sheet) {
        $cellValue = $sheet->getCell('C2')->getValue();
        
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
    
    private function extractCongDoanList($sheet) {
        $congDoanList = [];
        $row = 5;
        $maxEmptyRows = 5;
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
    
    private function normalizeText($text) {
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
    }
    
    private function getNextMaCongDoanNumber() {
        $result = mysqli_query($this->db, "SELECT MAX(CAST(SUBSTRING(ma_cong_doan, 3) AS UNSIGNED)) as max_num FROM cong_doan WHERE ma_cong_doan REGEXP '^CD[0-9]+$'");
        $row = mysqli_fetch_assoc($result);
        $maxNum = intval($row['max_num'] ?? 0);
        return $maxNum + 1;
    }
    
    private function formatMaCongDoan($number) {
        return 'CD' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
    
    private function findMaHangByCode($maHang) {
        $maHang = strtoupper(trim($maHang));
        
        $stmt = mysqli_prepare($this->db, "SELECT id, ma_hang, ten_hang FROM ma_hang WHERE UPPER(TRIM(ma_hang)) = ?");
        mysqli_stmt_bind_param($stmt, "s", $maHang);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $maHangData = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $maHangData;
    }
    
    public function checkExistingReports($maHangId) {
        $stmt = mysqli_prepare($this->db,
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN trang_thai IN ('submitted','approved','locked') THEN 1 ELSE 0 END) as locked,
                SUM(CASE WHEN trang_thai = 'draft' THEN 1 ELSE 0 END) as draft
             FROM bao_cao_nang_suat
             WHERE ma_hang_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $maHangId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return [
            'has_reports' => intval($row['total']) > 0,
            'total_reports' => intval($row['total']),
            'locked_reports' => intval($row['locked']),
            'draft_reports' => intval($row['draft'])
        ];
    }
}
