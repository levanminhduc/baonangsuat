
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
        'locked': 'Đã khóa'
    };
    return map[status] || status;
}

export function showLoading() {
    let overlay = document.querySelector('.loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
}

export function hideLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

export function showToast(message, type = 'success') {
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
}
