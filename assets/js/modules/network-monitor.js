/**
 * Network Monitor Module
 * Detect offline/online và hiển thị overlay khi mất kết nối mạng
 * 
 * @usage
 * import networkMonitor from './modules/network-monitor.js';
 * // Auto-init khi DOM ready trên trang nhap-nang-suat.php
 * 
 * // Đăng ký callbacks
 * networkMonitor.on('network:offline', () => { ... });
 * networkMonitor.on('network:online', () => { ... });
 */

const DEFAULT_CONFIG = {
    overlayId: 'networkOfflineOverlay',
    overlayZIndex: 101, // Cao hơn loading overlay (z-100)
    animationDuration: 300, // ms
    messages: {
        offline: 'Mất kết nối mạng',
        offlineDescription: 'Vui lòng kiểm tra kết nối internet của bạn',
        reconnected: 'Đã kết nối lại mạng!'
    },
    showToastOnReconnect: true,
    autoHideDelay: 500, // Delay trước khi ẩn overlay khi online
    debug: false
};

class NetworkMonitor {
    constructor(config = {}) {
        this.config = { ...DEFAULT_CONFIG, ...config };
        this.isOnline = navigator.onLine;
        this.overlayEl = null;
        this.callbacks = new Map();
        this.boundHandleOnline = this.handleOnline.bind(this);
        this.boundHandleOffline = this.handleOffline.bind(this);
        this.initialized = false;
    }

    /**
     * Khởi tạo network monitor
     */
    init() {
        if (this.initialized) {
            this.log('Already initialized');
            return;
        }

        this.log('Initializing...');

        // Bind events
        window.addEventListener('online', this.boundHandleOnline);
        window.addEventListener('offline', this.boundHandleOffline);

        // Tạo overlay (hidden by default)
        this.createOverlay();

        // Check initial state
        if (!navigator.onLine) {
            this.showOverlay();
        }

        this.initialized = true;
        this.log('Initialized. Current state:', navigator.onLine ? 'online' : 'offline');
    }

    /**
     * Cleanup resources
     */
    destroy() {
        this.log('Destroying...');

        window.removeEventListener('online', this.boundHandleOnline);
        window.removeEventListener('offline', this.boundHandleOffline);

        if (this.overlayEl && this.overlayEl.parentNode) {
            this.overlayEl.parentNode.removeChild(this.overlayEl);
        }

        this.callbacks.clear();
        this.initialized = false;
    }

    /**
     * Kiểm tra trạng thái kết nối hiện tại
     * @returns {boolean}
     */
    isConnected() {
        return this.isOnline;
    }

    /**
     * Đăng ký callback cho event
     * @param {string} event - 'network:online' hoặc 'network:offline'
     * @param {Function} callback
     */
    on(event, callback) {
        if (!this.callbacks.has(event)) {
            this.callbacks.set(event, new Set());
        }
        this.callbacks.get(event).add(callback);
    }

    /**
     * Hủy đăng ký callback
     * @param {string} event
     * @param {Function} callback
     */
    off(event, callback) {
        if (this.callbacks.has(event)) {
            this.callbacks.get(event).delete(callback);
        }
    }

    // ==================== Private Methods ====================

    handleOnline() {
        this.log('Network online');
        this.isOnline = true;

        // Delay một chút trước khi ẩn overlay để tránh flicker
        setTimeout(() => {
            this.hideOverlay();
        }, this.config.autoHideDelay);

        // Show toast nếu enabled
        if (this.config.showToastOnReconnect && window.toast) {
            window.toast.show(this.config.messages.reconnected, 'success');
        }

        // Enable inputs trong editorContainer
        this.toggleEditorInputs(true);

        this.notifyCallbacks('network:online');
    }

    handleOffline() {
        this.log('Network offline');
        this.isOnline = false;
        this.showOverlay();
        
        // Disable inputs trong editorContainer
        this.toggleEditorInputs(false);
        
        this.notifyCallbacks('network:offline');
    }

    /**
     * Toggle trạng thái enable/disable của inputs trong editor
     * @param {boolean} enable
     */
    toggleEditorInputs(enable) {
        const editorContainer = document.getElementById('editorContainer');
        if (!editorContainer) return;

        const inputs = editorContainer.querySelectorAll('input, select, button');
        inputs.forEach(input => {
            if (enable) {
                // Restore trạng thái ban đầu
                if (input.dataset.wasDisabled === 'false') {
                    input.disabled = false;
                }
                delete input.dataset.wasDisabled;
            } else {
                // Lưu trạng thái hiện tại trước khi disable
                input.dataset.wasDisabled = input.disabled.toString();
                input.disabled = true;
            }
        });
    }

    showOverlay() {
        if (!this.overlayEl) return;

        this.overlayEl.classList.remove('hidden');
        // Force reflow
        void this.overlayEl.offsetWidth;

        this.overlayEl.style.pointerEvents = 'auto';
        this.overlayEl.classList.remove('opacity-0');

        const contentEl = this.overlayEl.querySelector('[data-content]');
        if (contentEl) {
            contentEl.classList.remove('scale-95');
        }
    }

    hideOverlay() {
        if (!this.overlayEl) return;

        this.overlayEl.classList.add('opacity-0');

        const contentEl = this.overlayEl.querySelector('[data-content]');
        if (contentEl) {
            contentEl.classList.add('scale-95');
        }

        this.overlayEl.style.pointerEvents = 'none';

        setTimeout(() => {
            this.overlayEl.classList.add('hidden');
        }, this.config.animationDuration);
    }

    createOverlay() {
        // Check if already exists
        const existing = document.getElementById(this.config.overlayId);
        if (existing) {
            this.overlayEl = existing;
            return;
        }

        const { messages, overlayZIndex } = this.config;

        const overlay = document.createElement('div');
        overlay.id = this.config.overlayId;
        overlay.className = `fixed inset-0 z-[${overlayZIndex}] bg-gray-900/70 backdrop-blur-sm hidden flex items-center justify-center transition-opacity duration-300 opacity-0`;
        overlay.style.pointerEvents = 'none';
        overlay.setAttribute('role', 'alertdialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'networkOfflineTitle');
        overlay.setAttribute('aria-describedby', 'networkOfflineDesc');

        overlay.innerHTML = `
            <div data-content class="bg-white p-8 rounded-2xl shadow-2xl flex flex-col items-center gap-5 max-w-sm mx-4 transform scale-95 transition-transform duration-300">
                <!-- Icon wrapper with spinner -->
                <div class="relative w-16 h-16">
                    <div class="absolute inset-0 border-[6px] border-red-500 border-t-transparent rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Messages -->
                <div class="flex flex-col items-center gap-2 text-center">
                    <span id="networkOfflineTitle" class="text-gray-800 font-bold text-xl">${messages.offline}</span>
                    <span id="networkOfflineDesc" class="text-gray-500 text-sm">${messages.offlineDescription}</span>
                </div>
                
                <!-- Reconnecting indicator -->
                <div class="flex items-center gap-2 text-gray-400 text-sm">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"></div>
                    <span>Đang chờ kết nối...</span>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        this.overlayEl = overlay;
    }

    notifyCallbacks(event) {
        if (this.callbacks.has(event)) {
            this.callbacks.get(event).forEach(callback => {
                try {
                    callback();
                } catch (e) {
                    console.error('NetworkMonitor callback error:', e);
                }
            });
        }
    }

    log(...args) {
        if (this.config.debug) {
            console.log('[NetworkMonitor]', ...args);
        }
    }
}

// Singleton instance
const networkMonitor = new NetworkMonitor();

// Auto-init khi DOM ready - chỉ trên trang nhap-nang-suat.php
const shouldInit = () => {
    // Check if on nhap-nang-suat.php page
    return window.location.pathname.includes('nhap-nang-suat');
};

if (shouldInit()) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => networkMonitor.init());
    } else {
        networkMonitor.init();
    }
}

export default networkMonitor;
