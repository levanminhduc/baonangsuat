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

export function reportInfoHtml(bc, { fieldsId, laoDong, ctns, statusExtra = '', ngayExtra = '' }) {
    const isEditable = bc.trang_thai === 'draft';
    const plain = (value, id = '') => `<span class="bns-field-value"${id ? ` id="${id}"` : ''}>${escapeHtml(value)}</span>`;
    const field = (label, value) => `<div class="bns-field"><span class="bns-muted">${label}</span>${value}</div>`;

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
