<?php

namespace App\Services;

use App\Support\Database;
use Exception;

class BaoCaoBulkService
{
    private $db;

    public function __construct(
        private DanhMucLookup $danhMuc,
        private BaoCaoService $baoCao,
    ) {
        $this->db = Database::getNangSuat();
    }

    public function bulkCreateBaoCao($items, $ma_nv, $skipExisting = true)
    {
        $created = [];
        $skipped = [];

        mysqli_begin_transaction($this->db);

        try {
            foreach ($items as $item) {
                $line_id = intval($item['line_id']);
                $ma_hang_id = intval($item['ma_hang_id']);
                $ngay_bao_cao = $item['ngay'];
                $ca_id = intval($item['ca_id']);

                $checkStmt = mysqli_prepare(
                    $this->db,
                    'SELECT id FROM bao_cao_nang_suat WHERE ngay_bao_cao = ? AND line_id = ? AND ca_id = ? AND ma_hang_id = ?',
                );
                mysqli_stmt_bind_param($checkStmt, 'siii', $ngay_bao_cao, $line_id, $ca_id, $ma_hang_id);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);
                $existing = mysqli_fetch_assoc($checkResult);
                mysqli_stmt_close($checkStmt);

                if ($existing) {
                    if ($skipExisting) {
                        $skipped[] = [
                            'line_id' => $line_id,
                            'ma_hang_id' => $ma_hang_id,
                            'ngay' => $ngay_bao_cao,
                            'ca_id' => $ca_id,
                            'reason' => 'exists',
                            'bao_cao_id' => intval($existing['id']),
                        ];
                        continue;
                    } else {
                        mysqli_rollback($this->db);
                        return [
                            'success' => false,
                            'message' => "Báo cáo đã tồn tại cho line_id=$line_id, ma_hang_id=$ma_hang_id, ngay=$ngay_bao_cao, ca_id=$ca_id",
                        ];
                    }
                }

                $mocGioResult = $this->danhMuc->getMocGioList($ca_id, $line_id);
                $mocGioList = $mocGioResult['data'];
                $tong_phut_hieu_dung = 0;
                if (count($mocGioList) > 0) {
                    $lastMoc = $mocGioList[count($mocGioList) - 1];
                    $tong_phut_hieu_dung = intval($lastMoc['so_phut_hieu_dung_luy_ke']);
                }

                $so_lao_dong = intval($item['so_lao_dong'] ?? 0);
                $ctns = intval($item['ctns'] ?? 0);
                $ghi_chu = $item['ghi_chu'] ?? '';

                $ct_gio = 0;
                if ($tong_phut_hieu_dung > 0 && $ctns > 0) {
                    $ct_gio = round($ctns / ($tong_phut_hieu_dung / 60), 2);
                }

                $routing = $this->danhMuc->getRouting($ma_hang_id, $line_id);
                $routingSnapshot = json_encode([
                    'version' => 1,
                    'created_at' => date('c'),
                    'routing' => $routing,
                ], JSON_UNESCAPED_UNICODE);

                $stmt = mysqli_prepare(
                    $this->db,
                    'INSERT INTO bao_cao_nang_suat
                     (ngay_bao_cao, line_id, ca_id, ma_hang_id, so_lao_dong, ctns, ct_gio, tong_phut_hieu_dung, ghi_chu, tao_boi, routing_snapshot)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    'siiiiidisss',
                    $ngay_bao_cao,
                    $line_id,
                    $ca_id,
                    $ma_hang_id,
                    $so_lao_dong,
                    $ctns,
                    $ct_gio,
                    $tong_phut_hieu_dung,
                    $ghi_chu,
                    $ma_nv,
                    $routingSnapshot,
                );

                if (!mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    mysqli_rollback($this->db);
                    return ['success' => false, 'message' => 'Lỗi tạo báo cáo: ' . mysqli_error($this->db)];
                }

                $bao_cao_id = mysqli_insert_id($this->db);
                mysqli_stmt_close($stmt);

                $this->baoCao->preGenerateEntries($bao_cao_id, $routing, $mocGioList, $ma_nv);

                $created[] = [
                    'line_id' => $line_id,
                    'ma_hang_id' => $ma_hang_id,
                    'ngay' => $ngay_bao_cao,
                    'ca_id' => $ca_id,
                    'bao_cao_id' => $bao_cao_id,
                ];
            }

            mysqli_commit($this->db);

            return [
                'success' => true,
                'message' => 'Đã tạo báo cáo hàng loạt',
                'data' => [
                    'created' => $created,
                    'skipped' => $skipped,
                ],
            ];
        } catch (Exception $e) {
            mysqli_rollback($this->db);
            return ['success' => false, 'message' => 'Lỗi tạo báo cáo hàng loạt: ' . $e->getMessage()];
        }
    }

    public function deleteBaoCao($bao_cao_id)
    {
        $bao_cao_id = intval($bao_cao_id);

        $stmt = mysqli_prepare($this->db, 'SELECT id FROM bao_cao_nang_suat WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$baoCao) {
            return ['success' => false, 'message' => 'Báo cáo không tồn tại'];
        }

        mysqli_begin_transaction($this->db);

        try {
            $entryStmt = mysqli_prepare($this->db, 'SELECT id FROM nhap_lieu_nang_suat WHERE bao_cao_id = ?');
            mysqli_stmt_bind_param($entryStmt, 'i', $bao_cao_id);
            mysqli_stmt_execute($entryStmt);
            $entryResult = mysqli_stmt_get_result($entryStmt);
            $entryIds = [];
            while ($row = mysqli_fetch_assoc($entryResult)) {
                $entryIds[] = $row['id'];
            }
            mysqli_stmt_close($entryStmt);

            if (!empty($entryIds)) {
                $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
                $types = str_repeat('i', count($entryIds));
                $auditStmt = mysqli_prepare($this->db, "DELETE FROM nhap_lieu_nang_suat_audit WHERE entry_id IN ($placeholders)");
                mysqli_stmt_bind_param($auditStmt, $types, ...$entryIds);
                mysqli_stmt_execute($auditStmt);
                mysqli_stmt_close($auditStmt);
            }

            $deleteStmt = mysqli_prepare($this->db, 'DELETE FROM bao_cao_nang_suat WHERE id = ?');
            mysqli_stmt_bind_param($deleteStmt, 'i', $bao_cao_id);

            if (!mysqli_stmt_execute($deleteStmt)) {
                throw new Exception(mysqli_error($this->db));
            }

            $affected = mysqli_stmt_affected_rows($deleteStmt);
            mysqli_stmt_close($deleteStmt);

            if ($affected === 0) {
                throw new Exception('Không tìm thấy báo cáo');
            }

            mysqli_commit($this->db);
            return ['success' => true, 'message' => 'Xóa báo cáo thành công'];

        } catch (Exception $e) {
            mysqli_rollback($this->db);
            return ['success' => false, 'message' => 'Lỗi xóa báo cáo: ' . $e->getMessage()];
        }
    }
}
