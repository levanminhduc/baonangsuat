import { api } from '../admin-api.js';
import { showToast, showLoading, hideLoading, escapeHtml } from '../admin-utils.js';
import { getState } from './state.js';

export function renderFormOptions() {
    const state = getState();
    
    // Render Ca options
    const caSelect = document.getElementById('bulkCa');
    if (caSelect && state.caList.length > 0) {
        caSelect.innerHTML = '<option value="">-- Chọn Ca --</option>' + 
            state.caList.map(ca => `<option value="${ca.id}">${escapeHtml(ca.ten_ca)}</option>`).join('');
    }

    // Render Ma Hang options
    const maHangSelect = document.getElementById('bulkMaHang');
    if (maHangSelect && state.maHang.length > 0) {
        maHangSelect.innerHTML = '<option value="">-- Chọn Mã hàng --</option>' + 
            state.maHang.filter(mh => mh.is_active == 1).map(mh => `<option value="${mh.id}">${escapeHtml(mh.ma_hang)} - ${escapeHtml(mh.ten_hang)}</option>`).join('');
    }

    // Render Lines checkboxes
    renderLinesCheckboxes();
}

function renderLinesCheckboxes() {
    const state = getState();
    const container = document.getElementById('bulkLinesContainer');
    if (!container) return;

    if (state.lines.length === 0) {
        container.innerHTML = '<p class="col-span-4 text-gray-500 text-sm italic">Chưa có LINE nào hoạt động</p>';
        return;
    }

    container.innerHTML = state.lines.filter(l => l.is_active == 1).map(line => `
        <div class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded">
            <input type="checkbox" id="line_bulk_${line.id}" name="lines[]" value="${line.id}" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary bulk-line-checkbox">
            <label for="line_bulk_${line.id}" class="text-sm text-gray-700 cursor-pointer select-none flex-1">${escapeHtml(line.ten_line)}</label>
        </div>
    `).join('');

    // Bind Select All event
    const selectAll = document.getElementById('bulkSelectAllLines');
    if (selectAll) {
        // Remove old listener if any (simple way is to clone node or just re-add, assuming init is called once per page load usually, or guard)
        // Since module init runs once, this is fine.
        selectAll.addEventListener('change', (e) => {
            document.querySelectorAll('.bulk-line-checkbox').forEach(cb => cb.checked = e.target.checked);
        });
    }
}

export async function handleBulkCreate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const date = formData.get('ngay');
    const caId = formData.get('ca_id');
    const maHangId = formData.get('ma_hang_id');
    const ctns = parseInt(formData.get('ctns')) || 0;
    const soLaoDong = parseInt(formData.get('so_lao_dong')) || 0;
    const skipExisting = document.getElementById('bulkSkipExisting').checked;
    
    // Validate inputs
    if (ctns < 0 || soLaoDong < 0) {
        showToast('CTNS và Số lao động phải là số không âm', 'error');
        return;
    }

    // Get selected lines
    const selectedLines = [];
    document.querySelectorAll('.bulk-line-checkbox:checked').forEach(cb => {
        selectedLines.push(cb.value);
    });

    if (selectedLines.length === 0) {
        showToast('Vui lòng chọn ít nhất một LINE', 'error');
        return;
    }

    if (!date || !caId || !maHangId) {
        showToast('Vui lòng điền đầy đủ thông tin', 'error');
        return;
    }

    // Construct payload
    const items = selectedLines.map(lineId => ({
        line_id: lineId,
        ma_hang_id: maHangId,
        ngay: date,
        ca_id: caId,
        ctns: ctns,
        so_lao_dong: soLaoDong
    }));

    showLoading();
    try {
        const response = await api('POST', '/admin/bao-cao/bulk-create', {
            items: items,
            skip_existing: skipExisting
        });

        if (response.success) {
            showToast(response.message, 'success');
            
            // Show result area
            const resultArea = document.getElementById('bulkResultArea');
            const countCreated = document.getElementById('bulkCountCreated');
            const countSkipped = document.getElementById('bulkCountSkipped');
            
            if (resultArea && countCreated && countSkipped) {
                resultArea.classList.remove('hidden');
                
                const createdCount = response.data.created ? response.data.created.length : 0;
                const skippedCount = response.data.skipped ? response.data.skipped.length : 0;
                
                countCreated.textContent = createdCount;
                countSkipped.textContent = skippedCount;
            }
        } else {
            showToast(response.message, 'error');
        }
    } catch (error) {
        showToast('Lỗi khi tạo báo cáo hàng loạt', 'error');
        console.error(error);
    } finally {
        hideLoading();
    }
}

export function bindEvents() {
    const form = document.getElementById('bulkCreateForm');
    if (form) {
        form.addEventListener('submit', handleBulkCreate);
    }
}

export function init() {
    bindEvents();
}
