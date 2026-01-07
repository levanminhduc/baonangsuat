import { Router } from './modules/router.js';
import { api } from './modules/admin-api.js';
import { closeModal, showLoading, hideLoading, showToast } from './modules/admin-utils.js';
import { getState, setAdminRouter } from './modules/admin/state.js';

// Global window assignments that are always needed
window.closeModal = closeModal;

// Module Configuration
const modules = {
    'lines': {
        path: './modules/admin/lines.js',
        deps: [],
        instance: null,
        loaded: false,
        init: async (m) => {
            m.init();
            window.editLine = m.editLine;
            window.deleteLine = m.deleteLine;
            await m.loadLines();
        }
    },
    'user-lines': {
        path: './modules/admin/user-lines.js',
        deps: ['lines', 'permissions'],
        instance: null,
        loaded: false,
        init: async (m) => {
            m.init();
            window.deleteUserLine = m.deleteUserLine;
            await m.loadUserLines();
            m.updateUserLineFilterSelect();
        }
    },
    'permissions': {
        path: './modules/admin/permissions.js',
        deps: [],
        instance: null,
        loaded: false,
        init: async (m) => {
            m.init();
            window.toggleHistoryPermission = m.toggleHistoryPermission;
            window.toggleCreateReportPermission = m.toggleCreateReportPermission;
            await m.loadUsers();
        }
    },
    'bulk-create': {
        path: './modules/admin/bulk-create.js',
        deps: ['lines', 'ma-hang', 'moc-gio'],
        instance: null,
        loaded: false,
        init: async (m) => {
            m.init();
            m.renderFormOptions();
        }
    },
    'ma-hang': {
        path: './modules/admin/ma-hang.js',
        deps: [],
        instance: null,
        loaded: false,
        init: async (m) => {
            m.init();
            window.editMaHang = m.editMaHang;
            window.deleteMaHang = m.deleteMaHang;
            await m.loadMaHang();
        }
    },
    'cong-doan': {
        path: './modules/admin/cong-doan.js',
        deps: [],
        instance: null,
        loaded: false,
        init: async (m) => {
            m.init();
            window.editCongDoan = m.editCongDoan;
            window.deleteCongDoan = m.deleteCongDoan;
            await m.loadCongDoan();
        }
    },
    'routing': {
        path: './modules/admin/routing.js',
        deps: ['lines', 'ma-hang', 'cong-doan'],
        instance: null,
        loaded: false,
        init: async (m) => {
            m.init();
            window.editRouting = m.editRouting;
            window.deleteRouting = m.deleteRouting;
            // Routing doesn't load data initially, waits for filter selection
        }
    },
    'presets': {
        path: './modules/admin/presets.js',
        deps: ['moc-gio'], // Needs caList from moc-gio
        instance: null,
        loaded: false,
        init: async (m) => {
            m.init();
            window.viewPresetDetail = m.viewPresetDetail;
            window.editPreset = m.editPreset;
            window.deletePreset = m.deletePreset;
            window.showCopyPresetModal = m.showCopyPresetModal;
            window.showAssignLinesModal = m.showAssignLinesModal;
            window.unassignLine = m.unassignLine;
            await m.loadPresets();
        }
    },
    'moc-gio': {
        path: './modules/admin/moc-gio.js',
        deps: ['lines'],
        instance: null,
        loaded: false,
        init: async (m) => {
            m.init();
            window.editMocGio = m.editMocGio;
            window.deleteMocGio = m.deleteMocGio;
            await m.loadCaList();
            m.updateMocGioLineSelect();
        }
    }
};

async function loadModule(name) {
    if (!modules[name]) {
        console.error(`Module ${name} not found`);
        return;
    }

    const moduleConfig = modules[name];
    if (moduleConfig.loaded) return moduleConfig.instance;

    // Load dependencies first
    if (moduleConfig.deps && moduleConfig.deps.length > 0) {
        for (const dep of moduleConfig.deps) {
            await loadModule(dep);
        }
    }

    try {
        // Dynamic import
        const module = await import(moduleConfig.path);
        moduleConfig.instance = module;
        
        // Initialize
        if (moduleConfig.init) {
            await moduleConfig.init(module);
        }

        moduleConfig.loaded = true;
        return module;
    } catch (error) {
        console.error(`Error loading module ${name}:`, error);
        throw error;
    }
}

function initRouter() {
    const legacyAliases = {
        'lines': '/lines',
        'user-lines': '/user-lines',
        'permissions': '/permissions',
        'ma-hang': '/ma-hang',
        'cong-doan': '/cong-doan',
        'routing': '/routing',
        'presets': '/presets',
        'moc-gio': '/moc-gio',
        'bulk-create': '/bulk-create'
    };
    
    const adminRouter = new Router(null, {
        legacyAliases: legacyAliases,
        defaultRoute: '/lines'
    });
    
    // Helper to add routes
    const addTabRoute = (path, tabName) => {
        adminRouter.add(path, () => switchTab(tabName, false));
    };

    Object.keys(modules).forEach(name => {
        addTabRoute('/' + name, name);
    });
    
    setAdminRouter(adminRouter);
    return adminRouter;
}

async function switchTab(tabName, updateHistory = true) {
    // Show loading immediately
    showLoading();

    try {
        // Load the module and its dependencies
        await loadModule(tabName);

        // Update UI
        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.admin-tab-content').forEach(c => c.classList.remove('active'));
        
        const tabBtn = document.querySelector(`[data-tab="${tabName}"]`);
        const tabContent = document.getElementById(`${tabName}Tab`);
        
        if (tabBtn) tabBtn.classList.add('active');
        if (tabContent) tabContent.classList.add('active');

        if (updateHistory) {
            history.pushState(null, null, `#${tabName}`);
        }

        window.scrollTo(0, 0);
    } catch (error) {
        showToast('Lỗi tải dữ liệu. Vui lòng thử lại.', 'error');
        console.error(error);
    } finally {
        hideLoading();
    }
}

function bindGlobalEvents() {
    document.getElementById('logoutBtn').addEventListener('click', logout);
    
    document.querySelectorAll('.admin-tab').forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            const tabName = tab.dataset.tab;
            const state = getState();
            if (state.adminRouter) {
                state.adminRouter.navigate('/' + tabName);
            } else {
                switchTab(tabName);
            }
        });
    });
}

async function logout() {
    await api('GET', '/auth/logout');
    window.location.href = 'index.php';
}

document.addEventListener('DOMContentLoaded', () => {
    bindGlobalEvents();
    
    // Start router
    const adminRouter = initRouter();
    
    // If no hash/route, default to lines which is handled by router's defaultRoute or initial logic
    // Router.start(true) triggers the current route
    adminRouter.start(true);
});
