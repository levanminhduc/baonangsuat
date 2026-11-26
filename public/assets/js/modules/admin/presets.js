import { api } from '../api.js';
import { escapeHtml, showToast, closeModal, showConfirmModal, showLoading, hideLoading } from '../utils.js';
import { getState, setPresets, setCurrentPresetDetail, setAssignedLines } from './state.js';
import { registerActions } from './actions.js';
import { renderAssignedLinesSection, bindPresetLinesEvents } from './preset-lines.js';

export async function loadPresets() {
    try {
        const response = await api('GET', '/moc-gio-sets');
        if (response.success) {
            setPresets(response.data);
            renderPresetsTable();
        }
    } catch (error) {
        showToast('Lỗi tải danh sách preset', 'error');
    }
}

export function renderPresetsTable() {
    const state = getState();
    const tbody = document.querySelector('#presetsTable tbody');
    if (!tbody) return;
    
    if (state.presets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Chưa có preset nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = state.presets.map(preset => `
        <tr>
            <td>${preset.id}</td>
            <td>${escapeHtml(preset.ten_set)}</td>
            <td>${escapeHtml(preset.ma_ca || '')} - ${escapeHtml(preset.ten_ca || '')}</td>
            <td><span class="status-badge ${preset.is_default == 1 ? 'status-approved' : 'status-draft'}">${preset.is_default == 1 ? 'Có' : 'Không'}</span></td>
            <td><span class="status-badge ${preset.is_active == 1 ? 'status-approved' : 'status-locked'}">${preset.is_active == 1 ? 'Hoạt động' : 'Tắt'}</span></td>
            <td>
                <button class="btn btn-sm btn-secondary" data-fn="viewPresetDetail" data-args="[${preset.id}]">Chi tiết</button>
                <button class="btn btn-sm btn-primary" data-fn="editPreset" data-args="[${preset.id}]">Sửa</button>
                <button class="btn btn-sm btn-info" data-fn="showCopyPresetModal" data-args="[${preset.id}]">Copy</button>
                <button class="btn btn-sm btn-danger" data-fn="deletePreset" data-args="[${preset.id}]">Xóa</button>
            </td>
        </tr>
    `).join('');
}

export function showPresetModal(presetId = null) {
    const state = getState();
    const modal = document.getElementById('presetModal');
    if (!modal) return;
    
    const title = document.getElementById('presetModalTitle');
    const form = document.getElementById('presetForm');
    const isActiveGroup = document.getElementById('presetIsActiveGroup');
    const caSelect = document.getElementById('presetCaSelect');
    
    form.reset();
    document.getElementById('presetId').value = '';
    
    caSelect.innerHTML = '<option value="">-- Chọn ca --</option>' +
        state.caList.map(ca =>
            `<option value="${ca.id}">${escapeHtml(ca.ma_ca)} - ${escapeHtml(ca.ten_ca)}</option>`
        ).join('');
    
    if (presetId) {
        title.textContent = 'Sửa Preset';
        isActiveGroup.style.display = 'block';
        const preset = state.presets.find(p => p.id == presetId);
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

export function editPreset(presetId) {
    showPresetModal(presetId);
}

export async function handlePresetSubmit(e) {
    e.preventDefault();
    
    const state = getState();
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
            
            const caInfo = state.caList.find(c => c.id == ca_id) || { ma_ca: '', ten_ca: '' };
            
            if (is_default === 1) {
                state.presets.forEach(p => {
                    if (p.ca_id == ca_id) {
                        p.is_default = 0;
                    }
                });
            }

            if (presetId) {
                const index = state.presets.findIndex(p => p.id == presetId);
                if (index !== -1) {
                    state.presets[index] = {
                        ...state.presets[index],
                        ten_set,
                        ca_id,
                        ma_ca: caInfo.ma_ca,
                        ten_ca: caInfo.ten_ca,
                        is_default,
                        is_active
                    };
                    
                    if (state.currentPresetDetail && state.currentPresetDetail.id == presetId) {
                        state.currentPresetDetail.ten_set = ten_set;
                        document.getElementById('presetDetailTitle').textContent = `Chi tiết: ${ten_set}`;
                    }
                }
            } else {
                const newId = response.data?.id || response.id; 
                
                const newPreset = response.data && typeof response.data === 'object' && response.data.ten_set ? response.data : {
                    id: newId,
                    ten_set,
                    ca_id,
                    ma_ca: caInfo.ma_ca,
                    ten_ca: caInfo.ten_ca,
                    is_default,
                    is_active: 1
                };
                
                if (!newPreset.ma_ca) newPreset.ma_ca = caInfo.ma_ca;
                if (!newPreset.ten_ca) newPreset.ten_ca = caInfo.ten_ca;

                state.presets.push(newPreset);
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

export function deletePreset(presetId) {
    const state = getState();
    showConfirmModal('Bạn có chắc muốn xóa preset này?', async () => {
        showLoading();
        try {
            const response = await api('DELETE', `/moc-gio-sets/${presetId}`);
            if (response.success) {
                showToast(response.message, 'success');
                
                const index = state.presets.findIndex(p => p.id == presetId);
                if (index !== -1) {
                    state.presets.splice(index, 1);
                }
                renderPresetsTable();
                
                if (state.currentPresetDetail && state.currentPresetDetail.id == presetId) {
                    closeModal('presetDetailModal');
                    setCurrentPresetDetail(null);
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

export async function viewPresetDetail(presetId) {
    try {
        const [presetResponse, linesResponse] = await Promise.all([
            api('GET', `/moc-gio-sets/${presetId}`),
            api('GET', `/moc-gio-sets/${presetId}/lines`)
        ]);
        
        if (presetResponse.success) {
            setCurrentPresetDetail(presetResponse.data);
            setAssignedLines(linesResponse.success ? linesResponse.data : []);
            renderPresetDetailModal();
        } else {
            showToast(presetResponse.message, 'error');
        }
    } catch (error) {
        showToast('Lỗi tải chi tiết preset', 'error');
    }
}

export function renderPresetDetailModal() {
    const state = getState();
    const modal = document.getElementById('presetDetailModal');
    if (!modal || !state.currentPresetDetail) return;
    
    document.getElementById('presetDetailTitle').textContent = `Chi tiết: ${state.currentPresetDetail.ten_set}`;
    
    const mocGioContainer = document.getElementById('presetMocGioList');
    if (mocGioContainer) {
        const mocGios = state.currentPresetDetail.moc_gio || [];
        if (mocGios.length === 0) {
            mocGioContainer.innerHTML = '<p class="text-gray-500 italic w-full">Chưa có mốc giờ nào được thiết lập. Vui lòng thêm mốc giờ trong phần quản lý Mốc giờ.</p>';
        } else {
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

export function bindEvents() {
    const presetForm = document.getElementById('presetForm');
    if (presetForm) {
        presetForm.addEventListener('submit', handlePresetSubmit);
    }
    
    const addPresetBtn = document.getElementById('addPresetBtn');
    if (addPresetBtn) {
        addPresetBtn.addEventListener('click', () => showPresetModal());
    }

    bindPresetLinesEvents(renderPresetsTable);
}

export function init() {
    bindEvents();
}

registerActions({ viewPresetDetail, editPreset, deletePreset });
