
export class Router {
    constructor(app, options = {}) {
        this.app = app;
        this.routes = [];
        this.currentParams = {};
        this.legacyAliases = options.legacyAliases || {};
        this.defaultRoute = options.defaultRoute || '/nhap-bao-cao';
        
        this.init();
    }

    init() {
    }

    start(handleCurrent = true) {
        window.addEventListener('hashchange', () => this.handleHashChange());
        if (handleCurrent) {
            this.handleHashChange();
        }
    }

    add(pattern, handler) {
        this.routes.push({
            pattern: pattern,
            regex: new RegExp('^' + pattern.replace(/\//g, '\\/').replace(/:([^/]+)/g, '([^/]+)') + '$'),
            keys: (pattern.match(/:([^/]+)/g) || []).map(k => k.substring(1)),
            handler: handler
        });
        return this;
    }

    addLegacyAlias(oldHash, newHash) {
        this.legacyAliases[oldHash] = newHash;
        return this;
    }

    navigate(hash) {
        if (!hash.startsWith('#')) {
            hash = '#' + hash;
        }
        window.location.hash = hash;
        window.scrollTo(0, 0);
    }

    handleHashChange() {
        let hash = window.location.hash.slice(1);
        
        if (!hash) {
            this.navigate(this.defaultRoute);
            return;
        }
        
        if (this.legacyAliases[hash]) {
            history.replaceState(null, null, '#' + this.legacyAliases[hash]);
            hash = this.legacyAliases[hash];
        }
        
        let matched = false;
        for (const route of this.routes) {
            const match = hash.match(route.regex);
            if (match) {
                const params = {};
                route.keys.forEach((key, index) => {
                    params[key] = match[index + 1];
                });
                this.currentParams = params;
                route.handler(params);
                matched = true;
                break;
            }
        }

        if (!matched) {
            this.navigate(this.defaultRoute);
        }
    }

    getCurrentTab() {
        const hash = window.location.hash.slice(1);
        if (hash.startsWith('/')) {
            return hash.slice(1).split('/')[0];
        }
        return hash.split('/')[0];
    }
}
