import { api } from '../api.js';
import { showLoading, hideLoading, showToast, confirmDialog } from '../utils.js';

export const workflowMethods = {
    async submitReport() {
        if (!this.baoCao) return;
        
        if (this.modifiedEntries.size > 0) {
            await this.saveChanges();
        }
        
        if (!await confirmDialog('Bạn có chắc muốn chốt báo cáo này?')) return;
        
        try {
            showLoading();
            const response = await api('POST', `/bao-cao/${this.baoCao.id}/submit`);
            if (response.success) {
                showToast('Đã chốt báo cáo', 'success');
                await this.loadReport(this.baoCao.id);
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi chốt báo cáo', 'error');
        } finally {
            hideLoading();
        }
    },
    
    async completeReport() {
        if (!this.baoCao) return;
        
        if (!await confirmDialog('Bạn có chắc muốn đánh dấu hoàn tất đơn hàng này? Báo cáo sẽ không hiển thị trong danh sách hàng ngày nữa.')) return;
        
        try {
            showLoading();
            const response = await api('POST', `/bao-cao/${this.baoCao.id}/complete`);
            if (response.success) {
                showToast('Đã hoàn tất đơn hàng', 'success');
                this.showReportList();
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi hoàn tất đơn hàng', 'error');
        } finally {
            hideLoading();
        }
    },
    
    async reopenReport() {
        if (!this.baoCao) return;
        
        if (!await confirmDialog('Bạn có chắc muốn mở lại báo cáo này?')) return;
        
        try {
            showLoading();
            const response = await api('POST', `/bao-cao/${this.baoCao.id}/reopen`);
            if (response.success) {
                showToast('Đã mở lại báo cáo', 'success');
                await this.loadReport(this.baoCao.id);
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi mở lại báo cáo', 'error');
        } finally {
            hideLoading();
        }
    },
};
