<?php
header('Content-Type: application/json; charset=utf-8');

$allowedOrigins = [
    'http://localhost',
    'http://127.0.0.1',
    'https://localhost',
    'https://127.0.0.1'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins) || strpos($origin, 'http://localhost:') === 0 || strpos($origin, 'http://127.0.0.1:') === 0) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/NangSuatService.php';
require_once __DIR__ . '/../classes/AdminService.php';
require_once __DIR__ . '/../classes/services/MocGioSetService.php';
require_once __DIR__ . '/../classes/services/HistoryService.php';
require_once __DIR__ . '/../classes/services/ImportService.php';
require_once __DIR__ . '/../csrf.php';

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

function checkRateLimit($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    $maxAttempts = 5;
    $lockoutTime = 900;
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    
    if (time() - $data['first_attempt'] > $lockoutTime) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
        return true;
    }
    
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    return true;
}

function incrementRateLimit($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    $_SESSION[$key]['attempts']++;
}

function resetRateLimit($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    unset($_SESSION[$key]);
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
    return validateCsrfToken($token);
}

function requireCsrf() {
    if (!validateCsrf()) {
        response(['success' => false, 'message' => 'CSRF token không hợp lệ', 'csrf_error' => true], 403);
    }
}

try {
    switch ($segments[0]) {
        case 'csrf-token':
            if ($method === 'GET') {
                response(['success' => true, 'token' => getCsrfToken()]);
            }
            response(['success' => false, 'message' => 'Method not allowed'], 405);
            break;
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
        case 'import':
            handleImport($segments, $method, $input);
            break;
        case 'import-history':
            requireLogin();
            requireRole(['admin']);
            handleImportHistory($method, array_slice($segments, 1), $input);
            break;
        case 'moc-gio-sets':
            handleMocGioSets($segments, $method, $input);
            break;
        case 'bao-cao-history':
            handleBaoCaoHistory($segments, $method);
            break;
        case 'user-permissions':
            handleUserPermissions($segments, $method, $input);
            break;
        case 'bieu-do':
            handleBieuDo($segments, $method);
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
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!checkRateLimit($clientIp)) {
                response(['success' => false, 'message' => 'Quá nhiều lần thử. Vui lòng đợi 15 phút'], 429);
            }
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            if (empty($username) || empty($password)) {
                response(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
            }
            $result = Auth::login($username, $password);
            if ($result['success']) {
                resetRateLimit($clientIp);
                if (isset($result['no_line']) && $result['no_line']) {
                    $result['redirect_url'] = 'no-line.php';
                } elseif (!isset($result['need_select_line']) || !$result['need_select_line']) {
                    $result['redirect_url'] = Auth::getDefaultPage();
                }
            } else {
                incrementRateLimit($clientIp);
            }
            response($result);
            break;
            
        case 'select-line':
            if ($method !== 'POST') {
                response(['success' => false, 'message' => 'Method not allowed'], 405);
            }
            requireLogin();
            requireCsrf();
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
            $sessionData = Auth::getSession();
            $sessionData['can_view_history'] = Auth::canViewHistory();
            response(['success' => true, 'data' => $sessionData]);
            break;
            
        default:
            response(['success' => false, 'message' => 'Action không hợp lệ'], 404);
    }
}

function handleContext($segments, $method) {
    if ($method !== 'GET') {
        response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $isAdmin = Auth::checkRole(['admin']);
    $canCreateAnyLine = Auth::canCreateReportForAnyLine();
    if ($isAdmin || $canCreateAnyLine) {
        requireLogin();
    } else {
        requireLine();
    }
    
    $session = Auth::getSession();
    $service = new NangSuatService();
    
    $ca_id = isset($_GET['ca_id']) ? intval($_GET['ca_id']) : null;
    
    if ($session['line_id']) {
        $context = $service->getContext($session['line_id'], $ca_id);
    } else {
        $context = [];
    }
    
    $context['session'] = $session;
    $context['can_view_history'] = Auth::canViewHistory();
    $context['can_create_report'] = Auth::canCreateReport();
    $context['can_create_report_any_line'] = $canCreateAnyLine;
    
    response(['success' => true, 'data' => $context]);
}

function handleBaoCao($segments, $method, $input) {
    $isAdmin = Auth::checkRole(['admin']);
    $canCreateAnyLine = Auth::canCreateReportForAnyLine();
    if ($isAdmin || $canCreateAnyLine) {
        requireLogin();
    } else {
        requireLine();
    }
    $session = Auth::getSession();
    $service = new NangSuatService();
    
    $baoCaoId = isset($segments[1]) && is_numeric($segments[1]) ? intval($segments[1]) : null;
    $action = $baoCaoId ? ($segments[2] ?? '') : ($segments[1] ?? '');
    
    if ($method === 'GET' && !$baoCaoId) {
        $filters = [
            'ngay_tu' => $_GET['ngay_tu'] ?? null,
            'ngay_den' => $_GET['ngay_den'] ?? null,
            'ca_id' => isset($_GET['ca_id']) ? intval($_GET['ca_id']) : null,
            'ma_hang_id' => isset($_GET['ma_hang_id']) ? intval($_GET['ma_hang_id']) : null,
            'include_completed' => isset($_GET['include_completed']) && $_GET['include_completed'] === '1'
        ];
        $list = $service->getBaoCaoList($session['line_id'], $filters);
        response(['success' => true, 'data' => $list]);
    }
    
    if ($method === 'POST' && !$baoCaoId) {
        requireCsrf();
        if (!Auth::canCreateReport()) {
            response(['success' => false, 'message' => 'Không có quyền tạo báo cáo'], 403);
        }
        if ($canCreateAnyLine && isset($input['line_id']) && intval($input['line_id']) > 0) {
            $input['line_id'] = intval($input['line_id']);
        } else {
            $input['line_id'] = $session['line_id'];
        }
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
        requireCsrf();
        $entries = $input['entries'] ?? [];
        $version = $input['version'] ?? 1;
        $result = $service->updateEntries($baoCaoId, $entries, $version, $session['ma_nv']);
        response($result);
    }
    
    if ($method === 'PUT' && $baoCaoId && $action === 'header') {
        requireRole(['to_truong', 'quan_doc', 'admin']);
        requireCsrf();
        $version = $input['version'] ?? 1;
        $result = $service->updateHeader($baoCaoId, $input, $version);
        response($result);
    }
    
    if ($method === 'POST' && $baoCaoId && $action === 'submit') {
        requireRole(['to_truong', 'quan_doc', 'admin']);
        requireCsrf();
        response($service->submitBaoCao($baoCaoId, $session['ma_nv']));
    }
    
    if ($method === 'POST' && $baoCaoId && $action === 'approve') {
        requireRole(['quan_doc', 'admin']);
        requireCsrf();
        response($service->approveBaoCao($baoCaoId, $session['ma_nv']));
    }
    
    if ($method === 'POST' && $baoCaoId && $action === 'unlock') {
        requireRole(['admin']);
        requireCsrf();
        response($service->unlockBaoCao($baoCaoId, $session['ma_nv']));
    }
    
    if ($method === 'POST' && $baoCaoId && $action === 'complete') {
        requireRole(['to_truong', 'quan_doc', 'admin']);
        requireCsrf();
        response($service->completeBaoCao($baoCaoId, $session['ma_nv']));
    }
    
    if ($method === 'POST' && $baoCaoId && $action === 'reopen') {
        requireRole(['admin']);
        requireCsrf();
        response($service->reopenBaoCao($baoCaoId, $session['ma_nv']));
    }
    
    if ($method === 'DELETE' && $baoCaoId && !$action) {
        requireRole(['admin']);
        requireCsrf();
        response($service->deleteBaoCao($baoCaoId));
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

function handleMocGioSets($segments, $method, $input) {
    requireRole(['admin']);
    $service = new MocGioSetService();
    
    $id = isset($segments[1]) && is_numeric($segments[1]) ? intval($segments[1]) : null;
    $action = $id ? ($segments[2] ?? '') : ($segments[1] ?? '');
    
    if ($method === 'GET' && !$id && !$action) {
        $ca_id = isset($_GET['ca_id']) ? intval($_GET['ca_id']) : null;
        response(['success' => true, 'data' => $service->getList($ca_id)]);
    }
    
    if ($method === 'GET' && $id && !$action) {
        $set = $service->get($id);
        if (!$set) {
            response(['success' => false, 'message' => 'Preset không tồn tại'], 404);
        }
        response(['success' => true, 'data' => $set]);
    }
    
    if ($method === 'POST' && !$id && !$action) {
        requireCsrf();
        $ca_id = intval($input['ca_id'] ?? 0);
        $ten_set = $input['ten_set'] ?? '';
        $is_default = isset($input['is_default']) ? intval($input['is_default']) : 0;
        response($service->create($ca_id, $ten_set, $is_default));
    }
    
    if ($method === 'POST' && !$id && $action === 'copy') {
        requireCsrf();
        $source_set_id = intval($input['source_set_id'] ?? 0);
        $new_ten_set = $input['ten_set'] ?? '';
        response($service->copyPreset($source_set_id, $new_ten_set));
    }
    
    if ($method === 'PUT' && $id && !$action) {
        requireCsrf();
        $ten_set = $input['ten_set'] ?? '';
        $is_default = isset($input['is_default']) ? intval($input['is_default']) : null;
        $is_active = isset($input['is_active']) ? intval($input['is_active']) : null;
        response($service->update($id, $ten_set, $is_default, $is_active));
    }
    
    if ($method === 'DELETE' && $id && !$action) {
        requireCsrf();
        response($service->delete($id));
    }
    
    if ($method === 'GET' && $id && $action === 'lines') {
        response(['success' => true, 'data' => $service->getLines($id)]);
    }
    
    if ($method === 'POST' && $id && $action === 'lines') {
        requireCsrf();
        $line_ids = $input['line_ids'] ?? [];
        response($service->assignLines($id, $line_ids));
    }
    
    if ($method === 'DELETE' && $id && $action === 'lines') {
        requireCsrf();
        $line_ids = $input['line_ids'] ?? [];
        response($service->unassignLines($id, $line_ids));
    }
    
    if ($method === 'GET' && $action === 'unassigned-lines') {
        $ca_id = isset($_GET['ca_id']) ? intval($_GET['ca_id']) : 0;
        response(['success' => true, 'data' => $service->getUnassignedLines($ca_id)]);
    }
    
    if ($method === 'GET' && $action === 'resolve') {
        $ca_id = isset($_GET['ca_id']) ? intval($_GET['ca_id']) : 0;
        $line_id = isset($_GET['line_id']) ? intval($_GET['line_id']) : 0;
        response(['success' => true, 'data' => $service->getMocGioForLine($ca_id, $line_id)]);
    }
    
    response(['success' => false, 'message' => 'Endpoint không hợp lệ'], 404);
}

function handleBaoCaoHistory($segments, $method) {
    requireLogin();
    
    $session = Auth::getSession();
    $isAdmin = Auth::checkRole(['admin']);
    
    if (!$isAdmin && !Auth::canViewHistory()) {
        response(['success' => false, 'message' => 'Không có quyền xem lịch sử'], 403);
    }
    
    $historyService = new HistoryService();
    
    $reportId = isset($segments[1]) && is_numeric($segments[1]) ? intval($segments[1]) : null;
    
    if ($method === 'GET' && !$reportId) {
        $filters = [
            'ngay_tu' => $_GET['ngay_tu'] ?? null,
            'ngay_den' => $_GET['ngay_den'] ?? null,
            'ca_id' => isset($_GET['ca_id']) ? intval($_GET['ca_id']) : null,
            'ma_hang_id' => isset($_GET['ma_hang_id']) ? intval($_GET['ma_hang_id']) : null,
            'trang_thai' => $_GET['trang_thai'] ?? null,
            'page' => isset($_GET['page']) ? intval($_GET['page']) : 1,
            'page_size' => isset($_GET['page_size']) ? intval($_GET['page_size']) : 20
        ];
        
        if ($isAdmin) {
            $filters['line_id'] = isset($_GET['line_id']) ? intval($_GET['line_id']) : null;
        } else {
            $filters['line_id'] = $session['line_id'];
        }
        
        $result = $historyService->getReportList($filters);
        response(['success' => true, 'data' => $result['data'], 'pagination' => $result['pagination']]);
    }
    
    if ($method === 'GET' && $reportId) {
        $reportLineId = $historyService->getReportLineId($reportId);
        
        if ($reportLineId === null) {
            response(['success' => false, 'message' => 'Báo cáo không tồn tại'], 404);
        }
        
        if (!$isAdmin && $reportLineId !== $session['line_id']) {
            response(['success' => false, 'message' => 'Không có quyền xem báo cáo này'], 403);
        }
        
        $detail = $historyService->getReportDetail($reportId);
        if (!$detail) {
            response(['success' => false, 'message' => 'Báo cáo không tồn tại'], 404);
        }
        
        response(['success' => true, 'data' => $detail]);
    }
    
    response(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handleImport($segments, $method, $input) {
    requireLogin();
    
    if (!Auth::canImport()) {
        response(['success' => false, 'message' => 'Không có quyền import mã hàng và công đoạn'], 403);
    }
    
    $action = $segments[1] ?? '';
    
    if ($method === 'POST' && $action === 'preview') {
        requireCsrf();
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File vượt quá kích thước cho phép',
                UPLOAD_ERR_FORM_SIZE => 'File vượt quá kích thước cho phép',
                UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
                UPLOAD_ERR_NO_FILE => 'Không có file được upload',
                UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
                UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file',
                UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension'
            ];
            $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
            $message = $errorMessages[$errorCode] ?? 'Lỗi upload file';
            response(['success' => false, 'message' => $message, 'error_code' => 'NO_FILE_UPLOADED'], 400);
        }
        
        $file = $_FILES['file'];
        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel'
        ];
        $allowedExts = ['xlsx', 'xls'];
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowedMimes)) {
            @unlink($file['tmp_name']);
            response(['success' => false, 'message' => 'File không đúng định dạng Excel', 'error_code' => 'INVALID_MIME_TYPE'], 400);
        }
        
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            @unlink($file['tmp_name']);
            response(['success' => false, 'message' => 'File vượt quá 10MB', 'error_code' => 'FILE_TOO_LARGE'], 400);
        }
        
        $importService = new ImportService();
        try {
            $result = $importService->preview($file['tmp_name']);
        } finally {
            @unlink($file['tmp_name']);
        }
        response($result);
    }
    
    if ($method === 'POST' && $action === 'confirm') {
        requireCsrf();
        
        $maHangList = $input['ma_hang_list'] ?? [];
        $fileName = $input['file_name'] ?? '';
        $fileSize = intval($input['file_size'] ?? 0);
        $previewStats = $input['preview_stats'] ?? [];
        $previewErrors = $input['preview_errors'] ?? [];
        
        if (empty($maHangList)) {
            response(['success' => false, 'message' => 'Danh sách mã hàng không được rỗng', 'error_code' => 'VALIDATION_FAILED'], 400);
        }
        
        $importService = new ImportService();
        
        // Set preview data for history
        $importService->setPreviewData([
            'stats' => $previewStats,
            'errors' => $previewErrors,
            'data' => $maHangList
        ]);
        
        // Get current user
        $session = Auth::getSession();
        $importedBy = $session['ma_nv'] ?? '';
        
        $result = $importService->confirm(
            $maHangList, 
            $input['acknowledge_deletion'] ?? false,
            $fileName,
            $fileSize,
            $importedBy
        );
        response($result);
    }
    
    response(['success' => false, 'message' => 'Endpoint không hợp lệ'], 404);
}

function handleImportHistory($method, $segments, $input) {
    require_once __DIR__ . '/../classes/services/ImportService.php';
    
    $importService = new ImportService();
    $id = isset($segments[0]) && is_numeric($segments[0]) ? intval($segments[0]) : null;
    
    if ($method === 'GET') {
        if ($id !== null) {
            // GET /import-history/{id} - Get detail
            $result = $importService->getImportHistoryDetail($id);
            if ($result === null) {
                response(['success' => false, 'message' => 'Không tìm thấy lịch sử import'], 404);
            }
            response(['success' => true, 'data' => $result]);
        } else {
            // GET /import-history - List with pagination
            $page = intval($_GET['page'] ?? 1);
            $pageSize = intval($_GET['page_size'] ?? 20);
            
            $filters = [];
            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }
            if (!empty($_GET['import_boi'])) {
                $filters['import_boi'] = $_GET['import_boi'];
            }
            if (!empty($_GET['trang_thai'])) {
                $filters['trang_thai'] = $_GET['trang_thai'];
            }
            
            $result = $importService->getImportHistoryList($page, $pageSize, $filters);
            response(['success' => true, 'data' => $result['data'], 'pagination' => $result['pagination']]);
        }
    }
    
    response(['success' => false, 'message' => 'Method not allowed'], 405);
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
        
        $nguoiTao = $_SESSION['ma_nv'] ?? null;
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

function handleBieuDo($segments, $method) {
    requireLogin();
    
    $action = $segments[1] ?? '';
    $session = Auth::getSession();
    $isAdmin = Auth::checkRole(['admin']);
    
    require_once __DIR__ . '/../classes/services/ChartService.php';
    $service = new ChartService();
    
    // GET /bieu-do/so-sanh - Get productivity comparison chart data
    if ($method === 'GET' && $action === 'so-sanh') {
        $line_id = isset($_GET['line_id']) ? intval($_GET['line_id']) : 0;
        $ma_hang_id = isset($_GET['ma_hang_id']) ? intval($_GET['ma_hang_id']) : 0;
        $ngay = $_GET['ngay'] ?? date('Y-m-d');
        $ca_id = isset($_GET['ca_id']) ? intval($_GET['ca_id']) : 0;
        
        // Validate required parameters
        if (!$line_id || !$ma_hang_id || !$ca_id) {
            response(['success' => false, 'message' => 'Thiếu tham số bắt buộc (line_id, ma_hang_id, ca_id)'], 400);
        }
        
        // Validate LINE access for non-admin
        if (!$isAdmin) {
            if ($session['line_id'] != $line_id) {
                response(['success' => false, 'message' => 'Không có quyền xem LINE này'], 403);
            }
        }
        
        $result = $service->getProductivityComparison($line_id, $ma_hang_id, $ngay, $ca_id);
        response($result);
    }
    
    // GET /bieu-do/ma-hang-list - Get list of ma_hang with reports for a LINE and date
    if ($method === 'GET' && $action === 'ma-hang-list') {
        $line_id = isset($_GET['line_id']) ? intval($_GET['line_id']) : 0;
        $ngay = $_GET['ngay'] ?? date('Y-m-d');
        
        if (!$line_id) {
            response(['success' => false, 'message' => 'Thiếu tham số line_id'], 400);
        }
        
        // Validate LINE access for non-admin
        if (!$isAdmin) {
            if ($session['line_id'] != $line_id) {
                response(['success' => false, 'message' => 'Không có quyền xem LINE này'], 403);
            }
        }
        
        $result = $service->getMaHangListForChart($line_id, $ngay);
        response(['success' => true, 'data' => $result]);
    }
    
    // GET /bieu-do/lines - Get list of active lines (for admin)
    if ($method === 'GET' && $action === 'lines') {
        if (!$isAdmin) {
            response(['success' => false, 'message' => 'Không có quyền'], 403);
        }
        $result = $service->getLineList();
        response(['success' => true, 'data' => $result]);
    }
    
    // GET /bieu-do/ca-list - Get list of shifts
    if ($method === 'GET' && $action === 'ca-list') {
        $result = $service->getCaList();
        response(['success' => true, 'data' => $result]);
    }
    
    // GET /bieu-do/so-sanh-chi-tiet - Get detailed per-công đoạn comparison
    if ($method === 'GET' && $action === 'so-sanh-chi-tiet') {
        $line_id = isset($_GET['line_id']) ? intval($_GET['line_id']) : null;
        $ma_hang_id = isset($_GET['ma_hang_id']) ? intval($_GET['ma_hang_id']) : null;
        $ngay = $_GET['ngay'] ?? date('Y-m-d');
        $ca_id = isset($_GET['ca_id']) ? intval($_GET['ca_id']) : null;
        
        // Validate required parameters
        if (!$line_id || !$ma_hang_id || !$ca_id) {
            response(['success' => false, 'message' => 'Thiếu tham số bắt buộc (line_id, ma_hang_id, ca_id)'], 400);
        }
        
        // Validate LINE access for non-admin
        if (!$isAdmin) {
            if ($session['line_id'] != $line_id) {
                response(['success' => false, 'message' => 'Không có quyền xem LINE này'], 403);
            }
        }
        
        $result = $service->getProductivityComparisonDetailed($line_id, $ma_hang_id, $ngay, $ca_id);
        response($result);
    }
    
    // GET /bieu-do/so-sanh-matrix - Get matrix view (công đoạn × mốc giờ) with heatmap
    if ($method === 'GET' && $action === 'so-sanh-matrix') {
        $line_id = isset($_GET['line_id']) ? intval($_GET['line_id']) : null;
        $ma_hang_id = isset($_GET['ma_hang_id']) ? intval($_GET['ma_hang_id']) : null;
        $ngay = $_GET['ngay'] ?? date('Y-m-d');
        $ca_id = isset($_GET['ca_id']) ? intval($_GET['ca_id']) : null;
        
        // Validate required parameters
        if (!$line_id || !$ma_hang_id || !$ca_id) {
            response(['success' => false, 'message' => 'Thiếu tham số bắt buộc (line_id, ma_hang_id, ca_id)'], 400);
        }
        
        // Validate LINE access for non-admin
        if (!$isAdmin) {
            if ($session['line_id'] != $line_id) {
                response(['success' => false, 'message' => 'Không có quyền xem LINE này'], 403);
            }
        }
        
        $result = $service->getProductivityComparisonMatrix($line_id, $ma_hang_id, $ngay, $ca_id);
        response($result);
    }
    
    response(['success' => false, 'message' => 'Endpoint không hợp lệ'], 404);
}
