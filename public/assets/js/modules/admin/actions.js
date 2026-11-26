const actions = new Map();

export function registerActions(map) {
    Object.entries(map).forEach(([name, fn]) => actions.set(name, fn));
}

export function runAction(name, args = []) {
    const fn = actions.get(name);
    if (typeof fn !== 'function') return false;
    fn(...args);
    return true;
}
