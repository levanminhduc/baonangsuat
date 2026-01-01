# Thiết kế tính năng Lịch sử báo cáo hằng ngày

## Overview
Tính năng “Lịch sử báo cáo hằng ngày” cho phép người dùng xem lại các báo cáo theo ngày, có khả năng drill-down vào chi tiết công đoạn/mốc giờ. Admin xem toàn bộ LINE và quản lý quyền xem tab lịch sử cho từng user. Nhân viên chỉ xem dữ liệu thuộc LINE mà họ thuộc về.

## Architecture
- Hệ thống giữ nguyên mô hình đa CSDL:
  - `mysqli` cho xác thực và phân quyền theo user.
  - `nang_suat` cho dữ liệu báo cáo năng suất.
  - `quan_ly_nhan_su` chỉ đọc.
- Tính năng lịch sử sử dụng dữ liệu hiện có: `bao_cao_nang_suat`, `nhap_lieu_nang_suat`, `cong_doan`, `moc_gio`, `line`, `ca_lam`, `ma_hang`.
- Thêm bảng quyền xem lịch sử trong `mysqli` để bật/tắt tab lịch sử theo user.

## Data flow
1. Client gọi API lấy context session để biết role, line_id, và cờ quyền xem lịch sử.
2. Khi vào tab lịch sử:
   - Admin: gọi API danh sách lịch sử, filter theo line/date; có thể drill-down.
   - Nhân viên: chỉ thấy lịch sử của line_id hiện tại.
3. Drill-down: gọi API chi tiết báo cáo theo ngày/line/ca/ma_hang.
4. Admin quản lý quyền: gọi API danh sách user và cập nhật quyền xem lịch sử.

## API contracts
Nguyên tắc:
- Tất cả endpoint POST/PUT/DELETE yêu cầu CSRF token.
- GET chỉ trả về dữ liệu trong phạm vi quyền.
- Base path theo router hiện tại: `/baonangsuat/api/`.

### 1) History list
- `GET /bao-cao-history`
- Query:
  - `ngay_tu` (YYYY-MM-DD, optional)
  - `ngay_den` (YYYY-MM-DD, optional)
  - `line_id` (optional; admin-only)
  - `ca_id` (optional)
  - `ma_hang_id` (optional)
  - `page` (optional)
  - `page_size` (optional)
- Auth:
  - Admin: xem tất cả, có thể filter line_id.
  - Nhân viên: bắt buộc line_id = session line_id.
- Response:
  - Danh sách báo cáo theo ngày, kèm tổng hợp: `so_lao_dong`, `ctns`, `ct_gio`, `tong_phut_hieu_dung`, `trang_thai`, `ma_line`, `ma_ca`, `ma_hang`.

### 2) History detail (drill-down)
- `GET /bao-cao-history/{bao_cao_id}`
- Auth:
  - Admin: xem tất cả.
  - Nhân viên: chỉ xem nếu bao_cao thuộc line_id của session.
- Response:
  - Header báo cáo + danh sách `moc_gio` + `routing` + `entries` (giống cấu trúc chi tiết hiện có).

### 3) Permission management
- `GET /admin/history-permissions`
  - Admin-only
  - Trả về danh sách user + quyền xem lịch sử
- `PUT /admin/history-permissions/{user_id}`
  - Body: `can_view_history` (0/1)
  - Admin-only, cần CSRF

### 4) Session context extension
- `GET /context`
  - Bổ sung `can_view_history` trong session context để UI bật/tắt tab lịch sử.

## Data model
### Thêm bảng trong `mysqli`
Bảng mới: `user_history_permissions`
- `user_id` INT NOT NULL PRIMARY KEY
- `can_view_history` TINYINT(1) NOT NULL DEFAULT 0
- `updated_by` VARCHAR(50) NULL
- `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
- FK: `user_id` -> `user.id`

### Ràng buộc
- Một user có tối đa 1 record trong `user_history_permissions`.
- Admin mặc định có quyền xem lịch sử mà không cần record (override ở logic).

## Permission model
- Admin:
  - Xem tất cả lịch sử của mọi LINE.
  - Không bị ràng buộc `user_history_permissions`.
- Nhân viên LINE:
  - Chỉ xem lịch sử của `line_id` hiện tại.
  - Chỉ được thấy tab lịch sử nếu `can_view_history = 1`.

## UI flow
### Tab “Lịch sử” (nhân viên)
- Hiển thị nếu `can_view_history = 1`.
- Mặc định filter 7 ngày gần nhất.
- Danh sách báo cáo theo ngày (table).
- Click vào một dòng -> mở panel/route detail để xem chi tiết công đoạn/mốc giờ.

### Tab “Lịch sử” (admin)
- Luôn hiển thị.
- Filter theo khoảng ngày, LINE, ca, mã hàng.
- Click dòng -> drill-down chi tiết.

### Admin permission management
- Màn quản trị: danh sách user + toggle quyền xem lịch sử.
- Hành động toggle gọi API cập nhật quyền.

## Trade-offs & options
### Option A: Bảng quyền riêng trong `mysqli`
- Ưu điểm: chuẩn hoá, không sửa bảng user hiện có, dễ audit.
- Nhược điểm: cần join/lookup thêm.

### Option B: Thêm cột `can_view_history` vào bảng user
- Ưu điểm: đơn giản, ít join.
- Nhược điểm: can thiệp schema auth, rủi ro ảnh hưởng phần khác.

### Option C: Quyền theo LINE (line_id)
- Ưu điểm: dễ quản trị theo line.
- Nhược điểm: không linh hoạt theo từng user.

**Khuyến nghị:** Option A vì phù hợp multi-db, không đụng bảng user và đủ linh hoạt theo yêu cầu.

## Security
- Kiểm tra role và line_id ở API.
- Admin luôn được xem tất cả.
- Nhân viên chỉ xem line_id của session.
- CSRF cho mọi endpoint cập nhật quyền.
- Không mở rộng CORS ngoài localhost hiện có.

## Observability
- Log tối thiểu: user_id, action, line_id, date range khi truy vấn lịch sử.
- Thêm log audit cho thay đổi quyền `can_view_history`.

## Rollout/Migration
- Migration 1: tạo bảng `user_history_permissions` trong `mysqli`.
- Backfill: mặc định 0 cho tất cả user non-admin.
- Admin auto-allow bằng logic (không cần data).
- Không thay đổi dữ liệu lịch sử hiện có.

## Testing
Checklist QA:
- Admin xem lịch sử tất cả line, filter đúng.
- Nhân viên có quyền: chỉ xem lịch sử line của mình.
- Nhân viên không quyền: không thấy tab lịch sử.
- Drill-down trả đúng chi tiết công đoạn/mốc giờ.
- CSRF bắt buộc khi admin cập nhật quyền.
- Pagination hoạt động và không leak dữ liệu.

## Risks & open questions
- Cần xác nhận có pagination hay giới hạn mặc định số dòng trong list.
- Cần xác nhận UI drill-down dùng modal hay điều hướng trang.

## Implementation plan (est.)
1. Thiết kế migration `mysqli` (0.5d) — Backend
2. Thêm HistoryService + query list/detail (1.5d) — Backend
3. Cập nhật Auth/session context để trả `can_view_history` (0.5d) — Backend
4. API endpoints cho history + permission (1d) — Backend
5. UI tab lịch sử + filter + drill-down (2d) — Frontend
6. UI quản lý quyền cho admin (1d) — Frontend
7. QA + test cases (1d) — QA
