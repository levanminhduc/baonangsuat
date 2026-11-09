import { api } from '../admin-api.js';
import { escapeHtml, showToast, closeModal, showConfirmModal, showLoading, hideLoading } from '../admin-utils.js';
import { getState, setPresets, setCurrentPresetDetail, setAssignedLines } from './state.js';

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
                <button class="btn btn-sm btn-secondary" onclick="window.viewPresetDetail(${preset.id})">Chi tiết</button>
                <button class="btn btn-sm btn-primary" onclick="window.editPreset(${preset.id})">Sửa</button>
                <button class="btn btn-sm btn-info" onclick="window.showCopyPresetModal(${preset.id})">Copy</button>
                <button class="btn btn-sm btn-danger" onclick="window.deletePreset(${preset.id})">Xóa</button>
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

export function renderAssignedLinesSection() {
    const state = getState();
    const linesContainer = document.getElementById('presetAssignedLines');
    if (!linesContainer) return;

    if (state.assignedLines.length === 0) {
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
                        ${state.assignedLines.map((line, index) => `
                            <tr class="${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'} hover:bg-gray-100 transition-colors">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">${escapeHtml(line.ma_line)}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${escapeHtml(line.ten_line)}</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <button class="text-red-600 hover:text-red-900 px-3 py-1 rounded-md hover:bg-red-50 transition-colors border border-transparent hover:border-red-200" onclick="window.unassignLine(${state.currentPresetDetail.id}, ${line.line_id})">
                                        Bỏ gán
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            <div class="mt-2 text-right text-xs text-gray-500">
                Tổng số: <span class="font-semibold text-gray-700">${state.assignedLines.length}</span> LINE
            </div>
        `;
    }
}

export async function showAssignLinesModal(presetId) {
    const state = getState();
    const preset = state.presets.find(p => p.id == presetId);
    if (!preset) return;
    
    try {
        const response = await api('GET', `/moc-gio-sets/unassigned-lines?ca_id=${preset.ca_id}`);
        if (response.success) {
            const unassignedLines = response.data;
            const modal = document.getElementById('assignLinesModal');
            if (!modal) return;
            
            document.getElementById('assignLinesPresetId').value = presetId;
            document.getElementById('assignLinesTitle').textContent = `Gán LINE cho: ${preset.ten_set}`;
            
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

export async function handleAssignLines(e) {
    e.preventDefault();
    
    const state = getState();
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
            
            const newLines = state.lines
                .filter(l => line_ids.includes(Number(l.id)))
                .map(l => ({ line_id: Number(l.id), ma_line: l.ma_line, ten_line: l.ten_line }));
            
            const currentIds = new Set(state.assignedLines.map(l => Number(l.line_id)));
            const linesToAdd = newLines.filter(l => !currentIds.has(l.line_id));
            
            setAssignedLines([...state.assignedLines, ...linesToAdd]);
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

export function unassignLine(presetId, lineId) {
    const state = getState();
    showConfirmModal('Bạn có chắc muốn bỏ gán LINE này?', async () => {
        showLoading();
        try {
            const response = await api('DELETE', `/moc-gio-sets/${presetId}/lines`, { line_ids: [lineId] });
            if (response.success) {
                showToast(response.message, 'success');
                
                setAssignedLines(state.assignedLines.filter(l => Number(l.line_id) !== Number(lineId)));
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

export function showCopyPresetModal(presetId) {
    const state = getState();
    const preset = state.presets.find(p => p.id == presetId);
    if (!preset) return;
    
    const modal = document.getElementById('copyPresetModal');
    if (!modal) return;
    
    document.getElementById('copyPresetSourceId').value = presetId;
    document.getElementById('copyPresetNewName').value = `${preset.ten_set} (Copy)`;
    
    modal.classList.remove('hidden');
}

export async function handleCopyPreset(e) {
    e.preventDefault();
    
    const state = getState();
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
            
            const sourcePreset = state.presets.find(p => p.id == source_set_id);
            
            const newId = response.data?.id || response.id;
            
            const newPreset = response.data && typeof response.data === 'object' && response.data.ten_set ? response.data : {
                id: newId,
                ten_set,
                ca_id: sourcePreset ? sourcePreset.ca_id : null,
                ma_ca: sourcePreset ? sourcePreset.ma_ca : '',
                ten_ca: sourcePreset ? sourcePreset.ten_ca : '',
                is_default: 0,
                is_active: 1
            };
            
            if (!newPreset.ma_ca && sourcePreset) newPreset.ma_ca = sourcePreset.ma_ca;
            if (!newPreset.ten_ca && sourcePreset) newPreset.ten_ca = sourcePreset.ten_ca;
            
            state.presets.push(newPreset);
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

export function bindEvents() {
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

export function init() {
    bindEvents();
}
