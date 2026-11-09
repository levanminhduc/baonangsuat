class RealtimeService {
    constructor() {
        this.worker = null;
        this.serverOffset = 0;
        this.callbacks = new Map();
        this.clockElementId = null;
    }

    init(serverTimestamp) {
        const clientTimestamp = Math.floor(Date.now() / 1000);
        this.serverOffset = serverTimestamp - clientTimestamp;
        
        this.worker = new Worker('/baonangsuat/assets/js/workers/realtime-worker.js');
        this.worker.onmessage = (e) => this.handleMessage(e);
        
        window.addEventListener('beforeunload', () => this.destroy());
    }

    handleMessage(e) {
        const { id, timestamp } = e.data;
        
        if (id === 'clock' && this.clockElementId) {
            this.updateClockDisplay(timestamp);
        }
        
        const callback = this.callbacks.get(id);
        if (callback) {
            callback(timestamp);
        }
    }

    updateClockDisplay(timestamp) {
        const serverTime = new Date(timestamp + (this.serverOffset * 1000));
        const h = serverTime.getHours().toString().padStart(2, '0');
        const m = serverTime.getMinutes().toString().padStart(2, '0');
        const s = serverTime.getSeconds().toString().padStart(2, '0');
        
        const el = document.getElementById(this.clockElementId);
        if (el) {
            el.textContent = `[${h}:${m}:${s}]`;
        }
    }

    startClock(elementId, interval = 1000) {
        this.clockElementId = elementId;
        if (this.worker) {
            this.worker.postMessage({ action: 'start', id: 'clock', interval });
        }
    }

    startPolling(id, interval, callback) {
        this.callbacks.set(id, callback);
        if (this.worker) {
            this.worker.postMessage({ action: 'start', id, interval });
        }
    }

    stopPolling(id) {
        this.callbacks.delete(id);
        if (this.worker) {
            this.worker.postMessage({ action: 'stop', id });
        }
    }

    destroy() {
        if (this.worker) {
            this.worker.postMessage({ action: 'stopAll' });
            this.worker.terminate();
            this.worker = null;
        }
        this.callbacks.clear();
    }
}

const realtimeService = new RealtimeService();
export default realtimeService;
