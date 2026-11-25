import { api } from '../api.js';
import { showToast } from '../utils.js';
import { state } from './state.js';
import { loadChartData } from './data.js';

/**
 * Tìm dòng trong danh sách mã hàng: ưu tiên bao_cao_id; URL cũ không có thì lấy báo cáo đầu của mã hàng + ca
 */
export function findMaHangItem(maHangId, caId, baoCaoId) {
    if (baoCaoId) {
        const byId = state.maHangList.find(i => i.bao_cao_id === baoCaoId && i.id === maHangId && i.ca_id === caId);
        if (byId) {
            return byId;
        }
    }
    return state.maHangList.find(i => i.id === maHangId && i.ca_id === caId) || null;
}

/**
 * Parse query params and populate state.filters
 * Read line_id, ngay, ma_hang_id, ca_id from URL
 */
export function loadFiltersFromURL() {
    const params = new URLSearchParams(window.location.search);
    
    // Store URL filters for deferred selection after dropdowns load
    state.urlFilters = {
        line_id: params.get('line_id') ? parseInt(params.get('line_id')) : null,
        ngay: params.get('ngay') || null,
        ma_hang_id: params.get('ma_hang_id') ? parseInt(params.get('ma_hang_id')) : null,
        ca_id: params.get('ca_id') ? parseInt(params.get('ca_id')) : null,
        bao_cao_id: params.get('bao_cao_id') ? parseInt(params.get('bao_cao_id')) : null
    };
    
    // Apply ngay from URL if present, otherwise use today's date (from input default)
    if (state.urlFilters.ngay) {
        state.filters.ngay = state.urlFilters.ngay;
    }
    
    // For non-admin users, line_id comes from session, not URL
    if (!state.isAdmin && state.urlFilters.line_id) {
        // Ignore URL line_id for non-admin (security)
        state.urlFilters.line_id = null;
    }
}

/**
 * Update browser URL with current filter state
 * @param {boolean} replaceState - Use replaceState instead of pushState (for initial load)
 */
export function updateURL(replaceState = false) {
    const params = new URLSearchParams();
    
    // Only include non-null values
    if (state.filters.line_id) {
        params.set('line_id', state.filters.line_id);
    }
    if (state.filters.ngay) {
        params.set('ngay', state.filters.ngay);
    }
    if (state.filters.ma_hang_id) {
        params.set('ma_hang_id', state.filters.ma_hang_id);
    }
    if (state.filters.ca_id) {
        params.set('ca_id', state.filters.ca_id);
    }
    if (state.filters.bao_cao_id) {
        params.set('bao_cao_id', state.filters.bao_cao_id);
    }
    
    const queryString = params.toString();
    const newUrl = queryString 
        ? `${window.location.pathname}?${queryString}`
        : window.location.pathname;
    
    // Avoid pushing same state
    if (window.location.search === (queryString ? `?${queryString}` : '')) {
        return;
    }
    
    if (replaceState) {
        window.history.replaceState({ filters: state.filters }, '', newUrl);
    } else {
        window.history.pushState({ filters: state.filters }, '', newUrl);
    }
}

/**
 * Set dropdown/input values from state.filters
 */
export function syncUIWithFilters() {
    // Sync date input
    if (state.filters.ngay && state.elements.filterDate) {
        state.elements.filterDate.value = state.filters.ngay;
    }
    
    // Sync LINE dropdown (admin only)
    if (state.isAdmin && state.filters.line_id && state.elements.filterLine) {
        state.elements.filterLine.value = state.filters.line_id;
    }
    
    // Sync Ma Hang dropdown - option value = bao_cao_id
    if (state.filters.ma_hang_id && state.filters.ca_id && state.elements.filterMaHang) {
        const item = findMaHangItem(state.filters.ma_hang_id, state.filters.ca_id, state.filters.bao_cao_id);
        
        if (item) {
            state.filters.bao_cao_id = item.bao_cao_id;
            state.elements.filterMaHang.value = String(item.bao_cao_id);
        } else {
            // Invalid ma_hang_id from URL - clear it
            state.filters.ma_hang_id = null;
            state.filters.ca_id = null;
            state.filters.bao_cao_id = null;
        }
    }
}

/**
 * Handle browser back/forward navigation
 */
export function bindPopState() {
    window.addEventListener('popstate', (event) => {
        // Reload filters from current URL
        loadFiltersFromURL();
        
        // Apply ngay from URL
        if (state.urlFilters.ngay) {
            state.filters.ngay = state.urlFilters.ngay;
        }
        
        // Apply line_id from URL (admin only)
        if (state.isAdmin && state.urlFilters.line_id) {
            state.filters.line_id = state.urlFilters.line_id;
        }
        
        // Sync UI with date/line
        syncUIWithFilters();
        
        // Reload ma_hang list, then select from URL and auto-load chart
        loadMaHangList().then(() => {
            // After ma_hang list loads, apply URL selection
            if (state.urlFilters.ma_hang_id && state.urlFilters.ca_id) {
                state.filters.ma_hang_id = state.urlFilters.ma_hang_id;
                state.filters.ca_id = state.urlFilters.ca_id;
                state.filters.bao_cao_id = state.urlFilters.bao_cao_id;
                syncUIWithFilters();
                
                // Auto-load chart if all filters are valid
                if (hasAllRequiredFilters()) {
                    loadChartData();
                }
            }
        });
    });
}

/**
 * Check if all required filters are present and valid
 */
export function hasAllRequiredFilters() {
    return !!(state.filters.line_id && state.filters.ngay && 
              state.filters.ma_hang_id && state.filters.ca_id);
}

export function bindElements() {
    state.elements = {
        filterLine: document.getElementById('filterLine'),
        filterDate: document.getElementById('filterDate'),
        filterMaHang: document.getElementById('filterMaHang'),
        filterCa: document.getElementById('filterCa'),
        loadChartBtn: document.getElementById('loadChartBtn'),
        loadingState: document.getElementById('loadingState'),
        noDataState: document.getElementById('noDataState'),
        noDataMessage: document.getElementById('noDataMessage'),
        chartSection: document.getElementById('chartSection'),
        chartCanvas: document.getElementById('productivityChart'),
        // Info elements
        infoLine: document.getElementById('infoLine'),
        infoDate: document.getElementById('infoDate'),
        infoCa: document.getElementById('infoCa'),
        infoMaHang: document.getElementById('infoMaHang'),
        infoCtns: document.getElementById('infoCtns'),
        // Summary elements
        summaryChiTieu: document.getElementById('summaryChiTieu'),
        summaryThucTe: document.getElementById('summaryThucTe'),
        summaryChenhLech: document.getElementById('summaryChenhLech'),
        summaryTyLe: document.getElementById('summaryTyLe'),
        // View toggle elements
        btnViewTongQuan: document.getElementById('btnViewTongQuan'),
        btnViewCongDoan: document.getElementById('btnViewCongDoan'),
        viewTongQuan: document.getElementById('viewTongQuan'),
        viewCongDoan: document.getElementById('viewCongDoan'),
        congDoanLoading: document.getElementById('congDoanLoading'),
        congDoanTable: document.getElementById('congDoanTable'),
        congDoanChart: document.getElementById('congDoanChart'),
        // Matrix view elements
        toggleMatrix: document.getElementById('toggleMatrix'),
        viewSummary: document.getElementById('viewSummary'),
        viewMatrix: document.getElementById('viewMatrix'),
        matrixLoading: document.getElementById('matrixLoading'),
        matrixNoData: document.getElementById('matrixNoData'),
        matrixTable: document.getElementById('matrixTable'),
        matrixTableHead: document.getElementById('matrixTableHead'),
        matrixTableBody: document.getElementById('matrixTableBody'),
        // Chart type toggle elements (AC-17 to AC-21)
        btnChartLine: document.getElementById('btnChartLine'),
        btnChartBar: document.getElementById('btnChartBar'),
        btnChartTable: document.getElementById('btnChartTable'),
        tongQuanChartContainer: document.getElementById('tongQuanChartContainer'),
        tongQuanTableContainer: document.getElementById('tongQuanTableContainer'),
        tongQuanTableHead: document.getElementById('tongQuanTableHead'),
        tongQuanTableBody: document.getElementById('tongQuanTableBody'),
        toggleTongQuanMatrix: document.getElementById('toggleTongQuanMatrix'),
        tongQuanMatrixContainer: document.getElementById('tongQuanMatrixContainer'),
        tongQuanMatrixLoading: document.getElementById('tongQuanMatrixLoading'),
        tongQuanMatrixHead: document.getElementById('tongQuanMatrixHead'),
        tongQuanMatrixBody: document.getElementById('tongQuanMatrixBody'),
        btnChartMultiLine: document.getElementById('btnChartMultiLine'),
        btnChartStacked: document.getElementById('btnChartStacked')
    };
}

export function bindEvents() {
    // Load chart button
    state.elements.loadChartBtn.addEventListener('click', () => loadChartData());
    
    // LINE change - reload ma_hang list
    state.elements.filterLine.addEventListener('change', () => {
        state.filters.line_id = state.elements.filterLine.value ? parseInt(state.elements.filterLine.value) : null;
        loadMaHangList();
    });
    
    // Date change - reload ma_hang list
    state.elements.filterDate.addEventListener('change', () => {
        state.filters.ngay = state.elements.filterDate.value;
        loadMaHangList();
    });
    
    // Ma Hang change - update ca_id
    state.elements.filterMaHang.addEventListener('change', () => {
        const selected = parseInt(state.elements.filterMaHang.value);
        const item = selected ? state.maHangList.find(i => i.bao_cao_id === selected) : null;
        if (item) {
            state.filters.ma_hang_id = item.id;
            state.filters.ca_id = item.ca_id;
            state.filters.bao_cao_id = item.bao_cao_id;
        } else {
            state.filters.ma_hang_id = null;
            state.filters.ca_id = null;
            state.filters.bao_cao_id = null;
        }
    });
    
    // Enter key to load chart
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.target.matches('input[type="text"], textarea')) {
            loadChartData();
        }
    });
}

export async function loadInitialData() {
    // Set initial filter values from URL or defaults
    if (state.urlFilters.ngay) {
        state.filters.ngay = state.urlFilters.ngay;
        state.elements.filterDate.value = state.urlFilters.ngay;
    } else {
        state.filters.ngay = state.elements.filterDate.value;
    }
    
    if (state.isAdmin) {
        // Load lines for admin
        await loadLineList();
        
        // After line list loads, select from URL if present
        if (state.urlFilters.line_id) {
            const optionExists = Array.from(state.elements.filterLine.options)
                .some(opt => opt.value == state.urlFilters.line_id);
            
            if (optionExists) {
                state.filters.line_id = state.urlFilters.line_id;
                state.elements.filterLine.value = state.urlFilters.line_id;
                
                // Load ma_hang list for this line
                await loadMaHangList();
            }
        }
    } else {
        // Non-admin: use assigned line
        state.filters.line_id = window.appConfig.lineId;
        await loadMaHangList();
    }
    
    // After ma_hang list loads, select from URL and auto-load chart if all params present
    if (state.urlFilters.ma_hang_id && state.urlFilters.ca_id && state.maHangList.length > 0) {
        const item = findMaHangItem(state.urlFilters.ma_hang_id, state.urlFilters.ca_id, state.urlFilters.bao_cao_id);
        
        if (item) {
            state.filters.ma_hang_id = item.id;
            state.filters.ca_id = item.ca_id;
            state.filters.bao_cao_id = item.bao_cao_id;
            state.elements.filterMaHang.value = String(item.bao_cao_id);
            
            // Auto-load chart if all required params are present in URL
            if (hasAllRequiredFilters()) {
                await loadChartData();
            }
        }
    }
    
    // Mark initial load complete
    state.isInitialLoad = false;
}

export async function loadLineList() {
    try {
        const response = await api('GET', '/admin/lines');
        if (response.success && Array.isArray(response.data)) {
            const select = state.elements.filterLine;
            select.innerHTML = '<option value="">-- Chọn LINE --</option>';
            response.data.forEach(line => {
                const option = document.createElement('option');
                option.value = line.id;
                option.textContent = `${line.ma_line} - ${line.ten_line}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load lines:', error);
        showToast('Không thể tải danh sách LINE', 'error');
    }
}

export async function loadMaHangList() {
    const lineId = state.filters.line_id;
    const ngay = state.filters.ngay;
    
    // Clear current selection
    state.elements.filterMaHang.innerHTML = '<option value="">-- Chọn mã hàng --</option>';
    state.filters.ma_hang_id = null;
    state.filters.ca_id = null;
    state.filters.bao_cao_id = null;
    
    if (!lineId || !ngay) {
        return;
    }
    
    try {
        const response = await api('GET', `/bieu-do/ma-hang-list?line_id=${lineId}&ngay=${ngay}`);
        if (response.success && Array.isArray(response.data)) {
            state.maHangList = response.data;
            
            if (response.data.length === 0) {
                state.elements.filterMaHang.innerHTML = '<option value="">Không có mã hàng</option>';
                return;
            }
            
            state.elements.filterMaHang.innerHTML = '<option value="">-- Chọn mã hàng --</option>';
            response.data.forEach(item => {
                const option = document.createElement('option');
                option.value = String(item.bao_cao_id);
                let label = `${item.ma_hang} - ${item.ten_hang} (${item.ca_ten})`;
                if (item.po_number) {
                    label += ` · PO ${item.po_number}`;
                }
                if (item.sub_art_code) {
                    label += ` · ${item.sub_art_code}`;
                }
                option.textContent = label;
                state.elements.filterMaHang.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load ma_hang list:', error);
        showToast('Không thể tải danh sách mã hàng', 'error');
    }
}
