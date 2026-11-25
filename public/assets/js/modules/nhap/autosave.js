import { api } from '../api.js';
import { showToast, updateStatusBar } from '../utils.js';
import networkMonitor from '../network-monitor.js';

export const autosaveMethods = {
    scheduleSave() {
        if (this.saveTimer) {
            clearTimeout(this.saveTimer);
        }
        this.saveTimer = setTimeout(() => this.saveChanges(), 1500);
        updateStatusBar('Đang chờ lưu...', 'saving');
    },
    
    async saveChanges() {
        if (this.modifiedEntries.size === 0 || this.isSaving) return;
        
        // Check network trước khi save
        if (!networkMonitor.isConnected()) {
            updateStatusBar('Không có kết nối mạng', 'error');
            return;
        }
        
        this.isSaving = true;
        updateStatusBar('Đang lưu...', 'saving');
        
        const entries = Array.from(this.modifiedEntries.values());
        
        try {
            const response = await api('PUT', `/bao-cao/${this.baoCao.id}/entries`, {
                entries: entries,
                version: this.version
            });
            
            if (response.success) {
                this.version = response.new_version;
                this.modifiedEntries.clear();
                
                document.querySelectorAll('.bns-cell-input.modified, .bns-m-input.modified').forEach(inp => {
                    inp.classList.remove('modified');
                    inp.classList.add('saved');
                    setTimeout(() => inp.classList.remove('saved'), 500);
                });
                
                updateStatusBar('Đã lưu', 'success');
                this.refreshPoTienDo();
            } else {
                updateStatusBar('Lỗi: ' + response.message, 'error');
                if (response.message.includes('cập nhật bởi người khác')) {
                    showToast('Dữ liệu đã thay đổi, đang tải lại...', 'warning');
                    await this.loadReport(this.baoCao.id);
                }
            }
        } catch (error) {
            updateStatusBar('Lỗi lưu dữ liệu', 'error');
        } finally {
            this.isSaving = false;
        }
    },
    
    async updateHeader() {
        const ldInput = document.getElementById('headerLaoDong');
        const ctnsInput = document.getElementById('headerCtns');
        
        if (!ldInput || !ctnsInput) return;
        
        try {
            const response = await api('PUT', `/bao-cao/${this.baoCao.id}/header`, {
                so_lao_dong: parseInt(ldInput.value) || 0,
                ctns: parseInt(ctnsInput.value) || 0,
                version: this.version
            });
            
            if (response.success) {
                this.version = response.new_version;
                const ctGioDisplay = document.getElementById('ctGioDisplay');
                if (ctGioDisplay) {
                    ctGioDisplay.textContent = response.ct_gio;
                }
                this.baoCao.ctns = parseInt(ctnsInput.value) || 0;
                this.baoCao.so_lao_dong = parseInt(ldInput.value) || 0;
                this.baoCao.ct_gio = response.ct_gio;
                
                await this.loadReport(this.baoCao.id);
                
                showToast('Cập nhật header thành công', 'success');
            } else {
                showToast(response.message, 'error');
            }
        } catch (error) {
            showToast('Lỗi cập nhật header', 'error');
        }
    },
};
