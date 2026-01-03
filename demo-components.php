<?php
require_once __DIR__ . '/includes/security-headers.php';

// Mock Auth class if needed for Navbar, or just let it handle itself (it checks session)
require_once __DIR__ . '/classes/Auth.php';

$navTitle = 'Demo UI Components';
$showHomeBtn = true;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo UI Components - Báo Năng Suất</title>
    
    <!-- Dependencies from index.php -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Project Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Tailwind CSS Offline -->
    <link rel="stylesheet" href="assets/tailwind/dist/main.css">

    <style>
        .component-section {
            margin-bottom: 3rem;
            padding: 2rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: #f9fafb;
        }
        .component-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
            color: #111827;
        }
        .example-block {
            background: white;
            padding: 1.5rem;
            border-radius: 0.375rem;
            border: 1px solid #e5e7eb;
            margin-bottom: 1rem;
        }
        .example-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            font-family: monospace;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        
        <!-- 1. Status Badges -->
        <div class="component-section">
            <h2 class="component-title">Status Badge</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="example-block">
                    <div class="example-label">Success / Approved</div>
                    <?php 
                    $status = 'success'; $label = 'Hoàn thành';
                    include 'includes/components/status-badge.php'; 
                    unset($status, $label, $class, $size);
                    ?>
                </div>
                <div class="example-block">
                    <div class="example-label">Warning / Pending</div>
                    <?php 
                    $status = 'warning'; $label = 'Đang chờ';
                    include 'includes/components/status-badge.php'; 
                    unset($status, $label, $class, $size);
                    ?>
                </div>
                <div class="example-block">
                    <div class="example-label">Danger / Rejected</div>
                    <?php 
                    $status = 'danger'; $label = 'Từ chối';
                    include 'includes/components/status-badge.php'; 
                    unset($status, $label, $class, $size);
                    ?>
                </div>
                <div class="example-block">
                    <div class="example-label">Info</div>
                    <?php 
                    $status = 'info'; $label = 'Thông tin';
                    include 'includes/components/status-badge.php'; 
                    unset($status, $label, $class, $size);
                    ?>
                </div>
                <div class="example-block">
                    <div class="example-label">Small Size</div>
                    <?php 
                    $status = 'primary'; $label = 'Primary Small'; $size = 'sm';
                    include 'includes/components/status-badge.php'; 
                    unset($status, $label, $class, $size);
                    ?>
                </div>
            </div>
        </div>

        <!-- 2. Buttons -->
        <div class="component-section">
            <h2 class="component-title">Button</h2>
            <div class="flex flex-wrap gap-4 mb-4">
                <?php 
                $label = 'Primary Button'; $variant = 'primary';
                include 'includes/components/button.php'; 
                unset($label, $variant, $class, $size, $type, $disabled, $id, $onClick);
                ?>

                <?php 
                $label = 'Success'; $variant = 'success';
                include 'includes/components/button.php'; 
                unset($label, $variant, $class, $size, $type, $disabled, $id, $onClick);
                ?>

                <?php 
                $label = 'Warning'; $variant = 'warning';
                include 'includes/components/button.php'; 
                unset($label, $variant, $class, $size, $type, $disabled, $id, $onClick);
                ?>

                <?php 
                $label = 'Danger'; $variant = 'danger';
                include 'includes/components/button.php'; 
                unset($label, $variant, $class, $size, $type, $disabled, $id, $onClick);
                ?>

                <?php 
                $label = 'Secondary'; $variant = 'secondary';
                include 'includes/components/button.php'; 
                unset($label, $variant, $class, $size, $type, $disabled, $id, $onClick);
                ?>
            </div>
            <div class="flex flex-wrap gap-4 items-center">
                <?php 
                $label = 'Small Button'; $size = 'sm';
                include 'includes/components/button.php'; 
                unset($label, $variant, $class, $size, $type, $disabled, $id, $onClick);
                ?>

                <?php 
                $label = 'Large Button'; $size = 'lg';
                include 'includes/components/button.php'; 
                unset($label, $variant, $class, $size, $type, $disabled, $id, $onClick);
                ?>

                <?php 
                $label = 'Disabled'; $disabled = true;
                include 'includes/components/button.php'; 
                unset($label, $variant, $class, $size, $type, $disabled, $id, $onClick);
                ?>
            </div>
        </div>

        <!-- 3. Alerts -->
        <div class="component-section">
            <h2 class="component-title">Alert</h2>
            <div class="space-y-4">
                <?php 
                $type = 'info'; $message = 'Đây là thông báo thông tin cơ bản.';
                include 'includes/components/alert.php'; 
                unset($type, $message, $dismissible, $id, $class);
                ?>

                <?php 
                $type = 'success'; $message = 'Thao tác thành công! Dữ liệu đã được lưu.';
                include 'includes/components/alert.php'; 
                unset($type, $message, $dismissible, $id, $class);
                ?>

                <?php 
                $type = 'warning'; $message = 'Cảnh báo: Hành động này không thể hoàn tác.';
                include 'includes/components/alert.php'; 
                unset($type, $message, $dismissible, $id, $class);
                ?>

                <?php 
                $type = 'error'; $message = 'Lỗi: Không thể kết nối đến máy chủ.'; $dismissible = true;
                include 'includes/components/alert.php'; 
                unset($type, $message, $dismissible, $id, $class);
                ?>
            </div>
        </div>

        <!-- 4. Form Elements -->
        <div class="component-section">
            <h2 class="component-title">Form Input & Select</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Inputs -->
                <div class="space-y-2">
                    <h3 class="font-medium mb-3">Inputs</h3>
                    <?php 
                    $label = 'Tên đăng nhập'; $name = 'demo_username'; $placeholder = 'Nhập tên đăng nhập'; $required = true;
                    include 'includes/components/form-input.php'; 
                    unset($label, $name, $placeholder, $required, $type, $id, $value, $disabled, $readonly, $error, $helperText, $class, $step, $min, $max);
                    ?>

                    <?php 
                    $label = 'Mật khẩu'; $type = 'password'; $name = 'demo_password'; $helperText = 'Mật khẩu phải có ít nhất 6 ký tự';
                    include 'includes/components/form-input.php'; 
                    unset($label, $name, $placeholder, $required, $type, $id, $value, $disabled, $readonly, $error, $helperText, $class, $step, $min, $max);
                    ?>

                    <?php 
                    $label = 'Số lượng (Lỗi)'; $type = 'number'; $name = 'demo_number'; $value = '-1'; $error = 'Số lượng phải lớn hơn 0';
                    include 'includes/components/form-input.php'; 
                    unset($label, $name, $placeholder, $required, $type, $id, $value, $disabled, $readonly, $error, $helperText, $class, $step, $min, $max);
                    ?>
                </div>

                <!-- Selects -->
                <div class="space-y-2">
                    <h3 class="font-medium mb-3">Selects</h3>
                    <?php 
                    $label = 'Chọn phòng ban'; $name = 'demo_dept'; 
                    $options = ['it' => 'Công nghệ thông tin', 'hr' => 'Nhân sự', 'acc' => 'Kế toán'];
                    include 'includes/components/form-select.php'; 
                    unset($label, $name, $options, $placeholder, $required, $disabled, $error, $helperText, $class, $multiple, $id, $value);
                    ?>

                    <?php 
                    $label = 'Chọn nhiều kỹ năng'; $name = 'demo_skills'; $multiple = true;
                    $options = ['php' => 'PHP', 'js' => 'JavaScript', 'sql' => 'SQL', 'html' => 'HTML'];
                    $value = ['php', 'sql'];
                    include 'includes/components/form-select.php'; 
                    unset($label, $name, $options, $placeholder, $required, $disabled, $error, $helperText, $class, $multiple, $id, $value);
                    ?>
                    
                    <?php 
                    $label = 'Vô hiệu hóa'; $name = 'demo_disabled'; $disabled = true; $placeholder = 'Không thể chọn';
                    include 'includes/components/form-select.php'; 
                    unset($label, $name, $options, $placeholder, $required, $disabled, $error, $helperText, $class, $multiple, $id, $value);
                    ?>
                </div>
            </div>
        </div>

        <!-- 5. Cards -->
        <div class="component-section">
            <h2 class="component-title">Card</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php 
                $header = 'Card Title (Default Shadow)';
                $body = '<p class="text-gray-600">Nội dung của thẻ card mặc định. Có bóng đổ nhẹ và bo góc.</p>';
                $footer = '<button class="text-sm text-blue-600 hover:underline">Xem chi tiết</button>';
                include 'includes/components/card.php'; 
                unset($header, $body, $footer, $variant, $class, $id, $noPadding);
                ?>

                <?php 
                $header = 'Bordered Card';
                $variant = 'bordered';
                $body = '<p class="text-gray-600">Thẻ card có viền, không có bóng đổ. Thích hợp cho layout phẳng.</p>';
                include 'includes/components/card.php'; 
                unset($header, $body, $footer, $variant, $class, $id, $noPadding);
                ?>
            </div>
        </div>

        <!-- 6. Tabs -->
        <div class="component-section">
            <h2 class="component-title">Tabs</h2>
            <?php 
            $tabs = [
                [
                    'id' => 'tab-info',
                    'label' => 'Thông tin chung',
                    'content' => '<div class="p-4 bg-gray-50 rounded">Nội dung tab Thông tin chung. Có thể chứa bất kỳ HTML nào.</div>',
                    'active' => true
                ],
                [
                    'id' => 'tab-history',
                    'label' => 'Lịch sử hoạt động',
                    'content' => '<div class="p-4 bg-gray-50 rounded">Nội dung tab Lịch sử. Danh sách các hoạt động gần đây...</div>'
                ],
                [
                    'id' => 'tab-settings',
                    'label' => 'Cài đặt',
                    'content' => '<div class="p-4 bg-gray-50 rounded">Nội dung tab Cài đặt. Form cấu hình...</div>'
                ]
            ];
            include 'includes/components/tabs.php';
            unset($tabs, $id, $class);
            ?>
        </div>

        <!-- 7. Line Preset Table -->
        <div class="component-section">
            <h2 class="component-title">Line Preset Table</h2>
            <p class="mb-4 text-sm text-gray-500">Bảng quản lý danh sách LINE (Mock data)</p>
            <?php 
            $assignedLines = [
                ['line_id' => 1, 'ten_line' => 'Chuyền 01', 'ma_line' => 'LINE01'],
                ['line_id' => 2, 'ten_line' => 'Chuyền 02', 'ma_line' => 'LINE02'],
                ['line_id' => 3, 'ten_line' => 'Chuyền May 3', 'ma_line' => 'LINE03']
            ];
            $unassignedLines = [
                ['id' => 4, 'ten_line' => 'Chuyền 04', 'ma_line' => 'LINE04'],
                ['id' => 5, 'ten_line' => 'Chuyền 05', 'ma_line' => 'LINE05']
            ];
            $readOnly = false;
            include 'includes/components/line-preset-table.php';
            unset($assignedLines, $unassignedLines, $readOnly);
            ?>
        </div>

        <!-- 8. Interactive Elements -->
        <div class="component-section">
            <h2 class="component-title">Interactive Elements</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Toast Demo -->
                <div class="example-block">
                    <h3 class="font-medium mb-3">Toasts</h3>
                    <div class="flex flex-col gap-2">
                        <button onclick="window.toast.success('Thao tác thành công!', 3000)" class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Show Success</button>
                        <button onclick="window.toast.error('Đã xảy ra lỗi!', 3000)" class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">Show Error</button>
                        <button onclick="window.toast.warning('Cảnh báo hệ thống', 3000)" class="bg-orange-500 text-white px-3 py-2 rounded text-sm hover:bg-orange-600">Show Warning</button>
                        <button onclick="window.toast.info('Thông tin mới cập nhật', 3000)" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Show Info</button>
                        
                        <div class="h-px bg-gray-200 my-2"></div>
                        <p class="text-xs text-gray-500 mb-1">Stacking Demo:</p>
                        <button onclick="spamToast()" class="bg-indigo-600 text-white px-3 py-2 rounded text-sm hover:bg-indigo-700">
                            <i class="fas fa-layer-group mr-1"></i> Spam 5x Success
                        </button>
                    </div>
                </div>

                <!-- Modal Demo -->
                <div class="example-block">
                    <h3 class="font-medium mb-3">Modal Component</h3>
                    <div class="space-y-3">
                        <button onclick="Modal.open('demoModal')" class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            Open Base Modal
                        </button>
                        <button onclick="showDemoModal()" class="w-full bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                            Open Custom Confirm Modal
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Base Modal uses modal-base.php + JS Module</p>
                </div>

                <!-- Loading Overlay Demo -->
                <div class="example-block">
                    <h3 class="font-medium mb-3">Loading Overlay</h3>
                    <button onclick="showDemoLoading()" class="w-full bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900">
                        Show Overlay (3s)
                    </button>
                    <p class="text-xs text-gray-500 mt-2">Overlay sẽ tự tắt sau 3 giây</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Interactive Components Includes -->
    <?php include 'includes/components/toast.php'; ?>
    <?php include 'includes/components/confirm-modal.php'; ?>
    <?php include 'includes/components/loading-overlay.php'; ?>
    <?php include 'includes/components/demo-modal.php'; ?>

    <!-- JS Modules -->
    <script type="module">
        import Modal from './assets/js/modules/modal.js';
        
        // Expose to window for inline onclick handlers
        window.Modal = Modal;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            Modal.init();
        });
    </script>

    <!-- Demo Scripts -->
    <script>
        // Toast Stacking Demo
        function spamToast() {
            let count = 0;
            const interval = setInterval(() => {
                window.toast.success('Thao tác lặp lại thành công!', 3000);
                count++;
                if (count >= 5) clearInterval(interval);
            }, 200);
        }

        // Modal Demo Logic
        function showDemoModal() {
            const modal = document.getElementById('confirmModal');
            const title = document.getElementById('confirmModalTitle');
            const message = document.getElementById('confirmMessage');
            const confirmBtn = document.getElementById('confirmBtn');

            if (modal && title && message && confirmBtn) {
                title.textContent = 'Xác nhận Demo';
                message.textContent = 'Bạn có chắc chắn muốn thực hiện hành động này trong trang Demo không?';
                
                // Show modal
                modal.classList.remove('hidden');
                
                // Handle confirm click
                confirmBtn.onclick = function() {
                    closeModal('confirmModal');
                    window.toast.success('Đã xác nhận thành công!');
                };
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Loading Demo Logic
        function showDemoLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                // Show
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.remove('opacity-0'), 10);
                
                // Hide after 3s
                setTimeout(() => {
                    overlay.classList.add('opacity-0');
                    setTimeout(() => overlay.classList.add('hidden'), 300);
                }, 3000);
            }
        }

        // Helper for Line Table Demo (Required by the component's script)
        window.onAddLines = function(selectedIds) {
            console.log('Selected Lines:', selectedIds);
            closeAddLineModal();
            window.toast.success(`Đã thêm ${selectedIds.length} LINE vào preset (Demo only)`);
        };

        window.onRemoveLine = function(lineId) {
            console.log('Remove Line:', lineId);
            window.toast.info(`Đã xóa LINE ID ${lineId} (Demo only)`);
        };
    </script>

    <!-- Bootstrap Bundle from index.php -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
