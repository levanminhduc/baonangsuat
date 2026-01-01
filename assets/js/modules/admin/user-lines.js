import { api } from '../admin-api.js';
import { escapeHtml, showToast, closeModal, showConfirmModal } from '../admin-utils.js';
import { getState, setUserLines, setUserLineFilterLineId } from './state.js';

export async function loadUserLines() {
    try {
        const response = await api('GET', '/admin/user-lines');
        if (response.success) {
            setUserLines(response.data);
            renderUserLinesTable();
        }
    } catch (error) {
        showToast('Lỗi tải danh sách mapping', 'error');
    }
}

export function renderUserLinesTable() {
    const state = getState();
    const tbody = document.querySelector('#userLinesTable tbody');
    
    let filteredData = state.userLines;
    if (state.userLineFilterLineId) {
        filteredData = state.userLines.filter(ul => ul.line_id == state.userLineFilterLineId);
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
                <button class="btn btn-sm btn-danger" onclick="window.deleteUserLine('${escapeHtml(ul.ma_nv)}', ${ul.line_id})">Xóa</button>
            </td>
        </tr>
    `).join('');
}

export function handleUserLineFilterChange() {
    const lineId = document.getElementById('userLineFilterLine').value;
    setUserLineFilterLineId(lineId);
    renderUserLinesTable();
}

export function updateUserLineFilterSelect() {
    const state = getState();
    const select = document.getElementById('userLineFilterLine');
    if (!select) return;
    
    // Ensure lines are loaded (dependency check)
    if (!state.lines || state.lines.length === 0) {
        console.warn('Lines not loaded yet for user-lines filter');
    }
    
    select.innerHTML = '<option value="">-- Tất cả LINE --</option>' +
        state.lines.map(l =>
            `<option value="${l.id}">${escapeHtml(l.ma_line)} - ${escapeHtml(l.ten_line)}</option>`
        ).join('');
}

export function handleUserSearch(e) {
    const state = getState();
    const searchTerm = e.target.value.toLowerCase().trim();
    const select = document.getElementById('userSelect');
    const currentValue = select.value;
    
    let filteredOptions = state.allUsersOptions;
    if (searchTerm) {
        filteredOptions = state.allUsersOptions.filter(opt => opt.searchText.includes(searchTerm));
    }
    
    select.innerHTML = '<option value="">-- Chọn User --</option>' +
        filteredOptions.map(opt =>
            `<option value="${escapeHtml(opt.value)}" ${opt.value === currentValue ? 'selected' : ''}>${escapeHtml(opt.label)}</option>`
        ).join('');
}

export function showUserLineModal() {
    const state = getState();
    const modal = document.getElementById('userLineModal');
    const form = document.getElementById('userLineForm');
    const lineSelect = document.getElementById('lineSelect');
    const userSelect = document.getElementById('userSelect');
    const userSearchInput = document.getElementById('userSearchInput');
    
    form.reset();
    userSearchInput.value = '';
    
    // Need users loaded first (usually done by permissions module or we might need to load them here too if isolated)
    // For now assuming allUsersOptions is populated or we might need to fetch users if empty
    if (state.allUsersOptions.length === 0) {
        // Fallback or better approach: user-lines should probably depend on 'permissions' or a shared 'users' module
        // But per current scope, we just render what we have. 
        // In fully lazy loaded system, we might need to fetch users here if permissions tab wasn't visited.
        // Let's assume for now we might need to fetch users if empty.
        // But strictly following current task, we just ensure lines are there.
    }

    userSelect.innerHTML = '<option value="">-- Chọn User --</option>' +
        state.allUsersOptions.map(opt =>
            `<option value="${escapeHtml(opt.value)}">${escapeHtml(opt.label)}</option>`
        ).join('');
    
    lineSelect.innerHTML = '<option value="">-- Chọn LINE --</option>' +
        state.lines.filter(l => l.is_active == 1).map(l =>
            `<option value="${l.id}">${escapeHtml(l.ma_line)} - ${escapeHtml(l.ten_line)}</option>`
        ).join('');
    
    modal.classList.remove('hidden');
}

export async function handleUserLineSubmit(e) {
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

export function deleteUserLine(ma_nv, line_id) {
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

export function bindEvents() {
    document.getElementById('addUserLineBtn').addEventListener('click', () => showUserLineModal());
    document.getElementById('userLineForm').addEventListener('submit', handleUserLineSubmit);
    document.getElementById('userLineFilterLine').addEventListener('change', handleUserLineFilterChange);
    document.getElementById('userSearchInput').addEventListener('input', handleUserSearch);
}

export function init() {
    bindEvents();
}
