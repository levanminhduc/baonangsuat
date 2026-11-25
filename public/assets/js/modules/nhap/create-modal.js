import { api } from '../api.js';
import { showLoading, hideLoading, showToast, escapeHtml } from '../utils.js';

export const createModalMethods = {
    showCreateModal() {
        const modal = document.getElementById('createModal');
        if (modal) {
            modal.classList.remove('hidden');
            this.populateModalSelects();
        }
    },
    
    async populateModalSelects() {
        const caSelect = document.getElementById('modalCa');
        const maHangSelect = document.getElementById('modalMaHang');
        const lineGroup = document.getElementById('modalLineGroup');
        const lineSelect = document.getElementById('modalLine');
        
        if (caSelect && window.appContext?.ca_list) {
            caSelect.innerHTML = window.appContext.ca_list.map(ca =>
                `<option value="${escapeHtml(ca.id)}">${escapeHtml(ca.ma_ca)} - ${escapeHtml(ca.ten_ca)}</option>`
            ).join('');
        }
        
        if (maHangSelect && window.appContext?.ma_hang_list) {
            maHangSelect.innerHTML = '<option value="">-- Chọn mã hàng --</option>' +
                window.appContext.ma_hang_list.map(mh =>
                    `<option value="${escapeHtml(mh.id)}">${escapeHtml(mh.ma_hang)} - ${escapeHtml(mh.ten_hang)}</option>`
                ).join('');
        }
        
        if (window.appContext?.can_create_report_any_line && lineGroup && lineSelect) {
            lineGroup.style.display = 'block';
            try {
                const response = await api('GET', '/admin/lines');
                if (response.success) {
                    lineSelect.innerHTML = '<option value="">-- Chọn LINE --</option>' +
                        response.data.filter(l => l.is_active).map(l =>
                            `<option value="${escapeHtml(l.id)}">${escapeHtml(l.ma_line)} - ${escapeHtml(l.ten_line)}</option>`
                        ).join('');
                }
            } catch (e) {
                console.error('Error loading lines', e);
            }
        } else if (lineGroup) {
            lineGroup.style.display = 'none';
        }

        if (maHangSelect) {
            maHangSelect.onchange = () => {
                this.fillDinhMuc();
                this.onModalMaHangChange();
            };
        }
        if (lineSelect) lineSelect.onchange = () => this.fillDinhMuc();
        const poSelect = document.getElementById('modalPo');
        if (poSelect) poSelect.onchange = () => this.onModalPoChange();
        this.fillDinhMuc();
        this.onModalMaHangChange();
    },

    async onModalMaHangChange() {
        const poSelect = document.getElementById('modalPo');
        if (!poSelect) return;
        await this.loadPoOptions(document.getElementById('modalMaHang')?.value, poSelect, document.getElementById('modalPoHint'));
        await this.onModalPoChange();
    },

    onModalPoChange() {
        return this.loadSubartOptions(
            document.getElementById('modalMaHang')?.value,
            document.getElementById('modalPo')?.value,
            document.getElementById('modalSubart'),
            document.getElementById('modalSubartGroup')
        );
    },

    // Tự điền LĐ + CTNS đã import theo mã hàng + LINE; không có thì để 0 cho người dùng nhập
    fillDinhMuc() {
        const maHangId = document.getElementById('modalMaHang')?.value;
        const laoDongInput = document.getElementById('modalLaoDong');
        const ctnsInput = document.getElementById('modalCtns');
        if (!laoDongInput || !ctnsInput) return;

        const lineId = window.appContext?.can_create_report_any_line
            ? document.getElementById('modalLine')?.value
            : window.appContext?.line?.id;
        const maHang = (window.appContext?.ma_hang_list || []).find(mh => String(mh.id) === String(maHangId));
        const dinhMuc = maHang?.dinh_muc?.[lineId];

        laoDongInput.value = dinhMuc ? dinhMuc.so_lao_dong : 0;
        ctnsInput.value = dinhMuc ? dinhMuc.ctns : 0;
    },
    
    async createReport() {
        const ngay = document.getElementById('modalNgay')?.value;
        const ca_id = document.getElementById('modalCa')?.value;
        const ma_hang_id = document.getElementById('modalMaHang')?.value;
        const so_lao_dong = document.getElementById('modalLaoDong')?.value || 0;
        const ctns = document.getElementById('modalCtns')?.value || 0;
        const line_id = document.getElementById('modalLine')?.value;
        
        if (!ngay || !ca_id || !ma_hang_id) {
            showToast('Vui lòng điền đầy đủ thông tin', 'error');
            return;
        }
        
        if (window.appContext?.can_create_report_any_line && !line_id) {
            showToast('Vui lòng chọn LINE', 'error');
            return;
        }
        
        try {
            showLoading();
            const payload = {
                ngay_bao_cao: ngay,
                ca_id: parseInt(ca_id),
                ma_hang_id: parseInt(ma_hang_id),
                so_lao_dong: parseInt(so_lao_dong),
                ctns: parseInt(ctns)
            };

            const po_number = document.getElementById('modalPo')?.value;
            const sub_art_code = document.getElementById('modalSubart')?.value;
            if (po_number) {
                payload.po_number = po_number;
                if (sub_art_code) payload.sub_art_code = sub_art_code;
            }
            
            if (window.appContext?.can_create_report_any_line && line_id) {
                payload.line_id = parseInt(line_id);
            }
            
            const response = await api('POST', '/bao-cao', payload);
            
            if (response.success) {
                this.closeModal();
                showToast('Tạo báo cáo thành công', 'success');
                await this.loadReport(response.bao_cao_id);
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi tạo báo cáo: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    },
    
    closeModal() {
        document.querySelectorAll('.modal').forEach(m => m.classList.add('hidden'));
    },
};
