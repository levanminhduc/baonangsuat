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

if (!Auth::hasLine()) {
    header('Location: no-line.php');
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: '#143583',
              'primary-dark': '#0f2a66',
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
    $navTitle = 'NHẬP NĂNG SUẤT THEO GIỜ';
    $showAddBtn = true;
    $addBtnUrl = '#';
    $addBtnId = 'navCreateReportBtn';
    $showHomeBtn = true;
    include __DIR__ . '/includes/navbar.php';
    ?>
    <div class="app-container">
        <div style="text-align: right; background: transparent; padding: 5px 15px; font-size: 0.85em; color: #666;">
            <span class="user-info" style="font-weight: 500;"><?php echo htmlspecialchars($session['ho_ten']); ?> (<?php echo htmlspecialchars($session['line_ten']); ?>)</span>
            <span style="margin: 0 5px;">|</span>
            <a href="#" id="logoutBtn" style="color: #666; text-decoration: none;">Đăng xuất</a>
        </div>
        
        <div id="reportListContainer" class="report-list">
            <div class="report-list-header">
                <h2>Danh sách báo cáo hôm nay</h2>
                <button id="createReportBtn" class="btn btn-primary">+ Tạo báo cáo mới</button>
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Ngày</th>
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
    
    <div id="createModal" class="modal hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="window.app.closeModal()">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg p-0 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800 m-0">Tạo báo cáo mới</h2>
                <button type="button" class="w-8 h-8 flex items-center justify-center bg-red-500 hover:bg-red-700 rounded text-white transition-colors" onclick="window.app.closeModal()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="createForm" onsubmit="event.preventDefault(); window.app.createReport();" class="p-6 space-y-4">
                <div class="form-group space-y-1">
                    <label for="modalNgay" class="block text-sm font-medium text-gray-700">Ngày báo cáo</label>
                    <input type="date" id="modalNgay" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                </div>
                <div class="form-group" style="display: none;">
                    <label for="modalCa">Ca làm việc</label>
                    <select id="modalCa" required></select>
                </div>
                <div class="form-group space-y-1">
                    <label for="modalMaHang" class="block text-sm font-medium text-gray-700">Mã hàng</label>
                    <select id="modalMaHang" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none bg-white"></select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group space-y-1">
                        <label for="modalLaoDong" class="block text-sm font-medium text-gray-700">Số lao động</label>
                        <input type="number" id="modalLaoDong" value="0" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                    </div>
                    <div class="form-group space-y-1">
                        <label for="modalCtns" class="block text-sm font-medium text-gray-700">Chỉ tiêu năng suất (CTNS)</label>
                        <input type="number" id="modalCtns" value="0" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors outline-none">
                    </div>
                </div>
                <div class="modal-actions flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <button type="button" class="btn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-700 text-white transition-colors font-medium" onclick="window.app.closeModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg bg-primary hover:bg-primary-dark text-white shadow-md hover:shadow-lg transition-all font-medium">Tạo báo cáo</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        window.initialParams = {
            line: "<?php echo isset($_GET['line']) ? htmlspecialchars($_GET['line']) : ''; ?>",
            ma_hang: "<?php echo isset($_GET['ma_hang']) ? htmlspecialchars($_GET['ma_hang']) : ''; ?>",
            ngay: "<?php echo isset($_GET['ngay']) ? htmlspecialchars($_GET['ngay']) : ''; ?>"
        };
    </script>
    <script type="module" src="assets/js/app.js"></script>
</body>
</html>
