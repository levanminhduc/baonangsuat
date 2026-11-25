import { api } from '../api.js';
import { showLoading, hideLoading, showToast, getStatusText, escapeHtml } from '../utils.js';

export const reportListMethods = {
    handleInitialRouteQueryParams() {
        const params = new URLSearchParams(window.location.search);
        const line = params.get('line');
        const ma_hang = params.get('ma_hang');
        const ngay = params.get('ngay');

        if (line && ma_hang && ngay) {
            this.loadReportFromParams(line, ma_hang, ngay);
            return true;
        }
        return false;
    },

    async loadReportFromParams(line, ma_hang, ngay) {
        try {
            showLoading();
            const response = await api('GET', `/bao-cao?ngay_tu=${ngay}&ngay_den=${ngay}`);
            
            if (response.success && Array.isArray(response.data)) {
                // Tìm báo cáo khớp với mã hàng và ngày (line được filter bởi session backend)
                const report = response.data.find(r => 
                    r.ma_hang === ma_hang && 
                    r.ngay_bao_cao === ngay
                );
                
                if (report) {
                    this.loadReport(report.id, false);
                    // Ensure tab is input
                    this.switchMainTab('input');
                } else {
                    showToast('Không tìm thấy báo cáo', 'error');
                    window.history.replaceState(null, '', window.location.pathname + window.location.hash);
                }
            } else {
                showToast('Không tìm thấy dữ liệu', 'error');
            }
        } catch (error) {
            console.error(error);
            showToast('Lỗi tải báo cáo từ URL', 'error');
        } finally {
            hideLoading();
        }
    },

    async loadReportList() {
        try {
            const response = await api('GET', `/bao-cao`);
            if (response.success) {
                this.renderReportList(response.data);
            }
        } catch (error) {
            showToast('Lỗi tải danh sách báo cáo', 'error');
        }
    },
    
    updateCreateButtonVisibility() {
        if (!window.appContext) return;
        
        // Admin always has permission, otherwise check can_create_report
        const isAdmin = window.appContext.session && window.appContext.session.vai_tro === 'admin';
        const canCreate = isAdmin || window.appContext.can_create_report;
        
        const createBtn = document.getElementById('createReportBtn');
        const navCreateBtn = document.getElementById('navCreateReportBtn');
        const navCreateBtnMobile = document.querySelector('.navCreateReportBtn-mobile');
        
        if (!canCreate) {
            if (createBtn) createBtn.classList.add('hidden');
            if (navCreateBtn) navCreateBtn.classList.add('hidden');
            if (navCreateBtnMobile) navCreateBtnMobile.classList.add('hidden');
        } else {
            if (createBtn) createBtn.classList.remove('hidden');
            if (navCreateBtn) navCreateBtn.classList.remove('hidden');
            if (navCreateBtnMobile) navCreateBtnMobile.classList.remove('hidden');
        }
    },

    renderReportList(reports) {
        const container = document.getElementById('reportListContainer');
        const editorContainer = document.getElementById('editorContainer');
        
        if (!container) return;
        
        container.classList.remove('hidden');
        if (editorContainer) editorContainer.classList.add('hidden');
        
        const tbody = container.querySelector('tbody');
        if (!tbody) return;
        
        if (reports.length === 0) {
            let message = 'Chưa có báo cáo nào đang xử lý';
            
            if (window.appContext) {
                const isAdmin = window.appContext.session && window.appContext.session.vai_tro === 'admin';
                const canCreate = isAdmin || window.appContext.can_create_report;
                if (!canCreate) {
                    message = 'Chưa có báo cáo. Vui lòng liên hệ quản trị viên.';
                }
            }
            
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:30px;">${message}</td></tr>`;
            return;
        }
        
        tbody.innerHTML = reports.map(r => `
            <tr data-id="${escapeHtml(r.id)}" data-line="${escapeHtml(r.ma_line)}" data-ma-hang="${escapeHtml(r.ma_hang)}" data-ngay="${escapeHtml(r.ngay_bao_cao)}" style="cursor: pointer;">
                <td>${escapeHtml(r.ngay_bao_cao)}</td>
                <td>${escapeHtml(r.ma_hang)}</td>
                <td>${escapeHtml(r.so_lao_dong)}</td>
                <td>${escapeHtml(r.ctns)}</td>
                <td>${escapeHtml(r.ct_gio)}</td>
                <td><span class="status-badge status-${escapeHtml(r.trang_thai)}">${escapeHtml(getStatusText(r.trang_thai))}</span></td>
            </tr>
        `).join('');
        
        tbody.querySelectorAll('tr[data-id]').forEach(row => {
            row.addEventListener('click', (e) => {
                if (e.ctrlKey || e.metaKey) return; 
                e.preventDefault();
                this.loadReport(parseInt(row.dataset.id));
            });
        });
    },

    showReportList(updateUrl = true) {
        if (this.modifiedEntries.size > 0) {
            this.saveChanges();
        }
        this.baoCao = null;
        this.resetAppBar();
        if (updateUrl) {
            window.history.pushState(null, '', window.location.pathname);
        }
        this.loadReportList();
    },
};
