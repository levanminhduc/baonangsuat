import {
    isLuyKeStatusEnabled,
    formatLuyKeStatusLabel,
    getStatusClass,
    buildLuyKeTooltip
} from './luy-ke-config.js';
import { computeLuyKeStatus, getLastInputMocId } from './luy-ke-calculator.js';

// "08:30:00" → "8:30"
export function formatMoc(gio) {
    const [h, m] = String(gio).split(':');
    return `${parseInt(h, 10)}:${m || '00'}`;
}

export function formatPercent(value) {
    return value.toFixed(1).replace('.', ',') + '%';
}

// Ngưỡng hiệu suất: >=100 đạt, >=90 gần đạt, <90 chưa đạt
export function hieuSuatLevel(value) {
    if (value >= 100) return 'ok';
    if (value >= 90) return 'warn';
    return 'bad';
}

const LEVEL_COLOR = { ok: 'var(--bns-success)', warn: 'var(--bns-warning)', bad: 'var(--bns-error)' };
const NUM_ATTRS = 'type="text" inputmode="numeric" pattern="[0-9]*" enterkeyhint="next" autocomplete="off"';

function escapeHtml(text) {
    return String(text ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function isLkStage(cd) {
    return cd.la_cong_doan_tinh_luy_ke === 1 || cd.la_cong_doan_tinh_luy_ke === '1';
}

export class GridManager {
    constructor(app) {
        this.app = app;
        this.currentCell = null;
        this.lastBaoCao = null;
        this.nowIndex = -1;
        this.mobileIndex = 0;
    }

    // Báo cáo hôm nay: mốc vừa qua (mốc cuối có giờ <= giờ hiện tại) là mốc đang nhập; ngày khác thì không có
    computeNowIndex(baoCao) {
        const now = new Date();
        const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        if (baoCao.ngay_bao_cao !== today) return -1;
        const minutes = now.getHours() * 60 + now.getMinutes();
        let idx = 0;
        baoCao.moc_gio_list.forEach((moc, i) => {
            const [h, m] = String(moc.gio).split(':').map(Number);
            if (h * 60 + (m || 0) <= minutes) idx = i;
        });
        return idx;
    }

    // Mốc mặc định của danh sách mobile: mốc đang nhập, không thì mốc cuối đã có số
    defaultMobileIndex(baoCao) {
        if (this.nowIndex >= 0) return this.nowIndex;
        let idx = 0;
        baoCao.moc_gio_list.forEach((moc, i) => {
            const hasData = baoCao.routing.some(cd => parseInt(baoCao.entries[`${cd.cong_doan_id}_${moc.id}`]?.so_luong) > 0);
            if (hasData) idx = i;
        });
        return idx;
    }

    // Màu từng ô theo chỉ tiêu lũy kế đã bỏ ở giao diện mới (so số một giờ với chỉ tiêu cộng dồn nên gần như ô nào cũng đỏ)
    getCellColorClass() {
        return '';
    }

    updateCellColor() {}

    renderGrid(baoCao) {
        this.lastBaoCao = baoCao;
        const container = document.getElementById('gridContainer');
        if (!container || !baoCao) return;

        const isEditable = baoCao.trang_thai === 'draft';
        const mocList = baoCao.moc_gio_list;
        this.nowIndex = this.computeNowIndex(baoCao);
        this.mobileIndex = this.defaultMobileIndex(baoCao);
        const colClass = (i) => [i === this.nowIndex ? 'is-now' : '', this.nowIndex >= 0 && i > this.nowIndex ? 'is-later' : ''].join(' ').trim();
        const lastMocId = mocList.length ? mocList[mocList.length - 1].id : null;

        const headerHtml = `
            <tr>
                <th class="is-stick-l">Công đoạn</th>
                ${mocList.map((m, i) => `<th class="${colClass(i)}">${formatMoc(m.gio)}</th>`).join('')}
                <th class="is-stick-r">Lũy kế</th>
            </tr>
            <tr class="is-meta">
                <td class="is-stick-l">Chỉ tiêu lũy kế</td>
                ${mocList.map((m, i) => `<td class="${colClass(i)}">${baoCao.chi_tieu_luy_ke[m.id] || 0}</td>`).join('')}
                <td class="is-stick-r" style="font-weight:400">${lastMocId ? (baoCao.chi_tieu_luy_ke[lastMocId] || 0) : ''}</td>
            </tr>
            <tr class="is-meta">
                <td class="is-stick-l">Hiệu suất</td>
                ${mocList.map((m, i) => `<td class="hieu-suat-cell ${colClass(i)}" data-moc="${m.id}">–</td>`).join('')}
                <td class="is-stick-r"></td>
            </tr>
        `;

        let bodyHtml = '';
        baoCao.routing.forEach((cd, idx) => {
            const lk = isLkStage(cd);
            const name = escapeHtml(cd.ten_cong_doan);
            bodyHtml += `<tr data-cd="${cd.cong_doan_id}"${lk ? ' class="is-lk"' : ''}>`;
            bodyHtml += `<td class="is-stick-l"><div class="bns-stage"><span class="bns-stt">${idx + 1}</span><span class="bns-stage-name" title="${name}">${name}</span>${lk ? '<span class="bns-badge bns-badge-primary" title="Công đoạn tính lũy kế">LK</span>' : ''}</div></td>`;

            mocList.forEach((moc, i) => {
                const entry = baoCao.entries[`${cd.cong_doan_id}_${moc.id}`];
                const value = entry ? parseInt(entry.so_luong) : 0;
                if (isEditable) {
                    bodyHtml += `<td class="bns-cell ${colClass(i)}"><input class="bns-cell-input" ${NUM_ATTRS} aria-label="${name} ${formatMoc(moc.gio)}" data-cd="${cd.cong_doan_id}" data-moc="${moc.id}" value="${value || ''}"></td>`;
                } else {
                    bodyHtml += `<td class="${colClass(i)}" data-moc="${moc.id}" data-value="${value}">${value || ''}</td>`;
                }
            });

            bodyHtml += `<td class="is-stick-r cell-luyke" data-cd="${cd.cong_doan_id}"></td>`;
            bodyHtml += '</tr>';
        });

        container.innerHTML = `
            <div class="bns-entry-scroll">
                <table class="bns-entry-table">
                    <thead>${headerHtml}</thead>
                    <tbody>${bodyHtml}</tbody>
                </table>
            </div>
            <div class="bns-mobile" id="bnsMobileEntry"></div>
        `;

        baoCao.routing.forEach(cd => this.updateRowLuyKe(cd.cong_doan_id));
        this.renderMobileList();

        if (isEditable) {
            this.bindGridEvents();
        }

        this.updateHieuSuat(baoCao);
    }

    // Số của một ô lấy từ bảng desktop (nguồn duy nhất; danh sách mobile chỉ phản chiếu)
    getValue(cdId, mocId) {
        const input = document.querySelector(`.bns-cell-input[data-cd="${cdId}"][data-moc="${mocId}"]`);
        if (input) return parseInt(input.value) || 0;
        const cell = document.querySelector(`.bns-entry-table tr[data-cd="${cdId}"] td[data-moc="${mocId}"]`);
        return cell ? parseInt(cell.dataset.value) || 0 : 0;
    }

    // Ô nhập là lũy kế tại mốc → lũy kế của dòng = số ở mốc muộn nhất đã nhập, không cộng dồn
    rowTotal(cdId) {
        return this.lastBaoCao.moc_gio_list.reduce((last, moc) => this.getValue(cdId, moc.id) || last, 0);
    }

    renderMobileList() {
        const box = document.getElementById('bnsMobileEntry');
        const baoCao = this.lastBaoCao;
        if (!box || !baoCao) return;

        const isEditable = baoCao.trang_thai === 'draft';
        const mocList = baoCao.moc_gio_list;
        const moc = mocList[this.mobileIndex];
        if (!moc) {
            box.innerHTML = '';
            return;
        }

        const chips = mocList.map((m, i) => `<button type="button" class="bns-chip${this.nowIndex >= 0 && i < this.nowIndex ? ' is-done' : ''}" data-index="${i}" data-moc="${m.id}" aria-pressed="${i === this.mobileIndex}"><span class="bns-dot" hidden></span>${formatMoc(m.gio)}</button>`).join('');

        const rows = baoCao.routing.map((cd, idx) => {
            const lk = isLkStage(cd);
            const name = escapeHtml(cd.ten_cong_doan);
            const value = this.getValue(cd.cong_doan_id, moc.id);
            const qty = isEditable
                ? `<input class="bns-m-input" ${NUM_ATTRS} aria-label="${name} ${formatMoc(moc.gio)}" data-cd="${cd.cong_doan_id}" data-moc="${moc.id}" value="${value || ''}">`
                : `<span style="padding-right:6px">${value || ''}</span>`;
            return `<li class="bns-m-cols${lk ? ' is-lk' : ''}">
                <div class="bns-m-name"><span class="bns-stt">${idx + 1}</span><div>${name}${lk ? ' <span class="bns-badge bns-badge-primary" style="vertical-align:middle">LK</span>' : ''}</div></div>
                <div class="bns-m-qty">${qty}</div>
                <div class="bns-m-total" data-m-total="${cd.cong_doan_id}">${this.rowTotal(cd.cong_doan_id)}</div>
            </li>`;
        }).join('');

        box.innerHTML = `
            <div class="bns-mobile-head">
                <div class="bns-chips" role="group" aria-label="Mốc giờ">${chips}</div>
                <div class="bns-m-cols bns-m-colhead"><div>Công đoạn</div><div>SL ${formatMoc(moc.gio)}</div><div>Lũy kế</div></div>
            </div>
            <ul class="bns-m-list">${rows}</ul>
        `;

        box.querySelectorAll('.bns-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                this.mobileIndex = parseInt(chip.dataset.index);
                this.renderMobileList();
                this.updateHieuSuat(this.lastBaoCao);
            });
        });
        const pressed = box.querySelector('.bns-chip[aria-pressed="true"]');
        if (pressed) pressed.scrollIntoView({ block: 'nearest', inline: 'center' });

        if (isEditable) {
            box.querySelectorAll('.bns-m-input').forEach(input => {
                input.addEventListener('focus', (e) => e.target.select());
                input.addEventListener('input', (e) => {
                    const mInput = e.target;
                    const digits = mInput.value.replace(/\D/g, '');
                    if (mInput.value !== digits) mInput.value = digits;
                    // Ghi vào ô bảng desktop rồi đi đúng luồng lưu cũ
                    const twin = document.querySelector(`.bns-cell-input[data-cd="${mInput.dataset.cd}"][data-moc="${mInput.dataset.moc}"]`);
                    if (!twin) return;
                    twin.value = digits;
                    this.app.onCellChange(twin);
                });
                input.addEventListener('keydown', (e) => {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();
                    const all = Array.from(box.querySelectorAll('.bns-m-input'));
                    const next = all[all.indexOf(e.target) + 1];
                    if (next) next.focus(); else e.target.blur();
                });
            });
        }
    }

    // Ô bảng desktop đổi trạng thái lưu thì ô mobile tương ứng đổi theo
    syncMobileState(input) {
        const mInput = document.querySelector(`.bns-m-input[data-cd="${input.dataset.cd}"][data-moc="${input.dataset.moc}"]`);
        if (!mInput) return;
        ['modified', 'saving', 'saved', 'error'].forEach(cls => mInput.classList.toggle(cls, input.classList.contains(cls)));
        if (document.activeElement !== mInput && mInput.value !== input.value) mInput.value = input.value;
    }

    bindGridEvents() {
        const inputs = document.querySelectorAll('.bns-cell-input');

        inputs.forEach(input => {
            input.addEventListener('focus', (e) => {
                this.currentCell = e.target;
                e.target.select();
            });

            input.addEventListener('input', (e) => {
                const digits = e.target.value.replace(/\D/g, '');
                if (e.target.value !== digits) e.target.value = digits;
                this.app.onCellChange(e.target);
            });

            input.addEventListener('keydown', (e) => {
                this.handleCellKeyDown(e);
            });
        });
    }

    updateHieuSuat(baoCao) {
        if (!baoCao) return;

        const mocList = baoCao.moc_gio_list;
        const lkStages = baoCao.routing.filter(isLkStage);
        const pctByMoc = {};

        mocList.forEach((moc, i) => {
            const chiTieu = baoCao.chi_tieu_luy_ke[moc.id] || 0;
            // Ô nhập đã là lũy kế tại mốc → so thẳng với chỉ tiêu lũy kế của mốc đó
            const thucTe = lkStages.reduce((sum, cd) => sum + this.getValue(cd.cong_doan_id, moc.id), 0);
            const isLater = this.nowIndex >= 0 && i > this.nowIndex;
            const pct = chiTieu > 0 && !isLater ? (thucTe / chiTieu) * 100 : null;
            pctByMoc[moc.id] = { pct, thucTe, chiTieu };

            const hieuSuatCell = document.querySelector(`.hieu-suat-cell[data-moc="${moc.id}"]`);
            if (hieuSuatCell) {
                hieuSuatCell.classList.remove('hieu-suat-green', 'hieu-suat-yellow', 'hieu-suat-red');
                if (pct === null) {
                    hieuSuatCell.textContent = '–';
                } else {
                    hieuSuatCell.textContent = formatPercent(pct);
                    hieuSuatCell.classList.add({ ok: 'hieu-suat-green', warn: 'hieu-suat-yellow', bad: 'hieu-suat-red' }[hieuSuatLevel(pct)]);
                }
            }

            // Chấm màu trên nút mốc giờ (mobile)
            const dot = document.querySelector(`.bns-chip[data-moc="${moc.id}"] .bns-dot`);
            if (dot) {
                const show = pct !== null && (this.nowIndex < 0 || i <= this.nowIndex);
                dot.hidden = !show;
                if (show) dot.style.background = LEVEL_COLOR[hieuSuatLevel(pct)];
            }
        });

        // Lũy kế tại mốc đang xem: mốc đang nhập, không thì mốc của danh sách mobile
        const focusMoc = mocList[this.nowIndex >= 0 ? this.nowIndex : this.mobileIndex];
        if (this.app && typeof this.app.updateLuyKeSummary === 'function') {
            this.app.updateLuyKeSummary(focusMoc ? { gio: formatMoc(focusMoc.gio), ...pctByMoc[focusMoc.id] } : null);
        }
    }

    updateRowLuyKe(cdId) {
        const row = document.querySelector(`.bns-entry-table tr[data-cd="${cdId}"]`);
        if (!row || !this.lastBaoCao) return;

        const inputValuesByMoc = {};
        this.lastBaoCao.moc_gio_list.forEach(moc => {
            inputValuesByMoc[moc.id] = this.getValue(cdId, moc.id);
        });
        const total = this.rowTotal(cdId);

        const mTotal = document.querySelector(`[data-m-total="${cdId}"]`);
        if (mTotal) mTotal.textContent = total;

        const luyKeCell = row.querySelector('.cell-luyke');
        if (!luyKeCell) return;

        if (isLuyKeStatusEnabled()) {
            const targetMocId = getLastInputMocId(inputValuesByMoc, this.lastBaoCao.moc_gio_list);
            const { statusByMocId, detailByMocId } = computeLuyKeStatus({
                mocGioList: this.lastBaoCao.moc_gio_list,
                chiTieuLuyKeMap: this.lastBaoCao.chi_tieu_luy_ke,
                luyKeThucTeMap: null,
                inputValuesByMoc,
                isEditable: true
            });

            const status = targetMocId ? statusByMocId[targetMocId] : null;
            const detail = targetMocId ? detailByMocId[targetMocId] : null;

            if (status) {
                const statusLabel = formatLuyKeStatusLabel(status);
                const statusClass = getStatusClass(status);
                const tooltip = buildLuyKeTooltip(detail?.chiTieu, detail?.thucTe, status);

                luyKeCell.innerHTML = `
                    <div class="luy-ke-status-container">
                        <span class="luy-ke-status-cell ${statusClass}">
                            ${statusLabel}
                            <span class="luy-ke-tooltip">${tooltip}</span>
                        </span>
                    </div>
                `;
                return;
            }
        }
        luyKeCell.textContent = total;
    }

    handleCellKeyDown(e) {
        const input = e.target;
        const key = e.key;

        if (key === 'Enter' || key === 'Tab') {
            e.preventDefault();
            this.moveToNextCell(input, e.shiftKey ? -1 : 1);
        } else if (key === 'ArrowUp') {
            e.preventDefault();
            this.moveToCell(input, -1, 0);
        } else if (key === 'ArrowDown') {
            e.preventDefault();
            this.moveToCell(input, 1, 0);
        } else if (key === 'ArrowLeft' && input.selectionStart === 0 && input.selectionEnd === 0) {
            e.preventDefault();
            this.moveToCell(input, 0, -1);
        } else if (key === 'ArrowRight' && input.selectionStart === input.value.length) {
            e.preventDefault();
            this.moveToCell(input, 0, 1);
        }
    }

    moveToNextCell(currentInput, direction) {
        const allInputs = Array.from(document.querySelectorAll('.bns-cell-input'));
        const currentIndex = allInputs.indexOf(currentInput);
        const nextIndex = currentIndex + direction;

        if (nextIndex >= 0 && nextIndex < allInputs.length) {
            allInputs[nextIndex].focus();
        }
    }

    moveToCell(currentInput, rowDelta, colDelta) {
        const currentRow = currentInput.closest('tr');
        const currentCell = currentInput.closest('td');
        const rows = Array.from(document.querySelectorAll('.bns-entry-table tbody tr'));
        const rowIndex = rows.indexOf(currentRow);
        const cells = Array.from(currentRow.querySelectorAll('td'));
        const colIndex = cells.indexOf(currentCell);

        const targetRowIndex = rowIndex + rowDelta;
        const targetColIndex = colIndex + colDelta;

        if (targetRowIndex >= 0 && targetRowIndex < rows.length) {
            const targetRow = rows[targetRowIndex];
            const targetCells = targetRow.querySelectorAll('td');

            if (targetColIndex >= 0 && targetColIndex < targetCells.length) {
                const targetInput = targetCells[targetColIndex].querySelector('.bns-cell-input');
                if (targetInput) {
                    targetInput.focus();
                }
            }
        }
    }
}
