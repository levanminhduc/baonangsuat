<?php

namespace App\Services;

use App\Support\Database;

class BaoCaoWorkflowService
{
    private $db;

    public function __construct(private BaoCaoService $baoCao, private LuyKeCalculator $luyKe)
    {
        $this->db = Database::getNangSuat();
    }

    public function submitBaoCao($bao_cao_id, $ma_nv)
    {
        $baoCao = $this->baoCao->getBaoCao($bao_cao_id);
        $ketQuaLuyKe = $baoCao ? $this->luyKe->buildKetQua($baoCao, date('c')) : null;
        return $this->changeTrangThai($bao_cao_id, 'submitted', ['draft'], $ma_nv, $ketQuaLuyKe);
    }

    public function approveBaoCao($bao_cao_id, $ma_nv)
    {
        return $this->changeTrangThai($bao_cao_id, 'approved', ['submitted'], $ma_nv);
    }

    public function unlockBaoCao($bao_cao_id, $ma_nv)
    {
        return $this->changeTrangThai($bao_cao_id, 'draft', ['submitted', 'approved', 'locked'], $ma_nv);
    }

    public function completeBaoCao($bao_cao_id, $ma_nv)
    {
        $stmt = mysqli_prepare($this->db, 'SELECT trang_thai FROM bao_cao_nang_suat WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$baoCao) {
            return ['success' => false, 'message' => 'Báo cáo không tồn tại'];
        }

        if ($baoCao['trang_thai'] === 'completed') {
            return ['success' => false, 'message' => 'Báo cáo đã hoàn tất rồi'];
        }

        $ma_nv = strtoupper(trim($ma_nv));
        $updateStmt = mysqli_prepare(
            $this->db,
            "UPDATE bao_cao_nang_suat SET trang_thai = 'completed', hoan_tat_luc = NOW(), hoan_tat_boi = ? WHERE id = ?",
        );
        mysqli_stmt_bind_param($updateStmt, 'si', $ma_nv, $bao_cao_id);

        if (mysqli_stmt_execute($updateStmt)) {
            mysqli_stmt_close($updateStmt);
            return ['success' => true, 'message' => 'Đã đánh dấu hoàn tất đơn hàng'];
        }

        mysqli_stmt_close($updateStmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật trạng thái'];
    }

    public function reopenBaoCao($bao_cao_id, $ma_nv)
    {
        $stmt = mysqli_prepare($this->db, 'SELECT trang_thai FROM bao_cao_nang_suat WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$baoCao) {
            return ['success' => false, 'message' => 'Báo cáo không tồn tại'];
        }

        if ($baoCao['trang_thai'] !== 'completed') {
            return ['success' => false, 'message' => 'Chỉ có thể mở lại báo cáo đã hoàn tất'];
        }

        $updateStmt = mysqli_prepare(
            $this->db,
            "UPDATE bao_cao_nang_suat SET trang_thai = 'draft', hoan_tat_luc = NULL, hoan_tat_boi = NULL WHERE id = ?",
        );
        mysqli_stmt_bind_param($updateStmt, 'i', $bao_cao_id);

        if (mysqli_stmt_execute($updateStmt)) {
            mysqli_stmt_close($updateStmt);
            return ['success' => true, 'message' => 'Đã mở lại báo cáo'];
        }

        mysqli_stmt_close($updateStmt);
        return ['success' => false, 'message' => 'Lỗi mở lại báo cáo'];
    }

    private function changeTrangThai($bao_cao_id, $newStatus, $allowedStatuses, $ma_nv, $ketQuaLuyKe = null)
    {
        $stmt = mysqli_prepare($this->db, 'SELECT trang_thai FROM bao_cao_nang_suat WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$baoCao) {
            return ['success' => false, 'message' => 'Báo cáo không tồn tại'];
        }

        if (!in_array($baoCao['trang_thai'], $allowedStatuses)) {
            return ['success' => false, 'message' => 'Trạng thái hiện tại không cho phép thao tác này'];
        }

        if ($ketQuaLuyKe !== null) {
            $ketQuaLuyKeJson = json_encode($ketQuaLuyKe, JSON_UNESCAPED_UNICODE);
            $updateStmt = mysqli_prepare(
                $this->db,
                'UPDATE bao_cao_nang_suat SET trang_thai = ?, ket_qua_luy_ke = ? WHERE id = ?',
            );
            mysqli_stmt_bind_param($updateStmt, 'ssi', $newStatus, $ketQuaLuyKeJson, $bao_cao_id);
        } else {
            $updateStmt = mysqli_prepare(
                $this->db,
                'UPDATE bao_cao_nang_suat SET trang_thai = ? WHERE id = ?',
            );
            mysqli_stmt_bind_param($updateStmt, 'si', $newStatus, $bao_cao_id);
        }

        if (mysqli_stmt_execute($updateStmt)) {
            mysqli_stmt_close($updateStmt);
            return ['success' => true, 'message' => 'Cập nhật trạng thái thành công'];
        }

        mysqli_stmt_close($updateStmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật trạng thái'];
    }

}
