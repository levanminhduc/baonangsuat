const API_BASE = '/baonangsuat/api';

class NangSuatApp {
    constructor() {
        this.baoCao = null;
        this.version = 1;
        this.modifiedEntries = new Map();
        this.saveTimer = null;
        this.isSaving = false;
        this.currentCell = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadContext().then(() => {
            this.handleInitialRoute();
        });
        
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.reportId) {
                this.loadReport(e.state.reportId, false);
            } else if (e.state === null) {
                this.showReportList(false);
            }
        });
    }

    async handleInitialRoute() {
        const params = new URLSearchParams(window.location.search);
        const line = params.get('line');
        const ma_hang = params.get('ma_hang');
        const ngay = params.get('ngay');

        if (line && ma_hang && ngay) {
            try {
                this.showLoading();
                const response = await this.api('GET', `/bao-cao?ngay_tu=${ngay}&ngay_den=${ngay}`);
                
                if (response.success && Array.isArray(response.data)) {
                    // Tìm báo cáo khớp với mã hàng và ngày (line được filter bởi session backend)
                    const report = response.data.find(r => 
                        r.ma_hang === ma_hang && 
                        r.ngay_bao_cao === ngay
                    );
                    
                    if (report) {
                        this.loadReport(report.id, false);
                    } else {
                        this.showToast('Không tìm thấy báo cáo', 'error');
                        window.history.replaceState(null, '', window.location.pathname);
                    }
                } else {
                    this.showToast('Không tìm thấy dữ liệu', 'error');
                }
            } catch (error) {
                console.error(error);
                this.showToast('Lỗi tải báo cáo từ URL', 'error');
            } finally {
                this.hideLoading();
            }
        }
    }
    
    bindEvents() {
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
        }
        
        const createBtn = document.getElementById('createReportBtn');
        if (createBtn) {
            createBtn.addEventListener('click', () => this.showCreateModal());
        }
        
        const navCreateBtn = document.getElementById('navCreateReportBtn');
        if (navCreateBtn) {
            navCreateBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showCreateModal();
            });
        }
        
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitReport());
        }
        
        const backBtn = document.getElementById('backBtn');
        if (backBtn) {
            backBtn.addEventListener('click', () => this.showReportList());
        }
        
        document.addEventListener('keydown', (e) => this.handleGlobalKeyDown(e));
    }
    
    async loadContext() {
        try {
            this.showLoading();
            const response = await this.api('GET', '/context');
            if (response.success) {
                window.appContext = response.data;
                this.renderHeader(response.data);
                await this.loadReportList();
            }
        } catch (error) {
            this.showToast('Lỗi tải dữ liệu: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    renderHeader(context) {
        const userInfo = document.querySelector('.user-info');
        if (userInfo && context.session) {
            userInfo.innerHTML = `${context.session.ho_ten} (${context.session.line_ten || 'User'})`;
        }
    }
    
    async loadReportList() {
        try {
            const today = new Date().toISOString().split('T')[0];
            const response = await this.api('GET', `/bao-cao?ngay_tu=${today}&ngay_den=${today}`);
            if (response.success) {
                this.renderReportList(response.data);
            }
        } catch (error) {
            this.showToast('Lỗi tải danh sách báo cáo', 'error');
        }
    }
    
    renderReportList(reports) {
        const container = document.getElementById('reportListContainer');
        const editorContainer = document.getElementById('editorContainer');
        
        if (!container) return;
        
        container.classList.remove('hidden');
        if (editorContainer) editorContainer.classList.add('hidden');
        
        const tbody = container.querySelector('tbody');
        if (!tbody) return;
        
        if (reports.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;">Chưa có báo cáo nào hôm nay</td></tr>';
            return;
        }
        
        tbody.innerHTML = reports.map(r => `
            <tr data-id="${r.id}" data-line="${r.ma_line}" data-ma-hang="${r.ma_hang}" data-ngay="${r.ngay_bao_cao}" style="cursor: pointer;">
                <td>${r.ngay_bao_cao}</td>
                <td>${r.ma_hang}</td>
                <td>${r.so_lao_dong}</td>
                <td>${r.ctns}</td>
                <td>${r.ct_gio}</td>
                <td><span class="status-badge status-${r.trang_thai}">${this.getStatusText(r.trang_thai)}</span></td>
            </tr>
        `).join('');
        
        tbody.querySelectorAll('tr[data-id]').forEach(row => {
            row.addEventListener('click', (e) => {
                if (e.ctrlKey || e.metaKey) return; 
                e.preventDefault();
                this.loadReport(parseInt(row.dataset.id));
            });
        });
    }
    
    getStatusText(status) {
        const map = {
            'draft': 'Nháp',
            'submitted': 'Đã gửi',
            'approved': 'Đã duyệt',
            'locked': 'Đã khóa'
        };
        return map[status] || status;
    }
    
    showCreateModal() {
        const modal = document.getElementById('createModal');
        if (modal) {
            modal.classList.remove('hidden');
            this.populateModalSelects();
        }
    }
    
    populateModalSelects() {
        const caSelect = document.getElementById('modalCa');
        const maHangSelect = document.getElementById('modalMaHang');
        
        if (caSelect && window.appContext?.ca_list) {
            caSelect.innerHTML = window.appContext.ca_list.map(ca => 
                `<option value="${ca.id}">${ca.ma_ca} - ${ca.ten_ca}</option>`
            ).join('');
        }
        
        if (maHangSelect && window.appContext?.ma_hang_list) {
            maHangSelect.innerHTML = '<option value="">-- Chọn mã hàng --</option>' +
                window.appContext.ma_hang_list.map(mh => 
                    `<option value="${mh.id}">${mh.ma_hang} - ${mh.ten_hang}</option>`
                ).join('');
        }
    }
    
    async createReport() {
        const ngay = document.getElementById('modalNgay')?.value;
        const ca_id = document.getElementById('modalCa')?.value;
        const ma_hang_id = document.getElementById('modalMaHang')?.value;
        const so_lao_dong = document.getElementById('modalLaoDong')?.value || 0;
        const ctns = document.getElementById('modalCtns')?.value || 0;
        
        if (!ngay || !ca_id || !ma_hang_id) {
            this.showToast('Vui lòng điền đầy đủ thông tin', 'error');
            return;
        }
        
        try {
            this.showLoading();
            const response = await this.api('POST', '/bao-cao', {
                ngay_bao_cao: ngay,
                ca_id: parseInt(ca_id),
                ma_hang_id: parseInt(ma_hang_id),
                so_lao_dong: parseInt(so_lao_dong),
                ctns: parseInt(ctns)
            });
            
            if (response.success) {
                this.closeModal();
                this.showToast('Tạo báo cáo thành công', 'success');
                await this.loadReport(response.bao_cao_id);
            } else {
                this.showToast(response.message, 'error');
            }
        } catch (error) {
            this.showToast('Lỗi tạo báo cáo: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    closeModal() {
        document.querySelectorAll('.modal').forEach(m => m.classList.add('hidden'));
    }
    
    async loadReport(baoCaoId, updateUrl = true) {
        try {
            this.showLoading();
            const response = await this.api('GET', `/bao-cao/${baoCaoId}`);
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
                this.showToast(response.message, 'error');
            }
        } catch (error) {
            this.showToast('Lỗi tải báo cáo: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    renderEditor() {
        const listContainer = document.getElementById('reportListContainer');
        const editorContainer = document.getElementById('editorContainer');
        
        if (listContainer) listContainer.classList.add('hidden');
        if (editorContainer) editorContainer.classList.remove('hidden');
        
        this.renderReportHeader();
        this.renderGrid();
        this.updateStatusBar('Đã tải báo cáo');
    }
    
    renderReportHeader() {
        const header = document.querySelector('.report-header');
        if (!header || !this.baoCao) return;
        
        const bc = this.baoCao;
        const isEditable = bc.trang_thai === 'draft';
        
        header.innerHTML = `
            <div class="header-field">
                <label>LINE:</label>
                <span class="value">${bc.ma_line}</span>
            </div>
            <div class="header-field">
                <label>LĐ:</label>
                ${isEditable 
                    ? `<input type="number" id="headerLaoDong" value="${bc.so_lao_dong}" min="0" style="width:60px">`
                    : `<span class="value">${bc.so_lao_dong}</span>`
                }
            </div>
            <div class="header-field">
                <label>MH:</label>
                <span class="value">${bc.ma_hang}</span>
            </div>
            <div class="header-field">
                <label>CTNS:</label>
                ${isEditable 
                    ? `<input type="number" id="headerCtns" value="${bc.ctns}" min="0" style="width:80px">`
                    : `<span class="value">${bc.ctns}</span>`
                }
            </div>
            <div class="header-field">
                <label>CT/Giờ:</label>
                <span class="value" id="ctGioDisplay">${bc.ct_gio}</span>
            </div>
            <div class="header-actions">
                <button id="backBtn" class="btn">← Quay lại</button>
                ${isEditable ? `<button id="submitBtn" class="btn btn-success">Chốt báo cáo</button>` : ''}
            </div>
        `;
        
        if (isEditable) {
            const ldInput = document.getElementById('headerLaoDong');
            const ctnsInput = document.getElementById('headerCtns');
            
            if (ldInput) {
                ldInput.addEventListener('change', () => this.updateHeader());
            }
            if (ctnsInput) {
                ctnsInput.addEventListener('change', () => this.updateHeader());
            }
        }
        
        const backBtn = document.getElementById('backBtn');
        if (backBtn) {
            backBtn.addEventListener('click', () => this.showReportList());
        }
        
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitReport());
        }
    }
    
    formatGio(gioString) {
        const parts = gioString.split(':');
        const hour = parseInt(parts[0], 10);
        return `${hour}h`;
    }
    
    renderGrid() {
        const container = document.getElementById('gridContainer');
        if (!container || !this.baoCao) return;
        
        const bc = this.baoCao;
        const isEditable = bc.trang_thai === 'draft';
        
        let headerHtml = `
            <tr>
                <th class="col-stt" rowspan="3">STT</th>
                <th class="col-name" rowspan="3">Tên công đoạn</th>
                ${bc.moc_gio_list.map(m => `<th class="col-hour">${this.formatGio(m.gio)}</th>`).join('')}
                <th class="col-luyke" rowspan="3">Lũy kế</th>
            </tr>
            <tr class="row-chitieu">
                ${bc.moc_gio_list.map(m => `<td>${bc.chi_tieu_luy_ke[m.id] || 0}</td>`).join('')}
            </tr>
            <tr class="row-hieuSuat">
                ${bc.moc_gio_list.map(m => `<td class="hieu-suat-cell" data-moc="${m.id}">-</td>`).join('')}
            </tr>
        `;
        
        let bodyHtml = '';
        bc.routing.forEach((cd, idx) => {
            let luyKe = 0;
            bodyHtml += `<tr data-cd="${cd.cong_doan_id}">`;
            bodyHtml += `<td class="cell-readonly">${idx + 1}</td>`;
            bodyHtml += `<td class="cell-name" title="${cd.ten_cong_doan}">${cd.ten_cong_doan}</td>`;
            
            bc.moc_gio_list.forEach(moc => {
                const key = `${cd.cong_doan_id}_${moc.id}`;
                const entry = bc.entries[key];
                const value = entry ? parseInt(entry.so_luong) : 0;
                luyKe += value;
                
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
            
            bodyHtml += `<td class="cell-luyke" data-cd="${cd.cong_doan_id}">${luyKe}</td>`;
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
        
        this.updateHieuSuat();
    }
    
    updateHieuSuat() {
        if (!this.baoCao) return;
        
        const bc = this.baoCao;
        
        bc.moc_gio_list.forEach(moc => {
            const chiTieu = bc.chi_tieu_luy_ke[moc.id] || 0;
            
            let tongThucTe = 0;
            bc.routing.forEach(cd => {
                if (cd.la_cong_doan_tinh_luy_ke === 1 || cd.la_cong_doan_tinh_luy_ke === '1') {
                    const inputs = document.querySelectorAll(`.cell-input[data-cd="${cd.cong_doan_id}"]`);
                    inputs.forEach(input => {
                        const mocId = parseInt(input.dataset.moc);
                        const currentMocThuTu = bc.moc_gio_list.find(m => m.id === moc.id)?.thu_tu || 0;
                        const inputMocThuTu = bc.moc_gio_list.find(m => m.id === mocId)?.thu_tu || 0;
                        if (inputMocThuTu <= currentMocThuTu) {
                            tongThucTe += parseInt(input.value) || 0;
                        }
                    });
                    
                    if (inputs.length === 0) {
                        bc.moc_gio_list.forEach(m => {
                            if (m.thu_tu <= (bc.moc_gio_list.find(x => x.id === moc.id)?.thu_tu || 0)) {
                                const key = `${cd.cong_doan_id}_${m.id}`;
                                const entry = bc.entries[key];
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
    
    bindGridEvents() {
        const inputs = document.querySelectorAll('.cell-input');
        
        inputs.forEach(input => {
            input.addEventListener('focus', (e) => {
                this.currentCell = e.target;
                e.target.select();
            });
            
            input.addEventListener('input', (e) => {
                this.onCellChange(e.target);
            });
            
            input.addEventListener('keydown', (e) => {
                this.handleCellKeyDown(e);
            });
        });
    }
    
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
        this.updateRowLuyKe(cdId);
        this.updateHieuSuat();
        this.scheduleSave();
    }
    
    updateRowLuyKe(cdId) {
        const row = document.querySelector(`tr[data-cd="${cdId}"]`);
        if (!row) return;
        
        const inputs = row.querySelectorAll('.cell-input');
        let total = 0;
        inputs.forEach(inp => {
            total += parseInt(inp.value) || 0;
        });
        
        const luyKeCell = row.querySelector('.cell-luyke');
        if (luyKeCell) {
            luyKeCell.textContent = total;
        }
    }
    
    scheduleSave() {
        if (this.saveTimer) {
            clearTimeout(this.saveTimer);
        }
        this.saveTimer = setTimeout(() => this.saveChanges(), 1500);
        this.updateStatusBar('Đang chờ lưu...', 'saving');
    }
    
    async saveChanges() {
        if (this.modifiedEntries.size === 0 || this.isSaving) return;
        
        this.isSaving = true;
        this.updateStatusBar('Đang lưu...', 'saving');
        
        const entries = Array.from(this.modifiedEntries.values());
        
        try {
            const response = await this.api('PUT', `/bao-cao/${this.baoCao.id}/entries`, {
                entries: entries,
                version: this.version
            });
            
            if (response.success) {
                this.version = response.new_version;
                this.modifiedEntries.clear();
                
                document.querySelectorAll('.cell-input.modified').forEach(inp => {
                    inp.classList.remove('modified');
                    inp.classList.add('saved');
                    setTimeout(() => inp.classList.remove('saved'), 500);
                });
                
                this.updateStatusBar('Đã lưu', 'success');
            } else {
                this.updateStatusBar('Lỗi: ' + response.message, 'error');
                if (response.message.includes('cập nhật bởi người khác')) {
                    this.showToast('Dữ liệu đã thay đổi, đang tải lại...', 'warning');
                    await this.loadReport(this.baoCao.id);
                }
            }
        } catch (error) {
            this.updateStatusBar('Lỗi lưu dữ liệu', 'error');
        } finally {
            this.isSaving = false;
        }
    }
    
    async updateHeader() {
        const ldInput = document.getElementById('headerLaoDong');
        const ctnsInput = document.getElementById('headerCtns');
        
        if (!ldInput || !ctnsInput) return;
        
        try {
            const response = await this.api('PUT', `/bao-cao/${this.baoCao.id}/header`, {
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
                
                this.showToast('Cập nhật header thành công', 'success');
            } else {
                this.showToast(response.message, 'error');
            }
        } catch (error) {
            this.showToast('Lỗi cập nhật header', 'error');
        }
    }
    
    async submitReport() {
        if (!this.baoCao) return;
        
        if (this.modifiedEntries.size > 0) {
            await this.saveChanges();
        }
        
        if (!confirm('Bạn có chắc muốn chốt báo cáo này?')) return;
        
        try {
            this.showLoading();
            const response = await this.api('POST', `/bao-cao/${this.baoCao.id}/submit`);
            if (response.success) {
                this.showToast('Đã chốt báo cáo', 'success');
                await this.loadReport(this.baoCao.id);
            } else {
                this.showToast(response.message, 'error');
            }
        } catch (error) {
            this.showToast('Lỗi chốt báo cáo', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    showReportList(updateUrl = true) {
        if (this.modifiedEntries.size > 0) {
            this.saveChanges();
        }
        this.baoCao = null;
        if (updateUrl) {
            window.history.pushState(null, '', window.location.pathname);
        }
        this.loadReportList();
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
    
    handleGlobalKeyDown(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            if (this.modifiedEntries.size > 0) {
                this.saveChanges();
            }
        }
    }
    
    updateStatusBar(message, status = 'success') {
        const statusMessage = document.querySelector('.status-message span');
        const statusIndicator = document.querySelector('.status-indicator');
        
        if (statusMessage) {
            statusMessage.textContent = message;
        }
        
        if (statusIndicator) {
            statusIndicator.className = 'status-indicator';
            if (status === 'saving') {
                statusIndicator.classList.add('saving');
            } else if (status === 'error') {
                statusIndicator.classList.add('error');
            }
        }
    }
    
    async logout() {
        try {
            await this.api('GET', '/auth/logout');
            window.location.href = 'index.php';
        } catch (error) {
            window.location.href = 'index.php';
        }
    }
    
    async api(method, endpoint, data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(API_BASE + endpoint, options);
        return await response.json();
    }
    
    showLoading() {
        let overlay = document.querySelector('.loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    }
    
    hideLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
    
    showToast(message, type = 'success') {
        const existing = document.querySelectorAll('.toast');
        existing.forEach(t => t.remove());
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
}

class LoginApp {
    constructor() {
        this.bindEvents();
    }
    
    bindEvents() {
        const form = document.getElementById('loginForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleLogin(e));
        }
    }
    
    async handleLogin(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('loginError');
        const submitBtn = e.target.querySelector('button[type="submit"]');
        
        if (!username || !password) {
            this.showError('Vui lòng nhập đầy đủ thông tin');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang đăng nhập...';
        
        try {
            const response = await fetch('/baonangsuat/api/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.need_select_line) {
                    this.showLineSelect(result.lines);
                } else if (result.no_line) {
                    this.showError('Tài khoản chưa được phân LINE. Liên hệ admin.');
                } else {
                    window.location.href = 'nhap-nang-suat.php';
                }
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Lỗi kết nối server');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Đăng nhập';
        }
    }
    
    showError(message) {
        const errorDiv = document.getElementById('loginError');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
        }
    }
    
    showLineSelect(lines) {
        const modal = document.getElementById('lineSelectModal');
        const list = document.getElementById('lineList');
        
        if (!modal || !list) return;
        
        list.innerHTML = lines.map(line => 
            `<li data-id="${line.id}">${line.ma_line} - ${line.ten_line}</li>`
        ).join('');
        
        list.querySelectorAll('li').forEach(li => {
            li.addEventListener('click', async () => {
                const lineId = li.dataset.id;
                try {
                    const response = await fetch('/baonangsuat/api/auth/select-line', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ line_id: parseInt(lineId) })
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.location.href = 'nhap-nang-suat.php';
                    } else {
                        alert(result.message);
                    }
                } catch (error) {
                    alert('Lỗi chọn LINE');
                }
            });
        });
        
        modal.classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('loginForm')) {
        new LoginApp();
    } else if (document.getElementById('editorContainer')) {
        window.app = new NangSuatApp();
    }
});
