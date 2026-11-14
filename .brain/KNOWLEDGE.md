# Knowledge — bẫy và mẹo của repo này
<!-- Thứ KHÔNG hiển nhiên từ code mà người hay agent mới sẽ vấp: cách chạy local, test hay chập chờn, thứ tự bắt buộc, phụ thuộc ngầm, lệnh hay dùng.
     Mỗi mục 1–2 dòng: - [module] điều cần biết — bằng chứng (path hoặc lệnh) · nguồn · ngày
     Tối đa khoảng 80 mục. /sync gộp mục trùng và đánh dấu ?stale khi bằng chứng không còn.
     Quy ước code thuộc về CLAUDE.md. Bản đồ thuộc về SYSTEM.md. Lý do thuộc về DECISIONS.md.
     Ví dụ:
     - [test] `pnpm test` cần Redis chạy sẵn ở cổng 6379, thiếu thì treo 30 giây rồi fail khó hiểu — docker-compose.dev.yml · B-003 · 2026-11-14
-->

## Chạy và môi trường
- [env] Config DB và whitelist admin nằm NGOÀI repo: `C:/xampp/config/db.php`, `C:/xampp/config/admin_whitelist.php` (whitelist rỗng/thiếu → mọi admin vào admin.php) — app/Support/Database.php, app/Auth/Auth.php:471 · brain-init · 2026-11-14
- [env] Bắt buộc có repo authV2 nằm cạnh (`../authV2/bootstrap.php`), kể cả khi chạy test CLI — bootstrap/app.php · brain-init · 2026-11-14
- [env] `vendor/` gitignored → `composer install` trước; Tailwind dist gitignored → `tools\build-css.bat` (các trang hiện vẫn nạp Tailwind CDN) — .claude/rules/tailwind-js.md · brain-init · 2026-11-14
- [env] 3 DB: `mysqli` (user, user_permissions), `nang_suat` (dữ liệu), `quan_ly_nhan_su` (HR, chỉ đọc) — AGENTS.md · brain-init · 2026-11-14

## Test và build
- [test] `php tests/php/run.php [filter]` — runner tự viết, chạy `tests/php/*_test.php` (32 test, pass ở 9765f3d), gồm structure_test khoá kiến trúc — tests/php/run.php · brain-init · 2026-11-14
- [test] `php tools/smoke.php` cần web server local ở http://localhost/baonangsuat; `--cookie=<HTSSOSESSID>` chỉ gọi GET; baseline-auth.json chứa dữ liệu thật → gitignored, không commit — tools/smoke.php, .gitignore:40 · brain-init · 2026-11-14
- [test] Gate mọi commit chạm PHP: php -l toàn bộ + `vendor/bin/php-cs-fixer fix --dry-run` (0 file) + unit + smoke — .claude/rules/refactor-verification-gates.md · brain-init · 2026-11-14
- [test] `tests/*.php` ở gốc tests/ (test-import-*, verify-import…) và `tests/router.spec.js` (Playwright, repo không có package.json) là script tay cũ, không nằm trong runner — tests/ · brain-init · 2026-11-14
- [test] Thao tác ghi (POST/PUT/DELETE, import confirm) không chạy bằng phiên thật; để user thử tay — .claude/rules/refactor-verification-gates.md · brain-init · 2026-11-14

## Bẫy trong code
- [js] `API_BASE = '/baonangsuat/api'` hard-code trong api.js, admin-api.js, admin/import.js, login.js — chạy dưới vhost phải sửa — AGENTS.md · brain-init · 2026-11-14
- [admin] `GET /api/admin/lines` có side-effect: đồng bộ LINE từ `quan_ly_nhan_su.phong_ban` (INSERT line mới) mỗi lần gọi — app/Services/AdminService.php:41 · brain-init · 2026-11-14
- [db] migrations đánh số trùng (hai file 010_) và thiếu 007/008; không có tool chạy migration, áp tay — database/migrations/ · brain-init · 2026-11-14
- [php] Không bật strict_types cho code cũ (chỉ app/Http/ có); đừng thêm khi sửa bug vì đổi hành vi so sánh — .claude/rules/psr12-code-style.md · brain-init · 2026-11-14
- [bootstrap] Không đặt timezone ở bootstrap; chỉ navbar đặt Asia/Bangkok cho trang, API dùng mặc định php.ini — bootstrap/app.php · brain-init · 2026-11-14
- [http] Không thêm `RewriteBase` vào .htaccess gốc (phải chạy cả localhost/baonangsuat lẫn vhost) — docs/adr/0001-single-entry-point-path-based-urls.md · brain-init · 2026-11-14
- [http] CORS `Access-Control-Allow-Origin: *` là rủi ro đã biết, giữ để tương thích — app/Http/Cors.php · brain-init · 2026-11-14

## Khác
