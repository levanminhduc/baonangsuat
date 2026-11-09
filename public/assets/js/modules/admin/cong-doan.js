import { api } from '../admin-api.js';
import { escapeHtml, showToast, closeModal, showConfirmModal } from '../admin-utils.js';
import { getState, setCongDoan } from './state.js';

export async function loadCongDoan() {
    try {
        const response = await api('GET', '/admin/cong-doan');
        if (response.success) {
            setCongDoan(response.data);
            renderCongDoanTable();
        }
    } catch (error) {
        showToast('Lỗi tải danh sách công đoạn', 'error');
    }
}

export function renderCongDoanTable() {
    const state = getState();
    const tbody = document.querySelector('#congDoanTable tbody');
    if (state.congDoan.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Chưa có công đoạn nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = state.congDoan.map(cd => `
        <tr>
            <td>${cd.id}</td>
            <td>${escapeHtml(cd.ma_cong_doan)}</td>
            <td>${escapeHtml(cd.ten_cong_doan)}</td>
            <td><span class="status-badge ${cd.la_cong_doan_thanh_pham == 1 ? 'status-approved' : 'status-draft'}">${cd.la_cong_doan_thanh_pham == 1 ? 'Có' : 'Không'}</span></td>
            <td><span class="status-badge ${cd.is_active == 1 ? 'status-approved' : 'status-locked'}">${cd.is_active == 1 ? 'Hoạt động' : 'Tắt'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="window.editCongDoan(${cd.id})">Sửa</button>
                <button class="btn btn-sm btn-danger" onclick="window.deleteCongDoan(${cd.id})">Xóa</button>
            </td>
        </tr>
    `).join('');
}

export function showCongDoanModal(congDoanId = null) {
    const state = getState();
    const modal = document.getElementById('congDoanModal');
    const title = document.getElementById('congDoanModalTitle');
    const form = document.getElementById('congDoanForm');
    const isActiveGroup = document.getElementById('congDoanIsActiveGroup');
    
    form.reset();
    document.getElementById('congDoanId').value = '';
    
    if (congDoanId) {
        title.textContent = 'Sửa Công đoạn';
        isActiveGroup.style.display = 'block';
        const cd = state.congDoan.find(c => c.id == congDoanId);
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

export function editCongDoan(congDoanId) {
    showCongDoanModal(congDoanId);
}

export async function handleCongDoanSubmit(e) {
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

export function deleteCongDoan(congDoanId) {
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
    }, 'Xác nhận xóa', 'danger');
}

export function bindEvents() {
    document.getElementById('addCongDoanBtn').addEventListener('click', () => showCongDoanModal());
    document.getElementById('congDoanForm').addEventListener('submit', handleCongDoanSubmit);
}

export function init() {
    bindEvents();
}
