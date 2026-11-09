const API_BASE = '/baonangsuat/api';
let csrfToken = null;

async function ensureCsrfToken() {
    if (csrfToken) {
        return csrfToken;
    }
    const response = await fetch(API_BASE + '/csrf-token');
    const result = await response.json();
    if (result && result.token) {
        csrfToken = result.token;
    }
    return csrfToken;
}

export async function api(method, endpoint, data = null) {
    const options = {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin'
    };
    
    if (method === 'POST' || method === 'PUT' || method === 'DELETE') {
        await ensureCsrfToken();
        if (csrfToken) {
            options.headers['X-CSRF-Token'] = csrfToken;
        }
    }
    
    if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
        options.body = JSON.stringify(data);
    }
    
    const response = await fetch(API_BASE + endpoint, options);
    return await response.json();
}

export function resetCsrfToken() {
    csrfToken = null;
}
