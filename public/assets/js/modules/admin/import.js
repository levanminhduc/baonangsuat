import { api, apiUpload } from '../api.js';
import { renderPreview } from './import-preview.js';
import { showToast, showLoading, hideLoading, showConfirmModal, closeModal } from '../utils.js';

let previewData = null;
let uploadedFileName = '';
let uploadedFileSize = 0;

async function uploadForPreview(file) {
    const formData = new FormData();
    formData.append('file', file);
    return apiUpload('/import/preview', formData);
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
    uploadedFileName = '';
    uploadedFileSize = 0;
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
    
    uploadedFileName = file.name;
    uploadedFileSize = file.size;
    
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

async function handleConfirmImport(acknowledgeDeletion = false) {
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
        })),
        dinh_muc_list: (item.dinh_muc_list || []).filter(dm => dm.line_id).map(dm => ({
            line_id: dm.line_id,
            so_lao_dong: dm.so_lao_dong,
            ctns: dm.ctns
        }))
    }));
    
    showLoading();
    
    try {
        const payload = { 
            ma_hang_list: maHangList,
            file_name: uploadedFileName,
            file_size: uploadedFileSize,
            preview_stats: previewData.stats || {},
            preview_errors: previewData.errors || []
        };
        if (acknowledgeDeletion) {
            payload.acknowledge_deletion = true;
        }
        
        const response = await api('POST', '/import/confirm', payload);
        
        if (response.success) {
            showToast(response.message || 'Import thành công', 'success');
            renderImportResult(response);
        } else if (response.error_code === 'DELETION_WARNING' && response.requires_acknowledgement) {
            hideLoading();
            showConfirmModal(
                response.message || 'Import sẽ xóa một số routing hiện có. Vui lòng xác nhận để tiếp tục.',
                async () => {
                    showLoading();
                    try {
                        const confirmResponse = await api('POST', '/import/confirm', {
                            ma_hang_list: maHangList,
                            acknowledge_deletion: true,
                            file_name: uploadedFileName,
                            file_size: uploadedFileSize,
                            preview_stats: previewData.stats || {},
                            preview_errors: previewData.errors || []
                        });
                        if (confirmResponse.success) {
                            showToast(confirmResponse.message || 'Import thành công', 'success');
                            renderImportResult(confirmResponse);
                        } else {
                            showToast(confirmResponse.message || 'Lỗi import', 'error');
                        }
                    } catch (error) {
                        console.error('Import confirm error:', error);
                        showToast('Lỗi kết nối server', 'error');
                    } finally {
                        hideLoading();
                    }
                    closeModal('confirmModal');
                },
                'Xác nhận xóa routing',
                'danger'
            );
            return;
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
