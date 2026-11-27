import { API_BASE } from './config.js';

const WRITE_METHODS = ['POST', 'PUT', 'DELETE'];

let csrfToken = null;

export async function fetchCsrfToken() {
    try {
        const response = await fetch(API_BASE + '/csrf-token', {
            method: 'GET',
            credentials: 'same-origin'
        });
        const result = await response.json();
        if (result.success) {
            csrfToken = result.token;
        }
    } catch (error) {
        console.error('Failed to fetch CSRF token:', error);
    }
    return csrfToken;
}

async function ensureToken() {
    return csrfToken || fetchCsrfToken();
}

async function send(method, endpoint, headers, body) {
    const isWrite = WRITE_METHODS.includes(method);
    const options = {
        method: method,
        headers: { ...headers },
        credentials: 'same-origin'
    };

    if (isWrite) {
        const token = await ensureToken();
        if (token) {
            options.headers['X-CSRF-Token'] = token;
        }
        if (body) {
            options.body = body;
        }
    }

    const response = await fetch(API_BASE + endpoint, options);
    return response.json();
}

async function sendWithRetry(method, endpoint, headers, body) {
    const result = await send(method, endpoint, headers, body);
    if (result && result.csrf_error) {
        await fetchCsrfToken();
        return send(method, endpoint, headers, body);
    }
    return result;
}

export async function api(method, endpoint, data = null) {
    const body = data ? JSON.stringify(data) : null;
    return sendWithRetry(method, endpoint, { 'Content-Type': 'application/json' }, body);
}

export async function apiUpload(endpoint, formData) {
    return sendWithRetry('POST', endpoint, {}, formData);
}
