import { api } from '../admin-api.js';
import { escapeHtml, showToast, closeModal, showConfirmModal } from '../admin-utils.js';
import { getState } from './state.js';

const fmt = (n) => Number(n).toLocaleString('vi-VN');
let current = null;

function el(id) {
    return document.getElementById(id);
}

export function updatePoSubartMaHangSelect() {
    const select = el('poSubartMaHangSelect');
    if (!select) return;
    select.innerHTML = '<option value="">-- Chọn mã hàng --</option>' +
        getState().maHang.filter(mh => mh.is_active == 1).map(mh =>
            `<option value="${mh.id}">${escapeHtml(mh.ma_hang)} - ${escapeHtml(mh.ten_hang)}</option>`
        ).join('');
}

function resetTable(message = '') {
    current = null;
    el('poSubartContent').innerHTML = message
        ? `<p class="p-4 text-sm text-gray-500">${escapeHtml(message)}</p>`
        : '';
}

async function onMaHangChange() {
    const maHangId = el('poSubartMaHangSelect').value;
    const poSelect = el('poSubartPoSelect');
    poSelect.innerHTML = '<option value="">-- Chọn PO --</option>';
    poSelect.disabled = true;
    resetTable();
    if (!maHangId) return;

    try {
        const response = await api('GET', `/danh-muc/po?ma_hang_id=${encodeURIComponent(maHangId)}`);
        if (!response.success) {
            showToast(response.message || 'Lỗi tải danh sách PO', 'error');
            return;
        }
        if (!response.data.available) {
            resetTable('Không kết nối được dữ liệu PO');
            return;
        }
        const items = response.data.items || [];
        if (items.length === 0) {
            resetTable('Mã hàng này chưa có PO');
            return;
        }
        poSelect.innerHTML = '<option value="">-- Chọn PO --</option>' + items.map(po =>
            `<option value="${escapeHtml(po.po_number)}">${escapeHtml(po.po_number)} — ${escapeHtml(po.customer_name || '')} — SL ${fmt(po.quantity || 0)}</option>`
        ).join('');
        poSelect.disabled = false;
    } catch (error) {
        showToast('Lỗi tải danh sách PO', 'error');
    }
}

export async function loadPoSubart() {
    const maHangId = el('poSubartMaHangSelect').value;
    const poNumber = el('poSubartPoSelect').value;
    if (!maHangId || !poNumber) {
        resetTable();
        return;
    }

    try {
        const response = await api('GET',
            `/admin/po-subart?ma_hang_id=${encodeURIComponent(maHangId)}&po_number=${encodeURIComponent(poNumber)}`);
        if (!response.success) {
            resetTable();
            showToast(response.message || 'Lỗi tải SL subart', 'error');
            return;
        }
        current = { maHangId, poNumber, data: response.data };
        renderPoSubart();
    } catch (error) {
        showToast('Lỗi tải SL subart', 'error');
    }
}

function renderPoSubart() {
    const container = el('poSubartContent');
    const d = current.data;

    if (!d.available) {
        container.innerHTML = '<p class="p-4 text-sm text-gray-500">Không kết nối được dữ liệu PO</p>';
        return;
    }

    const editable = !!d.editable;
    const rows = (d.items || []).map((it, i) => {
        const code = escapeHtml(it.sub_art_code);
        const badge = it.ngoai_danh_sach
            ? ' <span class="status-badge status-locked">không còn trong datchi</span>'
            : '';
        const input = `<input type="number" min="0" step="1" data-index="${i}" class="po-subart-qty w-32 px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-primary outline-none" value="${it.so_luong === null ? '' : it.so_luong}"${editable && !it.ngoai_danh_sach ? '' : ' readonly'}>`;
        let actions = '';
        if (editable) {
            const saveBtn = it.ngoai_danh_sach ? '' : `<button class="btn btn-sm btn-primary" data-action="save" data-index="${i}">Lưu</button> `;
            const delBtn = it.so_luong === null && !it.ngoai_danh_sach ? '' : `<button class="btn btn-sm btn-danger" data-action="delete" data-index="${i}">Xóa</button>`;
            actions = saveBtn + delBtn;
        }
        return `<tr>
            <td>${code}${badge}</td>
            <td>${input}</td>
            <td>${escapeHtml(it.updated_by || '')}</td>
            <td>${escapeHtml(it.updated_at || '')}</td>
            <td>${actions}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="5" class="text-center">PO này chưa có subart</td></tr>';

    const hasPo = d.so_luong_po !== null && d.so_luong_po !== undefined;
    const conLai = hasPo ? d.so_luong_po - d.tong_da_phan_bo : null;
    const conLaiClass = conLai !== null && conLai < 0 ? 'text-danger font-semibold' : 'text-gray-800';

    container.innerHTML = `
        ${editable ? '' : '<p class="px-4 pt-3 text-sm text-gray-500">SL subart lấy từ datchi</p>'}
        <table class="admin-table">
            <thead>
                <tr><th>Subart</th><th>Số lượng</th><th>Người sửa</th><th>Ngày sửa</th><th>Thao tác</th></tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
        <div class="flex flex-wrap gap-6 p-4 text-sm">
            <div>SL PO: <strong>${hasPo ? fmt(d.so_luong_po) : '—'}</strong></div>
            <div>Đã phân bổ: <strong>${fmt(d.tong_da_phan_bo)}</strong></div>
            <div>Còn được phân bổ: <strong class="${conLaiClass}">${conLai === null ? '—' : fmt(conLai)}</strong></div>
        </div>`;
}

async function saveRow(index) {
    const it = current.data.items[index];
    const input = el('poSubartContent').querySelector(`.po-subart-qty[data-index="${index}"]`);
    const raw = input.value.trim();
    if (raw === '' || !/^\d+$/.test(raw)) {
        showToast('Số lượng phải là số nguyên không âm', 'error');
        return;
    }
    try {
        const response = await api('PUT', '/admin/po-subart', {
            po_number: current.poNumber,
            ma_hang_id: Number(current.maHangId),
            sub_art_code: it.sub_art_code,
            so_luong: Number(raw)
        });
        showToast(response.message || (response.success ? 'Đã lưu' : 'Lỗi lưu SL subart'), response.success ? 'success' : 'error');
        if (response.success) await loadPoSubart();
    } catch (error) {
        showToast('Lỗi lưu SL subart', 'error');
    }
}

function deleteRow(index) {
    const it = current.data.items[index];
    showConfirmModal(`Bạn có chắc muốn xóa SL của subart ${it.sub_art_code}?`, async () => {
        try {
            const response = await api('DELETE', '/admin/po-subart', {
                po_number: current.poNumber,
                ma_hang_id: Number(current.maHangId),
                sub_art_code: it.sub_art_code
            });
            showToast(response.message || (response.success ? 'Đã xóa' : 'Lỗi xóa SL subart'), response.success ? 'success' : 'error');
            if (response.success) await loadPoSubart();
        } catch (error) {
            showToast('Lỗi xóa SL subart', 'error');
        }
        closeModal('confirmModal');
    }, 'Xác nhận xóa', 'danger');
}

export function bindEvents() {
    el('poSubartMaHangSelect').addEventListener('change', onMaHangChange);
    el('poSubartPoSelect').addEventListener('change', loadPoSubart);
    el('poSubartContent').addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn || !current) return;
        const index = Number(btn.dataset.index);
        if (btn.dataset.action === 'save') saveRow(index);
        else if (btn.dataset.action === 'delete') deleteRow(index);
    });
}

export function init() {
    bindEvents();
    updatePoSubartMaHangSelect();
}
