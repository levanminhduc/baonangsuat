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

    // Load saved credentials
    const savedUsername = localStorage.getItem('rememberedUsername');
    // Note: Storing passwords in localStorage is generally not recommended for security, 
    // but following existing pattern from request. Consider using cookies or tokens instead.
    const savedPassword = localStorage.getItem('rememberedPassword');
    if (savedUsername && savedPassword) {
        if (usernameInput) usernameInput.value = savedUsername;
        if (passwordInput) passwordInput.value = savedPassword;
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

// Class to handle login logic, integrating with existing app structure
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

        // Save credentials if remember me is checked
        if (rememberCheckbox && rememberCheckbox.checked) {
            localStorage.setItem('rememberedUsername', username);
            localStorage.setItem('rememberedPassword', password);
        } else {
            localStorage.removeItem('rememberedUsername');
            localStorage.removeItem('rememberedPassword');
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
        } else {
            alert(message);
        }
    }
    
    showLineSelect(lines) {
        // Use Bootstrap Modal
        const modalEl = document.getElementById('lineSelectModal');
        const list = document.getElementById('lineList');
        
        if (!modalEl || !list) return;
        
        list.innerHTML = lines.map(line => 
            `<li data-id="${line.id}">${line.ma_line} - ${line.ten_line}</li>`
        ).join('');
        
        list.querySelectorAll('li').forEach(li => {
            li.addEventListener('click', async () => {
                const lineId = li.dataset.id;
                try {
                    const response = await fetch('/baonangsuat/api/auth/select-line', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ line_id: parseInt(lineId) })
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.location.href = result.redirect_url || 'nhap-nang-suat.php';
                    } else {
                        alert(result.message);
                    }
                } catch (error) {
                    alert('Lỗi chọn LINE');
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
