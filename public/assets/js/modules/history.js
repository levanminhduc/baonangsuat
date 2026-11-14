import { api } from './api.js';
import { showLoading, hideLoading, showToast, getStatusText } from './utils.js';
import { GridManager } from './grid.js';
import { svgIcon, reportInfoHtml, bindInfoToggle, updateLuyKeSummary } from './report-view.js';

export class HistoryModule {
    constructor(app) {
        this.app = app;
        this.currentPage = 1;
        this.pageSize = 20;
        // Chi tiết lịch sử dùng đúng bảng của màn nhập, chỉ đọc
        this.gridManager = new GridManager(
            { updateLuyKeSummary: (summary) => updateLuyKeSummary(document.getElementById('historyInfo'), summary) },
            { containerId: 'historyGridContainer', readonly: true }
        );
        
        // Cache state
        this.historyCache = null;
        this.lastFilters = null;
        this.isLoaded = false;
        
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        const filterBtn = document.getElementById('historyFilterBtn');
        if (filterBtn) {
            filterBtn.addEventListener('click', () => {
                this.currentPage = 1;
                this.loadHistoryList(true); // Force refresh
            });
        }
    }

    async loadHistoryList(forceRefresh = false) {
        const dateFrom = document.getElementById('historyDateFrom')?.value || '';
        const dateTo = document.getElementById('historyDateTo')?.value || '';

        const currentFilters = {
            page: this.currentPage,
            pageSize: this.pageSize,
            dateFrom,
            dateTo
        };

        // Check cache
        if (!forceRefresh && this.isLoaded && this.historyCache && this.lastFilters && 
            JSON.stringify(this.lastFilters) === JSON.stringify(currentFilters)) {
            
            this.renderHistoryList(this.historyCache.data);
            this.renderPagination(this.historyCache.pagination);
            return;
        }

        try {
            showLoading();
            let url = `/bao-cao-history?page=${this.currentPage}&page_size=${this.pageSize}`;
            if (dateFrom) url += `&ngay_tu=${dateFrom}`;
            if (dateTo) url += `&ngay_den=${dateTo}`;

            const response = await api('GET', url);
            
            if (response.success) {
                // Update cache
                this.historyCache = response;
                this.lastFilters = currentFilters;
                this.isLoaded = true;

                this.renderHistoryList(response.data);
                this.renderPagination(response.pagination);
            } else {
                showToast(response.message || 'Lỗi tải lịch sử', 'error');
            }
        } catch (error) {
            console.error('Load history error:', error);
            showToast('Lỗi kết nối khi tải lịch sử', 'error');
        } finally {
            hideLoading();
        }
    }

    renderHistoryList(data) {
        const tbody = document.querySelector('#historyTable tbody');
        if (!tbody) return;

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">Không có dữ liệu</td></tr>';
            return;
        }

        const isAdmin = window.appContext?.session?.vai_tro === 'admin';
        
        tbody.innerHTML = data.map(item => {
            const hieuSuat = item.chi_tieu > 0 ? ((item.thuc_te / item.chi_tieu) * 100).toFixed(1) : 0;
            let hieuSuatClass = '';
            if (hieuSuat >= 100) hieuSuatClass = 'text-green-600 font-bold';
            else if (hieuSuat >= 90) hieuSuatClass = 'text-yellow-600 font-bold';
            else hieuSuatClass = 'text-red-600 font-bold';

            const deleteBtn = isAdmin ? `
                <button onclick="event.stopPropagation(); window.historyModule.deleteReport(${item.id})"
                    class="ml-2 text-red-600 hover:text-red-800 p-1" title="Xóa báo cáo">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            ` : '';

            return `
                <tr class="hover:bg-gray-50 cursor-pointer transition-colors" onclick="if(window.app && window.app.router) { window.app.router.navigate('/lich-su/' + ${item.id}) } else { window.historyModule.showDetail(${item.id}) }">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.ngay_bao_cao}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.ten_ca || item.ca_id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${item.ma_hang}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">${item.so_lao_dong}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">${item.chi_tieu}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center font-medium">${item.thuc_te}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center ${hieuSuatClass}">${hieuSuat}%</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                        <span class="status-badge status-${item.trang_thai}">${getStatusText(item.trang_thai)}</span>
                        ${deleteBtn}
                    </td>
                </tr>
            `;
        }).join('');
    }

    renderPagination(pagination) {
        const container = document.getElementById('historyPagination');
        if (!container || !pagination) return;

        const { page, total_pages, total_records } = pagination;
        
        let html = `
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Hiển thị <span class="font-medium">${(page - 1) * this.pageSize + 1}</span> đến <span class="font-medium">${Math.min(page * this.pageSize, total_records)}</span> trong số <span class="font-medium">${total_records}</span> kết quả
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
        `;

        // Previous button
        html += `
            <button onclick="window.historyModule.goToPage(${page - 1})" ${page <= 1 ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${page <= 1 ? 'opacity-50 cursor-not-allowed' : ''}">
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
        `;

        // Page numbers (simplified)
        // Show max 5 pages around current page
        let startPage = Math.max(1, page - 2);
        let endPage = Math.min(total_pages, page + 2);
        
        if (endPage - startPage < 4) {
             if (startPage === 1) endPage = Math.min(total_pages, 5);
             else if (endPage === total_pages) startPage = Math.max(1, total_pages - 4);
        }

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
            html += `
                <button onclick="window.historyModule.goToPage(${i})" aria-current="${i === page ? 'page' : 'false'}" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ${activeClass}">
                    ${i}
                </button>
            `;
        }

        // Next button
        html += `
            <button onclick="window.historyModule.goToPage(${page + 1})" ${page >= total_pages ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${page >= total_pages ? 'opacity-50 cursor-not-allowed' : ''}">
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
        `;

        html += `
                    </nav>
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    goToPage(page) {
        if (page < 1) return;
        this.currentPage = page;
        this.loadHistoryList();
    }

    async showDetail(reportId) {
        try {
            showLoading();
            const response = await api('GET', `/bao-cao-history/${reportId}`);
            
            if (response.success) {
                this.renderDetail(response.data);
                
                // Hide list, show detail
                document.getElementById('historyListContainer').classList.add('hidden');
                document.querySelector('.bg-white.p-4.rounded-lg.shadow-sm.mb-4').classList.add('hidden'); // Hide filter
                document.getElementById('historyDetailContainer').classList.remove('hidden');
            } else {
                showToast(response.message || 'Lỗi tải chi tiết', 'error');
            }
        } catch (error) {
            console.error('Show detail error:', error);
            showToast('Lỗi kết nối khi tải chi tiết', 'error');
        } finally {
            hideLoading();
        }
    }

    renderDetail(baoCao) {
        const info = document.getElementById('historyInfo');
        if (info) {
            const isFallback = baoCao.ket_qua_luy_ke_is_fallback == 1;
            info.innerHTML = reportInfoHtml(baoCao, {
                fieldsId: 'historyInfoFields',
                ngayExtra: isFallback ? ' <span class="bns-badge bns-badge-warn" title="Kết quả lũy kế tính lại">Tính lại</span>' : '',
            });
            bindInfoToggle(info);
        }

        const isAdmin = window.appContext?.session?.vai_tro === 'admin';
        const canUnlock = isAdmin && ['submitted', 'approved', 'locked'].includes(baoCao.trang_thai);
        const unlockBtn = canUnlock
            ? `<button type="button" class="bns-bar-btn is-ghost" title="Mở khóa báo cáo để chỉnh sửa" onclick="window.historyModule.unlockReport(${parseInt(baoCao.id)})">${svgIcon('<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/>')}<span>Mở khóa</span></button>`
            : '';
        this.app.renderAppBar(baoCao, {
            actions: unlockBtn,
            onBack: () => (this.app.router ? this.app.router.navigate('/lich-su') : this.hideDetail()),
        });

        this.gridManager.renderGrid(baoCao);
    }

    async unlockReport(reportId) {
        if (!confirm('Bạn có chắc chắn muốn mở khóa báo cáo này? Báo cáo sẽ được chuyển về trạng thái nháp (draft) và người dùng sẽ phải gửi lại.')) {
            return;
        }

        try {
            showLoading();
            const response = await api('POST', `/bao-cao/${reportId}/unlock`);
            
            if (response.success) {
                showToast('Mở khóa báo cáo thành công', 'success');
                // Reload detail to update status and buttons
                this.showDetail(reportId);
                // Invalidate cache since data changed
                this.historyCache = null;
                this.isLoaded = false;
            } else {
                showToast(response.message || 'Lỗi mở khóa báo cáo', 'error');
            }
        } catch (error) {
            console.error('Unlock report error:', error);
            showToast('Lỗi kết nối khi mở khóa báo cáo', 'error');
        } finally {
            hideLoading();
        }
    }

    deleteReport(reportId) {
        const message = 'Bạn có chắc chắn muốn xóa báo cáo này? Hành động này không thể hoàn tác.';
        this.showConfirmModal(message, async () => {
            this.closeConfirmModal();
            try {
                showLoading();
                const response = await api('DELETE', `/bao-cao/${reportId}`);
                
                if (response.success) {
                    showToast('Xóa báo cáo thành công', 'success');
                    this.historyCache = null;
                    this.isLoaded = false;
                    this.loadHistoryList(true);
                } else {
                    showToast(response.message || 'Lỗi xóa báo cáo', 'error');
                }
            } catch (error) {
                console.error('Delete report error:', error);
                showToast('Lỗi kết nối khi xóa báo cáo', 'error');
            } finally {
                hideLoading();
            }
        }, 'Xác nhận xóa', 'danger');
    }

    showConfirmModal(message, callback, title = 'Xác nhận', variant = 'primary', confirmText = 'Xác nhận', cancelText = 'Hủy', isHtml = false) {
        const modal = document.getElementById('confirmModal');
        if (!modal) {
            if (confirm(message)) callback();
            return;
        }
        
        const titleEl = document.getElementById('confirmModalTitle');
        if (titleEl) titleEl.textContent = title;
        
        const header = document.getElementById('confirmModalHeader');
        if (header) {
            header.classList.remove('bg-navbar-theme', 'border-navbar-theme', 'bg-danger', 'border-danger');
            if (variant === 'danger') {
                header.classList.add('bg-danger', 'border-danger');
            } else {
                header.classList.add('bg-navbar-theme', 'border-navbar-theme');
            }
        }
        
        const confirmBtn = document.getElementById('confirmBtn');
        if (confirmBtn) {
            confirmBtn.classList.remove('bg-primary', 'hover:bg-primary-dark', 'bg-danger', 'hover:bg-red-700');
            if (variant === 'danger') {
                confirmBtn.classList.add('bg-danger', 'hover:bg-red-700');
            } else {
                confirmBtn.classList.add('bg-primary', 'hover:bg-primary-dark');
            }
        }
        
        const messageEl = document.getElementById('confirmMessage');
        if (isHtml) {
            messageEl.innerHTML = message;
        } else {
            messageEl.textContent = message;
        }

        // Update button texts if provided
        if (confirmBtn && confirmText) {
            confirmBtn.textContent = confirmText;
        }

        const cancelBtn = document.getElementById('cancelConfirmBtn');
        if (cancelBtn) {
            if (cancelText === '') {
                cancelBtn.classList.add('hidden');
            } else {
                cancelBtn.classList.remove('hidden');
                cancelBtn.textContent = cancelText;
            }
        }

        // Handle null callback (info-only modal)
        if (callback === null) {
            if (confirmBtn) {
                confirmBtn.onclick = () => this.closeConfirmModal();
            }
        } else {
            confirmBtn.onclick = callback;
        }
        
        modal.classList.remove('hidden');
    }

    closeConfirmModal() {
        const modal = document.getElementById('confirmModal');
        if (modal) modal.classList.add('hidden');
    }

    hideDetail() {
        document.getElementById('historyDetailContainer').classList.add('hidden');
        document.getElementById('historyListContainer').classList.remove('hidden');
        document.querySelector('.bg-white.p-4.rounded-lg.shadow-sm.mb-4').classList.remove('hidden');
    }
}
