
export function formatGio(gioString) {
    const parts = gioString.split(':');
    const hour = parseInt(parts[0], 10);
    return `${hour}h`;
}

export function getStatusText(status) {
    const map = {
        'draft': 'Nháp',
        'submitted': 'Đã gửi',
        'approved': 'Đã duyệt',
        'locked': 'Đã khóa',
        'completed': 'Hoàn tất'
    };
    return map[status] || status;
}

export function showLoading(message = null) {
    const overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        // Fallback cho trường hợp component chưa được include
        console.warn('loadingOverlay not found in DOM');
        return;
    }
    
    overlay.classList.remove('hidden');
    void overlay.offsetWidth; // Force reflow for animation
    
    overlay.style.pointerEvents = 'auto';
    overlay.classList.remove('opacity-0');
    
    const transformEl = overlay.querySelector('.transform');
    if (transformEl) {
        transformEl.classList.remove('scale-95');
    }
}

export function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (!overlay) return;
    
    overlay.classList.add('opacity-0');
    
    const transformEl = overlay.querySelector('.transform');
    if (transformEl) {
        transformEl.classList.add('scale-95');
    }
    
    overlay.style.pointerEvents = 'none';
    
    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 300);
}

export function showToast(message, type = 'success') {
    if (window.toast && typeof window.toast.show === 'function') {
        window.toast.show(message, type);
        return;
    }
    
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

export function updateStatusBar(message, status = 'success') {
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

    // Trạng thái lưu hiển thị ở thanh trên + dải thông tin màn nhập
    let text = message;
    if (message === 'Đã lưu') {
        const now = new Date();
        text = `Đã lưu ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    } else if (message === 'Đã tải báo cáo') {
        text = 'Đã tải';
    } else if (status === 'error') {
        text = 'Chưa lưu';
    }
    document.querySelectorAll('[data-save-status]').forEach(el => {
        el.dataset.state = status;
        const textEl = el.querySelector('.bns-save-text');
        if (textEl) textEl.textContent = text;
    });

    // Lỗi lưu: hiện thanh báo kèm nút "Thử lại" ngay trên bảng
    const alertBox = document.getElementById('bnsSaveAlert');
    if (alertBox) {
        alertBox.hidden = status !== 'error';
        const alertText = document.getElementById('bnsSaveAlertText');
        if (alertText && status === 'error') {
            const reason = message.replace(/^Lỗi:\s*/, '');
            alertText.textContent = reason.includes('chưa được lưu') ? reason : reason + '. Số vừa nhập chưa được lưu.';
        }
    }
}
