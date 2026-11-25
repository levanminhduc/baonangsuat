import { state } from './state.js';
import { loadCongDoanData, loadTongQuanMatrix } from './data.js';
import { renderLineChart, renderBarChartTongQuan } from './charts.js';
import { renderTongQuanTable } from './matrix.js';

export function bindViewToggle() {
    state.elements.btnViewTongQuan.addEventListener('click', () => switchView('tongquan'));
    state.elements.btnViewCongDoan.addEventListener('click', () => switchView('congdoan'));
}

export function switchView(view) {
    state.currentView = view;
    
    // Toggle visibility
    state.elements.viewTongQuan.classList.toggle('hidden', view !== 'tongquan');
    state.elements.viewCongDoan.classList.toggle('hidden', view !== 'congdoan');
    
    // Toggle button styles
    if (view === 'tongquan') {
        state.elements.btnViewTongQuan.classList.add('bg-primary', 'text-white');
        state.elements.btnViewTongQuan.classList.remove('text-gray-600', 'hover:bg-gray-200');
        state.elements.btnViewCongDoan.classList.remove('bg-primary', 'text-white');
        state.elements.btnViewCongDoan.classList.add('text-gray-600', 'hover:bg-gray-200');
    } else {
        state.elements.btnViewCongDoan.classList.add('bg-primary', 'text-white');
        state.elements.btnViewCongDoan.classList.remove('text-gray-600', 'hover:bg-gray-200');
        state.elements.btnViewTongQuan.classList.remove('bg-primary', 'text-white');
        state.elements.btnViewTongQuan.classList.add('text-gray-600', 'hover:bg-gray-200');
    }
    
    // Load công đoạn data if switching to that view and not already loaded
    if (view === 'congdoan' && !state.congDoanDataLoaded) {
        loadCongDoanData();
    }
}

export function bindChartTypeToggle() {
    state.elements.btnChartLine?.addEventListener('click', () => switchChartType('line'));
    state.elements.btnChartBar?.addEventListener('click', () => switchChartType('bar'));
    state.elements.btnChartTable?.addEventListener('click', () => switchChartType('table'));
    state.elements.btnChartMultiLine?.addEventListener('click', () => switchChartType('multiline'));
    state.elements.btnChartStacked?.addEventListener('click', () => switchChartType('stacked'));
}

export async function renderMultiLineChart() {
    if (!state.tongQuanMatrixData) {
        await loadTongQuanMatrix();
    }
    const data = state.tongQuanMatrixData;
    if (!data) return;
    
    const ctx = state.elements.chartCanvas.getContext('2d');
    if (state.chart) state.chart.destroy();
    
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
    
    state.chart = new Chart(ctx, {
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

export async function renderStackedBarChart() {
    if (!state.tongQuanMatrixData) {
        await loadTongQuanMatrix();
    }
    const data = state.tongQuanMatrixData;
    if (!data) return;
    
    const ctx = state.elements.chartCanvas.getContext('2d');
    if (state.chart) state.chart.destroy();
    
    const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];
    
    const datasets = data.cong_doan_matrix.map((cd, idx) => ({
        label: cd.ten_cong_doan,
        data: data.moc_gio_list.map(mg => cd.moc_gio_data[mg.id]?.thuc_te || 0),
        backgroundColor: colors[idx % colors.length],
        borderRadius: 2
    }));
    
    state.chart = new Chart(ctx, {
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

export function switchChartType(type) {
    state.tongQuanChartType = type;
    updateChartTypeButtons(type);
    
    if (state.elements.toggleTongQuanMatrix) {
        state.elements.toggleTongQuanMatrix.checked = false;
    }
    state.elements.tongQuanMatrixContainer?.classList.add('hidden');
    
    if (type === 'multiline') {
        state.elements.tongQuanChartContainer?.classList.remove('hidden');
        state.elements.tongQuanTableContainer?.classList.add('hidden');
        renderMultiLineChart();
    } else if (type === 'stacked') {
        state.elements.tongQuanChartContainer?.classList.remove('hidden');
        state.elements.tongQuanTableContainer?.classList.add('hidden');
        state.elements.tongQuanMatrixContainer?.classList.add('hidden');
        if (state.elements.toggleTongQuanMatrix) state.elements.toggleTongQuanMatrix.checked = false;
        renderStackedBarChart();
    } else {
        renderTongQuan();
    }
}

export function updateChartTypeButtons(activeType) {
    const buttons = {
        line: state.elements.btnChartLine,
        bar: state.elements.btnChartBar,
        table: state.elements.btnChartTable,
        multiline: state.elements.btnChartMultiLine,
        stacked: state.elements.btnChartStacked
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

export function renderTongQuan() {
    if (!state.tongQuanData) return;
    
    const chartContainer = state.elements.tongQuanChartContainer;
    const tableContainer = state.elements.tongQuanTableContainer;
    
    switch (state.tongQuanChartType) {
        case 'line':
            chartContainer?.classList.remove('hidden');
            tableContainer?.classList.add('hidden');
            renderLineChart(state.tongQuanData);
            break;
        case 'bar':
            chartContainer?.classList.remove('hidden');
            tableContainer?.classList.add('hidden');
            renderBarChartTongQuan(state.tongQuanData);
            break;
        case 'table':
            chartContainer?.classList.add('hidden');
            tableContainer?.classList.remove('hidden');
            renderTongQuanTable(state.tongQuanData);
            break;
    }
}
