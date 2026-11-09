
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
}
