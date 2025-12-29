<?php
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['line_id'])) {
    header('Location: nhap-nang-suat.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Nhập Năng Suất</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php
    $navTitle = 'HỆ THỐNG NHẬP NĂNG SUẤT';
    $showAddBtn = false;
    $showHomeBtn = false;
    include __DIR__ . '/includes/navbar.php';
    ?>
    <div class="login-container">
        <div class="login-box">
            
            <div id="loginError" class="alert alert-error hidden"></div>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="username">Mã nhân viên</label>
                    <input type="text" id="username" name="username" required autocomplete="username" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
            </form>
        </div>
    </div>
    
    <div id="lineSelectModal" class="line-select-modal hidden">
        <div class="line-select-box">
            <h2>Chọn LINE làm việc</h2>
            <ul id="lineList" class="line-list"></ul>
        </div>
    </div>
    
    <script src="assets/js/app.js"></script>
</body>
</html>
