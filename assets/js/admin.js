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
let mocGioData = [];
let caListData = [];
let selectedCaId = null;
let selectedLineIdForMocGio = null;
let csrfToken = null;

// Preset Management Variables
let presetsData = [];
let currentPresetDetail = null;
let currentPresetMocGio = [];
let assignedLines = [];

document.addEventListener('DOMContentLoaded', () => {
    loadLines();
    loadUsers();
    loadUserLines();
    loadMaHang();
    loadCongDoan();
    loadCaList();
    loadPresets(); // New function call
    bindEvents();

    const initialTab = getTabFromHash();
    if (initialTab) {
        switchTab(initialTab, false);
    }
});

function bindEvents() {
    document.getElementById('logoutBtn').addEventListener('click', logout);
    
    document.querySelectorAll('.admin-tab').forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(tab.dataset.tab);
        });
    });
    
    window.addEventListener('hashchange', () => {
        const tab = getTabFromHash();
        if (tab) {
            switchTab(tab, false);
        }
    });
    
    document.getElementById('addLineBtn').addEventListener('click', () => showLineModal());
    document.getElementById('addUserLineBtn').addEventListener('click', () => showUserLineModal());
    document.getElementById('addMaHangBtn').addEventListener('click', () => showMaHangModal());
    document.getElementById('addCongDoanBtn').addEventListener('click', () => showCongDoanModal());
    document.getElementById('addRoutingBtn').addEventListener('click', () => showRoutingModal());
    document.getElementById('addMocGioBtn').addEventListener('click', () => showMocGioModal());
    document.getElementById('copyDefaultBtn').addEventListener('click', handleCopyDefault);
    
    document.getElementById('lineForm').addEventListener('submit', handleLineSubmit);
    document.getElementById('userLineForm').addEventListener('submit', handleUserLineSubmit);
    document.getElementById('maHangForm').addEventListener('submit', handleMaHangSubmit);
    document.getElementById('congDoanForm').addEventListener('submit', handleCongDoanSubmit);
    document.getElementById('routingForm').addEventListener('submit', handleRoutingSubmit);
    document.getElementById('mocGioForm').addEventListener('submit', handleMocGioSubmit);
    
    document.getElementById('routingMaHangSelect').addEventListener('change', handleRoutingMaHangChange);
    document.getElementById('userLineFilterLine').addEventListener('change', handleUserLineFilterChange);
    document.getElementById('userSearchInput').addEventListener('input', handleUserSearch);
    document.getElementById('mocGioCaSelect').addEventListener('change', handleMocGioFilterChange);
    document.getElementById('mocGioLineSelect').addEventListener('change', handleMocGioFilterChange);
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

function getTabFromHash() {
    const hash = window.location.hash.substring(1);
    const validTabs = ['lines', 'user-lines', 'ma-hang', 'cong-doan', 'routing', 'presets', 'moc-gio'];
    return validTabs.includes(hash) ? hash : null;
}

function switchTab(tabName, updateHistory = true) {
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.admin-tab-content').forEach(c => c.classList.remove('active'));
    
    const tabBtn = document.querySelector(`[data-tab="${tabName}"]`);
    const tabContent = document.getElementById(`${tabName}Tab`);
    
    if (tabBtn) tabBtn.classList.add('active');
    if (tabContent) tabContent.classList.add('active');

    if (updateHistory) {
        history.pushState(null, null, `#${tabName}`);
    }

    window.scrollTo(0, 0);
}

async function loadLines() {
    try {
        const response = await api('GET', '/admin/lines');
        if (response.success) {
            linesData = response.data;
            renderLinesTable();
            updateUserLineFilterSelect();
            updateMocGioLineSelect();
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

function showConfirmModal(message, callback, title = 'Xác nhận') {
    const modal = document.getElementById('confirmModal');
    
    // Update title if element exists
    const titleEl = document.getElementById('confirmModalTitle');
    if (titleEl) {
        titleEl.textContent = title;
    }
    
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmBtn').onclick = callback;
    modal.classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

async function ensureCsrfToken() {
    if (csrfToken) {
        return csrfToken;
    }
    const response = await fetch(API_BASE + '/csrf-token');
    const result = await response.json();
    if (result && result.token) {
        csrfToken = result.token;
    }
    return csrfToken;
}

async function api(method, endpoint, data = null) {
    const options = {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin'
    };
    
    if (method === 'POST' || method === 'PUT' || method === 'DELETE') {
        await ensureCsrfToken();
        if (csrfToken) {
            options.headers['X-CSRF-Token'] = csrfToken;
        }
    }
    
    if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
        options.body = JSON.stringify(data);
    }
    
    const response = await fetch(API_BASE + endpoint, options);
    return await response.json();
}

/**
 * Hiển thị loading overlay
 * @param {string} message - (Optional) Message tùy chỉnh, hiện tại dùng mặc định trong HTML
 */
function showLoading(message = null) {
    const overlay = document.getElementById('loadingOverlay');
    if (!overlay) return;
    
    overlay.classList.remove('hidden');
    // Force reflow
    void overlay.offsetWidth;
    
    overlay.style.pointerEvents = 'auto';
    overlay.classList.remove('opacity-0');
    overlay.querySelector('.transform').classList.remove('scale-95');
    
    // Disable all submit buttons/inputs to prevent double interactions
    const activeModal = document.querySelector('.modal:not(.hidden)');
    if (activeModal) {
        const buttons = activeModal.querySelectorAll('button, input, select, textarea');
        buttons.forEach(btn => {
            if (!btn.disabled) {
                btn.dataset.tempDisabled = 'true';
                btn.disabled = true;
                if (btn.classList.contains('btn-primary')) {
                    btn.classList.add('opacity-70', 'cursor-wait');
                }
            }
        });
    }
}

/**
 * Ẩn loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (!overlay) return;
    
    overlay.classList.add('opacity-0');
    overlay.querySelector('.transform').classList.add('scale-95');
    overlay.style.pointerEvents = 'none';
    
    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 300);
    
    // Re-enable elements
    const disabledElements = document.querySelectorAll('[data-temp-disabled="true"]');
    disabledElements.forEach(el => {
        el.disabled = false;
        delete el.dataset.tempDisabled;
        el.classList.remove('opacity-70', 'cursor-wait');
    });
}

async function logout() {
    await api('GET', '/auth/logout');
    window.location.href = 'index.php';
}

function escapeHtml(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, '&')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/"/g, '"')
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

async function loadCaList() {
    try {
        const response = await api('GET', '/admin/moc-gio/ca-list');
        if (response.success) {
            caListData = response.data;
            updateMocGioCaSelect();
            updateMocGioLineSelect();
        }
    } catch (error) {
        showToast('Lỗi tải danh sách ca', 'error');
    }
}

function updateMocGioCaSelect() {
    const select = document.getElementById('mocGioCaSelect');
    select.innerHTML = '<option value="">-- Chọn ca --</option>' +
        caListData.map(ca =>
            `<option value="${ca.id}">${escapeHtml(ca.ma_ca)} - ${escapeHtml(ca.ten_ca)}</option>`
        ).join('');
}

function updateMocGioLineSelect() {
    const select = document.getElementById('mocGioLineSelect');
    select.innerHTML = '<option value="">-- Tất cả (xem default) --</option>' +
        '<option value="default">Mốc giờ mặc định</option>' +
        linesData.filter(l => l.is_active == 1).map(l =>
            `<option value="${l.id}">${escapeHtml(l.ma_line)} - ${escapeHtml(l.ten_line)}</option>`
        ).join('');
}

async function handleMocGioFilterChange() {
    const caId = document.getElementById('mocGioCaSelect').value;
    const lineId = document.getElementById('mocGioLineSelect').value;
    const container = document.getElementById('mocGioTableContainer');
    const copyBtn = document.getElementById('copyDefaultBtn');
    const fallbackNotice = document.getElementById('mocGioFallbackNotice');
    
    if (!caId) {
        container.style.display = 'none';
        selectedCaId = null;
        selectedLineIdForMocGio = null;
        return;
    }
    
    selectedCaId = parseInt(caId);
    selectedLineIdForMocGio = lineId;
    
    const ca = caListData.find(c => c.id == selectedCaId);
    let titleText = `Mốc giờ: ${ca ? ca.ma_ca + ' - ' + ca.ten_ca : ''}`;
    
    if (lineId && lineId !== 'default') {
        const line = linesData.find(l => l.id == lineId);
        titleText += ` - ${line ? line.ten_line : 'LINE ' + lineId}`;
        copyBtn.style.display = 'inline-block';
    } else {
        titleText += ' (Mặc định)';
        copyBtn.style.display = 'none';
    }
    
    document.getElementById('mocGioTitle').textContent = titleText;
    
    await loadMocGio(caId, lineId === 'default' ? 'default' : lineId);
    container.style.display = 'block';
    
    const hasLineSpecific = mocGioData.length > 0 && mocGioData.some(m => m.line_id != null);
    if (lineId && lineId !== 'default' && !hasLineSpecific) {
        fallbackNotice.style.display = 'block';
        copyBtn.style.display = 'inline-block';
    } else {
        fallbackNotice.style.display = 'none';
        if (lineId && lineId !== 'default') {
            copyBtn.style.display = 'none';
        }
    }
}

async function loadMocGio(caId, lineId) {
    try {
        let url = `/admin/moc-gio?ca_id=${caId}`;
        if (lineId && lineId !== '') {
            url += `&line_id=${lineId}`;
        }
        const response = await api('GET', url);
        if (response.success) {
            mocGioData = response.data;
            renderMocGioTable();
        }
    } catch (error) {
        showToast('Lỗi tải mốc giờ', 'error');
    }
}

function renderMocGioTable() {
    const tbody = document.querySelector('#mocGioTable tbody');
    if (mocGioData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Chưa có mốc giờ nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = mocGioData.map(mg => {
        const isDefault = mg.line_id === null;
        const canDelete = !isDefault;
        return `
        <tr>
            <td>${mg.thu_tu}</td>
            <td>${escapeHtml(mg.gio)}</td>
            <td>${mg.so_phut_hieu_dung_luy_ke}</td>
            <td>${escapeHtml(mg.ma_ca)}</td>
            <td>${isDefault ? '<span class="text-gray-500 italic">Mặc định</span>' : escapeHtml(mg.ma_line)}</td>
            <td><span class="status-badge ${mg.is_active == 1 ? 'status-approved' : 'status-locked'}">${mg.is_active == 1 ? 'Hoạt động' : 'Tắt'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editMocGio(${mg.id})">Sửa</button>
                ${canDelete ? `<button class="btn btn-sm btn-danger" onclick="deleteMocGio(${mg.id})">Xóa</button>` : ''}
            </td>
        </tr>
    `}).join('');
}

function showMocGioModal(mocGioId = null) {
    if (!selectedCaId) {
        showToast('Vui lòng chọn ca trước', 'error');
        return;
    }
    
    const modal = document.getElementById('mocGioModal');
    const title = document.getElementById('mocGioModalTitle');
    const form = document.getElementById('mocGioForm');
    const isActiveGroup = document.getElementById('mocGioIsActiveGroup');
    
    form.reset();
    document.getElementById('mocGioId').value = '';
    document.getElementById('mocGioFormCaId').value = selectedCaId;
    
    const lineIdForForm = (selectedLineIdForMocGio && selectedLineIdForMocGio !== 'default') ? selectedLineIdForMocGio : '';
    document.getElementById('mocGioFormLineId').value = lineIdForForm;
    
    if (mocGioId) {
        title.textContent = 'Sửa Mốc giờ';
        isActiveGroup.style.display = 'block';
        const mg = mocGioData.find(m => m.id == mocGioId);
        if (mg) {
            document.getElementById('mocGioId').value = mg.id;
            document.getElementById('mocGioGio').value = mg.gio;
            document.getElementById('mocGioThuTu').value = mg.thu_tu;
            document.getElementById('mocGioPhutLuyKe').value = mg.so_phut_hieu_dung_luy_ke;
            document.getElementById('mocGioIsActive').checked = mg.is_active == 1;
        }
    } else {
        title.textContent = 'Thêm Mốc giờ mới';
        isActiveGroup.style.display = 'none';
        const maxThuTu = mocGioData.length > 0 ? Math.max(...mocGioData.map(m => m.thu_tu)) : 0;
        document.getElementById('mocGioThuTu').value = maxThuTu + 1;
        const lastMoc = mocGioData[mocGioData.length - 1];
        document.getElementById('mocGioPhutLuyKe').value = lastMoc ? lastMoc.so_phut_hieu_dung_luy_ke : 0;
    }
    
    modal.classList.remove('hidden');
}

function editMocGio(mocGioId) {
    showMocGioModal(mocGioId);
}

async function handleMocGioSubmit(e) {
    e.preventDefault();
    
    const mocGioId = document.getElementById('mocGioId').value;
    const ca_id = parseInt(document.getElementById('mocGioFormCaId').value);
    const line_id = document.getElementById('mocGioFormLineId').value || null;
    const gio = document.getElementById('mocGioGio').value;
    const thu_tu = parseInt(document.getElementById('mocGioThuTu').value);
    const so_phut_hieu_dung_luy_ke = parseInt(document.getElementById('mocGioPhutLuyKe').value);
    const is_active = document.getElementById('mocGioIsActive').checked ? 1 : 0;
    
    try {
        let response;
        if (mocGioId) {
            response = await api('PUT', `/admin/moc-gio/${mocGioId}`, { gio, thu_tu, so_phut_hieu_dung_luy_ke, is_active });
        } else {
            response = await api('POST', '/admin/moc-gio', { ca_id, line_id, gio, thu_tu, so_phut_hieu_dung_luy_ke });
        }
        
        if (response.success) {
            showToast(response.message, 'success');
            closeModal('mocGioModal');
            handleMocGioFilterChange();
        } else {
            showToast(response.message, 'error');
        }
    } catch (error) {
        showToast('Lỗi lưu mốc giờ', 'error');
    }
}

function deleteMocGio(mocGioId) {
    showConfirmModal('Bạn có chắc muốn xóa mốc giờ này?', async () => {
        try {
            const response = await api('DELETE', `/admin/moc-gio/${mocGioId}`);
            if (response.success) {
                showToast(response.message, 'success');
                handleMocGioFilterChange();
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi xóa mốc giờ', 'error');
        }
        closeModal('confirmModal');
    });
}

async function handleCopyDefault() {
    if (!selectedCaId || !selectedLineIdForMocGio || selectedLineIdForMocGio === 'default') {
        showToast('Vui lòng chọn ca và LINE cụ thể', 'error');
        return;
    }
    
    showConfirmModal('Copy mốc giờ mặc định sang LINE này?', async () => {
        try {
            const response = await api('POST', '/admin/moc-gio/copy-default', {
                ca_id: selectedCaId,
                line_id: parseInt(selectedLineIdForMocGio)
            });
            if (response.success) {
                showToast(response.message, 'success');
                handleMocGioFilterChange();
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi copy mốc giờ', 'error');
        }
        closeModal('confirmModal');
    });
}

window.closeModal = closeModal;
window.editLine = editLine;
window.deleteLine = deleteLine;
window.editMaHang = editMaHang;
window.deleteMaHang = deleteMaHang;
window.editCongDoan = editCongDoan;
window.deleteCongDoan = deleteCongDoan;
window.editRouting = editRouting;
window.deleteRouting = deleteRouting;
window.deleteUserLine = deleteUserLine;
window.editMocGio = editMocGio;
window.deleteMocGio = deleteMocGio;

async function loadPresets() {
    try {
        const response = await api('GET', '/moc-gio-sets');
        if (response.success) {
            presetsData = response.data;
            renderPresetsTable();
        }
    } catch (error) {
        showToast('Lỗi tải danh sách preset', 'error');
    }
}

function renderPresetsTable() {
    const tbody = document.querySelector('#presetsTable tbody');
    if (!tbody) return;
    
    if (presetsData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Chưa có preset nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = presetsData.map(preset => `
        <tr>
            <td>${preset.id}</td>
            <td>${escapeHtml(preset.ten_set)}</td>
            <td>${escapeHtml(preset.ma_ca || '')} - ${escapeHtml(preset.ten_ca || '')}</td>
            <td><span class="status-badge ${preset.is_default == 1 ? 'status-approved' : 'status-draft'}">${preset.is_default == 1 ? 'Có' : 'Không'}</span></td>
            <td><span class="status-badge ${preset.is_active == 1 ? 'status-approved' : 'status-locked'}">${preset.is_active == 1 ? 'Hoạt động' : 'Tắt'}</span></td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="viewPresetDetail(${preset.id})">Chi tiết</button>
                <button class="btn btn-sm btn-primary" onclick="editPreset(${preset.id})">Sửa</button>
                <button class="btn btn-sm btn-info" onclick="showCopyPresetModal(${preset.id})">Copy</button>
                <button class="btn btn-sm btn-danger" onclick="deletePreset(${preset.id})">Xóa</button>
            </td>
        </tr>
    `).join('');
}

function showPresetModal(presetId = null) {
    const modal = document.getElementById('presetModal');
    if (!modal) return;
    
    const title = document.getElementById('presetModalTitle');
    const form = document.getElementById('presetForm');
    const isActiveGroup = document.getElementById('presetIsActiveGroup');
    const caSelect = document.getElementById('presetCaSelect');
    
    form.reset();
    document.getElementById('presetId').value = '';
    
    caSelect.innerHTML = '<option value="">-- Chọn ca --</option>' +
        caListData.map(ca =>
            `<option value="${ca.id}">${escapeHtml(ca.ma_ca)} - ${escapeHtml(ca.ten_ca)}</option>`
        ).join('');
    
    if (presetId) {
        title.textContent = 'Sửa Preset';
        isActiveGroup.style.display = 'block';
        const preset = presetsData.find(p => p.id == presetId);
        if (preset) {
            document.getElementById('presetId').value = preset.id;
            document.getElementById('presetTenSet').value = preset.ten_set;
            document.getElementById('presetCaSelect').value = preset.ca_id;
            document.getElementById('presetIsDefault').checked = preset.is_default == 1;
            document.getElementById('presetIsActive').checked = preset.is_active == 1;
        }
    } else {
        title.textContent = 'Thêm Preset mới';
        isActiveGroup.style.display = 'none';
    }
    
    modal.classList.remove('hidden');
}

function editPreset(presetId) {
    showPresetModal(presetId);
}

async function handlePresetSubmit(e) {
    e.preventDefault();
    
    const presetId = document.getElementById('presetId').value;
    const ten_set = document.getElementById('presetTenSet').value.trim();
    const ca_id = parseInt(document.getElementById('presetCaSelect').value);
    const is_default = document.getElementById('presetIsDefault').checked ? 1 : 0;
    const is_active = document.getElementById('presetIsActive')?.checked ? 1 : 0;
    
    if (!ten_set || !ca_id) {
        showToast('Vui lòng nhập đầy đủ thông tin', 'error');
        return;
    }
    
    showLoading();
    try {
        let response;
        if (presetId) {
            response = await api('PUT', `/moc-gio-sets/${presetId}`, { ten_set, is_default, is_active });
        } else {
            response = await api('POST', '/moc-gio-sets', { ca_id, ten_set, is_default });
        }
        
        if (response.success) {
            showToast(response.message, 'success');
            closeModal('presetModal');
            
            // Partial Update Logic
            const caInfo = caListData.find(c => c.id == ca_id) || { ma_ca: '', ten_ca: '' };
            
            // Nếu set là default, bỏ default các preset khác cùng ca
            if (is_default === 1) {
                presetsData.forEach(p => {
                    if (p.ca_id == ca_id) {
                        p.is_default = 0;
                    }
                });
            }

            if (presetId) {
                // UPDATE
                const index = presetsData.findIndex(p => p.id == presetId);
                if (index !== -1) {
                    presetsData[index] = {
                        ...presetsData[index],
                        ten_set,
                        ca_id, // Cho phép đổi ca? API backend có thể chặn, nhưng cứ update state
                        ma_ca: caInfo.ma_ca,
                        ten_ca: caInfo.ten_ca,
                        is_default,
                        is_active
                    };
                    
                    // Nếu đang xem chi tiết preset này, update title modal
                    if (currentPresetDetail && currentPresetDetail.id == presetId) {
                        currentPresetDetail.ten_set = ten_set;
                        document.getElementById('presetDetailTitle').textContent = `Chi tiết: ${ten_set}`;
                    }
                }
            } else {
                // CREATE
                // API response cho create thường trả về id của item mới tạo trong data hoặc id field
                // Giả sử response.data chứa object mới hoặc id. 
                // Nếu API trả về ID:
                const newId = response.data?.id || response.id; 
                
                // Nếu API trả về full object thì dùng luôn, không thì construct
                const newPreset = response.data && typeof response.data === 'object' && response.data.ten_set ? response.data : {
                    id: newId,
                    ten_set,
                    ca_id,
                    ma_ca: caInfo.ma_ca,
                    ten_ca: caInfo.ten_ca,
                    is_default,
                    is_active: 1 // Default active on create usually
                };
                
                // Nếu response.data không có ma_ca/ten_ca, patch vào
                if (!newPreset.ma_ca) newPreset.ma_ca = caInfo.ma_ca;
                if (!newPreset.ten_ca) newPreset.ten_ca = caInfo.ten_ca;

                presetsData.push(newPreset);
            }
            
            renderPresetsTable();
        } else {
            showToast(response.message, 'error');
        }
    } catch (error) {
        console.error(error);
        showToast('Lỗi lưu preset', 'error');
    } finally {
        hideLoading();
    }
}

function deletePreset(presetId) {
    showConfirmModal('Bạn có chắc muốn xóa preset này?', async () => {
        showLoading();
        try {
            const response = await api('DELETE', `/moc-gio-sets/${presetId}`);
            if (response.success) {
                showToast(response.message, 'success');
                
                // Partial Update Logic
                presetsData = presetsData.filter(p => p.id != presetId);
                renderPresetsTable();
                
                // Nếu đang xem chi tiết preset bị xóa, đóng modal
                if (currentPresetDetail && currentPresetDetail.id == presetId) {
                    closeModal('presetDetailModal');
                    currentPresetDetail = null;
                }
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi xóa preset', 'error');
        } finally {
            hideLoading();
        }
        closeModal('confirmModal');
    });
}

async function viewPresetDetail(presetId) {
    try {
        const [presetResponse, linesResponse] = await Promise.all([
            api('GET', `/moc-gio-sets/${presetId}`),
            api('GET', `/moc-gio-sets/${presetId}/lines`)
        ]);
        
        if (presetResponse.success) {
            currentPresetDetail = presetResponse.data;
            assignedLines = linesResponse.success ? linesResponse.data : [];
            renderPresetDetailModal();
        } else {
            showToast(presetResponse.message, 'error');
        }
    } catch (error) {
        showToast('Lỗi tải chi tiết preset', 'error');
    }
}

function renderPresetDetailModal() {
    const modal = document.getElementById('presetDetailModal');
    if (!modal || !currentPresetDetail) return;
    
    document.getElementById('presetDetailTitle').textContent = `Chi tiết: ${currentPresetDetail.ten_set}`;
    
    // Render Mốc Giờ
    const mocGioContainer = document.getElementById('presetMocGioList');
    if (mocGioContainer) {
        const mocGios = currentPresetDetail.moc_gio || [];
        if (mocGios.length === 0) {
            mocGioContainer.innerHTML = '<p class="text-gray-500 italic w-full">Chưa có mốc giờ nào được thiết lập. Vui lòng thêm mốc giờ trong phần quản lý Mốc giờ.</p>';
        } else {
            // Sort theo thứ tự
            mocGios.sort((a, b) => a.thu_tu - b.thu_tu);
            
            mocGioContainer.innerHTML = mocGios.map(mg => `
                <div class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm font-medium border border-blue-100 flex items-center gap-2 shadow-sm">
                    <span class="font-bold">${escapeHtml(mg.gio)}</span>
                    <span class="text-blue-300">|</span>
                    <span class="text-gray-600 text-xs" title="Phút lũy kế">${mg.so_phut_hieu_dung_luy_ke}p</span>
                </div>
            `).join('');
        }
    }
    
    renderAssignedLinesSection();
    
    modal.classList.remove('hidden');
}

function renderAssignedLinesSection() {
    const linesContainer = document.getElementById('presetAssignedLines');
    if (!linesContainer) return;

    if (assignedLines.length === 0) {
        linesContainer.innerHTML = '<div class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300"><p>Chưa có LINE nào được gán</p><p class="text-xs mt-1">Sử dụng nút "Gán thêm LINE" để thêm</p></div>';
    } else {
        linesContainer.innerHTML = `
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Mã LINE</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tên LINE</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 text-right text-sm font-semibold text-gray-900 sm:pr-6">
                                Thao Tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        ${assignedLines.map((line, index) => `
                            <tr class="${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'} hover:bg-gray-100 transition-colors">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">${escapeHtml(line.ma_line)}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${escapeHtml(line.ten_line)}</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <button class="text-red-600 hover:text-red-900 px-3 py-1 rounded-md hover:bg-red-50 transition-colors border border-transparent hover:border-red-200" onclick="unassignLine(${currentPresetDetail.id}, ${line.line_id})">
                                        Bỏ gán
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            <div class="mt-2 text-right text-xs text-gray-500">
                Tổng số: <span class="font-semibold text-gray-700">${assignedLines.length}</span> LINE
            </div>
        `;
    }
}

async function showAssignLinesModal(presetId) {
    const preset = presetsData.find(p => p.id == presetId);
    if (!preset) return;
    
    try {
        const response = await api('GET', `/moc-gio-sets/unassigned-lines?ca_id=${preset.ca_id}`);
        if (response.success) {
            const unassignedLines = response.data;
            const modal = document.getElementById('assignLinesModal');
            if (!modal) return;
            
            document.getElementById('assignLinesPresetId').value = presetId;
            document.getElementById('assignLinesTitle').textContent = `Gán LINE cho: ${preset.ten_set}`;
            
            // Reset search input
            const searchInput = document.getElementById('assignLinesSearch');
            if (searchInput) searchInput.value = '';
            
            const container = document.getElementById('unassignedLinesContainer');
            if (unassignedLines.length === 0) {
                container.innerHTML = '<p class="text-gray-500 col-span-full text-center py-4">Không có LINE nào chưa được gán hoặc tất cả đã được gán</p>';
            } else {
                container.innerHTML = unassignedLines.map(line => `
                    <label class="checkbox-item flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-all bg-white shadow-sm group">
                        <input type="checkbox" name="line_ids" value="${line.id}" class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary group-hover:scale-110 transition-transform">
                        <div class="flex flex-col overflow-hidden">
                            <span class="text-gray-800 font-bold truncate group-hover:text-primary transition-colors">${escapeHtml(line.ma_line)}</span>
                            <span class="text-gray-500 text-xs truncate">${escapeHtml(line.ten_line)}</span>
                        </div>
                    </label>
                `).join('');
            }
            
            modal.classList.remove('hidden');
        } else {
            showToast(response.message, 'error');
        }
    } catch (error) {
        showToast('Lỗi tải danh sách LINE', 'error');
    }
}

async function handleAssignLines(e) {
    e.preventDefault();
    
    const presetId = document.getElementById('assignLinesPresetId').value;
    const checkboxes = document.querySelectorAll('#unassignedLinesContainer input[name="line_ids"]:checked');
    const line_ids = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (line_ids.length === 0) {
        showToast('Vui lòng chọn ít nhất một LINE', 'error');
        return;
    }
    
    showLoading();
    try {
        const response = await api('POST', `/moc-gio-sets/${presetId}/lines`, { line_ids });
        if (response.success) {
            showToast(response.message, 'success');
            closeModal('assignLinesModal');
            
            const newLines = linesData
                .filter(l => line_ids.includes(Number(l.id)))
                .map(l => ({ line_id: Number(l.id), ma_line: l.ma_line, ten_line: l.ten_line }));
            
            const currentIds = new Set(assignedLines.map(l => Number(l.line_id)));
            const linesToAdd = newLines.filter(l => !currentIds.has(l.line_id));
            
            assignedLines = [...assignedLines, ...linesToAdd];
            renderAssignedLinesSection();
        } else {
            showToast(response.message, 'error');
        }
    } catch (error) {
        showToast('Lỗi gán LINE', 'error');
    } finally {
        hideLoading();
    }
}

function unassignLine(presetId, lineId) {
    showConfirmModal('Bạn có chắc muốn bỏ gán LINE này?', async () => {
        showLoading();
        try {
            const response = await api('DELETE', `/moc-gio-sets/${presetId}/lines`, { line_ids: [lineId] });
            if (response.success) {
                showToast(response.message, 'success');
                
                assignedLines = assignedLines.filter(l => Number(l.line_id) !== Number(lineId));
                renderAssignedLinesSection();
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi bỏ gán LINE', 'error');
        } finally {
            hideLoading();
        }
        closeModal('confirmModal');
    });
}

function showCopyPresetModal(presetId) {
    const preset = presetsData.find(p => p.id == presetId);
    if (!preset) return;
    
    const modal = document.getElementById('copyPresetModal');
    if (!modal) return;
    
    document.getElementById('copyPresetSourceId').value = presetId;
    document.getElementById('copyPresetNewName').value = `${preset.ten_set} (Copy)`;
    
    modal.classList.remove('hidden');
}

async function handleCopyPreset(e) {
    e.preventDefault();
    
    const source_set_id = parseInt(document.getElementById('copyPresetSourceId').value);
    const ten_set = document.getElementById('copyPresetNewName').value.trim();
    
    if (!ten_set) {
        showToast('Vui lòng nhập tên preset mới', 'error');
        return;
    }
    
    showLoading();
    try {
        const response = await api('POST', '/moc-gio-sets/copy', { source_set_id, ten_set });
        if (response.success) {
            showToast(response.message, 'success');
            closeModal('copyPresetModal');
            
            // Partial Update Logic
            // Lấy thông tin preset nguồn để copy metadata (ca_id, ma_ca, ten_ca)
            const sourcePreset = presetsData.find(p => p.id == source_set_id);
            
            // API copy thường trả về ID mới hoặc object mới
            // Giả sử API trả về { success: true, message: "...", data: { id: 123, ... } } hoặc data là ID
            const newId = response.data?.id || response.id;
            
            const newPreset = response.data && typeof response.data === 'object' && response.data.ten_set ? response.data : {
                id: newId,
                ten_set,
                ca_id: sourcePreset ? sourcePreset.ca_id : null,
                ma_ca: sourcePreset ? sourcePreset.ma_ca : '',
                ten_ca: sourcePreset ? sourcePreset.ten_ca : '',
                is_default: 0, // Copy thường không set default ngay
                is_active: 1
            };
            
            if (!newPreset.ma_ca && sourcePreset) newPreset.ma_ca = sourcePreset.ma_ca;
            if (!newPreset.ten_ca && sourcePreset) newPreset.ten_ca = sourcePreset.ten_ca;
            
            presetsData.push(newPreset);
            renderPresetsTable();
        } else {
            showToast(response.message, 'error');
        }
    } catch (error) {
        showToast('Lỗi copy preset', 'error');
    } finally {
        hideLoading();
    }
}

function bindPresetEvents() {
    const presetForm = document.getElementById('presetForm');
    if (presetForm) {
        presetForm.addEventListener('submit', handlePresetSubmit);
    }
    
    const addPresetBtn = document.getElementById('addPresetBtn');
    if (addPresetBtn) {
        addPresetBtn.addEventListener('click', () => showPresetModal());
    }
    
    const assignLinesForm = document.getElementById('assignLinesForm');
    if (assignLinesForm) {
        assignLinesForm.addEventListener('submit', handleAssignLines);
    }
    
    const copyPresetForm = document.getElementById('copyPresetForm');
    if (copyPresetForm) {
        copyPresetForm.addEventListener('submit', handleCopyPreset);
    }

    const assignLinesSearch = document.getElementById('assignLinesSearch');
    if (assignLinesSearch) {
        assignLinesSearch.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase().trim();
            const items = document.querySelectorAll('#unassignedLinesContainer .checkbox-item');
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(term)) {
                    item.classList.remove('hidden');
                    item.style.display = 'flex';
                } else {
                    item.classList.add('hidden');
                    item.style.display = 'none';
                }
            });
        });
    }
}

document.addEventListener('DOMContentLoaded', bindPresetEvents);

window.viewPresetDetail = viewPresetDetail;
window.editPreset = editPreset;
window.deletePreset = deletePreset;
window.showCopyPresetModal = showCopyPresetModal;
window.showAssignLinesModal = showAssignLinesModal;
window.unassignLine = unassignLine;
