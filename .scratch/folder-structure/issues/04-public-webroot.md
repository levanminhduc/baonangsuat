# 04: Chuyển web root sang `public/`

**What to build:** Apache chỉ phục vụ nội dung trong `public/`. Mọi URL cũ (`/baonangsuat/<trang>.php`, `/baonangsuat/api/...`, `/baonangsuat/assets/...`, `/baonangsuat/img/...`) vẫn chạy y nguyên, không phải sửa cấu hình Apache.

- Trang, router API, `assets/`, `img/` chuyển vào `public/`.
- `.htaccess` gốc theo mẫu lichkehoach:
  - rewrite mọi request vào `public/`;
  - không có `RewriteBase`, không bọc `IfModule` để mod_rewrite tắt thì báo lỗi ngay thay vì lộ code;
  - chặn truy cập trực tiếp `app bootstrap config database docs tests tools vendor` và mọi dotfile, trả 403.
- Router API vẫn chạy sau khi đổi vị trí, giữ nguyên `$basePath` và CORS.
- Tailwind: content glob trỏ theo cây mới, output ghi vào `public/assets/...`.
- Chạy được cả dưới `localhost/baonangsuat/` lẫn vhost.

Xem spec: `.scratch/folder-structure/spec.md`, ADR 0001.

**Blocked by:** 02, 03

**Status:** ready-for-agent

- [ ] Gốc repo không còn file `.php` trang nào; `public/` chứa trang, `api/`, `assets/`, `img/`
- [ ] `.htaccess` gốc không có `RewriteBase`, không bọc `IfModule`
- [ ] Các thư mục code và dotfile trả 403 qua web (cập nhật baseline có chủ đích cho các URL này)
- [ ] Worker realtime và mọi asset tải được (200)
- [ ] Build Tailwind ra đúng thư mục mới, trang không mất style (so số class và kích thước CSS với trước)
- [ ] Smoke 2 chế độ: diff = 0 ngoài các URL mới bị chặn 403
- [ ] `php -l` và `php tests/php/run.php` xanh
