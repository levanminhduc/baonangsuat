<?php

namespace App\Services;

use App\Support\Database;
use Exception;

class ImportService
{
    private $db;
    private $lastPreviewStats = [];
    private $lastPreviewErrors = [];
    private $lastPreviewData = [];

    public function __construct(
        private ImportExcelParser $parser,
        private ImportHistoryService $history,
        private ImportLookupService $lookup,
    ) {
        $this->db = Database::getNangSuat();
    }

    public function preview($filePath)
    {
        $sheets = $this->parser->parseExcel($filePath);

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
            'total_ma_hang_with_reports' => 0,
        ];

        $newCongDoanCounter = $this->lookup->getNextMaCongDoanNumber();
        $newCongDoanMap = [];

        foreach ($sheets as $sheetData) {
            $sheetName = $sheetData['sheet_name'];
            $maHang = $sheetData['ma_hang'];
            $congDoanNames = $sheetData['cong_doan_list'];

            if (empty($maHang)) {
                $errors[] = [
                    'sheet_name' => $sheetName,
                    'cell' => 'C1',
                    'error_code' => 'INVALID_MA_HANG_FORMAT',
                    'message' => "Không tìm thấy mã hàng trong ô C1 (mẫu cũ: C2). Định dạng cần: 'MH: XXXX'",
                ];
                continue;
            }

            if (empty($congDoanNames)) {
                $errors[] = [
                    'sheet_name' => $sheetName,
                    'cell' => 'C4',
                    'error_code' => 'EMPTY_CONG_DOAN_LIST',
                    'message' => 'Không tìm thấy công đoạn nào từ C4 trở đi (mẫu cũ: C5)',
                ];
                continue;
            }

            $existingMaHang = $this->lookup->findMaHangByCode($maHang);
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
                $activeReportsCheck = $this->lookup->checkBlockingReports($maHangId);
                $hasActiveReportsForItem = $activeReportsCheck['is_blocked']; // Reusing method, treating as info

                if ($hasActiveReportsForItem) {
                    $hasActiveReports = true;
                    $stats['total_ma_hang_with_reports']++;

                    // Set warning message - informational, not blocking
                    $totalReports = $activeReportsCheck['summary']['locked_count'] + $activeReportsCheck['summary']['draft_with_data_count'];
                    $hasWarning = true;
                    $warningMessage = "Có {$totalReports} báo cáo đang sử dụng mã hàng này. Các báo cáo này sẽ giữ nguyên routing cũ (dùng snapshot, không bị ảnh hưởng).";
                }

                $reportStats = $this->lookup->checkExistingReports($maHangId);
                $routingToDelete = $this->lookup->countRoutingToDelete($maHangId, []);
                if ($routingToDelete > 0 && !$hasWarning) {
                    $hasWarning = true;
                    $warningMessage = "Import sẽ xóa {$routingToDelete} công đoạn routing hiện tại của mã hàng {$maHang}";
                } elseif ($routingToDelete > 0 && $hasWarning) {
                    $warningMessage .= " Import cũng sẽ xóa {$routingToDelete} công đoạn routing hiện tại.";
                }
            }

            $congDoanList = [];
            $thuTu = 1;

            foreach ($congDoanNames as $tenCongDoan) {
                $normalizedName = $this->parser->normalizeText($tenCongDoan);
                $upperName = mb_strtoupper($normalizedName, 'UTF-8');

                $existingCongDoan = $this->lookup->findCongDoanByName($tenCongDoan);
                $isNewCongDoan = ($existingCongDoan === null);

                if ($isNewCongDoan) {
                    if (isset($newCongDoanMap[$upperName])) {
                        $maCongDoan = $newCongDoanMap[$upperName];
                    } else {
                        $maCongDoan = $this->parser->formatMaCongDoan($newCongDoanCounter);
                        $newCongDoanMap[$upperName] = $maCongDoan;
                        $newCongDoanCounter++;
                        $stats['total_cong_doan_new']++;
                    }

                    $congDoanList[] = [
                        'thu_tu' => $thuTu,
                        'ten_cong_doan' => $normalizedName,
                        'ma_cong_doan' => $maCongDoan,
                        'is_new' => true,
                        'existing_id' => null,
                    ];
                } else {
                    $stats['total_cong_doan_existing']++;
                    $congDoanList[] = [
                        'thu_tu' => $thuTu,
                        'ten_cong_doan' => $normalizedName,
                        'ma_cong_doan' => $existingCongDoan['ma_cong_doan'],
                        'is_new' => false,
                        'existing_id' => intval($existingCongDoan['id']),
                    ];
                }

                $stats['total_routing_new']++;
                $thuTu++;
            }

            $dinhMucList = [];
            foreach ($sheetData['dinh_muc_list'] as $dinhMuc) {
                $line = $this->lookup->findLineByLabel($dinhMuc['line_label']);
                $dinhMucList[] = $dinhMuc + [
                    'line_id' => $line ? intval($line['id']) : null,
                    'ma_line' => $line['ma_line'] ?? null,
                    'ten_line' => $line['ten_line'] ?? null,
                ];
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
                'cong_doan_list' => $congDoanList,
                'dinh_muc_list' => $dinhMucList,
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
            'has_active_reports' => $hasActiveReports,
        ];
    }

    /**
     * Set preview data (called before confirm)
     */
    public function setPreviewData(array $previewResult)
    {
        $this->lastPreviewStats = $previewResult['stats'] ?? [];
        $this->lastPreviewErrors = $previewResult['errors'] ?? [];
        $this->lastPreviewData = $previewResult['data'] ?? [];
    }

    public function confirm($maHangList, $acknowledgeDeletion = false, $fileName = '', $fileSize = 0, $importedBy = '')
    {
        $startTime = microtime(true);
        if (empty($maHangList)) {
            return ['success' => false, 'message' => 'Danh sách mã hàng không được rỗng', 'error_code' => 'VALIDATION_FAILED'];
        }

        // Check for existing ma_hang that need deletion acknowledgement
        $hasExistingMaHang = false;

        foreach ($maHangList as $maHangData) {
            $maHang = strtoupper(trim($maHangData['ma_hang'] ?? ''));
            if (!empty($maHang)) {
                $existing = $this->lookup->findMaHangByCode($maHang);
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
            'routing_deleted' => 0,
            'dinh_muc_saved' => 0,
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

                $existingMaHang = $this->lookup->findMaHangByCode($maHang);
                $isExistingMaHang = ($existingMaHang !== null);

                if (!$isExistingMaHang) {
                    $stmt = mysqli_prepare($this->db, 'INSERT INTO ma_hang (ma_hang, ten_hang, is_active) VALUES (?, ?, 1)');
                    mysqli_stmt_bind_param($stmt, 'ss', $maHang, $tenHang);
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
                        $upperName = mb_strtoupper($this->parser->normalizeText($tenCongDoan), 'UTF-8');

                        if (isset($createdCongDoanMap[$upperName])) {
                            $congDoanId = $createdCongDoanMap[$upperName];
                        } else {
                            $checkExisting = $this->lookup->findCongDoanByName($tenCongDoan);
                            if ($checkExisting !== null) {
                                $congDoanId = intval($checkExisting['id']);
                            } else {
                                if (empty($maCongDoan)) {
                                    $maCongDoan = $this->lookup->generateMaCongDoan();
                                }

                                $stmt = mysqli_prepare($this->db, 'INSERT INTO cong_doan (ma_cong_doan, ten_cong_doan, is_active, la_cong_doan_thanh_pham) VALUES (?, ?, 1, 0)');
                                mysqli_stmt_bind_param($stmt, 'ss', $maCongDoan, $tenCongDoan);
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
                        'thu_tu' => $thuTu,
                    ];
                }

                if ($isExistingMaHang && !empty($routingDataList)) {
                    $newCongDoanIds = array_column($routingDataList, 'cong_doan_id');
                    if (empty($newCongDoanIds)) {
                        $deleteStmt = mysqli_prepare($this->db, 'DELETE FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND line_id IS NULL');
                        mysqli_stmt_bind_param($deleteStmt, 'i', $maHangId);
                    } else {
                        $placeholders = implode(',', array_fill(0, count($newCongDoanIds), '?'));
                        $deleteStmt = mysqli_prepare($this->db, "DELETE FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND line_id IS NULL AND cong_doan_id NOT IN ($placeholders)");
                        mysqli_stmt_bind_param($deleteStmt, 'i' . str_repeat('i', count($newCongDoanIds)), $maHangId, ...$newCongDoanIds);
                    }
                    mysqli_stmt_execute($deleteStmt);
                    $stats['routing_deleted'] += mysqli_stmt_affected_rows($deleteStmt);
                    mysqli_stmt_close($deleteStmt);
                }

                foreach ($routingDataList as $routingData) {
                    $congDoanId = $routingData['cong_doan_id'];
                    $thuTu = $routingData['thu_tu'];

                    $checkRoutingStmt = mysqli_prepare($this->db, 'SELECT id, thu_tu FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND cong_doan_id = ? AND line_id IS NULL');
                    mysqli_stmt_bind_param($checkRoutingStmt, 'ii', $maHangId, $congDoanId);
                    mysqli_stmt_execute($checkRoutingStmt);
                    $checkResult = mysqli_stmt_get_result($checkRoutingStmt);
                    $existingRouting = mysqli_fetch_assoc($checkResult);
                    mysqli_stmt_close($checkRoutingStmt);

                    if ($existingRouting === null) {
                        $stmt = mysqli_prepare($this->db, 'INSERT INTO ma_hang_cong_doan (ma_hang_id, cong_doan_id, thu_tu, bat_buoc, la_cong_doan_tinh_luy_ke, line_id, hieu_luc_tu) VALUES (?, ?, ?, 1, 0, NULL, ?)');
                        mysqli_stmt_bind_param($stmt, 'iiis', $maHangId, $congDoanId, $thuTu, $hieuLucTu);
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception('Lỗi tạo routing: ' . mysqli_error($this->db));
                        }
                        mysqli_stmt_close($stmt);
                        $stats['routing_created']++;
                    } else {
                        if (intval($existingRouting['thu_tu']) !== $thuTu) {
                            $updateStmt = mysqli_prepare($this->db, 'UPDATE ma_hang_cong_doan SET thu_tu = ? WHERE id = ?');
                            $routingId = intval($existingRouting['id']);
                            mysqli_stmt_bind_param($updateStmt, 'ii', $thuTu, $routingId);
                            mysqli_stmt_execute($updateStmt);
                            mysqli_stmt_close($updateStmt);
                        }
                    }
                }

                // Không có công đoạn lũy kế thì hiệu suất luôn trống → lấy công đoạn cuối (thường là KCS thành phẩm), không đè lựa chọn của admin
                if (!empty($routingDataList)) {
                    $lkStmt = mysqli_prepare($this->db, 'SELECT 1 FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND line_id IS NULL AND la_cong_doan_tinh_luy_ke = 1 LIMIT 1');
                    mysqli_stmt_bind_param($lkStmt, 'i', $maHangId);
                    mysqli_stmt_execute($lkStmt);
                    $hasLuyKe = mysqli_fetch_row(mysqli_stmt_get_result($lkStmt)) !== null;
                    mysqli_stmt_close($lkStmt);

                    if (!$hasLuyKe) {
                        $last = $routingDataList[0];
                        foreach ($routingDataList as $routingData) {
                            if ($routingData['thu_tu'] >= $last['thu_tu']) {
                                $last = $routingData;
                            }
                        }
                        $lastCongDoanId = $last['cong_doan_id'];
                        $setLkStmt = mysqli_prepare($this->db, 'UPDATE ma_hang_cong_doan SET la_cong_doan_tinh_luy_ke = 1 WHERE ma_hang_id = ? AND cong_doan_id = ? AND line_id IS NULL');
                        mysqli_stmt_bind_param($setLkStmt, 'ii', $maHangId, $lastCongDoanId);
                        mysqli_stmt_execute($setLkStmt);
                        mysqli_stmt_close($setLkStmt);
                    }
                }

                foreach ($maHangData['dinh_muc_list'] ?? [] as $dinhMuc) {
                    $lineId = intval($dinhMuc['line_id'] ?? 0);
                    if ($lineId <= 0) {
                        continue;
                    }
                    $soLaoDong = max(0, intval($dinhMuc['so_lao_dong'] ?? 0));
                    $ctns = max(0, intval($dinhMuc['ctns'] ?? 0));

                    $stmt = mysqli_prepare(
                        $this->db,
                        'INSERT INTO ma_hang_line_dinh_muc (ma_hang_id, line_id, so_lao_dong, ctns) VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE so_lao_dong = VALUES(so_lao_dong), ctns = VALUES(ctns)',
                    );
                    mysqli_stmt_bind_param($stmt, 'iiii', $maHangId, $lineId, $soLaoDong, $ctns);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Lỗi lưu LĐ/CTNS: ' . mysqli_error($this->db));
                    }
                    mysqli_stmt_close($stmt);
                    $stats['dinh_muc_saved']++;
                }
            }

            mysqli_commit($this->db);

            // Calculate processing time
            $processingTimeMs = (int)((microtime(true) - $startTime) * 1000);

            // Build details for history
            $details = $this->history->buildDetailsArray($maHangList, $createdCongDoanMap ?? []);

            // Save to history if we have file info
            $historyId = null;
            if (!empty($fileName)) {
                $historyId = $this->history->saveImportHistory(
                    $fileName,
                    $fileSize,
                    $importedBy,
                    $this->lastPreviewStats,
                    $stats,
                    $this->lastPreviewErrors,
                    $details,
                    $processingTimeMs,
                );
            }

            return [
                'success' => true,
                'message' => 'Import thành công',
                'stats' => $stats,
                'history_id' => $historyId,
            ];

        } catch (Exception $e) {
            mysqli_rollback($this->db);
            return [
                'success' => false,
                'message' => 'Lỗi khi import: ' . $e->getMessage(),
                'error_code' => 'IMPORT_FAILED',
            ];
        }
    }
}
