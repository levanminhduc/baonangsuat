import { api } from '../admin-api.js';
import { escapeHtml, showToast, showLoading, hideLoading } from '../admin-utils.js';

let currentPage = 1;
const pageSize = 20;
let currentFilters = {};

export async function loadImportHistory(page = 1) {
    currentPage = page;
    showLoading();
    
    try {
        const params = new URLSearchParams({
            page: page,
            page_size: pageSize,
            ...currentFilters
        });
        
        const response = await api('GET', `/import-history?${params.toString()}`);
        
        if (response.success) {
            renderHistoryList(response.data, response.pagination);
        } else {
            showToast(response.message || 'Lỗi tải lịch sử import', 'error');
        }
    } catch (error) {
        console.error('Load import history error:', error);
        showToast('Lỗi kết nối server', 'error');
    } finally {
        hideLoading();
    }
}

export async function viewImportDetail(id) {
    showLoading();
    
    try {
        const response = await api('GET', `/import-history/${id}`);
        
        if (response.success) {
            renderDetailModal(response.data);
        } else {
            showToast(response.message || 'Lỗi tải chi tiết', 'error');
        }
    } catch (error) {
        console.error('View import detail error:', error);
        showToast('Lỗi kết nối server', 'error');
    } finally {
        hideLoading();
    }
}

function renderHistoryList(data, pagination) {
    const tbody = document.querySelector('#importHistoryTable tbody');
    if (!tbody) return;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-8 text-gray-500">
                    <div class="flex flex-col items-center gap-2">
                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Chưa có lịch sử import nào</span>
                    </div>
                </td>
            </tr>
        `;
        renderPagination(pagination);
        return;
    }
    
    tbody.innerHTML = data.map(item => {
        const statusIcon = getStatusIcon(item.trang_thai);
        const statusClass = getStatusClass(item.trang_thai);
        const fileSize = formatFileSize(item.kich_thuoc_file);
        const importTime = formatDateTime(item.import_luc);
        
        return `
            <tr class="hover:bg-gray-50 border-b border-gray-100">
                <td class="px-4 py-3">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(importTime.date)}</div>
                    <div class="text-xs text-gray-500">${escapeHtml(importTime.time)}</div>
                </td>
                <td class="px-4 py-3">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(item.import_boi)}</div>
                </td>
                <td class="px-4 py-3">
                    <div class="text-sm text-gray-900 truncate max-w-[150px]" title="${escapeHtml(item.ten_file)}">${escapeHtml(item.ten_file)}</div>
                    <div class="text-xs text-gray-500">${fileSize}</div>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 text-green-700 font-bold text-sm">${item.ma_hang_da_tao || 0}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold text-sm">${item.ma_hang_da_cap_nhat || 0}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 text-purple-700 font-bold text-sm">${item.cong_doan_da_tao || 0}</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="text-sm">
                        <span class="text-green-600 font-medium">+${item.routing_da_tao || 0}</span>
                        <span class="text-gray-400 mx-1">/</span>
                        <span class="text-red-600 font-medium">-${item.routing_da_xoa || 0}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ${statusClass}">
                        ${statusIcon}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <button onclick="viewImportDetail(${item.id})" class="text-primary hover:text-primary-dark transition-colors" title="Xem chi tiết">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    renderPagination(pagination);
}

function renderPagination(pagination) {
    const container = document.getElementById('importHistoryPagination');
    if (!container || !pagination) return;
    
    const { page, total_pages, total } = pagination;
    
    if (total_pages <= 1) {
        container.innerHTML = `<div class="text-sm text-gray-500">Tổng: ${total} bản ghi</div>`;
        return;
    }
    
    let pagesHtml = '';
    for (let i = 1; i <= total_pages; i++) {
        if (i === 1 || i === total_pages || (i >= page - 2 && i <= page + 2)) {
            const activeClass = i === page ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-100';
            pagesHtml += `<button onclick="loadImportHistory(${i})" class="px-3 py-1 rounded border ${activeClass}">${i}</button>`;
        } else if (i === page - 3 || i === page + 3) {
            pagesHtml += `<span class="px-2">...</span>`;
        }
    }
    
    container.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">Tổng: ${total} bản ghi</div>
            <div class="flex items-center gap-2">
                <button onclick="loadImportHistory(${page - 1})" ${page <= 1 ? 'disabled' : ''} class="px-3 py-1 rounded border bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">← Trước</button>
                ${pagesHtml}
                <button onclick="loadImportHistory(${page + 1})" ${page >= total_pages ? 'disabled' : ''} class="px-3 py-1 rounded border bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">Sau →</button>
            </div>
        </div>
    `;
}

function renderDetailModal(detail) {
    const modal = document.getElementById('importHistoryDetailModal');
    if (!modal) return;
    
    const statusIcon = getStatusIcon(detail.trang_thai);
    const statusClass = getStatusClass(detail.trang_thai);
    const statusText = getStatusText(detail.trang_thai);
    const fileSize = formatFileSize(detail.kich_thuoc_file);
    const importTime = formatDateTime(detail.import_luc);
    
    const chiTiet = detail.chi_tiet || { ma_hang: [], cong_doan_moi: [] };
    const maHangList = chiTiet.ma_hang || [];
    const congDoanMoi = chiTiet.cong_doan_moi || [];
    
    const content = `
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="bg-primary text-white px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-bold">Chi tiết Import #${detail.id}</h3>
                <button onclick="closeImportHistoryModal()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <!-- Info Card -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">📁 File</div>
                            <div class="font-medium">${escapeHtml(detail.ten_file)}</div>
                            <div class="text-xs text-gray-400">${fileSize}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">👤 Người import</div>
                            <div class="font-medium">${escapeHtml(detail.import_boi)}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">🕐 Thời gian</div>
                            <div class="font-medium">${importTime.date} ${importTime.time}</div>
                            <div class="text-xs text-gray-400">${detail.thoi_gian_xu_ly_ms}ms</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Trạng thái</div>
                            <div class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ${statusClass}">
                                ${statusIcon} ${statusText}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">${detail.ma_hang_da_tao || 0}</div>
                        <div class="text-xs text-gray-500">MH mới</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600">${detail.ma_hang_da_cap_nhat || 0}</div>
                        <div class="text-xs text-gray-500">MH cập nhật</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-purple-600">${detail.cong_doan_da_tao || 0}</div>
                        <div class="text-xs text-gray-500">CĐ mới</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
                        <div class="text-lg font-bold">
                            <span class="text-green-600">+${detail.routing_da_tao || 0}</span>
                            <span class="text-gray-400">/</span>
                            <span class="text-red-600">-${detail.routing_da_xoa || 0}</span>
                        </div>
                        <div class="text-xs text-gray-500">Routing</div>
                    </div>
                </div>
                
                <!-- Ma Hang List -->
                ${maHangList.length > 0 ? `
                <div class="mb-6">
                    <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Chi tiết Mã hàng (${maHangList.length})
                    </h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        ${maHangList.map(mh => `
                            <div class="flex items-center justify-between bg-white border border-gray-200 rounded px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">${escapeHtml(mh.ma_hang)}</span>
                                    <span class="text-gray-500 text-sm">${escapeHtml(mh.ten_hang || '')}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs px-2 py-0.5 rounded ${mh.is_new ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'}">
                                        ${mh.is_new ? 'MỚI' : 'CẬP NHẬT'}
                                    </span>
                                    <span class="text-xs text-gray-500">${mh.cong_doan_count} CĐ</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
                
                <!-- Cong Doan Moi -->
                ${congDoanMoi.length > 0 ? `
                <div class="mb-6">
                    <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Công đoạn mới được tạo (${congDoanMoi.length})
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        ${congDoanMoi.map(cd => `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-50 text-purple-700 rounded-full text-sm border border-purple-200">
                                ${escapeHtml(cd.ten_cong_doan)}
                            </span>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
                
                <!-- Errors -->
                ${detail.loi && detail.loi.length > 0 ? `
                <div class="mb-6">
                    <h4 class="font-medium text-red-700 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Lỗi khi phân tích (${detail.loi.length})
                    </h4>
                    <div class="space-y-2 max-h-32 overflow-y-auto">
                        ${detail.loi.map(err => `
                            <div class="bg-red-50 border border-red-200 rounded px-3 py-2 text-sm text-red-700">
                                <span class="font-medium">${escapeHtml(err.sheet_name || 'N/A')}</span>
                                <span class="text-red-400 mx-2">|</span>
                                ${escapeHtml(err.message || 'Lỗi không xác định')}
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            </div>
            
            <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex justify-end">
                <button onclick="closeImportHistoryModal()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                    Đóng
                </button>
            </div>
        </div>
    `;
    
    modal.innerHTML = content;
    modal.classList.remove('hidden');
}

function closeImportHistoryModal() {
    const modal = document.getElementById('importHistoryDetailModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function getStatusIcon(status) {
    switch (status) {
        case 'success': return '✅';
        case 'partial': return '⚠️';
        case 'failed': return '❌';
        default: return '❓';
    }
}

function getStatusText(status) {
    switch (status) {
        case 'success': return 'Thành công';
        case 'partial': return 'Một phần';
        case 'failed': return 'Thất bại';
        default: return 'Không rõ';
    }
}

function getStatusClass(status) {
    switch (status) {
        case 'success': return 'bg-green-100 text-green-700';
        case 'partial': return 'bg-yellow-100 text-yellow-700';
        case 'failed': return 'bg-red-100 text-red-700';
        default: return 'bg-gray-100 text-gray-700';
    }
}

function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function formatDateTime(dateString) {
    if (!dateString) return { date: 'N/A', time: '' };
    const date = new Date(dateString);
    return {
        date: date.toLocaleDateString('vi-VN'),
        time: date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    };
}

function applyFilters() {
    currentFilters = {};
    
    const dateFrom = document.getElementById('historyDateFrom')?.value;
    const dateTo = document.getElementById('historyDateTo')?.value;
    const importBoi = document.getElementById('historyUserSearch')?.value;
    const trangThai = document.getElementById('historyStatusFilter')?.value;
    
    if (dateFrom) currentFilters.date_from = dateFrom;
    if (dateTo) currentFilters.date_to = dateTo;
    if (importBoi) currentFilters.import_boi = importBoi;
    if (trangThai) currentFilters.trang_thai = trangThai;
    
    loadImportHistory(1);
}

function bindEvents() {
    document.getElementById('historyDateFrom')?.addEventListener('change', applyFilters);
    document.getElementById('historyDateTo')?.addEventListener('change', applyFilters);
    document.getElementById('historyStatusFilter')?.addEventListener('change', applyFilters);
    
    document.getElementById('historyUserSearch')?.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });
    
    // Close modal on backdrop click
    document.getElementById('importHistoryDetailModal')?.addEventListener('click', (e) => {
        if (e.target.id === 'importHistoryDetailModal') {
            closeImportHistoryModal();
        }
    });
}

export function init() {
    bindEvents();
}

// Export for global access
window.loadImportHistory = loadImportHistory;
window.viewImportDetail = viewImportDetail;
window.closeImportHistoryModal = closeImportHistoryModal;
