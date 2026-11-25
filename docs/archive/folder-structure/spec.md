# Spec: Chuẩn hoá cây thư mục baonangsuat theo nhansuhtdb

Status: ready-for-agent

## Problem Statement

baonangsuat lệch cấu trúc so với các dự án anh em cùng host (nhansuhtdb, lichkehoach, khsanxuat), nên người bảo trì phải nhớ một bố cục riêng cho mỗi repo. Cụ thể:

- Gốc repo chính là web root: `classes/`, `config/`, `database/`, `docs/`, `tests/` đều truy cập được qua URL, và không có `.htaccess` gốc nào chặn.
- `composer.json` đã khai `App\` → `classes/` nhưng 13 class không có namespace; mọi file nạp class bằng `require_once` tay (api router ~10 dòng, mỗi service tự require Database).
- 6 trang PHP ở gốc, mỗi trang tự lặp đoạn kiểm tra đăng nhập/LINE/quyền; API là một file 1100 dòng với `switch` + 11 hàm `handleXxx`.
- Rác còn trong repo: `indexdept.php` + `contdb.php` + `includes/file-icons.php` (copy từ lichkehoach, không ai gọi), `demo-components.php`, file `$null`, file bundle backup 46MB đang được git track.
- `.gitignore` ignore `tests/`, `tools/`, `.claude/` dù `tests/` đang được dùng.

## Solution

Đưa baonangsuat về kiến trúc đích của nhansuhtdb (bản đầy đủ): một web root `public/` với một front controller duy nhất, `bootstrap/` là chỗ nạp duy nhất, code PSR-4 trong `app/` (namespace `App\`), Router + Middleware copy từ nhansuhtdb, view tách khỏi controller. Người dùng cuối không thấy khác biệt: **mọi URL trang, URL API REST, asset giữ nguyên**, hành vi đăng nhập/LINE/quyền/CSRF giữ nguyên (xem ADR 0001).

Cây đích:

```
baonangsuat/
├── .htaccess         rewrite mọi request → public/, chặn thư mục code + dotfile
├── public/           web root: index.php (front controller) + assets/ + img/
├── bootstrap/        app.php: autoload + config + authV2 bootstrap
├── app/              PSR-4 App\ : Http/ (Router, Request, Response, Middleware),
│                     Controllers/, Services/, Auth/, Support/ (Database)
├── config/           routes.php + config app (db.php vẫn ở ngoài repo)
├── views/            HTML trang + partials/ (navbar)
├── components/       13 UI component (từ includes/components)
├── database/         schema + migrations (giữ nguyên)
├── tests/            php/ (runner + test), smoke baseline
├── tools/            build-css.bat, tailwind exe (ignored), smoke.ps1, script python
├── docs/             + plans/ gộp vào, adr/, agents/
└── .claude/rules/    6 rule kiến trúc chỉnh từ nhansuhtdb
```

## User Stories

1. As a công nhân/tổ trưởng nhập năng suất, I want `/baonangsuat/nhap-nang-suat.php` vẫn mở được như cũ, so that bookmark và thói quen không bị phá.
2. As a người dùng chưa đăng nhập, I want mọi trang vẫn chuyển sang trang login chung authV2 kèm `?redirect=` đúng trang gốc, so that login xong quay lại đúng chỗ.
3. As a người dùng có nhiều LINE, I want trang chọn LINE (`index.php`) hoạt động như cũ, so that tôi vẫn chọn được LINE trước khi nhập.
4. As a người dùng không có LINE, I want vẫn được đưa tới `no-line.php`, so that tôi biết mình chưa được gán LINE.
5. As a admin, I want `admin.php` và mọi tab lazy-load vẫn chạy, so that quản trị không gián đoạn.
6. As a admin không nằm trong whitelist, I want vẫn bị chuyển về `nhap-nang-suat.php` như cũ, so that quy tắc whitelist không đổi.
7. As a người xem biểu đồ, I want `bieu-do.php` và API biểu đồ trả cùng dữ liệu, so that báo cáo không lệch.
8. As a người đọc hướng dẫn, I want `huong-dan.php` vẫn mở từ navbar, so that link không chết.
9. As a frontend JS, I want mọi endpoint `/baonangsuat/api/<resource>/...` giữ nguyên method, path, mã HTTP và JSON shape, so that không phải sửa `API_BASE` hay module nào.
10. As a frontend JS, I want `GET /api/csrf-token` và header `X-CSRF-Token` hoạt động như cũ, kể cả `csrf_error: true` khi sai token, so that cơ chế refresh token không vỡ.
11. As a frontend JS, I want API trả 401 JSON khi chưa login, 403 khi thiếu LINE/quyền, so that xử lý lỗi phía client không đổi.
12. As a realtime clock, I want Worker ở `/baonangsuat/assets/js/workers/...` vẫn tải được, so that đồng hồ server không chết.
13. As a người dùng import Excel, I want upload/preview/confirm import vẫn chạy với PhpSpreadsheet, so that nghiệp vụ import không đổi.
14. As a quản trị hệ thống, I want truy cập web trực tiếp vào `app/`, `bootstrap/`, `config/`, `database/`, `docs/`, `tests/`, `tools/`, `vendor/` và mọi dotfile bị 403, so that code và cấu hình không bị lộ.
15. As a quản trị hệ thống, I want không phải sửa cấu hình Apache trên Laragon hay XAMPP production, so that deploy chỉ là copy code.
16. As a quản trị hệ thống, I want app chạy được cả dưới `localhost/baonangsuat/` lẫn vhost, so that hai môi trường dùng chung `.htaccess`.
17. As a dev, I want chỉ một dòng `require bootstrap/app.php` ở entry point, so that không còn quên `require_once` gây fatal.
18. As a dev, I want mọi class có namespace `App\...` và Composer autoload, so that cấu trúc giống nhansuhtdb.
19. As a dev, I want route khai báo tập trung (METHOD + path → Controller::method + middleware), so that tìm endpoint không phải đọc switch 1100 dòng.
20. As a dev, I want kiểm tra login/LINE/role/CSRF/security headers/CORS là middleware khai theo route, so that không lặp lại ở đầu mỗi file.
21. As a dev chuyển qua lại giữa các repo, I want `App\Http` (Router, Request, Response, Middleware) giống nhansuhtdb, so that sửa một bên chép sang bên kia được.
22. As a dev, I want trang HTML nằm trong `views/` và controller chỉ lo quyền + dữ liệu, so that tách template khỏi logic.
23. As a dev, I want UI component dùng chung ở `components/` và navbar ở `views/partials/`, so that bố cục giống nhansuhtdb.
24. As a dev, I want PHP 8.2 là baseline (khoá platform 8.2.12), so that dùng được cú pháp hiện đại và khớp production.
25. As a dev, I want PSR-12 được kiểm bằng php-cs-fixer cho `app/`, `bootstrap/`, `config/`, so that style đồng nhất với nhansuhtdb.
26. As a dev, I want build Tailwind vẫn chạy bằng một lệnh, với content glob và output trỏ đúng cây mới, so that CSS không thiếu class.
27. As a dev, I want một test cấu trúc fail khi có người thêm file PHP ở gốc, file thực thi lạ trong `public/`, `require_once` nạp class trong `app/`, hoặc route trỏ tới class/method không tồn tại, so that cấu trúc không bị trôi về sau.
28. As a dev, I want smoke test so sánh mọi URL với baseline chụp trước refactor, so that chứng minh hành vi không đổi bằng dữ liệu chứ không bằng "đã test tay".
29. As a dev, I want smoke test có chế độ đăng nhập bằng cookie `HTSSOSESSID`, so that so được cả JSON của các GET API thật.
30. As a dev, I want rác (`indexdept.php`, `contdb.php`, `includes/file-icons.php`, `demo-components.php`, `$null`, bundle 46MB) biến khỏi repo, so that cây sạch.
31. As a dev, I want `.gitignore` track `tests/`, `tools/` (trừ `.exe`) và `.claude/rules/`, so that test, tool và rule được version.
32. As a agent (Claude) mở repo, I want `.claude/rules/` và `CLAUDE.md`/`AGENTS.md` mô tả đúng cây mới, so that phiên sau hiểu kiến trúc ngay.
33. As a reviewer, I want commit di chuyển file tách khỏi commit đổi code, so that diff review được.

## Implementation Decisions

- **Mức chuẩn hoá**: nhansuhtdb bản đầy đủ — một front controller cho cả trang HTML lẫn API.
- **URL (ADR 0001)**: giữ nguyên URL trang `*.php` và URL API REST. `.htaccess` gốc rewrite về `public/` theo mẫu lichkehoach: không `RewriteBase`, không bọc `IfModule` (fail-closed), chặn thư mục code + dotfile → 403. Front controller nhận diện trang và route API theo đường dẫn, không dùng `?page=`/`?action=`. Không có redirect 302 mới.
- **Router**: copy `App\Http` của nhansuhtdb (Router, Request, các Response, Middleware interface) rồi chỉnh: khớp theo METHOD + path (có tham số segment) thay cho key `?action=`. Bảng route ở `config/routes.php`; mỗi route khai middleware. 404 khi không khớp path, 405 khi khớp path sai method, giữ JSON shape lỗi hiện tại cho API.
- **Middleware** (thay hàm helper trong api router và đoạn đầu mỗi trang): `RequireLogin` (trang → Guard authV2 redirect; API → 401 JSON), `RequireLine` (API 403 / trang → `no-line.php` như cũ), `RequireRole`, `VerifyCsrfToken` (giữ `csrf_error: true`), `SecurityHeaders` (từ `includes/security-headers.php`), `Cors` cho nhóm `/api` (giữ `Access-Control-Allow-Origin: *` như cũ — ghi là rủi ro, xử lý riêng).
- **Controllers**: mỗi `handleXxx` → một `App\Controllers\XxxController` (11 class: Auth, Context, BaoCao, DanhMuc, Admin, MocGioSets, BaoCaoHistory, Import, ImportHistory, UserPermissions, BieuDo) + route `csrf-token`. Mỗi trang (index/LINE picker, nhap-nang-suat, admin, bieu-do, huong-dan, no-line) → một `XxxPageController` lo quyền/LINE/whitelist và dữ liệu, render `views/<trang>.php`.
- **Views**: HTML trang chuyển gần như nguyên văn vào `views/`; không chia nhỏ view lớn trong đợt này.
- **Class cũ → PSR-4**: `classes/` → `app/` với namespace (`App\Auth\Auth`, `App\Auth\LineSelection`, `App\Services\...`, `App\Support\Database`); đổi tên một lần, thêm `use` ở mọi call-site, **không** dùng `class_alias`. `config/Database.php` → `App\Support\Database`, vẫn đọc `C:/xampp/config/db.php` (và admin whitelist) ngoài repo.
- **CSRF helper**: `csrf.php` gốc → `App\Http\CsrfToken` (hoặc tương đương), giữ session key `bns_csrf_token`.
- **Bootstrap**: `bootstrap/app.php` nạp `vendor/autoload.php`, hằng đường dẫn gốc, timezone, và `authV2/bootstrap.php` (đường dẫn tương đối tới thư mục anh em). **Không** dùng `auto_prepend_file`.
- **Composer**: `autoload.psr-4` `App\` → `app/`; `require.php` `>=8.2`; `config.platform.php` `8.2.12`.
- **Asset**: `assets/` và `img/` → `public/`. Đường dẫn cứng `/baonangsuat/...` trong JS và `$basePath` giữ nguyên (chạy nhờ rewrite).
- **Tailwind**: `tailwind*.config.js` + `assets/tailwind/src` giữ ở gốc như nhansuhtdb, sửa `content` glob theo cây mới; output ghi vào `public/assets/tailwind/dist/`. `build-css.bat` → `tools/`.
- **Dọn rác**: xoá `indexdept.php`, `contdb.php`, `includes/file-icons.php`, `demo-components.php`, `$null`; `git rm --cached` bundle 46MB và chuyển ra ngoài repo; không viết lại lịch sử. `includes/` bị xoá hẳn sau khi chuyển. `plans/` gộp vào `docs/plans/`. `scripts/` → `tools/`.
- **PSR-12**: `.php-cs-fixer.dist.php` copy từ nhansuhtdb, phạm vi `app/`, `bootstrap/`, `config/`. `views/`, `components/` không thuộc PSR-12.
- **Tài liệu**: tạo `.claude/rules/` (6 file chỉnh từ nhansuhtdb: target-architecture, psr4-autoload, psr12-code-style, single-entry-point-routing, refactor-verification-gates, tailwind-js); viết lại phần Architecture của `CLAUDE.md` và `AGENTS.md`.
- **Quy trình**: branch `refactor/folder-structure`; commit move-only tách khỏi commit sửa code; merge về `main` khi xong toàn bộ.

## Testing Decisions

- Test tốt = test hành vi quan sát được từ ngoài (mã HTTP, đích redirect, JSON trả về), không test chi tiết cài đặt. Đợt này là refactor cấu trúc nên không thêm unit test cho từng controller/service.
- **Seam chính — HTTP thật qua Apache** (`tools/smoke.ps1`):
  - Danh sách URL: 6 trang, mọi route API (theo method), vài asset (css, js, worker, img), các thư mục bị chặn.
  - Chế độ chưa đăng nhập: so mã HTTP + `Location` (trang) / JSON shape (API) với **baseline chụp trên `main` trước refactor**, lưu trong `tests/`.
  - Chế độ đăng nhập: nhận cookie `HTSSOSESSID` qua tham số, gọi các GET API (và các trang), so JSON body/mã HTTP với baseline cùng cookie. Không gọi POST/PUT/DELETE để tránh ghi dữ liệu thật.
  - Mỗi bước refactor phải cho diff baseline = 0 (trừ các URL rác đã xoá có chủ đích, và các đường dẫn bị chặn mới chuyển sang 403).
- **Seam phụ — PHP in-process** qua runner có sẵn `tests/php/run.php` (prior art: `line_selection_test.php`):
  - `structure_test`: gốc repo 0 file `.php`; `public/` chỉ có `index.php` + asset; mọi route trỏ class/method tồn tại; `app/` 0 `require_once` nạp class.
  - `router_test`: path → route, tham số segment, 404, 405.
  - `line_selection_test`: giữ, chỉ đổi cách nạp sang autoload.
- **Gate mỗi bước**: `php -l` toàn repo (trừ vendor) + `php tests/php/run.php` xanh + smoke (2 chế độ) diff = 0 + user login thử tay một lần.

## Out of Scope

- Gom đường dẫn `/baonangsuat/` về hằng `BASE_URL` (làm ticket riêng sau).
- Chia nhỏ view lớn (`admin.php` 949 dòng, `huong-dan.php` 424 dòng).
- Bỏ/thu hẹp CORS `Access-Control-Allow-Origin: *` (ghi rủi ro, xử lý riêng).
- Thay đổi logic nghiệp vụ, schema DB, cơ chế auth/mật khẩu của authV2.
- Thêm `auto_prepend_file` authV2.
- Viết lại lịch sử git để xoá hẳn bundle 46MB.
- Sửa test cũ ngoài `tests/php/` (`router.spec.js`, các script import test) ngoài việc cập nhật đường dẫn nếu chúng còn chạy.

## Further Notes

- Production: XAMPP, PHP 8.2, `mod_rewrite` + `AllowOverride All` + `authV2` nằm cạnh — xác nhận bởi chủ repo (các repo anh em đang chạy cùng cơ chế). Vẫn thêm checklist kiểm trước deploy.
- `tests/` và `tools/` đang bị `.gitignore`; phải sửa `.gitignore` trước khi thêm smoke/baseline để chúng được commit.
- `assets/tailwind/dist/` đang ignore (`dist/`); giữ nguyên hành vi build-on-deploy, chỉ đổi đường dẫn output.
- `Auth.php` require authV2 bằng đường dẫn tương đối theo độ sâu thư mục; chuyển vào bootstrap để chỉ còn một chỗ phụ thuộc.
- ADR liên quan: `docs/adr/0001-single-entry-point-path-based-urls.md`.
