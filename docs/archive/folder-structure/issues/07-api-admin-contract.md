# 07: Chuyển API admin + xoá router API cũ

**What to build:** Các nhóm API `admin` và `user-permissions` thành controller đi qua Router, giữ nguyên whitelist admin và cách kiểm tra quyền. Thu gọn: xoá router API cũ và bỏ cơ chế nhường route trong front controller, từ đây mọi request API chỉ đi qua bảng route.

Xem spec: `.scratch/folder-structure/spec.md` (Controllers).

**Blocked by:** 05, 06

**Status:** ready-for-agent

- [ ] `admin` và `user-permissions` có controller + route, middleware đúng như cũ
- [ ] Router API cũ và cơ chế nhường route đã bị xoá; không còn file thực thi API nào ngoài front controller
- [ ] Các tab admin lazy-load mở được và gọi API thành công (thử tay)
- [ ] Smoke 2 chế độ diff = 0; `php -l`, php-cs-fixer dry-run, `php tests/php/run.php` xanh
