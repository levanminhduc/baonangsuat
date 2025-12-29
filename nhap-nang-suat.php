<?php
session_start();
require_once __DIR__ . '/classes/Auth.php';

if (!Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if (!Auth::hasLine()) {
    header('Location: index.php');
    exit;
}

$session = Auth::getSession();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập Năng Suất - <?php echo htmlspecialchars($session['line_ten']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php
    $navTitle = 'NHẬP NĂNG SUẤT THEO GIỜ';
    $showAddBtn = true;
    $addBtnUrl = '#';
    $addBtnId = 'navCreateReportBtn';
    $showHomeBtn = true;
    include __DIR__ . '/includes/navbar.php';
    ?>
    <div class="app-container">
        <header class="header">
            <div class="header-right">
                <span class="user-info"><?php echo htmlspecialchars($session['ho_ten']); ?> | <?php echo htmlspecialchars($session['line_ten']); ?></span>
                <button id="logoutBtn" class="btn btn-logout">Đăng xuất</button>
            </div>
        </header>
        
        <div id="reportListContainer" class="report-list">
            <div class="report-list-header">
                <h2>Danh sách báo cáo hôm nay</h2>
                <button id="createReportBtn" class="btn btn-primary">+ Tạo báo cáo mới</button>
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Ca</th>
                        <th>Mã hàng</th>
                        <th>Lao động</th>
                        <th>CTNS</th>
                        <th>CT/Giờ</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        
        <div id="editorContainer" class="hidden">
            <div class="report-header"></div>
            <div id="gridContainer" class="grid-container"></div>
        </div>
        
        <footer class="status-bar">
            <div class="status-message">
                <span class="status-indicator"></span>
                <span>Sẵn sàng</span>
            </div>
            <div class="status-info">
                Phím tắt: Enter/Tab (di chuyển), Ctrl+S (lưu)
            </div>
        </footer>
    </div>
    
    <div id="createModal" class="modal hidden">
        <div class="modal-content">
            <h2>Tạo báo cáo mới</h2>
            <form id="createForm" onsubmit="event.preventDefault(); window.app.createReport();">
                <div class="form-group">
                    <label for="modalNgay">Ngày báo cáo</label>
                    <input type="date" id="modalNgay" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="modalCa">Ca làm việc</label>
                    <select id="modalCa" required></select>
                </div>
                <div class="form-group">
                    <label for="modalMaHang">Mã hàng</label>
                    <select id="modalMaHang" required></select>
                </div>
                <div class="form-group">
                    <label for="modalLaoDong">Số lao động</label>
                    <input type="number" id="modalLaoDong" value="0" min="0">
                </div>
                <div class="form-group">
                    <label for="modalCtns">Chỉ tiêu năng suất (CTNS)</label>
                    <input type="number" id="modalCtns" value="0" min="0">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="window.app.closeModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo báo cáo</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/app.js"></script>
</body>
</html>
