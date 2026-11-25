# baonangsuat → authV2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** baonangsuat bỏ login riêng, dùng phiên đăng nhập chung của authV2 (SSO nội bộ), giữ nguyên phân quyền và luồng LINE.

**Architecture:** `classes/Auth.php` thành shim: mở phiên qua `authV2/bootstrap.php`, lấy danh tính từ `SessionContract`, dựng lại bối cảnh (role, nhân viên, LINE) mỗi request từ DB. Logic chọn/đối chiếu LINE tách thành lớp thuần `LineSelection` để test được không cần DB. Trang dùng `Guard` để redirect sang login chung; API giữ JSON 401.

**Tech Stack:** PHP 8.2 (CLI) / 8.3 (Apache Laragon), mysqli, JS thuần (ES modules), package `Hoatho\SharedAuth` tại `C:/laragon/www/authV2`.

**Spec:** `docs/superpowers/specs/2026-11-08-baonangsuat-authv2-design.md`

## Global Constraints

- Không đổi schema DB. `SHARED_AUTH_UPGRADE_ON_LOGIN` giữ TẮT, không ghi hash mật khẩu.
- `ma_nv` luôn `strtoupper(trim($name))`.
- Base path `/baonangsuat/` hard-code như hiện tại; redirect sang authV2 dùng **đường dẫn tuyệt đối** `/baonangsuat/...`.
- Mọi khoá session do baonangsuat ghi có tiền tố `bns_`. Không ghi `ma_nv`, `vai_tro`, `ho_ten`, `phong_ban_*`, `line_*`, `csrf_token` vào `$_SESSION` nữa.
- Bối cảnh danh tính (role, nhân viên) giữ trong bộ nhớ request (static), không lưu session. Chỉ LINE đã chọn (`bns_line`) và CSRF (`bns_csrf_token`) nằm trong session. *(Chặt hơn spec §5.2 — spec để cấu trúc nội bộ cho builder.)*
- `Auth::getSession()` trả mảng với **tên khoá cũ**: `user_id, ma_nv, ho_ten, vai_tro, phong_ban_ma, phong_ban_ten, line_id, line_ma, line_ten`.
- API trả JSON 401 khi chưa đăng nhập, không bao giờ 302.
- Logout luôn là **điều hướng trang** tới `EnvConfig::logoutUrl('/baonangsuat/index.php')`, không `fetch`.
- `indexdept.php`, `demo-components.php` không thêm auth.
- Không sửa cấu hình Apache/XAMPP, không deploy.

## Review Focus

1. **User trong phiên chung đã bị xoá khỏi bảng `user`** → phải đăng xuất phiên chung rồi về login, không lặp vô hạn index ↔ authV2 (authV2 thấy "đã login" sẽ đẩy ngược lại). Test: Task 3 Step 5.
2. **Đổi tài khoản ở dự án khác** (login user B trong nhansuhtdb khi `bns_line` của A còn) → LINE của A bị bỏ. Test: `reconcile_drops_line_of_other_owner` (Task 2).
3. **LINE bị tắt (`is_active=0`) hoặc gỡ khỏi user sau khi đã chọn** → bỏ LINE, quay lại chọn/no-line. Test: `reconcile_drops_line_no_longer_allowed` (Task 2).
4. **POST `auth/select-line` với LINE ngoài danh sách** → `success:false`, session không đổi. Test: `pick_rejects_line_not_allowed` (Task 2) + curl (Task 4 Step 3).
5. **Gọi API khi phiên hết hạn / chưa login** → JSON 401, không HTML trang login. Test: Task 4 Step 3.

---

### Task 1: Đăng ký baonangsuat vào allowlist authV2 (repo authV2)

**Files:**
- Modify: `C:/laragon/www/authV2/src/EnvConfig.php` (nhánh mặc định của `projects()`)
- Test: `C:/laragon/www/authV2/tests/unit/env_config_test.php:118-140`

**Interfaces:**
- Produces: `EnvConfig::projects()` mặc định có `'/baonangsuat/' => 'Báo năng suất'`.

- [ ] **Step 1:** Sửa assert danh sách mặc định trong test `projects: mặc định và khi khai báo qua env` để kỳ vọng thêm `'/baonangsuat/' => 'Báo năng suất'` (cuối mảng).
- [ ] **Step 2:** `cd C:/laragon/www/authV2 && php tests/run.php env_config` → FAIL ở test projects.
- [ ] **Step 3:** Thêm `'/baonangsuat/' => 'Báo năng suất',` vào mảng mặc định trong `EnvConfig::projects()`.
- [ ] **Step 4:** `php tests/run.php` → toàn bộ pass (HTTP test có thể SKIP nếu thiếu MySQL; không được FAIL).
- [ ] **Step 5:** Commit trong repo authV2:
```bash
cd C:/laragon/www/authV2 && git add src/EnvConfig.php tests/unit/env_config_test.php && git commit -m "Thêm baonangsuat vào allowlist redirect"
```

---

### Task 2: `LineSelection` — logic LINE thuần + test runner PHP

**Files:**
- Create: `classes/LineSelection.php`
- Create: `tests/php/run.php` (runner tối thiểu: `test(string $name, callable $fn)`, `assertSame($expected, $actual, string $msg = '')`, in `.`/`F`, exit 1 nếu có fail, 404 nếu không phải CLI)
- Create: `tests/php/line_selection_test.php`

**Interfaces:**
- Kiểu dữ liệu: `$line` = `['id' => int, 'ma_line' => string, 'ten_line' => string]`; `$stored` = `$line + ['owner' => int]`.
- Produces:
  - `LineSelection::reconcile(?array $stored, int $userId, array $allowed, bool $isAdmin): ?array` — trả `$stored` mới (có `owner`) hoặc `null`.
    1. `$stored` có `owner === $userId` và `id` nằm trong `$allowed` → trả bản ghi với `ma_line`/`ten_line` lấy từ `$allowed` (tên mới nhất), `owner` giữ nguyên.
    2. Ngược lại, `!$isAdmin && count($allowed) === 1` → `$allowed[0] + ['owner' => $userId]`.
    3. Còn lại → `null`.
  - `LineSelection::pick(int $lineId, int $userId, array $allowed): ?array` — `$lineId` có trong `$allowed` → bản ghi + `owner`; không có → `null`.
  - So khớp `id` bằng `(int)` hai phía (mysqli trả string).

- [ ] **Step 1: Viết test** trong `tests/php/line_selection_test.php` (require `../../classes/LineSelection.php`), dữ liệu: `$l1 = ['id'=>1,'ma_line'=>'L1','ten_line'=>'Line 1']`, `$l2 = ['id'=>2,'ma_line'=>'L2','ten_line'=>'Line 2']`:
  - `reconcile_keeps_valid_line`: `reconcile($l1+['owner'=>7], 7, [$l1,$l2], false)` === `$l1+['owner'=>7]`
  - `reconcile_refreshes_names`: stored `ten_line` 'Cũ' → kết quả `ten_line` === 'Line 1'
  - `reconcile_drops_line_of_other_owner`: `reconcile($l1+['owner'=>7], 8, [$l1,$l2], false)` === `null`
  - `reconcile_drops_line_no_longer_allowed`: `reconcile($l2+['owner'=>7], 7, [$l1,$l3], false)` (`$l3` id 3) === `null`
  - `reconcile_autopicks_single_line`: `reconcile(null, 7, [$l1], false)` === `$l1+['owner'=>7]`
  - `reconcile_autopick_replaces_foreign_line`: `reconcile($l2+['owner'=>8], 7, [$l1], false)` === `$l1+['owner'=>7]`
  - `reconcile_no_autopick_for_multiple`: `reconcile(null, 7, [$l1,$l2], false)` === `null`
  - `reconcile_no_autopick_for_admin`: `reconcile(null, 7, [$l1], true)` === `null`
  - `reconcile_none_allowed`: `reconcile(null, 7, [], false)` === `null`
  - `reconcile_matches_string_ids`: stored id `1`, allowed id `'1'` → không null
  - `pick_accepts_allowed_line`: `pick(2, 7, [$l1,$l2])` === `$l2+['owner'=>7]`
  - `pick_rejects_line_not_allowed`: `pick(3, 7, [$l1,$l2])` === `null`
- [ ] **Step 2:** `php tests/php/run.php` → FAIL (class chưa có).
- [ ] **Step 3:** Viết `tests/php/run.php` (glob `tests/php/*_test.php`) và `classes/LineSelection.php` (`final class`, chỉ static, không đụng DB/session).
- [ ] **Step 4:** `php tests/php/run.php` → 12 pass, exit 0.
- [ ] **Step 5:** Commit: `git add classes/LineSelection.php tests/php && git commit -m "Thêm LineSelection: đối chiếu và chọn LINE theo user"`

---

### Task 3: `Auth.php` + `csrf.php` thành shim authV2

**Files:**
- Modify: `classes/Auth.php` (viết lại phần mở phiên, `login`, `logout`, `isLoggedIn`, `hasLine`, `getSession`, `checkRole`, `getDefaultPage`, `selectLine`, `canViewHistory`; giữ nguyên `hasPermission`, `getUserPermissions`, `getNhanVienInfo`, `getUserLines`)
- Modify: `csrf.php`
- Modify: `api/index.php:791`

**Interfaces:**
- Consumes: `LineSelection::reconcile`, `LineSelection::pick` (Task 2); `Hoatho\SharedAuth\{SessionContract, SessionBootstrap, Guard, EnvConfig}`.
- Produces (public static, mọi trang/API dùng):
  - `Auth::isLoggedIn(): bool` — `SessionContract::isLoggedIn()` **và** user còn tồn tại trong `mysqli.user`.
  - `Auth::hasLine(): bool`
  - `Auth::getSession(): array` — tên khoá cũ (xem Global Constraints).
  - `Auth::checkRole(string|array $roles): bool` — so với `user.role`.
  - `Auth::allowedLines(): array` — list `$line`; rỗng nếu chưa login.
  - `Auth::selectLine(int $lineId): array` — `['success'=>bool,'message'=>string]`; dùng `LineSelection::pick`; thất bại message `'LINE không hợp lệ'`, session không đổi.
  - `Auth::requirePageLogin(): void` — dùng cho trang HTML: `(new Guard())->requireLogin()`; nếu phiên chung có user nhưng user không còn trong DB → `SessionBootstrap::destroy()` rồi 302 tới `Auth::loginUrl()`; exit.
  - `Auth::loginUrl(string $returnPath = '/baonangsuat/index.php'): string` — `EnvConfig::loginUrl() . '?redirect=' . rawurlencode($returnPath)`.
  - `Auth::logoutUrl(): string` — `EnvConfig::logoutUrl('/baonangsuat/index.php')`.
  - `Auth::getDefaultPage(): string` — chưa login `'index.php'`; admin `'admin.php'`; có LINE `'nhap-nang-suat.php'`; có ≥1 LINE được phép nhưng chưa chọn `'index.php'`; 0 LINE `'no-line.php'`.
  - Bỏ: `Auth::login()`, `Auth::logout()`, hằng `ALLOW_PLAINTEXT_PASSWORD`.
- Hành vi nội bộ:
  - Đầu file: `require_once __DIR__ . '/../../authV2/bootstrap.php';` thay khối `session_start()`.
  - Một hàm private `boot()` chạy **một lần/request** (static flag), gọi từ mọi method public đọc trạng thái: lấy `SessionContract::userId()`; query `SELECT id, name, role FROM user WHERE id = ?` (`Database::getMysqli()`); không thấy → coi như chưa login; có → `ma_nv = strtoupper(trim(name))`, `getNhanVienInfo($ma_nv)`, `getUserLines($ma_nv, $phong_ban_ma)`; rồi `$_SESSION['bns_line'] = LineSelection::reconcile($_SESSION['bns_line'] ?? null, $userId, $lines, $role === 'admin')` (null → `unset`).
  - `ho_ten` rỗng → dùng `ma_nv` (như cũ).
- `csrf.php`: thay `session_start()` bằng `require_once __DIR__ . '/../authV2/bootstrap.php';` (đường dẫn khác `Auth.php`); khoá `csrf_token` → `bns_csrf_token`. Tên hàm giữ nguyên.
- `api/index.php:791`: `$nguoiTao = Auth::getSession()['ma_nv'];`

- [ ] **Step 1:** Thực hiện các thay đổi trên.
- [ ] **Step 2:** `php -l classes/Auth.php && php -l csrf.php && php -l api/index.php` → "No syntax errors".
- [ ] **Step 3: Cổng grep** — `rg -n "session_start|password_verify|ALLOW_PLAINTEXT|_SESSION\['(ma_nv|vai_tro|ho_ten|phong_ban_ma|phong_ban_ten|line_id|line_ma|line_ten|csrf_token)'\]" --glob '*.php' .` → không còn kết quả nào (ngoài `docs/`).
- [ ] **Step 4:** `php tests/php/run.php` → pass.
- [ ] **Step 5: Review Focus #1** (sau khi Task 6 xong thì chạy lại): đăng nhập ở `http://localhost/authV2/public/` bằng user tạm `__bns_tmp__` (INSERT vào `mysqli.user` với role rỗng, password plaintext), xoá dòng đó khỏi `user`, mở `http://localhost/baonangsuat/nhap-nang-suat.php` → kết thúc ở form login authV2 (không lặp redirect; kiểm bằng `curl -s -o NUL -w "%{http_code} %{redirect_url}"` theo cookie jar). Xoá user tạm nếu còn.
- [ ] **Step 6:** Commit: `git add classes/Auth.php csrf.php api/index.php && git commit -m "Auth thành shim authV2: phiên chung, dựng bối cảnh mỗi request"`

---

### Task 4: Dọn API auth

**Files:**
- Modify: `api/index.php` (`checkRateLimit`/`incrementRateLimit`/`resetRateLimit` dòng 48-82; `handleAuth` case `login`, `logout`)

**Interfaces:**
- Consumes: `Auth::selectLine`, `Auth::getDefaultPage`, `Auth::isLoggedIn` (Task 3).
- Produces: route `auth/select-line` (POST, CSRF) trả `{success, message, redirect_url?}`; `auth/session` giữ nguyên; `auth/login`, `auth/logout` → 404 qua nhánh `default` của `handleAuth`.

- [ ] **Step 1:** Xoá 3 hàm rate-limit, case `login`, case `logout`. `select-line`: `Auth::selectLine(intval($input['line_id'] ?? 0))`; thất bại trả HTTP 403.
- [ ] **Step 2:** `php -l api/index.php`; `rg -n "RateLimit|Auth::login|Auth::logout" api` → rỗng.
- [ ] **Step 3: Kiểm bằng curl** (cookie jar trong scratchpad):
  - Không cookie: `curl -s -i http://localhost/baonangsuat/api/context` → `401`, `Content-Type: application/json`, body `"Chưa đăng nhập"`.
  - `POST /baonangsuat/api/auth/login` → 404 JSON.
  - Đã login (lấy cookie qua form authV2 như smoke_test.php làm) + CSRF từ `GET /api/csrf-token`: `POST auth/select-line` với `line_id` không thuộc user → 403 `success:false`; với LINE của user → `success:true`.
- [ ] **Step 4:** Commit: `git add api/index.php && git commit -m "API: bỏ login/logout/rate-limit riêng, select-line chỉ nhận LINE của user"`

---

### Task 5: `index.php` thành điểm vào + hộp chọn LINE

**Files:**
- Modify: `index.php`
- Modify: `assets/js/login.js` (rút gọn chỉ còn phần chọn LINE)

**Interfaces:**
- Consumes: `Auth::requirePageLogin`, `Auth::checkRole`, `Auth::hasLine`, `Auth::allowedLines` (Task 3); `POST /api/auth/select-line`, `GET /api/csrf-token` (Task 4).
- Produces: `index.php` render `<script id="allowedLines" type="application/json">` chứa `Auth::allowedLines()` (`json_encode` với `JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE`); `login.js` đọc nó, mở `#lineSelectModal` ngay khi load.

- [ ] **Step 1:** `index.php` logic đầu trang, theo thứ tự: `Auth::requirePageLogin()`; admin → `admin.php`; `hasLine()` → `nhap-nang-suat.php`; `allowedLines()` rỗng → `no-line.php`; còn lại render trang chỉ có header/logo + modal chọn LINE + toast (bỏ form username/password, remember, quên mật khẩu, `csrf.php` include). Thêm link "Đăng xuất" tới `Auth::logoutUrl()` trong trang.
- [ ] **Step 2:** `login.js`: bỏ toggle password, remember username (`localStorage rememberedUsername` — xoá cả key cũ), submit form, `fetch api/auth/login`. Giữ `showLineSelect(lines)` + `fetchLoginCsrfToken()`; lines lấy từ `#allowedLines`. Nội dung `<li>` dựng bằng `textContent` thay vì chuỗi HTML.
- [ ] **Step 3: Kiểm thủ công** (local DB hiện có 1 user gán 1 LINE; tạo tạm user thứ 2 gán 2 LINE trong `nang_suat.user_line`, xoá khi xong):
  - Chưa login, mở `/baonangsuat/` → form authV2, URL có `redirect=%2Fbaonangsuat%2Findex.php`; login → về baonangsuat.
  - User 1 LINE → thẳng `nhap-nang-suat.php`, navbar đúng tên + LINE.
  - User 2 LINE → modal 2 LINE; chọn → `nhap-nang-suat.php` đúng LINE.
- [ ] **Step 4:** Commit: `git add index.php assets/js/login.js && git commit -m "index.php: điểm vào SSO, chỉ còn chọn LINE"`

---

### Task 6: Trang được bảo vệ + đăng xuất

**Files:**
- Modify: `admin.php:5-13`, `nhap-nang-suat.php:5-13`, `no-line.php:5-18`, `no-line.php:68-83`
- Modify: `includes/navbar.php:103`
- Modify: `assets/js/app.js:143-146,618-625`, `assets/js/admin.js:209,225-228`

**Interfaces:**
- Consumes: `Auth::requirePageLogin`, `Auth::getDefaultPage`, `Auth::logoutUrl` (Task 3).

- [ ] **Step 1:** Ba trang: thay khối `if (!Auth::isLoggedIn()) { Location: index.php }` bằng `Auth::requirePageLogin();`.
  - `admin.php`: nhánh không phải admin giữ nguyên (`getDefaultPage()`).
  - `nhap-nang-suat.php`: không admin và `!hasLine()` → `Location: ` . `Auth::getDefaultPage()` (về `index.php` nếu còn LINE để chọn, `no-line.php` nếu không có).
  - `no-line.php`: admin → `admin.php`; `hasLine()` → `nhap-nang-suat.php`; `allowedLines()` khác rỗng → `index.php`; chỉ 0 LINE mới render.
- [ ] **Step 2:** Đăng xuất: `navbar.php` `#logoutBtn` `href` = `htmlspecialchars(Auth::logoutUrl())`; `no-line.php` đổi `<button onclick="logout()">` thành `<a href="<?= htmlspecialchars(Auth::logoutUrl()) ?>">` cùng class, xoá hàm JS `logout()`. Xoá listener + hàm `logout()` trong `app.js` và `admin.js` để link tự điều hướng.
- [ ] **Step 3:** `rg -n "auth/logout" assets includes *.php` → rỗng; `php -l` các file PHP đã sửa.
- [ ] **Step 4: Kiểm thủ công:** admin login → `admin.php` có menu Quản trị; user có/không `can_view_history` → tab Lịch sử hiện/ẩn; bấm Đăng xuất ở navbar → về form login authV2, mở `/nhansuhtdb/` → bị đòi login. User 0 LINE → `no-line.php`, nút Đăng xuất hoạt động.
- [ ] **Step 5:** Commit: `git add admin.php nhap-nang-suat.php no-line.php includes/navbar.php assets/js/app.js assets/js/admin.js && git commit -m "Trang dùng Guard của authV2, đăng xuất qua phiên chung"`

---

### Task 7: Kiểm tra đầu-cuối + cập nhật tài liệu

**Files:**
- Modify: `tests/router.spec.js:13-26` (bước login qua form authV2: `#username`/`#password` tại `/authV2/public/index.php`, chờ URL `**/nhap-nang-suat.php*`; selector thật lấy từ `authV2/views/login.php`)
- Modify: `AGENTS.md` (mục Non-Obvious Behaviors: bỏ các dòng plaintext fallback, username login, session regeneration, rate limiting, remember username; thêm: xác thực qua authV2/SSO, khoá session `bns_*`, LINE tự chọn/đối chiếu mỗi request, logout là điều hướng)
- Modify: `C:/laragon/www/authV2/.agents/.dd/specs/shared-auth/tasks.md` mục 5.3 (baonangsuat đã port: nhóm 1, kết quả test, không có điểm ghi `user.password`), `C:/laragon/www/authV2/CLAUDE.md` (thêm baonangsuat vào danh sách dự án đã port)

- [ ] **Step 1:** `php C:/laragon/www/authV2/.claude/skills/port-to-authv2/scripts/smoke_test.php baonangsuat/nhap-nang-suat.php baonangsuat/indexdept.php` → 0 fail. *(Lưu ý: script login bằng user đầu tiên trong `mysqli.user`; đặt `SMOKE_USER` nếu cần user có LINE.)*
- [ ] **Step 2:** `cd C:/laragon/www/authV2 && php tests/run.php` → 0 fail; `cd C:/laragon/www/baonangsuat && php tests/php/run.php` → 0 fail.
- [ ] **Step 3: Review Focus #2:** login user A (có LINE) → vào baonangsuat; ở cùng trình duyệt logout rồi login user B tại `/nhansuhtdb/` → mở `/baonangsuat/` → navbar là B, LINE của B (hoặc `no-line.php`), không phải LINE của A.
- [ ] **Step 4:** Chạy lại Task 3 Step 5 (user bị xoá → không lặp redirect).
- [ ] **Step 5:** Cập nhật `router.spec.js`, `AGENTS.md`, file authV2. Chạy `npx playwright test tests/router.spec.js` nếu Playwright đã cài; chưa cài thì ghi rõ là chưa chạy.
- [ ] **Step 6:** Commit hai repo:
```bash
git add tests/router.spec.js AGENTS.md docs/superpowers && git commit -m "Tài liệu và test cho port authV2"
cd C:/laragon/www/authV2 && git add .agents/.dd/specs/shared-auth/tasks.md CLAUDE.md && git commit -m "Ghi nhận baonangsuat đã port"
```

## Ghi chú cho deploy sau (không làm trong plan này)

- Production thêm `/baonangsuat/|Báo năng suất` vào `SHARED_AUTH_PROJECTS`.
- Chạy `SELECT name FROM user WHERE name <> UPPER(TRIM(name))` trên DB production; có dòng thì báo lại trước khi bật.
- Mọi người dùng baonangsuat bị đăng xuất một lần (cookie `HTSSOSESSID`).
