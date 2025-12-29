# Hệ Thống Nhập Năng Suất (Excel-like)

## 1. Giới thiệu dự án
Hệ thống nhập năng suất là một ứng dụng web cho phép nhập liệu và theo dõi năng suất sản xuất theo giờ và công đoạn. Giao diện được thiết kế dạng bảng tính (Excel-like) giúp người dùng nhập liệu nhanh chóng, hỗ trợ tự động lưu và tính toán lũy kế.

## 2. Yêu cầu hệ thống
*   **PHP**: 7.4 trở lên
*   **MySQL**: 5.7 trở lên (hoặc MariaDB tương đương)
*   **Web Server**: Apache (có hỗ trợ mod_rewrite)
*   **Browser**: Chrome, Firefox, Edge (phiên bản mới nhất)

## 3. Cài đặt

### Bước 1: Chuẩn bị Database
1.  Tạo database tên `nang_suat` (nếu chưa có).
2.  Import cấu trúc và dữ liệu mẫu từ file `database/schema.sql` vào database `nang_suat`.

### Bước 2: Cấu hình kết nối
Hệ thống sử dụng file cấu hình chung tại đường dẫn `C:/xampp/config/db.php`.
Tạo file này nếu chưa tồn tại với nội dung sau:

```php
<?php
return [
    'host' => 'localhost',
    'username' => 'root',        // User database của bạn
    'password' => '',            // Password database của bạn
    'database' => 'mysqli',      // Database chung (nếu có)
    'database_nang_suat' => 'nang_suat',
    'database_nhan_su' => 'quan_ly_nhan_su'
];
```

### Bước 3: Cấu hình Web Server
Đảm bảo thư mục dự án `baonangsuat` nằm trong thư mục gốc của Web Server (ví dụ: `C:/xampp/htdocs/baonangsuat`).

Truy cập hệ thống qua địa chỉ: `http://localhost/baonangsuat/`

## 4. Cấu trúc Database
Hệ thống bao gồm 11 bảng chính:

1.  **line**: Danh sách các chuyền sản xuất.
2.  **phong_ban_line**: Liên kết phòng ban và chuyền.
3.  **user_line**: Phân quyền nhân viên quản lý chuyền nào.
4.  **ma_hang**: Danh mục mã hàng.
5.  **cong_doan**: Danh mục các công đoạn sản xuất (Cắt, May, Là, ...).
6.  **ma_hang_cong_doan**: Định nghĩa quy trình (routing) cho từng mã hàng.
7.  **ca_lam**: Định nghĩa các ca làm việc (Sáng, Chiều, HC).
8.  **moc_gio**: Các mốc giờ nhập liệu trong từng ca.
9.  **bao_cao_nang_suat**: Bảng header lưu thông tin chung của báo cáo ngày.
10. **nhap_lieu_nang_suat**: Bảng lưu chi tiết sản lượng từng công đoạn theo mốc giờ.
11. **nhap_lieu_nang_suat_audit**: Lưu lịch sử thay đổi dữ liệu nhập liệu.

## 5. API Endpoints
Các API chính được cung cấp tại `/baonangsuat/api/`:

### Authentication
*   `POST /auth/login`: Đăng nhập.
*   `POST /auth/select-line`: Chọn chuyền làm việc.
*   `GET /auth/logout`: Đăng xuất.

### Báo cáo
*   `GET /bao-cao`: Lấy danh sách báo cáo (có filter).
*   `POST /bao-cao`: Tạo báo cáo mới.
*   `GET /bao-cao/{id}`: Lấy chi tiết báo cáo.
*   `PUT /bao-cao/{id}/entries`: Cập nhật số lượng (nhập liệu).
*   `PUT /bao-cao/{id}/header`: Cập nhật thông tin chung (LĐ, CTNS).
*   `POST /bao-cao/{id}/submit`: Chốt báo cáo.

### Danh mục
*   `GET /danh-muc/ca`: Danh sách ca.
*   `GET /danh-muc/ma-hang`: Danh sách mã hàng.
*   `GET /danh-muc/routing`: Lấy quy trình sản xuất.

## 6. Hướng dẫn sử dụng

### Quy trình nhập liệu
1.  **Đăng nhập**: Sử dụng tài khoản nhân viên được cấp.
2.  **Chọn Chuyền**: Nếu tài khoản quản lý nhiều chuyền, chọn chuyền cần nhập liệu.
3.  **Tạo Báo Cáo**:
    *   Bấm nút "Tạo báo cáo mới".
    *   Chọn Ngày, Ca làm việc, Mã hàng.
    *   Nhập số lao động và chỉ tiêu (nếu có).
4.  **Nhập Năng Suất**:
    *   Giao diện hiển thị bảng ma trận: Hàng (Công đoạn) x Cột (Giờ).
    *   Nhập số lượng vào các ô tương ứng.
    *   Hệ thống **Tự Động Lưu** sau khi ngừng gõ hoặc chuyển ô.
    *   Ô đang lưu sẽ có màu vàng, đã lưu thành công chuyển màu xanh.
5.  **Chốt Báo Cáo**:
    *   Sau khi hoàn thành nhập liệu, bấm "Chốt báo cáo".
    *   Báo cáo đã chốt sẽ không thể chỉnh sửa (trừ khi được mở khóa bởi Admin).

## 7. Phím tắt (Keyboard Shortcuts)
Hệ thống hỗ trợ thao tác bàn phím để nhập liệu nhanh:

| Phím tắt | Chức năng |
| :--- | :--- |
| `Enter` | Di chuyển đến ô tiếp theo |
| `Tab` | Di chuyển đến ô tiếp theo |
| `Shift + Enter/Tab` | Di chuyển về ô trước đó |
| `Mũi tên Lên` | Di chuyển lên ô trên |
| `Mũi tên Xuống` | Di chuyển xuống ô dưới |
| `Mũi tên Trái` | Di chuyển sang trái (khi con trỏ ở đầu dòng) |
| `Mũi tên Phải` | Di chuyển sang phải (khi con trỏ ở cuối dòng) |
| `Ctrl + S` | Lưu dữ liệu thủ công (dù hệ thống có tự động lưu) |
