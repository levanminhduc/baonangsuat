import { api } from './api.js';
import { showLoading, hideLoading, showToast, getStatusText, formatGio } from './utils.js';
import { GridManager } from './grid.js';

export class HistoryModule {
    constructor(app) {
        this.app = app;
        this.currentPage = 1;
        this.pageSize = 20;
        this.gridManager = new GridManager(app); // Reuse GridManager for detail view
        
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

        const closeDetailBtn = document.getElementById('closeHistoryDetailBtn');
        if (closeDetailBtn) {
            closeDetailBtn.addEventListener('click', () => {
                if (window.app && window.app.router) {
                    window.app.router.navigate('/lich-su');
                } else {
                    this.hideDetail();
                }
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
        // Render Header
        const header = document.querySelector('.history-report-header');
        if (header) {
            const isFallback = baoCao.ket_qua_luy_ke_is_fallback == 1;
            const fallbackBadge = isFallback 
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 ml-2 border border-yellow-200">Tính lại</span>` 
                : '';

            // Check unlock permission
            const session = window.appContext?.session || {};
            const isAdmin = session.vai_tro === 'admin';
            const canUnlock = isAdmin && ['submitted', 'approved', 'locked'].includes(baoCao.trang_thai);
            
            const unlockBtn = canUnlock ? `
                <button onclick="window.historyModule.unlockReport(${baoCao.id})"
                    class="btn bg-red-600 hover:bg-red-700 text-white text-sm font-medium w-full flex justify-center items-center shadow-sm transition-colors duration-200"
                    title="Mở khóa báo cáo để chỉnh sửa">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a1 1 0 001-1v-6a1 1 0 00-1-1H9a1 1 0 00-1 1v6a1 1 0 001 1z"></path>
                    </svg>
                    Mở khóa
                </button>
            ` : '';

            header.innerHTML = `
                <div class="header-field">
                    <label>LINE:</label>
                    <span class="value">${baoCao.ma_line}</span>
                </div>
                <div class="header-field">
                    <label>LĐ:</label>
                    <span class="value">${baoCao.so_lao_dong}</span>
                </div>
                <div class="header-field">
                    <label>MH:</label>
                    <span class="value">${baoCao.ma_hang}</span>
                </div>
                <div class="header-field">
                    <label>CTNS:</label>
                    <span class="value">${baoCao.ctns}</span>
                </div>
                <div class="header-field">
                    <label>CT/Giờ:</label>
                    <span class="value">${baoCao.ct_gio}</span>
                </div>
                <div class="header-field">
                    <label>Ngày:</label>
                    <span class="value">${baoCao.ngay_bao_cao}${fallbackBadge}</span>
                </div>
                <div class="header-actions">
                    ${unlockBtn}
                </div>
            `;
        }

        // Render Grid (Readonly)
        const container = document.getElementById('historyGridContainer');
        if (!container) return;

        let headerHtml = `
            <tr>
                <th class="col-stt" rowspan="3">STT</th>
                <th class="col-name" rowspan="3">Tên công đoạn</th>
                ${baoCao.moc_gio_list.map(m => `<th class="col-hour">${formatGio(m.gio)}</th>`).join('')}
                <th class="col-luyke" rowspan="3">Lũy kế</th>
            </tr>
            <tr class="row-chitieu">
                ${baoCao.moc_gio_list.map(m => `<td>${(baoCao.chi_tieu_luy_ke && baoCao.chi_tieu_luy_ke[m.id]) || 0}</td>`).join('')}
            </tr>
            <tr class="row-hieuSuat">
                ${baoCao.moc_gio_list.map(m => `<td class="hieu-suat-cell-history" data-moc="${m.id}">-</td>`).join('')}
            </tr>
        `;

        let bodyHtml = '';
        baoCao.routing.forEach((cd, idx) => {
            let luyKe = 0;
            bodyHtml += `<tr>`;
            bodyHtml += `<td class="cell-readonly">${idx + 1}</td>`;
            bodyHtml += `<td class="cell-name" title="${cd.ten_cong_doan}">${cd.ten_cong_doan}</td>`;
            
            baoCao.moc_gio_list.forEach(moc => {
                const key = `${cd.cong_doan_id}_${moc.id}`;
                const entry = baoCao.entries[key];
                const value = entry ? parseInt(entry.so_luong) : 0;
                luyKe += value;
                
                const chiTieu = (baoCao.chi_tieu_luy_ke && baoCao.chi_tieu_luy_ke[moc.id]) || 0;
                let cellClass = 'cell-readonly text-center';
                
                // Add color class based on comparison with target
                if (chiTieu > 0) {
                    cellClass += (value >= chiTieu ? ' cell-pass' : ' cell-fail');
                }
                
                bodyHtml += `<td class="${cellClass}">${value || ''}</td>`;
            });
            
            // Xử lý hiển thị lũy kế và trạng thái từ ket_qua_luy_ke
            let luyKeDisplay = luyKe;
            let statusHtml = '';

            if (baoCao.ket_qua_luy_ke && baoCao.ket_qua_luy_ke.cong_doan) {
                const result = baoCao.ket_qua_luy_ke.cong_doan.find(c => c.cong_doan_id == cd.cong_doan_id);
                if (result) {
                    luyKeDisplay = result.luy_ke_thuc_te;
                    
                    if (result.trang_thai) {
                        const statusMap = {
                            'dat': { text: 'Đạt', class: 'luy-ke-status-pass' },
                            'chua_dat': { text: 'Chưa đạt', class: 'luy-ke-status-fail' },
                            'na': { text: 'N/A', class: 'luy-ke-status-na' }
                        };
                        const s = statusMap[result.trang_thai] || statusMap['na'];
                        
                        // Chỉ hiện badge nếu có trạng thái ý nghĩa hoặc là công đoạn tính lũy kế
                        if (result.trang_thai !== 'na' || cd.la_cong_doan_tinh_luy_ke == 1 || cd.la_cong_doan_tinh_luy_ke == '1') {
                            statusHtml = `
                                <div class="mt-1 flex justify-center">
                                    <div class="luy-ke-status-cell ${s.class}">
                                        ${s.text}
                                        <span class="luy-ke-tooltip">
                                            Thực tế: ${result.luy_ke_thuc_te} / Mục tiêu: ${result.chi_tieu_luy_ke}
                                        </span>
                                    </div>
                                </div>
                            `;
                        }
                    }
                }
            }

            bodyHtml += `<td class="cell-luyke text-center">
                <div class="flex flex-col items-center justify-center">
                    <span class="font-bold">${luyKeDisplay}</span>
                    ${statusHtml}
                </div>
            </td>`;
            bodyHtml += '</tr>';
        });

        container.innerHTML = `
            <table class="excel-grid">
                <thead>${headerHtml}</thead>
                <tbody>${bodyHtml}</tbody>
            </table>
        `;

        // Calculate and update Hieu Suat for Readonly view
        this.updateHieuSuatHistory(baoCao);
    }

    updateHieuSuatHistory(baoCao) {
        // Similar to GridManager but purely calculation based on data, no DOM input reading
        baoCao.moc_gio_list.forEach(moc => {
            const chiTieu = (baoCao.chi_tieu_luy_ke && baoCao.chi_tieu_luy_ke[moc.id]) || 0;
            let tongThucTe = 0;

            baoCao.routing.forEach(cd => {
                if (cd.la_cong_doan_tinh_luy_ke === 1 || cd.la_cong_doan_tinh_luy_ke === '1') {
                     // Sum from entries only (since it's history)
                     // Logic: Sum entries of this CD for all Moc Gio <= current Moc Gio
                     baoCao.moc_gio_list.forEach(m => {
                        if (m.thu_tu <= moc.thu_tu) {
                            const key = `${cd.cong_doan_id}_${m.id}`;
                            const entry = baoCao.entries[key];
                            if (entry) {
                                tongThucTe += parseInt(entry.so_luong) || 0;
                            }
                        }
                    });
                }
            });

            const hieuSuatCell = document.querySelector(`.hieu-suat-cell-history[data-moc="${moc.id}"]`);
            if (hieuSuatCell && chiTieu > 0) {
                const hieuSuat = (tongThucTe / chiTieu) * 100;
                hieuSuatCell.textContent = hieuSuat.toFixed(1) + '%';
                
                hieuSuatCell.classList.remove('hieu-suat-green', 'hieu-suat-yellow', 'hieu-suat-red');
                if (hieuSuat >= 100) {
                    hieuSuatCell.classList.add('hieu-suat-green');
                } else if (hieuSuat >= 90) {
                    hieuSuatCell.classList.add('hieu-suat-yellow');
                } else {
                    hieuSuatCell.classList.add('hieu-suat-red');
                }
            }
        });
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

    showConfirmModal(message, callback, title = 'Xác nhận', variant = 'primary') {
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
        
        document.getElementById('confirmMessage').textContent = message;
        confirmBtn.onclick = callback;
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
