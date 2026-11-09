# 05: Router + Middleware, tracer bullet qua API

**What to build:** Một front controller duy nhất (`public/index.php`) nhận mọi request API.

- Router khớp theo METHOD + đường dẫn (có tham số segment). Bảng route tập trung trong `config/routes.php`, mỗi route khai middleware riêng.
- Bộ `App\Http` (Router, Request, các Response, Middleware interface) chép từ nhansuhtdb rồi chỉnh để khớp theo đường dẫn thay cho `?action=` (ADR 0001).
- Middleware:
  - RequireLogin: API trả 401 JSON, trang chuyển qua Guard authV2.
  - RequireLine: 403.
  - RequireRole: 403.
  - VerifyCsrfToken: 403 kèm `csrf_error: true`.
  - Cors cho nhóm `/api`, giữ `*` như cũ.
- Mở rộng trước: route nào chưa khai trong bảng thì front controller nhường cho router API cũ, nên app vẫn chạy đủ.
- Chuyển 3 nhóm đầu thành controller: `csrf-token`, `auth`, `context`.
- Không thêm redirect 302 nào, URL API không đổi.

Xem spec: `.scratch/folder-structure/spec.md` (Router, Middleware, Controllers).

**Blocked by:** 04

**Status:** ready-for-agent

- [ ] `csrf-token`, `auth`, `context` đi qua Router + controller mới; router API cũ không còn nhánh tương ứng
- [ ] Route chưa chuyển vẫn chạy qua router cũ (cơ chế nhường)
- [ ] 404 khi không khớp đường dẫn, 405 khi sai method, JSON lỗi giữ shape hiện tại
- [ ] `router_test` mới (đường dẫn → route, tham số, 404, 405) xanh trong runner `tests/php`
- [ ] Ghi chú rủi ro CORS `*` trong code hoặc doc như spec
- [ ] Smoke 2 chế độ diff = 0; `php -l`, php-cs-fixer dry-run, `php tests/php/run.php` xanh
