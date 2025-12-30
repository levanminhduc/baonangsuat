<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/NangSuatService.php';
require_once __DIR__ . '/../classes/AdminService.php';

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

try {
    switch ($segments[0]) {
        case 'auth':
            handleAuth($segments, $method, $input);
            break;
        case 'context':
            handleContext($segments, $method);
            break;
        case 'bao-cao':
            handleBaoCao($segments, $method, $input);
            break;
        case 'danh-muc':
            handleDanhMuc($segments, $method, $input);
            break;
        case 'admin':
            handleAdmin($segments, $method, $input);
            break;
        default:
            response(['success' => false, 'message' => 'API không tồn tại'], 404);
    }
} catch (Exception $e) {
    response(['success' => false, 'message' => $e->getMessage()], 500);
}

function handleAuth($segments, $method, $input) {
    $action = $segments[1] ?? '';
    
    switch ($action) {
        case 'login':
            if ($method !== 'POST') {
                response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            if (empty($username) || empty($password)) {
                response(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
            }
            $result = Auth::login($username, $password);
            if ($result['success']) {
                if (isset($result['no_line']) && $result['no_line']) {
                    $result['redirect_url'] = 'no-line.php';
                } elseif (!isset($result['need_select_line']) || !$result['need_select_line']) {
                    $result['redirect_url'] = Auth::getDefaultPage();
                }
            }
            response($result);
            break;
            
        case 'select-line':
            if ($method !== 'POST') {
                response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            requireLogin();
            $line_id = intval($input['line_id'] ?? 0);
            $result = Auth::selectLine($line_id);
            if ($result['success']) {
                $result['redirect_url'] = Auth::getDefaultPage();
            }
            response($result);
            break;
            
        case 'logout':
            response(Auth::logout());
            break;
            
        case 'session':
            requireLogin();
            response(['success' => true, 'data' => Auth::getSession()]);
            break;
            
        default:
            response(['success' => false, 'message' => 'Action không hợp lệ'], 404);
    }
}

function handleContext($segments, $method) {
    if ($method !== 'GET') {
        response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    requireLine();
    
    $session = Auth::getSession();
    $service = new NangSuatService();
    
    $ca_id = isset($_GET['ca_id']) ? intval($_GET['ca_id']) : null;
    $context = $service->getContext($session['line_id'], $ca_id);
    $context['session'] = $session;
    
    response(['success' => true, 'data' => $context]);
}

function handleBaoCao($segments, $method, $input) {
    requireLine();
    $session = Auth::getSession();
    $service = new NangSuatService();
    
    $baoCaoId = isset($segments[1]) && is_numeric($segments[1]) ? intval($segments[1]) : null;
    $action = $baoCaoId ? ($segments[2] ?? '') : ($segments[1] ?? '');
    
    if ($method === 'GET' && !$baoCaoId) {
        $filters = [
            'ngay_tu' => $_GET['ngay_tu'] ?? null,
            'ngay_den' => $_GET['ngay_den'] ?? null,
            'ca_id' => isset($_GET['ca_id']) ? intval($_GET['ca_id']) : null,
            'ma_hang_id' => isset($_GET['ma_hang_id']) ? intval($_GET['ma_hang_id']) : null
        ];
        $list = $service->getBaoCaoList($session['line_id'], $filters);
        response(['success' => true, 'data' => $list]);
    }
    
    if ($method === 'POST' && !$baoCaoId) {
        $input['line_id'] = $session['line_id'];
        $result = $service->createBaoCao($input, $session['ma_nv']);
        response($result);
    }
    
    if ($method === 'GET' && $baoCaoId && !$action) {
        $baoCao = $service->getBaoCao($baoCaoId);
        if (!$baoCao) {
            response(['success' => false, 'message' => 'Báo cáo không tồn tại'], 404);
        }
        response(['success' => true, 'data' => $baoCao]);
    }
    
    if ($method === 'GET' && $baoCaoId && $action === 'routing') {
        $baoCao = $service->getBaoCao($baoCaoId);
        if (!$baoCao) {
            response(['success' => false, 'message' => 'Báo cáo không tồn tại'], 404);
        }
        response(['success' => true, 'data' => $service->getRouting($baoCao['ma_hang_id'], $baoCao['line_id'])]);
    }
    
    if ($method === 'PUT' && $baoCaoId && $action === 'entries') {
        requireRole(['to_truong', 'quan_doc', 'admin']);
        $entries = $input['entries'] ?? [];
        $version = $input['version'] ?? 1;
        $result = $service->updateEntries($baoCaoId, $entries, $version, $session['ma_nv']);
        response($result);
    }
    
    if ($method === 'PUT' && $baoCaoId && $action === 'header') {
        requireRole(['to_truong', 'quan_doc', 'admin']);
        $version = $input['version'] ?? 1;
        $result = $service->updateHeader($baoCaoId, $input, $version);
        response($result);
    }
    
    if ($method === 'POST' && $baoCaoId && $action === 'submit') {
        requireRole(['to_truong', 'quan_doc', 'admin']);
        response($service->submitBaoCao($baoCaoId, $session['ma_nv']));
    }
    
    if ($method === 'POST' && $baoCaoId && $action === 'approve') {
        requireRole(['quan_doc', 'admin']);
        response($service->approveBaoCao($baoCaoId, $session['ma_nv']));
    }
    
    if ($method === 'POST' && $baoCaoId && $action === 'unlock') {
        requireRole(['admin']);
        response($service->unlockBaoCao($baoCaoId, $session['ma_nv']));
    }
    
    response(['success' => false, 'message' => 'Endpoint không hợp lệ'], 404);
}

function handleDanhMuc($segments, $method, $input) {
    requireLogin();
    $service = new NangSuatService();
    
    $type = $segments[1] ?? '';
    
    switch ($type) {
        case 'ca':
            if ($method === 'GET') {
                response(['success' => true, 'data' => $service->getCaList()]);
            }
            break;
            
        case 'ma-hang':
            if ($method === 'GET') {
                response(['success' => true, 'data' => $service->getMaHangList()]);
            }
            break;
            
        case 'moc-gio':
            if ($method === 'GET') {
                $ca_id = isset($_GET['ca_id']) ? intval($_GET['ca_id']) : 0;
                response(['success' => true, 'data' => $service->getMocGioList($ca_id)]);
            }
            break;
            
        case 'routing':
            if ($method === 'GET') {
                $ma_hang_id = isset($_GET['ma_hang_id']) ? intval($_GET['ma_hang_id']) : 0;
                $line_id = isset($_GET['line_id']) ? intval($_GET['line_id']) : null;
                response(['success' => true, 'data' => $service->getRouting($ma_hang_id, $line_id)]);
            }
            break;
            
        default:
            response(['success' => false, 'message' => 'Danh mục không hợp lệ'], 404);
    }
    
    response(['success' => false, 'message' => 'Method not allowed'], 405);
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
                $ma_line = $input['ma_line'] ?? '';
                $ten_line = $input['ten_line'] ?? '';
                response($service->createLine($ma_line, $ten_line));
            }
            
            if ($method === 'PUT' && $id) {
                $ma_line = $input['ma_line'] ?? '';
                $ten_line = $input['ten_line'] ?? '';
                $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;
                response($service->updateLine($id, $ma_line, $ten_line, $is_active));
            }
            
            if ($method === 'DELETE' && $id) {
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
                $ma_nv = $input['ma_nv'] ?? '';
                $line_id = intval($input['line_id'] ?? 0);
                response($service->addUserLine($ma_nv, $line_id));
            }
            
            if ($method === 'DELETE') {
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
                $ma_hang = $input['ma_hang'] ?? '';
                $ten_hang = $input['ten_hang'] ?? '';
                response($service->createMaHang($ma_hang, $ten_hang));
            }
            
            if ($method === 'PUT' && $id) {
                $ma_hang = $input['ma_hang'] ?? '';
                $ten_hang = $input['ten_hang'] ?? '';
                $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;
                response($service->updateMaHang($id, $ma_hang, $ten_hang, $is_active));
            }
            
            if ($method === 'DELETE' && $id) {
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
                $ma_cong_doan = $input['ma_cong_doan'] ?? '';
                $ten_cong_doan = $input['ten_cong_doan'] ?? '';
                $la_cong_doan_thanh_pham = isset($input['la_cong_doan_thanh_pham']) ? intval($input['la_cong_doan_thanh_pham']) : 0;
                response($service->createCongDoan($ma_cong_doan, $ten_cong_doan, $la_cong_doan_thanh_pham));
            }
            
            if ($method === 'PUT' && $id) {
                $ma_cong_doan = $input['ma_cong_doan'] ?? '';
                $ten_cong_doan = $input['ten_cong_doan'] ?? '';
                $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;
                $la_cong_doan_thanh_pham = isset($input['la_cong_doan_thanh_pham']) ? intval($input['la_cong_doan_thanh_pham']) : 0;
                response($service->updateCongDoan($id, $ma_cong_doan, $ten_cong_doan, $is_active, $la_cong_doan_thanh_pham));
            }
            
            if ($method === 'DELETE' && $id) {
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
                $thu_tu = intval($input['thu_tu'] ?? 1);
                $bat_buoc = isset($input['bat_buoc']) ? intval($input['bat_buoc']) : 1;
                $la_cong_doan_tinh_luy_ke = isset($input['la_cong_doan_tinh_luy_ke']) ? intval($input['la_cong_doan_tinh_luy_ke']) : 0;
                $line_id = isset($input['line_id']) && $input['line_id'] !== '' ? intval($input['line_id']) : null;
                $ghi_chu = $input['ghi_chu'] ?? '';
                response($service->updateRouting($id, $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id, $ghi_chu));
            }
            
            if ($method === 'DELETE' && $id) {
                response($service->removeRouting($id));
            }
            break;
            
        default:
            response(['success' => false, 'message' => 'Resource không hợp lệ'], 404);
    }
    
    response(['success' => false, 'message' => 'Endpoint không hợp lệ'], 404);
}
