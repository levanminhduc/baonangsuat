<?php
require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../bootstrap/app.php';

use App\Auth\Auth;

Auth::requirePageLogin();

$session = Auth::getSession();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hướng Dẫn Sử Dụng - Hệ thống Năng suất</title>
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
    <style>
        /* Guide specific styles */
        .guide-content h2 { font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem; color: #143583; border-bottom: 2px solid #eee; padding-bottom: 0.5rem; }
        .guide-content h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.75rem; color: #333; }
        .guide-content h4 { font-size: 1.1rem; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; color: #555; }
        .guide-content p { margin-bottom: 1rem; line-height: 1.6; color: #4b5563; }
        .guide-content ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; }
        .guide-content ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; }
        .guide-content li { margin-bottom: 0.5rem; color: #4b5563; }
        .guide-content table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        .guide-content th, .guide-content td { border: 1px solid #e5e7eb; padding: 0.75rem; text-align: left; }
        .guide-content th { background-color: #f9fafb; font-weight: 600; color: #374151; }
        .guide-content code { background-color: #f3f4f6; padding: 0.2rem 0.4rem; rounded: 0.25rem; font-family: monospace; font-size: 0.9em; color: #c7254e; }
        .guide-content pre { background-color: #f8fafc; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin-bottom: 1rem; border: 1px solid #e2e8f0; }
        .step-card { background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .step-number { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; background-color: #143583; color: white; border-radius: 9999px; font-weight: bold; margin-right: 0.75rem; }
        .note-box { background-color: #fffbeb; border-left: 4px solid #fbbf24; padding: 1rem; margin-bottom: 1rem; border-radius: 0.25rem; }
        .warning-box { background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 1rem; margin-bottom: 1rem; border-radius: 0.25rem; }
        .keyboard-key { display: inline-block; padding: 0.25rem 0.5rem; background-color: #f3f4f6; border: 1px solid #d1d5db; border-radius: 0.25rem; font-family: monospace; font-size: 0.875rem; color: #374151; box-shadow: 0 1px 0 rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-50">
    <?php
    $navTitle = 'HƯỚNG DẪN SỬ DỤNG';
    $showAddBtn = false;
    $showHomeBtn = true;
    include __DIR__ . '/../includes/navbar.php';
    ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm min-h-[600px]">
            <?php
            // Tab 1: Chung
            ob_start();
            ?>
            <div class="guide-content p-6">
                <h2>Giới Thiệu Hệ Thống</h2>
                <p>Hệ thống <strong>Báo Năng Suất</strong> là ứng dụng web giúp theo dõi và quản lý năng suất sản xuất của các LINE trong nhà máy, cho phép nhập liệu thời gian thực và theo dõi tiến độ.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="step-card">
                        <h3 class="!mt-0">Chức năng chính</h3>
                        <ul class="!mb-0">
                            <li>✅ Nhập số liệu năng suất theo từng mốc giờ</li>
                            <li>✅ Theo dõi tiến độ sản xuất Real-time</li>
                            <li>✅ Xem lịch sử báo cáo các ngày trước</li>
                            <li>✅ Quản lý dữ liệu sản xuất (Admin)</li>
                        </ul>
                    </div>
                    <div class="step-card">
                        <h3 class="!mt-0">Thuật ngữ quan trọng</h3>
                        <table class="!mb-0 text-sm">
                            <tr><th width="30%">LINE</th><td>Đơn vị sản xuất (dây chuyền may)</td></tr>
                            <tr><th>Routing</th><td>Quy trình = Mã hàng + Các công đoạn</td></tr>
                            <tr><th>Mốc giờ</th><td>Thời điểm nhập số liệu (VD: 7:30, 8:30)</td></tr>
                            <tr><th>Preset</th><td>Bộ cài đặt sẵn các mốc giờ cho ca</td></tr>
                        </table>
                    </div>
                </div>

                <h2>Hướng Dẫn Đăng Nhập</h2>
                <div class="step-card">
                    <ol class="space-y-4">
                        <li class="flex items-start">
                            <span class="step-number">1</span>
                            <div>
                                <strong>Truy cập:</strong> Mở trình duyệt hoặc ứng dụng LINE, vào địa chỉ hệ thống.
                            </div>
                        </li>
                        <li class="flex items-start">
                            <span class="step-number">2</span>
                            <div>
                                <strong>Đăng nhập:</strong> Nhập <code>Mã nhân viên</code> và <code>Mật khẩu</code>. 
                                <br><em class="text-sm text-gray-500">(Ghi nhớ đăng nhập để lần sau không cần nhập lại mã NV)</em>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <span class="step-number">3</span>
                            <div>
                                <strong>Chọn LINE:</strong> Nếu bạn phụ trách nhiều LINE, hệ thống sẽ yêu cầu chọn LINE làm việc.
                            </div>
                        </li>
                    </ol>
                </div>

                <h2>Bảng Phân Quyền</h2>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Chức năng</th>
                                <th class="text-center">Admin</th>
                                <th class="text-center">Quản Đốc</th>
                                <th class="text-center">Tổ Trưởng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="bg-gray-100 font-semibold">QUẢN TRỊ HỆ THỐNG</td>
                            </tr>
                            <tr>
                                <td>Quản lý LINE, Mã hàng, Routing...</td>
                                <td class="text-center text-green-600">✅</td>
                                <td class="text-center text-gray-400">❌</td>
                                <td class="text-center text-gray-400">❌</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="bg-gray-100 font-semibold">BÁO CÁO NĂNG SUẤT</td>
                            </tr>
                            <tr>
                                <td>Tạo báo cáo & Nhập liệu</td>
                                <td class="text-center text-green-600">✅</td>
                                <td class="text-center">⭕ (Theo quyền)</td>
                                <td class="text-center">⭕ (Theo quyền)</td>
                            </tr>
                            <tr>
                                <td>Mở khóa báo cáo đã chốt</td>
                                <td class="text-center text-green-600">✅</td>
                                <td class="text-center text-gray-400">❌</td>
                                <td class="text-center text-gray-400">❌</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="bg-gray-100 font-semibold">XEM & DUYỆT</td>
                            </tr>
                            <tr>
                                <td>Xem lịch sử</td>
                                <td class="text-center text-green-600">✅</td>
                                <td class="text-center">⭕ (Cần quyền xem)</td>
                                <td class="text-center">⭕ (Cần quyền xem)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
            $contentChung = ob_get_clean();

            // Tab 2: Admin
            ob_start();
            ?>
            <div class="guide-content p-6">
                <div class="warning-box">
                    <strong>⚠️ QUAN TRỌNG:</strong> Admin cần tuân thủ đúng thứ tự setup dưới đây để hệ thống hoạt động chính xác.
                </div>

                <h2>Quy Trình Setup Hệ Thống</h2>
                <div class="flex flex-col md:flex-row justify-between items-center gap-2 mb-8 text-sm">
                    <div class="bg-blue-50 border border-blue-200 p-3 rounded text-center flex-1 w-full">1. Tạo LINE</div>
                    <div class="text-gray-400">→</div>
                    <div class="bg-blue-50 border border-blue-200 p-3 rounded text-center flex-1 w-full">2. Mã hàng</div>
                    <div class="text-gray-400">→</div>
                    <div class="bg-blue-50 border border-blue-200 p-3 rounded text-center flex-1 w-full">3. Công đoạn</div>
                    <div class="text-gray-400">→</div>
                    <div class="bg-blue-50 border border-blue-200 p-3 rounded text-center flex-1 w-full font-bold text-blue-800">4. Routing</div>
                    <div class="text-gray-400">→</div>
                    <div class="bg-blue-50 border border-blue-200 p-3 rounded text-center flex-1 w-full">5. Preset</div>
                    <div class="text-gray-400">→</div>
                    <div class="bg-blue-50 border border-blue-200 p-3 rounded text-center flex-1 w-full">6. User-LINE</div>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <div class="step-card">
                        <h3>1. Quản lý LINE & Mã hàng</h3>
                        <p>Khai báo danh sách các dây chuyền sản xuất và danh sách mã sản phẩm.</p>
                        <ul>
                            <li><strong>LINE:</strong> Mã LINE (L01), Tên LINE (Line May 1).</li>
                            <li><strong>Mã hàng:</strong> Mã SP (MH001), Tên SP (Áo sơ mi).</li>
                        </ul>
                    </div>

                    <div class="step-card">
                        <h3>2. Quản lý Công đoạn & Routing</h3>
                        <p>Đây là bước quan trọng nhất. Routing định nghĩa quy trình sản xuất.</p>
                        <ul>
                            <li><strong>Công đoạn:</strong> Tạo các bước như Cắt, May, Đóng gói. Đánh dấu <em>"Là công đoạn thành phẩm"</em> cho bước cuối.</li>
                            <li><strong>Routing:</strong> Kết nối Mã hàng ↔ Công đoạn.
                                <ul class="mt-2 text-sm text-gray-600">
                                    <li>Chọn Mã hàng.</li>
                                    <li>Thêm từng công đoạn theo thứ tự.</li>
                                    <li>Đánh dấu <strong>"Tính lũy kế"</strong> cho công đoạn thành phẩm.</li>
                                </ul>
                            </li>
                        </ul>
                    </div>

                    <div class="step-card">
                        <h3>3. Quản lý Preset Mốc giờ</h3>
                        <p>Thiết lập các khung giờ nhập liệu cho từng ca.</p>
                        <ul>
                            <li>Tạo Preset (VD: Ca Sáng - Chuẩn).</li>
                            <li>Thêm các mốc giờ (7:30, 8:30...) và số phút lũy kế tương ứng.</li>
                            <li>Có thể gán Preset riêng cho từng LINE nếu cần.</li>
                        </ul>
                    </div>

                    <div class="step-card">
                        <h3>4. Gán User & Cấp Quyền</h3>
                        <p>Phân công nhân sự vào vị trí làm việc.</p>
                        <ul>
                            <li><strong>User-LINE:</strong> Gán nhân viên vào LINE cụ thể. Nếu không gán, họ không thể tạo báo cáo.</li>
                            <li><strong>Cấp quyền:</strong>
                                <ul class="mt-2 text-sm text-gray-600">
                                    <li><code>Xem lịch sử</code>: Cho phép xem báo cáo cũ.</li>
                                    <li><code>Tạo báo cáo</code>: Cho phép nhập liệu.</li>
                                    <li><code>Tạo báo cáo (chọn LINE)</code>: Dành cho Quản đốc quản lý nhiều LINE.</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>

                <h3>Tạo Báo Cáo Hàng Loạt</h3>
                <p>Admin có thể tạo sẵn báo cáo cho nhiều LINE cùng lúc vào đầu ngày:</p>
                <ol>
                    <li>Vào tab <strong>Tạo Báo Cáo</strong>.</li>
                    <li>Chọn Ngày, Ca, Mã hàng, CTNS.</li>
                    <li>Chọn danh sách LINE áp dụng.</li>
                    <li>Nhấn <strong>Tạo báo cáo hàng loạt</strong>.</li>
                </ol>
            </div>
            <?php
            $contentAdmin = ob_get_clean();

            // Tab 3: Tổ Trưởng
            ob_start();
            ?>
            <div class="guide-content p-6">
                <h2>Dành Cho Tổ Trưởng</h2>
                <p>Nhiệm vụ: Tạo báo cáo, nhập số liệu theo giờ, chốt báo cáo cuối ca.</p>

                <div class="step-card bg-blue-50 border-blue-200">
                    <h3 class="text-blue-800">Quy trình hàng ngày</h3>
                    <ol class="font-semibold text-blue-900">
                        <li>1. Đăng nhập & Chọn LINE</li>
                        <li>2. Tạo báo cáo mới (nếu chưa có)</li>
                        <li>3. Nhập số liệu thực tế từng giờ</li>
                        <li>4. Chốt báo cáo khi hết ca</li>
                    </ol>
                </div>

                <h3>1. Tạo Báo Cáo Mới</h3>
                <p>Nhấn nút <span class="bg-blue-600 text-white px-2 py-1 rounded text-xs">+ Tạo báo cáo mới</span> và điền:</p>
                <ul>
                    <li><strong>Mã hàng:</strong> Chọn sản phẩm đang chạy.</li>
                    <li><strong>Số lao động:</strong> Số công nhân hiện tại.</li>
                    <li><strong>CTNS:</strong> Chỉ tiêu được giao.</li>
                </ul>

                <h3>2. Nhập Số Liệu</h3>
                <div class="note-box">
                    💡 <strong>Mẹo:</strong> Hệ thống tự động lưu ngay khi bạn nhập xong và chuyển ô.
                </div>
                <p>Giao diện nhập liệu dạng bảng lưới. Bạn chỉ cần click vào ô tương ứng với <strong>Công đoạn</strong> và <strong>Mốc giờ</strong>.</p>
                
                <h4>Phím tắt hỗ trợ:</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="p-3 border rounded text-center">
                        <span class="keyboard-key">Enter</span>
                        <div class="text-xs mt-1 text-gray-500">Xuống dưới</div>
                    </div>
                    <div class="p-3 border rounded text-center">
                        <span class="keyboard-key">Tab</span>
                        <div class="text-xs mt-1 text-gray-500">Sang phải</div>
                    </div>
                    <div class="p-3 border rounded text-center">
                        <span class="keyboard-key">Shift</span> + <span class="keyboard-key">Tab</span>
                        <div class="text-xs mt-1 text-gray-500">Sang trái</div>
                    </div>
                    <div class="p-3 border rounded text-center">
                        <span class="keyboard-key">Mũi tên</span>
                        <div class="text-xs mt-1 text-gray-500">Di chuyển</div>
                    </div>
                </div>

                <h3>3. Chốt Báo Cáo</h3>
                <p>Cuối ca, nhấn nút <strong>[Chốt báo cáo]</strong>.</p>
                <div class="warning-box">
                    ⚠️ <strong>Lưu ý:</strong> Sau khi chốt, bạn KHÔNG THỂ sửa số liệu. Nếu cần sửa, hãy liên hệ Admin để mở khóa.
                </div>
            </div>
            <?php
            $contentToTruong = ob_get_clean();

            // Tab 4: Quản Đốc
            ob_start();
            ?>
            <div class="guide-content p-6">
                <h2>Dành Cho Quản Đốc</h2>
                <p>Nhiệm vụ: Giám sát năng suất nhiều LINE, duyệt báo cáo.</p>

                <h3>1. Xem Báo Cáo Các LINE</h3>
                <p>Nếu bạn được cấp quyền <em>"Tạo báo cáo cho LINE khác"</em>, bạn có thể chuyển đổi giữa các LINE để xem tiến độ.</p>
                <p>Trạng thái báo cáo:</p>
                <ul>
                    <li><span class="inline-block w-3 h-3 bg-gray-200 rounded-full mr-1"></span> <strong>Nháp:</strong> Đang sản xuất/nhập liệu.</li>
                    <li><span class="inline-block w-3 h-3 bg-yellow-400 rounded-full mr-1"></span> <strong>Đã chốt:</strong> Tổ trưởng đã hoàn thành, chờ duyệt.</li>
                    <li><span class="inline-block w-3 h-3 bg-green-500 rounded-full mr-1"></span> <strong>Đã duyệt:</strong> Đã kiểm tra và xác nhận.</li>
                </ul>

                <h3>2. Duyệt Báo Cáo</h3>
                <ol>
                    <li>Mở báo cáo có trạng thái <strong>Đã chốt</strong>.</li>
                    <li>Kiểm tra lại các số liệu tổng và chi tiết.</li>
                    <li>Nhấn nút <strong>[Duyệt báo cáo]</strong> để xác nhận số liệu chính xác.</li>
                </ol>

                <h3>3. Xem Lịch Sử</h3>
                <p>Vào tab <strong>Lịch sử</strong> để tra cứu dữ liệu quá khứ. Bạn có thể lọc theo khoảng thời gian và xem chi tiết từng báo cáo.</p>
            </div>
            <?php
            $contentQuanDoc = ob_get_clean();

            // Tab 5: Sự cố & FAQ
            ob_start();
            ?>
            <div class="guide-content p-6">
                <h2>Xử Lý Sự Cố Thường Gặp</h2>
                
                <div class="space-y-4">
                    <div class="border border-red-100 rounded-lg overflow-hidden">
                        <div class="bg-red-50 px-4 py-2 font-semibold text-red-800">Không thể đăng nhập</div>
                        <div class="p-4 bg-white">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Kiểm tra CapsLock (Mật khẩu phân biệt hoa thường).</li>
                                <li>Tài khoản bị khóa sau 5 lần sai? Đợi 15 phút.</li>
                                <li>Kiểm tra kết nối mạng.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="border border-yellow-100 rounded-lg overflow-hidden">
                        <div class="bg-yellow-50 px-4 py-2 font-semibold text-yellow-800">Không tạo được báo cáo</div>
                        <div class="p-4 bg-white">
                            <ul class="list-disc pl-5 space-y-1">
                                <li><strong>Lỗi "Chưa phân LINE":</strong> Liên hệ Admin gán User vào LINE.</li>
                                <li><strong>Không thấy Mã hàng:</strong> Mã hàng chưa có Routing (Liên hệ Admin).</li>
                                <li><strong>Lỗi "Không có mốc giờ":</strong> Chưa setup Preset cho ca này (Liên hệ Admin).</li>
                            </ul>
                        </div>
                    </div>

                    <div class="border border-blue-100 rounded-lg overflow-hidden">
                        <div class="bg-blue-50 px-4 py-2 font-semibold text-blue-800">Không nhập được số liệu</div>
                        <div class="p-4 bg-white">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Kiểm tra báo cáo đã <strong>Chốt</strong> chưa? Nếu đã chốt thì không sửa được.</li>
                                <li>Ô màu xám là ô không cần nhập (theo Routing).</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <h2 class="mt-8">Câu Hỏi Thường Gặp (FAQ)</h2>
                <div class="space-y-4">
                    <div>
                        <h4 class="text-gray-800 font-bold">Q: Tôi đã chốt nhầm, làm sao mở lại?</h4>
                        <p class="text-gray-600 pl-4 border-l-2 border-gray-200">A: Chỉ Admin mới có quyền mở khóa báo cáo đã chốt. Hãy liên hệ bộ phận IT/Admin.</p>
                    </div>
                    <div>
                        <h4 class="text-gray-800 font-bold">Q: Tại sao tôi không thấy tab Lịch sử?</h4>
                        <p class="text-gray-600 pl-4 border-l-2 border-gray-200">A: Bạn cần được cấp quyền <code>can_view_history</code>. Hãy yêu cầu Admin cấp quyền này.</p>
                    </div>
                    <div>
                        <h4 class="text-gray-800 font-bold">Q: Số liệu có tự lưu không?</h4>
                        <p class="text-gray-600 pl-4 border-l-2 border-gray-200">A: Có. Hệ thống lưu tự động ngay lập tức sau mỗi lần bạn nhập số.</p>
                    </div>
                </div>

                <div class="mt-8 p-6 bg-gray-100 rounded-lg text-center">
                    <h3 class="mt-0">Cần hỗ trợ thêm?</h3>
                    <p>Vui lòng liên hệ bộ phận kỹ thuật:</p>
                    <div class="font-bold text-lg text-primary">📧 support@hoatho.com</div>
                </div>
            </div>
            <?php
            $contentFAQ = ob_get_clean();

            // Define Tabs
            $tabs = [
                ['id' => 'tab-chung', 'label' => 'Tổng Quan & Đăng Nhập', 'content' => $contentChung, 'active' => true],
                ['id' => 'tab-to-truong', 'label' => 'Cho Tổ Trưởng', 'content' => $contentToTruong],
                ['id' => 'tab-quan-doc', 'label' => 'Cho Quản Đốc', 'content' => $contentQuanDoc],
                ['id' => 'tab-admin', 'label' => 'Cho Admin', 'content' => $contentAdmin], // Admin sau cùng vì ít người dùng hơn
                ['id' => 'tab-faq', 'label' => 'Sự cố & FAQ', 'content' => $contentFAQ],
            ];

            // Use the tabs component
            include __DIR__ . '/../includes/components/tabs.php';
            ?>
        </div>
    </div>
</body>
</html>
