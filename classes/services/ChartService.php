<?php
require_once __DIR__ . '/../../config/Database.php';

class ChartService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getNangSuat();
    }
    
    /**
     * Get productivity comparison data for chart
     * 
     * @param int $line_id LINE ID
     * @param int $ma_hang_id Product code ID
     * @param string $ngay Date (YYYY-MM-DD)
     * @param int $ca_id Shift ID
     * @return array Chart data response
     */
    public function getProductivityComparison($line_id, $ma_hang_id, $ngay, $ca_id) {
        // 1. Get report for the given filters
        $stmt = mysqli_prepare($this->db,
            "SELECT bc.id, bc.line_id, bc.ma_hang_id, bc.ca_id, bc.ngay_bao_cao,
                    bc.ctns, bc.tong_phut_hieu_dung, bc.ct_gio, bc.so_lao_dong, bc.routing_snapshot,
                    l.ma_line, l.ten_line,
                    mh.ma_hang, mh.ten_hang,
                    c.ma_ca, c.ten_ca
             FROM bao_cao_nang_suat bc
             JOIN line l ON l.id = bc.line_id
             JOIN ma_hang mh ON mh.id = bc.ma_hang_id
             JOIN ca_lam c ON c.id = bc.ca_id
             WHERE bc.line_id = ? AND bc.ma_hang_id = ? AND bc.ngay_bao_cao = ? AND bc.ca_id = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "iisi", $line_id, $ma_hang_id, $ngay, $ca_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$baoCao) {
            return [
                'success' => false,
                'message' => 'Không có dữ liệu cho bộ lọc đã chọn'
            ];
        }
        
        // 2. Get moc_gio list for the shift and line
        $mocGioList = $this->getMocGioList($ca_id, $line_id);
        
        if (empty($mocGioList)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy mốc giờ cho ca này'
            ];
        }
        
        // 3. Get routing (for la_cong_doan_tinh_luy_ke)
        $routing = $this->getRouting($ma_hang_id, $line_id, $baoCao['routing_snapshot']);
        
        // 4. Get entries for this report
        $entries = $this->getEntries($baoCao['id']);
        
        // 5. Calculate chi_tieu_luy_ke (target) for each moc_gio
        $ctns = intval($baoCao['ctns']);
        $tongPhut = intval($baoCao['tong_phut_hieu_dung']);
        
        $chiTieuArray = [];
        $thucTeArray = [];
        $labels = [];
        
        foreach ($mocGioList as $mocGio) {
            $mocGioId = intval($mocGio['id']);
            $soPhutLuyKe = intval($mocGio['so_phut_hieu_dung_luy_ke']);
            $labels[] = $mocGio['gio'];
            
            // Calculate target: chi_tieu_luy_ke = round(ctns * phut_luy_ke / tong_phut)
            $chiTieu = 0;
            if ($tongPhut > 0 && $ctns > 0) {
                $chiTieu = round($ctns * $soPhutLuyKe / $tongPhut);
            }
            $chiTieuArray[] = $chiTieu;
            
            // Calculate actual: cumulative sum of so_luong for stages with la_cong_doan_tinh_luy_ke = 1
            $thucTe = 0;
            foreach ($routing as $cd) {
                if (intval($cd['la_cong_doan_tinh_luy_ke']) === 1) {
                    $key = $cd['cong_doan_id'] . '_' . $mocGioId;
                    if (isset($entries[$key])) {
                        $thucTe += intval($entries[$key]['so_luong']);
                    }
                }
            }
            $thucTeArray[] = $thucTe;
        }
        
        // 6. Calculate summary
        $tongChiTieu = !empty($chiTieuArray) ? end($chiTieuArray) : 0;
        $tongThucTe = !empty($thucTeArray) ? end($thucTeArray) : 0;
        $chenhLech = $tongThucTe - $tongChiTieu;
        $tyLeHoanThanh = $tongChiTieu > 0 ? round(($tongThucTe / $tongChiTieu) * 100, 1) : 0;
        
        return [
            'success' => true,
            'data' => [
                'bao_cao_id' => intval($baoCao['id']),
                'line' => [
                    'id' => intval($baoCao['line_id']),
                    'ma_line' => $baoCao['ma_line'],
                    'ten_line' => $baoCao['ten_line']
                ],
                'ma_hang' => [
                    'id' => intval($baoCao['ma_hang_id']),
                    'ma_hang' => $baoCao['ma_hang'],
                    'ten_hang' => $baoCao['ten_hang']
                ],
                'ngay' => $baoCao['ngay_bao_cao'],
                'ca' => [
                    'id' => intval($baoCao['ca_id']),
                    'ma_ca' => $baoCao['ma_ca'],
                    'ten_ca' => $baoCao['ten_ca']
                ],
                'ctns' => $ctns,
                'so_lao_dong' => intval($baoCao['so_lao_dong']),
                'chart' => [
                    'labels' => $labels,
                    'chi_tieu' => $chiTieuArray,
                    'thuc_te' => $thucTeArray
                ],
                'summary' => [
                    'tong_chi_tieu' => $tongChiTieu,
                    'tong_thuc_te' => $tongThucTe,
                    'chenh_lech' => $chenhLech,
                    'ty_le_hoan_thanh' => $tyLeHoanThanh
                ]
            ]
        ];
    }
    
    /**
     * Get list of ma_hang that have reports for a given LINE and date
     * 
     * @param int $line_id LINE ID
     * @param string $ngay Date (YYYY-MM-DD)
     * @return array List of ma_hang with ca info
     */
    public function getMaHangListForChart($line_id, $ngay) {
        $stmt = mysqli_prepare($this->db,
            "SELECT DISTINCT mh.id, mh.ma_hang, mh.ten_hang, bc.ca_id, c.ten_ca, c.ma_ca
             FROM bao_cao_nang_suat bc
             JOIN ma_hang mh ON mh.id = bc.ma_hang_id
             JOIN ca_lam c ON c.id = bc.ca_id
             WHERE bc.line_id = ? AND bc.ngay_bao_cao = ?
             ORDER BY c.id, mh.ma_hang"
        );
        mysqli_stmt_bind_param($stmt, "is", $line_id, $ngay);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = [
                'id' => intval($row['id']),
                'ma_hang' => $row['ma_hang'],
                'ten_hang' => $row['ten_hang'],
                'ca_id' => intval($row['ca_id']),
                'ca_ten' => $row['ten_ca'],
                'ca_ma' => $row['ma_ca']
            ];
        }
        mysqli_stmt_close($stmt);
        
        return $list;
    }
    
    /**
     * Get moc_gio list for a shift, with LINE-specific fallback
     */
    private function getMocGioList($ca_id, $line_id = null) {
        // Try LINE-specific first
        if ($line_id !== null) {
            $stmt = mysqli_prepare($this->db,
                "SELECT id, gio, thu_tu, so_phut_hieu_dung_luy_ke
                 FROM moc_gio
                 WHERE ca_id = ? AND line_id = ? AND is_active = 1
                 ORDER BY thu_tu"
            );
            mysqli_stmt_bind_param($stmt, "ii", $ca_id, $line_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $list = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $list[] = $row;
            }
            mysqli_stmt_close($stmt);
            
            if (count($list) > 0) {
                return $list;
            }
        }
        
        // Fallback to default (line_id IS NULL)
        $stmt = mysqli_prepare($this->db,
            "SELECT id, gio, thu_tu, so_phut_hieu_dung_luy_ke
             FROM moc_gio
             WHERE ca_id = ? AND line_id IS NULL AND is_active = 1
             ORDER BY thu_tu"
        );
        mysqli_stmt_bind_param($stmt, "i", $ca_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        mysqli_stmt_close($stmt);
        
        return $list;
    }
    
    /**
     * Get routing for a ma_hang, using snapshot if available
     */
    private function getRouting($ma_hang_id, $line_id, $routingSnapshot = null) {
        // Use snapshot if available
        if (!empty($routingSnapshot)) {
            $snapshot = json_decode($routingSnapshot, true);
            if (isset($snapshot['routing']) && is_array($snapshot['routing'])) {
                return $snapshot['routing'];
            }
        }
        
        // Query current routing
        $sql = "SELECT 
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
                    mhd.thu_tu";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $ma_hang_id, $line_id, $line_id);
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
    
    /**
     * Get entries for a report, keyed by cong_doan_id_moc_gio_id
     */
    private function getEntries($bao_cao_id) {
        $stmt = mysqli_prepare($this->db, 
            "SELECT nl.id, nl.cong_doan_id, nl.moc_gio_id, nl.so_luong
             FROM nhap_lieu_nang_suat nl
             WHERE nl.bao_cao_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $bao_cao_id);
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
    
    /**
     * Get list of active lines
     */
    public function getLineList() {
        $result = mysqli_query($this->db, "SELECT id, ma_line, ten_line FROM line WHERE is_active = 1 ORDER BY ma_line");
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    /**
     * Get list of shifts
     */
    public function getCaList() {
        $result = mysqli_query($this->db, "SELECT id, ma_ca, ten_ca FROM ca_lam WHERE is_active = 1 ORDER BY id");
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }
    
    /**
     * Get productivity comparison matrix (công đoạn × mốc giờ) with heatmap data
     * 
     * @param int $line_id LINE ID
     * @param int $ma_hang_id Product code ID
     * @param string $ngay Date (YYYY-MM-DD)
     * @param int $ca_id Shift ID
     * @return array [
     *   'moc_gio_list' => [
     *     ['id' => 1, 'gio' => '09:00', 'so_phut_hieu_dung_luy_ke' => 90, 'chi_tieu' => 88],
     *     ...
     *   ],
     *   'cong_doan_matrix' => [
     *     [
     *       'cong_doan_id' => int,
     *       'ten_cong_doan' => string,
     *       'moc_gio_data' => [
     *         moc_gio_id => [
     *           'chi_tieu' => int,
     *           'thuc_te' => int,
     *           'chenh_lech' => int,
     *           'ty_le' => float,
     *           'trang_thai' => string  // 'dat' | 'can_chu_y' | 'chua_dat'
     *         ],
     *         ...
     *       ],
     *       'tong' => [              // Last mốc giờ aggregate (summary)
     *         'chi_tieu' => int,
     *         'thuc_te' => int,
     *         'chenh_lech' => int,
     *         'ty_le' => float,
     *         'trang_thai' => string
     *       ]
     *     ],
     *     ...
     *   ],
     *   // Metadata
     *   'ctns' => int,
     *   'tong_phut' => int,
     *   'line' => [...],
     *   'ma_hang' => [...],
     *   'ngay' => string
     * ]
     */
    public function getProductivityComparisonMatrix($line_id, $ma_hang_id, $ngay, $ca_id) {
        // 1. Get report for the given filters
        $stmt = mysqli_prepare($this->db,
            "SELECT bc.id, bc.line_id, bc.ma_hang_id, bc.ca_id, bc.ngay_bao_cao,
                    bc.ctns, bc.tong_phut_hieu_dung, bc.ct_gio, bc.so_lao_dong, bc.routing_snapshot,
                    l.ma_line, l.ten_line,
                    mh.ma_hang, mh.ten_hang,
                    c.ma_ca, c.ten_ca
             FROM bao_cao_nang_suat bc
             JOIN line l ON l.id = bc.line_id
             JOIN ma_hang mh ON mh.id = bc.ma_hang_id
             JOIN ca_lam c ON c.id = bc.ca_id
             WHERE bc.line_id = ? AND bc.ma_hang_id = ? AND bc.ngay_bao_cao = ? AND bc.ca_id = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "iisi", $line_id, $ma_hang_id, $ngay, $ca_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$baoCao) {
            return [
                'success' => false,
                'message' => 'Không có dữ liệu cho bộ lọc đã chọn'
            ];
        }
        
        // 2. Get moc_gio list for the shift and line
        $mocGioList = $this->getMocGioList($ca_id, $line_id);
        
        if (empty($mocGioList)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy mốc giờ cho ca này'
            ];
        }
        
        // 3. Get routing (all công đoạn for this mã hàng)
        $routing = $this->getRouting($ma_hang_id, $line_id, $baoCao['routing_snapshot']);
        
        if (empty($routing)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy routing cho mã hàng này'
            ];
        }
        
        // 4. Get entries for this report
        $entries = $this->getEntries($baoCao['id']);
        
        // 5. Calculate chi_tieu for each mốc giờ
        $ctns = intval($baoCao['ctns']);
        $tongPhut = intval($baoCao['tong_phut_hieu_dung']);
        
        // Build mốc giờ list with chi_tieu
        $mocGioListWithChiTieu = [];
        foreach ($mocGioList as $mocGio) {
            $soPhutLuyKe = intval($mocGio['so_phut_hieu_dung_luy_ke']);
            $chiTieu = 0;
            if ($tongPhut > 0 && $ctns > 0) {
                $chiTieu = round($ctns * $soPhutLuyKe / $tongPhut);
            }
            $mocGioListWithChiTieu[] = [
                'id' => intval($mocGio['id']),
                'gio' => $mocGio['gio'],
                'so_phut_hieu_dung_luy_ke' => $soPhutLuyKe,
                'chi_tieu' => $chiTieu
            ];
        }
        
        // 6. Build matrix for each công đoạn
        $congDoanMatrix = [];
        $lastMocGioId = end($mocGioListWithChiTieu)['id'];
        
        foreach ($routing as $cd) {
            $congDoanId = intval($cd['cong_doan_id']);
            $tenCongDoan = $cd['ten_cong_doan'];
            
            $mocGioData = [];
            $tongChiTieu = 0;
            $tongThucTe = 0;
            
            foreach ($mocGioListWithChiTieu as $mocGio) {
                $mocGioId = $mocGio['id'];
                $chiTieu = $mocGio['chi_tieu'];
                
                // Get thực tế from entries
                $key = $congDoanId . '_' . $mocGioId;
                $thucTe = 0;
                if (isset($entries[$key])) {
                    $thucTe = intval($entries[$key]['so_luong']);
                }
                
                // Calculate metrics
                $chenhLech = $thucTe - $chiTieu;
                $tyLe = $chiTieu > 0 ? round(($thucTe / $chiTieu) * 100, 1) : 0;
                
                // Determine status based on thresholds
                $trangThai = 'chua_dat'; // < 80%
                if ($tyLe >= 95) {
                    $trangThai = 'dat';
                } elseif ($tyLe >= 80) {
                    $trangThai = 'can_chu_y';
                }
                
                $mocGioData[$mocGioId] = [
                    'chi_tieu' => $chiTieu,
                    'thuc_te' => $thucTe,
                    'chenh_lech' => $chenhLech,
                    'ty_le' => $tyLe,
                    'trang_thai' => $trangThai
                ];
                
                // Track last mốc giờ values for summary
                if ($mocGioId === $lastMocGioId) {
                    $tongChiTieu = $chiTieu;
                    $tongThucTe = $thucTe;
                }
            }
            
            // Calculate summary (tổng) - same as last mốc giờ
            $tongChenhLech = $tongThucTe - $tongChiTieu;
            $tongTyLe = $tongChiTieu > 0 ? round(($tongThucTe / $tongChiTieu) * 100, 1) : 0;
            $tongTrangThai = 'chua_dat';
            if ($tongTyLe >= 95) {
                $tongTrangThai = 'dat';
            } elseif ($tongTyLe >= 80) {
                $tongTrangThai = 'can_chu_y';
            }
            
            $congDoanMatrix[] = [
                'cong_doan_id' => $congDoanId,
                'ten_cong_doan' => $tenCongDoan,
                'moc_gio_data' => $mocGioData,
                'tong' => [
                    'chi_tieu' => $tongChiTieu,
                    'thuc_te' => $tongThucTe,
                    'chenh_lech' => $tongChenhLech,
                    'ty_le' => $tongTyLe,
                    'trang_thai' => $tongTrangThai
                ]
            ];
        }
        
        return [
            'success' => true,
            'data' => [
                'bao_cao_id' => intval($baoCao['id']),
                'line' => [
                    'id' => intval($baoCao['line_id']),
                    'ma_line' => $baoCao['ma_line'],
                    'ten_line' => $baoCao['ten_line']
                ],
                'ma_hang' => [
                    'id' => intval($baoCao['ma_hang_id']),
                    'ma_hang' => $baoCao['ma_hang'],
                    'ten_hang' => $baoCao['ten_hang']
                ],
                'ngay' => $baoCao['ngay_bao_cao'],
                'ca' => [
                    'id' => intval($baoCao['ca_id']),
                    'ma_ca' => $baoCao['ma_ca'],
                    'ten_ca' => $baoCao['ten_ca']
                ],
                'ctns' => $ctns,
                'tong_phut' => $tongPhut,
                'moc_gio_list' => $mocGioListWithChiTieu,
                'cong_doan_matrix' => $congDoanMatrix
            ]
        ];
    }
    
    /**
     * Get detailed per-công đoạn productivity comparison
     * 
     * @param int $line_id LINE ID
     * @param int $ma_hang_id Product code ID
     * @param string $ngay Date (YYYY-MM-DD)
     * @param int $ca_id Shift ID
     * @return array [
     *   'cong_doan_details' => [
     *     [
     *       'cong_doan_id' => int,
     *       'ten_cong_doan' => string,
     *       'chi_tieu' => int,        // = CTNS (same for all)
     *       'thuc_te' => int,         // sum of so_luong at last mốc giờ
     *       'chenh_lech' => int,      // thuc_te - chi_tieu
     *       'ty_le' => float,         // (thuc_te / chi_tieu) * 100
     *       'trang_thai' => string    // 'dat' | 'chua_dat'
     *     ],
     *     ...
     *   ],
     *   'cong_doan_chart' => [
     *     'labels' => ['Cắt', 'May', ...],
     *     'chi_tieu' => [500, 500, ...],
     *     'thuc_te' => [450, 480, ...],
     *     'below_target' => [true, true, ...]
     *   ]
     * ]
     */
    public function getProductivityComparisonDetailed($line_id, $ma_hang_id, $ngay, $ca_id) {
        // 1. Get report for the given filters
        $stmt = mysqli_prepare($this->db,
            "SELECT bc.id, bc.line_id, bc.ma_hang_id, bc.ca_id, bc.ngay_bao_cao,
                    bc.ctns, bc.tong_phut_hieu_dung, bc.ct_gio, bc.so_lao_dong, bc.routing_snapshot,
                    l.ma_line, l.ten_line,
                    mh.ma_hang, mh.ten_hang,
                    c.ma_ca, c.ten_ca
             FROM bao_cao_nang_suat bc
             JOIN line l ON l.id = bc.line_id
             JOIN ma_hang mh ON mh.id = bc.ma_hang_id
             JOIN ca_lam c ON c.id = bc.ca_id
             WHERE bc.line_id = ? AND bc.ma_hang_id = ? AND bc.ngay_bao_cao = ? AND bc.ca_id = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "iisi", $line_id, $ma_hang_id, $ngay, $ca_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $baoCao = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$baoCao) {
            return [
                'success' => false,
                'message' => 'Không có dữ liệu cho bộ lọc đã chọn'
            ];
        }
        
        // 2. Get moc_gio list for the shift and line
        $mocGioList = $this->getMocGioList($ca_id, $line_id);
        
        if (empty($mocGioList)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy mốc giờ cho ca này'
            ];
        }
        
        // Find the LAST mốc giờ (highest thu_tu)
        $lastMocGio = end($mocGioList);
        $lastMocGioId = intval($lastMocGio['id']);
        
        // 3. Get routing (all công đoạn for this mã hàng)
        $routing = $this->getRouting($ma_hang_id, $line_id, $baoCao['routing_snapshot']);
        
        if (empty($routing)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy routing cho mã hàng này'
            ];
        }
        
        // 4. Get entries for this report
        $entries = $this->getEntries($baoCao['id']);
        
        // 5. Calculate per-công đoạn breakdown
        $ctns = intval($baoCao['ctns']);
        
        $congDoanDetails = [];
        $chartLabels = [];
        $chartChiTieu = [];
        $chartThucTe = [];
        $chartBelowTarget = [];
        
        foreach ($routing as $cd) {
            $congDoanId = intval($cd['cong_doan_id']);
            $tenCongDoan = $cd['ten_cong_doan'];
            
            // chi_tieu = CTNS (same for all công đoạn)
            $chiTieu = $ctns;
            
            // thuc_te = entry.so_luong where cong_doan_id and moc_gio_id = last mốc giờ
            $key = $congDoanId . '_' . $lastMocGioId;
            $thucTe = 0;
            if (isset($entries[$key])) {
                $thucTe = intval($entries[$key]['so_luong']);
            }
            
            // Calculate chênh_lệch, tỷ_lệ, trạng_thái
            $chenhLech = $thucTe - $chiTieu;
            $tyLe = $chiTieu > 0 ? round(($thucTe / $chiTieu) * 100, 1) : 0;
            $trangThai = $thucTe >= $chiTieu ? 'dat' : 'chua_dat';
            
            $congDoanDetails[] = [
                'cong_doan_id' => $congDoanId,
                'ten_cong_doan' => $tenCongDoan,
                'chi_tieu' => $chiTieu,
                'thuc_te' => $thucTe,
                'chenh_lech' => $chenhLech,
                'ty_le' => $tyLe,
                'trang_thai' => $trangThai
            ];
            
            // Chart data
            $chartLabels[] = $tenCongDoan;
            $chartChiTieu[] = $chiTieu;
            $chartThucTe[] = $thucTe;
            $chartBelowTarget[] = $thucTe < $chiTieu;
        }
        
        return [
            'success' => true,
            'data' => [
                'bao_cao_id' => intval($baoCao['id']),
                'line' => [
                    'id' => intval($baoCao['line_id']),
                    'ma_line' => $baoCao['ma_line'],
                    'ten_line' => $baoCao['ten_line']
                ],
                'ma_hang' => [
                    'id' => intval($baoCao['ma_hang_id']),
                    'ma_hang' => $baoCao['ma_hang'],
                    'ten_hang' => $baoCao['ten_hang']
                ],
                'ngay' => $baoCao['ngay_bao_cao'],
                'ca' => [
                    'id' => intval($baoCao['ca_id']),
                    'ma_ca' => $baoCao['ma_ca'],
                    'ten_ca' => $baoCao['ten_ca']
                ],
                'ctns' => $ctns,
                'last_moc_gio' => $lastMocGio['gio'],
                'cong_doan_details' => $congDoanDetails,
                'cong_doan_chart' => [
                    'labels' => $chartLabels,
                    'chi_tieu' => $chartChiTieu,
                    'thuc_te' => $chartThucTe,
                    'below_target' => $chartBelowTarget
                ]
            ]
        ];
    }
}
