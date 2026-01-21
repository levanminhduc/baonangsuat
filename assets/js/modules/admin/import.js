import { api } from '../admin-api.js';
import { escapeHtml, showToast, showLoading, hideLoading, showConfirmModal, closeModal } from '../admin-utils.js';

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
    
    const response = await fetch(API_BASE + '/import/preview', options);
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
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center shadow-sm">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Sheets</div>
            <div class="text-2xl font-bold text-primary">${stats.total_sheets || 0}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center shadow-sm">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Mã hàng mới</div>
            <div class="text-2xl font-bold text-success">${stats.total_ma_hang_new || 0}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center shadow-sm">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Mã hàng cũ</div>
            <div class="text-2xl font-bold text-gray-600">${stats.total_ma_hang_existing || 0}</div>
        </div>
        ${stats.total_blocked_ma_hang > 0 ? `
        <div class="bg-white p-3 rounded-lg border border-danger text-center shadow-sm ring-1 ring-red-100">
            <div class="text-xs text-red-500 uppercase tracking-wide font-medium mb-1">Bị chặn</div>
            <div class="text-2xl font-bold text-danger">${stats.total_blocked_ma_hang}</div>
        </div>
        ` : ''}
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center shadow-sm">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Công đoạn mới</div>
            <div class="text-2xl font-bold text-success">${stats.total_cong_doan_new || 0}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center shadow-sm">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Công đoạn cũ</div>
            <div class="text-2xl font-bold text-gray-600">${stats.total_cong_doan_existing || 0}</div>
        </div>
        <div class="bg-white p-3 rounded-lg border border-gray-200 text-center shadow-sm">
            <div class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Routing mới</div>
            <div class="text-2xl font-bold text-primary">${stats.total_routing_new || 0}</div>
        </div>
    `;
    
    const errors = response.errors || [];
    if (errors.length > 0) {
        errorsContainer.classList.remove('hidden');
        errorsListContainer.innerHTML = errors.map(err => `
            <div class="border-l-4 border-danger bg-red-50 p-3 rounded-r shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-1">
                    <span class="font-bold text-danger text-sm whitespace-nowrap">${escapeHtml(err.sheet_name)}</span>
                    <span class="text-xs text-red-400 font-mono bg-white px-1.5 py-0.5 rounded border border-red-100 self-start sm:self-auto">${escapeHtml(err.cell || 'N/A')}</span>
                    <span class="text-sm text-red-700 mt-1 sm:mt-0">${escapeHtml(err.message)}</span>
                </div>
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
        const hasBlocking = response.has_blocking_reports || false;
        const totalBlocked = response.stats?.total_blocked_ma_hang || 0;
        const confirmBtn = document.getElementById('importConfirmBtn');
        
        // Disable import if ALL are blocked or if we want to force resolution
        // The requirement says "If ALL selected ma_hang are blocked, disable import button"
        const allBlocked = data.every(item => item.is_blocked);
        confirmBtn.disabled = allBlocked;
        
        if (hasBlocking) {
            const warningEl = document.createElement('div');
            warningEl.className = 'mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm flex items-center gap-2';
            warningEl.innerHTML = `
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>Có ${totalBlocked} mã hàng bị chặn, không thể import. Vui lòng kiểm tra danh sách bên dưới.</span>
            `;
            previewListContainer.before(warningEl);
        }

        previewListContainer.innerHTML = data.map(item => {
            const isNew = item.is_new;
            const isBlocked = item.is_blocked || false;
            const blockingCheck = item.blocking_check || null;
            const hasWarning = item.has_warning || false;
            const warningMessage = item.warning_message || '';
            const reportStats = item.report_stats || null;
            
            const badgeClass = isNew
                ? 'bg-success text-white'
                : 'bg-gray-200 text-gray-700';
            const badgeText = isNew ? 'Mới' : 'Cập nhật';
            
            const congDoanList = item.cong_doan_list || [];
            const newCount = congDoanList.filter(cd => cd.is_new).length;
            const existingCount = congDoanList.length - newCount;
            
            let blockingHtml = '';
            if (isBlocked && blockingCheck) {
                const reports = blockingCheck.blocking_reports || [];
                blockingHtml = `
                    <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg blocking-panel">
                        <div class="flex items-start gap-2 mb-2 text-red-800">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-bold">Mã hàng bị chặn</p>
                                <p class="text-xs mt-1">${escapeHtml(blockingCheck.message)}</p>
                            </div>
                        </div>
                        <details class="group mt-2">
                            <summary class="text-xs text-red-600 cursor-pointer hover:underline font-medium list-none flex items-center gap-1">
                                <span>Xem danh sách báo cáo</span>
                                <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </summary>
                            <div class="mt-2 overflow-x-auto">
                                <table class="min-w-full text-[11px] border-collapse">
                                    <thead>
                                        <tr class="bg-red-100 text-red-700">
                                            <th class="px-2 py-1 text-left border border-red-200">Ngày</th>
                                            <th class="px-2 py-1 text-left border border-red-200">Line</th>
                                            <th class="px-2 py-1 text-left border border-red-200">Ca</th>
                                            <th class="px-2 py-1 text-left border border-red-200">Trạng thái</th>
                                            <th class="px-2 py-1 text-left border border-red-200">Lý do</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        ${reports.map(rep => `
                                            <tr class="blocking-report-row ${rep.reason === 'LOCKED_REPORT' ? 'status-locked bg-red-50/50' : 'status-draft-with-data'}">
                                                <td class="px-2 py-1 border border-red-100">${escapeHtml(rep.ngay_bao_cao)}</td>
                                                <td class="px-2 py-1 border border-red-100">${escapeHtml(rep.ten_line)}</td>
                                                <td class="px-2 py-1 border border-red-100 font-medium">${escapeHtml(rep.ma_ca)}</td>
                                                <td class="px-2 py-1 border border-red-100">${escapeHtml(rep.trang_thai_label || rep.trang_thai_display || rep.trang_thai)}</td>
                                                <td class="px-2 py-1 border border-red-100 italic">${escapeHtml(rep.reason_label || rep.reason_display || rep.reason)}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </details>
                        <div class="mt-3 text-[11px] text-red-700 font-medium italic border-t border-red-100 pt-2">
                            Vui lòng hoàn thành hoặc xóa các báo cáo trước khi import.
                        </div>
                    </div>
                `;
            }

            const warningHtml = hasWarning && !isBlocked ? `
                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-yellow-800">Cảnh báo</p>
                            <p class="text-xs text-yellow-700 mt-1">${escapeHtml(warningMessage)}</p>
                            ${reportStats ? `
                                <div class="mt-2 flex gap-3 text-xs">
                                    <span class="text-yellow-700">Tổng: ${reportStats.total_reports} báo cáo</span>
                                    <span class="text-red-600 font-medium">Đã chốt: ${reportStats.locked_reports}</span>
                                    <span class="text-gray-600">Nháp: ${reportStats.draft_reports}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            ` : '';
            
            const borderClass = isBlocked ? 'border-danger blocked-item ring-1 ring-red-200' : (hasWarning ? 'border-yellow-300 ring-1 ring-yellow-200' : 'border-gray-200');

            return `
                <div class="bg-white border ${borderClass} rounded-lg p-4 hover:border-primary transition-colors shadow-sm ${isBlocked ? 'opacity-95' : ''}">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center flex-wrap gap-2">
                                <span class="font-bold text-gray-800 text-lg truncate" title="${escapeHtml(item.ma_hang)}">${escapeHtml(item.ma_hang)}</span>
                                <span class="text-xs px-2 py-0.5 rounded font-medium whitespace-nowrap ${badgeClass}">${badgeText}</span>
                                ${isBlocked ? '<span class="text-danger" title="Mã hàng bị chặn">🚫</span>' : (hasWarning ? '<span class="text-yellow-600" title="Có báo cáo đã chốt">⚠️</span>' : '')}
                            </div>
                            <p class="text-sm text-gray-600 mt-1 truncate" title="${escapeHtml(item.ten_hang || '')}">${escapeHtml(item.ten_hang || '')}</p>
                        </div>
                        <div class="flex-shrink-0 self-start">
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded inline-block max-w-[150px] truncate" title="Sheet: ${escapeHtml(item.sheet_name)}">Sheet: ${escapeHtml(item.sheet_name)}</span>
                        </div>
                    </div>
                    
                    ${blockingHtml}
                    ${warningHtml}
                    
                    <div class="mt-3 text-sm border-t border-gray-100 pt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                        <span class="font-medium text-gray-700 flex items-center gap-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            ${congDoanList.length} công đoạn
                        </span>
                        <div class="flex gap-3 text-xs">
                            ${newCount > 0 ? `<span class="text-success font-medium bg-green-50 px-1.5 py-0.5 rounded">+${newCount} mới</span>` : ''}
                            ${existingCount > 0 ? `<span class="text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">${existingCount} có sẵn</span>` : ''}
                        </div>
                    </div>
                    
                    ${congDoanList.length > 0 ? `
                        <details class="mt-3 group bg-gray-50 rounded-lg border border-gray-100">
                            <summary class="p-3 text-sm text-primary cursor-pointer hover:bg-gray-100 select-none list-none flex items-center justify-between font-medium rounded-lg transition-colors">
                                <span>Xem chi tiết công đoạn</span>
                                <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </summary>
                            <div class="px-3 pb-3 space-y-1 max-h-60 overflow-y-auto custom-scrollbar">
                                ${congDoanList.map((cd, idx) => `
                                    <div class="text-sm flex justify-between items-start py-2 border-b border-gray-200 last:border-0 gap-2">
                                        <div class="flex gap-2">
                                            <span class="text-gray-400 text-xs mt-0.5 w-5 text-right flex-shrink-0">${idx + 1}.</span>
                                            <span class="text-gray-700 break-words">${escapeHtml(cd.ten_cong_doan)}</span>
                                        </div>
                                        <span class="text-xs whitespace-nowrap ${cd.is_new ? 'text-success font-medium bg-green-50 px-1.5 py-0.5 rounded border border-green-100' : 'text-gray-400 bg-white px-1.5 py-0.5 rounded border border-gray-200'}">
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
        }))
    }));
    
    showLoading();
    
    try {
        const payload = { ma_hang_list: maHangList };
        if (acknowledgeDeletion) {
            payload.acknowledge_deletion = true;
        }
        
        const response = await api('POST', '/import/confirm', payload);
        
        if (response.success) {
            showToast(response.message || 'Import thành công', 'success');
            renderImportResult(response);
        } else if (response.error_code === 'IMPORT_BLOCKED') {
            hideLoading();
            const blockedList = response.blocked_ma_hang || [];
            const blockedHtml = blockedList.length > 0 
                ? `<div class="mt-3 text-left max-h-60 overflow-y-auto">
                    <ul class="list-disc list-inside space-y-1">
                        ${blockedList.map(m => `<li class="text-sm font-medium text-red-700">${escapeHtml(m)}</li>`).join('')}
                    </ul>
                   </div>`
                : '';
            
            showConfirmModal(
                `<div>
                    <p>${escapeHtml(response.message || 'Một số mã hàng bị chặn và không thể import do đang có báo cáo sử dụng.')}</p>
                    ${blockedHtml}
                    <p class="mt-4 text-xs text-gray-500 italic">Vui lòng quay lại màn hình preview để xem chi tiết các báo cáo gây chặn.</p>
                </div>`,
                null, // No callback, just show info
                'Import bị chặn',
                'danger',
                'Đã hiểu',
                '' // Hide cancel button
            );
        } else if (response.error_code === 'DELETION_WARNING' && response.requires_acknowledgement) {
            hideLoading();
            showConfirmModal(
                response.message || 'Import sẽ xóa một số routing hiện có. Vui lòng xác nhận để tiếp tục.',
                async () => {
                    showLoading();
                    try {
                        const confirmResponse = await api('POST', '/import/confirm', {
                            ma_hang_list: maHangList,
                            acknowledge_deletion: true
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
