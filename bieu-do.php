<?php
require_once __DIR__ . '/includes/security-headers.php';
require_once __DIR__ . '/classes/Auth.php';

// Auth guards - require login
if (!Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Allow access if user has LINE or is admin
if (!Auth::checkRole(['admin']) && !Auth::hasLine()) {
    header('Location: no-line.php');
    exit;
}

$session = Auth::getSession();
$isAdmin = Auth::checkRole(['admin']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biểu đồ năng suất - <?php echo htmlspecialchars($session['line_ten'] ?? 'Admin'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
        @media (max-width: 640px) {
            .chart-container {
                height: 300px;
            }
        }
        .filter-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        .summary-card {
            transition: all 0.2s ease;
        }
        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #143583;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php
    $navTitle = 'BIỂU ĐỒ NĂNG SUẤT';
    $showAddBtn = false;
    $showHomeBtn = true;
    include __DIR__ . '/includes/navbar.php';
    ?>
    
    <div class="app-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Bộ lọc</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- LINE Filter -->
                <div>
                    <label for="filterLine" class="block text-sm font-medium text-gray-700 mb-1">LINE</label>
                    <select id="filterLine" class="filter-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white" <?php echo !$isAdmin ? 'disabled' : ''; ?>>
                        <?php if (!$isAdmin): ?>
                            <option value="<?php echo $session['line_id']; ?>"><?php echo htmlspecialchars($session['line_ten']); ?></option>
                        <?php else: ?>
                            <option value="">-- Chọn LINE --</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Date Filter -->
                <div>
                    <label for="filterDate" class="block text-sm font-medium text-gray-700 mb-1">Ngày</label>
                    <input type="date" id="filterDate" value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                
                <!-- Mã hàng Filter -->
                <div>
                    <label for="filterMaHang" class="block text-sm font-medium text-gray-700 mb-1">Mã hàng</label>
                    <select id="filterMaHang" class="filter-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                        <option value="">-- Chọn mã hàng --</option>
                    </select>
                </div>
                
                <!-- Ca Filter (hidden, auto-select based on ma_hang) -->
                <input type="hidden" id="filterCa" value="">
                
                <!-- Load Button -->
                <div class="flex items-end">
                    <button id="loadChartBtn" class="w-full sm:w-auto px-6 py-2 bg-primary hover:bg-primary-dark text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Xem biểu đồ
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Loading State -->
        <div id="loadingState" class="hidden bg-white rounded-lg shadow-sm p-8 mb-6">
            <div class="flex flex-col items-center justify-center">
                <div class="loading-spinner mb-4"></div>
                <p class="text-gray-600">Đang tải dữ liệu...</p>
            </div>
        </div>
        
        <!-- No Data State -->
        <div id="noDataState" class="hidden bg-white rounded-lg shadow-sm p-8 mb-6">
            <div class="flex flex-col items-center justify-center text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-lg font-medium" id="noDataMessage">Không có dữ liệu cho bộ lọc đã chọn</p>
                <p class="text-sm mt-2">Vui lòng chọn LINE, ngày và mã hàng để xem biểu đồ</p>
            </div>
        </div>
        
        <!-- Chart Section -->
        <div id="chartSection" class="hidden">
            <!-- Report Info Header -->
            <div id="reportInfo" class="bg-white rounded-lg shadow-sm p-4 mb-6">
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">LINE:</span>
                        <span id="infoLine" class="font-semibold text-gray-800">-</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">Ngày:</span>
                        <span id="infoDate" class="font-semibold text-gray-800">-</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">Ca:</span>
                        <span id="infoCa" class="font-semibold text-gray-800">-</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">Mã hàng:</span>
                        <span id="infoMaHang" class="font-semibold text-gray-800">-</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">CTNS:</span>
                        <span id="infoCtns" class="font-semibold text-primary">-</span>
                    </div>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="summary-card bg-white rounded-lg shadow-sm p-4">
                    <p class="text-sm text-gray-500 mb-1">Chỉ tiêu lũy kế</p>
                    <p id="summaryChiTieu" class="text-2xl font-bold text-blue-600">-</p>
                </div>
                <div class="summary-card bg-white rounded-lg shadow-sm p-4">
                    <p class="text-sm text-gray-500 mb-1">Thực tế lũy kế</p>
                    <p id="summaryThucTe" class="text-2xl font-bold text-green-600">-</p>
                </div>
                <div class="summary-card bg-white rounded-lg shadow-sm p-4">
                    <p class="text-sm text-gray-500 mb-1">Chênh lệch</p>
                    <p id="summaryChenhLech" class="text-2xl font-bold">-</p>
                </div>
                <div class="summary-card bg-white rounded-lg shadow-sm p-4">
                    <p class="text-sm text-gray-500 mb-1">Tỷ lệ hoàn thành</p>
                    <p id="summaryTyLe" class="text-2xl font-bold">-</p>
                </div>
            </div>
            
            <!-- View Toggle -->
            <div class="flex justify-center mb-4">
                <div class="inline-flex rounded-lg border border-gray-200 bg-gray-100 p-1">
                    <button id="btnViewTongQuan" class="px-4 py-2 rounded-md bg-primary text-white text-sm font-medium transition-colors">Tổng quan</button>
                    <button id="btnViewCongDoan" class="px-4 py-2 rounded-md text-gray-600 hover:bg-gray-200 text-sm font-medium transition-colors">Theo công đoạn</button>
                </div>
            </div>
            
            <!-- View: Tổng quan (existing chart) -->
            <div id="viewTongQuan">
                <!-- Chart Container -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="chart-container">
                        <canvas id="productivityChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- View: Theo công đoạn (new) -->
            <div id="viewCongDoan" class="hidden">
                <!-- Loading state for công đoạn view -->
                <div id="congDoanLoading" class="hidden bg-white rounded-lg shadow-sm p-8 mb-6">
                    <div class="flex flex-col items-center justify-center">
                        <div class="loading-spinner mb-4"></div>
                        <p class="text-gray-600">Đang tải dữ liệu công đoạn...</p>
                    </div>
                </div>
                
                <!-- Matrix Toggle -->
                <div class="flex items-center justify-end mb-4">
                    <label class="flex items-center cursor-pointer">
                        <span class="mr-2 text-sm font-medium text-gray-700">Chi tiết theo mốc giờ</span>
                        <div class="relative">
                            <input type="checkbox" id="toggleMatrix" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </div>
                    </label>
                </div>
                
                <!-- Summary View (existing table + bar chart) -->
                <div id="viewSummary">
                    <!-- Summary Table -->
                    <div class="overflow-x-auto mb-6 bg-white rounded-lg shadow-sm">
                        <table id="congDoanTable" class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Công đoạn</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Chỉ tiêu</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thực tế</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Chênh lệch</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tỷ lệ (%)</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                    
                    <!-- Bar Chart -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <div class="chart-container">
                            <canvas id="congDoanChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Matrix View (hidden by default) -->
                <div id="viewMatrix" class="hidden">
                    <div id="matrixLoading" class="text-center py-8 hidden">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                        <p class="mt-2 text-gray-600">Đang tải dữ liệu...</p>
                    </div>
                    <div id="matrixNoData" class="text-center py-8 text-gray-500 hidden">
                        Không có dữ liệu
                    </div>
                    <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
                        <table id="matrixTable" class="min-w-full text-sm">
                            <thead id="matrixTableHead" class="bg-gray-50"></thead>
                            <tbody id="matrixTableBody" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                    
                    <!-- Legend -->
                    <div class="flex flex-wrap justify-center mt-4 gap-4 text-sm">
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-4 rounded bg-green-500"></div>
                            <span>≥95% (Đạt)</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-4 rounded bg-yellow-500"></div>
                            <span>80-94% (Cần chú ý)</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-4 rounded bg-red-500"></div>
                            <span>&lt;80% (Chưa đạt)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/components/toast.php'; ?>
    
    <script>
        window.appConfig = {
            isAdmin: <?php echo $isAdmin ? 'true' : 'false'; ?>,
            lineId: <?php echo $session['line_id'] ?? 'null'; ?>,
            lineTen: "<?php echo htmlspecialchars($session['line_ten'] ?? ''); ?>"
        };
    </script>
    <script type="module" src="assets/js/bieu-do.js"></script>
</body>
</html>
