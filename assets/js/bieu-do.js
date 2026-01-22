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
        this.currentView = 'tongquan';
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
        
        this.init();
    }
    
    async init() {
        await fetchCsrfToken();
        this.bindElements();
        this.bindEvents();
        this.bindViewToggle();
        this.bindMatrixToggle();
        await this.loadInitialData();
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
            matrixTableBody: document.getElementById('matrixTableBody')
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
        // Set initial filter values
        this.filters.ngay = this.elements.filterDate.value;
        
        if (this.isAdmin) {
            // Load lines for admin
            await this.loadLineList();
        } else {
            // Non-admin: use assigned line
            this.filters.line_id = window.appConfig.lineId;
            await this.loadMaHangList();
        }
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
        
        // Reset matrix toggle to off
        if (this.elements.toggleMatrix) {
            this.elements.toggleMatrix.checked = false;
            this.toggleMatrixView(false);
        }
        
        try {
            const response = await api('GET', `/bieu-do/so-sanh?line_id=${line_id}&ma_hang_id=${ma_hang_id}&ngay=${ngay}&ca_id=${ca_id}`);
            
            if (response.success) {
                this.renderChart(response.data);
                this.showChart();
                
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
        
        // Destroy existing chart if any
        if (this.congDoanChart) {
            this.congDoanChart.destroy();
        }
        
        // Create colors array based on below_target
        const thucTeColors = chartData.below_target.map(below => 
            below ? '#EF4444' : '#10B981'  // red if below, green if met
        );
        
        this.congDoanChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Chỉ tiêu',
                        data: chartData.chi_tieu,
                        backgroundColor: '#3B82F6',
                        borderWidth: 0,
                        borderRadius: 4
                    },
                    {
                        label: 'Thực tế',
                        data: chartData.thuc_te,
                        backgroundColor: thucTeColors,
                        borderWidth: 0,
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
                                return `${context.dataset.label}: ${this.formatNumber(value)}`;
                            },
                            afterBody: (tooltipItems) => {
                                if (tooltipItems.length >= 2) {
                                    const chiTieu = tooltipItems[0].parsed.y;
                                    const thucTe = tooltipItems[1].parsed.y;
                                    const diff = thucTe - chiTieu;
                                    const pct = chiTieu > 0 ? ((thucTe / chiTieu) * 100).toFixed(1) : 0;
                                    return [
                                        '',
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
        
        // Destroy existing chart
        if (this.chart) {
            this.chart.destroy();
        }
        
        // Create chart
        const ctx = this.elements.chartCanvas.getContext('2d');
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
                            font: {
                                size: 13,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        callbacks: {
                            title: (tooltipItems) => {
                                return `Mốc giờ: ${tooltipItems[0].label}`;
                            },
                            label: (context) => {
                                const value = context.parsed.y;
                                return `${context.dataset.label}: ${this.formatNumber(value)}`;
                            },
                            afterBody: (tooltipItems) => {
                                if (tooltipItems.length >= 2) {
                                    const chiTieu = tooltipItems[0].parsed.y;
                                    const thucTe = tooltipItems[1].parsed.y;
                                    const diff = thucTe - chiTieu;
                                    const pct = chiTieu > 0 ? ((thucTe / chiTieu) * 100).toFixed(1) : 0;
                                    return [
                                        '',
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
                        title: {
                            display: true,
                            text: 'Mốc giờ',
                            font: { size: 13, weight: '500' }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Số lượng',
                            font: { size: 13, weight: '500' }
                        },
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                }
            }
        });
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
}

// Initialize app
window.bieuDoApp = new BieuDoApp();
