<?php
require_once __DIR__ . '/classes/Auth.php';

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
    <title>Đăng nhập - Hệ thống Nhập Năng Suất</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: '#2196F3',
              'primary-dark': '#1976D2',
              success: '#4CAF50',
              warning: '#ff9800',
              danger: '#f44336',
            }
          }
        }
      }
    </script>
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
    
    <div id="lineSelectModal" class="line-select-modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm transition-opacity opacity-0 pointer-events-none data-[show=true]:opacity-100 data-[show=true]:pointer-events-auto">
        <div class="line-select-box bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all scale-95 data-[show=true]:scale-100 p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-3">Chọn LINE làm việc</h2>
            <ul id="lineList" class="line-list space-y-2 max-h-[60vh] overflow-y-auto pr-2"></ul>
        </div>
    </div>
    
    <script src="assets/js/app.js"></script>
</body>
</html>
