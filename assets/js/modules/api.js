const API_BASE = '/baonangsuat/api';
let csrfToken = null;

export async function fetchCsrfToken() {
    try {
        const response = await fetch(API_BASE + '/csrf-token', {
            method: 'GET',
            credentials: 'include'
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

export function getCsrfToken() {
    return csrfToken;
}

export async function api(method, endpoint, data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include'
    };
    
    if (csrfToken && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
        options.headers['X-CSRF-Token'] = csrfToken;
    }
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    const response = await fetch(API_BASE + endpoint, options);
    const result = await response.json();
    
    if (result.csrf_error) {
        await fetchCsrfToken();
    }
    
    return result;
}
