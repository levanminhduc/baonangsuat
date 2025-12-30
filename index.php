<?php
require_once __DIR__ . '/includes/security-headers.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/csrf.php';

if (Auth::isLoggedIn()) {
    header('Location: ' . Auth::getDefaultPage());
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Báo Năng Suất</title>
    
    <!-- Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <div class="login-header text-center">
                <div class="hoatho-logo">
                    <img src="img/logoht.svg" alt="Hoa Tho Logo" width="100" height="100">
                </div>
                <h3 class="login-title">HỆ THỐNG BÁO NĂNG SUẤT</h3>
            </div>

            <div class="login-body">
                <div id="loginAlert" class="alert alert-danger d-none" role="alert"></div>

                <form method="post" action="" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                    <div class="form-group mb-3">
                        <label for="username"><i class="fas fa-user me-2"></i>Mã nhân viên</label>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="username" autofocus>
                    </div>

                    <div class="form-group mb-3">
                        <label for="password"><i class="fas fa-lock me-2"></i>Mật khẩu</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="login-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Ghi nhớ đăng nhập</label>
                        </div>
                        <div class="forgot-password">
                            <a href="#">Quên mật khẩu?</a>
                        </div>
                    </div>

                    <div class="login-button">
                        <button type="submit" id="loginBtn" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                        </button>
                    </div>
                </form>
            </div>

            <footer class="login-footer text-center">
                <p>© 2025 - HỆ THỐNG BÁO NĂNG SUẤT - CÔNG TY MAY HOÀ THỌ ĐIỆN BÀN</p>
            </footer>
        </div>
    </div>

    <!-- Line Selection Modal -->
    <div class="modal fade" id="lineSelectModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chọn LINE làm việc</h5>
                </div>
                <div class="modal-body p-0">
                    <ul id="lineList" class="line-list"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>
