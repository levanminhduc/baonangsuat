<?php
require_once __DIR__ . '/includes/security-headers.php';
require_once __DIR__ . '/classes/Auth.php';

if (!Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if (Auth::checkRole(['admin'])) {
    header('Location: admin.php');
    exit;
}

if (Auth::hasLine() || Auth::canCreateReportForAnyLine()) {
    header('Location: nhap-nang-suat.php');
    exit;
}

$session = Auth::getSession();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chưa được phân LINE - Hệ thống Nhập Năng Suất</title>
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
        <div class="login-box text-center">
            <div class="mb-6">
                <svg class="w-16 h-16 mx-auto text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            
            <h2 class="text-xl font-bold text-gray-800 mb-4">Chưa được phân LINE</h2>
            
            <p class="text-gray-600 mb-6">
                Xin chào <strong><?php echo htmlspecialchars($session['ho_ten'] ?? $session['ma_nv']); ?></strong>,<br>
                Tài khoản của bạn chưa được phân công LINE làm việc.<br>
                Vui lòng liên hệ quản trị viên để được hỗ trợ.
            </p>
            
            <button onclick="logout()" class="btn btn-primary">Đăng xuất</button>
        </div>
    </div>
    
    <script>
        function logout() {
            fetch('/baonangsuat/api/auth/logout', { method: 'POST' })
                .then(() => {
                    window.location.href = 'index.php';
                })
                .catch(() => {
                    window.location.href = 'index.php';
                });
        }
    </script>
</body>
</html>
