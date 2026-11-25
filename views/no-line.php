<?php use App\Auth\Auth; ?>
<?php
$page = [
    'title' => 'Chưa được phân LINE - Hệ thống Nhập Năng Suất',
    'bodyClass' => 'theme-no-line',
    'navTitle' => 'Hệ thống nhập năng suất',
    'navActive' => '',
    'showAddBtn' => false,
    'showHomeBtn' => false,
];
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
            
            <a href="<?= htmlspecialchars(Auth::logoutUrl()) ?>" class="btn btn-primary">Đăng xuất</a>
        </div>
    </div>
