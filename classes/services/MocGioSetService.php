<?php
require_once __DIR__ . '/../../config/Database.php';

class MocGioSetService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getNangSuat();
    }
    
    public function getList($ca_id = null) {
        $sql = "SELECT mgs.id, mgs.ca_id, mgs.ten_set, mgs.is_default, mgs.is_active,
                       mgs.created_at, mgs.updated_at,
                       c.ma_ca, c.ten_ca,
                       (SELECT COUNT(*) FROM line_moc_gio_set lms WHERE lms.set_id = mgs.id AND lms.is_active = 1) as line_count,
                       (SELECT COUNT(*) FROM moc_gio mg WHERE mg.set_id = mgs.id AND mg.is_active = 1) as moc_gio_count
                FROM moc_gio_set mgs
                JOIN ca_lam c ON c.id = mgs.ca_id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if ($ca_id !== null) {
            $sql .= " AND mgs.ca_id = ?";
            $params[] = intval($ca_id);
            $types .= "i";
        }
        
        $sql .= " ORDER BY mgs.ca_id, mgs.is_default DESC, mgs.ten_set";
        
        if (count($params) > 0) {
            $stmt = mysqli_prepare($this->db, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($this->db, $sql);
        }
        
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
        
        return $list;
    }
    
    public function get($id) {
        $stmt = mysqli_prepare($this->db,
            "SELECT mgs.*, c.ma_ca, c.ten_ca
             FROM moc_gio_set mgs
             JOIN ca_lam c ON c.id = mgs.ca_id
             WHERE mgs.id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $set = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($set) {
            $mocGioStmt = mysqli_prepare($this->db,
                "SELECT id, gio, thu_tu, so_phut_hieu_dung_luy_ke, is_active
                 FROM moc_gio
                 WHERE set_id = ? AND is_active = 1
                 ORDER BY thu_tu"
            );
            mysqli_stmt_bind_param($mocGioStmt, "i", $id);
            mysqli_stmt_execute($mocGioStmt);
            $mocGioResult = mysqli_stmt_get_result($mocGioStmt);
            
            $mocGioList = [];
            while ($row = mysqli_fetch_assoc($mocGioResult)) {
                $mocGioList[] = $row;
            }
            mysqli_stmt_close($mocGioStmt);
            
            $set['moc_gio'] = $mocGioList;
        }
        
        return $set;
    }
    
    public function create($ca_id, $ten_set, $is_default = 0) {
        $ca_id = intval($ca_id);
        $ten_set = trim($ten_set);
        $is_default = intval($is_default);
        
        if (empty($ten_set)) {
            return ['success' => false, 'message' => 'Vui lòng nhập tên preset'];
        }
        
        $checkStmt = mysqli_prepare($this->db,
            "SELECT id FROM moc_gio_set WHERE ca_id = ? AND ten_set = ?"
        );
        mysqli_stmt_bind_param($checkStmt, "is", $ca_id, $ten_set);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Tên preset đã tồn tại trong ca này'];
        }
        mysqli_stmt_close($checkStmt);
        
        if ($is_default === 1) {
            $updateStmt = mysqli_prepare($this->db,
                "UPDATE moc_gio_set SET is_default = 0 WHERE ca_id = ?"
            );
            mysqli_stmt_bind_param($updateStmt, "i", $ca_id);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
        
        $stmt = mysqli_prepare($this->db,
            "INSERT INTO moc_gio_set (ca_id, ten_set, is_default, is_active) VALUES (?, ?, ?, 1)"
        );
        mysqli_stmt_bind_param($stmt, "isi", $ca_id, $ten_set, $is_default);
        
        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($this->db);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Tạo preset thành công', 'id' => $id];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi tạo preset: ' . mysqli_error($this->db)];
    }
    
    public function update($id, $ten_set, $is_default = null, $is_active = null) {
        $id = intval($id);
        $ten_set = trim($ten_set);
        
        $existing = $this->get($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Preset không tồn tại'];
        }
        
        if (empty($ten_set)) {
            return ['success' => false, 'message' => 'Vui lòng nhập tên preset'];
        }
        
        $checkStmt = mysqli_prepare($this->db,
            "SELECT id FROM moc_gio_set WHERE ca_id = ? AND ten_set = ? AND id != ?"
        );
        mysqli_stmt_bind_param($checkStmt, "isi", $existing['ca_id'], $ten_set, $id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Tên preset đã tồn tại trong ca này'];
        }
        mysqli_stmt_close($checkStmt);
        
        if ($is_default === 1) {
            $updateStmt = mysqli_prepare($this->db,
                "UPDATE moc_gio_set SET is_default = 0 WHERE ca_id = ? AND id != ?"
            );
            mysqli_stmt_bind_param($updateStmt, "ii", $existing['ca_id'], $id);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
        
        $sql = "UPDATE moc_gio_set SET ten_set = ?";
        $params = [$ten_set];
        $types = "s";
        
        if ($is_default !== null) {
            $sql .= ", is_default = ?";
            $params[] = intval($is_default);
            $types .= "i";
        }
        
        if ($is_active !== null) {
            $sql .= ", is_active = ?";
            $params[] = intval($is_active);
            $types .= "i";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = mysqli_prepare($this->db, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Cập nhật preset thành công'];
        }
        
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi cập nhật preset'];
    }
    
    public function delete($id) {
        $id = intval($id);
        
        $existing = $this->get($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Preset không tồn tại'];
        }
        
        if ($existing['is_default'] == 1) {
            return ['success' => false, 'message' => 'Không thể xóa preset mặc định'];
        }
        
        $checkStmt = mysqli_prepare($this->db,
            "SELECT COUNT(*) as cnt FROM moc_gio mg 
             JOIN nhap_lieu_nang_suat nlns ON nlns.moc_gio_id = mg.id 
             WHERE mg.set_id = ?"
        );
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $countRow = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);
        
        if ($countRow['cnt'] > 0) {
            return ['success' => false, 'message' => 'Không thể xóa preset đã có dữ liệu nhập liệu'];
        }
        
        mysqli_begin_transaction($this->db);
        
        try {
            $deleteMapping = mysqli_prepare($this->db, "DELETE FROM line_moc_gio_set WHERE set_id = ?");
            mysqli_stmt_bind_param($deleteMapping, "i", $id);
            mysqli_stmt_execute($deleteMapping);
            mysqli_stmt_close($deleteMapping);
            
            $deleteMocGio = mysqli_prepare($this->db, "DELETE FROM moc_gio WHERE set_id = ?");
            mysqli_stmt_bind_param($deleteMocGio, "i", $id);
            mysqli_stmt_execute($deleteMocGio);
            mysqli_stmt_close($deleteMocGio);
            
            $deleteSet = mysqli_prepare($this->db, "DELETE FROM moc_gio_set WHERE id = ?");
            mysqli_stmt_bind_param($deleteSet, "i", $id);
            mysqli_stmt_execute($deleteSet);
            mysqli_stmt_close($deleteSet);
            
            mysqli_commit($this->db);
            return ['success' => true, 'message' => 'Xóa preset thành công'];
        } catch (Exception $e) {
            mysqli_rollback($this->db);
            return ['success' => false, 'message' => 'Lỗi xóa preset: ' . $e->getMessage()];
        }
    }
    
    public function getLines($set_id) {
        $stmt = mysqli_prepare($this->db,
            "SELECT lms.id, lms.line_id, lms.ca_id, lms.set_id, lms.is_active,
                    l.ma_line, l.ten_line
             FROM line_moc_gio_set lms
             JOIN line l ON l.id = lms.line_id
             WHERE lms.set_id = ? AND lms.is_active = 1
             ORDER BY l.ma_line"
        );
        mysqli_stmt_bind_param($stmt, "i", $set_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $lines = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $lines[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $lines;
    }
    
    public function assignLines($set_id, $line_ids) {
        $set_id = intval($set_id);
        
        $set = $this->get($set_id);
        if (!$set) {
            return ['success' => false, 'message' => 'Preset không tồn tại'];
        }
        
        if (!is_array($line_ids) || count($line_ids) === 0) {
            return ['success' => false, 'message' => 'Vui lòng chọn ít nhất một LINE'];
        }
        
        $ca_id = $set['ca_id'];
        $assignedCount = 0;
        $errors = [];
        
        mysqli_begin_transaction($this->db);
        
        try {
            foreach ($line_ids as $line_id) {
                $line_id = intval($line_id);
                
                $checkStmt = mysqli_prepare($this->db,
                    "SELECT id, set_id FROM line_moc_gio_set WHERE line_id = ? AND ca_id = ?"
                );
                mysqli_stmt_bind_param($checkStmt, "ii", $line_id, $ca_id);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);
                $existingMapping = mysqli_fetch_assoc($checkResult);
                mysqli_stmt_close($checkStmt);
                
                if ($existingMapping) {
                    if ($existingMapping['set_id'] == $set_id) {
                        continue;
                    }
                    
                    $updateStmt = mysqli_prepare($this->db,
                        "UPDATE line_moc_gio_set SET set_id = ?, is_active = 1 WHERE id = ?"
                    );
                    mysqli_stmt_bind_param($updateStmt, "ii", $set_id, $existingMapping['id']);
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                } else {
                    $insertStmt = mysqli_prepare($this->db,
                        "INSERT INTO line_moc_gio_set (line_id, ca_id, set_id, is_active) VALUES (?, ?, ?, 1)"
                    );
                    mysqli_stmt_bind_param($insertStmt, "iii", $line_id, $ca_id, $set_id);
                    mysqli_stmt_execute($insertStmt);
                    mysqli_stmt_close($insertStmt);
                }
                
                $assignedCount++;
            }
            
            mysqli_commit($this->db);
            return [
                'success' => true,
                'message' => "Đã gán {$assignedCount} LINE vào preset",
                'assigned_count' => $assignedCount
            ];
        } catch (Exception $e) {
            mysqli_rollback($this->db);
            return ['success' => false, 'message' => 'Lỗi gán LINE: ' . $e->getMessage()];
        }
    }
    
    public function unassignLines($set_id, $line_ids) {
        $set_id = intval($set_id);
        
        $set = $this->get($set_id);
        if (!$set) {
            return ['success' => false, 'message' => 'Preset không tồn tại'];
        }
        
        if (!is_array($line_ids) || count($line_ids) === 0) {
            return ['success' => false, 'message' => 'Vui lòng chọn ít nhất một LINE'];
        }
        
        $removedCount = 0;
        
        foreach ($line_ids as $line_id) {
            $line_id = intval($line_id);
            
            $deleteStmt = mysqli_prepare($this->db,
                "DELETE FROM line_moc_gio_set WHERE set_id = ? AND line_id = ?"
            );
            mysqli_stmt_bind_param($deleteStmt, "ii", $set_id, $line_id);
            mysqli_stmt_execute($deleteStmt);
            
            if (mysqli_affected_rows($this->db) > 0) {
                $removedCount++;
            }
            
            mysqli_stmt_close($deleteStmt);
        }
        
        return [
            'success' => true,
            'message' => "Đã bỏ gán {$removedCount} LINE khỏi preset",
            'removed_count' => $removedCount
        ];
    }
    
    public function resolveSetForLine($ca_id, $line_id) {
        $ca_id = intval($ca_id);
        $line_id = intval($line_id);
        
        $stmt = mysqli_prepare($this->db,
            "SELECT lms.set_id, mgs.ten_set, mgs.is_default
             FROM line_moc_gio_set lms
             JOIN moc_gio_set mgs ON mgs.id = lms.set_id
             WHERE lms.line_id = ? AND lms.ca_id = ? AND lms.is_active = 1 AND mgs.is_active = 1"
        );
        mysqli_stmt_bind_param($stmt, "ii", $line_id, $ca_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $mapping = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($mapping) {
            return [
                'set_id' => $mapping['set_id'],
                'ten_set' => $mapping['ten_set'],
                'is_fallback' => false
            ];
        }
        
        $defaultStmt = mysqli_prepare($this->db,
            "SELECT id, ten_set FROM moc_gio_set WHERE ca_id = ? AND is_default = 1 AND is_active = 1"
        );
        mysqli_stmt_bind_param($defaultStmt, "i", $ca_id);
        mysqli_stmt_execute($defaultStmt);
        $defaultResult = mysqli_stmt_get_result($defaultStmt);
        $defaultSet = mysqli_fetch_assoc($defaultResult);
        mysqli_stmt_close($defaultStmt);
        
        if ($defaultSet) {
            return [
                'set_id' => $defaultSet['id'],
                'ten_set' => $defaultSet['ten_set'],
                'is_fallback' => true
            ];
        }
        
        return null;
    }
    
    public function getMocGioForLine($ca_id, $line_id) {
        $resolved = $this->resolveSetForLine($ca_id, $line_id);
        
        if (!$resolved) {
            return [
                'moc_gio' => [],
                'set_info' => null,
                'is_fallback' => true
            ];
        }
        
        $stmt = mysqli_prepare($this->db,
            "SELECT mg.id, mg.gio, mg.thu_tu, mg.so_phut_hieu_dung_luy_ke, mg.is_active
             FROM moc_gio mg
             WHERE mg.set_id = ? AND mg.is_active = 1
             ORDER BY mg.thu_tu"
        );
        mysqli_stmt_bind_param($stmt, "i", $resolved['set_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $mocGioList = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $mocGioList[] = $row;
        }
        mysqli_stmt_close($stmt);
        
        return [
            'moc_gio' => $mocGioList,
            'set_info' => [
                'set_id' => $resolved['set_id'],
                'ten_set' => $resolved['ten_set']
            ],
            'is_fallback' => $resolved['is_fallback']
        ];
    }
    
    public function getUnassignedLines($ca_id) {
        $ca_id = intval($ca_id);
        
        $stmt = mysqli_prepare($this->db,
            "SELECT l.id, l.ma_line, l.ten_line
             FROM line l
             WHERE l.is_active = 1
               AND l.id NOT IN (
                   SELECT lms.line_id 
                   FROM line_moc_gio_set lms 
                   WHERE lms.ca_id = ? AND lms.is_active = 1
               )
             ORDER BY l.ma_line"
        );
        mysqli_stmt_bind_param($stmt, "i", $ca_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $lines = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $lines[] = $row;
        }
        
        mysqli_stmt_close($stmt);
        return $lines;
    }
    
    public function copyPreset($source_set_id, $new_ten_set) {
        $source_set_id = intval($source_set_id);
        $new_ten_set = trim($new_ten_set);
        
        $sourceSet = $this->get($source_set_id);
        if (!$sourceSet) {
            return ['success' => false, 'message' => 'Preset nguồn không tồn tại'];
        }
        
        $createResult = $this->create($sourceSet['ca_id'], $new_ten_set, 0);
        if (!$createResult['success']) {
            return $createResult;
        }
        
        $newSetId = $createResult['id'];
        
        $mocGioStmt = mysqli_prepare($this->db,
            "SELECT gio, thu_tu, so_phut_hieu_dung_luy_ke FROM moc_gio WHERE set_id = ? AND is_active = 1 ORDER BY thu_tu"
        );
        mysqli_stmt_bind_param($mocGioStmt, "i", $source_set_id);
        mysqli_stmt_execute($mocGioStmt);
        $mocGioResult = mysqli_stmt_get_result($mocGioStmt);
        
        $insertStmt = mysqli_prepare($this->db,
            "INSERT INTO moc_gio (ca_id, set_id, gio, thu_tu, so_phut_hieu_dung_luy_ke, is_active) VALUES (?, ?, ?, ?, ?, 1)"
        );
        
        $copiedCount = 0;
        while ($moc = mysqli_fetch_assoc($mocGioResult)) {
            mysqli_stmt_bind_param($insertStmt, "iisii", 
                $sourceSet['ca_id'], $newSetId, $moc['gio'], $moc['thu_tu'], $moc['so_phut_hieu_dung_luy_ke']
            );
            mysqli_stmt_execute($insertStmt);
            $copiedCount++;
        }
        
        mysqli_stmt_close($mocGioStmt);
        mysqli_stmt_close($insertStmt);
        
        return [
            'success' => true,
            'message' => "Đã copy preset với {$copiedCount} mốc giờ",
            'id' => $newSetId,
            'copied_count' => $copiedCount
        ];
    }
}
