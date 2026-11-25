import { state } from './state.js';
import { renderTongQuan } from './chart-type.js';
import { formatNumber, formatDate } from './format.js';

export function renderCongDoanChart(chartData) {
    if (!chartData || !chartData.labels || chartData.labels.length === 0) {
        return;
    }
    
    const ctx = state.elements.congDoanChart.getContext('2d');
    
    if (state.congDoanChart) {
        state.congDoanChart.destroy();
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
    
    state.congDoanChart = new Chart(ctx, {
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
                                    return `${dataset.label}: +${formatNumber(value)} (lũy kế: ${formatNumber(dataset._cumulativeChiTieu)})`;
                                }
                                return `${dataset.label}: ${formatNumber(value)}`;
                            }
                            if (dataset.stack === 'actual' && dataset._cumulativeData) {
                                const cumulative = dataset._cumulativeData[context.dataIndex] || 0;
                                return `${dataset.label}: +${formatNumber(value)} (lũy kế: ${formatNumber(cumulative)})`;
                            }
                            return `${context.dataset.label}: ${formatNumber(value)}`;
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
                                    `Tổng lũy kế: ${formatNumber(thucTe)}`,
                                    `Chênh lệch: ${diff >= 0 ? '+' : ''}${formatNumber(diff)}`,
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

export function renderChart(data) {
    // Cache data for re-rendering when chart type changes (AC-21)
    state.tongQuanData = data;
    
    // Update info header
    state.elements.infoLine.textContent = `${data.line.ma_line} - ${data.line.ten_line}`;
    state.elements.infoDate.textContent = formatDate(data.ngay);
    state.elements.infoCa.textContent = data.ca.ten_ca;
    state.elements.infoMaHang.textContent = `${data.ma_hang.ma_hang} - ${data.ma_hang.ten_hang}`;
    state.elements.infoCtns.textContent = formatNumber(data.ctns);
    
    // Update summary
    const summary = data.summary;
    state.elements.summaryChiTieu.textContent = formatNumber(summary.tong_chi_tieu);
    state.elements.summaryThucTe.textContent = formatNumber(summary.tong_thuc_te);
    
    // Chenh lech with color
    const chenhLech = summary.chenh_lech;
    state.elements.summaryChenhLech.textContent = (chenhLech >= 0 ? '+' : '') + formatNumber(chenhLech);
    state.elements.summaryChenhLech.className = `text-2xl font-bold ${chenhLech >= 0 ? 'text-green-600' : 'text-red-600'}`;
    
    // Ty le with color
    const tyLe = summary.ty_le_hoan_thanh;
    state.elements.summaryTyLe.textContent = `${tyLe}%`;
    state.elements.summaryTyLe.className = `text-2xl font-bold ${tyLe >= 100 ? 'text-green-600' : tyLe >= 80 ? 'text-yellow-600' : 'text-red-600'}`;
    
    // Render based on current chart type (AC-17 to AC-21)
    renderTongQuan();
}

export function showLoading() {
    state.elements.loadingState.classList.remove('hidden');
    state.elements.noDataState.classList.add('hidden');
    state.elements.chartSection.classList.add('hidden');
}

export function showNoData(message = 'Không có dữ liệu cho bộ lọc đã chọn') {
    state.elements.loadingState.classList.add('hidden');
    state.elements.noDataState.classList.remove('hidden');
    state.elements.chartSection.classList.add('hidden');
    state.elements.noDataMessage.textContent = message;
}

export function showChart() {
    state.elements.loadingState.classList.add('hidden');
    state.elements.noDataState.classList.add('hidden');
    state.elements.chartSection.classList.remove('hidden');
}

export function renderLineChart(data) {
    const ctx = state.elements.chartCanvas.getContext('2d');
    if (state.chart) state.chart.destroy();
    
    state.chart = new Chart(ctx, {
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
                        label: (context) => `${context.dataset.label}: ${formatNumber(context.parsed.y)}`,
                        afterBody: (tooltipItems) => {
                            if (tooltipItems.length >= 2) {
                                const chiTieu = tooltipItems[0].parsed.y;
                                const thucTe = tooltipItems[1].parsed.y;
                                const diff = thucTe - chiTieu;
                                const pct = chiTieu > 0 ? ((thucTe / chiTieu) * 100).toFixed(1) : 0;
                                return ['', `Chênh lệch: ${diff >= 0 ? '+' : ''}${formatNumber(diff)}`, `Tỷ lệ: ${pct}%`];
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

export function renderBarChartTongQuan(data) {
    const ctx = state.elements.chartCanvas.getContext('2d');
    if (state.chart) state.chart.destroy();
    
    // Dynamic colors for thực tế bars (AC-19)
    const thucTeColors = data.chart.thuc_te.map((val, i) => 
        val >= data.chart.chi_tieu[i] ? '#10B981' : '#EF4444'
    );
    
    state.chart = new Chart(ctx, {
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
                        label: (context) => `${context.dataset.label}: ${formatNumber(context.parsed.y)}`,
                        afterBody: (tooltipItems) => {
                            if (tooltipItems.length >= 2) {
                                const chiTieu = tooltipItems[0].parsed.y;
                                const thucTe = tooltipItems[1].parsed.y;
                                const diff = thucTe - chiTieu;
                                const pct = chiTieu > 0 ? ((thucTe / chiTieu) * 100).toFixed(1) : 0;
                                return ['', `Chênh lệch: ${diff >= 0 ? '+' : ''}${formatNumber(diff)}`, `Tỷ lệ: ${pct}%`];
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
