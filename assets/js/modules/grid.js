import { formatGio } from './utils.js';
import {
    isLuyKeStatusEnabled,
    formatLuyKeStatusLabel,
    getStatusClass,
    buildLuyKeTooltip
} from './luy-ke-config.js';
import { computeLuyKeStatus, getLastInputMocId } from './luy-ke-calculator.js';

export class GridManager {
    constructor(app) {
        this.app = app;
        this.currentCell = null;
        this.lastBaoCao = null;
    }

    renderGrid(baoCao) {
        this.lastBaoCao = baoCao;
        const container = document.getElementById('gridContainer');
        if (!container || !baoCao) return;
        
        const isEditable = baoCao.trang_thai === 'draft';
        const isStatusEnabled = isLuyKeStatusEnabled();
        
        let headerHtml = `
            <tr>
                <th class="col-stt" rowspan="3">STT</th>
                <th class="col-name" rowspan="3">Tên công đoạn</th>
                ${baoCao.moc_gio_list.map(m => `<th class="col-hour">${formatGio(m.gio)}</th>`).join('')}
                <th class="col-luyke" rowspan="3">Lũy kế</th>
            </tr>
            <tr class="row-chitieu">
                ${baoCao.moc_gio_list.map(m => `<td>${baoCao.chi_tieu_luy_ke[m.id] || 0}</td>`).join('')}
            </tr>
            <tr class="row-hieuSuat">
                ${baoCao.moc_gio_list.map(m => `<td class="hieu-suat-cell" data-moc="${m.id}">-</td>`).join('')}
            </tr>
        `;
        
        let bodyHtml = '';
        baoCao.routing.forEach((cd, idx) => {
            let luyKe = 0;
            const inputValuesByMoc = {};

            bodyHtml += `<tr data-cd="${cd.cong_doan_id}">`;
            bodyHtml += `<td class="cell-readonly">${idx + 1}</td>`;
            bodyHtml += `<td class="cell-name" title="${cd.ten_cong_doan}">${cd.ten_cong_doan}</td>`;
            
            baoCao.moc_gio_list.forEach(moc => {
                const key = `${cd.cong_doan_id}_${moc.id}`;
                const entry = baoCao.entries[key];
                const value = entry ? parseInt(entry.so_luong) : 0;
                luyKe += value;
                inputValuesByMoc[moc.id] = value;
                
                if (isEditable) {
                    bodyHtml += `<td>
                        <input type="number" 
                            class="cell-input" 
                            data-cd="${cd.cong_doan_id}" 
                            data-moc="${moc.id}"
                            value="${value || ''}"
                            min="0">
                    </td>`;
                } else {
                    bodyHtml += `<td class="cell-readonly">${value || ''}</td>`;
                }
            });
            
            let luyKeContent = luyKe;
            let luyKeClass = 'cell-luyke';

            if (isStatusEnabled) {
                const targetMocId = getLastInputMocId(inputValuesByMoc, baoCao.moc_gio_list);
                
                const { statusByMocId, detailByMocId } = computeLuyKeStatus({
                    mocGioList: baoCao.moc_gio_list,
                    chiTieuLuyKeMap: baoCao.chi_tieu_luy_ke,
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
                     
                     luyKeContent = `
                        <div class="luy-ke-status-container">
                            <span class="luy-ke-status-cell ${statusClass}">
                                ${statusLabel}
                                <span class="luy-ke-tooltip">${tooltip}</span>
                            </span>
                        </div>
                     `;
                }
            }

            bodyHtml += `<td class="${luyKeClass}" data-cd="${cd.cong_doan_id}">${luyKeContent}</td>`;
            bodyHtml += '</tr>';
        });
        
        container.innerHTML = `
            <table class="excel-grid">
                <thead>${headerHtml}</thead>
                <tbody>${bodyHtml}</tbody>
            </table>
        `;
        
        if (isEditable) {
            this.bindGridEvents();
        }
        
        this.updateHieuSuat(baoCao);
    }
    
    bindGridEvents() {
        const inputs = document.querySelectorAll('.cell-input');
        
        inputs.forEach(input => {
            input.addEventListener('focus', (e) => {
                this.currentCell = e.target;
                e.target.select();
            });
            
            input.addEventListener('input', (e) => {
                this.app.onCellChange(e.target);
            });
            
            input.addEventListener('keydown', (e) => {
                this.handleCellKeyDown(e);
            });
        });
    }

    updateHieuSuat(baoCao) {
        if (!baoCao) return;
        
        baoCao.moc_gio_list.forEach(moc => {
            const chiTieu = baoCao.chi_tieu_luy_ke[moc.id] || 0;
            
            let tongThucTe = 0;
            baoCao.routing.forEach(cd => {
                if (cd.la_cong_doan_tinh_luy_ke === 1 || cd.la_cong_doan_tinh_luy_ke === '1') {
                    const inputs = document.querySelectorAll(`.cell-input[data-cd="${cd.cong_doan_id}"]`);
                    inputs.forEach(input => {
                        const mocId = parseInt(input.dataset.moc);
                        const currentMocThuTu = baoCao.moc_gio_list.find(m => m.id === moc.id)?.thu_tu || 0;
                        const inputMocThuTu = baoCao.moc_gio_list.find(m => m.id === mocId)?.thu_tu || 0;
                        if (inputMocThuTu <= currentMocThuTu) {
                            tongThucTe += parseInt(input.value) || 0;
                        }
                    });
                    
                    if (inputs.length === 0) {
                        baoCao.moc_gio_list.forEach(m => {
                            if (m.thu_tu <= (baoCao.moc_gio_list.find(x => x.id === moc.id)?.thu_tu || 0)) {
                                const key = `${cd.cong_doan_id}_${m.id}`;
                                const entry = baoCao.entries[key];
                                if (entry) {
                                    tongThucTe += parseInt(entry.so_luong) || 0;
                                }
                            }
                        });
                    }
                }
            });
            
            const hieuSuatCell = document.querySelector(`.hieu-suat-cell[data-moc="${moc.id}"]`);
            if (hieuSuatCell && chiTieu > 0) {
                const hieuSuat = (tongThucTe / chiTieu) * 100;
                hieuSuatCell.textContent = hieuSuat.toFixed(1) + '%';
                
                hieuSuatCell.classList.remove('hieu-suat-green', 'hieu-suat-yellow', 'hieu-suat-red');
                if (hieuSuat >= 100) {
                    hieuSuatCell.classList.add('hieu-suat-green');
                } else if (hieuSuat >= 90) {
                    hieuSuatCell.classList.add('hieu-suat-yellow');
                } else {
                    hieuSuatCell.classList.add('hieu-suat-red');
                }
            } else if (hieuSuatCell) {
                hieuSuatCell.textContent = '-';
                hieuSuatCell.classList.remove('hieu-suat-green', 'hieu-suat-yellow', 'hieu-suat-red');
            }
        });
    }

    updateRowLuyKe(cdId) {
        const row = document.querySelector(`tr[data-cd="${cdId}"]`);
        if (!row) return;
        
        const inputs = row.querySelectorAll('.cell-input');
        const inputValuesByMoc = {};
        let total = 0;

        inputs.forEach(inp => {
            const val = parseInt(inp.value) || 0;
            total += val;
            const mocId = inp.dataset.moc;
            if (mocId) inputValuesByMoc[mocId] = val;
        });
        
        const luyKeCell = row.querySelector('.cell-luyke');
        if (luyKeCell) {
            const isStatusEnabled = isLuyKeStatusEnabled();

            if (isStatusEnabled) {
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
                } else {
                    luyKeCell.textContent = total;
                }
            } else {
                luyKeCell.textContent = total;
            }
        }
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
        } else if (key === 'ArrowLeft' && input.selectionStart === 0) {
            e.preventDefault();
            this.moveToCell(input, 0, -1);
        } else if (key === 'ArrowRight' && input.selectionStart === input.value.length) {
            e.preventDefault();
            this.moveToCell(input, 0, 1);
        }
    }
    
    moveToNextCell(currentInput, direction) {
        const allInputs = Array.from(document.querySelectorAll('.cell-input'));
        const currentIndex = allInputs.indexOf(currentInput);
        const nextIndex = currentIndex + direction;
        
        if (nextIndex >= 0 && nextIndex < allInputs.length) {
            allInputs[nextIndex].focus();
        }
    }
    
    moveToCell(currentInput, rowDelta, colDelta) {
        const currentRow = currentInput.closest('tr');
        const currentCell = currentInput.closest('td');
        const rows = Array.from(document.querySelectorAll('.excel-grid tbody tr'));
        const rowIndex = rows.indexOf(currentRow);
        const cells = Array.from(currentRow.querySelectorAll('td'));
        const colIndex = cells.indexOf(currentCell);
        
        const targetRowIndex = rowIndex + rowDelta;
        const targetColIndex = colIndex + colDelta;
        
        if (targetRowIndex >= 0 && targetRowIndex < rows.length) {
            const targetRow = rows[targetRowIndex];
            const targetCells = targetRow.querySelectorAll('td');
            
            if (targetColIndex >= 0 && targetColIndex < targetCells.length) {
                const targetInput = targetCells[targetColIndex].querySelector('.cell-input');
                if (targetInput) {
                    targetInput.focus();
                }
            }
        }
    }
}