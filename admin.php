<?php
require_once __DIR__ . '/includes/security-headers.php';
require_once __DIR__ . '/classes/Auth.php';

if (!Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if (!Auth::checkRole(['admin'])) {
    header('Location: ' . Auth::getDefaultPage());
    exit;
}

$session = Auth::getSession();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Admin - Hệ thống Năng suất</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: '#143583',
              'primary-dark': '#0f2a66',
              success: '#4CAF50',
              warning: '#ff9800',
              danger: '#f44336',
            }
          }
        }
      }
    </script>
</head>
<body>
    <?php
    $navTitle = 'QUẢN LÝ HỆ THỐNG NĂNG SUẤT';
    $showAddBtn = false;
    $showHomeBtn = true;
    include __DIR__ . '/includes/navbar.php';
    ?>
    <div class="app-container">
        <div class="user-info-bar">
            <span class="user-name"><?php echo htmlspecialchars($session['ho_ten']); ?> (Admin)</span>
            <span class="separator">|</span>
            <a href="nhap-nang-suat.php">Về trang chính</a>
            <span class="separator">|</span>
            <a href="#" id="logoutBtn">Đăng xuất</a>
        </div>
        
        <div class="admin-content">
            <div class="admin-tabs">
                <button class="admin-tab active" data-tab="lines">Quản lý LINE</button>
                <button class="admin-tab" data-tab="user-lines">Quản lý User-LINE</button>
                <button class="admin-tab" data-tab="ma-hang">Quản lý Mã hàng</button>
                <button class="admin-tab" data-tab="cong-doan">Quản lý Công đoạn</button>
                <button class="admin-tab" data-tab="routing">Quản lý Routing</button>
            </div>
            
            <div id="linesTab" class="admin-tab-content active">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h2>Danh sách LINE</h2>
                        <button id="addLineBtn" class="btn btn-primary">+ Thêm LINE</button>
                    </div>
                    <table class="admin-table" id="linesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mã LINE</th>
                                <th>Tên LINE</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            
            <div id="user-linesTab" class="admin-tab-content">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h2>Danh sách User-LINE Mapping</h2>
                        <button id="addUserLineBtn" class="btn btn-primary">+ Thêm Mapping</button>
                    </div>
                    <div class="form-group filter-group">
                        <label for="userLineFilterLine">Lọc theo LINE:</label>
                        <select id="userLineFilterLine" class="custom-select form-control filter-select-line">
                            <option value="">-- Tất cả LINE --</option>
                        </select>
                    </div>
                    <table class="admin-table" id="userLinesTable">
                        <thead>
                            <tr>
                                <th>Mã NV</th>
                                <th>Họ tên</th>
                                <th>Mã LINE</th>
                                <th>Tên LINE</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            
            <div id="ma-hangTab" class="admin-tab-content">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h2>Danh sách Mã hàng</h2>
                        <button id="addMaHangBtn" class="btn btn-primary">+ Thêm Mã hàng</button>
                    </div>
                    <table class="admin-table" id="maHangTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mã hàng</th>
                                <th>Tên hàng</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            
            <div id="cong-doanTab" class="admin-tab-content">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h2>Danh sách Công đoạn</h2>
                        <button id="addCongDoanBtn" class="btn btn-primary">+ Thêm Công đoạn</button>
                    </div>
                    <table class="admin-table" id="congDoanTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mã CĐ</th>
                                <th>Tên công đoạn</th>
                                <th>Thành phẩm</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            
            <div id="routingTab" class="admin-tab-content">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h2>Quản lý Routing</h2>
                    </div>
                    <div class="form-group filter-group">
                        <label for="routingMaHangSelect">Chọn Mã hàng:</label>
                        <select id="routingMaHangSelect" class="custom-select form-control filter-select-routing">
                            <option value="">-- Chọn mã hàng --</option>
                        </select>
                    </div>
                    <div id="routingTableContainer" style="display: none;">
                        <div class="panel-header">
                            <h3 id="routingTitle">Routing cho: </h3>
                            <button id="addRoutingBtn" class="btn btn-primary">+ Thêm Công đoạn</button>
                        </div>
                        <table class="admin-table" id="routingTable">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Công đoạn</th>
                                    <th>LINE</th>
                                    <th>Tính lũy kế</th>
                                    <th>Bắt buộc</th>
                                    <th>Ghi chú</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Line Modal -->
    <div id="lineModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('lineModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-md p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 id="lineModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm LINE mới</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('lineModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="lineForm" class="p-6 space-y-4">
                <input type="hidden" id="lineId" name="id">
                <div class="form-group space-y-1">
                    <label for="maLine" class="block text-sm font-medium text-gray-700">Mã LINE</label>
                    <input type="text" id="maLine" name="ma_line" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="form-group space-y-1">
                    <label for="tenLine" class="block text-sm font-medium text-gray-700">Tên LINE</label>
                    <input type="text" id="tenLine" name="ten_line" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="form-group" id="isActiveGroup" style="display:none">
                    <div class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="isActive" name="is_active" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="isActive" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Đang hoạt động</label>
                    </div>
                </div>
                <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('lineModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- User Line Modal -->
    <div id="userLineModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('userLineModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-md p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800 m-0">Thêm User-LINE Mapping</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('userLineModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="userLineForm" class="p-6 space-y-4">
                <div class="form-group space-y-1">
                    <label for="userSelect" class="block text-sm font-medium text-gray-700">Chọn User</label>
                    <select id="userSelect" name="ma_nv" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none bg-white">
                        <option value="">-- Chọn User --</option>
                    </select>
                    <input type="text" id="userSearchInput" placeholder="Tìm kiếm user..." class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none text-sm">
                </div>
                <div class="form-group space-y-1">
                    <label for="lineSelect" class="block text-sm font-medium text-gray-700">Chọn LINE</label>
                    <select id="lineSelect" name="line_id" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none bg-white">
                        <option value="">-- Chọn LINE --</option>
                    </select>
                </div>
                <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('userLineModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Thêm</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Confirm Modal -->
    <div id="confirmModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('confirmModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-sm p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800 m-0">Xác nhận</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('confirmModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6">
                <p id="confirmMessage" class="text-gray-600 text-base leading-relaxed"></p>
                <div class="modal-actions flex justify-end gap-3 pt-6 mt-2">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('confirmModal')">Hủy</button>
                    <button type="button" id="confirmBtn" class="btn btn-danger px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white shadow-md hover:shadow-lg transition-all font-medium">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ma Hang Modal -->
    <div id="maHangModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('maHangModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-md p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 id="maHangModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm Mã hàng mới</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('maHangModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="maHangForm" class="p-6 space-y-4">
                <input type="hidden" id="maHangId" name="id">
                <div class="form-group space-y-1">
                    <label for="maHangCode" class="block text-sm font-medium text-gray-700">Mã hàng</label>
                    <input type="text" id="maHangCode" name="ma_hang" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="form-group space-y-1">
                    <label for="tenHang" class="block text-sm font-medium text-gray-700">Tên hàng</label>
                    <input type="text" id="tenHang" name="ten_hang" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="form-group" id="maHangIsActiveGroup" style="display:none">
                    <div class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="maHangIsActive" name="is_active" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="maHangIsActive" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Đang hoạt động</label>
                    </div>
                </div>
                <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('maHangModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Cong Doan Modal -->
    <div id="congDoanModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('congDoanModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-md p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 id="congDoanModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm Công đoạn mới</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('congDoanModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="congDoanForm" class="p-6 space-y-4">
                <input type="hidden" id="congDoanId" name="id">
                <div class="form-group space-y-1">
                    <label for="maCongDoan" class="block text-sm font-medium text-gray-700">Mã công đoạn</label>
                    <input type="text" id="maCongDoan" name="ma_cong_doan" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="form-group space-y-1">
                    <label for="tenCongDoan" class="block text-sm font-medium text-gray-700">Tên công đoạn</label>
                    <input type="text" id="tenCongDoan" name="ten_cong_doan" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="form-group">
                    <div class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="laCongDoanThanhPham" name="la_cong_doan_thanh_pham" class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="laCongDoanThanhPham" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Là công đoạn thành phẩm</label>
                    </div>
                </div>
                <div class="form-group" id="congDoanIsActiveGroup" style="display:none">
                    <div class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="congDoanIsActive" name="is_active" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="congDoanIsActive" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Đang hoạt động</label>
                    </div>
                </div>
                <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('congDoanModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Routing Modal -->
    <div id="routingModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('routingModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 id="routingModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm Công đoạn vào Routing</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('routingModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="routingForm" class="p-6 space-y-4">
                <input type="hidden" id="routingId" name="id">
                <input type="hidden" id="routingMaHangId" name="ma_hang_id">
                <div class="form-group space-y-1" id="routingCongDoanGroup">
                    <label for="routingCongDoanSelect" class="block text-sm font-medium text-gray-700">Công đoạn</label>
                    <select id="routingCongDoanSelect" name="cong_doan_id" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none bg-white">
                        <option value="">-- Chọn công đoạn --</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group space-y-1">
                        <label for="routingThuTu" class="block text-sm font-medium text-gray-700">Thứ tự</label>
                        <input type="number" id="routingThuTu" name="thu_tu" value="1" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                    </div>
                    <div class="form-group space-y-1">
                        <label for="routingLineSelect" class="block text-sm font-medium text-gray-700">LINE (tùy chọn)</label>
                        <select id="routingLineSelect" name="line_id" class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none bg-white">
                            <option value="">-- Tất cả LINE --</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-col space-y-2">
                    <div class="form-group">
                        <div class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="routingBatBuoc" name="bat_buoc" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                            <label for="routingBatBuoc" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Bắt buộc</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="routingTinhLuyKe" name="la_cong_doan_tinh_luy_ke" class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                            <label for="routingTinhLuyKe" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Tính lũy kế</label>
                        </div>
                    </div>
                </div>
                <div class="form-group space-y-1">
                    <label for="routingGhiChu" class="block text-sm font-medium text-gray-700">Ghi chú</label>
                    <input type="text" id="routingGhiChu" name="ghi_chu" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('routingModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>