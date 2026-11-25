import { escapeHtml } from '../utils.js';
import { state } from './state.js';
import { loadMatrixData, loadTongQuanMatrix } from './data.js';
import { renderTongQuan } from './chart-type.js';
import { formatNumber } from './format.js';

export function renderCongDoanTable(details) {
    const tbody = state.elements.congDoanTable.querySelector('tbody');
    
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
            <td class="px-4 py-3 font-medium text-gray-900">${escapeHtml(cd.ten_cong_doan)}</td>
            <td class="px-4 py-3 text-right text-gray-700">${formatNumber(cd.chi_tieu)}</td>
            <td class="px-4 py-3 text-right text-gray-700">${formatNumber(cd.thuc_te)}</td>
            <td class="px-4 py-3 text-right ${cd.chenh_lech < 0 ? 'text-red-600' : 'text-green-600'} font-medium">
                ${cd.chenh_lech >= 0 ? '+' : ''}${formatNumber(cd.chenh_lech)}
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

export function bindMatrixToggle() {
    if (state.elements.toggleMatrix) {
        state.elements.toggleMatrix.addEventListener('change', (e) => {
            toggleMatrixView(e.target.checked);
        });
    }
}

export function toggleMatrixView(showMatrix) {
    if (!state.elements.viewSummary || !state.elements.viewMatrix) {
        return;
    }
    
    state.elements.viewSummary.classList.toggle('hidden', showMatrix);
    state.elements.viewMatrix.classList.toggle('hidden', !showMatrix);
    
    if (showMatrix && !state.matrixDataLoaded) {
        loadMatrixData();
    }
}

export function renderMatrixTable(data) {
    const thead = state.elements.matrixTableHead;
    const tbody = state.elements.matrixTableBody;
    
    // Header: Công đoạn + each mốc giờ + Tổng
    thead.innerHTML = `
        <tr>
            <th class="px-3 py-3 text-left font-semibold sticky left-0 bg-gray-50 border-r z-10 min-w-[150px]">Công đoạn</th>
            ${data.moc_gio_list.map(mg => `
                <th class="px-3 py-3 text-center font-semibold min-w-[90px] whitespace-nowrap">${escapeHtml(mg.gio)}</th>
            `).join('')}
            <th class="px-3 py-3 text-center font-semibold bg-gray-100 min-w-[90px]">TỔNG</th>
        </tr>
    `;
    
    // Body: each công đoạn row
    tbody.innerHTML = data.cong_doan_matrix.map(cd => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-3 py-2 font-medium sticky left-0 bg-white border-r z-10">${escapeHtml(cd.ten_cong_doan)}</td>
            ${data.moc_gio_list.map(mg => {
                const cell = cd.moc_gio_data[mg.id] || { chi_tieu: 0, thuc_te: 0, ty_le: 0, trang_thai: 'chua_dat' };
                return renderMatrixCell(cell, false);
            }).join('')}
            ${renderMatrixCell(cd.tong, true)}
        </tr>
    `).join('');
}

export function renderMatrixCell(cell, isSummary = false) {
    const bgColor = getCellColor(cell.trang_thai);
    const bgClass = isSummary ? 'bg-gray-50' : '';
    
    return `
        <td class="px-2 py-2 text-center ${bgClass}">
            <div class="rounded px-2 py-1.5 text-white text-xs font-medium ${bgColor} shadow-sm">
                <div class="font-semibold">${formatNumber(cell.thuc_te)}/${formatNumber(cell.chi_tieu)}</div>
                <div class="text-[10px] opacity-90 mt-0.5">${cell.ty_le.toFixed(0)}%</div>
            </div>
        </td>
    `;
}

export function getCellColor(trangThai) {
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

export function bindTongQuanMatrixToggle() {
    state.elements.toggleTongQuanMatrix?.addEventListener('change', (e) => {
        toggleTongQuanMatrix(e.target.checked);
    });
}

export function toggleTongQuanMatrix(show) {
    const chartContainer = state.elements.tongQuanChartContainer;
    const tableContainer = state.elements.tongQuanTableContainer;
    const matrixContainer = state.elements.tongQuanMatrixContainer;
    
    if (show) {
        chartContainer?.classList.add('hidden');
        tableContainer?.classList.add('hidden');
        matrixContainer?.classList.remove('hidden');
        if (!state.tongQuanMatrixLoaded) {
            loadTongQuanMatrix();
        }
    } else {
        matrixContainer?.classList.add('hidden');
        renderTongQuan();
    }
}

export function renderTongQuanMatrix(data) {
    const thead = state.elements.tongQuanMatrixHead;
    const tbody = state.elements.tongQuanMatrixBody;
    
    thead.innerHTML = `
        <tr>
            <th class="px-3 py-2 text-left font-semibold sticky left-0 bg-gray-50 border-r">Công đoạn</th>
            ${data.moc_gio_list.map(mg => `<th class="px-3 py-2 text-center font-semibold min-w-[80px]">${escapeHtml(mg.gio)}</th>`).join('')}
            <th class="px-3 py-2 text-center font-semibold bg-gray-100 min-w-[80px]">TỔNG</th>
        </tr>
    `;
    
    tbody.innerHTML = data.cong_doan_matrix.map(cd => `
        <tr class="border-b hover:bg-gray-50">
            <td class="px-3 py-2 font-medium sticky left-0 bg-white border-r">${escapeHtml(cd.ten_cong_doan)}</td>
            ${data.moc_gio_list.map(mg => {
                const cell = cd.moc_gio_data[mg.id] || { chi_tieu: 0, thuc_te: 0, ty_le: 0 };
                return renderMatrixCellTongQuan(cell);
            }).join('')}
            ${renderMatrixCellTongQuan(cd.tong, true)}
        </tr>
    `).join('');
}

export function renderMatrixCellTongQuan(cell, isSummary = false) {
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

export function renderTongQuanTable(data) {
    const thead = state.elements.tongQuanTableHead;
    const tbody = state.elements.tongQuanTableBody;
    
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
                <td class="px-4 py-3 font-medium">${escapeHtml(label)}</td>
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
