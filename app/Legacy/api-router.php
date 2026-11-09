<?php
use App\Auth\Auth;
use App\Http\CsrfToken;
use App\Services\AdminService;
use App\Services\ChartService;
use App\Services\HistoryService;
use App\Services\ImportService;
use App\Services\MocGioSetService;
use App\Services\NangSuatService;
use App\Support\Database;

// Router API cũ, chỉ còn các nhóm chưa chuyển sang App\Http\Router (xem config/routes.php).
// public/index.php đã gửi header CORS, xử lý OPTIONS và nạp bootstrap trước khi include file này.

$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/baonangsuat/api/';
$path = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));
$path = trim($path, '/');
$segments = explode('/', $path);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

function response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireLogin() {
    if (!Auth::isLoggedIn()) {
        response(['success' => false, 'message' => 'Chưa đăng nhập'], 401);
    }
}

function requireLine() {
    requireLogin();
    if (!Auth::hasLine()) {
        response(['success' => false, 'message' => 'Chưa chọn LINE'], 403);
    }
}

function requireRole($roles) {
    requireLogin();
    if (!Auth::checkRole($roles)) {
        response(['success' => false, 'message' => 'Không có quyền thực hiện'], 403);
    }
}

function validateCsrf() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token)) {
        return false;
    }
    return CsrfToken::validate($token);
}

function requireCsrf() {
    if (!validateCsrf()) {
        response(['success' => false, 'message' => 'CSRF token không hợp lệ', 'csrf_error' => true], 403);
    }
}

try {
    switch ($segments[0]) {
        case 'admin':
            handleAdmin($segments, $method, $input);
            break;
        case 'user-permissions':
            handleUserPermissions($segments, $method, $input);
            break;
        default:
            response(['success' => false, 'message' => 'API không tồn tại'], 404);
    }
} catch (Exception $e) {
    response(['success' => false, 'message' => $e->getMessage()], 500);
}

function handleAdmin($segments, $method, $input) {
    requireRole(['admin']);
    $service = new AdminService();
    
    $resource = $segments[1] ?? '';
    $id = isset($segments[2]) && is_numeric($segments[2]) ? intval($segments[2]) : null;
    
    switch ($resource) {
        case 'lines':
            if ($method === 'GET' && !$id) {
                response(['success' => true, 'data' => $service->getLineList()]);
            }
            
            if ($method === 'GET' && $id) {
                $line = $service->getLine($id);
                if (!$line) {
                    response(['success' => false, 'message' => 'LINE không tồn tại'], 404);
                }
                response(['success' => true, 'data' => $line]);
            }
            
            if ($method === 'POST') {
                requireCsrf();
                $ma_line = $input['ma_line'] ?? '';
                $ten_line = $input['ten_line'] ?? '';
                response($service->createLine($ma_line, $ten_line));
            }
            
            if ($method === 'PUT' && $id) {
                requireCsrf();
                $ma_line = $input['ma_line'] ?? '';
                $ten_line = $input['ten_line'] ?? '';
                $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;
                response($service->updateLine($id, $ma_line, $ten_line, $is_active));
            }
            
            if ($method === 'DELETE' && $id) {
                requireCsrf();
                response($service->deleteLine($id));
            }
            break;
            
        case 'users':
            if ($method === 'GET') {
                response(['success' => true, 'data' => $service->getUsersWithInfo()]);
            }
            break;
            
        case 'user-lines':
            if ($method === 'GET') {
                response(['success' => true, 'data' => $service->getUserLineListWithInfo()]);
            }
            
            if ($method === 'POST') {
                requireCsrf();
                $ma_nv = $input['ma_nv'] ?? '';
                $line_id = intval($input['line_id'] ?? 0);
                response($service->addUserLine($ma_nv, $line_id));
            }
            
            if ($method === 'DELETE') {
                requireCsrf();
                $ma_nv = $input['ma_nv'] ?? '';
                $line_id = intval($input['line_id'] ?? 0);
                response($service->removeUserLine($ma_nv, $line_id));
            }
            break;
            
        case 'ma-hang':
            if ($method === 'GET' && !$id) {
                response(['success' => true, 'data' => $service->getMaHangList()]);
            }
            
            if ($method === 'GET' && $id) {
                $maHang = $service->getMaHang($id);
                if (!$maHang) {
                    response(['success' => false, 'message' => 'Mã hàng không tồn tại'], 404);
                }
                response(['success' => true, 'data' => $maHang]);
            }
            
            if ($method === 'POST') {
                requireCsrf();
                $ma_hang = $input['ma_hang'] ?? '';
                $ten_hang = $input['ten_hang'] ?? '';
                response($service->createMaHang($ma_hang, $ten_hang));
            }
            
            if ($method === 'PUT' && $id) {
                requireCsrf();
                $ma_hang = $input['ma_hang'] ?? '';
                $ten_hang = $input['ten_hang'] ?? '';
                $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;
                response($service->updateMaHang($id, $ma_hang, $ten_hang, $is_active));
            }
            
            if ($method === 'DELETE' && $id) {
                requireCsrf();
                response($service->deleteMaHang($id));
            }
            break;
            
        case 'cong-doan':
            if ($method === 'GET' && !$id) {
                response(['success' => true, 'data' => $service->getCongDoanList()]);
            }
            
            if ($method === 'GET' && $id) {
                $congDoan = $service->getCongDoan($id);
                if (!$congDoan) {
                    response(['success' => false, 'message' => 'Công đoạn không tồn tại'], 404);
                }
                response(['success' => true, 'data' => $congDoan]);
            }
            
            if ($method === 'POST') {
                requireCsrf();
                $ma_cong_doan = $input['ma_cong_doan'] ?? '';
                $ten_cong_doan = $input['ten_cong_doan'] ?? '';
                $la_cong_doan_thanh_pham = isset($input['la_cong_doan_thanh_pham']) ? intval($input['la_cong_doan_thanh_pham']) : 0;
                response($service->createCongDoan($ma_cong_doan, $ten_cong_doan, $la_cong_doan_thanh_pham));
            }
            
            if ($method === 'PUT' && $id) {
                requireCsrf();
                $ma_cong_doan = $input['ma_cong_doan'] ?? '';
                $ten_cong_doan = $input['ten_cong_doan'] ?? '';
                $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;
                $la_cong_doan_thanh_pham = isset($input['la_cong_doan_thanh_pham']) ? intval($input['la_cong_doan_thanh_pham']) : 0;
                response($service->updateCongDoan($id, $ma_cong_doan, $ten_cong_doan, $is_active, $la_cong_doan_thanh_pham));
            }
            
            if ($method === 'DELETE' && $id) {
                requireCsrf();
                response($service->deleteCongDoan($id));
            }
            break;
            
        case 'routing':
            if ($method === 'GET' && !$id) {
                $ma_hang_id = isset($_GET['ma_hang_id']) ? intval($_GET['ma_hang_id']) : 0;
                response(['success' => true, 'data' => $service->getRoutingList($ma_hang_id)]);
            }
            
            if ($method === 'GET' && $id) {
                $routing = $service->getRouting($id);
                if (!$routing) {
                    response(['success' => false, 'message' => 'Routing không tồn tại'], 404);
                }
                response(['success' => true, 'data' => $routing]);
            }
            
            if ($method === 'POST') {
                requireCsrf();
                $ma_hang_id = intval($input['ma_hang_id'] ?? 0);
                $cong_doan_id = intval($input['cong_doan_id'] ?? 0);
                $thu_tu = intval($input['thu_tu'] ?? 1);
                $bat_buoc = isset($input['bat_buoc']) ? intval($input['bat_buoc']) : 1;
                $la_cong_doan_tinh_luy_ke = isset($input['la_cong_doan_tinh_luy_ke']) ? intval($input['la_cong_doan_tinh_luy_ke']) : 0;
                $line_id = isset($input['line_id']) && $input['line_id'] !== '' ? intval($input['line_id']) : null;
                $ghi_chu = $input['ghi_chu'] ?? '';
                response($service->addRouting($ma_hang_id, $cong_doan_id, $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id, $ghi_chu));
            }
            
            if ($method === 'PUT' && $id) {
                requireCsrf();
                $thu_tu = intval($input['thu_tu'] ?? 1);
                $bat_buoc = isset($input['bat_buoc']) ? intval($input['bat_buoc']) : 1;
                $la_cong_doan_tinh_luy_ke = isset($input['la_cong_doan_tinh_luy_ke']) ? intval($input['la_cong_doan_tinh_luy_ke']) : 0;
                $line_id = isset($input['line_id']) && $input['line_id'] !== '' ? intval($input['line_id']) : null;
                $ghi_chu = $input['ghi_chu'] ?? '';
                response($service->updateRouting($id, $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id, $ghi_chu));
            }
            
            if ($method === 'DELETE' && $id) {
                requireCsrf();
                response($service->removeRouting($id));
            }
            break;
            
        case 'moc-gio':
            $action = $segments[2] ?? '';
            
            if ($method === 'GET' && $action === 'ca-list') {
                response(['success' => true, 'data' => $service->getCaListForMocGio()]);
            }
            
            if ($method === 'GET' && !$id && $action !== 'ca-list') {
                $ca_id = isset($_GET['ca_id']) ? intval($_GET['ca_id']) : null;
                $line_id = $_GET['line_id'] ?? null;
                response(['success' => true, 'data' => $service->getMocGioList($ca_id, $line_id)]);
            }
            
            if ($method === 'GET' && $id) {
                $mocGio = $service->getMocGio($id);
                if (!$mocGio) {
                    response(['success' => false, 'message' => 'Mốc giờ không tồn tại'], 404);
                }
                response(['success' => true, 'data' => $mocGio]);
            }
            
            if ($method === 'POST' && $action === 'copy-default') {
                requireCsrf();
                $ca_id = intval($input['ca_id'] ?? 0);
                $line_id = intval($input['line_id'] ?? 0);
                response($service->copyMocGioDefaultToLine($ca_id, $line_id));
            }
            
            if ($method === 'POST' && !$action) {
                requireCsrf();
                $ca_id = intval($input['ca_id'] ?? 0);
                $line_id = isset($input['line_id']) && $input['line_id'] !== '' ? intval($input['line_id']) : null;
                $gio = $input['gio'] ?? '';
                $thu_tu = intval($input['thu_tu'] ?? 1);
                $so_phut_hieu_dung_luy_ke = intval($input['so_phut_hieu_dung_luy_ke'] ?? 0);
                response($service->createMocGio($ca_id, $line_id, $gio, $thu_tu, $so_phut_hieu_dung_luy_ke));
            }
            
            if ($method === 'PUT' && $id) {
                requireCsrf();
                $gio = $input['gio'] ?? '';
                $thu_tu = intval($input['thu_tu'] ?? 1);
                $so_phut_hieu_dung_luy_ke = intval($input['so_phut_hieu_dung_luy_ke'] ?? 0);
                $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;
                response($service->updateMocGio($id, $gio, $thu_tu, $so_phut_hieu_dung_luy_ke, $is_active));
            }
            
            if ($method === 'DELETE' && $id) {
                requireCsrf();
                response($service->deleteMocGio($id));
            }
            break;
            
        case 'bao-cao':
            $action = $segments[2] ?? '';
            
            if ($method === 'POST' && $action === 'bulk-create') {
                requireCsrf();
                $nangSuatService = new NangSuatService();
                $session = Auth::getSession();
                $items = $input['items'] ?? [];
                $skipExisting = isset($input['skip_existing']) ? (bool)$input['skip_existing'] : true;
                
                if (empty($items)) {
                    response(['success' => false, 'message' => 'Danh sách items không được rỗng'], 400);
                }
                
                foreach ($items as $index => $item) {
                    if (empty($item['line_id']) || empty($item['ma_hang_id']) || empty($item['ngay']) || empty($item['ca_id'])) {
                        response(['success' => false, 'message' => "Item $index thiếu thông tin bắt buộc (line_id, ma_hang_id, ngay, ca_id)"], 400);
                    }
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['ngay'])) {
                        response(['success' => false, 'message' => "Item $index có định dạng ngày không hợp lệ (yêu cầu YYYY-MM-DD)"], 400);
                    }
                }
                
                $result = $nangSuatService->bulkCreateBaoCao($items, $session['ma_nv'], $skipExisting);
                response($result);
            }
            break;
            
        default:
            response(['success' => false, 'message' => 'Resource không hợp lệ'], 404);
    }
    
    response(['success' => false, 'message' => 'Endpoint không hợp lệ'], 404);
}

function handleUserPermissions($segments, $method, $input) {
    requireRole(['admin']);
    
    $mysqli = Database::getMysqli();
    
    // Bulk fetch permissions for multiple users
    if ($method === 'POST' && ($segments[1] ?? '') === 'bulk') {
        $userIds = $input['userIds'] ?? [];
        
        if (!is_array($userIds)) {
            response(['success' => false, 'error' => 'userIds must be an array'], 400);
        }
        
        $permissions = Auth::getAllUsersPermissions($userIds);
        response(['success' => true, 'data' => $permissions]);
    }
    
    $userId = isset($segments[1]) && is_numeric($segments[1]) ? intval($segments[1]) : null;
    $permissionKey = $segments[2] ?? null;
    
    if ($method === 'GET' && $userId) {
        $permissions = Auth::getUserPermissions($userId);
        response(['success' => true, 'data' => ['nguoi_dung_id' => $userId, 'permissions' => $permissions]]);
    }
    
    if ($method === 'POST') {
        requireCsrf();
        
        $userId = intval($input['nguoi_dung_id'] ?? 0);
        $permissionKey = $input['quyen'] ?? '';
        
        if ($userId <= 0 || empty($permissionKey)) {
            response(['success' => false, 'message' => 'Thiếu thông tin nguoi_dung_id hoặc quyen'], 400);
        }
        
        $stmt = mysqli_prepare($mysqli, "SELECT id FROM user WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (!mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            response(['success' => false, 'message' => 'User không tồn tại'], 404);
        }
        mysqli_stmt_close($stmt);
        
        $stmt = mysqli_prepare($mysqli, "SELECT id FROM user_permissions WHERE nguoi_dung_id = ? AND quyen = ?");
        mysqli_stmt_bind_param($stmt, "is", $userId, $permissionKey);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            response(['success' => true, 'message' => 'Permission đã tồn tại']);
        }
        mysqli_stmt_close($stmt);
        
        $nguoiTao = Auth::getSession()['ma_nv'];
        $stmt = mysqli_prepare($mysqli, "INSERT INTO user_permissions (nguoi_dung_id, quyen, nguoi_tao) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iss", $userId, $permissionKey, $nguoiTao);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            response(['success' => true, 'message' => 'Đã cấp quyền thành công']);
        } else {
            mysqli_stmt_close($stmt);
            response(['success' => false, 'message' => 'Không thể cấp quyền'], 500);
        }
    }
    
    if ($method === 'DELETE' && $userId && $permissionKey) {
        requireCsrf();
        
        $stmt = mysqli_prepare($mysqli, "DELETE FROM user_permissions WHERE nguoi_dung_id = ? AND quyen = ?");
        mysqli_stmt_bind_param($stmt, "is", $userId, $permissionKey);
        
        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected > 0) {
                response(['success' => true, 'message' => 'Đã thu hồi quyền thành công']);
            } else {
                response(['success' => true, 'message' => 'Permission không tồn tại']);
            }
        } else {
            mysqli_stmt_close($stmt);
            response(['success' => false, 'message' => 'Không thể thu hồi quyền'], 500);
        }
    }
    
    response(['success' => false, 'message' => 'Endpoint không hợp lệ'], 404);
}

