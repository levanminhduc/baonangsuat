document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const rememberCheckbox = document.getElementById('remember');
    const usernameInput = document.getElementById('username');
    const alertBox = document.querySelector('.alert-danger');

    // Toggle password visibility
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    const savedUsername = localStorage.getItem('rememberedUsername');
    if (savedUsername) {
        if (usernameInput) usernameInput.value = savedUsername;
        if (rememberCheckbox) rememberCheckbox.checked = true;
    }

    // Handle alert box fade out
    if (alertBox && alertBox.textContent.trim() !== '') {
        setTimeout(function() {
            alertBox.style.transition = 'opacity 0.5s';
            alertBox.style.opacity = '0';
            setTimeout(function() {
                alertBox.style.display = 'none';
            }, 500);
        }, 5000);
    }
});

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

class LoginApp {
    constructor() {
        this.bindEvents();
    }
    
    bindEvents() {
        const form = document.getElementById('loginForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleLogin(e));
        }
    }
    
    async handleLogin(e) {
        e.preventDefault();
        
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const rememberCheckbox = document.getElementById('remember');
        const loginBtn = document.getElementById('loginBtn');
        const alertBox = document.getElementById('loginAlert');
        
        const username = usernameInput.value.trim();
        const password = passwordInput.value;
        
        if (!username || !password) {
            this.showError('Vui lòng nhập đầy đủ thông tin');
            return;
        }

        if (rememberCheckbox && rememberCheckbox.checked) {
            localStorage.setItem('rememberedUsername', username);
        } else {
            localStorage.removeItem('rememberedUsername');
        }
        
        // Show loading state
        const originalBtnText = loginBtn.innerHTML;
        loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
        loginBtn.disabled = true;
        if (alertBox) alertBox.classList.add('d-none');
        
        try {
            const response = await fetch('/baonangsuat/api/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.need_select_line) {
                    this.showLineSelect(result.lines);
                } else if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                } else {
                    window.location.href = 'index.php';
                }
            } else {
                this.showError(result.message);
                loginBtn.innerHTML = originalBtnText;
                loginBtn.disabled = false;
            }
        } catch (error) {
            console.error(error);
            this.showError('Lỗi kết nối server');
            loginBtn.innerHTML = originalBtnText;
            loginBtn.disabled = false;
        }
    }
    
    showError(message) {
        const alertBox = document.getElementById('loginAlert');
        if (alertBox) {
            alertBox.textContent = message;
            alertBox.classList.remove('d-none');
            alertBox.classList.add('d-block');
        } else if (window.toast && typeof window.toast.show === 'function') {
            window.toast.error(message);
        } else {
            alert(message);
        }
    }
    
    async showLineSelect(lines) {
        const modalEl = document.getElementById('lineSelectModal');
        const list = document.getElementById('lineList');
        
        if (!modalEl || !list) return;

        await fetchLoginCsrfToken();
        
        list.innerHTML = lines.map(line =>
            `<li data-id="${line.id}">${line.ma_line} - ${line.ten_line}</li>`
        ).join('');
        
        list.querySelectorAll('li').forEach(li => {
            li.addEventListener('click', async () => {
                const lineId = li.dataset.id;
                try {
                    const headers = { 'Content-Type': 'application/json' };
                    if (loginCsrfToken) {
                        headers['X-CSRF-Token'] = loginCsrfToken;
                    }
                    const response = await fetch('/baonangsuat/api/auth/select-line', {
                        method: 'POST',
                        headers: headers,
                        credentials: 'include',
                        body: JSON.stringify({ line_id: parseInt(lineId) })
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.location.href = result.redirect_url || 'nhap-nang-suat.php';
                    } else {
                        if (window.toast && typeof window.toast.error === 'function') {
                            window.toast.error(result.message);
                        } else {
                            alert(result.message);
                        }
                    }
                } catch (error) {
                    if (window.toast && typeof window.toast.error === 'function') {
                        window.toast.error('Lỗi chọn LINE');
                    } else {
                        alert('Lỗi chọn LINE');
                    }
                }
            });
        });
        
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

// Initialize LoginApp if login form exists
if (document.getElementById('loginForm')) {
    new LoginApp();
}
