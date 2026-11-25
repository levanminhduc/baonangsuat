<?php

namespace App\Services;

use App\Support\Database;

class ImportHistoryService
{
    private $db;
    private $dbNhanSu;

    public function __construct()
    {
        $this->db = Database::getNangSuat();
        $this->dbNhanSu = Database::getNhanSu();
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
        int $processingTimeMs,
    ): int {
        $status = 'success';
        if (!empty($errors) && empty($confirmStats)) {
            $status = 'failed';
        } elseif (!empty($errors)) {
            $status = 'partial';
        }

        $stmt = mysqli_prepare(
            $this->db,
            'INSERT INTO import_history (
                ten_file, kich_thuoc_file, import_boi,
                so_sheets, so_ma_hang_moi, so_ma_hang_cu,
                so_cong_doan_moi, so_cong_doan_cu, so_routing_moi, so_routing_xoa,
                ma_hang_da_tao, ma_hang_da_cap_nhat, cong_doan_da_tao,
                routing_da_tao, routing_da_xoa,
                trang_thai, loi, chi_tiet, thoi_gian_xu_ly_ms
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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

        mysqli_stmt_bind_param(
            $stmt,
            'sisiiiiiiiiiiiisssi',
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
            $processingTimeMs,
        );

        mysqli_stmt_execute($stmt);
        $historyId = mysqli_insert_id($this->db);
        mysqli_stmt_close($stmt);

        return $historyId;
    }

    /**
     * Get import history list with pagination
     */
    public function getImportHistoryList(int $page = 1, int $pageSize = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $pageSize;

        $whereConditions = [];
        $params = [];
        $types = '';

        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(ih.import_luc) >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(ih.import_luc) <= ?';
            $params[] = $filters['date_to'];
            $types .= 's';
        }

        if (!empty($filters['import_boi'])) {
            $whereConditions[] = 'ih.import_boi LIKE ?';
            $params[] = '%' . $filters['import_boi'] . '%';
            $types .= 's';
        }

        if (!empty($filters['trang_thai'])) {
            $whereConditions[] = 'ih.trang_thai = ?';
            $params[] = $filters['trang_thai'];
            $types .= 's';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

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
        $types .= 'ii';

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
                    "SELECT UPPER(ma_nv) as ma_nv, ho_ten FROM nhan_vien WHERE UPPER(ma_nv) IN ($placeholders)",
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
                'total_pages' => ceil($total / $pageSize),
            ],
        ];
    }

    /**
     * Get import history detail
     */
    public function getImportHistoryDetail(int $id): ?array
    {
        $stmt = mysqli_prepare($this->db, 'SELECT * FROM import_history WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);
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
                    'SELECT ho_ten FROM nhan_vien WHERE UPPER(ma_nv) = ?',
                );
                mysqli_stmt_bind_param($nhanVienStmt, 's', $maNvUpper);
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
    public function buildDetailsArray(array $maHangList, array $createdCongDoan = []): array
    {
        $details = [
            'ma_hang' => [],
            'cong_doan_moi' => [],
        ];

        foreach ($maHangList as $item) {
            $details['ma_hang'][] = [
                'ma_hang' => $item['ma_hang'] ?? '',
                'ten_hang' => $item['ten_hang'] ?? '',
                'is_new' => $item['is_new'] ?? false,
                'existing_id' => $item['existing_id'] ?? null,
                'cong_doan_count' => count($item['cong_doan_list'] ?? []),
                'sheet_name' => $item['sheet_name'] ?? '',
            ];
        }

        foreach ($createdCongDoan as $name => $id) {
            $details['cong_doan_moi'][] = [
                'ten_cong_doan' => $name,
                'created_id' => $id,
            ];
        }

        return $details;
    }
}
