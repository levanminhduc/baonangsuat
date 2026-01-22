/**
 * Biểu đồ năng suất - Chart Module
 * Displays productivity comparison chart (Chỉ tiêu vs Thực tế)
 */

import { fetchCsrfToken, api } from './modules/api.js';
import { showToast } from './modules/utils.js';

class BieuDoApp {
    constructor() {
        this.chart = null;
        this.congDoanChart = null;
        this.currentView = 'congdoan'; // Default to 'congdoan' (Theo công đoạn view)
        this.congDoanDataLoaded = false;
        this.matrixDataLoaded = false;
        this.filters = {
            line_id: null,
            ma_hang_id: null,
            ngay: null,
            ca_id: null
        };
        this.maHangList = [];
        this.isAdmin = window.appConfig?.isAdmin ?? false;
        
        // Chart Type Toggle state (AC-17 to AC-21)
        this.tongQuanChartType = 'line';  // 'line' | 'bar' | 'table' | 'multiline'
        this.tongQuanData = null;         // Cache data for re-rendering
        this.tongQuanMatrixData = null;
        this.tongQuanMatrixLoaded = false;
        
        // URL filter persistence state
        this.urlFilters = null;  // Filters loaded from URL (for deferred selection)
        this.isInitialLoad = true;  // Track if this is initial page load
        
        this.init();
    }
    
    async init() {
        await fetchCsrfToken();
        this.bindElements();
        this.loadFiltersFromURL();  // Load URL params early
        this.bindEvents();
        this.bindViewToggle();
        this.bindMatrixToggle();
        this.bindChartTypeToggle();
        this.bindTongQuanMatrixToggle();
        this.bindPopState();  // Handle browser back/forward
        await this.loadInitialData();
    }
    
    // =====================
    // URL Filter Persistence Methods
    // =====================
    
    /**
     * Parse query params and populate this.filters
     * Read line_id, ngay, ma_hang_id, ca_id from URL
     */
    loadFiltersFromURL() {
        const params = new URLSearchParams(window.location.search);
        
        // Store URL filters for deferred selection after dropdowns load
        this.urlFilters = {
            line_id: params.get('line_id') ? parseInt(params.get('line_id')) : null,
            ngay: params.get('ngay') || null,
            ma_hang_id: params.get('ma_hang_id') ? parseInt(params.get('ma_hang_id')) : null,
            ca_id: params.get('ca_id') ? parseInt(params.get('ca_id')) : null
        };
        
        // Apply ngay from URL if present, otherwise use today's date (from input default)
        if (this.urlFilters.ngay) {
            this.filters.ngay = this.urlFilters.ngay;
        }
        
        // For non-admin users, line_id comes from session, not URL
        if (!this.isAdmin && this.urlFilters.line_id) {
            // Ignore URL line_id for non-admin (security)
            this.urlFilters.line_id = null;
        }
    }
    
    /**
     * Update browser URL with current filter state
     * @param {boolean} replaceState - Use replaceState instead of pushState (for initial load)
     */
    updateURL(replaceState = false) {
        const params = new URLSearchParams();
        
        // Only include non-null values
        if (this.filters.line_id) {
            params.set('line_id', this.filters.line_id);
        }
        if (this.filters.ngay) {
            params.set('ngay', this.filters.ngay);
        }
        if (this.filters.ma_hang_id) {
            params.set('ma_hang_id', this.filters.ma_hang_id);
        }
        if (this.filters.ca_id) {
            params.set('ca_id', this.filters.ca_id);
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
            window.history.replaceState({ filters: this.filters }, '', newUrl);
        } else {
            window.history.pushState({ filters: this.filters }, '', newUrl);
        }
    }
    
    /**
     * Set dropdown/input values from this.filters
     */
    syncUIWithFilters() {
        // Sync date input
        if (this.filters.ngay && this.elements.filterDate) {
            this.elements.filterDate.value = this.filters.ngay;
        }
        
        // Sync LINE dropdown (admin only)
        if (this.isAdmin && this.filters.line_id && this.elements.filterLine) {
            this.elements.filterLine.value = this.filters.line_id;
        }
        
        // Sync Ma Hang dropdown - construct composite value "{ma_hang_id}_{ca_id}"
        if (this.filters.ma_hang_id && this.filters.ca_id && this.elements.filterMaHang) {
            const compositeValue = `${this.filters.ma_hang_id}_${this.filters.ca_id}`;
            const optionExists = Array.from(this.elements.filterMaHang.options)
                .some(opt => opt.value === compositeValue);
            
            if (optionExists) {
                this.elements.filterMaHang.value = compositeValue;
            } else {
                // Invalid ma_hang_id from URL - clear it
                this.filters.ma_hang_id = null;
                this.filters.ca_id = null;
            }
        }
    }
    
    /**
     * Handle browser back/forward navigation
     */
    bindPopState() {
        window.addEventListener('popstate', (event) => {
            // Reload filters from current URL
            this.loadFiltersFromURL();
            
            // Apply ngay from URL
            if (this.urlFilters.ngay) {
                this.filters.ngay = this.urlFilters.ngay;
            }
            
            // Apply line_id from URL (admin only)
            if (this.isAdmin && this.urlFilters.line_id) {
                this.filters.line_id = this.urlFilters.line_id;
            }
            
            // Sync UI with date/line
            this.syncUIWithFilters();
            
            // Reload ma_hang list, then select from URL and auto-load chart
            this.loadMaHangList().then(() => {
                // After ma_hang list loads, apply URL selection
                if (this.urlFilters.ma_hang_id && this.urlFilters.ca_id) {
                    this.filters.ma_hang_id = this.urlFilters.ma_hang_id;
                    this.filters.ca_id = this.urlFilters.ca_id;
                    this.syncUIWithFilters();
                    
                    // Auto-load chart if all filters are valid
                    if (this.hasAllRequiredFilters()) {
                        this.loadChartData();
                    }
                }
            });
        });
    }
    
    /**
     * Check if all required filters are present and valid
     */
    hasAllRequiredFilters() {
        return !!(this.filters.line_id && this.filters.ngay && 
                  this.filters.ma_hang_id && this.filters.ca_id);
    }
    
    bindElements() {
        this.elements = {
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
    
    bindEvents() {
        // Load chart button
        this.elements.loadChartBtn.addEventListener('click', () => this.loadChartData());
        
        // LINE change - reload ma_hang list
        this.elements.filterLine.addEventListener('change', () => {
            this.filters.line_id = this.elements.filterLine.value ? parseInt(this.elements.filterLine.value) : null;
            this.loadMaHangList();
        });
        
        // Date change - reload ma_hang list
        this.elements.filterDate.addEventListener('change', () => {
            this.filters.ngay = this.elements.filterDate.value;
            this.loadMaHangList();
        });
        
        // Ma Hang change - update ca_id
        this.elements.filterMaHang.addEventListener('change', () => {
            const selected = this.elements.filterMaHang.value;
            if (selected) {
                const [maHangId, caId] = selected.split('_');
                this.filters.ma_hang_id = parseInt(maHangId);
                this.filters.ca_id = parseInt(caId);
            } else {
                this.filters.ma_hang_id = null;
                this.filters.ca_id = null;
            }
        });
        
        // Enter key to load chart
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.target.matches('input[type="text"], textarea')) {
                this.loadChartData();
            }
        });
    }
    
    bindViewToggle() {
        this.elements.btnViewTongQuan.addEventListener('click', () => this.switchView('tongquan'));
        this.elements.btnViewCongDoan.addEventListener('click', () => this.switchView('congdoan'));
    }
    
    switchView(view) {
        this.currentView = view;
        
        // Toggle visibility
        this.elements.viewTongQuan.classList.toggle('hidden', view !== 'tongquan');
        this.elements.viewCongDoan.classList.toggle('hidden', view !== 'congdoan');
        
        // Toggle button styles
        if (view === 'tongquan') {
            this.elements.btnViewTongQuan.classList.add('bg-primary', 'text-white');
            this.elements.btnViewTongQuan.classList.remove('text-gray-600', 'hover:bg-gray-200');
            this.elements.btnViewCongDoan.classList.remove('bg-primary', 'text-white');
            this.elements.btnViewCongDoan.classList.add('text-gray-600', 'hover:bg-gray-200');
        } else {
            this.elements.btnViewCongDoan.classList.add('bg-primary', 'text-white');
            this.elements.btnViewCongDoan.classList.remove('text-gray-600', 'hover:bg-gray-200');
            this.elements.btnViewTongQuan.classList.remove('bg-primary', 'text-white');
            this.elements.btnViewTongQuan.classList.add('text-gray-600', 'hover:bg-gray-200');
        }
        
        // Load công đoạn data if switching to that view and not already loaded
        if (view === 'congdoan' && !this.congDoanDataLoaded) {
            this.loadCongDoanData();
        }
    }
    
    async loadInitialData() {
        // Set initial filter values from URL or defaults
        if (this.urlFilters.ngay) {
            this.filters.ngay = this.urlFilters.ngay;
            this.elements.filterDate.value = this.urlFilters.ngay;
        } else {
            this.filters.ngay = this.elements.filterDate.value;
        }
        
        if (this.isAdmin) {
            // Load lines for admin
            await this.loadLineList();
            
            // After line list loads, select from URL if present
            if (this.urlFilters.line_id) {
                const optionExists = Array.from(this.elements.filterLine.options)
                    .some(opt => opt.value == this.urlFilters.line_id);
                
                if (optionExists) {
                    this.filters.line_id = this.urlFilters.line_id;
                    this.elements.filterLine.value = this.urlFilters.line_id;
                    
                    // Load ma_hang list for this line
                    await this.loadMaHangList();
                }
            }
        } else {
            // Non-admin: use assigned line
            this.filters.line_id = window.appConfig.lineId;
            await this.loadMaHangList();
        }
        
        // After ma_hang list loads, select from URL and auto-load chart if all params present
        if (this.urlFilters.ma_hang_id && this.urlFilters.ca_id && this.maHangList.length > 0) {
            const compositeValue = `${this.urlFilters.ma_hang_id}_${this.urlFilters.ca_id}`;
            const optionExists = Array.from(this.elements.filterMaHang.options)
                .some(opt => opt.value === compositeValue);
            
            if (optionExists) {
                this.filters.ma_hang_id = this.urlFilters.ma_hang_id;
                this.filters.ca_id = this.urlFilters.ca_id;
                this.elements.filterMaHang.value = compositeValue;
                
                // Auto-load chart if all required params are present in URL
                if (this.hasAllRequiredFilters()) {
                    await this.loadChartData();
                }
            }
        }
        
        // Mark initial load complete
        this.isInitialLoad = false;
    }
    
    async loadLineList() {
        try {
            const response = await api('GET', '/admin/lines');
            if (response.success && Array.isArray(response.data)) {
                const select = this.elements.filterLine;
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
    
    async loadMaHangList() {
        const lineId = this.filters.line_id;
        const ngay = this.filters.ngay;
        
        // Clear current selection
        this.elements.filterMaHang.innerHTML = '<option value="">-- Chọn mã hàng --</option>';
        this.filters.ma_hang_id = null;
        this.filters.ca_id = null;
        
        if (!lineId || !ngay) {
            return;
        }
        
        try {
            const response = await api('GET', `/bieu-do/ma-hang-list?line_id=${lineId}&ngay=${ngay}`);
            if (response.success && Array.isArray(response.data)) {
                this.maHangList = response.data;
                
                if (response.data.length === 0) {
                    this.elements.filterMaHang.innerHTML = '<option value="">Không có mã hàng</option>';
                    return;
                }
                
                this.elements.filterMaHang.innerHTML = '<option value="">-- Chọn mã hàng --</option>';
                response.data.forEach(item => {
                    const option = document.createElement('option');
                    // Store both ma_hang_id and ca_id in value
                    option.value = `${item.id}_${item.ca_id}`;
                    option.textContent = `${item.ma_hang} - ${item.ten_hang} (${item.ca_ten})`;
                    this.elements.filterMaHang.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Failed to load ma_hang list:', error);
            showToast('Không thể tải danh sách mã hàng', 'error');
        }
    }
    
    async loadChartData() {
        const { line_id, ma_hang_id, ngay, ca_id } = this.filters;
        
        // Validate filters
        if (!line_id) {
            showToast('Vui lòng chọn LINE', 'warning');
            return;
        }
        if (!ngay) {
            showToast('Vui lòng chọn ngày', 'warning');
            return;
        }
        if (!ma_hang_id || !ca_id) {
            showToast('Vui lòng chọn mã hàng', 'warning');
            return;
        }
        
        // Show loading
        this.showLoading();
        
        // Reset công đoạn and matrix data loaded flags when loading new data
        this.congDoanDataLoaded = false;
        this.matrixDataLoaded = false;
        this.tongQuanMatrixLoaded = false;
        
        // Reset matrix toggle to off
        if (this.elements.toggleMatrix) {
            this.elements.toggleMatrix.checked = false;
            this.toggleMatrixView(false);
        }
        
        if (this.elements.toggleTongQuanMatrix) {
            this.elements.toggleTongQuanMatrix.checked = false;
            this.toggleTongQuanMatrix(false);
        }
        
        try {
            const response = await api('GET', `/bieu-do/so-sanh?line_id=${line_id}&ma_hang_id=${ma_hang_id}&ngay=${ngay}&ca_id=${ca_id}`);
            
            if (response.success) {
                this.renderChart(response.data);
                this.showChart();
                
                // Update URL with current filter values after successful load
                this.updateURL(this.isInitialLoad);
                
                // If currently on công đoạn view, load that data too
                if (this.currentView === 'congdoan') {
                    this.loadCongDoanData();
                }
            } else {
                this.showNoData(response.message || 'Không có dữ liệu');
            }
        } catch (error) {
            console.error('Failed to load chart data:', error);
            this.showNoData('Có lỗi xảy ra khi tải dữ liệu');
            showToast('Không thể tải dữ liệu biểu đồ', 'error');
        }
    }
    
    async loadCongDoanData() {
        const { line_id, ma_hang_id, ngay, ca_id } = this.filters;
        
        // Skip if filters are not valid
        if (!line_id || !ma_hang_id || !ca_id) {
            return;
        }
        
        // Show loading
        this.elements.congDoanLoading.classList.remove('hidden');
        
        try {
            const params = new URLSearchParams({
                line_id: line_id,
                ma_hang_id: ma_hang_id,
                ngay: ngay,
                ca_id: ca_id
            });
            
            const response = await api('GET', `/bieu-do/so-sanh-chi-tiet?${params}`);
            
            if (response.success) {
                this.renderCongDoanTable(response.data.cong_doan_details);
                this.renderCongDoanChart(response.data.cong_doan_chart);
                this.congDoanDataLoaded = true;
            } else {
                showToast(response.message || 'Không thể tải dữ liệu công đoạn', 'error');
            }
        } catch (error) {
            console.error('Failed to load công đoạn data:', error);
            showToast('Không thể tải dữ liệu công đoạn', 'error');
        } finally {
            this.elements.congDoanLoading.classList.add('hidden');
        }
    }
    
    renderCongDoanTable(details) {
        const tbody = this.elements.congDoanTable.querySelector('tbody');
        
        if (!details || details.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        Không có dữ liệu công đoạn
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = details.map(cd => `
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900">${this.escapeHtml(cd.ten_cong_doan)}</td>
                <td class="px-4 py-3 text-right text-gray-700">${this.formatNumber(cd.chi_tieu)}</td>
                <td class="px-4 py-3 text-right text-gray-700">${this.formatNumber(cd.thuc_te)}</td>
                <td class="px-4 py-3 text-right ${cd.chenh_lech < 0 ? 'text-red-600' : 'text-green-600'} font-medium">
                    ${cd.chenh_lech >= 0 ? '+' : ''}${this.formatNumber(cd.chenh_lech)}
                </td>
                <td class="px-4 py-3 text-right font-medium ${cd.ty_le >= 100 ? 'text-green-600' : cd.ty_le >= 80 ? 'text-yellow-600' : 'text-red-600'}">
                    ${cd.ty_le.toFixed(1)}%
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 rounded-full text-xs font-medium ${
                        cd.trang_thai === 'dat' 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-red-100 text-red-800'
                    }">
                        ${cd.trang_thai === 'dat' ? 'Đạt' : 'Chưa đạt'}
                    </span>
                </td>
            </tr>
        `).join('');
    }
    
    renderCongDoanChart(chartData) {
        if (!chartData || !chartData.labels || chartData.labels.length === 0) {
            return;
        }
        
        const ctx = this.elements.congDoanChart.getContext('2d');
        
        if (this.congDoanChart) {
            this.congDoanChart.destroy();
        }
        
        const mocGioColors = ['#86EFAC', '#4ADE80', '#22C55E', '#16A34A', '#15803D', '#166534', '#14532D', '#052E16'];
        const mocGioLabels = chartData.moc_gio_labels || [];
        const mocGioThucTe = chartData.moc_gio_thuc_te || {};
        const mocGioIds = Object.keys(mocGioThucTe).map(id => parseInt(id)).sort((a, b) => a - b);
        
        const datasets = [];
        const chiTieuColors = ['#FCA5A5', '#F87171', '#EF4444', '#DC2626', '#B91C1C', '#991B1B', '#7F1D1D', '#450A0A'];

        if (chartData.moc_gio_chi_tieu) {
            const mocGioChiTieu = chartData.moc_gio_chi_tieu;
            const targetMocGioIds = Object.keys(mocGioChiTieu).map(id => parseInt(id)).sort((a, b) => a - b);

            targetMocGioIds.forEach((mocGioId, idx) => {
                const currentChiTieu = mocGioChiTieu[mocGioId];
                let incrementalChiTieu = currentChiTieu;
                
                if (idx > 0) {
                    const prevMocGioId = targetMocGioIds[idx - 1];
                    const prevChiTieu = mocGioChiTieu[prevMocGioId] || 0;
                    incrementalChiTieu = currentChiTieu - prevChiTieu;
                }
                
                const label = chartData.moc_gio_labels[idx] || `Mốc ${idx + 1}`;

                datasets.push({
                    type: 'line',
                    label: `CT ${label}`,
                    data: Array(chartData.labels.length).fill(incrementalChiTieu),
                    borderColor: chiTieuColors[idx % chiTieuColors.length],
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 3,
                    pointBackgroundColor: chiTieuColors[idx % chiTieuColors.length],
                    fill: false,
                    order: 0,
                    _cumulativeChiTieu: currentChiTieu
                });
            });
        } else {
            datasets.push({
                type: 'line',
                label: 'Chỉ tiêu',
                data: chartData.chi_tieu,
                borderColor: '#EF4444',
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 4,
                pointBackgroundColor: '#EF4444',
                fill: false,
                order: 0
            });
        }
        
        if (mocGioLabels.length > 0 && mocGioIds.length > 0) {
            mocGioIds.forEach((mocGioId, idx) => {
                const currentValues = mocGioThucTe[mocGioId] || [];
                
                const incrementalValues = currentValues.map((val, cdIdx) => {
                    if (idx === 0) {
                        return val;
                    } else {
                        const prevMocGioId = mocGioIds[idx - 1];
                        const prevVal = mocGioThucTe[prevMocGioId]?.[cdIdx] || 0;
                        return Math.max(0, val - prevVal);
                    }
                });
                
                datasets.push({
                    label: mocGioLabels[idx] || `Mốc ${idx + 1}`,
                    data: incrementalValues,
                    backgroundColor: mocGioColors[idx % mocGioColors.length],
                    borderWidth: 0,
                    borderRadius: idx === mocGioIds.length - 1 ? 4 : 0,
                    stack: 'actual',
                    _cumulativeData: currentValues
                });
            });
        } else {
            const thucTeColors = chartData.below_target.map(below => 
                below ? '#EF4444' : '#10B981'
            );
            datasets.push({
                label: 'Thực tế',
                data: chartData.thuc_te,
                backgroundColor: thucTeColors,
                borderWidth: 0,
                borderRadius: 4,
                stack: 'actual'
            });
        }
        
        this.congDoanChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { 
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            font: {
                                size: 13,
                                weight: '500'
                            }
                        }
                    },
                    title: { 
                        display: true, 
                        text: 'So sánh Chỉ tiêu vs Thực tế theo Công đoạn',
                        font: { size: 15, weight: '600' },
                        padding: { bottom: 20 }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        callbacks: {
                            label: (context) => {
                                const value = context.parsed.y;
                                const dataset = context.dataset;
                                if (dataset.type === 'line') {
                                    if (dataset._cumulativeChiTieu) {
                                        return `${dataset.label}: +${this.formatNumber(value)} (lũy kế: ${this.formatNumber(dataset._cumulativeChiTieu)})`;
                                    }
                                    return `${dataset.label}: ${this.formatNumber(value)}`;
                                }
                                if (dataset.stack === 'actual' && dataset._cumulativeData) {
                                    const cumulative = dataset._cumulativeData[context.dataIndex] || 0;
                                    return `${dataset.label}: +${this.formatNumber(value)} (lũy kế: ${this.formatNumber(cumulative)})`;
                                }
                                return `${context.dataset.label}: ${this.formatNumber(value)}`;
                            },
                            afterBody: (tooltipItems) => {
                                const actualItems = tooltipItems.filter(t => t.dataset.stack === 'actual');
                                if (actualItems.length > 0) {
                                    const dataIndex = actualItems[0].dataIndex;
                                    const chiTieu = chartData.chi_tieu[dataIndex];
                                    const lastActualDataset = actualItems[actualItems.length - 1].dataset;
                                    const thucTe = lastActualDataset._cumulativeData 
                                        ? lastActualDataset._cumulativeData[dataIndex] || 0
                                        : actualItems.reduce((sum, t) => sum + t.parsed.y, 0);
                                    const diff = thucTe - chiTieu;
                                    const pct = chiTieu > 0 ? ((thucTe / chiTieu) * 100).toFixed(1) : 0;
                                    return [
                                        '',
                                        `Tổng lũy kế: ${this.formatNumber(thucTe)}`,
                                        `Chênh lệch: ${diff >= 0 ? '+' : ''}${this.formatNumber(diff)}`,
                                        `Tỷ lệ: ${pct}%`
                                    ];
                                }
                                return [];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Công đoạn',
                            font: { size: 13, weight: '500' }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: { 
                        stacked: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Số lượng',
                            font: { size: 13, weight: '500' }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                }
            }
        });
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    renderChart(data) {
        // Cache data for re-rendering when chart type changes (AC-21)
        this.tongQuanData = data;
        
        // Update info header
        this.elements.infoLine.textContent = `${data.line.ma_line} - ${data.line.ten_line}`;
        this.elements.infoDate.textContent = this.formatDate(data.ngay);
        this.elements.infoCa.textContent = data.ca.ten_ca;
        this.elements.infoMaHang.textContent = `${data.ma_hang.ma_hang} - ${data.ma_hang.ten_hang}`;
        this.elements.infoCtns.textContent = this.formatNumber(data.ctns);
        
        // Update summary
        const summary = data.summary;
        this.elements.summaryChiTieu.textContent = this.formatNumber(summary.tong_chi_tieu);
        this.elements.summaryThucTe.textContent = this.formatNumber(summary.tong_thuc_te);
        
        // Chenh lech with color
        const chenhLech = summary.chenh_lech;
        this.elements.summaryChenhLech.textContent = (chenhLech >= 0 ? '+' : '') + this.formatNumber(chenhLech);
        this.elements.summaryChenhLech.className = `text-2xl font-bold ${chenhLech >= 0 ? 'text-green-600' : 'text-red-600'}`;
        
        // Ty le with color
        const tyLe = summary.ty_le_hoan_thanh;
        this.elements.summaryTyLe.textContent = `${tyLe}%`;
        this.elements.summaryTyLe.className = `text-2xl font-bold ${tyLe >= 100 ? 'text-green-600' : tyLe >= 80 ? 'text-yellow-600' : 'text-red-600'}`;
        
        // Render based on current chart type (AC-17 to AC-21)
        this.renderTongQuan();
    }
    
    showLoading() {
        this.elements.loadingState.classList.remove('hidden');
        this.elements.noDataState.classList.add('hidden');
        this.elements.chartSection.classList.add('hidden');
    }
    
    showNoData(message = 'Không có dữ liệu cho bộ lọc đã chọn') {
        this.elements.loadingState.classList.add('hidden');
        this.elements.noDataState.classList.remove('hidden');
        this.elements.chartSection.classList.add('hidden');
        this.elements.noDataMessage.textContent = message;
    }
    
    showChart() {
        this.elements.loadingState.classList.add('hidden');
        this.elements.noDataState.classList.add('hidden');
        this.elements.chartSection.classList.remove('hidden');
    }
    
    formatNumber(num) {
        return new Intl.NumberFormat('vi-VN').format(num);
    }
    
    formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('vi-VN', {
            weekday: 'short',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
    
    // =====================
    // Matrix View Methods
    // =====================
    
    bindMatrixToggle() {
        if (this.elements.toggleMatrix) {
            this.elements.toggleMatrix.addEventListener('change', (e) => {
                this.toggleMatrixView(e.target.checked);
            });
        }
    }
    
    toggleMatrixView(showMatrix) {
        if (!this.elements.viewSummary || !this.elements.viewMatrix) {
            return;
        }
        
        this.elements.viewSummary.classList.toggle('hidden', showMatrix);
        this.elements.viewMatrix.classList.toggle('hidden', !showMatrix);
        
        if (showMatrix && !this.matrixDataLoaded) {
            this.loadMatrixData();
        }
    }
    
    async loadMatrixData() {
        const { line_id, ma_hang_id, ngay, ca_id } = this.filters;
        
        // Skip if filters are not valid
        if (!line_id || !ma_hang_id || !ca_id) {
            return;
        }
        
        const loading = this.elements.matrixLoading;
        const noData = this.elements.matrixNoData;
        const table = this.elements.matrixTable;
        
        loading.classList.remove('hidden');
        noData.classList.add('hidden');
        table.classList.add('hidden');
        
        try {
            const params = new URLSearchParams({
                line_id: line_id,
                ma_hang_id: ma_hang_id,
                ngay: ngay,
                ca_id: ca_id
            });
            
            const response = await api('GET', `/bieu-do/so-sanh-matrix?${params}`);
            loading.classList.add('hidden');
            
            if (response.success && response.data.cong_doan_matrix && response.data.cong_doan_matrix.length > 0) {
                this.renderMatrixTable(response.data);
                table.classList.remove('hidden');
                this.matrixDataLoaded = true;
            } else {
                noData.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Failed to load matrix data:', error);
            loading.classList.add('hidden');
            noData.classList.remove('hidden');
            showToast('Không thể tải dữ liệu ma trận', 'error');
        }
    }
    
    renderMatrixTable(data) {
        const thead = this.elements.matrixTableHead;
        const tbody = this.elements.matrixTableBody;
        
        // Header: Công đoạn + each mốc giờ + Tổng
        thead.innerHTML = `
            <tr>
                <th class="px-3 py-3 text-left font-semibold sticky left-0 bg-gray-50 border-r z-10 min-w-[150px]">Công đoạn</th>
                ${data.moc_gio_list.map(mg => `
                    <th class="px-3 py-3 text-center font-semibold min-w-[90px] whitespace-nowrap">${this.escapeHtml(mg.gio)}</th>
                `).join('')}
                <th class="px-3 py-3 text-center font-semibold bg-gray-100 min-w-[90px]">TỔNG</th>
            </tr>
        `;
        
        // Body: each công đoạn row
        tbody.innerHTML = data.cong_doan_matrix.map(cd => `
            <tr class="border-b hover:bg-gray-50">
                <td class="px-3 py-2 font-medium sticky left-0 bg-white border-r z-10">${this.escapeHtml(cd.ten_cong_doan)}</td>
                ${data.moc_gio_list.map(mg => {
                    const cell = cd.moc_gio_data[mg.id] || { chi_tieu: 0, thuc_te: 0, ty_le: 0, trang_thai: 'chua_dat' };
                    return this.renderMatrixCell(cell, false);
                }).join('')}
                ${this.renderMatrixCell(cd.tong, true)}
            </tr>
        `).join('');
    }
    
    renderMatrixCell(cell, isSummary = false) {
        const bgColor = this.getCellColor(cell.trang_thai);
        const bgClass = isSummary ? 'bg-gray-50' : '';
        
        return `
            <td class="px-2 py-2 text-center ${bgClass}">
                <div class="rounded px-2 py-1.5 text-white text-xs font-medium ${bgColor} shadow-sm">
                    <div class="font-semibold">${this.formatNumber(cell.thuc_te)}/${this.formatNumber(cell.chi_tieu)}</div>
                    <div class="text-[10px] opacity-90 mt-0.5">${cell.ty_le.toFixed(0)}%</div>
                </div>
            </td>
        `;
    }
    
    getCellColor(trangThai) {
        switch (trangThai) {
            case 'dat':
                return 'bg-green-500';
            case 'can_chu_y':
                return 'bg-yellow-500';
            case 'chua_dat':
            default:
                return 'bg-red-500';
        }
    }
    
    // =====================
    // Chart Type Toggle Methods (AC-17 to AC-21)
    // =====================
    
    bindChartTypeToggle() {
        this.elements.btnChartLine?.addEventListener('click', () => this.switchChartType('line'));
        this.elements.btnChartBar?.addEventListener('click', () => this.switchChartType('bar'));
        this.elements.btnChartTable?.addEventListener('click', () => this.switchChartType('table'));
        this.elements.btnChartMultiLine?.addEventListener('click', () => this.switchChartType('multiline'));
        this.elements.btnChartStacked?.addEventListener('click', () => this.switchChartType('stacked'));
    }
    
    bindTongQuanMatrixToggle() {
        this.elements.toggleTongQuanMatrix?.addEventListener('change', (e) => {
            this.toggleTongQuanMatrix(e.target.checked);
        });
    }
    
    toggleTongQuanMatrix(show) {
        const chartContainer = this.elements.tongQuanChartContainer;
        const tableContainer = this.elements.tongQuanTableContainer;
        const matrixContainer = this.elements.tongQuanMatrixContainer;
        
        if (show) {
            chartContainer?.classList.add('hidden');
            tableContainer?.classList.add('hidden');
            matrixContainer?.classList.remove('hidden');
            if (!this.tongQuanMatrixLoaded) {
                this.loadTongQuanMatrix();
            }
        } else {
            matrixContainer?.classList.add('hidden');
            this.renderTongQuan();
        }
    }
    
    async loadTongQuanMatrix() {
        const loading = this.elements.tongQuanMatrixLoading;
        loading?.classList.remove('hidden');
        
        const params = new URLSearchParams({
            line_id: this.filters.line_id,
            ma_hang_id: this.filters.ma_hang_id,
            ngay: this.filters.ngay,
            ca_id: this.filters.ca_id
        });
        
        try {
            const response = await api('GET', `/bieu-do/so-sanh-matrix?${params}`);
            loading?.classList.add('hidden');
            
            if (response.success) {
                this.tongQuanMatrixData = response.data;
                this.renderTongQuanMatrix(response.data);
                this.tongQuanMatrixLoaded = true;
            }
        } catch (error) {
            loading?.classList.add('hidden');
            showToast('Không thể tải dữ liệu ma trận', 'error');
        }
    }
    
    renderTongQuanMatrix(data) {
        const thead = this.elements.tongQuanMatrixHead;
        const tbody = this.elements.tongQuanMatrixBody;
        
        thead.innerHTML = `
            <tr>
                <th class="px-3 py-2 text-left font-semibold sticky left-0 bg-gray-50 border-r">Công đoạn</th>
                ${data.moc_gio_list.map(mg => `<th class="px-3 py-2 text-center font-semibold min-w-[80px]">${mg.gio}</th>`).join('')}
                <th class="px-3 py-2 text-center font-semibold bg-gray-100 min-w-[80px]">TỔNG</th>
            </tr>
        `;
        
        tbody.innerHTML = data.cong_doan_matrix.map(cd => `
            <tr class="border-b hover:bg-gray-50">
                <td class="px-3 py-2 font-medium sticky left-0 bg-white border-r">${this.escapeHtml(cd.ten_cong_doan)}</td>
                ${data.moc_gio_list.map(mg => {
                    const cell = cd.moc_gio_data[mg.id] || { chi_tieu: 0, thuc_te: 0, ty_le: 0 };
                    return this.renderMatrixCellTongQuan(cell);
                }).join('')}
                ${this.renderMatrixCellTongQuan(cd.tong, true)}
            </tr>
        `).join('');
    }
    
    renderMatrixCellTongQuan(cell, isSummary = false) {
        const bgColor = cell.ty_le >= 95 ? 'bg-green-500' : cell.ty_le >= 80 ? 'bg-yellow-500' : 'bg-red-500';
        const bgClass = isSummary ? 'bg-gray-100' : '';
        
        return `
            <td class="px-2 py-2 text-center ${bgClass}">
                <div class="rounded px-2 py-1 text-white text-xs font-medium ${bgColor}">
                    <div>${cell.thuc_te}/${cell.chi_tieu}</div>
                    <div class="text-[10px] opacity-90">${cell.ty_le.toFixed(0)}%</div>
                </div>
            </td>
        `;
    }
    
    async renderMultiLineChart() {
        if (!this.tongQuanMatrixData) {
            await this.loadTongQuanMatrix();
        }
        const data = this.tongQuanMatrixData;
        if (!data) return;
        
        const ctx = this.elements.chartCanvas.getContext('2d');
        if (this.chart) this.chart.destroy();
        
        const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];
        
        const datasets = data.cong_doan_matrix.map((cd, idx) => ({
            label: cd.ten_cong_doan,
            data: data.moc_gio_list.map(mg => cd.moc_gio_data[mg.id]?.thuc_te || 0),
            borderColor: colors[idx % colors.length],
            backgroundColor: colors[idx % colors.length] + '20',
            borderWidth: 2,
            fill: false,
            tension: 0.3
        }));
        
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.moc_gio_list.map(mg => mg.gio),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Năng suất theo công đoạn' }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
    
    async renderStackedBarChart() {
        if (!this.tongQuanMatrixData) {
            await this.loadTongQuanMatrix();
        }
        const data = this.tongQuanMatrixData;
        if (!data) return;
        
        const ctx = this.elements.chartCanvas.getContext('2d');
        if (this.chart) this.chart.destroy();
        
        const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];
        
        const datasets = data.cong_doan_matrix.map((cd, idx) => ({
            label: cd.ten_cong_doan,
            data: data.moc_gio_list.map(mg => cd.moc_gio_data[mg.id]?.thuc_te || 0),
            backgroundColor: colors[idx % colors.length],
            borderRadius: 2
        }));
        
        this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.moc_gio_list.map(mg => mg.gio),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Năng suất xếp chồng theo công đoạn' }
                },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });
    }
    
    switchChartType(type) {
        this.tongQuanChartType = type;
        this.updateChartTypeButtons(type);
        
        if (this.elements.toggleTongQuanMatrix) {
            this.elements.toggleTongQuanMatrix.checked = false;
        }
        this.elements.tongQuanMatrixContainer?.classList.add('hidden');
        
        if (type === 'multiline') {
            this.elements.tongQuanChartContainer?.classList.remove('hidden');
            this.elements.tongQuanTableContainer?.classList.add('hidden');
            this.renderMultiLineChart();
        } else if (type === 'stacked') {
            this.elements.tongQuanChartContainer?.classList.remove('hidden');
            this.elements.tongQuanTableContainer?.classList.add('hidden');
            this.elements.tongQuanMatrixContainer?.classList.add('hidden');
            if (this.elements.toggleTongQuanMatrix) this.elements.toggleTongQuanMatrix.checked = false;
            this.renderStackedBarChart();
        } else {
            this.renderTongQuan();
        }
    }
    
    updateChartTypeButtons(activeType) {
        const buttons = {
            line: this.elements.btnChartLine,
            bar: this.elements.btnChartBar,
            table: this.elements.btnChartTable,
            multiline: this.elements.btnChartMultiLine,
            stacked: this.elements.btnChartStacked
        };
        
        Object.entries(buttons).forEach(([type, btn]) => {
            if (!btn) return;
            if (type === activeType) {
                btn.classList.add('bg-primary', 'text-white');
                btn.classList.remove('text-gray-600', 'hover:bg-gray-200');
            } else {
                btn.classList.remove('bg-primary', 'text-white');
                btn.classList.add('text-gray-600', 'hover:bg-gray-200');
            }
        });
    }
    
    renderTongQuan() {
        if (!this.tongQuanData) return;
        
        const chartContainer = this.elements.tongQuanChartContainer;
        const tableContainer = this.elements.tongQuanTableContainer;
        
        switch (this.tongQuanChartType) {
            case 'line':
                chartContainer?.classList.remove('hidden');
                tableContainer?.classList.add('hidden');
                this.renderLineChart(this.tongQuanData);
                break;
            case 'bar':
                chartContainer?.classList.remove('hidden');
                tableContainer?.classList.add('hidden');
                this.renderBarChartTongQuan(this.tongQuanData);
                break;
            case 'table':
                chartContainer?.classList.add('hidden');
                tableContainer?.classList.remove('hidden');
                this.renderTongQuanTable(this.tongQuanData);
                break;
        }
    }
    
    renderLineChart(data) {
        const ctx = this.elements.chartCanvas.getContext('2d');
        if (this.chart) this.chart.destroy();
        
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.chart.labels,
                datasets: [
                    {
                        label: 'Chỉ tiêu',
                        data: data.chart.chi_tieu,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#3B82F6'
                    },
                    {
                        label: 'Thực tế',
                        data: data.chart.thuc_te,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#10B981'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { 
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            font: { size: 13, weight: '500' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        callbacks: {
                            title: (tooltipItems) => `Mốc giờ: ${tooltipItems[0].label}`,
                            label: (context) => `${context.dataset.label}: ${this.formatNumber(context.parsed.y)}`,
                            afterBody: (tooltipItems) => {
                                if (tooltipItems.length >= 2) {
                                    const chiTieu = tooltipItems[0].parsed.y;
                                    const thucTe = tooltipItems[1].parsed.y;
                                    const diff = thucTe - chiTieu;
                                    const pct = chiTieu > 0 ? ((thucTe / chiTieu) * 100).toFixed(1) : 0;
                                    return ['', `Chênh lệch: ${diff >= 0 ? '+' : ''}${this.formatNumber(diff)}`, `Tỷ lệ: ${pct}%`];
                                }
                                return [];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Mốc giờ', font: { size: 13, weight: '500' } },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Số lượng', font: { size: 13, weight: '500' } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    }
                }
            }
        });
    }
    
    renderBarChartTongQuan(data) {
        const ctx = this.elements.chartCanvas.getContext('2d');
        if (this.chart) this.chart.destroy();
        
        // Dynamic colors for thực tế bars (AC-19)
        const thucTeColors = data.chart.thuc_te.map((val, i) => 
            val >= data.chart.chi_tieu[i] ? '#10B981' : '#EF4444'
        );
        
        this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.chart.labels,
                datasets: [
                    {
                        label: 'Chỉ tiêu',
                        data: data.chart.chi_tieu,
                        backgroundColor: '#3B82F6',
                        borderRadius: 4
                    },
                    {
                        label: 'Thực tế',
                        data: data.chart.thuc_te,
                        backgroundColor: thucTeColors,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: { 
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            font: { size: 13, weight: '500' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        callbacks: {
                            title: (tooltipItems) => `Mốc giờ: ${tooltipItems[0].label}`,
                            label: (context) => `${context.dataset.label}: ${this.formatNumber(context.parsed.y)}`,
                            afterBody: (tooltipItems) => {
                                if (tooltipItems.length >= 2) {
                                    const chiTieu = tooltipItems[0].parsed.y;
                                    const thucTe = tooltipItems[1].parsed.y;
                                    const diff = thucTe - chiTieu;
                                    const pct = chiTieu > 0 ? ((thucTe / chiTieu) * 100).toFixed(1) : 0;
                                    return ['', `Chênh lệch: ${diff >= 0 ? '+' : ''}${this.formatNumber(diff)}`, `Tỷ lệ: ${pct}%`];
                                }
                                return [];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Mốc giờ', font: { size: 13, weight: '500' } },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Số lượng', font: { size: 13, weight: '500' } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    }
                }
            }
        });
    }
    
    renderTongQuanTable(data) {
        const thead = this.elements.tongQuanTableHead;
        const tbody = this.elements.tongQuanTableBody;
        
        // Generate table header
        thead.innerHTML = `
            <tr>
                <th class="px-4 py-3 text-left font-semibold">Mốc giờ</th>
                <th class="px-4 py-3 text-right font-semibold">Chỉ tiêu</th>
                <th class="px-4 py-3 text-right font-semibold">Thực tế</th>
                <th class="px-4 py-3 text-right font-semibold">Chênh lệch</th>
                <th class="px-4 py-3 text-right font-semibold">Tỷ lệ (%)</th>
                <th class="px-4 py-3 text-center font-semibold">Trạng thái</th>
            </tr>
        `;
        
        // Generate table body (AC-20)
        tbody.innerHTML = data.chart.labels.map((label, i) => {
            const chiTieu = data.chart.chi_tieu[i];
            const thucTe = data.chart.thuc_te[i];
            const chenhLech = thucTe - chiTieu;
            const tyLe = chiTieu > 0 ? ((thucTe / chiTieu) * 100).toFixed(1) : 0;
            const trangThai = tyLe >= 95 ? 'dat' : tyLe >= 80 ? 'can_chu_y' : 'chua_dat';
            
            const statusClasses = {
                dat: 'bg-green-100 text-green-800',
                can_chu_y: 'bg-yellow-100 text-yellow-800',
                chua_dat: 'bg-red-100 text-red-800'
            };
            const statusText = {
                dat: 'Đạt',
                can_chu_y: 'Cần chú ý',
                chua_dat: 'Chưa đạt'
            };
            
            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">${this.escapeHtml(label)}</td>
                    <td class="px-4 py-3 text-right">${chiTieu.toLocaleString()}</td>
                    <td class="px-4 py-3 text-right">${thucTe.toLocaleString()}</td>
                    <td class="px-4 py-3 text-right ${chenhLech >= 0 ? 'text-green-600' : 'text-red-600'}">
                        ${chenhLech >= 0 ? '+' : ''}${chenhLech.toLocaleString()}
                    </td>
                    <td class="px-4 py-3 text-right font-medium">${tyLe}%</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium ${statusClasses[trangThai]}">
                            ${statusText[trangThai]}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    }
}

// Initialize app
window.bieuDoApp = new BieuDoApp();
