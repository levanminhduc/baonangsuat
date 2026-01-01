import { api } from '../admin-api.js';
import { escapeHtml, showToast, closeModal, showConfirmModal } from '../admin-utils.js';
import { getState, setLines } from './state.js';

export async function loadLines() {
    try {
        const response = await api('GET', '/admin/lines');
        if (response.success) {
            setLines(response.data);
            renderLinesTable();
        }
    } catch (error) {
        showToast('Lỗi tải danh sách LINE', 'error');
    }
}

export function renderLinesTable() {
    const state = getState();
    const tbody = document.querySelector('#linesTable tbody');
    if (state.lines.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Chưa có LINE nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = state.lines.map(line => `
        <tr>
            <td>${line.id}</td>
            <td>${escapeHtml(line.ma_line)}</td>
            <td>${escapeHtml(line.ten_line)}</td>
            <td><span class="status-badge ${line.is_active == 1 ? 'status-approved' : 'status-locked'}">${line.is_active == 1 ? 'Hoạt động' : 'Tắt'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="window.editLine(${line.id})">Sửa</button>
                <button class="btn btn-sm btn-danger" onclick="window.deleteLine(${line.id})">Xóa</button>
            </td>
        </tr>
    `).join('');
}

export function showLineModal(lineId = null) {
    const state = getState();
    const modal = document.getElementById('lineModal');
    const title = document.getElementById('lineModalTitle');
    const form = document.getElementById('lineForm');
    const isActiveGroup = document.getElementById('isActiveGroup');
    
    form.reset();
    document.getElementById('lineId').value = '';
    
    if (lineId) {
        title.textContent = 'Sửa LINE';
        isActiveGroup.style.display = 'block';
        const line = state.lines.find(l => l.id == lineId);
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

export function editLine(lineId) {
    showLineModal(lineId);
}

export async function handleLineSubmit(e) {
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

export function deleteLine(lineId) {
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

export function bindEvents() {
    document.getElementById('addLineBtn').addEventListener('click', () => showLineModal());
    document.getElementById('lineForm').addEventListener('submit', handleLineSubmit);
}

export function init() {
    bindEvents();
}
