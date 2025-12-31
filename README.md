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
Hệ thống sử dụng **3 database riêng biệt** với các mục đích khác nhau:

| Database | Mục đích |
| :--- | :--- |
| `mysqli` | Xác thực người dùng (user authentication) |
| `nang_suat` | Dữ liệu năng suất sản xuất |
| `quan_ly_nhan_su` | Thông tin nhân viên (chỉ đọc - read-only) |

1.  Tạo database `nang_suat` (nếu chưa có).
2.  Import cấu trúc và dữ liệu mẫu từ file [`database/schema.sql`](database/schema.sql) vào database `nang_suat`.
3.  Đảm bảo database `mysqli` và `quan_ly_nhan_su` đã tồn tại và có dữ liệu phù hợp.

### Bước 2: Cấu hình kết nối
Hệ thống sử dụng file cấu hình **bên ngoài thư mục dự án** tại đường dẫn `C:/xampp/config/db.php`.

> ⚠️ **Lưu ý quan trọng**: File cấu hình nằm ngoài thư mục gốc dự án để bảo mật thông tin đăng nhập database.

Tạo file này nếu chưa tồn tại với nội dung sau:

```php
<?php
return [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'mysqli',
    'database_nang_suat' => 'nang_suat',
    'database_nhan_su' => 'quan_ly_nhan_su'
];
```

### Bước 3: Cấu hình Web Server
Đảm bảo thư mục dự án `baonangsuat` nằm trong thư mục gốc của Web Server (ví dụ: `C:/xampp/htdocs/baonangsuat`).

Truy cập hệ thống qua địa chỉ: `http://localhost/baonangsuat/`

## 4. Kiến trúc hệ thống

### 4.1. Các file quan trọng

| File | Mô tả |
| :--- | :--- |
| [`config/Database.php`](config/Database.php) | Multi-DB singleton với 3 kết nối database |
| [`classes/Auth.php`](classes/Auth.php) | Xác thực + ánh xạ người dùng LINE |
| [`api/index.php`](api/index.php) | Single-file API router |
| [`classes/NangSuatService.php`](classes/NangSuatService.php) | Logic nghiệp vụ chính |
| [`classes/AdminService.php`](classes/AdminService.php) | Logic quản trị hệ thống |
| [`classes/services/MocGioSetService.php`](classes/services/MocGioSetService.php) | Quản lý preset mốc giờ |
| [`csrf.php`](csrf.php) | CSRF token management |
| [`includes/security-headers.php`](includes/security-headers.php) | Security headers |

### 4.2. Các hành vi đặc biệt

| Hành vi | Mô tả |
| :--- | :--- |
| `ma_nv` phải UPPERCASE | Tất cả code sử dụng `strtoupper(trim($ma_nv))` trước khi truy vấn |
| Hỗ trợ mật khẩu plaintext | Auth chấp nhận CẢ bcrypt VÀ plaintext passwords |
| Base path cố định | `/baonangsuat/` được hardcode trong PHP redirects và JS fetch calls |
| Pre-generated entries | Tạo báo cáo sẽ tự động tạo TẤT CẢ entries với `so_luong = 0` |
| Cờ `la_cong_doan_tinh_luy_ke` | Chỉ các công đoạn có flag=1 mới được tính lũy kế |
| CSRF protection | Tất cả POST/PUT/DELETE endpoints (trừ login) yêu cầu X-CSRF-Token header. Token lấy qua GET /api/csrf-token |
| Security Headers | X-Content-Type-Options: nosniff, X-Frame-Options: DENY, X-XSS-Protection, CSP |
| Session regeneration | Regenerate session ID on login để prevent session fixation |
| Rate limiting | 5 failed login attempts per IP trong 15 phút → HTTP 429 |
| CORS restricted | API chỉ cho phép localhost origins (127.0.0.1, localhost với bất kỳ port) |
| Remember username only | Chỉ nhớ username trong localStorage, không lưu password |

## 5. Cấu trúc Database
Database `nang_suat` bao gồm 13 bảng chính:

1.  **line**: Danh sách các chuyền sản xuất.
2.  **phong_ban_line**: Liên kết phòng ban và chuyền.
3.  **user_line**: Phân quyền nhân viên quản lý chuyền nào.
4.  **ma_hang**: Danh mục mã hàng.
5.  **cong_doan**: Danh mục các công đoạn sản xuất (Cắt, May, Là, ...).
6.  **ma_hang_cong_doan**: Định nghĩa quy trình (routing) cho từng mã hàng.
7.  **ca_lam**: Định nghĩa các ca làm việc (Sáng, Chiều, HC).
8.  **moc_gio**: Các mốc giờ nhập liệu trong từng ca (có thêm cột `set_id` FK → moc_gio_set).
9.  **bao_cao_nang_suat**: Bảng header lưu thông tin chung của báo cáo ngày.
10. **nhap_lieu_nang_suat**: Bảng lưu chi tiết sản lượng từng công đoạn theo mốc giờ.
11. **nhap_lieu_nang_suat_audit**: Lưu lịch sử thay đổi dữ liệu nhập liệu.
12. **moc_gio_set**: Preset mốc giờ theo ca.
13. **line_moc_gio_set**: Mapping LINE → Preset theo ca.

## 6. API Endpoints
Các API chính được cung cấp tại `/baonangsuat/api/`:

### Authentication
*   `POST /auth/login`: Đăng nhập.
*   `POST /auth/select-line`: Chọn chuyền làm việc.
*   `GET /auth/logout`: Đăng xuất.
*   `GET /auth/session`: Lấy thông tin session hiện tại.

### CSRF Token
*   `GET /csrf-token`: Lấy CSRF token cho các request POST/PUT/DELETE.

### Context
*   `GET /context`: Lấy thông tin ngữ cảnh (session, ca làm việc, mốc giờ).

### Báo cáo
*   `GET /bao-cao`: Lấy danh sách báo cáo (có filter).
*   `POST /bao-cao`: Tạo báo cáo mới.
*   `GET /bao-cao/{id}`: Lấy chi tiết báo cáo.
*   `GET /bao-cao/{id}/routing`: Lấy routing của báo cáo.
*   `PUT /bao-cao/{id}/entries`: Cập nhật số lượng (nhập liệu).
*   `PUT /bao-cao/{id}/header`: Cập nhật thông tin chung (LĐ, CTNS).
*   `POST /bao-cao/{id}/submit`: Chốt báo cáo.
*   `POST /bao-cao/{id}/approve`: Duyệt báo cáo (Quản đốc/Admin).
*   `POST /bao-cao/{id}/unlock`: Mở khóa báo cáo (Admin only).

### Danh mục
*   `GET /danh-muc/ca`: Danh sách ca.
*   `GET /danh-muc/ma-hang`: Danh sách mã hàng.
*   `GET /danh-muc/moc-gio`: Danh sách mốc giờ theo ca.
*   `GET /danh-muc/routing`: Lấy quy trình sản xuất.

### Admin (Yêu cầu quyền admin)
*   `GET /admin/lines`: Danh sách chuyền.
*   `POST /admin/lines`: Tạo chuyền mới.
*   `GET /admin/lines/{id}`: Chi tiết chuyền.
*   `PUT /admin/lines/{id}`: Cập nhật chuyền.
*   `DELETE /admin/lines/{id}`: Xóa chuyền.
*   `GET /admin/users`: Danh sách người dùng.
*   `GET /admin/user-lines`: Danh sách phân quyền user-line.
*   `POST /admin/user-lines`: Thêm phân quyền user-line.
*   `DELETE /admin/user-lines`: Xóa phân quyền user-line.
*   `GET /admin/ma-hang`: Danh sách mã hàng.
*   `POST /admin/ma-hang`: Tạo mã hàng mới.
*   `PUT /admin/ma-hang/{id}`: Cập nhật mã hàng.
*   `DELETE /admin/ma-hang/{id}`: Xóa mã hàng.
*   `GET /admin/cong-doan`: Danh sách công đoạn.
*   `POST /admin/cong-doan`: Tạo công đoạn mới.
*   `PUT /admin/cong-doan/{id}`: Cập nhật công đoạn.
*   `DELETE /admin/cong-doan/{id}`: Xóa công đoạn.
*   `GET /admin/routing`: Danh sách routing.
*   `POST /admin/routing`: Tạo routing mới.
*   `PUT /admin/routing/{id}`: Cập nhật routing.
*   `DELETE /admin/routing/{id}`: Xóa routing.

#### Admin - Mốc Giờ
*   `GET /admin/moc-gio/ca-list`: Danh sách ca cho mốc giờ.
*   `GET /admin/moc-gio`: Danh sách mốc giờ (filter ca_id, line_id).
*   `GET /admin/moc-gio/{id}`: Chi tiết mốc giờ.
*   `POST /admin/moc-gio`: Tạo mốc giờ mới.
*   `POST /admin/moc-gio/copy-default`: Copy mốc giờ mặc định cho LINE.
*   `PUT /admin/moc-gio/{id}`: Cập nhật mốc giờ.
*   `DELETE /admin/moc-gio/{id}`: Xóa mốc giờ.

### Preset Mốc Giờ
*   `GET /moc-gio-sets`: Danh sách preset theo ca.
*   `GET /moc-gio-sets/{id}`: Chi tiết preset (kèm mốc giờ).
*   `POST /moc-gio-sets`: Tạo preset mới.
*   `PUT /moc-gio-sets/{id}`: Cập nhật preset.
*   `DELETE /moc-gio-sets/{id}`: Xóa preset.
*   `POST /moc-gio-sets/copy`: Copy preset.
*   `GET /moc-gio-sets/{id}/lines`: Danh sách LINE đã gán.
*   `POST /moc-gio-sets/{id}/lines`: Gán LINE vào preset.
*   `DELETE /moc-gio-sets/{id}/lines`: Bỏ gán LINE.
*   `GET /moc-gio-sets/unassigned-lines`: LINE chưa gán preset.
*   `GET /moc-gio-sets/resolve`: Resolve preset cho LINE cụ thể.

## 7. Hướng dẫn sử dụng

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

### Phân quyền người dùng

| Vai trò | Quyền hạn |
| :--- | :--- |
| `to_truong` | Nhập liệu, chốt báo cáo |
| `quan_doc` | Nhập liệu, chốt báo cáo, duyệt báo cáo |
| `admin` | Toàn quyền: nhập liệu, chốt, duyệt, mở khóa, quản lý danh mục |

## 8. Phím tắt (Keyboard Shortcuts)
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

## 9. Trạng thái báo cáo

| Trạng thái | Mô tả |
| :--- | :--- |
| `draft` | Bản nháp - có thể chỉnh sửa |
| `submitted` | Đã chốt - chờ duyệt |
| `approved` | Đã duyệt |
| `locked` | Đã khóa - chỉ Admin mới có thể mở khóa |
