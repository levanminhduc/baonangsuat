<?php
require_once __DIR__ . '/classes/Auth.php';

if (!Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if (!Auth::checkRole(['admin'])) {
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
</head>
<body>
    <?php
    $navTitle = 'QUẢN LÝ HỆ THỐNG NĂNG SUẤT';
    $showAddBtn = false;
    $showHomeBtn = true;
    include __DIR__ . '/includes/navbar.php';
    ?>
    <div class="app-container">
        <div class="header">
            <div class="header-right">
                <span class="user-info"><?php echo htmlspecialchars($session['ho_ten']); ?> (Admin)</span>
                <a href="nhap-nang-suat.php" class="btn btn-logout">Về trang chính</a>
                <button id="logoutBtn" class="btn btn-logout">Đăng xuất</button>
            </div>
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
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="userLineFilterLine">Lọc theo LINE:</label>
                        <select id="userLineFilterLine" class="form-control" style="max-width: 300px; display: inline-block; margin-left: 10px;">
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
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="routingMaHangSelect">Chọn Mã hàng:</label>
                        <select id="routingMaHangSelect" class="form-control" style="max-width: 400px;">
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
    
    <div id="lineModal" class="modal hidden">
        <div class="modal-content">
            <h2 id="lineModalTitle">Thêm LINE mới</h2>
            <form id="lineForm">
                <input type="hidden" id="lineId" name="id">
                <div class="form-group">
                    <label for="maLine">Mã LINE</label>
                    <input type="text" id="maLine" name="ma_line" required>
                </div>
                <div class="form-group">
                    <label for="tenLine">Tên LINE</label>
                    <input type="text" id="tenLine" name="ten_line" required>
                </div>
                <div class="form-group" id="isActiveGroup" style="display:none">
                    <label>
                        <input type="checkbox" id="isActive" name="is_active" checked>
                        Đang hoạt động
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeModal('lineModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="userLineModal" class="modal hidden">
        <div class="modal-content">
            <h2>Thêm User-LINE Mapping</h2>
            <form id="userLineForm">
                <div class="form-group">
                    <label for="userSelect">Chọn User</label>
                    <select id="userSelect" name="ma_nv" required style="width: 100%;">
                        <option value="">-- Chọn User --</option>
                    </select>
                    <input type="text" id="userSearchInput" placeholder="Tìm kiếm user..." style="width: 100%; margin-top: 5px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="form-group">
                    <label for="lineSelect">Chọn LINE</label>
                    <select id="lineSelect" name="line_id" required>
                        <option value="">-- Chọn LINE --</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeModal('userLineModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="confirmModal" class="modal hidden">
        <div class="modal-content">
            <h2>Xác nhận</h2>
            <p id="confirmMessage"></p>
            <div class="modal-actions">
                <button type="button" class="btn" onclick="closeModal('confirmModal')">Hủy</button>
                <button type="button" id="confirmBtn" class="btn btn-danger">Xác nhận</button>
            </div>
        </div>
    </div>
    
    <div id="maHangModal" class="modal hidden">
        <div class="modal-content">
            <h2 id="maHangModalTitle">Thêm Mã hàng mới</h2>
            <form id="maHangForm">
                <input type="hidden" id="maHangId" name="id">
                <div class="form-group">
                    <label for="maHangCode">Mã hàng</label>
                    <input type="text" id="maHangCode" name="ma_hang" required>
                </div>
                <div class="form-group">
                    <label for="tenHang">Tên hàng</label>
                    <input type="text" id="tenHang" name="ten_hang" required>
                </div>
                <div class="form-group" id="maHangIsActiveGroup" style="display:none">
                    <label>
                        <input type="checkbox" id="maHangIsActive" name="is_active" checked>
                        Đang hoạt động
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeModal('maHangModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="congDoanModal" class="modal hidden">
        <div class="modal-content">
            <h2 id="congDoanModalTitle">Thêm Công đoạn mới</h2>
            <form id="congDoanForm">
                <input type="hidden" id="congDoanId" name="id">
                <div class="form-group">
                    <label for="maCongDoan">Mã công đoạn</label>
                    <input type="text" id="maCongDoan" name="ma_cong_doan" required>
                </div>
                <div class="form-group">
                    <label for="tenCongDoan">Tên công đoạn</label>
                    <input type="text" id="tenCongDoan" name="ten_cong_doan" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="laCongDoanThanhPham" name="la_cong_doan_thanh_pham">
                        Là công đoạn thành phẩm
                    </label>
                </div>
                <div class="form-group" id="congDoanIsActiveGroup" style="display:none">
                    <label>
                        <input type="checkbox" id="congDoanIsActive" name="is_active" checked>
                        Đang hoạt động
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeModal('congDoanModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="routingModal" class="modal hidden">
        <div class="modal-content">
            <h2 id="routingModalTitle">Thêm Công đoạn vào Routing</h2>
            <form id="routingForm">
                <input type="hidden" id="routingId" name="id">
                <input type="hidden" id="routingMaHangId" name="ma_hang_id">
                <div class="form-group" id="routingCongDoanGroup">
                    <label for="routingCongDoanSelect">Công đoạn</label>
                    <select id="routingCongDoanSelect" name="cong_doan_id" required>
                        <option value="">-- Chọn công đoạn --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="routingThuTu">Thứ tự</label>
                    <input type="number" id="routingThuTu" name="thu_tu" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label for="routingLineSelect">LINE (tùy chọn)</label>
                    <select id="routingLineSelect" name="line_id">
                        <option value="">-- Tất cả LINE --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="routingBatBuoc" name="bat_buoc" checked>
                        Bắt buộc
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="routingTinhLuyKe" name="la_cong_doan_tinh_luy_ke">
                        Tính lũy kế
                    </label>
                </div>
                <div class="form-group">
                    <label for="routingGhiChu">Ghi chú</label>
                    <input type="text" id="routingGhiChu" name="ghi_chu">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeModal('routingModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const API_BASE = '/baonangsuat/api';
    let linesData = [];
    let userLinesData = [];
    let usersData = [];
    let allUsersOptions = [];
    let maHangData = [];
    let congDoanData = [];
    let routingData = [];
    let selectedMaHangId = null;
    let userLineFilterLineId = '';

    document.addEventListener('DOMContentLoaded', () => {
        loadLines();
        loadUsers();
        loadUserLines();
        loadMaHang();
        loadCongDoan();
        bindEvents();
    });

    function bindEvents() {
        document.getElementById('logoutBtn').addEventListener('click', logout);
        
        document.querySelectorAll('.admin-tab').forEach(tab => {
            tab.addEventListener('click', () => switchTab(tab.dataset.tab));
        });
        
        document.getElementById('addLineBtn').addEventListener('click', () => showLineModal());
        document.getElementById('addUserLineBtn').addEventListener('click', () => showUserLineModal());
        document.getElementById('addMaHangBtn').addEventListener('click', () => showMaHangModal());
        document.getElementById('addCongDoanBtn').addEventListener('click', () => showCongDoanModal());
        document.getElementById('addRoutingBtn').addEventListener('click', () => showRoutingModal());
        
        document.getElementById('lineForm').addEventListener('submit', handleLineSubmit);
        document.getElementById('userLineForm').addEventListener('submit', handleUserLineSubmit);
        document.getElementById('maHangForm').addEventListener('submit', handleMaHangSubmit);
        document.getElementById('congDoanForm').addEventListener('submit', handleCongDoanSubmit);
        document.getElementById('routingForm').addEventListener('submit', handleRoutingSubmit);
        
        document.getElementById('routingMaHangSelect').addEventListener('change', handleRoutingMaHangChange);
        document.getElementById('userLineFilterLine').addEventListener('change', handleUserLineFilterChange);
        document.getElementById('userSearchInput').addEventListener('input', handleUserSearch);
    }
    
    async function loadUsers() {
        try {
            const response = await api('GET', '/admin/users');
            if (response.success) {
                usersData = response.data;
                buildUserOptions();
            }
        } catch (error) {
            showToast('Lỗi tải danh sách users', 'error');
        }
    }
    
    function buildUserOptions() {
        allUsersOptions = usersData.map(u => ({
            value: u.name,
            label: u.ho_ten ? `${u.name} - ${u.ho_ten}` : u.name,
            searchText: `${u.name} ${u.ho_ten || ''}`.toLowerCase()
        }));
    }
    
    function handleUserLineFilterChange() {
        userLineFilterLineId = document.getElementById('userLineFilterLine').value;
        renderUserLinesTable();
    }
    
    function handleUserSearch(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const select = document.getElementById('userSelect');
        const currentValue = select.value;
        
        let filteredOptions = allUsersOptions;
        if (searchTerm) {
            filteredOptions = allUsersOptions.filter(opt => opt.searchText.includes(searchTerm));
        }
        
        select.innerHTML = '<option value="">-- Chọn User --</option>' +
            filteredOptions.map(opt =>
                `<option value="${escapeHtml(opt.value)}" ${opt.value === currentValue ? 'selected' : ''}>${escapeHtml(opt.label)}</option>`
            ).join('');
    }
    
    function updateUserLineFilterSelect() {
        const select = document.getElementById('userLineFilterLine');
        select.innerHTML = '<option value="">-- Tất cả LINE --</option>' +
            linesData.map(l =>
                `<option value="${l.id}">${escapeHtml(l.ma_line)} - ${escapeHtml(l.ten_line)}</option>`
            ).join('');
    }

    function switchTab(tabName) {
        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.admin-tab-content').forEach(c => c.classList.remove('active'));
        
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        document.getElementById(`${tabName}Tab`).classList.add('active');
    }

    async function loadLines() {
        try {
            const response = await api('GET', '/admin/lines');
            if (response.success) {
                linesData = response.data;
                renderLinesTable();
                updateUserLineFilterSelect();
            }
        } catch (error) {
            showToast('Lỗi tải danh sách LINE', 'error');
        }
    }

    function renderLinesTable() {
        const tbody = document.querySelector('#linesTable tbody');
        if (linesData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">Chưa có LINE nào</td></tr>';
            return;
        }
        
        tbody.innerHTML = linesData.map(line => `
            <tr>
                <td>${line.id}</td>
                <td>${escapeHtml(line.ma_line)}</td>
                <td>${escapeHtml(line.ten_line)}</td>
                <td><span class="status-badge ${line.is_active == 1 ? 'status-approved' : 'status-locked'}">${line.is_active == 1 ? 'Hoạt động' : 'Tắt'}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editLine(${line.id})">Sửa</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteLine(${line.id})">Xóa</button>
                </td>
            </tr>
        `).join('');
    }

    async function loadUserLines() {
        try {
            const response = await api('GET', '/admin/user-lines');
            if (response.success) {
                userLinesData = response.data;
                renderUserLinesTable();
            }
        } catch (error) {
            showToast('Lỗi tải danh sách mapping', 'error');
        }
    }

    function renderUserLinesTable() {
        const tbody = document.querySelector('#userLinesTable tbody');
        
        let filteredData = userLinesData;
        if (userLineFilterLineId) {
            filteredData = userLinesData.filter(ul => ul.line_id == userLineFilterLineId);
        }
        
        if (filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">Chưa có mapping nào</td></tr>';
            return;
        }
        
        tbody.innerHTML = filteredData.map(ul => `
            <tr>
                <td>${escapeHtml(ul.ma_nv)}</td>
                <td>${escapeHtml(ul.ho_ten || '-')}</td>
                <td>${escapeHtml(ul.ma_line)}</td>
                <td>${escapeHtml(ul.ten_line)}</td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="deleteUserLine('${escapeHtml(ul.ma_nv)}', ${ul.line_id})">Xóa</button>
                </td>
            </tr>
        `).join('');
    }

    function showLineModal(lineId = null) {
        const modal = document.getElementById('lineModal');
        const title = document.getElementById('lineModalTitle');
        const form = document.getElementById('lineForm');
        const isActiveGroup = document.getElementById('isActiveGroup');
        
        form.reset();
        document.getElementById('lineId').value = '';
        
        if (lineId) {
            title.textContent = 'Sửa LINE';
            isActiveGroup.style.display = 'block';
            const line = linesData.find(l => l.id == lineId);
            if (line) {
                document.getElementById('lineId').value = line.id;
                document.getElementById('maLine').value = line.ma_line;
                document.getElementById('tenLine').value = line.ten_line;
                document.getElementById('isActive').checked = line.is_active == 1;
            }
        } else {
            title.textContent = 'Thêm LINE mới';
            isActiveGroup.style.display = 'none';
        }
        
        modal.classList.remove('hidden');
    }

    function editLine(lineId) {
        showLineModal(lineId);
    }

    async function handleLineSubmit(e) {
        e.preventDefault();
        
        const lineId = document.getElementById('lineId').value;
        const ma_line = document.getElementById('maLine').value.trim();
        const ten_line = document.getElementById('tenLine').value.trim();
        const is_active = document.getElementById('isActive').checked ? 1 : 0;
        
        try {
            let response;
            if (lineId) {
                response = await api('PUT', `/admin/lines/${lineId}`, { ma_line, ten_line, is_active });
            } else {
                response = await api('POST', '/admin/lines', { ma_line, ten_line });
            }
            
            if (response.success) {
                showToast(response.message, 'success');
                closeModal('lineModal');
                loadLines();
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi lưu LINE', 'error');
        }
    }

    function deleteLine(lineId) {
        showConfirmModal('Bạn có chắc muốn xóa LINE này?', async () => {
            try {
                const response = await api('DELETE', `/admin/lines/${lineId}`);
                if (response.success) {
                    showToast(response.message, 'success');
                    loadLines();
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast('Lỗi xóa LINE', 'error');
            }
            closeModal('confirmModal');
        });
    }

    function showUserLineModal() {
        const modal = document.getElementById('userLineModal');
        const form = document.getElementById('userLineForm');
        const lineSelect = document.getElementById('lineSelect');
        const userSelect = document.getElementById('userSelect');
        const userSearchInput = document.getElementById('userSearchInput');
        
        form.reset();
        userSearchInput.value = '';
        
        userSelect.innerHTML = '<option value="">-- Chọn User --</option>' +
            allUsersOptions.map(opt =>
                `<option value="${escapeHtml(opt.value)}">${escapeHtml(opt.label)}</option>`
            ).join('');
        
        lineSelect.innerHTML = '<option value="">-- Chọn LINE --</option>' +
            linesData.filter(l => l.is_active == 1).map(l =>
                `<option value="${l.id}">${escapeHtml(l.ma_line)} - ${escapeHtml(l.ten_line)}</option>`
            ).join('');
        
        modal.classList.remove('hidden');
    }

    async function handleUserLineSubmit(e) {
        e.preventDefault();
        
        const ma_nv = document.getElementById('userSelect').value.trim();
        const line_id = parseInt(document.getElementById('lineSelect').value);
        
        if (!ma_nv) {
            showToast('Vui lòng chọn user', 'error');
            return;
        }
        
        try {
            const response = await api('POST', '/admin/user-lines', { ma_nv, line_id });
            
            if (response.success) {
                showToast(response.message, 'success');
                closeModal('userLineModal');
                loadUserLines();
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi thêm mapping', 'error');
        }
    }

    function deleteUserLine(ma_nv, line_id) {
        showConfirmModal(`Bạn có chắc muốn xóa mapping cho ${ma_nv}?`, async () => {
            try {
                const response = await api('DELETE', '/admin/user-lines', { ma_nv, line_id });
                if (response.success) {
                    showToast(response.message, 'success');
                    loadUserLines();
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast('Lỗi xóa mapping', 'error');
            }
            closeModal('confirmModal');
        });
    }

    function showConfirmModal(message, callback) {
        const modal = document.getElementById('confirmModal');
        document.getElementById('confirmMessage').textContent = message;
        document.getElementById('confirmBtn').onclick = callback;
        modal.classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    async function api(method, endpoint, data = null) {
        const options = {
            method: method,
            headers: { 'Content-Type': 'application/json' }
        };
        
        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(API_BASE + endpoint, options);
        return await response.json();
    }

    async function logout() {
        await api('GET', '/auth/logout');
        window.location.href = 'index.php';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showToast(message, type = 'success') {
        const existing = document.querySelectorAll('.toast');
        existing.forEach(t => t.remove());
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.remove(), 3000);
    }

    async function loadMaHang() {
        try {
            const response = await api('GET', '/admin/ma-hang');
            if (response.success) {
                maHangData = response.data;
                renderMaHangTable();
                updateRoutingMaHangSelect();
            }
        } catch (error) {
            showToast('Lỗi tải danh sách mã hàng', 'error');
        }
    }

    function renderMaHangTable() {
        const tbody = document.querySelector('#maHangTable tbody');
        if (maHangData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">Chưa có mã hàng nào</td></tr>';
            return;
        }
        
        tbody.innerHTML = maHangData.map(mh => `
            <tr>
                <td>${mh.id}</td>
                <td>${escapeHtml(mh.ma_hang)}</td>
                <td>${escapeHtml(mh.ten_hang)}</td>
                <td><span class="status-badge ${mh.is_active == 1 ? 'status-approved' : 'status-locked'}">${mh.is_active == 1 ? 'Hoạt động' : 'Tắt'}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editMaHang(${mh.id})">Sửa</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteMaHang(${mh.id})">Xóa</button>
                </td>
            </tr>
        `).join('');
    }

    function updateRoutingMaHangSelect() {
        const select = document.getElementById('routingMaHangSelect');
        select.innerHTML = '<option value="">-- Chọn mã hàng --</option>' +
            maHangData.filter(mh => mh.is_active == 1).map(mh =>
                `<option value="${mh.id}">${escapeHtml(mh.ma_hang)} - ${escapeHtml(mh.ten_hang)}</option>`
            ).join('');
    }

    function showMaHangModal(maHangId = null) {
        const modal = document.getElementById('maHangModal');
        const title = document.getElementById('maHangModalTitle');
        const form = document.getElementById('maHangForm');
        const isActiveGroup = document.getElementById('maHangIsActiveGroup');
        
        form.reset();
        document.getElementById('maHangId').value = '';
        
        if (maHangId) {
            title.textContent = 'Sửa Mã hàng';
            isActiveGroup.style.display = 'block';
            const mh = maHangData.find(m => m.id == maHangId);
            if (mh) {
                document.getElementById('maHangId').value = mh.id;
                document.getElementById('maHangCode').value = mh.ma_hang;
                document.getElementById('tenHang').value = mh.ten_hang;
                document.getElementById('maHangIsActive').checked = mh.is_active == 1;
            }
        } else {
            title.textContent = 'Thêm Mã hàng mới';
            isActiveGroup.style.display = 'none';
        }
        
        modal.classList.remove('hidden');
    }

    function editMaHang(maHangId) {
        showMaHangModal(maHangId);
    }

    async function handleMaHangSubmit(e) {
        e.preventDefault();
        
        const maHangId = document.getElementById('maHangId').value;
        const ma_hang = document.getElementById('maHangCode').value.trim();
        const ten_hang = document.getElementById('tenHang').value.trim();
        const is_active = document.getElementById('maHangIsActive').checked ? 1 : 0;
        
        try {
            let response;
            if (maHangId) {
                response = await api('PUT', `/admin/ma-hang/${maHangId}`, { ma_hang, ten_hang, is_active });
            } else {
                response = await api('POST', '/admin/ma-hang', { ma_hang, ten_hang });
            }
            
            if (response.success) {
                showToast(response.message, 'success');
                closeModal('maHangModal');
                loadMaHang();
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi lưu mã hàng', 'error');
        }
    }

    function deleteMaHang(maHangId) {
        showConfirmModal('Bạn có chắc muốn xóa mã hàng này?', async () => {
            try {
                const response = await api('DELETE', `/admin/ma-hang/${maHangId}`);
                if (response.success) {
                    showToast(response.message, 'success');
                    loadMaHang();
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast('Lỗi xóa mã hàng', 'error');
            }
            closeModal('confirmModal');
        });
    }

    async function loadCongDoan() {
        try {
            const response = await api('GET', '/admin/cong-doan');
            if (response.success) {
                congDoanData = response.data;
                renderCongDoanTable();
            }
        } catch (error) {
            showToast('Lỗi tải danh sách công đoạn', 'error');
        }
    }

    function renderCongDoanTable() {
        const tbody = document.querySelector('#congDoanTable tbody');
        if (congDoanData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Chưa có công đoạn nào</td></tr>';
            return;
        }
        
        tbody.innerHTML = congDoanData.map(cd => `
            <tr>
                <td>${cd.id}</td>
                <td>${escapeHtml(cd.ma_cong_doan)}</td>
                <td>${escapeHtml(cd.ten_cong_doan)}</td>
                <td><span class="status-badge ${cd.la_cong_doan_thanh_pham == 1 ? 'status-approved' : 'status-draft'}">${cd.la_cong_doan_thanh_pham == 1 ? 'Có' : 'Không'}</span></td>
                <td><span class="status-badge ${cd.is_active == 1 ? 'status-approved' : 'status-locked'}">${cd.is_active == 1 ? 'Hoạt động' : 'Tắt'}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editCongDoan(${cd.id})">Sửa</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCongDoan(${cd.id})">Xóa</button>
                </td>
            </tr>
        `).join('');
    }

    function showCongDoanModal(congDoanId = null) {
        const modal = document.getElementById('congDoanModal');
        const title = document.getElementById('congDoanModalTitle');
        const form = document.getElementById('congDoanForm');
        const isActiveGroup = document.getElementById('congDoanIsActiveGroup');
        
        form.reset();
        document.getElementById('congDoanId').value = '';
        
        if (congDoanId) {
            title.textContent = 'Sửa Công đoạn';
            isActiveGroup.style.display = 'block';
            const cd = congDoanData.find(c => c.id == congDoanId);
            if (cd) {
                document.getElementById('congDoanId').value = cd.id;
                document.getElementById('maCongDoan').value = cd.ma_cong_doan;
                document.getElementById('tenCongDoan').value = cd.ten_cong_doan;
                document.getElementById('laCongDoanThanhPham').checked = cd.la_cong_doan_thanh_pham == 1;
                document.getElementById('congDoanIsActive').checked = cd.is_active == 1;
            }
        } else {
            title.textContent = 'Thêm Công đoạn mới';
            isActiveGroup.style.display = 'none';
        }
        
        modal.classList.remove('hidden');
    }

    function editCongDoan(congDoanId) {
        showCongDoanModal(congDoanId);
    }

    async function handleCongDoanSubmit(e) {
        e.preventDefault();
        
        const congDoanId = document.getElementById('congDoanId').value;
        const ma_cong_doan = document.getElementById('maCongDoan').value.trim();
        const ten_cong_doan = document.getElementById('tenCongDoan').value.trim();
        const la_cong_doan_thanh_pham = document.getElementById('laCongDoanThanhPham').checked ? 1 : 0;
        const is_active = document.getElementById('congDoanIsActive').checked ? 1 : 0;
        
        try {
            let response;
            if (congDoanId) {
                response = await api('PUT', `/admin/cong-doan/${congDoanId}`, { ma_cong_doan, ten_cong_doan, is_active, la_cong_doan_thanh_pham });
            } else {
                response = await api('POST', '/admin/cong-doan', { ma_cong_doan, ten_cong_doan, la_cong_doan_thanh_pham });
            }
            
            if (response.success) {
                showToast(response.message, 'success');
                closeModal('congDoanModal');
                loadCongDoan();
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi lưu công đoạn', 'error');
        }
    }

    function deleteCongDoan(congDoanId) {
        showConfirmModal('Bạn có chắc muốn xóa công đoạn này?', async () => {
            try {
                const response = await api('DELETE', `/admin/cong-doan/${congDoanId}`);
                if (response.success) {
                    showToast(response.message, 'success');
                    loadCongDoan();
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast('Lỗi xóa công đoạn', 'error');
            }
            closeModal('confirmModal');
        });
    }

    async function handleRoutingMaHangChange() {
        const maHangId = document.getElementById('routingMaHangSelect').value;
        const container = document.getElementById('routingTableContainer');
        
        if (!maHangId) {
            container.style.display = 'none';
            selectedMaHangId = null;
            return;
        }
        
        selectedMaHangId = parseInt(maHangId);
        const mh = maHangData.find(m => m.id == selectedMaHangId);
        document.getElementById('routingTitle').textContent = `Routing cho: ${mh ? mh.ma_hang + ' - ' + mh.ten_hang : ''}`;
        
        await loadRouting(selectedMaHangId);
        container.style.display = 'block';
    }

    async function loadRouting(maHangId) {
        try {
            const response = await api('GET', `/admin/routing?ma_hang_id=${maHangId}`);
            if (response.success) {
                routingData = response.data;
                renderRoutingTable();
            }
        } catch (error) {
            showToast('Lỗi tải routing', 'error');
        }
    }

    function renderRoutingTable() {
        const tbody = document.querySelector('#routingTable tbody');
        if (routingData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Chưa có công đoạn nào trong routing</td></tr>';
            return;
        }
        
        tbody.innerHTML = routingData.map(r => `
            <tr>
                <td>${r.thu_tu}</td>
                <td>${escapeHtml(r.ma_cong_doan)} - ${escapeHtml(r.ten_cong_doan)}</td>
                <td>${r.ma_line ? escapeHtml(r.ma_line) : 'Tất cả'}</td>
                <td><span class="status-badge ${r.la_cong_doan_tinh_luy_ke == 1 ? 'status-approved' : 'status-draft'}">${r.la_cong_doan_tinh_luy_ke == 1 ? 'Có' : 'Không'}</span></td>
                <td><span class="status-badge ${r.bat_buoc == 1 ? 'status-approved' : 'status-draft'}">${r.bat_buoc == 1 ? 'Có' : 'Không'}</span></td>
                <td>${escapeHtml(r.ghi_chu || '')}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editRouting(${r.id})">Sửa</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteRouting(${r.id})">Xóa</button>
                </td>
            </tr>
        `).join('');
    }

    function showRoutingModal(routingId = null) {
        if (!selectedMaHangId) {
            showToast('Vui lòng chọn mã hàng trước', 'error');
            return;
        }
        
        const modal = document.getElementById('routingModal');
        const title = document.getElementById('routingModalTitle');
        const form = document.getElementById('routingForm');
        const congDoanGroup = document.getElementById('routingCongDoanGroup');
        const congDoanSelect = document.getElementById('routingCongDoanSelect');
        const lineSelect = document.getElementById('routingLineSelect');
        
        form.reset();
        document.getElementById('routingId').value = '';
        document.getElementById('routingMaHangId').value = selectedMaHangId;
        document.getElementById('routingBatBuoc').checked = true;
        document.getElementById('routingTinhLuyKe').checked = false;
        
        congDoanSelect.innerHTML = '<option value="">-- Chọn công đoạn --</option>' +
            congDoanData.filter(cd => cd.is_active == 1).map(cd =>
                `<option value="${cd.id}">${escapeHtml(cd.ma_cong_doan)} - ${escapeHtml(cd.ten_cong_doan)}</option>`
            ).join('');
        
        lineSelect.innerHTML = '<option value="">-- Tất cả LINE --</option>' +
            linesData.filter(l => l.is_active == 1).map(l =>
                `<option value="${l.id}">${escapeHtml(l.ma_line)} - ${escapeHtml(l.ten_line)}</option>`
            ).join('');
        
        if (routingId) {
            title.textContent = 'Sửa Routing';
            congDoanGroup.style.display = 'none';
            const r = routingData.find(rt => rt.id == routingId);
            if (r) {
                document.getElementById('routingId').value = r.id;
                document.getElementById('routingThuTu').value = r.thu_tu;
                document.getElementById('routingLineSelect').value = r.line_id || '';
                document.getElementById('routingBatBuoc').checked = r.bat_buoc == 1;
                document.getElementById('routingTinhLuyKe').checked = r.la_cong_doan_tinh_luy_ke == 1;
                document.getElementById('routingGhiChu').value = r.ghi_chu || '';
            }
        } else {
            title.textContent = 'Thêm Công đoạn vào Routing';
            congDoanGroup.style.display = 'block';
            const maxThuTu = routingData.length > 0 ? Math.max(...routingData.map(r => r.thu_tu)) : 0;
            document.getElementById('routingThuTu').value = maxThuTu + 1;
        }
        
        modal.classList.remove('hidden');
    }

    function editRouting(routingId) {
        showRoutingModal(routingId);
    }

    async function handleRoutingSubmit(e) {
        e.preventDefault();
        
        const routingId = document.getElementById('routingId').value;
        const ma_hang_id = parseInt(document.getElementById('routingMaHangId').value);
        const cong_doan_id = parseInt(document.getElementById('routingCongDoanSelect').value) || null;
        const thu_tu = parseInt(document.getElementById('routingThuTu').value);
        const line_id = document.getElementById('routingLineSelect').value || null;
        const bat_buoc = document.getElementById('routingBatBuoc').checked ? 1 : 0;
        const la_cong_doan_tinh_luy_ke = document.getElementById('routingTinhLuyKe').checked ? 1 : 0;
        const ghi_chu = document.getElementById('routingGhiChu').value.trim();
        
        try {
            let response;
            if (routingId) {
                response = await api('PUT', `/admin/routing/${routingId}`, { thu_tu, bat_buoc, la_cong_doan_tinh_luy_ke, line_id, ghi_chu });
            } else {
                if (!cong_doan_id) {
                    showToast('Vui lòng chọn công đoạn', 'error');
                    return;
                }
                response = await api('POST', '/admin/routing', { ma_hang_id, cong_doan_id, thu_tu, bat_buoc, la_cong_doan_tinh_luy_ke, line_id, ghi_chu });
            }
            
            if (response.success) {
                showToast(response.message, 'success');
                closeModal('routingModal');
                loadRouting(selectedMaHangId);
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi lưu routing', 'error');
        }
    }

    function deleteRouting(routingId) {
        showConfirmModal('Bạn có chắc muốn xóa công đoạn này khỏi routing?', async () => {
            try {
                const response = await api('DELETE', `/admin/routing/${routingId}`);
                if (response.success) {
                    showToast(response.message, 'success');
                    loadRouting(selectedMaHangId);
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast('Lỗi xóa routing', 'error');
            }
            closeModal('confirmModal');
        });
    }
    </script>
</body>
</html>
