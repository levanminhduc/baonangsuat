export function escapeHtml(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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
    
    setTimeout(() => toast.remove(), 3000);
}

export function showLoading(message = null) {
    const overlay = document.getElementById('loadingOverlay');
    if (!overlay) return;
    
    overlay.classList.remove('hidden');
    void overlay.offsetWidth;
    
    overlay.style.pointerEvents = 'auto';
    overlay.classList.remove('opacity-0');
    overlay.querySelector('.transform').classList.remove('scale-95');
    
    const activeModal = document.querySelector('.modal:not(.hidden)');
    if (activeModal) {
        const buttons = activeModal.querySelectorAll('button, input, select, textarea');
        buttons.forEach(btn => {
            if (!btn.disabled) {
                btn.dataset.tempDisabled = 'true';
                btn.disabled = true;
                if (btn.classList.contains('btn-primary')) {
                    btn.classList.add('opacity-70', 'cursor-wait');
                }
            }
        });
    }
}

export function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (!overlay) return;
    
    overlay.classList.add('opacity-0');
    overlay.querySelector('.transform').classList.add('scale-95');
    overlay.style.pointerEvents = 'none';
    
    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 300);
    
    const disabledElements = document.querySelectorAll('[data-temp-disabled="true"]');
    disabledElements.forEach(el => {
        el.disabled = false;
        delete el.dataset.tempDisabled;
        el.classList.remove('opacity-70', 'cursor-wait');
    });
}

export function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

export function showConfirmModal(message, callback, title = 'Xác nhận', variant = 'primary') {
    const modal = document.getElementById('confirmModal');
    
    const titleEl = document.getElementById('confirmModalTitle');
    if (titleEl) {
        titleEl.textContent = title;
    }
    
    const header = document.getElementById('confirmModalHeader');
    if (header) {
        header.classList.remove('bg-navbar-theme', 'border-navbar-theme', 'bg-danger', 'border-danger');
        if (variant === 'danger') {
            header.classList.add('bg-danger', 'border-danger');
        } else {
            header.classList.add('bg-navbar-theme', 'border-navbar-theme');
        }
    }
    
    const confirmBtn = document.getElementById('confirmBtn');
    if (confirmBtn) {
        confirmBtn.classList.remove('bg-primary', 'hover:bg-primary-dark', 'bg-danger', 'hover:bg-red-700');
        if (variant === 'danger') {
            confirmBtn.classList.add('bg-danger', 'hover:bg-red-700');
        } else {
            confirmBtn.classList.add('bg-primary', 'hover:bg-primary-dark');
        }
    }
    
    document.getElementById('confirmMessage').textContent = message;
    confirmBtn.onclick = callback;
    modal.classList.remove('hidden');
}
