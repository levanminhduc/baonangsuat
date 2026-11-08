# Thiết kế: Chuyển xác thực baonangsuat sang authV2 (SSO nội bộ)

**Ngày tạo:** 2026-11-08
**Trạng thái:** Draft — chờ duyệt

## 1. Mục đích

Người dùng làm việc trong cả hệ sinh thái (baonangsuat, nhansuhtdb, lichkehoach, sangkienct,
khsanxuat) trong cùng một ngày. Mục tiêu: **đăng nhập một lần, đi lại giữa các dự án không phải gõ
lại mật khẩu; đăng xuất ở đâu cũng ra khỏi tất cả**. baonangsuat trở thành dự án thứ 5 dùng authV2
(`C:/laragon/www/authV2`, package `Hoatho\SharedAuth`), giống 4 dự án đã port.

Ranh giới: authV2 lo **ai đang đăng nhập**. baonangsuat giữ nguyên **phân quyền** (role admin,
`user_permissions`, LINE).

## 2. Bối cảnh người dùng (đã xác nhận)

- Người nhập năng suất dùng **máy riêng** → phiên trượt 30 ngày mặc định của authV2 là chấp nhận được.
- **Mỗi người một LINE, một máy.** Người A được gán LINE 1 thì chỉ được nhập LINE 1.
- authV2 **chưa lên production**. Đợt này làm và test trên Laragon; deploy production một lần sau,
  cùng authV2 (ngoài phạm vi tài liệu này).

## 3. Mục tiêu / Không làm / Phản mục tiêu

**Mục tiêu**
- baonangsuat không còn tự xác thực: không form login, không so mật khẩu.
- Trải nghiệm trong app giữ nguyên: navbar hiện tên + LINE/Admin, tab Lịch sử theo quyền, trang Admin.
- Chặn chọn LINE không thuộc người dùng (lỗ hổng hiện có ở `Auth::selectLine`).

**Không làm**
- Không đổi schema DB, không mã hoá mật khẩu (`SHARED_AUTH_UPGRADE_ON_LOGIN` giữ TẮT).
- Không thêm nút "đổi LINE".
- Không deploy production, không sửa cấu hình Apache/XAMPP.
- Không thêm auth cho `indexdept.php`, `demo-components.php` (hiện public, giữ public).

**Phản mục tiêu** (chạy được nhưng vẫn là thất bại)
- Login xong navbar trống / mất menu Admin / mất tab Lịch sử (bối cảnh session không dựng lại).
- Login xong rơi về portal authV2 thay vì quay lại baonangsuat.
- Bấm Đăng xuất nhưng vẫn còn đăng nhập.
- baonangsuat làm hỏng phiên/CSRF của dự án khác, hoặc ngược lại.
- Còn bất kỳ chỗ nào so mật khẩu trong baonangsuat.

## 4. Hiện trạng (kết quả audit authV2)

- `session_start` chỉ ở `classes/Auth.php` và `csrf.php` → nhóm 1 (sửa trực tiếp, `Auth.php` làm shim).
  `tasks.md` 5.3 của authV2 ghi "nhóm 2" — đi theo audit vì chỉ có 2 điểm mở phiên.
- So mật khẩu: `Auth.php:27-28` (+ hằng `ALLOW_PLAINTEXT_PASSWORD`). Không có điểm ghi mật khẩu, không remember-me.
- Login hiện tại: `index.php` (form) → `assets/js/login.js` → `POST api/auth/login` → `Auth::login()`
  dựng session, tự chọn LINE nếu có 1, trả `need_select_line` + modal nếu nhiều, `no_line` nếu 0.
- Khoá session login cũ dựng và nơi khác đọc: `user_id`, `ma_nv`, `vai_tro`, `ho_ten`, `phong_ban_ma`,
  `phong_ban_ten`, `line_id`, `line_ma`, `line_ten`. Ngoài `Auth.php`/`csrf.php` chỉ có
  `api/index.php:791` đọc thẳng `$_SESSION['ma_nv']`.
- Logout: 3 điểm — `app.js:618`, `admin.js:225` (`fetch GET /auth/logout`) và `no-line.php:74-75`
  (`fetch POST /api/auth/logout`), rồi về `index.php`.
- Dữ liệu local (2026-11-08): 0 user có `name` khác `UPPER(TRIM(name))`; 0 người có >1 LINE trực tiếp;
  `phong_ban_line` rỗng.
- Rate-limit login riêng: `checkRateLimit*` trong `api/index.php`.
- Realtime là polling qua Web Worker, không giữ session lâu → không lo khoá file session chung.

## 5. Thiết kế

### 5.1 Mở phiên
- `Auth.php` và `csrf.php` thay `session_start()` bằng `require_once __DIR__ . '/../../authV2/bootstrap.php'`
  ở `Auth.php`; `csrf.php` (nằm ở gốc repo) dùng `__DIR__ . '/../authV2/bootstrap.php'`.
  Production cũng đặt authV2 cạnh baonangsuat.
- Cookie phiên đổi sang `HTSSOSESSID` → **mọi người bị đăng xuất một lần** khi triển khai.

### 5.2 Danh tính & bối cảnh — dựng lại mỗi request
- `Auth::isLoggedIn()` = `SessionContract::isLoggedIn()`; user id lấy từ `SessionContract::userId()`.
- Một hàm sync (static, chạy một lần/request, cache bằng `static $done`) dựng bối cảnh từ DB:
  - `mysqli.user` theo `id` → `name`, `role`.
  - `ma_nv` = `strtoupper(trim(name))` (quy ước AGENTS.md). Code cũ lưu `name` thô và giá trị này được
    ghi làm người thao tác (`api/index.php:274-326`, `:791`). Local không có `name` nào khác dạng in hoa
    → không đổi dữ liệu. Trước deploy production chạy
    `SELECT name FROM user WHERE name <> UPPER(TRIM(name))`; có dòng thì báo lại trước khi bật.
  - `quan_ly_nhan_su.nhan_vien` + `phong_ban` theo `ma_nv` → `ho_ten`, `phong_ban_ma`, `phong_ban_ten`
    (truy vấn giống `getNhanVienInfo()` hiện có; lỗi thì để trống, `ho_ten` rơi về `ma_nv`).
  - User id không còn tồn tại trong `user` → coi như chưa đăng nhập.
- **Khoá session có tiền tố `bns_`** (`bns_ma_nv`, `bns_vai_tro`, `bns_ho_ten`, `bns_phong_ban_*`,
  `bns_line_*`, `bns_csrf_token`). Lý do: session giờ dùng chung; nhansuhtdb và sangkienct ghi
  `vai_tro`/`ho_ten`/`phong_ban_ten` với nghĩa khác, lichkehoach có `regenerateCsrfToken()` ghi đè
  `csrf_token`. Mọi chỗ đọc đổi theo (`Auth.php`, `csrf.php`, `api/index.php:791`).
- `Auth::getSession()` **giữ nguyên tên khoá trong mảng trả về** (`ma_nv`, `vai_tro`, `line_id`...) để
  API `auth/session`, `context` và JS không phải đổi.
- Giữ nguyên chữ ký public của `Auth`: `isLoggedIn`, `hasLine`, `getSession`, `checkRole`,
  `getDefaultPage`, `hasPermission`, `canViewHistory`, `getUserPermissions`, `selectLine`.

### 5.3 LINE
- Danh sách LINE được phép = quy tắc hiện có `getUserLines()`: `user_line` theo `ma_nv`; nếu rỗng thì
  `phong_ban_line` theo phòng ban. Chỉ LINE `is_active = 1`.
- LINE đã chọn lưu trong session kèm **chủ sở hữu** (`bns_line_owner` = user id). Mỗi request:
  chủ khác user hiện tại, hoặc LINE không còn trong danh sách được phép → xoá LINE đã chọn.
- Tự chọn: user không phải admin, chưa có LINE, danh sách có đúng 1 LINE → tự gán.
- `selectLine($id)` chỉ nhận LINE nằm trong danh sách được phép (sửa lỗ hổng hiện có).
- Admin không cần LINE (giữ hành vi hiện tại).
- LINE đã chọn sống theo phiên (tối đa 30 ngày), không có nút đổi LINE — chấp nhận vì mỗi người một
  LINE. Nếu sau này xuất hiện người nhiều LINE cần đổi thường xuyên thì làm thành việc riêng.

### 5.4 Trang & điều hướng
- **`index.php`** không còn là form login, thành điểm vào:
  - Chưa đăng nhập → 302 tới `EnvConfig::loginUrl() . '?redirect=' . rawurlencode('/baonangsuat/index.php')`
    (đường dẫn tuyệt đối, vì allowlist redirect của authV2 so theo prefix).
  - Admin → `admin.php`. Có LINE → `nhap-nang-suat.php`. Không có LINE được phép → `no-line.php`.
  - Nhiều LINE, chưa chọn → hiện modal chọn LINE (tái dùng phần modal + `select-line` trong
    `login.js`/markup hiện có; phần form username/password bỏ). Danh sách LINE được phép do PHP
    render sẵn vào trang (không còn lấy từ response login).
- **`admin.php`, `nhap-nang-suat.php`, `no-line.php`**: nhánh "chưa đăng nhập" đổi từ
  `Location: index.php` sang redirect tới login authV2 kèm `?redirect=` là URL hiện tại
  (dùng `Guard::requireLogin()` hoặc tương đương). Nhánh role giữ nguyên.
- Nhánh "chưa có LINE" (`nhap-nang-suat.php`, `no-line.php`): người có ≥1 LINE được phép nhưng chưa
  chọn → về `index.php` (để tự chọn hoặc hiện modal); chỉ người có 0 LINE mới ở lại `no-line.php`.
  Lý do: với SSO người dùng hay vào thẳng `nhap-nang-suat.php` (Guard trả về đúng trang, bookmark),
  không đi qua `index.php`.
- **Đăng xuất**: nút ở `app.js`, `admin.js` và `no-line.php` **chuyển trang** (`window.location`) tới
  `EnvConfig::logoutUrl('/baonangsuat/index.php')`, không dùng `fetch` — `logout.php` của authV2 từ chối
  request không phải điều hướng tài liệu (`Sec-Fetch-Dest: document`). URL logout được PHP render ra
  trang (vd. `data-logout-url`) vì base URL khác nhau giữa local (`/authV2/public`) và production (`/auth`).

### 5.5 API (`api/index.php`)
- Bỏ route `auth/login`, `auth/logout`, bộ `checkRateLimit`/`incrementRateLimit`/`resetRateLimit`.
- Giữ `auth/select-line` (đã có kiểm CSRF + kiểm LINE thuộc user), `auth/session`, `csrf-token`.
- `requireLogin()` vẫn trả **JSON 401** (không dùng Guard — 302 làm vỡ fetch). Chưa có JS xử lý 401
  riêng; giữ nguyên hành vi hiện tại.

### 5.6 Xoá
- `Auth::login()`, `ALLOW_PLAINTEXT_PASSWORD`, `Auth::logout()` (hoặc thành gọi `SessionBootstrap::destroy()`
  nếu còn nơi dùng), form login trong `index.php`, phần submit form trong `login.js`.

### 5.7 Sửa repo authV2 (thay đổi chéo repo)
- Thêm `'/baonangsuat/' => 'Báo năng suất'` vào nhánh mặc định `EnvConfig::projects()`, cập nhật
  `tests/unit/env_config_test.php`.
- Ghi chú cho deploy sau: production phải thêm `/baonangsuat/|Báo năng suất` vào `SHARED_AUTH_PROJECTS`.
- Cập nhật `authV2/.agents/.dd/specs/shared-auth/tasks.md` mục 5.3 và `authV2/CLAUDE.md` (danh sách dự án đã port).

## 6. Kiểm tra (definition of done)

- `php C:/laragon/www/authV2/.claude/skills/port-to-authv2/scripts/smoke_test.php baonangsuat/nhap-nang-suat.php`
  pass hết (Guard chặn, redirect đúng, login quay lại, SSO chéo, logout huỷ phiên chung, DB không bị ghi hash).
- `php tests/run.php` ở authV2 pass.
- Thủ công trên trình duyệt, đối chiếu DB:
  - User 1 LINE: login → vào thẳng `nhap-nang-suat.php`, navbar đúng tên + LINE.
  - User nhiều LINE: hiện modal; chọn LINE không thuộc mình qua API → bị từ chối.
  - User không LINE → `no-line.php`. Admin → `admin.php`, có menu Quản trị.
  - User có/không `can_view_history` → tab Lịch sử hiện/ẩn đúng.
  - Login ở nhansuhtdb rồi mở baonangsuat → vào thẳng. Logout ở baonangsuat → nhansuhtdb đòi login.
  - Đổi user trên cùng trình duyệt → LINE của user trước không còn.
- Cổng grep: không còn `password_verify`, `ALLOW_PLAINTEXT_PASSWORD`, `session_start` trong baonangsuat.
- `tests/router.spec.js` (Playwright): bước login bằng form cũ phải đổi sang login qua trang authV2.

## 7. Quyết định do Claude đưa ra (chưa được người dùng xác nhận riêng)

- Tiền tố `bns_` cho khoá session.
- Người chưa chọn LINE vào trang bất kỳ → về `index.php`; chỉ 0 LINE mới ở `no-line.php`.
- `ma_nv` in hoa (dữ liệu local không bị ảnh hưởng; production kiểm trước deploy).
- LINE chọn một lần, sống theo phiên, không nút đổi LINE.
- Sửa lỗ hổng `selectLine` nhận LINE bất kỳ.

## 8. Để builder tự quyết

- Cấu trúc nội bộ của hàm sync và cách tách file (giữ trong `Auth.php` hay helper riêng).
- Dùng `IdentitySync` của authV2 cho phần cột `user` hay tự viết truy vấn.
- Giao diện modal chọn LINE trên `index.php` mới (tái dùng tối đa markup hiện có).
- Có giữ khoá session không tiền tố như shim tạm hay không (hiện không nơi nào ngoài các file trên đọc).
