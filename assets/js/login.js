// Dọn khoá "ghi nhớ tên đăng nhập" của form login cũ
localStorage.removeItem('rememberedUsername');

const API_BASE = '/baonangsuat/api';
let loginCsrfToken = null;

async function fetchLoginCsrfToken() {
    try {
        const response = await fetch(API_BASE + '/csrf-token', {
            method: 'GET',
            credentials: 'include'
        });
        const result = await response.json();
        if (result.success) {
            loginCsrfToken = result.token;
        }
    } catch (error) {
        console.error('Failed to fetch CSRF token:', error);
    }
    return loginCsrfToken;
}

function notifyError(message) {
    if (window.toast && typeof window.toast.error === 'function') {
        window.toast.error(message);
    } else {
        alert(message);
    }
}

async function showLineSelect(lines) {
    const modalEl = document.getElementById('lineSelectModal');
    const list = document.getElementById('lineList');

    if (!modalEl || !list) return;

    await fetchLoginCsrfToken();

    list.textContent = '';
    lines.forEach(line => {
        const li = document.createElement('li');
        li.dataset.id = line.id;
        li.textContent = `${line.ma_line} - ${line.ten_line}`;
        list.appendChild(li);
    });

    list.querySelectorAll('li').forEach(li => {
        li.addEventListener('click', async () => {
            const lineId = li.dataset.id;
            try {
                const headers = { 'Content-Type': 'application/json' };
                if (loginCsrfToken) {
                    headers['X-CSRF-Token'] = loginCsrfToken;
                }
                const response = await fetch(API_BASE + '/auth/select-line', {
                    method: 'POST',
                    headers: headers,
                    credentials: 'include',
                    body: JSON.stringify({ line_id: parseInt(lineId) })
                });
                const result = await response.json();
                if (result.success) {
                    window.location.href = result.redirect_url || 'nhap-nang-suat.php';
                } else {
                    notifyError(result.message);
                }
            } catch (error) {
                notifyError('Lỗi chọn LINE');
            }
        });
    });

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const dataEl = document.getElementById('allowedLines');
    if (!dataEl) return;
    let lines = [];
    try {
        lines = JSON.parse(dataEl.textContent);
    } catch (error) {
        console.error('Invalid allowedLines data:', error);
    }
    if (lines.length > 0) {
        showLineSelect(lines);
    }
});
