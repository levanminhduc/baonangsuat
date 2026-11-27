import { api } from '../api.js';
import { showToast } from '../utils.js';
import { state } from './state.js';
import { updateURL } from './filters.js';
import { renderCongDoanChart, renderChart, showChartLoading, showNoData, showChart } from './charts.js';
import { renderCongDoanTable, toggleMatrixView, renderMatrixTable, toggleTongQuanMatrix, renderTongQuanMatrix } from './matrix.js';

export async function loadChartData() {
    const { line_id, ma_hang_id, ngay, ca_id, bao_cao_id } = state.filters;
    
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
    showChartLoading();
    
    // Reset công đoạn and matrix data loaded flags when loading new data
    state.congDoanDataLoaded = false;
    state.matrixDataLoaded = false;
    state.tongQuanMatrixLoaded = false;
    
    // Reset matrix toggle to off
    if (state.elements.toggleMatrix) {
        state.elements.toggleMatrix.checked = false;
        toggleMatrixView(false);
    }
    
    if (state.elements.toggleTongQuanMatrix) {
        state.elements.toggleTongQuanMatrix.checked = false;
        toggleTongQuanMatrix(false);
    }
    
    try {
        const response = await api('GET', `/bieu-do/so-sanh?line_id=${line_id}&ma_hang_id=${ma_hang_id}&ngay=${ngay}&ca_id=${ca_id}${bao_cao_id ? `&bao_cao_id=${bao_cao_id}` : ''}`);
        
        if (response.success) {
            renderChart(response.data);
            showChart();
            
            // Update URL with current filter values after successful load
            updateURL(state.isInitialLoad);
            
            // If currently on công đoạn view, load that data too
            if (state.currentView === 'congdoan') {
                loadCongDoanData();
            }
        } else {
            showNoData(response.message || 'Không có dữ liệu');
        }
    } catch (error) {
        console.error('Failed to load chart data:', error);
        showNoData('Có lỗi xảy ra khi tải dữ liệu');
        showToast('Không thể tải dữ liệu biểu đồ', 'error');
    }
}

export async function loadCongDoanData() {
    const { line_id, ma_hang_id, ngay, ca_id, bao_cao_id } = state.filters;
    
    // Skip if filters are not valid
    if (!line_id || !ma_hang_id || !ca_id) {
        return;
    }
    
    // Show loading
    state.elements.congDoanLoading.classList.remove('hidden');
    
    try {
        const params = new URLSearchParams({
            line_id: line_id,
            ma_hang_id: ma_hang_id,
            ngay: ngay,
            ca_id: ca_id
        });
        if (bao_cao_id) {
            params.set('bao_cao_id', bao_cao_id);
        }
        
        const response = await api('GET', `/bieu-do/so-sanh-chi-tiet?${params}`);
        
        if (response.success) {
            renderCongDoanTable(response.data.cong_doan_details);
            renderCongDoanChart(response.data.cong_doan_chart);
            state.congDoanDataLoaded = true;
        } else {
            showToast(response.message || 'Không thể tải dữ liệu công đoạn', 'error');
        }
    } catch (error) {
        console.error('Failed to load công đoạn data:', error);
        showToast('Không thể tải dữ liệu công đoạn', 'error');
    } finally {
        state.elements.congDoanLoading.classList.add('hidden');
    }
}

export async function loadMatrixData() {
    const { line_id, ma_hang_id, ngay, ca_id, bao_cao_id } = state.filters;
    
    // Skip if filters are not valid
    if (!line_id || !ma_hang_id || !ca_id) {
        return;
    }
    
    const loading = state.elements.matrixLoading;
    const noData = state.elements.matrixNoData;
    const table = state.elements.matrixTable;
    
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
        if (bao_cao_id) {
            params.set('bao_cao_id', bao_cao_id);
        }
        
        const response = await api('GET', `/bieu-do/so-sanh-matrix?${params}`);
        loading.classList.add('hidden');
        
        if (response.success && response.data.cong_doan_matrix && response.data.cong_doan_matrix.length > 0) {
            renderMatrixTable(response.data);
            table.classList.remove('hidden');
            state.matrixDataLoaded = true;
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

export async function loadTongQuanMatrix() {
    const loading = state.elements.tongQuanMatrixLoading;
    loading?.classList.remove('hidden');
    
    const params = new URLSearchParams({
        line_id: state.filters.line_id,
        ma_hang_id: state.filters.ma_hang_id,
        ngay: state.filters.ngay,
        ca_id: state.filters.ca_id
    });
    if (state.filters.bao_cao_id) {
        params.set('bao_cao_id', state.filters.bao_cao_id);
    }
    
    try {
        const response = await api('GET', `/bieu-do/so-sanh-matrix?${params}`);
        loading?.classList.add('hidden');
        
        if (response.success) {
            state.tongQuanMatrixData = response.data;
            renderTongQuanMatrix(response.data);
            state.tongQuanMatrixLoaded = true;
        }
    } catch (error) {
        loading?.classList.add('hidden');
        showToast('Không thể tải dữ liệu ma trận', 'error');
    }
}
