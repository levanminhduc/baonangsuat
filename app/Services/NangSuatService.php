<?php

namespace App\Services;

use App\Services\SubartSoLuong\SubartSoLuongFactory;
use App\Support\Database;
use Exception;
use mysqli_sql_exception;
use stdClass;
use Throwable;

class NangSuatService
{
    private const MSG_BAO_CAO_TRUNG = 'Báo cáo đã tồn tại cho ngày/ca/mã hàng/PO/subart này';

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

    private function daCoBaoCao($ngay_bao_cao, $line_id, $ca_id, $ma_hang_id, $po_number, $sub_art_code, $excludeId = 0)
    {
        $po = (string)$po_number;
        $sub = (string)$sub_art_code;
        $checkStmt = mysqli_prepare(
            $this->db,
            "SELECT id FROM bao_cao_nang_suat
             WHERE ngay_bao_cao = ? AND line_id = ? AND ca_id = ? AND ma_hang_id = ?
               AND IFNULL(po_number, '') = ? AND IFNULL(sub_art_code, '') = ? AND id <> ?",
        );
        mysqli_stmt_bind_param($checkStmt, 'siiissi', $ngay_bao_cao, $line_id, $ca_id, $ma_hang_id, $po, $sub, $excludeId);
        mysqli_stmt_execute($checkStmt);
        $found = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt)) !== null;
        mysqli_stmt_close($checkStmt);
        return $found;
    }

    // Chuẩn hoá + kiểm po_number/sub_art_code với datchi; lỗi trả kèm 'status' để controller đặt mã HTTP.
    private function chuanHoaPoSubart($ma_hang_id, $po_number, $sub_art_code)
    {
        $loi = function ($status, $message) {
            return ['success' => false, 'status' => $status, 'message' => $message];
        };
        if (($po_number !== null && !is_string($po_number)) || ($sub_art_code !== null && !is_string($sub_art_code))) {
            return $loi(422, 'PO hoặc subart không hợp lệ');
        }
        $po_number = trim((string)$po_number);
        $sub_art_code = trim((string)$sub_art_code);
        if ($po_number === '' && $sub_art_code === '') {
            return ['success' => true, 'po_number' => null, 'sub_art_code' => null];
        }
        if ($po_number === '') {
            return $loi(422, 'Chọn subart thì phải chọn PO');
        }
        if (strlen($po_number) > 50 || strlen($sub_art_code) > 100) {
            return $loi(422, 'PO hoặc subart không hợp lệ');
        }

        $stmt = mysqli_prepare($this->db, 'SELECT ma_hang FROM ma_hang WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $ma_hang_id);
        mysqli_stmt_execute($stmt);
        $maHang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$maHang) {
            return $loi(422, 'Mã hàng không tồn tại');
        }

        $datchi = new DatchiPoService();
        $po = $datchi->getPoQuantity($po_number, $maHang['ma_hang']);
        if (!$po['available']) {
            return $loi(503, 'Không kết nối được dữ liệu PO');
        }
        if ($po['quantity'] === null) {
            return $loi(422, 'PO ' . $po_number . ' không có mã hàng ' . $maHang['ma_hang']);
        }

        if ($sub_art_code === '') {
            return ['success' => true, 'po_number' => $po_number, 'sub_art_code' => null];
        }
        $subArts = $datchi->getSubArtsForStyle($maHang['ma_hang']);
        if (!$subArts['available']) {
            return $loi(503, 'Không kết nối được dữ liệu PO');
        }
        foreach ($subArts['items'] as $code) {
            if (strcasecmp($code, $sub_art_code) === 0) {
                return ['success' => true, 'po_number' => $po_number, 'sub_art_code' => $code];
            }
        }
        return $loi(422, 'Subart ' . $sub_art_code . ' không thuộc mã hàng ' . $maHang['ma_hang']);
    }

    public function createBaoCao($data, $ma_nv)
    {
        $ngay_bao_cao = $data['ngay_bao_cao'];
        $line_id = $data['line_id'];
        $ca_id = $data['ca_id'];
        $ma_hang_id = $data['ma_hang_id'];
        $so_lao_dong = intval($data['so_lao_dong'] ?? 0);
        $ctns = intval($data['ctns'] ?? 0);
        $ghi_chu = $data['ghi_chu'] ?? '';

        $poSubart = $this->chuanHoaPoSubart($ma_hang_id, $data['po_number'] ?? null, $data['sub_art_code'] ?? null);
        if (!$poSubart['success']) {
            return $poSubart;
        }
        $po_number = $poSubart['po_number'];
        $sub_art_code = $poSubart['sub_art_code'];

        if ($this->daCoBaoCao($ngay_bao_cao, $line_id, $ca_id, $ma_hang_id, $po_number, $sub_art_code)) {
            return ['success' => false, 'message' => self::MSG_BAO_CAO_TRUNG];
        }

        $mocGioResult = $this->getMocGioList($ca_id, $line_id);
        $mocGioList = $mocGioResult['data'];
        $tong_phut_hieu_dung = 0;
        if (count($mocGioList) > 0) {
            $lastMoc = $mocGioList[count($mocGioList) - 1];
            $tong_phut_hieu_dung = intval($lastMoc['so_phut_hieu_dung_luy_ke']);
        }

        $ct_gio = 0;
        if ($tong_phut_hieu_dung > 0 && $ctns > 0) {
            $ct_gio = round($ctns / ($tong_phut_hieu_dung / 60), 2);
        }

        $routing = $this->getRouting($ma_hang_id, $line_id);
        $routingSnapshot = json_encode([
            'version' => 1,
            'created_at' => date('c'),
            'routing' => $routing,
        ], JSON_UNESCAPED_UNICODE);

        $stmt = mysqli_prepare(
            $this->db,
            'INSERT INTO bao_cao_nang_suat
             (ngay_bao_cao, line_id, ca_id, ma_hang_id, so_lao_dong, ctns, ct_gio, tong_phut_hieu_dung, ghi_chu, tao_boi, routing_snapshot, po_number, sub_art_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        );
        mysqli_stmt_bind_param(
            $stmt,
            'siiiiidisssss',
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
            $po_number,
            $sub_art_code,
        );

        try {
            $ok = mysqli_stmt_execute($stmt);
        } catch (mysqli_sql_exception $e) {
            mysqli_stmt_close($stmt);
            // Hai request song song qua được check trùng thì UNIQUE chặn.
            if (intval($e->getCode()) === 1062) {
                return ['success' => false, 'message' => self::MSG_BAO_CAO_TRUNG];
            }
            throw $e;
        }
        if (!$ok) {
            $errno = mysqli_errno($this->db);
            mysqli_stmt_close($stmt);
            if ($errno === 1062) {
                return ['success' => false, 'message' => self::MSG_BAO_CAO_TRUNG];
            }
            return ['success' => false, 'message' => 'Lỗi tạo báo cáo: ' . mysqli_error($this->db)];
        }

        $bao_cao_id = mysqli_insert_id($this->db);
        mysqli_stmt_close($stmt);

        $this->preGenerateEntries($bao_cao_id, $routing, $mocGioList, $ma_nv);

        return [
            'success' => true,
            'message' => 'Tạo báo cáo thành công',
            'bao_cao_id' => $bao_cao_id,
        ];
    }

    private function preGenerateEntries($bao_cao_id, $routing, $mocGioList, $ma_nv)
    {
        $stmt = mysqli_prepare(
            $this->db,
            'INSERT INTO nhap_lieu_nang_suat (bao_cao_id, cong_doan_id, moc_gio_id, so_luong, nhap_boi)
             VALUES (?, ?, ?, 0, ?)',
        );

        foreach ($routing as $cd) {
            foreach ($mocGioList as $moc) {
                mysqli_stmt_bind_param($stmt, 'iiis', $bao_cao_id, $cd['cong_doan_id'], $moc['id'], $ma_nv);
                mysqli_stmt_execute($stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }

    public function getBaoCao($bao_cao_id)
    {
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT bc.*, l.ma_line, l.ten_line, c.ma_ca, c.ten_ca, mh.ma_hang, mh.ten_hang
             FROM bao_cao_nang_suat bc
             JOIN line l ON l.id = bc.line_id
             JOIN ca_lam c ON c.id = bc.ca_id
             JOIN ma_hang mh ON mh.id = bc.ma_hang_id
             WHERE bc.id = ?',
        );
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$baoCao) {
            return null;
        }

        if (!empty($baoCao['routing_snapshot'])) {
            $snapshot = json_decode($baoCao['routing_snapshot'], true);
            $baoCao['routing'] = $snapshot['routing'] ?? [];
            $baoCao['routing_is_snapshot'] = true;
        } else {
            $baoCao['routing'] = $this->getRouting($baoCao['ma_hang_id'], $baoCao['line_id']);
            $baoCao['routing_is_snapshot'] = false;
        }
        $mocGioResult = $this->getMocGioList($baoCao['ca_id'], $baoCao['line_id']);
        $baoCao['moc_gio_list'] = $mocGioResult['data'];
        $baoCao['moc_gio_is_fallback'] = $mocGioResult['is_fallback'];
        $baoCao['entries'] = $this->getEntries($bao_cao_id);
        $baoCao['chi_tieu_luy_ke'] = $this->calculateChiTieuLuyKe($baoCao);
        $baoCao['luy_ke_thuc_te'] = $this->calculateLuyKeThucTe($baoCao);
        $baoCao['po'] = $this->getKhoiPo($baoCao);

        return $baoCao;
    }

    // Khối PO/subart của báo cáo: SL + lũy kế thành phẩm của các báo cáo KHÁC (FE cộng báo cáo đang mở).
    private function getKhoiPo($baoCao)
    {
        $po_number = trim((string)($baoCao['po_number'] ?? ''));
        if ($po_number === '') {
            return null;
        }
        $sub_art_code = trim((string)($baoCao['sub_art_code'] ?? ''));

        // Datchi lỗi không được làm hỏng GET báo cáo.
        $available = false;
        $soLuongPo = null;
        try {
            $r = (new DatchiPoService())->getPoQuantity($po_number, $baoCao['ma_hang']);
            $available = (bool)$r['available'];
            $soLuongPo = $available ? $r['quantity'] : null;
        } catch (Throwable $e) {
        }

        $luyKePo = 0;
        $luyKeSub = 0;
        $khac = [];
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT id, line_id, routing_snapshot, sub_art_code
             FROM bao_cao_nang_suat WHERE po_number = ? AND ma_hang_id = ? AND id <> ?',
        );
        $maHangId = intval($baoCao['ma_hang_id']);
        $selfId = intval($baoCao['id']);
        mysqli_stmt_bind_param($stmt, 'sii', $po_number, $maHangId, $selfId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $khac[intval($row['id'])] = $row;
        }
        mysqli_stmt_close($stmt);

        if ($khac) {
            // Công đoạn thành phẩm theo snapshot từng báo cáo; thiếu thì lấy routing hiện tại của LINE.
            $congDoanTheoLine = [];
            $congDoan = [];
            foreach ($khac as $id => $row) {
                $cdId = PoTienDo::thanhPhamCongDoanId($row['routing_snapshot']);
                if ($cdId === null) {
                    $lineId = intval($row['line_id']);
                    if (!array_key_exists($lineId, $congDoanTheoLine)) {
                        $congDoanTheoLine[$lineId] = null;
                        foreach ($this->getRouting($maHangId, $lineId) as $cd) {
                            if (intval($cd['la_cong_doan_tinh_luy_ke']) === 1) {
                                $congDoanTheoLine[$lineId] = intval($cd['cong_doan_id']);
                                break;
                            }
                        }
                    }
                    $cdId = $congDoanTheoLine[$lineId];
                }
                $congDoan[$id] = $cdId;
            }

            // Ô nhập là lũy kế tại mốc → thành phẩm của báo cáo = số ở mốc muộn nhất đã nhập (như grid rowTotal).
            $ids = implode(',', array_map('intval', array_keys($khac)));
            $result = mysqli_query(
                $this->db,
                "SELECT nl.bao_cao_id, nl.cong_doan_id, nl.so_luong
                 FROM nhap_lieu_nang_suat nl
                 JOIN moc_gio mg ON mg.id = nl.moc_gio_id
                 WHERE nl.bao_cao_id IN ($ids) AND nl.so_luong > 0
                 ORDER BY nl.bao_cao_id, mg.thu_tu, mg.gio",
            );
            $thanhPham = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $id = intval($row['bao_cao_id']);
                if ($congDoan[$id] !== null && $congDoan[$id] === intval($row['cong_doan_id'])) {
                    $thanhPham[$id] = intval($row['so_luong']);
                }
            }
            foreach ($thanhPham as $id => $sl) {
                $luyKePo += $sl;
                if ($sub_art_code !== '' && strcasecmp((string)$khac[$id]['sub_art_code'], $sub_art_code) === 0) {
                    $luyKeSub += $sl;
                }
            }
        }

        $subArt = null;
        if ($sub_art_code !== '') {
            $soLuongSub = null;
            try {
                $items = SubartSoLuongFactory::make()->getSoLuong($po_number, $baoCao['ma_hang'])['items'] ?? [];
                foreach ($items as $code => $sl) {
                    if (strcasecmp((string)$code, $sub_art_code) === 0) {
                        $soLuongSub = intval($sl);
                        break;
                    }
                }
            } catch (Throwable $e) {
            }
            $subArt = [
                'sub_art_code' => $sub_art_code,
                'so_luong' => $soLuongSub,
                'luy_ke_bao_cao_khac' => $luyKeSub,
            ];
        }

        return [
            'po_number' => $po_number,
            'available' => $available,
            'so_luong_po' => $soLuongPo,
            'luy_ke_bao_cao_khac' => $luyKePo,
            'sub_art' => $subArt,
        ];
    }

    public function getEntries($bao_cao_id)
    {
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT id, cong_doan_id, moc_gio_id, so_luong, kieu_nhap, ghi_chu
             FROM nhap_lieu_nang_suat
             WHERE bao_cao_id = ?',
        );
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $entries = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $key = $row['cong_doan_id'] . '_' . $row['moc_gio_id'];
            $entries[$key] = $row;
        }
        mysqli_stmt_close($stmt);
        return $entries;
    }

    public function calculateChiTieuLuyKe($baoCao)
    {
        $chiTieuLuyKe = [];
        $ctns = intval($baoCao['ctns']);
        $tongPhut = intval($baoCao['tong_phut_hieu_dung']);

        if ($tongPhut <= 0 || $ctns <= 0) {
            foreach ($baoCao['moc_gio_list'] as $moc) {
                $chiTieuLuyKe[$moc['id']] = 0;
            }
            return $chiTieuLuyKe;
        }

        $lastMocId = null;
        foreach ($baoCao['moc_gio_list'] as $moc) {
            $phutLuyKe = intval($moc['so_phut_hieu_dung_luy_ke']);
            $chiTieu = round($ctns * $phutLuyKe / $tongPhut);
            $chiTieuLuyKe[$moc['id']] = $chiTieu;
            $lastMocId = $moc['id'];
        }

        if ($lastMocId !== null) {
            $chiTieuLuyKe[$lastMocId] = $ctns;
        }

        return $chiTieuLuyKe;
    }

    public function calculateLuyKeThucTe($baoCao)
    {
        $luyKeThucTe = [];
        $congDoanThanhPhamId = null;

        foreach ($baoCao['routing'] as $cd) {
            if ($cd['la_cong_doan_tinh_luy_ke'] == 1) {
                $congDoanThanhPhamId = $cd['cong_doan_id'];
                break;
            }
        }

        if (!$congDoanThanhPhamId) {
            foreach ($baoCao['moc_gio_list'] as $moc) {
                $luyKeThucTe[$moc['id']] = 0;
            }
            return $luyKeThucTe;
        }

        $cumulativeSum = 0;
        foreach ($baoCao['moc_gio_list'] as $moc) {
            $key = $congDoanThanhPhamId . '_' . $moc['id'];
            $entry = $baoCao['entries'][$key] ?? null;

            if ($entry && $entry['kieu_nhap'] === 'luy_ke') {
                $luyKeThucTe[$moc['id']] = intval($entry['so_luong']);
            } else {
                $soLuong = $entry ? intval($entry['so_luong']) : 0;
                $cumulativeSum += $soLuong;
                $luyKeThucTe[$moc['id']] = $cumulativeSum;
            }
        }

        return $luyKeThucTe;
    }

    public function calculateKetQuaLuyKe($bao_cao_id)
    {
        $baoCao = $this->getBaoCao($bao_cao_id);
        if (!$baoCao) {
            return null;
        }

        $ctns = intval($baoCao['ctns']);
        $tongPhut = intval($baoCao['tong_phut_hieu_dung']);
        $ctGio = floatval($baoCao['ct_gio']);

        $mocGioList = $baoCao['moc_gio_list'];
        $lastMoc = count($mocGioList) > 0 ? $mocGioList[count($mocGioList) - 1] : null;
        $soPhutHieuDungLuyKeCuoi = $lastMoc ? intval($lastMoc['so_phut_hieu_dung_luy_ke']) : 0;

        $chiTieuLuyKeTong = 0;
        if ($tongPhut > 0 && $ctns > 0) {
            $chiTieuLuyKeTong = round($ctns * $soPhutHieuDungLuyKeCuoi / $tongPhut);
        }

        $luyKeThucTeTong = 0;
        $congDoanDetails = [];

        foreach ($baoCao['routing'] as $cd) {
            $congDoanId = $cd['cong_doan_id'];
            $laCongDoanTinhLuyKe = intval($cd['la_cong_doan_tinh_luy_ke']);

            $luyKeCongDoan = 0;
            if ($lastMoc) {
                $key = $congDoanId . '_' . $lastMoc['id'];
                $entry = $baoCao['entries'][$key] ?? null;
                if ($entry) {
                    $luyKeCongDoan = intval($entry['so_luong']);
                }
            }

            $chiTieuCongDoan = $chiTieuLuyKeTong;

            $trangThai = 'na';
            if ($chiTieuCongDoan > 0) {
                $trangThai = $luyKeCongDoan >= $chiTieuCongDoan ? 'dat' : 'chua_dat';
            }

            if ($laCongDoanTinhLuyKe === 1) {
                $luyKeThucTeTong = $luyKeCongDoan;
            }

            $congDoanDetails[] = [
                'cong_doan_id' => $congDoanId,
                'cong_doan_ten' => $cd['ten_cong_doan'],
                'la_cong_doan_tinh_luy_ke' => $laCongDoanTinhLuyKe,
                'luy_ke_thuc_te' => $luyKeCongDoan,
                'chi_tieu_luy_ke' => $chiTieuCongDoan,
                'trang_thai' => $trangThai,
            ];
        }

        $trangThaiTong = 'na';
        if ($chiTieuLuyKeTong > 0) {
            $trangThaiTong = $luyKeThucTeTong >= $chiTieuLuyKeTong ? 'dat' : 'chua_dat';
        }

        return [
            'version' => 1,
            'generated_at' => date('c'),
            'source' => [
                'bao_cao_id' => intval($bao_cao_id),
                'ngay' => $baoCao['ngay_bao_cao'],
                'line_id' => intval($baoCao['line_id']),
                'ca_id' => intval($baoCao['ca_id']),
                'ma_hang_id' => intval($baoCao['ma_hang_id']),
            ],
            'inputs' => [
                'so_lao_dong' => intval($baoCao['so_lao_dong']),
                'ctns' => $ctns,
                'tong_phut_hieu_dung' => $tongPhut,
            ],
            'tong_hop' => [
                'ct_gio' => $ctGio,
                'luy_ke_thuc_te' => $luyKeThucTeTong,
                'chi_tieu_luy_ke' => $chiTieuLuyKeTong,
                'trang_thai' => $trangThaiTong,
            ],
            'cong_doan' => $congDoanDetails,
        ];
    }

    public function updateEntries($bao_cao_id, $entries, $version, $ma_nv)
    {
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT version, trang_thai, line_id FROM bao_cao_nang_suat WHERE id = ?',
        );
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$baoCao) {
            return ['success' => false, 'message' => 'Báo cáo không tồn tại'];
        }

        if ($baoCao['version'] != $version) {
            return ['success' => false, 'message' => 'Dữ liệu đã được cập nhật bởi người khác. Vui lòng tải lại trang.'];
        }

        if (in_array($baoCao['trang_thai'], ['submitted', 'approved', 'locked', 'completed'])) {
            return ['success' => false, 'message' => 'Báo cáo đã chốt, không thể sửa'];
        }

        mysqli_begin_transaction($this->db);

        try {
            $updateStmt = mysqli_prepare(
                $this->db,
                'UPDATE nhap_lieu_nang_suat SET so_luong = ?, nhap_boi = ?, nhap_luc = NOW() 
                 WHERE bao_cao_id = ? AND cong_doan_id = ? AND moc_gio_id = ?',
            );

            foreach ($entries as $entry) {
                $so_luong = max(0, intval($entry['so_luong']));
                $cong_doan_id = intval($entry['cong_doan_id']);
                $moc_gio_id = intval($entry['moc_gio_id']);

                mysqli_stmt_bind_param(
                    $updateStmt,
                    'isiii',
                    $so_luong,
                    $ma_nv,
                    $bao_cao_id,
                    $cong_doan_id,
                    $moc_gio_id,
                );
                mysqli_stmt_execute($updateStmt);
            }
            mysqli_stmt_close($updateStmt);

            $newVersion = intval($version) + 1;
            $versionStmt = mysqli_prepare(
                $this->db,
                'UPDATE bao_cao_nang_suat SET version = ? WHERE id = ?',
            );
            mysqli_stmt_bind_param($versionStmt, 'ii', $newVersion, $bao_cao_id);
            mysqli_stmt_execute($versionStmt);
            mysqli_stmt_close($versionStmt);

            mysqli_commit($this->db);

            return [
                'success' => true,
                'message' => 'Cập nhật thành công',
                'new_version' => $newVersion,
            ];
        } catch (Exception $e) {
            mysqli_rollback($this->db);
            return ['success' => false, 'message' => 'Lỗi cập nhật: ' . $e->getMessage()];
        }
    }

    public function updateHeader($bao_cao_id, $data, $version)
    {
        $stmt = mysqli_prepare(
            $this->db,
            'SELECT version, trang_thai, ca_id, line_id, ma_hang_id, ngay_bao_cao, po_number, sub_art_code FROM bao_cao_nang_suat WHERE id = ?',
        );
        mysqli_stmt_bind_param($stmt, 'i', $bao_cao_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$baoCao) {
            return ['success' => false, 'message' => 'Báo cáo không tồn tại'];
        }

        if ($baoCao['version'] != $version) {
            return ['success' => false, 'message' => 'Dữ liệu đã được cập nhật. Vui lòng tải lại trang.'];
        }

        if (in_array($baoCao['trang_thai'], ['approved', 'locked', 'completed'])) {
            return ['success' => false, 'message' => 'Báo cáo đã duyệt/khóa/hoàn tất, không thể sửa'];
        }

        $so_lao_dong = intval($data['so_lao_dong'] ?? 0);
        $ctns = intval($data['ctns'] ?? 0);
        $ghi_chu = $data['ghi_chu'] ?? '';

        // Không có khoá po_number/sub_art_code trong body thì giữ nguyên hai cột.
        $po_number = $baoCao['po_number'];
        $sub_art_code = $baoCao['sub_art_code'];
        if (array_key_exists('po_number', $data) || array_key_exists('sub_art_code', $data)) {
            $poSubart = $this->chuanHoaPoSubart(
                $baoCao['ma_hang_id'],
                array_key_exists('po_number', $data) ? $data['po_number'] : $po_number,
                array_key_exists('sub_art_code', $data) ? $data['sub_art_code'] : $sub_art_code,
            );
            if (!$poSubart['success']) {
                return $poSubart;
            }
            $po_number = $poSubart['po_number'];
            $sub_art_code = $poSubart['sub_art_code'];
            if ($this->daCoBaoCao($baoCao['ngay_bao_cao'], $baoCao['line_id'], $baoCao['ca_id'], $baoCao['ma_hang_id'], $po_number, $sub_art_code, intval($bao_cao_id))) {
                return ['success' => false, 'message' => self::MSG_BAO_CAO_TRUNG];
            }
        }

        $mocGioResult = $this->getMocGioList($baoCao['ca_id'], $baoCao['line_id']);
        $mocGioList = $mocGioResult['data'];
        $tong_phut_hieu_dung = 0;
        if (count($mocGioList) > 0) {
            $lastMoc = $mocGioList[count($mocGioList) - 1];
            $tong_phut_hieu_dung = intval($lastMoc['so_phut_hieu_dung_luy_ke']);
        }

        $ct_gio = 0;
        if ($tong_phut_hieu_dung > 0 && $ctns > 0) {
            $ct_gio = round($ctns / ($tong_phut_hieu_dung / 60), 2);
        }

        $newVersion = intval($version) + 1;

        $updateStmt = mysqli_prepare(
            $this->db,
            'UPDATE bao_cao_nang_suat
             SET so_lao_dong = ?, ctns = ?, ct_gio = ?, tong_phut_hieu_dung = ?, ghi_chu = ?, po_number = ?, sub_art_code = ?, version = ?
             WHERE id = ?',
        );
        mysqli_stmt_bind_param(
            $updateStmt,
            'iidisssii',
            $so_lao_dong,
            $ctns,
            $ct_gio,
            $tong_phut_hieu_dung,
            $ghi_chu,
            $po_number,
            $sub_art_code,
            $newVersion,
            $bao_cao_id,
        );

        try {
            $ok = mysqli_stmt_execute($updateStmt);
        } catch (mysqli_sql_exception $e) {
            mysqli_stmt_close($updateStmt);
            if (intval($e->getCode()) === 1062) {
                return ['success' => false, 'message' => self::MSG_BAO_CAO_TRUNG];
            }
            throw $e;
        }
        if ($ok) {
            mysqli_stmt_close($updateStmt);
            return [
                'success' => true,
                'message' => 'Cập nhật header thành công',
                'ct_gio' => $ct_gio,
                'new_version' => $newVersion,
            ];
        }

        mysqli_stmt_close($updateStmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật header'];
    }

    public function submitBaoCao($bao_cao_id, $ma_nv)
    {
        $ketQuaLuyKe = $this->calculateKetQuaLuyKe($bao_cao_id);
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

    public function getBaoCaoList($line_id, $filters = [])
    {
        $includeCompleted = isset($filters['include_completed']) ? (bool)$filters['include_completed'] : false;

        $sql = 'SELECT bc.id, bc.ngay_bao_cao, bc.so_lao_dong, bc.ctns, bc.ct_gio, bc.trang_thai,
                       l.ma_line, c.ma_ca, mh.ma_hang
                FROM bao_cao_nang_suat bc
                JOIN line l ON l.id = bc.line_id
                JOIN ca_lam c ON c.id = bc.ca_id
                JOIN ma_hang mh ON mh.id = bc.ma_hang_id
                WHERE bc.line_id = ?';

        $params = [$line_id];
        $types = 'i';

        if (!$includeCompleted) {
            $sql .= " AND bc.trang_thai != 'completed'";
        }

        if (!empty($filters['ngay_tu'])) {
            $sql .= ' AND bc.ngay_bao_cao >= ?';
            $params[] = $filters['ngay_tu'];
            $types .= 's';
        }

        if (!empty($filters['ngay_den'])) {
            $sql .= ' AND bc.ngay_bao_cao <= ?';
            $params[] = $filters['ngay_den'];
            $types .= 's';
        }

        if (!empty($filters['ca_id'])) {
            $sql .= ' AND bc.ca_id = ?';
            $params[] = $filters['ca_id'];
            $types .= 'i';
        }

        if (!empty($filters['ma_hang_id'])) {
            $sql .= ' AND bc.ma_hang_id = ?';
            $params[] = $filters['ma_hang_id'];
            $types .= 'i';
        }

        $sql .= ' ORDER BY bc.tao_luc ASC';

        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        mysqli_stmt_close($stmt);

        return $list;
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

                $mocGioResult = $this->getMocGioList($ca_id, $line_id);
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

                $routing = $this->getRouting($ma_hang_id, $line_id);
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

                $this->preGenerateEntries($bao_cao_id, $routing, $mocGioList, $ma_nv);

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
