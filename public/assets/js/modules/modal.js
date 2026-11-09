const Modal = {
    activeModal: null,
    previousActiveElement: null,
    eventHandlers: new Map(),
    defaults: {
        closeOnBackdrop: true,
        closeOnEsc: true,
        focusTarget: null,
        returnFocusTo: null,
        awaitAnimation: true
    },

    init() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeModal) {
                const modal = document.getElementById(this.activeModal);
                const options = this.getOptions(modal);
                if (options.closeOnEsc) {
                    this.close(this.activeModal, 'escape');
                }
            }
        });

        document.addEventListener('click', (e) => {
            const closeBtn = e.target.closest('[data-modal-close]');
            if (closeBtn) {
                const modal = closeBtn.closest('.modal');
                if (modal) {
                    this.close(modal.id, 'button');
                } else if (this.activeModal) {
                    this.close(this.activeModal, 'button');
                }
            }
        });
    },

    getOptions(modalElement) {
        const defaults = { ...this.defaults };
        if (!modalElement) return defaults;
        
        if (modalElement.dataset.closeOnBackdrop !== undefined) {
            defaults.closeOnBackdrop = modalElement.dataset.closeOnBackdrop === 'true';
        }
        if (modalElement.dataset.closeOnEsc !== undefined) {
            defaults.closeOnEsc = modalElement.dataset.closeOnEsc === 'true';
        }
        return defaults;
    },

    open(id, options = {}) {
        const modal = document.getElementById(id);
        if (!modal) {
            return;
        }

        if (this.activeModal && this.activeModal !== id) {
            this.close(this.activeModal, 'switch');
        }

        this.trigger(id, 'modal:before-open');

        const finalOptions = { ...this.getOptions(modal), ...options };
        modal.dataset.modalOptions = JSON.stringify(finalOptions);

        this.previousActiveElement = document.activeElement;

        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        
        document.body.style.overflow = 'hidden';

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!reduceMotion) {
            const panel = modal.querySelector('[role="document"]') || modal.querySelector('.modal-panel') || modal.children[0];
            if (panel) {
                panel.classList.add('modal-pop-in');
            }
        }

        if (finalOptions.closeOnBackdrop) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.close(id, 'backdrop');
                }
            });
        }

        this.activeModal = id;
        const focusTarget = finalOptions.focusTarget 
            ? modal.querySelector(finalOptions.focusTarget) 
            : this.getFocusableElements(modal)[0];
            
        if (focusTarget) {
            setTimeout(() => focusTarget.focus(), 50);
        }

        modal.addEventListener('keydown', this.handleTabKey);

        this.trigger(id, 'modal:after-open');
        this.trigger(id, 'modal:open');
    },

    close(id, reason = 'programmatic') {
        const modalId = id || this.activeModal;
        if (!modalId) return;
        
        const modal = document.getElementById(modalId);
        if (!modal) return;

        this.trigger(modalId, 'modal:before-close', { reason });

        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');

        const panel = modal.querySelector('[role="document"]') || modal.querySelector('.modal-panel') || modal.children[0];
        if (panel) {
            panel.classList.remove('modal-pop-in');
        }

        document.body.style.overflow = '';

        modal.removeEventListener('keydown', this.handleTabKey);
        
        if (this.previousActiveElement && document.body.contains(this.previousActiveElement)) {
            this.previousActiveElement.focus();
        }

        this.activeModal = null;
        this.previousActiveElement = null;

        this.trigger(modalId, 'modal:after-close', { reason });
        this.trigger(modalId, 'modal:close', { reason });
    },

    closeAll(reason = 'closeAll') {
        if (this.activeModal) {
            this.close(this.activeModal, reason);
        }
    },

    setLoading(id, isLoading) {
        const modal = document.getElementById(id);
        if (!modal) return;
        
        if (isLoading) {
            modal.classList.add('is-loading');
        } else {
            modal.classList.remove('is-loading');
        }
    },

    isOpen(id) {
        return this.activeModal === id;
    },

    on(eventName, handler) {
        if (!this.eventHandlers.has(eventName)) {
            this.eventHandlers.set(eventName, new Set());
        }
        this.eventHandlers.get(eventName).add(handler);
        return () => this.eventHandlers.get(eventName).delete(handler);
    },

    trigger(id, eventName, detail = {}) {
        const modal = document.getElementById(id);
        if (modal) {
            const event = new CustomEvent(eventName, { 
                bubbles: true, 
                detail: { ...detail, modalId: id } 
            });
            modal.dispatchEvent(event);
        }

        if (this.eventHandlers.has(eventName)) {
            this.eventHandlers.get(eventName).forEach(handler => handler({ id, ...detail }));
        }
    },

    getFocusableElements(element) {
        return Array.from(element.querySelectorAll(
            'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )).filter(el => !el.hasAttribute('disabled') && !el.getAttribute('aria-hidden'));
    },

    handleTabKey(e) {
        const modal = e.currentTarget;
        const focusables = Modal.getFocusableElements(modal);
        if (focusables.length === 0) return;

        const first = focusables[0];
        const last = focusables[focusables.length - 1];

        if (e.key === 'Tab') {
            if (e.shiftKey) {
                if (document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        }
    }
};

export default Modal;
