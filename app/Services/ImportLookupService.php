<?php

namespace App\Services;

use App\Support\Database;

class ImportLookupService
{
    private $db;

    public function __construct(private ImportExcelParser $parser)
    {
        $this->db = Database::getNangSuat();
    }

    public function generateMaCongDoan()
    {
        $nextNumber = $this->getNextMaCongDoanNumber();
        return $this->parser->formatMaCongDoan($nextNumber);
    }

    public function findCongDoanByName($name)
    {
        $normalized = $this->parser->normalizeText($name);
        $upperName = mb_strtoupper($normalized, 'UTF-8');

        $stmt = mysqli_prepare($this->db, 'SELECT id, ma_cong_doan, ten_cong_doan FROM cong_doan WHERE UPPER(TRIM(ten_cong_doan)) = ?');
        mysqli_stmt_bind_param($stmt, 's', $upperName);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $congDoan = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $congDoan;
    }

    // Nhãn LINE trong file là số ("1", "6"); LINE gộp ("1+6") không có trong hệ thống → null.
    public function findLineByLabel($label)
    {
        if (!preg_match('/^\d+$/', $label)) {
            return null;
        }

        $number = (string)intval($label);
        $maLine = 'LINE_' . $number;
        $tenLine = 'Chuyền ' . $number;
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT id, ma_line, ten_line FROM line
             WHERE is_active = 1 AND (UPPER(ma_line) = ? OR ma_line = ? OR ten_line = ?)
             ORDER BY id LIMIT 1',
        );
        mysqli_stmt_bind_param($stmt, 'sss', $maLine, $number, $tenLine);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $line = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $line;
    }

    public function getNextMaCongDoanNumber()
    {
        $result = mysqli_query($this->db, "SELECT MAX(CAST(SUBSTRING(ma_cong_doan, 3) AS UNSIGNED)) as max_num FROM cong_doan WHERE ma_cong_doan REGEXP '^CD[0-9]+$'");
        $row = mysqli_fetch_assoc($result);
        $maxNum = intval($row['max_num'] ?? 0);
        return $maxNum + 1;
    }

    public function findMaHangByCode($maHang)
    {
        $maHang = strtoupper(trim($maHang));

        $stmt = mysqli_prepare($this->db, 'SELECT id, ma_hang, ten_hang FROM ma_hang WHERE UPPER(TRIM(ma_hang)) = ?');
        mysqli_stmt_bind_param($stmt, 's', $maHang);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $maHangData = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $maHangData;
    }

    public function checkExistingReports($maHangId)
    {
        $stmt = mysqli_prepare(
            $this->db,
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN trang_thai IN ('submitted','approved','locked','completed') THEN 1 ELSE 0 END) as locked,
                SUM(CASE WHEN trang_thai = 'draft' THEN 1 ELSE 0 END) as draft
             FROM bao_cao_nang_suat
             WHERE ma_hang_id = ?",
        );
        mysqli_stmt_bind_param($stmt, 'i', $maHangId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return [
            'has_reports' => intval($row['total']) > 0,
            'total_reports' => intval($row['total']),
            'locked_reports' => intval($row['locked']),
            'draft_reports' => intval($row['draft']),
        ];
    }

    /**
     * Check for reports that would block import for a ma_hang
     * @return array ['is_blocked' => bool, 'blocking_reports' => [...], 'summary' => [...], 'message' => string]
     */
    public function checkBlockingReports(int $maHangId): array
    {
        $blockingReports = [];
        $summary = [
            'locked_count' => 0,
            'draft_with_data_count' => 0,
        ];

        // Status display mapping
        $statusLabels = [
            'submitted' => 'Đã nộp',
            'approved' => 'Đã duyệt',
            'locked' => 'Đã khóa',
            'completed' => 'Hoàn thành',
            'draft' => 'Nháp',
        ];

        // Reason display mapping
        $reasonLabels = [
            'LOCKED_REPORT' => 'Báo cáo đã chốt',
            'DRAFT_WITH_DATA' => 'Báo cáo đang nhập dữ liệu',
        ];

        // Query 1: Locked reports (submitted, approved, locked, completed)
        $stmtLocked = mysqli_prepare(
            $this->db,
            "SELECT bc.id, bc.ngay_bao_cao, l.ten_line, ca.ma_ca, bc.trang_thai, 
                    bc.tao_boi, 'LOCKED_REPORT' as reason
             FROM bao_cao_nang_suat bc
             LEFT JOIN line l ON l.id = bc.line_id
             LEFT JOIN ca_lam ca ON ca.id = bc.ca_id
             WHERE bc.ma_hang_id = ?
               AND bc.trang_thai IN ('submitted','approved','locked','completed')
             ORDER BY bc.ngay_bao_cao DESC
             LIMIT 50",
        );
        mysqli_stmt_bind_param($stmtLocked, 'i', $maHangId);
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
        $stmtDraft = mysqli_prepare(
            $this->db,
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
             LIMIT 50",
        );
        mysqli_stmt_bind_param($stmtDraft, 'i', $maHangId);
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
            'message' => $message,
        ];
    }

    public function countRoutingToDelete($maHangId, $newCongDoanIds)
    {
        if (empty($newCongDoanIds)) {
            $stmt = mysqli_prepare($this->db, 'SELECT COUNT(*) as cnt FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND line_id IS NULL');
            mysqli_stmt_bind_param($stmt, 'i', $maHangId);
        } else {
            $placeholders = implode(',', array_fill(0, count($newCongDoanIds), '?'));
            $stmt = mysqli_prepare($this->db, "SELECT COUNT(*) as cnt FROM ma_hang_cong_doan WHERE ma_hang_id = ? AND line_id IS NULL AND cong_doan_id NOT IN ($placeholders)");
            mysqli_stmt_bind_param($stmt, 'i' . str_repeat('i', count($newCongDoanIds)), $maHangId, ...$newCongDoanIds);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return intval($row['cnt']);
    }
}
