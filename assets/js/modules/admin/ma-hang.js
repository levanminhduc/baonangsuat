import { api } from '../admin-api.js';
import { escapeHtml, showToast, closeModal, showConfirmModal } from '../admin-utils.js';
import { getState, setMaHang } from './state.js';

export async function loadMaHang() {
    try {
        const response = await api('GET', '/admin/ma-hang');
        if (response.success) {
            setMaHang(response.data);
            renderMaHangTable();
            updateRoutingMaHangSelect();
        }
    } catch (error) {
        showToast('Lỗi tải danh sách mã hàng', 'error');
    }
}

export function renderMaHangTable() {
    const state = getState();
    const tbody = document.querySelector('#maHangTable tbody');
    if (state.maHang.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Chưa có mã hàng nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = state.maHang.map(mh => `
        <tr>
            <td>${mh.id}</td>
            <td>${escapeHtml(mh.ma_hang)}</td>
            <td>${escapeHtml(mh.ten_hang)}</td>
            <td><span class="status-badge ${mh.is_active == 1 ? 'status-approved' : 'status-locked'}">${mh.is_active == 1 ? 'Hoạt động' : 'Tắt'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="window.editMaHang(${mh.id})">Sửa</button>
                <button class="btn btn-sm btn-danger" onclick="window.deleteMaHang(${mh.id})">Xóa</button>
            </td>
        </tr>
    `).join('');
}

export function updateRoutingMaHangSelect() {
    const state = getState();
    const select = document.getElementById('routingMaHangSelect');
    if (!select) return;

    select.innerHTML = '<option value="">-- Chọn mã hàng --</option>' +
        state.maHang.filter(mh => mh.is_active == 1).map(mh =>
            `<option value="${mh.id}">${escapeHtml(mh.ma_hang)} - ${escapeHtml(mh.ten_hang)}</option>`
        ).join('');
}

export function showMaHangModal(maHangId = null) {
    const state = getState();
    const modal = document.getElementById('maHangModal');
    const title = document.getElementById('maHangModalTitle');
    const form = document.getElementById('maHangForm');
    const isActiveGroup = document.getElementById('maHangIsActiveGroup');
    
    form.reset();
    document.getElementById('maHangId').value = '';
    
    if (maHangId) {
        title.textContent = 'Sửa Mã hàng';
        isActiveGroup.style.display = 'block';
        const mh = state.maHang.find(m => m.id == maHangId);
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

export function editMaHang(maHangId) {
    showMaHangModal(maHangId);
}

export async function handleMaHangSubmit(e) {
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

export function deleteMaHang(maHangId) {
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
    }, 'Xác nhận xóa', 'danger');
}

export function bindEvents() {
    document.getElementById('addMaHangBtn').addEventListener('click', () => showMaHangModal());
    document.getElementById('maHangForm').addEventListener('submit', handleMaHangSubmit);
}

export function init() {
    bindEvents();
}
