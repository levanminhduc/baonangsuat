# Decisions — vì sao hệ thống như hiện tại
<!-- ADR rút gọn.
     GHI khi: chọn thư viện, kiến trúc hoặc pattern; có đánh đổi chủ ý; đặt quy ước mới; hoặc bác bỏ một hướng mà sau này dễ có người đề xuất lại.
     KHÔNG ghi: chi tiết triển khai, thứ đọc code là thấy.
     Trước khi thiết kế: grep file này theo module. Muốn làm trái một quyết định Active thì nêu rõ và hỏi người dùng.
     Đổi quyết định: thêm mục mới, sửa mục cũ thành "Status: Superseded by D-NNN". Không xoá.
     Ví dụ:
     ### D-001 Dùng cursor pagination cho mọi API danh sách
     Date: 2026-11-14 · Status: Active · Scope: api · From: F-012
     Why: offset chậm trên bảng orders hơn 5 triệu dòng.
     Rejected: offset/limit (chậm); keyset tự viết cho từng endpoint (không nhất quán).
-->

### D-001 Một entry point, URL dạng đường dẫn (giữ URL cũ, không `?page=`/`?action=`)
Date: 2026-11-14 (ghi nhận ADR có sẵn) · Status: Active · Scope: http, routes, front-controller · From: docs/adr/0001-single-entry-point-path-based-urls.md
Why: giữ bookmark, `?redirect=` của authV2, navbar và JS gọi API; tránh thêm redirect 302.
Rejected: `index.php?page=x` + `?action=` như nhansuhtdb (phải đổi toàn bộ URL API và viết shim redirect).
