<?php
require_once __DIR__ . '/../../config/Database.php';

class UserService
{
    private $db;
    private $dbMysqli;
    private $dbNhanSu;

    public function __construct()
    {
        $this->db = Database::getNangSuat();
        $this->dbMysqli = Database::getMysqli();
        $this->dbNhanSu = Database::getNhanSu();
    }

    public function getAll()
    {
        $result = mysqli_query($this->dbMysqli, "SELECT id, name, role FROM user ORDER BY name");
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }

    public function getAllWithInfo()
    {
        $users = $this->getAll();

        if (empty($users)) {
            return [];
        }

        $maNvList = array_map(function ($u) {
            return strtoupper(trim($u['name']));
        }, $users);
        $placeholders = str_repeat('?,', count($maNvList) - 1) . '?';

        $stmt = mysqli_prepare(
            $this->dbNhanSu,
            "SELECT UPPER(ma_nv) as ma_nv, ho_ten FROM nhan_vien WHERE UPPER(ma_nv) IN ($placeholders)"
        );

        $types = str_repeat('s', count($maNvList));
        mysqli_stmt_bind_param($stmt, $types, ...$maNvList);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $nhanVienMap = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $nhanVienMap[$row['ma_nv']] = $row['ho_ten'];
        }
        mysqli_stmt_close($stmt);

        foreach ($users as &$user) {
            $maNvUpper = strtoupper(trim($user['name']));
            $user['ho_ten'] = $nhanVienMap[$maNvUpper] ?? null;
        }

        return $users;
    }

    public function getUserLineList()
    {
        $sql = "SELECT ul.ma_nv, ul.line_id, l.ma_line, l.ten_line 
                FROM user_line ul 
                JOIN line l ON l.id = ul.line_id 
                ORDER BY ul.ma_nv, l.ma_line";
        $result = mysqli_query($this->db, $sql);
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }

    public function getUserLineListWithInfo()
    {
        $sql = "SELECT ul.ma_nv, ul.line_id, l.ma_line, l.ten_line
                FROM user_line ul
                JOIN line l ON l.id = ul.line_id
                ORDER BY ul.ma_nv, l.ma_line";
        $result = mysqli_query($this->db, $sql);
        $list = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row;
        }

        if (empty($list)) {
            return [];
        }

        $maNvList = array_unique(array_map(function ($ul) {
            return strtoupper(trim($ul['ma_nv']));
        }, $list));
        $maNvArray = array_values($maNvList);

        if (!empty($maNvArray)) {
            $placeholders = str_repeat('?,', count($maNvArray) - 1) . '?';
            $stmt = mysqli_prepare(
                $this->dbNhanSu,
                "SELECT UPPER(ma_nv) as ma_nv, ho_ten FROM nhan_vien WHERE UPPER(ma_nv) IN ($placeholders)"
            );
            $types = str_repeat('s', count($maNvArray));
            mysqli_stmt_bind_param($stmt, $types, ...$maNvArray);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $nhanVienMap = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $nhanVienMap[$row['ma_nv']] = $row['ho_ten'];
            }
            mysqli_stmt_close($stmt);

            foreach ($list as &$ul) {
                $maNvUpper = strtoupper(trim($ul['ma_nv']));
                $ul['ho_ten'] = $nhanVienMap[$maNvUpper] ?? null;
            }
        }

        return $list;
    }

    public function addUserLine($ma_nv, $line_id)
    {
        $ma_nv = strtoupper(trim($ma_nv));
        $line_id = intval($line_id);

        if (empty($ma_nv) || $line_id <= 0) {
            return ['success' => false, 'message' => 'Dữ liệu không hợp lệ'];
        }

        $checkStmt = mysqli_prepare($this->db, "SELECT 1 FROM user_line WHERE ma_nv = ? AND line_id = ?");
        mysqli_stmt_bind_param($checkStmt, "si", $ma_nv, $line_id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        if (mysqli_fetch_assoc($checkResult)) {
            mysqli_stmt_close($checkStmt);
            return ['success' => false, 'message' => 'Mapping đã tồn tại'];
        }
        mysqli_stmt_close($checkStmt);

        $stmt = mysqli_prepare($this->db, "INSERT INTO user_line (ma_nv, line_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $ma_nv, $line_id);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Thêm mapping thành công'];
        }

        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi thêm mapping: ' . mysqli_error($this->db)];
    }

    public function removeUserLine($ma_nv, $line_id)
    {
        $ma_nv = strtoupper(trim($ma_nv));
        $line_id = intval($line_id);

        $stmt = mysqli_prepare($this->db, "DELETE FROM user_line WHERE ma_nv = ? AND line_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $ma_nv, $line_id);

        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Xóa mapping thành công'];
            }
            return ['success' => false, 'message' => 'Không tìm thấy mapping'];
        }

        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Lỗi xóa mapping'];
    }
}