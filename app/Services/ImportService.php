<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportService {
    private $db;
    private $dbNhanSu;
    private $lastPreviewStats = [];
    private $lastPreviewErrors = [];
    private $lastPreviewData = [];
    
    public function __construct() {
        $this->db = Database::getNangSuat();
        $this->dbNhanSu = Database::getNhanSu();
    }
    
    public function preview($filePath) {
        $sheets = $this->parseExcel($filePath);
        
        $data = [];
        $errors = [];
        $hasActiveReports = false;
        $stats = [
            'total_sheets' => count($sheets),
            'total_ma_hang_new' => 0,
            'total_ma_hang_existing' => 0,
            'total_cong_doan_new' => 0,
            'total_cong_doan_existing' => 0,
            'total_routing_new' => 0,
            'routing_to_delete' => 0,
            'total_ma_hang_with_reports' => 0
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
            $activeReportsCheck = null;
            $hasActiveReportsForItem = false;
            $hasWarning = false;
            $warningMessage = '';
            $routingToDelete = 0;
            
            if ($isNewMaHang) {
                $stats['total_ma_hang_new']++;
            } else {
                $stats['total_ma_hang_existing']++;
                $maHangId = intval($existingMaHang['id']);
                
                // Check for active reports (informational only, not blocking)
                $activeReportsCheck = $this->checkBlockingReports($maHangId);
                $hasActiveReportsForItem = $activeReportsCheck['is_blocked']; // Reusing method, treating as info
                
                if ($hasActiveReportsForItem) {
                    $hasActiveReports = true;
                    $stats['total_ma_hang_with_reports']++;
                    
                    // Set warning message - informational, not blocking
                    $totalReports = $activeReportsCheck['summary']['locked_count'] + $activeReportsCheck['summary']['draft_with_data_count'];
                    $hasWarning = true;
                    $warningMessage = "Có {$totalReports} báo cáo đang sử dụng mã hàng này. Các báo cáo này sẽ giữ nguyên routing cũ (dùng snapshot, không bị ảnh hưởng).";
                }
                
                $reportStats = $this->checkExistingReports($maHangId);
                $routingToDelete = $this->countRoutingToDelete($maHangId, []);
                if ($routingToDelete > 0 && !$hasWarning) {
                    $hasWarning = true;
                    $warningMessage = "Import sẽ xóa {$routingToDelete} công đoạn routing hiện tại của mã hàng {$maHang}";
                } else if ($routingToDelete > 0 && $hasWarning) {
                    $warningMessage .= " Import cũng sẽ xóa {$routingToDelete} công đoạn routing hiện tại.";
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
                'has_active_reports' => $hasActiveReportsForItem,
                'active_reports_check' => $activeReportsCheck,
                'report_stats' => $reportStats,
                'has_warning' => $hasWarning,
                'warning_message' => $warningMessage,
                'cong_doan_list' => $congDoanList
            ];
            
            $stats['routing_to_delete'] += $routingToDelete;
        }
        
        $message = empty($errors) ? 'Phân tích file thành công' : 'Phân tích file hoàn tất với một số lỗi';
        
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'stats' => $stats,
            'errors' => $errors,
            'has_active_reports' => $hasActiveReports
        ];
    }
    
    /**
     * Set preview data (called before confirm)
     */
    public function setPreviewData(array $previewResult) {
        $this->lastPreviewStats = $previewResult['stats'] ?? [];
        $this->lastPreviewErrors = $previewResult['errors'] ?? [];
        $this->lastPreviewData = $previewResult['data'] ?? [];
    }
    
    public function confirm($maHangList, $acknowledgeDeletion = false, $fileName = '', $fileSize = 0, $importedBy = '') {
        $startTime = microtime(true);
        if (empty($maHangList)) {
            return ['success' => false, 'message' => 'Danh sách mã hàng không được rỗng', 'error_code' => 'VALIDATION_FAILED'];
        }
        
        // Check for existing ma_hang that need deletion acknowledgement
        $hasExistingMaHang = false;
        
        foreach ($maHangList as $maHangData) {
            $maHang = strtoupper(trim($maHangData['ma_hang'] ?? ''));
            if (!empty($maHang)) {
                $existing = $this->findMaHangByCode($maHang);
                if ($existing !== null) {
                    $hasExistingMaHang = true;
                    break;
                }
            }
        }
        
        if ($hasExistingMaHang && !$acknowledgeDeletion) {
            return ['success' => false, 'message' => 'Import sẽ xóa một số routing hiện có. Vui lòng xác nhận để tiếp tục.', 'error_code' => 'DELETION_WARNING', 'requires_acknowledgement' => true];
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
                    if (empty($newCongDoanIds)) {
                        $deleteStmt = mysqli_prepare($this->db, "DELETE FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND line_id IS NULL");
                        mysqli_stmt_bind_param($deleteStmt, "i", $maHangId);
                    } else {
                        $placeholders = implode(',', array_fill(0, count($newCongDoanIds), '?'));
                        $deleteStmt = mysqli_prepare($this->db, "DELETE FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND line_id IS NULL AND cong_doan_id NOT IN ($placeholders)");
                        mysqli_stmt_bind_param($deleteStmt, "i" . str_repeat("i", count($newCongDoanIds)), $maHangId, ...$newCongDoanIds);
                    }
                    mysqli_stmt_execute($deleteStmt);
                    $stats['routing_deleted'] += mysqli_stmt_affected_rows($deleteStmt);
                    mysqli_stmt_close($deleteStmt);
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
            
            // Calculate processing time
            $processingTimeMs = (int)((microtime(true) - $startTime) * 1000);

            // Build details for history
            $details = $this->buildDetailsArray($maHangList, $createdCongDoanMap ?? []);

            // Save to history if we have file info
            $historyId = null;
            if (!empty($fileName)) {
                $historyId = $this->saveImportHistory(
                    $fileName,
                    $fileSize,
                    $importedBy,
                    $this->lastPreviewStats,
                    $stats,
                    $this->lastPreviewErrors,
                    $details,
                    $processingTimeMs
                );
            }

            return [
                'success' => true,
                'message' => 'Import thành công',
                'stats' => $stats,
                'history_id' => $historyId
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
                SUM(CASE WHEN trang_thai IN ('submitted','approved','locked','completed') THEN 1 ELSE 0 END) as locked,
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
    
    /**
     * Check for reports that would block import for a ma_hang
     * @param int $maHangId
     * @return array ['is_blocked' => bool, 'blocking_reports' => [...], 'summary' => [...], 'message' => string]
     */
    public function checkBlockingReports(int $maHangId): array {
        $blockingReports = [];
        $summary = [
            'locked_count' => 0,
            'draft_with_data_count' => 0
        ];
        
        // Status display mapping
        $statusLabels = [
            'submitted' => 'Đã nộp',
            'approved' => 'Đã duyệt',
            'locked' => 'Đã khóa',
            'completed' => 'Hoàn thành',
            'draft' => 'Nháp'
        ];
        
        // Reason display mapping
        $reasonLabels = [
            'LOCKED_REPORT' => 'Báo cáo đã chốt',
            'DRAFT_WITH_DATA' => 'Báo cáo đang nhập dữ liệu'
        ];
        
        // Query 1: Locked reports (submitted, approved, locked, completed)
        $stmtLocked = mysqli_prepare($this->db,
            "SELECT bc.id, bc.ngay_bao_cao, l.ten_line, ca.ma_ca, bc.trang_thai, 
                    bc.tao_boi, 'LOCKED_REPORT' as reason
             FROM bao_cao_nang_suat bc
             LEFT JOIN line l ON l.id = bc.line_id
             LEFT JOIN ca_lam ca ON ca.id = bc.ca_id
             WHERE bc.ma_hang_id = ?
               AND bc.trang_thai IN ('submitted','approved','locked','completed')
             ORDER BY bc.ngay_bao_cao DESC
             LIMIT 50"
        );
        mysqli_stmt_bind_param($stmtLocked, "i", $maHangId);
        mysqli_stmt_execute($stmtLocked);
        $resultLocked = mysqli_stmt_get_result($stmtLocked);
        
        while ($row = mysqli_fetch_assoc($resultLocked)) {
            $row['trang_thai_label'] = $statusLabels[$row['trang_thai']] ?? $row['trang_thai'];
            $row['reason_label'] = $reasonLabels[$row['reason']] ?? $row['reason'];
            $row['ten_line'] = $row['ten_line'] ?? 'N/A';
            $row['ma_ca'] = $row['ma_ca'] ?? 'N/A';
            $blockingReports[] = $row;
            $summary['locked_count']++;
        }
        mysqli_stmt_close($stmtLocked);
        
        // Query 2: Draft reports with data (so_luong > 0)
        $stmtDraft = mysqli_prepare($this->db,
            "SELECT bc.id, bc.ngay_bao_cao, l.ten_line, ca.ma_ca, bc.trang_thai,
                    bc.tao_boi, 'DRAFT_WITH_DATA' as reason,
                    (SELECT COUNT(*) FROM nhap_lieu_nang_suat nl 
                     WHERE nl.bao_cao_id = bc.id AND nl.so_luong > 0) as total_entries
             FROM bao_cao_nang_suat bc
             LEFT JOIN line l ON l.id = bc.line_id  
             LEFT JOIN ca_lam ca ON ca.id = bc.ca_id
             WHERE bc.ma_hang_id = ?
               AND bc.trang_thai = 'draft'
               AND EXISTS (SELECT 1 FROM nhap_lieu_nang_suat nl 
                           WHERE nl.bao_cao_id = bc.id AND nl.so_luong > 0)
             ORDER BY bc.ngay_bao_cao DESC
             LIMIT 50"
        );
        mysqli_stmt_bind_param($stmtDraft, "i", $maHangId);
        mysqli_stmt_execute($stmtDraft);
        $resultDraft = mysqli_stmt_get_result($stmtDraft);
        
        while ($row = mysqli_fetch_assoc($resultDraft)) {
            $row['trang_thai_label'] = $statusLabels[$row['trang_thai']] ?? $row['trang_thai'];
            $row['reason_label'] = $reasonLabels[$row['reason']] ?? $row['reason'];
            $row['ten_line'] = $row['ten_line'] ?? 'N/A';
            $row['ma_ca'] = $row['ma_ca'] ?? 'N/A';
            $blockingReports[] = $row;
            $summary['draft_with_data_count']++;
        }
        mysqli_stmt_close($stmtDraft);
        
        $isBlocked = count($blockingReports) > 0;
        $totalBlocking = $summary['locked_count'] + $summary['draft_with_data_count'];
        
        $message = '';
        if ($isBlocked) {
            $message = "Có {$totalBlocking} báo cáo đang sử dụng mã hàng này.";
            if ($summary['locked_count'] > 0) {
                $message .= " {$summary['locked_count']} báo cáo đã chốt.";
            }
            if ($summary['draft_with_data_count'] > 0) {
                $message .= " {$summary['draft_with_data_count']} báo cáo đang nhập dữ liệu.";
            }
        }
        
        return [
            'is_blocked' => $isBlocked,
            'blocking_reports' => $blockingReports,
            'summary' => $summary,
            'message' => $message
        ];
    }
    
    public function countRoutingToDelete($maHangId, $newCongDoanIds) {
        if (empty($newCongDoanIds)) {
            $stmt = mysqli_prepare($this->db, "SELECT COUNT(*) as cnt FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND line_id IS NULL");
            mysqli_stmt_bind_param($stmt, "i", $maHangId);
        } else {
            $placeholders = implode(',', array_fill(0, count($newCongDoanIds), '?'));
            $stmt = mysqli_prepare($this->db, "SELECT COUNT(*) as cnt FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND line_id IS NULL AND cong_doan_id NOT IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, "i" . str_repeat("i", count($newCongDoanIds)), $maHangId, ...$newCongDoanIds);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return intval($row['cnt']);
    }
    
    /**
     * Save import history record
     */
    public function saveImportHistory(
        string $fileName,
        int $fileSize,
        string $importedBy,
        array $previewStats,
        array $confirmStats,
        array $errors,
        array $details,
        int $processingTimeMs
    ): int {
        $status = 'success';
        if (!empty($errors) && empty($confirmStats)) {
            $status = 'failed';
        } elseif (!empty($errors)) {
            $status = 'partial';
        }
        
        $stmt = mysqli_prepare($this->db,
            "INSERT INTO import_history (
                ten_file, kich_thuoc_file, import_boi,
                so_sheets, so_ma_hang_moi, so_ma_hang_cu,
                so_cong_doan_moi, so_cong_doan_cu, so_routing_moi, so_routing_xoa,
                ma_hang_da_tao, ma_hang_da_cap_nhat, cong_doan_da_tao,
                routing_da_tao, routing_da_xoa,
                trang_thai, loi, chi_tiet, thoi_gian_xu_ly_ms
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $loiJson = !empty($errors) ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null;
        $chiTietJson = !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
        
        // Extract previewStats to variables (mysqli_stmt_bind_param requires variables, not expressions)
        $totalSheets = $previewStats['total_sheets'] ?? 0;
        $totalMaHangNew = $previewStats['total_ma_hang_new'] ?? 0;
        $totalMaHangExisting = $previewStats['total_ma_hang_existing'] ?? 0;
        $totalCongDoanNew = $previewStats['total_cong_doan_new'] ?? 0;
        $totalCongDoanExisting = $previewStats['total_cong_doan_existing'] ?? 0;
        $totalRoutingNew = $previewStats['total_routing_new'] ?? 0;
        $routingToDelete = $previewStats['routing_to_delete'] ?? 0;
        
        // Extract confirmStats to variables
        $maHangCreated = $confirmStats['ma_hang_created'] ?? 0;
        $maHangUpdated = $confirmStats['ma_hang_updated'] ?? 0;
        $congDoanCreated = $confirmStats['cong_doan_created'] ?? 0;
        $routingCreated = $confirmStats['routing_created'] ?? 0;
        $routingDeleted = $confirmStats['routing_deleted'] ?? 0;
        
        mysqli_stmt_bind_param($stmt, "sisiiiiiiiiiiiisssi",
            $fileName,
            $fileSize,
            $importedBy,
            $totalSheets,
            $totalMaHangNew,
            $totalMaHangExisting,
            $totalCongDoanNew,
            $totalCongDoanExisting,
            $totalRoutingNew,
            $routingToDelete,
            $maHangCreated,
            $maHangUpdated,
            $congDoanCreated,
            $routingCreated,
            $routingDeleted,
            $status,
            $loiJson,
            $chiTietJson,
            $processingTimeMs
        );
        
        mysqli_stmt_execute($stmt);
        $historyId = mysqli_insert_id($this->db);
        mysqli_stmt_close($stmt);
        
        return $historyId;
    }

    /**
     * Get import history list with pagination
     */
    public function getImportHistoryList(int $page = 1, int $pageSize = 20, array $filters = []): array {
        $offset = ($page - 1) * $pageSize;
        
        $whereConditions = [];
        $params = [];
        $types = "";
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "DATE(ih.import_luc) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "DATE(ih.import_luc) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }
        
        if (!empty($filters['import_boi'])) {
            $whereConditions[] = "ih.import_boi LIKE ?";
            $params[] = "%" . $filters['import_boi'] . "%";
            $types .= "s";
        }
        
        if (!empty($filters['trang_thai'])) {
            $whereConditions[] = "ih.trang_thai = ?";
            $params[] = $filters['trang_thai'];
            $types .= "s";
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Count total
        $countSql = "SELECT COUNT(*) as total FROM import_history ih $whereClause";
        if (!empty($params)) {
            $countStmt = mysqli_prepare($this->db, $countSql);
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
            mysqli_stmt_execute($countStmt);
            $countResult = mysqli_stmt_get_result($countStmt);
        } else {
            $countResult = mysqli_query($this->db, $countSql);
        }
        $totalRow = mysqli_fetch_assoc($countResult);
        $total = intval($totalRow['total']);
        
        // Get data
        $sql = "SELECT ih.* FROM import_history ih $whereClause ORDER BY ih.import_luc DESC LIMIT ? OFFSET ?";
        $params[] = $pageSize;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        mysqli_stmt_close($stmt);
        
        // Fetch ho_ten from quan_ly_nhan_su.nhan_vien for all import_boi values
        if (!empty($data)) {
            $maNvList = array_unique(array_filter(array_map(function ($item) {
                return strtoupper(trim($item['import_boi'] ?? ''));
            }, $data)));
            
            if (!empty($maNvList)) {
                $maNvArray = array_values($maNvList);
                $placeholders = str_repeat('?,', count($maNvArray) - 1) . '?';
                $nhanVienStmt = mysqli_prepare(
                    $this->dbNhanSu,
                    "SELECT UPPER(ma_nv) as ma_nv, ho_ten FROM nhan_vien WHERE UPPER(ma_nv) IN ($placeholders)"
                );
                $nhanVienTypes = str_repeat('s', count($maNvArray));
                mysqli_stmt_bind_param($nhanVienStmt, $nhanVienTypes, ...$maNvArray);
                mysqli_stmt_execute($nhanVienStmt);
                $nhanVienResult = mysqli_stmt_get_result($nhanVienStmt);
                
                $nhanVienMap = [];
                while ($nhanVienRow = mysqli_fetch_assoc($nhanVienResult)) {
                    $nhanVienMap[$nhanVienRow['ma_nv']] = $nhanVienRow['ho_ten'];
                }
                mysqli_stmt_close($nhanVienStmt);
                
                // Add ho_ten to each data row
                foreach ($data as &$dataRow) {
                    $maNvUpper = strtoupper(trim($dataRow['import_boi'] ?? ''));
                    $dataRow['ho_ten'] = $nhanVienMap[$maNvUpper] ?? null;
                }
                unset($dataRow); // Break reference
            }
        }
        
        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_pages' => ceil($total / $pageSize)
            ]
        ];
    }

    /**
     * Get import history detail
     */
    public function getImportHistoryDetail(int $id): ?array {
        $stmt = mysqli_prepare($this->db, "SELECT * FROM import_history WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($row) {
            // Decode JSON fields
            if (!empty($row['loi'])) {
                $row['loi'] = json_decode($row['loi'], true);
            }
            if (!empty($row['chi_tiet'])) {
                $row['chi_tiet'] = json_decode($row['chi_tiet'], true);
            }
            
            // Fetch ho_ten from quan_ly_nhan_su.nhan_vien
            if (!empty($row['import_boi'])) {
                $maNvUpper = strtoupper(trim($row['import_boi']));
                $nhanVienStmt = mysqli_prepare(
                    $this->dbNhanSu,
                    "SELECT ho_ten FROM nhan_vien WHERE UPPER(ma_nv) = ?"
                );
                mysqli_stmt_bind_param($nhanVienStmt, "s", $maNvUpper);
                mysqli_stmt_execute($nhanVienStmt);
                $nhanVienResult = mysqli_stmt_get_result($nhanVienStmt);
                $nhanVienRow = mysqli_fetch_assoc($nhanVienResult);
                mysqli_stmt_close($nhanVienStmt);
                
                $row['ho_ten'] = $nhanVienRow['ho_ten'] ?? null;
            }
        }
        
        return $row;
    }

    /**
     * Build details array from maHangList for history storage
     */
    private function buildDetailsArray(array $maHangList, array $createdCongDoan = []): array {
        $details = [
            'ma_hang' => [],
            'cong_doan_moi' => []
        ];
        
        foreach ($maHangList as $item) {
            $details['ma_hang'][] = [
                'ma_hang' => $item['ma_hang'] ?? '',
                'ten_hang' => $item['ten_hang'] ?? '',
                'is_new' => $item['is_new'] ?? false,
                'existing_id' => $item['existing_id'] ?? null,
                'cong_doan_count' => count($item['cong_doan_list'] ?? []),
                'sheet_name' => $item['sheet_name'] ?? ''
            ];
        }
        
        foreach ($createdCongDoan as $name => $id) {
            $details['cong_doan_moi'][] = [
                'ten_cong_doan' => $name,
                'created_id' => $id
            ];
        }
        
        return $details;
    }
}
