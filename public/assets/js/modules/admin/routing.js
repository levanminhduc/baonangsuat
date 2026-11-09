import { api } from '../admin-api.js';
import { escapeHtml, showToast, closeModal, showConfirmModal } from '../admin-utils.js';
import { getState, setRouting, setSelectedMaHangId } from './state.js';

export async function handleRoutingMaHangChange() {
    const state = getState();
    const maHangId = document.getElementById('routingMaHangSelect').value;
    const container = document.getElementById('routingTableContainer');
    
    if (!maHangId) {
        container.style.display = 'none';
        setSelectedMaHangId(null);
        return;
    }
    
    setSelectedMaHangId(parseInt(maHangId));
    // Check if maHang exists, if not log warning but don't break as user might just need name
    // Dependency on 'ma-hang' module is handled by admin.js loader
    const mh = state.maHang.find(m => m.id == state.selectedMaHangId);
    document.getElementById('routingTitle').textContent = `Routing cho: ${mh ? mh.ma_hang + ' - ' + mh.ten_hang : ''}`;
    
    await loadRouting(state.selectedMaHangId);
    container.style.display = 'block';
}

export async function loadRouting(maHangId) {
    try {
        const response = await api('GET', `/admin/routing?ma_hang_id=${maHangId}`);
        if (response.success) {
            setRouting(response.data);
            renderRoutingTable();
        }
    } catch (error) {
        showToast('Lỗi tải routing', 'error');
    }
}

export function renderRoutingTable() {
    const state = getState();
    const tbody = document.querySelector('#routingTable tbody');
    if (state.routing.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Chưa có công đoạn nào trong routing</td></tr>';
        return;
    }
    
    tbody.innerHTML = state.routing.map(r => `
        <tr>
            <td>${r.thu_tu}</td>
            <td>${escapeHtml(r.ma_cong_doan)} - ${escapeHtml(r.ten_cong_doan)}</td>
            <td>${r.ma_line ? escapeHtml(r.ma_line) : 'Tất cả'}</td>
            <td><span class="status-badge ${r.la_cong_doan_tinh_luy_ke == 1 ? 'status-approved' : 'status-draft'}">${r.la_cong_doan_tinh_luy_ke == 1 ? 'Có' : 'Không'}</span></td>
            <td><span class="status-badge ${r.bat_buoc == 1 ? 'status-approved' : 'status-draft'}">${r.bat_buoc == 1 ? 'Có' : 'Không'}</span></td>
            <td>${escapeHtml(r.ghi_chu || '')}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="window.editRouting(${r.id})">Sửa</button>
                <button class="btn btn-sm btn-danger" onclick="window.deleteRouting(${r.id})">Xóa</button>
            </td>
        </tr>
    `).join('');
}

export function showRoutingModal(routingId = null) {
    const state = getState();
    if (!state.selectedMaHangId) {
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
    document.getElementById('routingMaHangId').value = state.selectedMaHangId;
    document.getElementById('routingBatBuoc').checked = true;
    document.getElementById('routingTinhLuyKe').checked = false;
    
    congDoanSelect.innerHTML = '<option value="">-- Chọn công đoạn --</option>' +
        state.congDoan.filter(cd => cd.is_active == 1).map(cd =>
            `<option value="${cd.id}">${escapeHtml(cd.ma_cong_doan)} - ${escapeHtml(cd.ten_cong_doan)}</option>`
        ).join('');
    
    lineSelect.innerHTML = '<option value="">-- Tất cả LINE --</option>' +
        state.lines.filter(l => l.is_active == 1).map(l =>
            `<option value="${l.id}">${escapeHtml(l.ma_line)} - ${escapeHtml(l.ten_line)}</option>`
        ).join('');
    
    if (routingId) {
        title.textContent = 'Sửa Routing';
        congDoanGroup.style.display = 'none';
        const r = state.routing.find(rt => rt.id == routingId);
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
        const maxThuTu = state.routing.length > 0 ? Math.max(...state.routing.map(r => r.thu_tu)) : 0;
        document.getElementById('routingThuTu').value = maxThuTu + 1;
    }
    
    modal.classList.remove('hidden');
}

export function editRouting(routingId) {
    showRoutingModal(routingId);
}

export async function handleRoutingSubmit(e) {
    e.preventDefault();
    
    const state = getState();
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
            loadRouting(state.selectedMaHangId);
        } else {
            showToast(response.message, 'error');
        }
    } catch (error) {
        showToast('Lỗi lưu routing', 'error');
    }
}

export function deleteRouting(routingId) {
    const state = getState();
    showConfirmModal('Bạn có chắc muốn xóa công đoạn này khỏi routing?', async () => {
        try {
            const response = await api('DELETE', `/admin/routing/${routingId}`);
            if (response.success) {
                showToast(response.message, 'success');
                loadRouting(state.selectedMaHangId);
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi xóa routing', 'error');
        }
        closeModal('confirmModal');
    });
}

export function bindEvents() {
    document.getElementById('addRoutingBtn').addEventListener('click', () => showRoutingModal());
    document.getElementById('routingForm').addEventListener('submit', handleRoutingSubmit);
    document.getElementById('routingMaHangSelect').addEventListener('change', handleRoutingMaHangChange);
}

export function init() {
    bindEvents();
}
