<?php

namespace App\Services;

use App\Support\Database;
use stdClass;

class DanhMucLookup
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getNangSuat();
    }

    public function getContext($line_id, $ca_id = null)
    {
        $mocGioResult = $ca_id ? $this->getMocGioList($ca_id, $line_id) : ['data' => [], 'is_fallback' => true];
        $context = [
            'line' => $this->getLine($line_id),
            'ca_list' => $this->getCaList(),
            'moc_gio_list' => $mocGioResult['data'],
            'moc_gio_is_fallback' => $mocGioResult['is_fallback'],
            'ma_hang_list' => $this->getMaHangList(),
        ];
        return $context;
    }

    public function getLine($line_id)
    {
        $stmt = mysqli_prepare($this->db, 'SELECT id, ma_line, ten_line FROM line WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $line_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $line = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $line;
    }

    public function getCaList()
    {
        $result = mysqli_query($this->db, 'SELECT id, ma_ca, ten_ca, gio_bat_dau, gio_ket_thuc FROM ca_lam WHERE is_active = 1 ORDER BY id');
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }

    public function getMocGioList($ca_id, $line_id = null)
    {
        if ($line_id !== null) {
            $stmt = mysqli_prepare(
                $this->db,
                'SELECT id, gio, thu_tu, so_phut_hieu_dung_luy_ke, 1 as is_line_specific
                 FROM moc_gio
                 WHERE ca_id = ? AND line_id = ? AND is_active = 1
                 ORDER BY thu_tu',
            );
            mysqli_stmt_bind_param($stmt, 'ii', $ca_id, $line_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $list = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $list[] = $row;
            }
            mysqli_stmt_close($stmt);

            if (count($list) > 0) {
                return ['data' => $list, 'is_fallback' => false];
            }
        }

        $stmt = mysqli_prepare(
            $this->db,
            'SELECT id, gio, thu_tu, so_phut_hieu_dung_luy_ke, 0 as is_line_specific
             FROM moc_gio
             WHERE ca_id = ? AND line_id IS NULL AND is_active = 1
             ORDER BY thu_tu',
        );
        mysqli_stmt_bind_param($stmt, 'i', $ca_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        mysqli_stmt_close($stmt);

        return ['data' => $list, 'is_fallback' => true];
    }

    public function getMaHangList()
    {
        $result = mysqli_query($this->db, 'SELECT id, ma_hang, ten_hang FROM ma_hang WHERE is_active = 1 ORDER BY ma_hang');
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['dinh_muc'] = new stdClass();
            $list[$row['id']] = $row;
        }

        // LĐ + CTNS theo LINE (từ file import) để modal tạo báo cáo tự điền
        $result = mysqli_query($this->db, 'SELECT ma_hang_id, line_id, so_lao_dong, ctns FROM ma_hang_line_dinh_muc');
        while ($row = mysqli_fetch_assoc($result)) {
            if (isset($list[$row['ma_hang_id']])) {
                $list[$row['ma_hang_id']]['dinh_muc']->{$row['line_id']} = [
                    'so_lao_dong' => intval($row['so_lao_dong']),
                    'ctns' => intval($row['ctns']),
                ];
            }
        }

        return array_values($list);
    }

    public function getRouting($ma_hang_id, $line_id = null)
    {
        $sql = 'SELECT 
                    mhd.id, mhd.cong_doan_id, cd.ma_cong_doan, cd.ten_cong_doan, 
                    mhd.thu_tu, mhd.bat_buoc, mhd.la_cong_doan_tinh_luy_ke, mhd.ghi_chu
                FROM ma_hang_cong_doan mhd
                JOIN cong_doan cd ON cd.id = mhd.cong_doan_id
                WHERE mhd.ma_hang_id = ?
                  AND (mhd.line_id = ? OR mhd.line_id IS NULL)
                  AND (mhd.hieu_luc_tu IS NULL OR mhd.hieu_luc_tu <= CURDATE())
                  AND (mhd.hieu_luc_den IS NULL OR mhd.hieu_luc_den >= CURDATE())
                  AND cd.is_active = 1
                ORDER BY 
                    CASE WHEN mhd.line_id = ? THEN 0 ELSE 1 END,
                    mhd.thu_tu';

        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, 'iii', $ma_hang_id, $line_id, $line_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $list = [];
        $seenCongDoan = [];
        while ($row = mysqli_fetch_assoc($result)) {
            if (!isset($seenCongDoan[$row['cong_doan_id']])) {
                $list[] = $row;
                $seenCongDoan[$row['cong_doan_id']] = true;
            }
        }
        mysqli_stmt_close($stmt);
        return $list;
    }
}
