import { api } from '../admin-api.js';
import { escapeHtml, showToast, closeModal, showConfirmModal } from '../admin-utils.js';
import { getState, setMocGio, setCaList, setSelectedCaId, setSelectedLineIdForMocGio } from './state.js';

export async function loadCaList() {
    try {
        const response = await api('GET', '/admin/moc-gio/ca-list');
        if (response.success) {
            setCaList(response.data);
            updateMocGioCaSelect();
            updateMocGioLineSelect();
        }
    } catch (error) {
        showToast('Lỗi tải danh sách ca', 'error');
    }
}

export function updateMocGioCaSelect() {
    const state = getState();
    const select = document.getElementById('mocGioCaSelect');
    if (!select) return;

    select.innerHTML = '<option value="">-- Chọn ca --</option>' +
        state.caList.map(ca =>
            `<option value="${ca.id}">${escapeHtml(ca.ma_ca)} - ${escapeHtml(ca.ten_ca)}</option>`
        ).join('');
}

export function updateMocGioLineSelect() {
    const state = getState();
    const select = document.getElementById('mocGioLineSelect');
    if (!select) return;

    select.innerHTML = '<option value="">-- Tất cả (xem default) --</option>' +
        '<option value="default">Mốc giờ mặc định</option>' +
        state.lines.filter(l => l.is_active == 1).map(l =>
            `<option value="${l.id}">${escapeHtml(l.ma_line)} - ${escapeHtml(l.ten_line)}</option>`
        ).join('');
}

export async function handleMocGioFilterChange() {
    const state = getState();
    const caId = document.getElementById('mocGioCaSelect').value;
    const lineId = document.getElementById('mocGioLineSelect').value;
    const container = document.getElementById('mocGioTableContainer');
    const copyBtn = document.getElementById('copyDefaultBtn');
    const fallbackNotice = document.getElementById('mocGioFallbackNotice');
    
    if (!caId) {
        container.style.display = 'none';
        setSelectedCaId(null);
        setSelectedLineIdForMocGio(null);
        return;
    }
    
    setSelectedCaId(parseInt(caId));
    setSelectedLineIdForMocGio(lineId);
    
    const ca = state.caList.find(c => c.id == state.selectedCaId);
    let titleText = `Mốc giờ: ${ca ? ca.ma_ca + ' - ' + ca.ten_ca : ''}`;
    
    if (lineId && lineId !== 'default') {
        const line = state.lines.find(l => l.id == lineId);
        titleText += ` - ${line ? line.ten_line : 'LINE ' + lineId}`;
        copyBtn.style.display = 'inline-block';
    } else {
        titleText += ' (Mặc định)';
        copyBtn.style.display = 'none';
    }
    
    document.getElementById('mocGioTitle').textContent = titleText;
    
    await loadMocGio(caId, lineId === 'default' ? 'default' : lineId);
    container.style.display = 'block';
    
    const hasLineSpecific = state.mocGio.length > 0 && state.mocGio.some(m => m.line_id != null);
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

export async function loadMocGio(caId, lineId) {
    try {
        let url = `/admin/moc-gio?ca_id=${caId}`;
        if (lineId && lineId !== '') {
            url += `&line_id=${lineId}`;
        }
        const response = await api('GET', url);
        if (response.success) {
            setMocGio(response.data);
            renderMocGioTable();
        }
    } catch (error) {
        showToast('Lỗi tải mốc giờ', 'error');
    }
}

export function renderMocGioTable() {
    const state = getState();
    const tbody = document.querySelector('#mocGioTable tbody');
    if (state.mocGio.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Chưa có mốc giờ nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = state.mocGio.map(mg => {
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
                <button class="btn btn-sm btn-primary" onclick="window.editMocGio(${mg.id})">Sửa</button>
                ${canDelete ? `<button class="btn btn-sm btn-danger" onclick="window.deleteMocGio(${mg.id})">Xóa</button>` : ''}
            </td>
        </tr>
    `}).join('');
}

export function showMocGioModal(mocGioId = null) {
    const state = getState();
    if (!state.selectedCaId) {
        showToast('Vui lòng chọn ca trước', 'error');
        return;
    }
    
    const modal = document.getElementById('mocGioModal');
    const title = document.getElementById('mocGioModalTitle');
    const form = document.getElementById('mocGioForm');
    const isActiveGroup = document.getElementById('mocGioIsActiveGroup');
    
    form.reset();
    document.getElementById('mocGioId').value = '';
    document.getElementById('mocGioFormCaId').value = state.selectedCaId;
    
    const lineIdForForm = (state.selectedLineIdForMocGio && state.selectedLineIdForMocGio !== 'default') ? state.selectedLineIdForMocGio : '';
    document.getElementById('mocGioFormLineId').value = lineIdForForm;
    
    if (mocGioId) {
        title.textContent = 'Sửa Mốc giờ';
        isActiveGroup.style.display = 'block';
        const mg = state.mocGio.find(m => m.id == mocGioId);
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
        const maxThuTu = state.mocGio.length > 0 ? Math.max(...state.mocGio.map(m => m.thu_tu)) : 0;
        document.getElementById('mocGioThuTu').value = maxThuTu + 1;
        const lastMoc = state.mocGio[state.mocGio.length - 1];
        document.getElementById('mocGioPhutLuyKe').value = lastMoc ? lastMoc.so_phut_hieu_dung_luy_ke : 0;
    }
    
    modal.classList.remove('hidden');
}

export function editMocGio(mocGioId) {
    showMocGioModal(mocGioId);
}

export async function handleMocGioSubmit(e) {
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

export function deleteMocGio(mocGioId) {
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

export async function handleCopyDefault() {
    const state = getState();
    if (!state.selectedCaId || !state.selectedLineIdForMocGio || state.selectedLineIdForMocGio === 'default') {
        showToast('Vui lòng chọn ca và LINE cụ thể', 'error');
        return;
    }
    
    showConfirmModal('Copy mốc giờ mặc định sang LINE này?', async () => {
        try {
            const response = await api('POST', '/admin/moc-gio/copy-default', {
                ca_id: state.selectedCaId,
                line_id: parseInt(state.selectedLineIdForMocGio)
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

export function bindEvents() {
    document.getElementById('addMocGioBtn').addEventListener('click', () => showMocGioModal());
    document.getElementById('mocGioForm').addEventListener('submit', handleMocGioSubmit);
    document.getElementById('mocGioCaSelect').addEventListener('change', handleMocGioFilterChange);
    document.getElementById('mocGioLineSelect').addEventListener('change', handleMocGioFilterChange);
    document.getElementById('copyDefaultBtn').addEventListener('click', handleCopyDefault);
}

export function init() {
    bindEvents();
}
