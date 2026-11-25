import { api } from '../api.js';
import { showLoading, hideLoading, showToast } from '../utils.js';
import { formatSoLuong, updatePoTienDo } from '../report-view.js';

export const poEditorMethods = {
    // Dùng chung cho modal tạo báo cáo và ô đổi PO trên màn nhập; Option(text, value) gán textContent nên chuỗi datchi không cần escape
    async loadPoOptions(maHangId, poSelect, hintEl, selectedPo = '') {
        const seq = poSelect._seq = (poSelect._seq || 0) + 1;
        poSelect.innerHTML = '';
        poSelect.appendChild(new Option('-- Không chọn PO --', ''));
        poSelect.disabled = false;
        hintEl?.classList.add('hidden');
        if (!maHangId) return;

        let available = false;
        let items = [];
        try {
            const response = await api('GET', `/danh-muc/po?ma_hang_id=${encodeURIComponent(maHangId)}`);
            if (seq !== poSelect._seq) return;
            if (response.success) {
                available = !!response.data.available;
                items = response.data.items || [];
            }
        } catch (e) {
            if (seq !== poSelect._seq) return;
        }

        if (!available) {
            poSelect.disabled = true;
            hintEl?.classList.remove('hidden');
            if (selectedPo) {
                // Không đọc được datchi vẫn hiển thị PO đang gắn, không để ô trống
                poSelect.appendChild(new Option(selectedPo, selectedPo));
                poSelect.value = selectedPo;
            }
            return;
        }
        items.forEach(it => {
            const khach = it.customer_name ? ` — ${it.customer_name}` : '';
            poSelect.appendChild(new Option(`${it.po_number}${khach} — SL ${formatSoLuong(it.quantity)}`, it.po_number));
        });
        if (selectedPo) {
            // PO đã gắn nhưng không còn trong danh sách (huỷ...) vẫn giữ để không mất giá trị
            if (!items.some(it => it.po_number === selectedPo)) poSelect.appendChild(new Option(selectedPo, selectedPo));
            poSelect.value = selectedPo;
        }
    },

    async loadSubartOptions(maHangId, po, subSelect, groupEl, selectedSub = '') {
        if (!subSelect) return;
        const seq = subSelect._seq = (subSelect._seq || 0) + 1;
        const show = visible => (groupEl || subSelect).classList.toggle('hidden', !visible);
        subSelect.innerHTML = '';
        show(false);
        if (!maHangId || !po) return;

        let items = [];
        try {
            const response = await api('GET', `/danh-muc/po-subart?ma_hang_id=${encodeURIComponent(maHangId)}&po_number=${encodeURIComponent(po)}`);
            if (seq !== subSelect._seq) return;
            if (response.success && response.data.available) items = response.data.items || [];
        } catch (e) {
            return;
        }
        if (items.length === 0) return;

        subSelect.appendChild(new Option('-- Không chọn subart --', ''));
        items.forEach(it => {
            const sl = it.so_luong === null || it.so_luong === undefined ? 'chưa nhập' : `SL ${formatSoLuong(it.so_luong)}`;
            subSelect.appendChild(new Option(`${it.sub_art_code} — ${sl}`, it.sub_art_code));
        });
        if (selectedSub) subSelect.value = selectedSub;
        show(true);
    },

    poEditorHtml() {
        const selectClass = 'w-full px-2 py-1 border border-gray-300 rounded bg-white text-sm';
        return `<div id="poEditor" class="bns-field hidden flex-wrap gap-2" style="flex-basis: 100%">
            <select id="poEditPo" class="${selectClass}" aria-label="PO"></select>
            <div id="poEditSubGroup" class="hidden w-full"><select id="poEditSubart" class="${selectClass}" aria-label="Subart"></select></div>
            <span id="poEditHint" class="hidden text-xs text-warning">Không kết nối được dữ liệu PO</span>
            <div class="flex gap-2">
                <button type="button" id="poEditSave" class="px-3 py-1 rounded bg-primary text-white text-sm">Lưu</button>
                <button type="button" id="poEditCancel" class="px-3 py-1 rounded border border-gray-300 text-sm">Hủy</button>
            </div>
        </div>`;
    },

    bindPoEditor() {
        const editor = document.getElementById('poEditor');
        const poSelect = document.getElementById('poEditPo');
        const subSelect = document.getElementById('poEditSubart');
        if (!editor || !poSelect) return;
        const bc = this.baoCao;

        const loadSub = (selected = '') => this.loadSubartOptions(bc.ma_hang_id, poSelect.value, subSelect, document.getElementById('poEditSubGroup'), selected);
        poSelect.addEventListener('change', () => loadSub());

        document.getElementById('poEditBtn')?.addEventListener('click', async () => {
            const opening = editor.classList.toggle('hidden') === false;
            if (!opening) return;
            await this.loadPoOptions(bc.ma_hang_id, poSelect, document.getElementById('poEditHint'), bc.po?.po_number || '');
            const selectedSub = bc.po?.sub_art?.sub_art_code || '';
            await loadSub(selectedSub);
            if (poSelect.disabled && selectedSub && subSelect.options.length === 0) {
                subSelect.appendChild(new Option(selectedSub, selectedSub));
                subSelect.value = selectedSub;
                document.getElementById('poEditSubGroup')?.classList.remove('hidden');
            }
            // PO không khả dụng thì khoá luôn nút Lưu, tránh gửi po_number rỗng làm mất PO đang gắn
            subSelect.disabled = poSelect.disabled;
            const saveBtn = document.getElementById('poEditSave');
            if (saveBtn) saveBtn.disabled = poSelect.disabled;
        });
        document.getElementById('poEditCancel')?.addEventListener('click', () => editor.classList.add('hidden'));
        document.getElementById('poEditSave')?.addEventListener('click', () => this.updatePoSubart(poSelect.value, subSelect.value));
    },

    async updatePoSubart(po, sub) {
        if (document.getElementById('poEditPo')?.disabled) return;
        if (this.modifiedEntries.size > 0) await this.saveChanges();
        try {
            showLoading();
            const response = await api('PUT', `/bao-cao/${this.baoCao.id}/header`, {
                so_lao_dong: parseInt(this.baoCao.so_lao_dong) || 0,
                ctns: parseInt(this.baoCao.ctns) || 0,
                po_number: po || null,
                sub_art_code: po && sub ? sub : null,
                version: this.version
            });
            if (response.success) {
                await this.loadReport(this.baoCao.id);
                showToast('Đã cập nhật PO/subart', 'success');
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi cập nhật PO/subart', 'error');
        } finally {
            hideLoading();
        }
    },

    // Lũy kế PO/subart = lũy kế các báo cáo khác (server) + thành phẩm báo cáo đang mở đọc từ lưới
    refreshPoTienDo() {
        const bc = this.baoCao;
        if (!bc?.po) return;
        const cd = (bc.routing || []).find(c => c.la_cong_doan_tinh_luy_ke === 1 || c.la_cong_doan_tinh_luy_ke === '1');
        const thanhPham = cd ? this.gridManager.rowTotal(cd.cong_doan_id) : 0;
        updatePoTienDo(document.getElementById('bnsInfo'), bc.po, thanhPham);
    },
};
