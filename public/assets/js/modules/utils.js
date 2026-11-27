import { toast } from './toast.js';

export function escapeHtml(text) {
    return String(text ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
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
        console.warn('loadingOverlay not found in DOM');
        return;
    }

    overlay.classList.remove('hidden');
    void overlay.offsetWidth;

    overlay.style.pointerEvents = 'auto';
    overlay.classList.remove('opacity-0');
    overlay.querySelector('.transform')?.classList.remove('scale-95');

    const activeModal = document.querySelector('.modal:not(.hidden)');
    if (activeModal) {
        activeModal.querySelectorAll('button, input, select, textarea').forEach(btn => {
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
    overlay.querySelector('.transform')?.classList.add('scale-95');
    overlay.style.pointerEvents = 'none';

    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 300);

    document.querySelectorAll('[data-temp-disabled="true"]').forEach(el => {
        el.disabled = false;
        delete el.dataset.tempDisabled;
        el.classList.remove('opacity-70', 'cursor-wait');
    });
}

export function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

export function showToast(message, type = 'success') {
    toast.show(message, type);
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

let pendingConfirm = null;

function settleConfirm(result) {
    if (!pendingConfirm) return;
    const { modal, resolve } = pendingConfirm;
    pendingConfirm = null;
    modal.removeEventListener('click', onConfirmModalClick);
    modal.classList.add('hidden');
    resolve(result);
}

function onConfirmModalClick(e) {
    if (e.target.closest('#confirmBtn')) {
        settleConfirm(true);
        return;
    }
    const closeEl = e.target.closest('[data-action="close-modal"][data-target="confirmModal"]');
    if (closeEl && (e.target === closeEl || !closeEl.classList.contains('modal'))) {
        settleConfirm(false);
    }
}

function applyConfirmContent(message, title, variant, confirmText, cancelText) {
    const titleEl = document.getElementById('confirmModalTitle');
    if (titleEl) titleEl.textContent = title;

    const header = document.getElementById('confirmModalHeader');
    if (header) {
        header.classList.remove('bg-navbar-theme', 'border-navbar-theme', 'bg-danger', 'border-danger');
        header.classList.add(...(variant === 'danger' ? ['bg-danger', 'border-danger'] : ['bg-navbar-theme', 'border-navbar-theme']));
    }

    const confirmBtn = document.getElementById('confirmBtn');
    if (confirmBtn) {
        confirmBtn.classList.remove('bg-primary', 'hover:bg-primary-dark', 'bg-danger', 'hover:bg-red-700');
        confirmBtn.classList.add(...(variant === 'danger' ? ['bg-danger', 'hover:bg-red-700'] : ['bg-primary', 'hover:bg-primary-dark']));
        if (confirmText) confirmBtn.textContent = confirmText;
    }

    const messageEl = document.getElementById('confirmMessage');
    if (messageEl) messageEl.textContent = message;

    const cancelBtn = document.getElementById('cancelConfirmBtn');
    if (cancelBtn) {
        if (cancelText === '') {
            cancelBtn.classList.add('hidden');
        } else {
            cancelBtn.classList.remove('hidden');
            cancelBtn.textContent = cancelText;
        }
    }
}

function openConfirm(message, title, variant, confirmText, cancelText) {
    const modal = document.getElementById('confirmModal');
    if (!modal) return Promise.resolve(false);

    settleConfirm(false);
    applyConfirmContent(message, title, variant, confirmText, cancelText);

    return new Promise(resolve => {
        pendingConfirm = { modal, resolve };
        modal.addEventListener('click', onConfirmModalClick);
        modal.classList.remove('hidden');
    });
}

export function confirmDialog(message, { title = 'Xác nhận', variant = 'primary', confirmText = 'Xác nhận' } = {}) {
    return openConfirm(message, title, variant, confirmText, 'Hủy');
}

export function showConfirmModal(message, callback, title = 'Xác nhận', variant = 'primary', confirmText = 'Xác nhận', cancelText = 'Hủy') {
    openConfirm(message, title, variant, confirmText, cancelText).then(confirmed => {
        if (confirmed && callback) callback();
    });
}
