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

if (!Auth::canAccessAdminPanel()) {
    header('Location: nhap-nang-suat.php');
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

        
        <div class="admin-content">
            <style>
                .menu-responsive {
                    display: flex;
                    gap: 5px;
                    margin-bottom: 20px;
                    border-bottom: 2px solid var(--border-color);
                    padding-bottom: 0;
                }

                /* Mobile: Horizontal scroll */
                @media (max-width: 767px) {
                    .menu-responsive {
                        overflow-x: auto;
                        white-space: nowrap;
                        flex-wrap: nowrap;
                        -webkit-overflow-scrolling: touch;
                        
                        /* Hide scrollbar */
                        -ms-overflow-style: none;
                        scrollbar-width: none;
                    }
                    .menu-responsive::-webkit-scrollbar {
                        display: none;
                    }
                }

                /* Desktop: Wrap */
                @media (min-width: 768px) {
                    .menu-responsive {
                        flex-wrap: wrap;
                        overflow-x: visible;
                    }
                }
            </style>
            <div class="menu-responsive">
                <button class="admin-tab active" data-tab="lines">Quản lý LINE</button>
                <button class="admin-tab" data-tab="user-lines">Quản lý User-LINE</button>
                <button class="admin-tab" data-tab="permissions">Quản lý Quyền</button>
                <button class="admin-tab" data-tab="bulk-create">Tạo Báo Cáo</button>
                <button class="admin-tab" data-tab="ma-hang">Quản lý Mã hàng</button>
                <button class="admin-tab" data-tab="cong-doan">Quản lý Công đoạn</button>
                <button class="admin-tab" data-tab="routing">Quản lý Routing</button>
                <button class="admin-tab" data-tab="presets">Quản lý Preset Mốc Giờ</button>
                <button class="admin-tab" data-tab="moc-gio">Quản lý Mốc giờ (Cũ)</button>
                <button class="admin-tab" data-tab="import">Import Excel</button>
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

            <div id="permissionsTab" class="admin-tab-content">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h2>Quản lý quyền Users</h2>
                    </div>
                    <div class="form-group filter-group">
                        <input type="text" id="permissionsSearch" placeholder="Tìm kiếm user..." class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                    </div>
                    <table class="admin-table" id="permissionsTable">
                        <thead>
                            <tr>
                                <th>Mã NV</th>
                                <th>Họ tên</th>
                                <th>Vai trò</th>
                                <th>Quyền xem Lịch sử</th>
                                <th>Quyền tạo báo cáo</th>
                                <th>Quyền tạo báo cáo (chọn LINE)</th>
                                <th>Quyền Import MH & CĐ</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            
            <div id="bulk-createTab" class="admin-tab-content">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h2>Tạo Báo Cáo Hàng Loạt</h2>
                    </div>
                    <div class="p-6">
                        <form id="bulkCreateForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="form-group space-y-1">
                                    <label for="bulkDate" class="block text-sm font-medium text-gray-700">Ngày</label>
                                    <input type="date" id="bulkDate" name="ngay" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                                </div>
                                <div class="form-group space-y-1">
                                    <label for="bulkCa" class="block text-sm font-medium text-gray-700">Ca</label>
                                    <select id="bulkCa" name="ca_id" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                                        <option value="">-- Chọn Ca --</option>
                                    </select>
                                </div>
                                <div class="form-group space-y-1">
                                    <label for="bulkMaHang" class="block text-sm font-medium text-gray-700">Mã hàng</label>
                                    <select id="bulkMaHang" name="ma_hang_id" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                                        <option value="">-- Chọn Mã hàng --</option>
                                    </select>
                                </div>
                                <div class="form-group space-y-1">
                                    <label for="bulkCtns" class="block text-sm font-medium text-gray-700">Chỉ tiêu năng suất (CTNS)</label>
                                    <input type="number" id="bulkCtns" name="ctns" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                                </div>
                                <div class="form-group space-y-1">
                                    <label for="bulkSoLaoDong" class="block text-sm font-medium text-gray-700">Số lao động (LĐ)</label>
                                    <input type="number" id="bulkSoLaoDong" name="so_lao_dong" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                                </div>
                            </div>

                            <div class="form-group space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Chọn LINE áp dụng</label>
                                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 max-h-60 overflow-y-auto">
                                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-200">
                                        <input type="checkbox" id="bulkSelectAllLines" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                        <label for="bulkSelectAllLines" class="text-sm font-semibold text-gray-700 cursor-pointer">Chọn tất cả</label>
                                    </div>
                                    <div id="bulkLinesContainer" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <!-- Checkboxes render via JS -->
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="bulkSkipExisting" name="skip_existing" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                                    <label for="bulkSkipExisting" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Bỏ qua nếu đã tồn tại (không báo lỗi)</label>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit" id="btnBulkCreate" class="btn btn-primary px-6 py-2.5 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium flex items-center gap-2">
                                    <span>Tạo báo cáo hàng loạt</span>
                                </button>
                            </div>
                        </form>

                        <!-- Results Area -->
                        <div id="bulkResultArea" class="hidden mt-6 p-4 rounded-lg border">
                            <h3 class="text-lg font-semibold mb-2" id="bulkResultTitle">Kết quả</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-green-50 p-3 rounded border border-green-200">
                                    <span class="text-green-700 font-medium">Đã tạo thành công:</span>
                                    <span id="bulkCountCreated" class="font-bold text-green-800 text-lg ml-1">0</span>
                                </div>
                                <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                                    <span class="text-yellow-700 font-medium">Đã bỏ qua (tồn tại):</span>
                                    <span id="bulkCountSkipped" class="font-bold text-yellow-800 text-lg ml-1">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
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

            <div id="presetsTab" class="admin-tab-content">
                <!-- Preset List View -->
                <div id="presetsListView" class="admin-panel">
                    <div class="panel-header">
                        <h2>Quản lý Preset Mốc Giờ</h2>
                        <button id="addPresetBtn" class="btn btn-primary">+ Thêm Preset</button>
                    </div>
                    <table class="admin-table" id="presetsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên Preset</th>
                                <th>Ca</th>
                                <th>Mặc định</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            
            <div id="moc-gioTab" class="admin-tab-content">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h2>Quản lý Mốc giờ theo LINE</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="form-group filter-group">
                            <label for="mocGioCaSelect">Chọn Ca:</label>
                            <select id="mocGioCaSelect" class="custom-select form-control">
                                <option value="">-- Chọn ca --</option>
                            </select>
                        </div>
                        <div class="form-group filter-group">
                            <label for="mocGioLineSelect">Chọn LINE:</label>
                            <select id="mocGioLineSelect" class="custom-select form-control">
                                <option value="">-- Tất cả (xem default) --</option>
                                <option value="default">Mốc giờ mặc định</option>
                            </select>
                        </div>
                    </div>
                    <div id="mocGioTableContainer" style="display: none;">
                        <div class="panel-header">
                            <h3 id="mocGioTitle">Mốc giờ: </h3>
                            <div class="flex gap-2">
                                <button id="copyDefaultBtn" class="btn bg-green-600 hover:bg-green-700 text-white" style="display: none;">Copy từ Default</button>
                                <button id="addMocGioBtn" class="btn btn-primary">+ Thêm Mốc giờ</button>
                            </div>
                        </div>
                        <div id="mocGioFallbackNotice" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-3 mb-4" style="display: none;">
                            <p class="font-medium">LINE này đang sử dụng mốc giờ mặc định</p>
                            <p class="text-sm">Nhấn "Copy từ Default" để tạo mốc giờ riêng cho LINE này.</p>
                        </div>
                        <table class="admin-table" id="mocGioTable">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Giờ</th>
                                    <th>Phút lũy kế</th>
                                    <th>Ca</th>
                                    <th>LINE</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="importTab" class="admin-tab-content">
                <div class="admin-panel">
                    <div class="panel-header">
                        <h2>Import Công Đoạn & Mã Hàng từ Excel</h2>
                    </div>
                    <div class="p-6">
                        <div id="importUploadZone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 md:p-10 text-center hover:border-primary hover:bg-gray-50 transition-all cursor-pointer bg-white group">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-6 h-6 text-gray-400 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                </div>
                                <div class="text-gray-600">
                                    <span class="font-medium block sm:inline">Kéo thả file Excel vào đây</span>
                                    <span class="text-gray-400 hidden sm:inline"> hoặc </span>
                                    <span class="text-primary font-medium hover:underline block sm:inline mt-1 sm:mt-0">Chọn file</span>
                                </div>
                                <p class="text-xs sm:text-sm text-gray-400">Hỗ trợ: .xlsx, .xls (tối đa 10MB)</p>
                            </div>
                            <input type="file" id="importFileInput" accept=".xlsx,.xls" class="hidden">
                        </div>

                        <div id="importSelectedFile" class="hidden mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-between gap-3 shadow-sm">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-8 h-8 rounded bg-white flex items-center justify-center border border-gray-200 flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <span id="importFileName" class="text-sm font-medium text-gray-700 truncate"></span>
                            </div>
                            <button type="button" id="importClearFile" class="p-1.5 rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all flex-shrink-0" title="Xóa file">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div id="importPreviewSection" class="hidden mt-6">
                            <div id="importStats" class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                            </div>

                            <div id="importErrors" class="hidden mb-6">
                                <h4 class="text-sm font-semibold text-danger mb-2">Sheets có lỗi:</h4>
                                <div id="importErrorsList" class="space-y-2"></div>
                            </div>

                            <div class="mb-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Chi tiết các sheet:</h4>
                                <div id="importPreviewList" class="space-y-3 max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t">
                                <button type="button" id="importCancelBtn" class="btn px-4 py-2 rounded-lg bg-gray-500 hover:bg-gray-700 text-white transition-colors font-medium">
                                    Hủy
                                </button>
                                <button type="button" id="importConfirmBtn" class="btn btn-primary px-6 py-2.5 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">
                                    Xác nhận Import
                                </button>
                            </div>
                        </div>

                        <div id="importResultSection" class="hidden mt-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800" id="importResultTitle">Kết quả Import</h3>
                            <div id="importResultStats" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Line Modal -->
    <div id="lineModal" class="modal hidden fixed inset-0 z-[1000] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('lineModal')">
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
    <div id="userLineModal" class="modal hidden fixed inset-0 z-[1000] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('userLineModal')">
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
    <?php include __DIR__ . '/includes/components/confirm-modal.php'; ?>
    
    <!-- Loading Overlay -->
    <?php include __DIR__ . '/includes/components/loading-overlay.php'; ?>

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
    
    <div id="mocGioModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('mocGioModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 id="mocGioModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm Mốc giờ mới</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('mocGioModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="mocGioForm" class="p-6 space-y-4">
                <input type="hidden" id="mocGioId" name="id">
                <input type="hidden" id="mocGioFormCaId" name="ca_id">
                <input type="hidden" id="mocGioFormLineId" name="line_id">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group space-y-1">
                        <label for="mocGioGio" class="block text-sm font-medium text-gray-700">Giờ (HH:MM)</label>
                        <input type="time" id="mocGioGio" name="gio" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                    </div>
                    <div class="form-group space-y-1">
                        <label for="mocGioThuTu" class="block text-sm font-medium text-gray-700">Thứ tự</label>
                        <input type="number" id="mocGioThuTu" name="thu_tu" value="1" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                    </div>
                </div>
                <div class="form-group space-y-1">
                    <label for="mocGioPhutLuyKe" class="block text-sm font-medium text-gray-700">Số phút hiệu dụng lũy kế</label>
                    <input type="number" id="mocGioPhutLuyKe" name="so_phut_hieu_dung_luy_ke" value="0" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                    <p class="text-xs text-gray-500">Tổng số phút làm việc hiệu dụng tính đến mốc giờ này</p>
                </div>
                <div class="form-group" id="mocGioIsActiveGroup" style="display:none">
                    <div class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="mocGioIsActive" name="is_active" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="mocGioIsActive" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Đang hoạt động</label>
                    </div>
                </div>
                <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('mocGioModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preset Detail Modal -->
    <div id="presetDetailModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('presetDetailModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-2xl p-0 overflow-hidden flex flex-col max-h-[90vh]" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center flex-shrink-0">
                <h2 id="presetDetailTitle" class="text-xl font-bold text-gray-800 m-0">Chi tiết Preset</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('presetDetailModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-grow">
                <div class="mb-6 border-b border-gray-100 pb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">Mốc giờ thiết lập</h3>
                    </div>
                    <div id="presetMocGioList" class="flex flex-wrap gap-2">
                        <!-- Mốc giờ sẽ được render ở đây -->
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-700">Danh sách LINE áp dụng</h3>
                        <button type="button" class="btn btn-sm btn-primary" onclick="showAssignLinesModal(currentPresetDetail.id)">+ Gán thêm LINE</button>
                    </div>
                    <div id="presetAssignedLines" class="border rounded-lg overflow-hidden">
                        <!-- Table content populated by JS -->
                    </div>
                </div>
            </div>

            <div class="modal-actions flex justify-end gap-3 p-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                <button type="button" class="btn px-4 py-2 rounded-lg bg-gray-500 hover:bg-gray-700 text-white transition-colors font-medium" onclick="closeModal('presetDetailModal')">Đóng</button>
            </div>
        </div>
    </div>

    <!-- Preset Modal (Create/Edit) -->
    <div id="presetModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('presetModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-md p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 id="presetModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm Preset mới</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('presetModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="presetForm" class="p-6 space-y-4">
                <input type="hidden" id="presetId" name="id">
                <div class="form-group space-y-1">
                    <label for="presetTenSet" class="block text-sm font-medium text-gray-700">Tên Preset</label>
                    <input type="text" id="presetTenSet" name="ten_set" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none" placeholder="VD: Ca Sáng - Chuẩn">
                </div>
                <div class="form-group space-y-1">
                    <label for="presetCaSelect" class="block text-sm font-medium text-gray-700">Áp dụng cho Ca</label>
                    <select id="presetCaSelect" name="ca_id" required class="custom-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none bg-white">
                        <option value="">-- Chọn ca --</option>
                    </select>
                </div>
                <div class="form-group">
                    <div class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="presetIsDefault" name="is_default" class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="presetIsDefault" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Đặt làm mặc định cho ca này</label>
                    </div>
                </div>
                <div class="form-group" id="presetIsActiveGroup" style="display:none">
                    <div class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="presetIsActive" name="is_active" checked class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="presetIsActive" class="text-sm font-medium text-gray-700 cursor-pointer select-none">Đang hoạt động</label>
                    </div>
                </div>
                <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('presetModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Lines Modal (Multi-select) -->
    <div id="assignLinesModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('assignLinesModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-2xl p-0 overflow-hidden flex flex-col max-h-[90vh]" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center flex-shrink-0">
                <h2 id="assignLinesTitle" class="text-xl font-bold text-gray-800 m-0">Gán LINE vào Preset</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('assignLinesModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="assignLinesForm" class="flex flex-col flex-grow overflow-hidden">
                <input type="hidden" id="assignLinesPresetId" name="preset_id">
                
                <div class="p-4 border-b bg-gray-50 flex-shrink-0">
                    <input type="text" id="assignLinesSearch" placeholder="Tìm kiếm LINE..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                </div>

                <div class="p-6 overflow-y-auto flex-grow">
                    <div id="unassignedLinesContainer" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <!-- Checkboxes populated by JS -->
                    </div>
                </div>

                <div class="modal-actions flex justify-end gap-3 p-4 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('assignLinesModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Gán đã chọn</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Copy Preset Modal -->
    <div id="copyPresetModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('copyPresetModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-md p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800 m-0">Copy Preset</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('copyPresetModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="copyPresetForm" class="p-6 space-y-4">
                <input type="hidden" id="copyPresetSourceId" name="source_set_id">
                <div class="form-group space-y-1">
                    <label for="copyPresetNewName" class="block text-sm font-medium text-gray-700">Tên Preset Mới</label>
                    <input type="text" id="copyPresetNewName" name="ten_set" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('copyPresetModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Copy</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Preset Moc Gio Modal (Reusing structure but specific IDs for Preset context) -->
    <div id="presetMocGioModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="closeModal('presetMocGioModal')">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 id="presetMocGioModalTitle" class="text-xl font-bold text-gray-800 m-0">Thêm Mốc giờ vào Preset</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="closeModal('presetMocGioModal')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="presetMocGioForm" class="p-6 space-y-4">
                <input type="hidden" id="presetMocGioId" name="id">
                <input type="hidden" id="presetMocGioPresetId" name="preset_id">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group space-y-1">
                        <label for="presetMocGioGio" class="block text-sm font-medium text-gray-700">Giờ (HH:MM)</label>
                        <input type="time" id="presetMocGioGio" name="gio" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                    </div>
                    <div class="form-group space-y-1">
                        <label for="presetMocGioThuTu" class="block text-sm font-medium text-gray-700">Thứ tự</label>
                        <input type="number" id="presetMocGioThuTu" name="thu_tu" value="1" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                    </div>
                </div>
                <div class="form-group space-y-1">
                    <label for="presetMocGioPhutLuyKe" class="block text-sm font-medium text-gray-700">Số phút hiệu dụng lũy kế</label>
                    <input type="number" id="presetMocGioPhutLuyKe" name="so_phut_hieu_dung_luy_ke" value="0" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="closeModal('presetMocGioModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script type="module" src="assets/js/admin.js"></script>
</body>
</html>