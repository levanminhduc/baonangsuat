import { api } from '../api.js';
import { showLoading, hideLoading, showToast, updateStatusBar, getStatusText, escapeHtml } from '../utils.js';
import { svgIcon, formatNgay, reportInfoHtml, bindInfoToggle, updateLuyKeSummary } from '../report-view.js';

export const editorMethods = {
    async loadReport(baoCaoId, updateUrl = true) {
        try {
            showLoading();
            const response = await api('GET', `/bao-cao/${baoCaoId}`);
            if (response.success) {
                this.baoCao = response.data;
                this.version = this.baoCao.version;
                this.modifiedEntries.clear();
                
                if (updateUrl) {
                    const params = new URLSearchParams();
                    params.set('line', this.baoCao.ma_line);
                    params.set('ma_hang', this.baoCao.ma_hang);
                    params.set('ngay', this.baoCao.ngay_bao_cao);
                    const newUrl = `${window.location.pathname}?${params.toString()}`;
                    window.history.pushState({ reportId: baoCaoId }, '', newUrl);
                }
                
                this.renderEditor();
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi tải báo cáo: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    },
    
    renderEditor() {
        const listContainer = document.getElementById('reportListContainer');
        const editorContainer = document.getElementById('editorContainer');
        
        if (listContainer) listContainer.classList.add('hidden');
        if (editorContainer) editorContainer.classList.remove('hidden');
        
        this.renderReportHeader();
        this.gridManager.renderGrid(this.baoCao);
        this.refreshPoTienDo();
        updateStatusBar('Đã tải báo cáo');
    },
    
    // Dải thông tin trên bảng nhập: lũy kế mốc đang nhập, trạng thái (mobile), các trường báo cáo
    renderReportHeader() {
        const info = document.getElementById('bnsInfo');
        if (!info || !this.baoCao) return;

        const bc = this.baoCao;
        const isEditable = bc.trang_thai === 'draft';
        const numInput = (id, value, label) => `<input class="bns-num-input" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="off" id="${id}" value="${escapeHtml(value)}" aria-label="${label}">`;

        info.innerHTML = reportInfoHtml(bc, {
            fieldsId: 'headerInfoContainer',
            laoDong: isEditable ? numInput('headerLaoDong', bc.so_lao_dong, 'Lao động') : undefined,
            ctns: isEditable ? numInput('headerCtns', bc.ctns, 'CTNS') : undefined,
            statusExtra: isEditable ? this.saveStatusHtml('') : '',
            poAction: isEditable ? `<button type="button" id="poEditBtn" class="ml-2 text-xs text-primary underline">${bc.po ? 'Đổi' : 'Chọn'}</button>` : '',
            poEditor: isEditable ? this.poEditorHtml() : '',
        });

        const footer = document.getElementById('gridFooter');
        if (footer) {
            footer.innerHTML = '';
            footer.style.display = 'none';
        }

        bindInfoToggle(info);

        if (isEditable) {
            this.bindPoEditor();
            ['headerLaoDong', 'headerCtns'].forEach(id => {
                const input = document.getElementById(id);
                if (!input) return;
                input.addEventListener('focus', () => input.select());
                input.addEventListener('input', () => {
                    const digits = input.value.replace(/\D/g, '');
                    if (input.value !== digits) input.value = digits;
                });
                input.addEventListener('change', () => this.updateHeader());
            });
        }

        // Nút "Thử lại" nằm cố định trong view, chỉ gắn sự kiện một lần
        const retryBtn = document.getElementById('bnsSaveRetry');
        if (retryBtn && !retryBtn.dataset.bound) {
            retryBtn.dataset.bound = '1';
            retryBtn.addEventListener('click', () => this.saveChanges());
        }
        const alertBox = document.getElementById('bnsSaveAlert');
        if (alertBox) alertBox.hidden = true;

        this.renderAppBar();
    },

    saveStatusHtml(extraClass) {
        return `<span class="bns-save ${extraClass}" data-save-status data-state="success" role="status" aria-live="polite">
            ${svgIcon('<path d="M20 6 9 17l-5-5"/>', 'bns-ico-success')}
            ${svgIcon('<path d="M21 12a9 9 0 1 1-6.219-8.56"/>', 'bns-ico-saving')}
            ${svgIcon('<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>', 'bns-ico-error')}
            <span class="bns-save-text">Đã tải</span>
        </span>`;
    },

    // Thanh trên khi mở báo cáo: quay lại, tên báo cáo, trạng thái, trạng thái lưu, nút thao tác.
    // Chi tiết lịch sử truyền history: { onBack, actions } → chỉ đọc, nút do history.js quyết định
    renderAppBar(bc = this.baoCao, history = null) {
        const slot = document.getElementById('bnsBarSlot');
        if (!slot || !bc) return;

        const isDraft = bc.trang_thai === 'draft';
        const isEditable = !history && isDraft;
        const isCompleted = bc.trang_thai === 'completed';
        const canComplete = !history && !isCompleted && !isDraft;
        const isAdmin = this.context?.session?.vai_tro === 'admin';
        const ngay = formatNgay(bc.ngay_bao_cao);
        const title = [bc.ma_line, bc.ten_ca || bc.ma_ca].filter(Boolean).map(escapeHtml).join(' · ');
        const check = svgIcon('<path d="M20 6 9 17l-5-5"/>');

        let actions = history ? history.actions : '';
        if (isEditable) {
            actions += `<button type="button" id="submitBtn" class="bns-bar-btn">${check}<span>Chốt<span class="bns-long"> báo cáo</span></span></button>`;
        }
        if (canComplete) {
            actions += `<button type="button" id="completeBtn" class="bns-bar-btn">${check}<span>Hoàn tất</span></button>`;
        }
        if (!history && isCompleted && isAdmin) {
            actions += `<button type="button" id="reopenBtn" class="bns-bar-btn is-ghost">${svgIcon('<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>')}<span>Mở lại</span></button>`;
        }

        slot.innerHTML = `
            <button type="button" id="backBtn" class="bns-icon-btn" aria-label="Quay lại danh sách" title="Quay lại danh sách">${svgIcon('<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>')}</button>
            <div class="bns-title-wrap">
                <h2 class="bns-title">${title}<span class="bns-bar-date"> · ${escapeHtml(ngay)}</span></h2>
                <span class="bns-badge bns-bar-badge ${isDraft ? 'is-draft' : 'is-locked'}">${escapeHtml(getStatusText(bc.trang_thai))}</span>
            </div>
            ${isEditable ? this.saveStatusHtml('bns-bar-save') : ''}
            ${actions}
        `;
        slot.hidden = false;
        document.body.setAttribute('data-bns-editing', '');

        document.getElementById('backBtn')?.addEventListener('click', () => (history ? history.onBack() : this.showReportList()));
        document.getElementById('submitBtn')?.addEventListener('click', () => this.submitReport());
        document.getElementById('completeBtn')?.addEventListener('click', () => this.completeReport());
        document.getElementById('reopenBtn')?.addEventListener('click', () => this.reopenReport());
    },

    // Về danh sách / sang lịch sử: thanh trên trở lại tiêu đề trang
    resetAppBar() {
        const slot = document.getElementById('bnsBarSlot');
        if (slot) {
            slot.hidden = true;
            slot.innerHTML = '';
        }
        document.body.removeAttribute('data-bns-editing');
    },

    // grid.js gọi mỗi lần tính lại hiệu suất: tóm tắt lũy kế tại mốc đang nhập
    updateLuyKeSummary(summary) {
        updateLuyKeSummary(document.getElementById('bnsInfo'), summary);
    },

    onCellChange(input) {
        const cdId = input.dataset.cd;
        const mocId = input.dataset.moc;
        const value = parseInt(input.value) || 0;
        
        const key = `${cdId}_${mocId}`;
        this.modifiedEntries.set(key, {
            cong_doan_id: parseInt(cdId),
            moc_gio_id: parseInt(mocId),
            so_luong: value
        });
        
        input.classList.add('modified');
        this.gridManager.syncMobileState(input);
        this.gridManager.updateRowLuyKe(cdId);
        this.gridManager.updateHieuSuat(this.baoCao);
        this.scheduleSave();
    },
};
