import { api } from '../admin-api.js';
import { escapeHtml, showToast, showLoading, hideLoading } from '../admin-utils.js';

const API_BASE = '/baonangsuat/api';
let previewData = null;

async function getCsrfToken() {
    const response = await fetch(API_BASE + '/csrf-token');
    const result = await response.json();
    return result.token || null;
}

async function uploadForPreview(file) {
    const csrfToken = await getCsrfToken();
    const formData = new FormData();
    formData.append('file', file);
    
    const options = {
        method: 'POST',
        headers: {},
        credentials: 'same-origin',
        body: formData
    };
    
    if (csrfToken) {
        options.headers['X-CSRF-Token'] = csrfToken;
    }
    
    const response = await fetch(API_BASE + '/admin/import/preview', options);
    return await response.json();
}

function renderUploadUI() {
    const container = document.getElementById('importContainer');
    if (!container) return;
    
    resetState();
    
    document.getElementById('importUploadZone').classList.remove('hidden');
    document.getElementById('importSelectedFile').classList.add('hidden');
    document.getElementById('importPreviewSection').classList.add('hidden');
    document.getElementById('importResultSection').classList.add('hidden');
}

function resetState() {
    previewData = null;
    const fileInput = document.getElementById('importFileInput');
    if (fileInput) fileInput.value = '';
}

function handleFileSelect(file) {
    if (!file) return;
    
    const allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel'
    ];
    const allowedExtensions = ['.xlsx', '.xls'];
    const fileExtension = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
    
    if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
        showToast('Chỉ hỗ trợ file Excel (.xlsx, .xls)', 'error');
        return;
    }
    
    if (file.size > 10 * 1024 * 1024) {
        showToast('File vượt quá 10MB', 'error');
        return;
    }
    
    document.getElementById('importFileName').textContent = file.name;
    document.getElementById('importSelectedFile').classList.remove('hidden');
    
    processFile(file);
}

async function processFile(file) {
    showLoading();
    
    try {
        const response = await uploadForPreview(file);
        
        if (response.success) {
            previewData = response;
            renderPreview(response);
            showToast(response.message || 'Phân tích file thành công', 'success');
        } else {
            showToast(response.message || 'Lỗi phân tích file', 'error');
            resetState();
            document.getElementById('importSelectedFile').classList.add('hidden');
        }
    } catch (error) {
        console.error('Import preview error:', error);
        showToast('Lỗi kết nối server', 'error');
        resetState();
        document.getElementById('importSelectedFile').classList.add('hidden');
    } finally {
        hideLoading();
    }
}

function renderPreview(response) {
    const previewSection = document.getElementById('importPreviewSection');
    const statsContainer = document.getElementById('importStats');
    const errorsContainer = document.getElementById('importErrors');
    const errorsListContainer = document.getElementById('importErrorsList');
    const previewListContainer = document.getElementById('importPreviewList');
    
    previewSection.classList.remove('hidden');
    
    const stats = response.stats || {};
    statsContainer.innerHTML = `
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium">Sheets</div>
            <div class="text-2xl font-bold text-primary">${stats.total_sheets || 0}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium">Mã hàng mới</div>
            <div class="text-2xl font-bold text-success">${stats.total_ma_hang_new || 0}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium">Mã hàng cũ</div>
            <div class="text-2xl font-bold text-gray-600">${stats.total_ma_hang_existing || 0}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium">Công đoạn mới</div>
            <div class="text-2xl font-bold text-success">${stats.total_cong_doan_new || 0}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium">Công đoạn cũ</div>
            <div class="text-2xl font-bold text-gray-600">${stats.total_cong_doan_existing || 0}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium">Routing mới</div>
            <div class="text-2xl font-bold text-primary">${stats.total_routing_new || 0}</div>
        </div>
    `;
    
    const errors = response.errors || [];
    if (errors.length > 0) {
        errorsContainer.classList.remove('hidden');
        errorsListContainer.innerHTML = errors.map(err => `
            <div class="border-l-4 border-danger bg-red-50 p-3 rounded-r">
                <p class="text-sm text-danger">
                    <span class="font-bold">${escapeHtml(err.sheet_name)}</span>
                    <span class="mx-1">(${escapeHtml(err.cell || '')}):</span>
                    ${escapeHtml(err.message)}
                </p>
            </div>
        `).join('');
    } else {
        errorsContainer.classList.add('hidden');
    }
    
    const data = response.data || [];
    if (data.length === 0) {
        previewListContainer.innerHTML = '<p class="text-gray-500 text-center py-4">Không có dữ liệu hợp lệ để import</p>';
        document.getElementById('importConfirmBtn').disabled = true;
    } else {
        document.getElementById('importConfirmBtn').disabled = false;
        previewListContainer.innerHTML = data.map(item => {
            const isNew = item.is_new;
            const badgeClass = isNew 
                ? 'bg-success text-white' 
                : 'bg-gray-200 text-gray-700';
            const badgeText = isNew ? 'Mới' : 'Cập nhật';
            
            const congDoanList = item.cong_doan_list || [];
            const newCount = congDoanList.filter(cd => cd.is_new).length;
            const existingCount = congDoanList.length - newCount;
            
            return `
                <div class="bg-white border border-gray-200 rounded-lg p-4 hover:border-primary transition-colors">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-800 text-lg">${escapeHtml(item.ma_hang)}</span>
                                <span class="text-xs px-2 py-0.5 rounded font-medium ${badgeClass}">${badgeText}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">${escapeHtml(item.ten_hang || '')}</p>
                        </div>
                        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Sheet: ${escapeHtml(item.sheet_name)}</span>
                    </div>
                    
                    <div class="mt-3 text-sm border-t border-gray-100 pt-3 flex items-center gap-4">
                        <span class="font-medium text-gray-700">${congDoanList.length} công đoạn</span>
                        <div class="flex gap-3 text-xs">
                            ${newCount > 0 ? `<span class="text-success font-medium">+${newCount} mới</span>` : ''}
                            ${existingCount > 0 ? `<span class="text-gray-500">${existingCount} có sẵn</span>` : ''}
                        </div>
                    </div>
                    
                    ${congDoanList.length > 0 ? `
                        <details class="mt-2 group">
                            <summary class="text-sm text-primary cursor-pointer hover:underline select-none list-none flex items-center gap-1 font-medium">
                                <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <span>Chi tiết công đoạn</span>
                            </summary>
                            <div class="mt-2 pl-5 space-y-1 max-h-40 overflow-y-auto">
                                ${congDoanList.map((cd, idx) => `
                                    <div class="text-sm flex justify-between items-center py-1 border-b border-gray-50 last:border-0">
                                        <span class="text-gray-700">
                                            <span class="text-gray-400 w-6 inline-block text-right mr-2">${idx + 1}.</span>
                                            ${escapeHtml(cd.ten_cong_doan)}
                                        </span>
                                        <span class="text-xs ${cd.is_new ? 'text-success font-medium' : 'text-gray-400'}">
                                            ${cd.ma_cong_doan} ${cd.is_new ? '(Mới)' : ''}
                                        </span>
                                    </div>
                                `).join('')}
                            </div>
                        </details>
                    ` : ''}
                </div>
            `;
        }).join('');
    }
}

async function handleConfirmImport() {
    if (!previewData || !previewData.data || previewData.data.length === 0) {
        showToast('Không có dữ liệu để import', 'error');
        return;
    }
    
    const maHangList = previewData.data.map(item => ({
        ma_hang: item.ma_hang,
        ten_hang: item.ten_hang,
        cong_doan_list: item.cong_doan_list.map(cd => ({
            thu_tu: cd.thu_tu,
            ten_cong_doan: cd.ten_cong_doan,
            ma_cong_doan: cd.ma_cong_doan,
            existing_id: cd.existing_id
        }))
    }));
    
    showLoading();
    
    try {
        const response = await api('POST', '/admin/import/confirm', { ma_hang_list: maHangList });
        
        if (response.success) {
            showToast(response.message || 'Import thành công', 'success');
            renderImportResult(response);
        } else {
            showToast(response.message || 'Lỗi import', 'error');
        }
    } catch (error) {
        console.error('Import confirm error:', error);
        showToast('Lỗi kết nối server', 'error');
    } finally {
        hideLoading();
    }
}

function renderImportResult(response) {
    document.getElementById('importPreviewSection').classList.add('hidden');
    document.getElementById('importSelectedFile').classList.add('hidden');
    
    const resultSection = document.getElementById('importResultSection');
    const resultStats = document.getElementById('importResultStats');
    
    resultSection.classList.remove('hidden');
    
    const stats = response.stats || {};
    resultStats.innerHTML = `
        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
            <div class="text-3xl font-bold text-success mb-1">${stats.ma_hang_created || 0}</div>
            <div class="text-sm text-gray-500 font-medium">Mã hàng mới</div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
            <div class="text-3xl font-bold text-primary mb-1">${stats.ma_hang_updated || 0}</div>
            <div class="text-sm text-gray-500 font-medium">Mã hàng cập nhật</div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
            <div class="text-3xl font-bold text-success mb-1">${stats.cong_doan_created || 0}</div>
            <div class="text-sm text-gray-500 font-medium">Công đoạn mới</div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
            <div class="text-3xl font-bold text-primary mb-1">${stats.routing_created || 0}</div>
            <div class="text-sm text-gray-500 font-medium">Routing mới</div>
        </div>
    `;
    
    resetState();
}

function handleCancel() {
    resetState();
    renderUploadUI();
}

function bindEvents() {
    const uploadZone = document.getElementById('importUploadZone');
    const fileInput = document.getElementById('importFileInput');
    const clearFileBtn = document.getElementById('importClearFile');
    const confirmBtn = document.getElementById('importConfirmBtn');
    const cancelBtn = document.getElementById('importCancelBtn');
    
    if (uploadZone) {
        uploadZone.addEventListener('click', () => {
            fileInput.click();
        });
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('border-primary', 'bg-gray-100');
        });
        
        uploadZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('border-primary', 'bg-gray-100');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('border-primary', 'bg-gray-100');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });
    }
    
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });
    }
    
    if (clearFileBtn) {
        clearFileBtn.addEventListener('click', handleCancel);
    }
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', handleConfirmImport);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', handleCancel);
    }
}

export function init() {
    bindEvents();
}
