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
- [env] Kết nối thứ 4 tuỳ chọn: Postgres datchi qua key `datchi_pg` trong db.php + ext pdo_pgsql. Apache local = Laragon php 8.3.28 (php.ini đã bật pdo_pgsql); PHP CLI = XAMPP 8.2 chưa bật → `php -d extension=pdo_pgsql` — app/Support/Database.php · F-015 · 2026-11-14
- [env] pdo_pgsql nối `connect_timeout` từ PDO::ATTR_TIMEOUT (mặc định 30) sau DSN → `connect_timeout` trong DSN vô hiệu; đo 21s → 3s khi đặt ATTR_TIMEOUT — app/Support/Database.php · F-015 · 2026-11-14

## Test và build
- [test] `php tests/php/run.php [filter]` — runner tự viết, chạy `tests/php/*_test.php` (32 test, pass ở 9765f3d), gồm structure_test khoá kiến trúc — tests/php/run.php · brain-init · 2026-11-14
- [test] `php tools/smoke.php` cần web server local ở http://localhost/baonangsuat; `--cookie=<HTSSOSESSID>` chỉ gọi GET; baseline-auth.json chứa dữ liệu thật → gitignored, không commit — tools/smoke.php, .gitignore:40 · brain-init · 2026-11-14
- [test] Gate mọi commit chạm PHP: php -l toàn bộ + `vendor/bin/php-cs-fixer fix --dry-run` (0 file) + unit + smoke — .claude/rules/refactor-verification-gates.md · brain-init · 2026-11-14
- [test] `tests/*.php` ở gốc tests/ (test-import-*, verify-import…) và `tests/router.spec.js` (Playwright, repo không có package.json) là script tay cũ, không nằm trong runner — tests/ · brain-init · 2026-11-14
- [test] Thao tác ghi (POST/PUT/DELETE, import confirm) không chạy bằng phiên thật; để user thử tay — .claude/rules/refactor-verification-gates.md · brain-init · 2026-11-14

## Bẫy trong code
- [git] `core.autocrlf=true` → nhiều file checkout CRLF: `php-cs-fixer --dry-run` báo hàng loạt file (false positive), Edit tool không khớp chuỗi nhiều dòng (sửa bằng script newline=''), smoke lệch hash body asset. Kiểm file mới: `--config=.php-cs-fixer.dist.php --path-mode=intersection <files>` — F-015 · 2026-11-14
- [js] Ô nhập grid là lũy kế tại mốc: tổng báo cáo = số ở mốc muộn nhất đã nhập (grid.rowTotal, calculateLuyKeThucTe), KHÔNG SUM các mốc — public/assets/js/modules/grid.js:174 · F-015 · 2026-11-14
- [php] `NangSuatService::updateHeader` mặc định so_lao_dong/ctns = 0 khi thiếu → mọi PUT header phải gửi lại cả hai — app/Services/NangSuatService.php · F-015 · 2026-11-14
- [php] `JsonResponse` final + strict_types; service trả mảng có `status` thì controller bóc ra làm HTTP status (BaoCaoController::withStatus, PoSubartController::toResponse) — F-015 · 2026-11-14
- [test] Test route thật: `(new Router(require BNS_ROOT . '/config/routes.php'))->match($method, $path)` → [status, route, params]; filter runner là substring tên file — tests/php/router_test.php · F-015 · 2026-11-14
- [js] `API_BASE = '/baonangsuat/api'` hard-code trong api.js, admin-api.js, admin/import.js, login.js — chạy dưới vhost phải sửa — AGENTS.md · brain-init · 2026-11-14
- [admin] `GET /api/admin/lines` có side-effect: đồng bộ LINE từ `quan_ly_nhan_su.phong_ban` (INSERT line mới) mỗi lần gọi — app/Services/AdminService.php:41 · brain-init · 2026-11-14
- [db] migrations đánh số trùng (hai file 010_) và thiếu 007/008; không có tool chạy migration, áp tay — database/migrations/ · brain-init · 2026-11-14
- [php] Không bật strict_types cho code cũ (chỉ app/Http/ có); đừng thêm khi sửa bug vì đổi hành vi so sánh — .claude/rules/psr12-code-style.md · brain-init · 2026-11-14
- [bootstrap] Không đặt timezone ở bootstrap; chỉ navbar đặt Asia/Bangkok cho trang, API dùng mặc định php.ini — bootstrap/app.php · brain-init · 2026-11-14
- [http] Không thêm `RewriteBase` vào .htaccess gốc (phải chạy cả localhost/baonangsuat lẫn vhost) — docs/adr/0001-single-entry-point-path-based-urls.md · brain-init · 2026-11-14
- [http] CORS `Access-Control-Allow-Origin: *` là rủi ro đã biết, giữ để tương thích — app/Http/Cors.php · brain-init · 2026-11-14

## Khác
- [css] Tailwind v4 lệch v3 ở: bảng màu oklch, line-height dạng tỉ lệ, selector `space-x/y`, preflight xoá nền input + opacity select disabled + padding ô ngày. Đã vá trong `src/v3-*.css`; nâng Tailwind phải so lại computed style — public/assets/tailwind/src/ · F-016 · 2026-11-25
- [css] `@utility` trùng tên built-in thì cả hai cùng sinh; muốn thay built-in: `@source not inline()` rồi viết CSS thường — src/v3-space.css · F-016 · 2026-11-25
- [css] style.css định nghĩa `.modal`, `.hidden`, `.line-list`, `.login-*`; bundle áp cho MỌI trang nên view mới đừng dùng lại tên đó — public/assets/css/style.css · F-016 · 2026-11-25
- [fe] components/confirm-modal.php dùng chung admin + nhập; mỗi trang tự bắt `close-modal` (admin.js / app.js) — trang mới include nó phải gắn listener · F-016 · 2026-11-25
- [fe] API trả `session.vai_tro` (không có `role`); `role` chỉ có ở bản ghi user của tab Quyền — app/Auth/Auth.php:72 · F-016 · 2026-11-25
- [test] Kiểm trang đã đăng nhập không cần cookie: PHP built-in server + phiên giả (reflection vào Auth) + `BNS_DB_CONFIG` trỏ DB `*_test`; DB test thiếu bảng HR (`phong_ban`, `nhan_vien`, `user`) nên tab LINE/User cần bảng giả — tests/php/support/db.php · F-016 · 2026-11-25
- [tool] Git Bash đổi pattern `rg` bắt đầu bằng `/` thành đường dẫn Windows: viết `[/]baonangsuat` · F-016 · 2026-11-25
- [tool] `.gitignore` phải neo `/vendor/`; `vendor/` trần nuốt luôn public/assets/vendor · F-016 · 2026-11-25
- [php] cs-fixer báo cả file khi working tree là CRLF (autocrlf); chạy `fix` đổi sang LF là đủ, git không ghi nhận thay đổi · F-016 · 2026-11-25
