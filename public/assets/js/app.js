import { fetchCsrfToken, api } from './modules/api.js';
import { showLoading, hideLoading, showToast, updateStatusBar, escapeHtml } from './modules/utils.js';
import { GridManager } from './modules/grid.js';
import { HistoryModule } from './modules/history.js';
import { Router } from './modules/router.js';
import networkMonitor from './modules/network-monitor.js';
import { reportListMethods } from './modules/nhap/report-list.js';
import { createModalMethods } from './modules/nhap/create-modal.js';
import { poEditorMethods } from './modules/nhap/po-editor.js';
import { editorMethods } from './modules/nhap/editor.js';
import { autosaveMethods } from './modules/nhap/autosave.js';
import { workflowMethods } from './modules/nhap/workflow.js';

class NangSuatApp {
    constructor() {
        this.baoCao = null;
        this.version = 1;
        this.modifiedEntries = new Map();
        this.saveTimer = null;
        this.isSaving = false;
        
        this.gridManager = new GridManager(this);
        this.historyModule = null;
        this.router = new Router(this);
        this.context = null;
        
        // Setup network monitor callbacks
        this.setupNetworkCallbacks();
        
        this.init();
    }
    
    /**
     * Setup callbacks cho network monitor
     * Pause auto-save khi offline, resume khi online
     */
    setupNetworkCallbacks() {
        // Pause auto-save khi offline
        networkMonitor.on('network:offline', () => {
            if (this.saveTimer) {
                clearTimeout(this.saveTimer);
                this.saveTimer = null;
            }
            updateStatusBar('Mất kết nối - Dữ liệu chưa được lưu', 'error');
        });

        // Resume khi online
        networkMonitor.on('network:online', () => {
            // Trigger save nếu có pending changes
            if (this.modifiedEntries.size > 0) {
                this.scheduleSave();
            } else {
                updateStatusBar('Đã kết nối lại', 'success');
            }
        });
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

        this.loadContext().then(async () => {
            const hasQueryParams = this.handleInitialRouteQueryParams();
            
            this.router.start(false);
            
            if (!hasQueryParams) {
                this.router.handleHashChange();
            } else {
                if (!window.location.hash) {
                    this.router.navigate('/nhap-bao-cao');
                }
            }
        });
        
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.reportId) {
                this.loadReport(e.state.reportId, false);
            } else if (e.state === null && !window.location.search.includes('line=')) {
                this.showReportList(false);
            }
        });
    }

    checkHistoryPermission(callback) {
        if (this.context && this.context.can_view_history) {
            callback();
        } else {
            showToast('Bạn không có quyền xem lịch sử', 'error');
            this.router.navigate('/nhap-bao-cao');
        }
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            const closeEl = e.target.closest('[data-action="close-modal"]');
            if (!closeEl) return;
            if (e.target === closeEl || !closeEl.classList.contains('modal')) {
                document.getElementById(closeEl.dataset.target)?.classList.add('hidden');
            }
        });

        document.getElementById('createForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.createReport();
        });

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
                this.context = response.data;
                this.renderHeader(response.data);
                
                this.updateCreateButtonVisibility();
                
                if (this.context.can_view_history) {
                    const tabHistory = document.getElementById('tabHistory');
                    const mainTabs = document.getElementById('mainTabs');
                    if (tabHistory) tabHistory.classList.remove('hidden');
                    if (mainTabs) mainTabs.classList.remove('hidden');
                    
                    if (!this.historyModule) {
                        this.historyModule = new HistoryModule(this);
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
            if (this.baoCao) this.renderAppBar();
            else this.resetAppBar();
        } else if (tabName === 'history') {
            tabHistory.classList.add('active', 'border-primary', 'text-primary');
            tabHistory.classList.remove('border-transparent', 'text-gray-500');
            tabInput.classList.remove('active', 'border-primary', 'text-primary');
            tabInput.classList.add('border-transparent', 'text-gray-500');
            
            contentHistory.classList.remove('hidden');
            contentInput.classList.add('hidden');
            this.resetAppBar();
        }
    }
    
    renderHeader(context) {
        const userInfo = document.querySelector('.user-info');
        if (userInfo && context.session) {
            userInfo.innerHTML = `${escapeHtml(context.session.ho_ten)} (${escapeHtml(context.session.line_ten || 'User')})`;
        }
    }

    handleGlobalKeyDown(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            if (this.modifiedEntries.size > 0) {
                this.saveChanges();
            }
        }
    }
}

Object.assign(NangSuatApp.prototype, reportListMethods, createModalMethods, poEditorMethods, editorMethods, autosaveMethods, workflowMethods);

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('editorContainer')) {
        new NangSuatApp();
    }
});
