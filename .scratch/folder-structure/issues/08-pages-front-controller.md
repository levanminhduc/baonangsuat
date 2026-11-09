# 08: Trang HTML qua front controller

**What to build:** 6 trang (chọn LINE, nhập năng suất, admin, biểu đồ, hướng dẫn, no-line) đi qua front controller, URL giữ nguyên dạng `/baonangsuat/<trang>.php` (ADR 0001).

- Mỗi trang có một PageController lo đăng nhập, LINE, role, whitelist admin và dữ liệu, rồi render HTML từ `views/`. HTML chuyển gần như nguyên văn, không chia nhỏ view.
- UI component dùng chung chuyển sang `components/` ở gốc, navbar sang `views/partials/`.
- Security headers thành middleware.
- Xoá thư mục `includes/` và các file trang trong `public/`.

Xem spec: `.scratch/folder-structure/spec.md` (Views, Middleware).

**Blocked by:** 05

**Status:** ready-for-agent

- [ ] 6 trang có route + PageController + view; `public/` chỉ còn front controller và asset
- [ ] Chưa đăng nhập → redirect sang authV2 kèm `?redirect=` đúng trang; không có LINE → `no-line.php`; admin ngoài whitelist → `nhap-nang-suat.php`, như cũ
- [ ] Security headers trả về giống trước (so header response của 1-2 trang)
- [ ] `includes/` không còn; content glob của Tailwind gồm `views/` và `components/`, CSS build không mất class
- [ ] Smoke 2 chế độ diff = 0; `php -l`, php-cs-fixer dry-run, `php tests/php/run.php` xanh
