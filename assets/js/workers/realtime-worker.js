const timers = new Map();

function tick(id, interval) {
    const timer = timers.get(id);
    if (!timer) return;
    
    const now = Date.now();
    self.postMessage({ id, timestamp: now });
    
    timer.timeoutId = setTimeout(() => tick(id, interval), interval);
}

self.onmessage = function(e) {
    const { action, id, interval } = e.data;
    
    switch (action) {
        case 'start':
            if (timers.has(id)) {
                clearTimeout(timers.get(id).timeoutId);
            }
            timers.set(id, { interval, timeoutId: null });
            tick(id, interval);
            break;
            
        case 'stop':
            if (timers.has(id)) {
                clearTimeout(timers.get(id).timeoutId);
                timers.delete(id);
            }
            break;
            
        case 'stopAll':
            timers.forEach((timer) => {
                clearTimeout(timer.timeoutId);
            });
            timers.clear();
            break;
    }
};
