import { fetchCsrfToken, api } from './modules/api.js';
import { showLoading, hideLoading, showToast, updateStatusBar, getStatusText } from './modules/utils.js';
import { GridManager } from './modules/grid.js';
import { HistoryModule } from './modules/history.js';
import { Router } from './modules/router.js';

class NangSuatApp {
    constructor() {
        this.baoCao = null;
        this.version = 1;
        this.modifiedEntries = new Map();
        this.saveTimer = null;
        this.isSaving = false;
        
        this.gridManager = new GridManager(this);
        this.historyModule = null; // Will be initialized if permission granted
        this.router = new Router(this);
        
        this.init();
    }
    
    async init() {
        await fetchCsrfToken();
        this.bindEvents();
        
        // Define routes
        this.router
            .add('/nhap-bao-cao', () => {
                this.switchMainTab('input');
            })
            .add('/lich-su', () => {
                this.checkHistoryPermission(() => {
                    this.switchMainTab('history');
                    this.historyModule.hideDetail();
                    
                    // Show loading if first time
                    if (!this.historyModule.isLoaded) {
                        this.historyModule.loadHistoryList();
                    }
                });
            })
            .add('/lich-su/:id', (params) => {
                this.checkHistoryPermission(() => {
                    this.switchMainTab('history');
                    this.historyModule.showDetail(params.id);
                });
            });

        this.loadContext().then(() => {
            // Check initial route via query params first (for backward compatibility)
            // If query params exist, they take precedence and we stay on input tab (but maybe URL changes)
            // But per requirements, query params opening a report should map to input tab
            const hasQueryParams = this.handleInitialRouteQueryParams();
            
            this.router.start(false); // Start listening to hash changes but don't handle yet
            
            if (!hasQueryParams) {
                // If no query params, respect the hash
                this.router.handleHashChange();
            } else {
                // If query params were handled, ensure hash is synced to /nhap-bao-cao if empty
                if (!window.location.hash) {
                    this.router.navigate('/nhap-bao-cao');
                }
            }
        });
        
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.reportId) {
                this.loadReport(e.state.reportId, false);
            } else if (e.state === null) {
                // Handle popstate for query param navigation if needed, 
                // but hash router handles hash changes separately.
                // This block is mainly for the "pushState" done in loadReport with query params.
                
                // If we are in hash mode, popstate might also trigger on hash change, 
                // but hashchange event handles that.
                // We only care about query param report detail state here.
                if (window.location.search.includes('line=')) {
                     // It's a query param state
                     // Do nothing or reload list?
                } else {
                     this.showReportList(false);
                }
            }
        });
    }

    checkHistoryPermission(callback) {
        if (window.appContext && window.appContext.can_view_history) {
            callback();
        } else {
            showToast('Bạn không có quyền xem lịch sử', 'error');
            this.router.navigate('/nhap-bao-cao');
        }
    }

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
    }

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
    }
    
    bindEvents() {
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
        }
        
        const createBtn = document.getElementById('createReportBtn');
        if (createBtn) {
            createBtn.addEventListener('click', () => this.showCreateModal());
        }
        
        const navCreateBtn = document.getElementById('navCreateReportBtn');
        if (navCreateBtn) {
            navCreateBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showCreateModal();
            });
        }
        
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitReport());
        }
        
        const backBtn = document.getElementById('backBtn');
        if (backBtn) {
            backBtn.addEventListener('click', () => this.showReportList());
        }

        // Tab events - Use Router Navigate
        const tabInput = document.getElementById('tabInput');
        const tabHistory = document.getElementById('tabHistory');

        if (tabInput) {
            tabInput.addEventListener('click', (e) => {
                e.preventDefault();
                this.router.navigate('/nhap-bao-cao');
            });
        }
        if (tabHistory) {
            tabHistory.addEventListener('click', (e) => {
                e.preventDefault();
                this.router.navigate('/lich-su');
            });
        }
        
        document.addEventListener('keydown', (e) => this.handleGlobalKeyDown(e));
    }
    
    async loadContext() {
        try {
            showLoading();
            const response = await api('GET', '/context');
            if (response.success) {
                window.appContext = response.data;
                this.renderHeader(response.data);
                
                // Permission check for create buttons
                this.updateCreateButtonVisibility();
                
                // Permission check for history tab
                if (window.appContext.can_view_history) {
                    const tabHistory = document.getElementById('tabHistory');
                    const mainTabs = document.getElementById('mainTabs');
                    if (tabHistory) tabHistory.classList.remove('hidden');
                    if (mainTabs) mainTabs.classList.remove('hidden');
                    
                    // Init history module if needed
                    if (!this.historyModule) {
                        this.historyModule = new HistoryModule(this);
                        window.historyModule = this.historyModule; // Expose for onclick handlers
                    }
                }

                await this.loadReportList();
            }
        } catch (error) {
            showToast('Lỗi tải dữ liệu: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    }

    switchMainTab(tabName) {
        const tabInput = document.getElementById('tabInput');
        const tabHistory = document.getElementById('tabHistory');
        const contentInput = document.getElementById('tabContentInput');
        const contentHistory = document.getElementById('tabContentHistory');

        if (tabName === 'input') {
            tabInput.classList.add('active', 'border-primary', 'text-primary');
            tabInput.classList.remove('border-transparent', 'text-gray-500');
            tabHistory.classList.remove('active', 'border-primary', 'text-primary');
            tabHistory.classList.add('border-transparent', 'text-gray-500');
            
            contentInput.classList.remove('hidden');
            contentHistory.classList.add('hidden');
        } else if (tabName === 'history') {
            tabHistory.classList.add('active', 'border-primary', 'text-primary');
            tabHistory.classList.remove('border-transparent', 'text-gray-500');
            tabInput.classList.remove('active', 'border-primary', 'text-primary');
            tabInput.classList.add('border-transparent', 'text-gray-500');
            
            contentHistory.classList.remove('hidden');
            contentInput.classList.add('hidden');
        }
    }
    
    renderHeader(context) {
        const userInfo = document.querySelector('.user-info');
        if (userInfo && context.session) {
            userInfo.innerHTML = `${context.session.ho_ten} (${context.session.line_ten || 'User'})`;
        }
    }
    
    async loadReportList() {
        try {
            const today = new Date().toISOString().split('T')[0];
            const response = await api('GET', `/bao-cao?ngay_tu=${today}&ngay_den=${today}`);
            if (response.success) {
                this.renderReportList(response.data);
            }
        } catch (error) {
            showToast('Lỗi tải danh sách báo cáo', 'error');
        }
    }
    
    updateCreateButtonVisibility() {
        if (!window.appContext) return;
        
        // Admin always has permission, otherwise check can_create_report
        const isAdmin = window.appContext.session && window.appContext.session.role === 'admin';
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
    }

    renderReportList(reports) {
        const container = document.getElementById('reportListContainer');
        const editorContainer = document.getElementById('editorContainer');
        
        if (!container) return;
        
        container.classList.remove('hidden');
        if (editorContainer) editorContainer.classList.add('hidden');
        
        const tbody = container.querySelector('tbody');
        if (!tbody) return;
        
        if (reports.length === 0) {
            let message = 'Chưa có báo cáo nào hôm nay';
            
            // Check permission for message
            if (window.appContext) {
                const isAdmin = window.appContext.session && window.appContext.session.role === 'admin';
                const canCreate = isAdmin || window.appContext.can_create_report;
                if (!canCreate) {
                    message = 'Chưa có báo cáo. Vui lòng liên hệ quản trị viên.';
                }
            }
            
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:30px;">${message}</td></tr>`;
            return;
        }
        
        tbody.innerHTML = reports.map(r => `
            <tr data-id="${r.id}" data-line="${r.ma_line}" data-ma-hang="${r.ma_hang}" data-ngay="${r.ngay_bao_cao}" style="cursor: pointer;">
                <td>${r.ngay_bao_cao}</td>
                <td>${r.ma_hang}</td>
                <td>${r.so_lao_dong}</td>
                <td>${r.ctns}</td>
                <td>${r.ct_gio}</td>
                <td><span class="status-badge status-${r.trang_thai}">${getStatusText(r.trang_thai)}</span></td>
            </tr>
        `).join('');
        
        tbody.querySelectorAll('tr[data-id]').forEach(row => {
            row.addEventListener('click', (e) => {
                if (e.ctrlKey || e.metaKey) return; 
                e.preventDefault();
                this.loadReport(parseInt(row.dataset.id));
            });
        });
    }
    
    showCreateModal() {
        const modal = document.getElementById('createModal');
        if (modal) {
            modal.classList.remove('hidden');
            this.populateModalSelects();
        }
    }
    
    async populateModalSelects() {
        const caSelect = document.getElementById('modalCa');
        const maHangSelect = document.getElementById('modalMaHang');
        const lineGroup = document.getElementById('modalLineGroup');
        const lineSelect = document.getElementById('modalLine');
        
        if (caSelect && window.appContext?.ca_list) {
            caSelect.innerHTML = window.appContext.ca_list.map(ca =>
                `<option value="${ca.id}">${ca.ma_ca} - ${ca.ten_ca}</option>`
            ).join('');
        }
        
        if (maHangSelect && window.appContext?.ma_hang_list) {
            maHangSelect.innerHTML = '<option value="">-- Chọn mã hàng --</option>' +
                window.appContext.ma_hang_list.map(mh =>
                    `<option value="${mh.id}">${mh.ma_hang} - ${mh.ten_hang}</option>`
                ).join('');
        }
        
        if (window.appContext?.can_create_report_any_line && lineGroup && lineSelect) {
            lineGroup.style.display = 'block';
            try {
                const response = await api('GET', '/admin/lines');
                if (response.success) {
                    lineSelect.innerHTML = '<option value="">-- Chọn LINE --</option>' +
                        response.data.filter(l => l.is_active).map(l =>
                            `<option value="${l.id}">${l.ma_line} - ${l.ten_line}</option>`
                        ).join('');
                }
            } catch (e) {
                console.error('Error loading lines', e);
            }
        } else if (lineGroup) {
            lineGroup.style.display = 'none';
        }
    }
    
    async createReport() {
        const ngay = document.getElementById('modalNgay')?.value;
        const ca_id = document.getElementById('modalCa')?.value;
        const ma_hang_id = document.getElementById('modalMaHang')?.value;
        const so_lao_dong = document.getElementById('modalLaoDong')?.value || 0;
        const ctns = document.getElementById('modalCtns')?.value || 0;
        const line_id = document.getElementById('modalLine')?.value;
        
        if (!ngay || !ca_id || !ma_hang_id) {
            showToast('Vui lòng điền đầy đủ thông tin', 'error');
            return;
        }
        
        if (window.appContext?.can_create_report_any_line && !line_id) {
            showToast('Vui lòng chọn LINE', 'error');
            return;
        }
        
        try {
            showLoading();
            const payload = {
                ngay_bao_cao: ngay,
                ca_id: parseInt(ca_id),
                ma_hang_id: parseInt(ma_hang_id),
                so_lao_dong: parseInt(so_lao_dong),
                ctns: parseInt(ctns)
            };
            
            if (window.appContext?.can_create_report_any_line && line_id) {
                payload.line_id = parseInt(line_id);
            }
            
            const response = await api('POST', '/bao-cao', payload);
            
            if (response.success) {
                this.closeModal();
                showToast('Tạo báo cáo thành công', 'success');
                await this.loadReport(response.bao_cao_id);
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi tạo báo cáo: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    }
    
    closeModal() {
        document.querySelectorAll('.modal').forEach(m => m.classList.add('hidden'));
    }
    
    async loadReport(baoCaoId, updateUrl = true) {
        try {
            showLoading();
            const response = await api('GET', `/bao-cao/${baoCaoId}`);
            if (response.success) {
                this.baoCao = response.data;
                this.version = this.baoCao.version;
                this.modifiedEntries.clear();
                
                if (updateUrl) {
                    const params = new URLSearchParams();
                    params.set('line', this.baoCao.ma_line);
                    params.set('ma_hang', this.baoCao.ma_hang);
                    params.set('ngay', this.baoCao.ngay_bao_cao);
                    const newUrl = `${window.location.pathname}?${params.toString()}`;
                    window.history.pushState({ reportId: baoCaoId }, '', newUrl);
                }
                
                this.renderEditor();
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi tải báo cáo: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    }
    
    renderEditor() {
        const listContainer = document.getElementById('reportListContainer');
        const editorContainer = document.getElementById('editorContainer');
        
        if (listContainer) listContainer.classList.add('hidden');
        if (editorContainer) editorContainer.classList.remove('hidden');
        
        this.renderReportHeader();
        this.gridManager.renderGrid(this.baoCao);
        updateStatusBar('Đã tải báo cáo');
    }
    
    renderReportHeader() {
        const header = document.querySelector('.report-header');
        if (!header || !this.baoCao) return;
        
        const bc = this.baoCao;
        const isEditable = bc.trang_thai === 'draft';
        
        header.innerHTML = `
            <div class="header-field">
                <label>LINE:</label>
                <span class="value">${bc.ma_line}</span>
            </div>
            <div class="header-field">
                <label>LĐ:</label>
                ${isEditable 
                    ? `<input type="number" id="headerLaoDong" value="${bc.so_lao_dong}" min="0" style="width:60px">`
                    : `<span class="value">${bc.so_lao_dong}</span>`
                }
            </div>
            <div class="header-field">
                <label>MH:</label>
                <span class="value">${bc.ma_hang}</span>
            </div>
            <div class="header-field">
                <label>CTNS:</label>
                ${isEditable 
                    ? `<input type="number" id="headerCtns" value="${bc.ctns}" min="0" style="width:80px">`
                    : `<span class="value">${bc.ctns}</span>`
                }
            </div>
            <div class="header-field">
                <label>CT/Giờ:</label>
                <span class="value" id="ctGioDisplay">${bc.ct_gio}</span>
            </div>
            <div class="header-actions">
                <button id="backBtn" class="btn">← Quay lại</button>
                ${isEditable ? `<button id="submitBtn" class="btn btn-success">Chốt báo cáo</button>` : ''}
            </div>
        `;
        
        if (isEditable) {
            const ldInput = document.getElementById('headerLaoDong');
            const ctnsInput = document.getElementById('headerCtns');
            
            if (ldInput) {
                ldInput.addEventListener('change', () => this.updateHeader());
            }
            if (ctnsInput) {
                ctnsInput.addEventListener('change', () => this.updateHeader());
            }
        }
        
        const backBtn = document.getElementById('backBtn');
        if (backBtn) {
            backBtn.addEventListener('click', () => this.showReportList());
        }
        
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitReport());
        }
    }
    
    onCellChange(input) {
        const cdId = input.dataset.cd;
        const mocId = input.dataset.moc;
        const value = parseInt(input.value) || 0;
        
        const key = `${cdId}_${mocId}`;
        this.modifiedEntries.set(key, {
            cong_doan_id: parseInt(cdId),
            moc_gio_id: parseInt(mocId),
            so_luong: value
        });
        
        input.classList.add('modified');
        this.gridManager.updateCellColor(input, this.baoCao);
        this.gridManager.updateRowLuyKe(cdId);
        this.gridManager.updateHieuSuat(this.baoCao);
        this.scheduleSave();
    }
    
    scheduleSave() {
        if (this.saveTimer) {
            clearTimeout(this.saveTimer);
        }
        this.saveTimer = setTimeout(() => this.saveChanges(), 1500);
        updateStatusBar('Đang chờ lưu...', 'saving');
    }
    
    async saveChanges() {
        if (this.modifiedEntries.size === 0 || this.isSaving) return;
        
        this.isSaving = true;
        updateStatusBar('Đang lưu...', 'saving');
        
        const entries = Array.from(this.modifiedEntries.values());
        
        try {
            const response = await api('PUT', `/bao-cao/${this.baoCao.id}/entries`, {
                entries: entries,
                version: this.version
            });
            
            if (response.success) {
                this.version = response.new_version;
                this.modifiedEntries.clear();
                
                document.querySelectorAll('.cell-input.modified').forEach(inp => {
                    inp.classList.remove('modified');
                    inp.classList.add('saved');
                    setTimeout(() => inp.classList.remove('saved'), 500);
                });
                
                updateStatusBar('Đã lưu', 'success');
            } else {
                updateStatusBar('Lỗi: ' + response.message, 'error');
                if (response.message.includes('cập nhật bởi người khác')) {
                    showToast('Dữ liệu đã thay đổi, đang tải lại...', 'warning');
                    await this.loadReport(this.baoCao.id);
                }
            }
        } catch (error) {
            updateStatusBar('Lỗi lưu dữ liệu', 'error');
        } finally {
            this.isSaving = false;
        }
    }
    
    async updateHeader() {
        const ldInput = document.getElementById('headerLaoDong');
        const ctnsInput = document.getElementById('headerCtns');
        
        if (!ldInput || !ctnsInput) return;
        
        try {
            const response = await api('PUT', `/bao-cao/${this.baoCao.id}/header`, {
                so_lao_dong: parseInt(ldInput.value) || 0,
                ctns: parseInt(ctnsInput.value) || 0,
                version: this.version
            });
            
            if (response.success) {
                this.version = response.new_version;
                const ctGioDisplay = document.getElementById('ctGioDisplay');
                if (ctGioDisplay) {
                    ctGioDisplay.textContent = response.ct_gio;
                }
                this.baoCao.ctns = parseInt(ctnsInput.value) || 0;
                this.baoCao.so_lao_dong = parseInt(ldInput.value) || 0;
                this.baoCao.ct_gio = response.ct_gio;
                
                await this.loadReport(this.baoCao.id);
                
                showToast('Cập nhật header thành công', 'success');
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi cập nhật header', 'error');
        }
    }
    
    async submitReport() {
        if (!this.baoCao) return;
        
        if (this.modifiedEntries.size > 0) {
            await this.saveChanges();
        }
        
        if (!confirm('Bạn có chắc muốn chốt báo cáo này?')) return;
        
        try {
            showLoading();
            const response = await api('POST', `/bao-cao/${this.baoCao.id}/submit`);
            if (response.success) {
                showToast('Đã chốt báo cáo', 'success');
                await this.loadReport(this.baoCao.id);
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi chốt báo cáo', 'error');
        } finally {
            hideLoading();
        }
    }
    
    showReportList(updateUrl = true) {
        if (this.modifiedEntries.size > 0) {
            this.saveChanges();
        }
        this.baoCao = null;
        if (updateUrl) {
            window.history.pushState(null, '', window.location.pathname);
        }
        this.loadReportList();
    }
    
    handleGlobalKeyDown(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            if (this.modifiedEntries.size > 0) {
                this.saveChanges();
            }
        }
    }
    
    async logout() {
        try {
            await api('GET', '/auth/logout');
            window.location.href = 'index.php';
        } catch (error) {
            window.location.href = 'index.php';
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('editorContainer')) {
        window.app = new NangSuatApp();
    }
});
