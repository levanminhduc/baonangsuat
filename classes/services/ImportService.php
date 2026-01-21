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
        $hasBlockingReports = false;
        $stats = [
            'total_sheets' => count($sheets),
            'total_ma_hang_new' => 0,
            'total_ma_hang_existing' => 0,
            'total_cong_doan_new' => 0,
            'total_cong_doan_existing' => 0,
            'total_routing_new' => 0,
            'routing_to_delete' => 0,
            'total_blocked_ma_hang' => 0
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
            $blockingCheck = null;
            $isBlocked = false;
            $hasWarning = false;
            $warningMessage = '';
            $routingToDelete = 0;
            
            if ($isNewMaHang) {
                $stats['total_ma_hang_new']++;
            } else {
                $stats['total_ma_hang_existing']++;
                $maHangId = intval($existingMaHang['id']);
                
                // Check for blocking reports
                $blockingCheck = $this->checkBlockingReports($maHangId);
                $isBlocked = $blockingCheck['is_blocked'];
                
                if ($isBlocked) {
                    $hasBlockingReports = true;
                    $stats['total_blocked_ma_hang']++;
                }
                
                $reportStats = $this->checkExistingReports($maHangId);
                if ($reportStats['locked_reports'] > 0 && !$isBlocked) {
                    $hasWarning = true;
                    $warningMessage = "Mã hàng {$maHang} có {$reportStats['locked_reports']} báo cáo đã chốt. Import routing mới có thể ảnh hưởng hiển thị báo cáo cũ nếu không có routing snapshot.";
                }
                $routingToDelete = $this->countRoutingToDelete($maHangId, []);
                if ($routingToDelete > 0 && !$isBlocked) {
                    $hasWarning = true;
                    $warningMessage = empty($warningMessage) ? 
                        "Import sẽ xóa {$routingToDelete} công đoạn routing hiện tại của mã hàng {$maHang}" : 
                        $warningMessage . " Import cũng sẽ xóa {$routingToDelete} công đoạn routing hiện tại.";
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
                'is_blocked' => $isBlocked,
                'blocking_check' => $blockingCheck,
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
            'has_blocking_reports' => $hasBlockingReports
        ];
    }
    
    public function confirm($maHangList, $acknowledgeDeletion = false) {
        if (empty($maHangList)) {
            return ['success' => false, 'message' => 'Danh sách mã hàng không được rỗng', 'error_code' => 'VALIDATION_FAILED'];
        }
        
        // Check for blocking reports BEFORE starting transaction
        $blockedMaHangList = [];
        $hasExistingMaHang = false;
        
        foreach ($maHangList as $maHangData) {
            $maHang = strtoupper(trim($maHangData['ma_hang'] ?? ''));
            if (!empty($maHang)) {
                $existing = $this->findMaHangByCode($maHang);
                if ($existing !== null) {
                    $hasExistingMaHang = true;
                    $maHangId = intval($existing['id']);
                    
                    // Check for blocking reports
                    $blockingCheck = $this->checkBlockingReports($maHangId);
                    if ($blockingCheck['is_blocked']) {
                        $totalBlocking = $blockingCheck['summary']['locked_count'] + $blockingCheck['summary']['draft_with_data_count'];
                        $blockedMaHangList[] = [
                            'ma_hang' => $maHang,
                            'ma_hang_id' => $maHangId,
                            'blocking_count' => $totalBlocking,
                            'blocking_check' => $blockingCheck
                        ];
                    }
                }
            }
        }
        
        // Block import if any ma_hang has blocking reports
        if (!empty($blockedMaHangList)) {
            $blockedMaHangCodes = array_column($blockedMaHangList, 'ma_hang');
            $blockedCount = count($blockedMaHangList);
            
            $message = $blockedCount === 1 
                ? "Không thể import: Mã hàng {$blockedMaHangList[0]['ma_hang']} đang có {$blockedMaHangList[0]['blocking_count']} báo cáo sử dụng."
                : "Không thể import: {$blockedCount} mã hàng đang có báo cáo sử dụng (" . implode(', ', $blockedMaHangCodes) . ").";
            
            $message .= " Vui lòng hoàn thành hoặc xóa các báo cáo trước khi import.";
            
            return [
                'success' => false,
                'message' => $message,
                'error_code' => 'IMPORT_BLOCKED',
                'blocked_ma_hang' => $blockedMaHangList
            ];
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
}
