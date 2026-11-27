const CONTAINER_ID = 'toast-container';
const CONTAINER_CLASS = 'fixed top-4 right-4 z-[1000] flex flex-col gap-3 pointer-events-none';

function getContainer() {
    let container = document.getElementById(CONTAINER_ID);
    if (!container) {
        container = document.createElement('div');
        container.id = CONTAINER_ID;
        container.className = CONTAINER_CLASS;
        document.body.appendChild(container);
    }
    return container;
}

export const toast = {
    activeToasts: {},

    show(message, type = 'info', duration = 3000) {
        const container = getContainer();

        const key = `${type}|${message}`;

        if (this.activeToasts[key]) {
            const data = this.activeToasts[key];

            if (data.removeTimeoutId) {
                clearTimeout(data.removeTimeoutId);
                data.removeTimeoutId = null;
                data.element.classList.remove('translate-x-full', 'opacity-0');
            }

            data.count++;

            let badge = data.element.querySelector('.toast-badge');
            if (!badge) {
                badge = document.createElement('div');
                badge.className = 'toast-badge absolute -top-2 -left-2 w-6 h-6 flex items-center justify-center bg-red-600 text-white text-xs font-bold rounded-full shadow border border-white z-10';
                data.element.appendChild(badge);
                data.element.classList.add('relative');
            }

            badge.textContent = data.count > 99 ? '99+' : data.count;
            badge.classList.remove('hidden');

            if (data.timeoutId) clearTimeout(data.timeoutId);
            if (duration > 0) {
                data.timeoutId = setTimeout(() => {
                    this.dismiss(key);
                }, duration);
            }

            data.element.classList.remove('scale-105');
            void data.element.offsetWidth;
            data.element.classList.add('scale-105');
            setTimeout(() => {
                data.element.classList.remove('scale-105');
            }, 100);

            return;
        }

        const el = document.createElement('div');

        let bgClass, iconHtml;
        switch (type) {
            case 'success':
                bgClass = 'bg-[#4CAF50]';
                iconHtml = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                break;
            case 'error':
            case 'danger':
                bgClass = 'bg-[#f44336]';
                iconHtml = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                break;
            case 'warning':
                bgClass = 'bg-[#ff9800]';
                iconHtml = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                break;
            default:
                bgClass = 'bg-[#143583]';
                iconHtml = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        }

        el.className = `pointer-events-auto flex items-center w-full max-w-xs p-4 space-x-3 text-white rounded-lg shadow-lg transform transition-all duration-300 translate-x-full opacity-0 ${bgClass}`;
        el.innerHTML = `
            <div class="flex-shrink-0">${iconHtml}</div>
            <div class="flex-1 text-sm font-medium break-words"></div>
            <button class="flex-shrink-0 ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex h-8 w-8 hover:bg-white/20 focus:ring-2 focus:ring-white">
                <span class="sr-only">Close</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        `;

        el.querySelector('.break-words').textContent = message;

        el.querySelector('button').addEventListener('click', () => this.dismiss(key));

        container.appendChild(el);

        let timeoutId = null;
        if (duration > 0) {
            timeoutId = setTimeout(() => {
                this.dismiss(key);
            }, duration);
        }

        this.activeToasts[key] = {
            element: el,
            count: 1,
            timeoutId,
            removeTimeoutId: null
        };

        requestAnimationFrame(() => {
            el.classList.remove('translate-x-full', 'opacity-0');
        });
    },

    dismiss(key) {
        if (!this.activeToasts[key]) return;

        const data = this.activeToasts[key];
        const { element, timeoutId } = data;

        if (timeoutId) clearTimeout(timeoutId);

        element.classList.add('translate-x-full', 'opacity-0');

        data.removeTimeoutId = setTimeout(() => {
            if (element.parentElement) element.remove();
            delete this.activeToasts[key];
        }, 300);
    },

    success(msg, dur) { this.show(msg, 'success', dur); },
    error(msg, dur) { this.show(msg, 'error', dur); },
    warning(msg, dur) { this.show(msg, 'warning', dur); },
    info(msg, dur) { this.show(msg, 'info', dur); }
};
