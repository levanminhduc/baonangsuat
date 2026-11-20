// Dải thông tin báo cáo dùng chung cho màn nhập (app.js) và chi tiết lịch sử (history.js)
import { escapeHtml, hieuSuatLevel, formatPercent } from './grid.js';
import { getStatusText } from './utils.js';

// Icon Lucide nét 2px, kích thước do CSS quyết định
export function svgIcon(paths, className = '') {
    return `<svg${className ? ` class="${className}"` : ''} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
}

export function formatNgay(ngay) {
    return String(ngay || '').split('-').reverse().join('/');
}

export function formatSoLuong(n) {
    return n === null || n === undefined ? '—' : Number(n).toLocaleString('vi-VN');
}

// Thành phẩm = số ở mốc muộn nhất đã nhập của công đoạn tính lũy kế (cùng cách tính với lũy kế các báo cáo khác ở server)
export function thanhPhamTuEntries(bc) {
    const cd = (bc.routing || []).find(c => c.la_cong_doan_tinh_luy_ke === 1 || c.la_cong_doan_tinh_luy_ke === '1');
    if (!cd) return 0;
    // Ô nhập là lũy kế tại mốc → lấy số ở mốc muộn nhất đã nhập
    return (bc.moc_gio_list || []).reduce((last, moc) => (parseInt(bc.entries?.[`${cd.cong_doan_id}_${moc.id}`]?.so_luong) || 0) || last, 0);
}

// Lũy kế + còn lại của một khối (PO hoặc subart); vượt thì còn lại 0 kèm số vượt
function tienDo(soLuong, khac, thanhPham) {
    const luyKe = (parseInt(khac) || 0) + thanhPham;
    if (soLuong === null || soLuong === undefined) return { luyKe, conLai: '—', vuot: 0 };
    const vuot = Math.max(0, luyKe - soLuong);
    return { luyKe, conLai: formatSoLuong(Math.max(0, soLuong - luyKe)), vuot };
}

function conLaiHtml(td) {
    const warn = td.vuot > 0 ? ` <span style="color: var(--bns-warning)">(vượt ${escapeHtml(formatSoLuong(td.vuot))})</span>` : '';
    return escapeHtml(td.conLai) + warn;
}

// Cập nhật tại chỗ các ô lũy kế/còn lại sau auto-save, không dựng lại dải thông tin
export function updatePoTienDo(root, po, thanhPham) {
    if (!root || !po) return;
    const set = (id, td) => {
        const lk = root.querySelector(`#${id}LuyKe`);
        const cl = root.querySelector(`#${id}ConLai`);
        if (lk) lk.textContent = formatSoLuong(td.luyKe);
        if (cl) cl.innerHTML = conLaiHtml(td);
    };
    set('po', tienDo(po.so_luong_po, po.luy_ke_bao_cao_khac, thanhPham));
    if (po.sub_art) set('sub', tienDo(po.sub_art.so_luong, po.sub_art.luy_ke_bao_cao_khac, thanhPham));
}

// poAction: nút đổi PO (chỉ màn nhập, báo cáo còn sửa được); poEditor: khối chọn PO/subart
export function reportInfoHtml(bc, { fieldsId, laoDong, ctns, statusExtra = '', ngayExtra = '', poAction = '', poEditor = '' }) {
    const isEditable = bc.trang_thai === 'draft';
    const plain = (value, id = '') => `<span class="bns-field-value"${id ? ` id="${id}"` : ''}>${escapeHtml(value)}</span>`;
    const field = (label, value) => `<div class="bns-field"><span class="bns-muted">${label}</span>${value}</div>`;
    const html = (value, id) => `<span class="bns-field-value" id="${id}">${value}</span>`;

    let poFields = '';
    if (bc.po) {
        const thanhPham = thanhPhamTuEntries(bc);
        const tdPo = tienDo(bc.po.so_luong_po, bc.po.luy_ke_bao_cao_khac, thanhPham);
        poFields += field('PO', plain(bc.po.po_number) + poAction);
        poFields += field('SL PO', plain(formatSoLuong(bc.po.so_luong_po)));
        poFields += field('Lũy kế PO', plain(formatSoLuong(tdPo.luyKe), 'poLuyKe'));
        poFields += field('Còn lại', html(conLaiHtml(tdPo), 'poConLai'));
        if (bc.po.sub_art) {
            const tdSub = tienDo(bc.po.sub_art.so_luong, bc.po.sub_art.luy_ke_bao_cao_khac, thanhPham);
            poFields += field('Subart', plain(bc.po.sub_art.sub_art_code));
            poFields += field('SL Subart', plain(formatSoLuong(bc.po.sub_art.so_luong)));
            poFields += field('Lũy kế Subart', plain(formatSoLuong(tdSub.luyKe), 'subLuyKe'));
            poFields += field('Còn lại Subart', html(conLaiHtml(tdSub), 'subConLai'));
        }
    } else if (poAction) {
        poFields += field('PO', plain('—') + poAction);
    }

    return `
        <div class="bns-info-lk">
            <div class="bns-info-lk-row">
                <span class="bns-muted" data-lk-label>Lũy kế</span>
                <div class="bns-meter is-inline"><span data-lk-meter></span></div>
                <b data-lk-value>–</b>
                <span class="bns-badge bns-badge-muted" data-lk-pct>–</span>
            </div>
            <div class="bns-meter is-block"><span data-lk-meter></span></div>
        </div>
        <div class="bns-info-status">
            <span class="bns-badge ${isEditable ? 'bns-badge-primary' : 'bns-badge-muted'}">${escapeHtml(getStatusText(bc.trang_thai))}</span>
            ${statusExtra}
            <button type="button" class="bns-icon-btn bns-toggle-info" data-info-toggle aria-expanded="true" aria-controls="${fieldsId}" title="Ẩn thông tin">${svgIcon('<path d="m18 15-6-6-6 6"/>')}</button>
        </div>
        <div class="bns-info-fields" id="${fieldsId}">
            ${field('Mã hàng', plain(bc.ma_hang))}
            ${field('Ngày', plain(formatNgay(bc.ngay_bao_cao)) + ngayExtra)}
            ${field('Lao động', laoDong ?? plain(bc.so_lao_dong))}
            ${field('CTNS', ctns ?? plain(bc.ctns))}
            ${field('CT/giờ', plain(bc.ct_gio, isEditable ? 'ctGioDisplay' : ''))}
            ${field('Ca', plain(bc.ten_ca || bc.ma_ca || ''))}
            ${poFields}
            ${poEditor}
        </div>
    `;
}

export function bindInfoToggle(root) {
    const btn = root.querySelector('[data-info-toggle]');
    const fields = root.querySelector('.bns-info-fields');
    if (!btn || !fields) return;
    btn.addEventListener('click', () => {
        const isHidden = fields.classList.toggle('hidden');
        btn.setAttribute('aria-expanded', String(!isHidden));
        btn.title = isHidden ? 'Hiện thông tin' : 'Ẩn thông tin';
        btn.innerHTML = svgIcon(isHidden ? '<path d="m6 9 6 6 6-6"/>' : '<path d="m18 15-6-6-6 6"/>');
    });
}

// GridManager gọi mỗi lần tính lại hiệu suất: tóm tắt lũy kế tại mốc đang nhập
export function updateLuyKeSummary(root, summary) {
    const label = root?.querySelector('[data-lk-label]');
    const value = root?.querySelector('[data-lk-value]');
    const pctEl = root?.querySelector('[data-lk-pct]');
    if (!label || !value || !pctEl) return;
    const meters = root.querySelectorAll('[data-lk-meter]');

    const hasPct = summary && summary.pct !== null && summary.pct !== undefined;
    label.textContent = summary ? `Lũy kế ${summary.gio}` : 'Lũy kế';
    value.innerHTML = summary ? `${summary.thucTe} <span>/ ${summary.chiTieu}</span>` : '–';

    if (!hasPct) {
        pctEl.textContent = '–';
        pctEl.className = 'bns-badge bns-badge-muted';
        meters.forEach(m => { m.style.width = '0'; });
        return;
    }

    const level = hieuSuatLevel(summary.pct);
    pctEl.textContent = formatPercent(summary.pct);
    pctEl.className = `bns-badge bns-badge-${level}`;
    const color = { ok: 'var(--bns-success)', warn: 'var(--bns-warning)', bad: 'var(--bns-error)' }[level];
    meters.forEach(m => {
        m.style.width = Math.min(100, summary.pct) + '%';
        m.style.background = color;
    });
}
