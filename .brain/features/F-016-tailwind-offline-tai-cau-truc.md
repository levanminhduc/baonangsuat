# F-016 Tailwind offline (bỏ CDN) + tái cấu trúc UI, JS, service, kho
Status: DONE
Related: F-014 extends (Router thêm container) · F-002/F-003/F-005/F-010/F-012/F-013 refactor nội bộ, không đổi contract HTTP
Complexity: hard   <!-- refactor lớn xuyên view/CSS/JS/service, chạy dài nhiều giờ -->
Risk: high   <!-- chuyển SQL phân quyền ra service, sửa XSS, đổi cách Database nạp config -->
Session model: fable

## 1. Problem & goal
Mất mạng là giao diện vỡ: 5 view nạp Tailwind Play CDN, `bieu-do.php` nạp Chart.js từ jsdelivr, `index.php` nạp Bootstrap + Font Awesome từ CDN. Bản build Tailwind v4 đã có nhưng không view nào dùng.
Cùng lúc xử lý 8 điểm cản trở việc update sau này (đánh giá 2026-11-25): view không có layout chung, hai hệ CSS, component chết, file "thần", `new Service()` rải rác, test nghiệp vụ mỏng, trùng lặp JS, rác trong kho.

"Xong" với người dùng: rút dây mạng, mọi trang vẫn hiển thị và chạy như hiện tại (không request nào ra ngoài máy chủ nội bộ). Hành vi và URL/JSON không đổi.

## 2. Scope
- In:
  1. Layout chung `views/layouts/app.php`; bỏ CDN + `tailwind.config` inline; tách `views/admin.php` theo tab; bỏ handler inline (`onclick`/`onsubmit`).
  2. Một bản build Tailwind v4 duy nhất, commit vào repo; CSS tay nhập vào build dưới layer; cắt selector chết; Chart.js về file local; `index.php` viết lại bằng Tailwind (bỏ Bootstrap/Font Awesome).
  3. Xoá component và module JS không dùng.
  4. Tách `NangSuatService`, `ImportService`, `app.js`, `bieu-do.js` theo trách nhiệm.
  5. Container autowire trong Router; controller nhận service qua constructor; bỏ facade `AdminService`; SQL của `UserPermissionsController` chuyển vào service.
  6. Test nghiệp vụ: lũy kế, kết quả submit, parser import (thuần); optimistic lock, tạo báo cáo, submit (tích hợp DB test, tuỳ chọn).
  7. Một `API_BASE` (lấy base path từ PHP), một `escapeHtml`, escape các chỗ `innerHTML` đang chèn dữ liệu API thô.
  8. Dọn kho: một file schema, số migration, script tay trong `tests/`, config Tailwind v3, docs cũ vào `docs/archive/`, cập nhật rule/CLAUDE/AGENTS/README.
  9. Sửa `session.role` → `vai_tro` ở app.js:298, 332, 772 (key `role` không tồn tại trong `Auth::getSession()`, Auth.php:213-223): nhánh admin ở màn nhập sẽ bật. Người dùng duyệt đổi hành vi này 2026-11-25. Làm trong S12.
- Out (cố ý không làm):
  - Đổi URL, shape JSON, mã HTTP, schema DB, nghiệp vụ.
  - Sửa race của optimistic lock (`updateEntries` kiểm version bằng PHP sau SELECT thường, `updateHeader` không có version trong WHERE) — chỉ khoá hành vi hiện tại bằng test; ghi BACKLOG.
  - Gộp `api.js` với `admin-api.js`, `utils.js` với `admin-utils.js`; gộp `getMocGioList`/`getRouting` trùng trong `ChartService`; SQL trong `Auth.php`; URL `/baonangsuat/` hard-code ở `Auth.php:81,88`; tách `AdminController` theo tài nguyên. Ghi BACKLOG.
  - `DESIGN.md`, `.scratch/` (issue tracker theo `docs/agents/issue-tracker.md`).
  - Thiết kế lại giao diện: mục tiêu là giữ nguyên hình, chỉ đổi nguồn CSS.

## 3. Acceptance criteria
- AC1 Là tổ trưởng ở xưởng mất mạng ngoài, tôi mở mọi trang vẫn đúng giao diện.
  - Given máy chủ nội bộ chạy, không có Internet · When mở `index.php`, `nhap-nang-suat.php`, `admin.php`, `bieu-do.php`, `huong-dan.php`, `no-line.php` · Then tab Network không có request nào tới host khác; `rg -n "https?://" views components public/assets/js` chỉ còn chuỗi namespace `http://www.w3.org/2000/svg`.
- AC2 Giao diện không đổi so với trước.
  - Given ảnh chụp 6 trang (và các modal admin, màn nhập đang mở báo cáo, chi tiết lịch sử) trước thay đổi · When chụp lại sau · Then không khác biệt nhìn thấy được ngoài đồng hồ server; khác biệt còn lại được liệt kê và người dùng chấp nhận.
- AC3 Một layout, một bản build.
  - `<head>` chỉ viết ở `views/layouts/app.php`; `rg -c "<head" views` = 1 file. Mọi trang link đúng một file Tailwind `assets/tailwind/dist/app.css`; `no-line.php` giữ màu primary `#2196F3` qua class theme.
  - `git ls-files public/assets/tailwind/dist` có `app.css`; build lại từ nguồn ra file giống hệt (`git diff --exit-code` sau `tools\build-css.bat`).
- AC4 Không còn handler inline.
  - `rg -n "\son(click|change|submit)=" views components public/assets/js` = 0 dòng.
- AC5 Không còn file chết.
  - Không còn `components/{alert,button,card,form-input,form-select,status-badge,demo-modal,modal-base,line-preset-table}.php`, `public/assets/js/modules/modal.js`, `tailwind.config.js`, `tailwind.no-line.config.js`.
- AC6 File lớn đã tách, không file nguồn nào trong danh sách sau vượt 500 dòng: `app/Services/*.php` thuộc nhóm báo cáo và import, `public/assets/js/app.js`, `public/assets/js/bieu-do.js`, `views/admin.php`.
- AC7 Controller không tự khởi tạo service.
  - `rg -n "new \w+Service\(" app/Controllers` = 0; `rg -n "mysqli_|->prepare\(" app/Controllers` = 0; không còn `app/Services/AdminService.php`.
- AC8 Có lưới test nghiệp vụ.
  - `php tests/php/run.php` chạy không cần DB và phủ: chỉ tiêu lũy kế, lũy kế thực tế, shape JSON `ket_qua_luy_ke` (golden), parser import trên `tests/fixtures/import-test.xlsx`, container autowire.
  - Khi đặt `BNS_DB_CONFIG` trỏ DB có tên kết thúc `_test`: thêm test xung đột version (`updateEntries`, `updateHeader`), tạo báo cáo sinh đủ entries = routing × mốc giờ, trùng báo cáo, submit lưu `ket_qua_luy_ke`. Không đặt biến → các test này bỏ qua, in rõ số test bỏ qua.
- AC9 JS không trùng lặp, không chèn thô.
  - `rg -n "API_BASE\s*=" public/assets/js` = 1 dòng (`modules/config.js`); `rg -n "function escapeHtml|escapeHtml\(.*\)\s*\{" public/assets/js` = 1 định nghĩa; `rg -n "/baonangsuat/" public/assets/js views components` = 0.
  - Các điểm chèn thô liệt kê ở S12 đều qua `escapeHtml`; `window.toast` hiển thị message bằng `textContent`.
- AC10 Kho gọn.
  - `database/` chỉ còn `schema.sql` + `migrations/` (không trùng số); `tests/` gốc không còn script tay; docs đã triển khai xong nằm ở `docs/archive/`; `rg -n "includes/|classes/|api/index\.php" docs --glob "!docs/archive/**" CLAUDE.md AGENTS.md README.md .claude/rules` = 0.
- AC11 Không hồi quy: `php tests/php/run.php` 0 fail, `vendor/bin/php-cs-fixer fix --dry-run` 0 file (kiểm theo `--path-mode=intersection` cho file đã chạm, xem KNOWLEDGE mục autocrlf), `php tools/smoke.php` khớp baseline (baseline cập nhật trong cùng commit khi asset đổi, ghi lý do).

## 4. Contracts
- API: `=` toàn bộ `config/routes.php` (URL, method, middleware, shape JSON, mã HTTP giữ nguyên). Không thêm route.
- Data: `=` mọi bảng. `~ database/schema.sql` thành nguồn duy nhất (nội dung lấy từ `install.sql`, bỏ `CREATE DATABASE`/`USE`); xoá `install.sql`, `nang_suat_schema.sql`. `~` đổi tên `010_add_completed_status.sql` → `010a_…`, `010_add_routing_version.sql` → `010b_…`; `+ database/migrations/README.md` (thứ tự, lý do thiếu 007/008, `user_permissions` thuộc DB `mysqli`).
- PHP nội bộ:
  - `+ App\Support\Container` (autowire theo type constructor, singleton theo class); `~ App\Http\Router` (owner F-014) dùng Container thay `new $class()` ở `Router.php:114,139,177`.
  - `~ App\Support\View` (owner F-014): `+ View::page(string $template, array $data, array $layout)` render view rồi bọc `views/layouts/app.php`; `render()` giữ nguyên.
  - `~ App\Support\Database` (owner legacy/F-015): đường dẫn config lấy từ env `BNS_DB_CONFIG` nếu có, mặc định `C:/xampp/config/db.php`.
  - `+ App\Http\Request::basePath()` công khai cho layout (hiện tính ở `Request.php:47-56`).
  - `~ NangSuatService` (owner F-002/F-003/F-015) tách thành `+ DanhMucLookup`, `+ LuyKeCalculator` (thuần), `+ BaoCaoService`, `+ BaoCaoWorkflowService`, `+ BaoCaoPoService`; xoá `NangSuatService` khi không còn nơi gọi.
  - Amended (S17/S18): thêm `+ BaoCaoBulkService` (bulkCreateBaoCao, deleteBaoCao) để `BaoCaoService` < 500 dòng.
  - `~ ImportService` (owner F-010) tách `+ ImportExcelParser`, `+ ImportHistoryService`; `ImportService` còn preview/confirm.
  - Amended (S20): thêm `+ ImportLookupService` (helper đọc DB của import) để `ImportService` < 500 dòng.
  - `- AdminService` (owner F-005…F-008); `LineService` nhận `+ getListSynced()` giữ side-effect đồng bộ HR của `AdminService::getLineList` (AdminService.php:39-43).
  - `+ UserPermissionService` nhận SQL từ `UserPermissionsController::grant` (32-78) và `revoke` (79-100).
- FE:
  - `+ views/layouts/app.php`, `+ views/admin/*.php` (partial theo tab + modal).
  - `+ public/assets/js/modules/config.js` (`API_BASE`, `BASE_PATH` đọc từ `<meta name="bns-base">`); `~ api.js`, `admin-api.js`, `admin/import.js`, `login.js`, `realtime-service.js:13` dùng lại.
  - `~ utils.js` giữ `escapeHtml` duy nhất (bản `String(x ?? '')`, 5 ký tự); `grid.js`, `admin-utils.js` re-export. Hệ quả: bản admin cũ trả `''` cho số `0`, bản mới trả `"0"`.
  - `+ public/assets/js/modules/nhap/*.js`, `+ modules/bieu-do/*.js` (tách từ `app.js`, `bieu-do.js`); `window.app`, `window.appContext`, `window.historyModule`, `window.toast` giữ.
  - `+ public/assets/vendor/chart.umd.min.js` (bản ghim, tải một lần).
  - `~ public/assets/tailwind/src/main.css` → `app.css` (một entry); `- no-line.css`; `~ tools/build-css.bat`; `~ .gitignore` bỏ ignore `public/assets/tailwind/dist/`.
  - `-` 9 component chết, `modules/modal.js`, `login.css` phần Bootstrap.

## 5. Overlap check
| Việc liên quan | Chồng ở đâu | Quyết định |
|---|---|---|
| F-014 / D-001 | Router, View, cấu trúc thư mục, `structure_test` | extend: thêm Container và `View::page`; URL và bảng route giữ nguyên, không trái D-001 |
| `docs/tailwind-offline-design.md` | Kế hoạch bỏ CDN (2 build main/no-line, config v3) | supersede: một build v4, theme no-line bằng biến CSS; doc cũ vào archive |
| F-015 (D-002…D-004) | `NangSuatService::getKhoiPo`, `chuanHoaPoSubart`, `updateHeader`; `app.js` editor PO | reuse: chuyển nguyên logic sang `BaoCaoPoService` và `modules/nhap/po-editor.js`; kết nối datchi tuỳ chọn giữ nguyên |
| F-013 | `UserPermissionsController` truy DB trực tiếp (ngoại lệ ghi trong SYSTEM.md) | extend: chuyển sang service, không đổi response |
| F-005 | `GET /api/admin/lines` có side-effect đồng bộ HR | reuse: giữ nguyên qua `LineService::getListSynced()` |
| BACKLOG nit toast innerHTML (`components/toast.php:92`) | cùng file, cùng mục 7 | gộp vào S12 |
| BACKLOG debt "method cũ trả 200 success:false", nit `getKhoiPo` gọi thừa, nit `updateHeader` | cùng file `NangSuatService` | không gộp: đổi hành vi; giữ nguyên khi tách |
| KNOWLEDGE autocrlf | sửa nhiều dòng trên file CRLF | agent sửa bằng script giữ newline hoặc Edit từng dòng; cs-fixer kiểm theo intersection |
| KNOWLEDGE smoke hash asset | `tools/smoke.php` băm nguyên byte `app.js`, `modules/api.js`, `style.css`, `admin.css`, `dist/main.css` | cập nhật `tests/smoke/urls.txt` + baseline trong cùng commit, ghi lý do |

Không có conflict; không trái quyết định Active nào.

## 6. Decisions
- Chọn: commit `public/assets/tailwind/dist/app.css` vì production không có công cụ build (exe 129 MB bị ignore). Bác bỏ: build lúc deploy (thêm bước tay, quên là vỡ giao diện). (durable)
- Chọn: một build, `no-line` đổi màu bằng class `theme-no-line` ghi đè `--color-primary`/`--color-primary-dark`. Bác bỏ: hai build (hai file gần giống nhau 44 KB, hai lệnh). (durable)
- Chọn: CSS tay nhập vào entry Tailwind bằng `@import … layer(components)` để utility thắng như dưới CDN (CDN chèn style sau cùng). Rủi ro: rule tay có specificity cao trước đây thắng utility nay thua; kiểm bằng ảnh chụp từng trang, chỗ lệch sửa tường minh. Bác bỏ: để CSS tay ngoài layer (thắng mọi utility: `.modal-content` style.css:565-577 và `body` style.css:19 sẽ đè `bg-white p-0 rounded-xl`, `bg-gray-100`). (durable)
- Amended (S5): đo computed style cho thấy dưới CDN, CSS tay có specificity cao đang THẮNG utility (input trong `.form-group`…). `layer(components)` làm đổi giao diện 30+ control, nên bundle KHÔNG dùng layer: CSS tay → CSS riêng trang (guide, chart) → preflight → lớp tương thích v3 → utility, đúng thứ tự cascade cũ.
- Chọn: giữ class Tailwind kiểu v3 trong view, thêm lớp tương thích trong `app.css` (màu border mặc định, `outline-none`, thang `shadow`/`rounded`, thay `bg-opacity-*`) theo hướng dẫn nâng cấp chính thức v4 do `researcher` xác nhận. Bác bỏ: đổi tên class hàng loạt trong view (diff lớn, khó soát).
- Chọn: `index.php` viết lại bằng Tailwind, icon SVG inline. Bác bỏ: chép Bootstrap + Font Awesome về local (giữ hệ CSS thứ ba, kèm hack `button.rounded-lg` ở main.css:20-22).
- Chọn: Container autowire nhỏ tự viết (reflection, ~60 dòng). Bác bỏ: thư viện DI (thêm phụ thuộc cho ~20 class); service locator tĩnh (không mock được qua constructor). (durable)
- Chọn: test nghiệp vụ hai tầng: thuần (luôn chạy) + tích hợp DB chỉ khi `BNS_DB_CONFIG` trỏ DB tên `*_test`. Bác bỏ: mock mysqli (giòn, không bắt được SQL sai); chạy trên DB thật (rule cấm thao tác ghi bằng dữ liệu thật). (durable)
- Chọn: không đánh lại số migration đã áp, chỉ gỡ trùng `010` thành `010a`/`010b` và ghi README. Bác bỏ: đánh lại 001…012 (làm sai mọi tham chiếu cũ, không lợi gì vì áp tay).
- Chọn: handler inline → `data-action` + một listener uỷ quyền mỗi trang. Bác bỏ: gắn listener từng nút (11 modal × 3 nút đóng trong admin).

## 7. Plan
Thứ tự: lưới an toàn → CSS/view → JS → backend → dọn kho. Không commit trong lúc chạy (chỉ `/ship`); chạy gate PHP khi chạm PHP, cập nhật baseline smoke khi asset đổi.

### S1 [sonnet] (test) Test thuần cho lũy kế và parser import
Files: `tests/php/luy_ke_test.php`, `tests/php/import_parser_test.php`, `tests/fixtures/import-test.xlsx` (git mv từ `tests/import-test.xlsx`), `tests/fixtures/6940.xlsx`
Do: test `calculateChiTieuLuyKe` (NangSuatService.php:512-539) và `calculateLuyKeThucTe` (540-575) qua `ReflectionClass::newInstanceWithoutConstructor` (constructor mở DB). Ô nhập là lũy kế tại mốc: thực tế = số ở mốc muộn nhất đã nhập, không SUM (KNOWLEDGE). Golden test shape `ket_qua_luy_ke` theo code thật (638-660: `version`, `generated_at`, `source`, `inputs`, `tong_hop`, `cong_doan`), viết cho API đích `LuyKeCalculator::buildKetQua(array $baoCao, string $now)`. Parser: gọi `parseExcel` (ImportService.php:470-499) bằng reflection trên fixture, khoá mã hàng + số công đoạn + định mức.
Done when: test lũy kế và parser PASS trên code hiện tại; golden test FAIL vì chưa có `LuyKeCalculator`. (AC8)

### S2 [sonnet] Một file schema + số migration
Files: `database/schema.sql`, xoá `database/install.sql`, `database/nang_suat_schema.sql`, `git mv` hai file `010_`, `database/migrations/README.md`
Do: `schema.sql` = nội dung `install.sql` (16 bảng, khớp code hiện tại) bỏ `CREATE DATABASE`/`USE`. README: thứ tự áp, 007/008 không tồn tại, 009 rollback `lenh_san_xuat`, `user_permissions` (migration 004) nằm ở DB `mysqli`. Sửa tham chiếu: README.md:24, docs/how-routing-snapshot.md:119, docs/deploy-checklist.md.
Done when: `mysql` nạp `schema.sql` vào DB rỗng không lỗi; `ls database` = `schema.sql`, `migrations/`; smoke `/database/schema.sql` vẫn 403. (AC10)

### S3 [opus] Database nhận config qua env + khung test tích hợp
Files: `app/Support/Database.php`, `tests/php/run.php`, `tests/php/support/db.php`, `tests/php/bao_cao_db_test.php`
Do: `BNS_DB_CONFIG` ghi đè đường dẫn config (Database.php:18). Helper test từ chối chạy nếu tên DB `nang_suat` không kết thúc `_test`, nạp `schema.sql`, seed tối thiểu, dọn sau mỗi test. Test: xung đột version `updateEntries` (679) và `updateHeader` (752), tạo báo cáo sinh đủ entries, trùng báo cáo, submit lưu `ket_qua_luy_ke`. Runner thêm `skip()` và in số test bỏ qua. Orchestrator làm vì sai là ghi vào DB thật.
Done when: không đặt env → 0 fail, báo số skip; đặt env trỏ `nang_suat_test` local → các test tích hợp PASS trên code chưa refactor. (AC8)

### S4 [opus] Chụp ảnh gốc + xác nhận khác biệt v3→v4
Files: scratchpad (ảnh), không sửa repo
Do: chụp 6 trang + 11 modal admin + màn nhập đang mở báo cáo + chi tiết lịch sử + biểu đồ có dữ liệu, ở 1440 px và 390 px, bằng trình duyệt đã đăng nhập. `researcher` đọc hướng dẫn nâng cấp Tailwind v4 chính thức, trả danh sách utility đổi nghĩa áp dụng cho các class repo đang dùng (`shadow-sm`, `outline-none`, `bg-opacity-*`, `border`/`ring` mặc định, `rounded`, cursor nút, màu placeholder).
Done when: có bộ ảnh gốc và bảng "class → xử lý trong lớp tương thích". (AC2)

### S5 [opus] Entry Tailwind duy nhất, layer cho CSS tay, commit dist, Chart.js local
Files: `public/assets/tailwind/src/app.css` (git mv từ `main.css`), xoá `src/no-line.css`, `tools/build-css.bat`, `.gitignore`, `public/assets/tailwind/dist/app.css`, `public/assets/vendor/chart.umd.min.js`
Do: `@source` thêm `views/**`, `components/**`, `public/assets/js/**`; safelist `z-[101]` (network-monitor.js:216 ghép chuỗi). `@import` 5 file CSS tay vào `layer(components)`. Lớp tương thích theo S4. `.theme-no-line` ghi đè 2 biến primary. Bỏ hack Bootstrap (main.css:20-22) sau S7. Tải Chart.js bản ghim (đúng major đang chạy trên trang hiện tại) một lần, ghi version + sha256 trong `public/assets/vendor/README.md`. Orchestrator làm vì quyết định thứ tự layer quyết định cả giao diện.
Done when: `tools\build-css.bat` ra một file; build hai lần cho kết quả giống nhau; file chứa mọi class arbitrary liệt kê trong khảo sát (`z-[1000]`, `z-[60]`, `z-[1100]`, `z-[100]`, `z-[101]`, `min-h-[600px]`, `max-h-[90vh]`, `bg-[#143583]`…). (AC3)

### S6 [sonnet] Layout chung + chuyển nhap, no-line, huong-dan
Files: `app/Support/View.php`, `app/Http/Request.php`, `views/layouts/app.php`, `views/{nhap-nang-suat,no-line,huong-dan}.php`, 3 PageController tương ứng
Do: `View::page($template, $data, ['title', 'bodyClass', 'scripts'])`. Layout: meta, `<meta name="bns-base">`, một `<link>` `assets/tailwind/dist/app.css`, navbar, toast, slot nội dung, slot script cuối trang. Xoá CDN + config inline (nhap:11-26, no-line:10-25, huong-dan:9-24) và các `<link>` CSS tay (đã vào build). `<style>` của huong-dan (25-44) chuyển vào `public/assets/css/guide.css` nhập vào build; bỏ dòng sai `rounded: 0.25rem` (huong-dan:37). `no-line` đặt `bodyClass = 'theme-no-line'`. Biến navbar (`$navTitle`, `$navActive`, `$showAddBtn`…) truyền qua `$layout`.
Done when: 3 trang render; ảnh sau khớp ảnh gốc; `rg "cdn.tailwindcss|tailwind\.config" views/{nhap-nang-suat,no-line,huong-dan}.php` = 0. (AC1, AC2, AC3)

### S7 [sonnet] Chuyển bieu-do + viết lại index bằng Tailwind
Files: `views/bieu-do.php`, `views/index.php`, `BieuDoPageController.php`, `IndexPageController.php`, `public/assets/css/chart.css`, `public/assets/css/login.css`, `public/assets/js/login.js`
Do: bieu-do dùng layout, Chart.js từ `assets/vendor/` (thay bieu-do:10), `<style>` 26-64 sang `chart.css`. index: bỏ Bootstrap (index:10, :56) và Font Awesome (:11), dựng lại bằng utility Tailwind + SVG inline, giữ nguyên id/hành vi mà `login.js` dùng và khối JSON `#allowedLines` (:51); `login.js` bỏ phụ thuộc JS Bootstrap nếu có.
Done when: `rg -n "https?://" views` chỉ còn namespace SVG; chọn LINE ở index hoạt động; ảnh bieu-do khớp, index được người dùng chấp nhận (trang này đổi nguồn CSS hoàn toàn). (AC1, AC2)

### S8 [sonnet] Tách admin.php theo tab + bỏ handler inline ở admin
Files: `views/admin.php`, `views/admin/*.php` (mỗi tab và mỗi modal một partial), `AdminPageController.php`, `public/assets/js/admin.js`, `public/assets/js/modules/admin-utils.js`
Do: admin dùng layout; 11 modal (admin.php:505-875) thành partial, tên khớp module JS trong `modules/admin/`. 33 `closeModal('<id>')` → `data-action="close-modal" data-target="<id>"`; 11 `event.stopPropagation()` thay bằng kiểm `event.target === overlay` trong listener uỷ quyền; `showAssignLinesModal` (admin.php:766) → `data-action`. `window.closeModal` (admin.js:7) giữ cho module đang gọi bằng JS.
Done when: `views/admin.php` < 150 dòng; `rg -n "\son\w+=" views/admin.php views/admin` = 0; mở/đóng cả 11 modal bằng nút X, nút Huỷ, bấm nền; ảnh khớp. (AC4, AC6)

### S9 [sonnet] Bỏ handler inline còn lại (nhap, components, history, user-lines)
Files: `views/nhap-nang-suat.php`, `components/confirm-modal.php`, `components/tabs.php`, `public/assets/js/app.js`, `public/assets/js/modules/history.js`, `public/assets/js/modules/admin/user-lines.js`
Do: nhap:166,170,174,211 và `closeModal` global nội tuyến (nhap:221-228) → `data-action` + listener trong app.js; form tạo báo cáo dùng `submit` listener. confirm-modal (:1,:5,:14), tabs (:37) tương tự; `switchTab` (tabs.php:62) giữ trong script của component nhưng gắn bằng listener. history.js sinh chuỗi onclick ở 105, 114, 150, 171, 179, 239 → `data-action` + `data-id`. user-lines.js:38 (ma_nv escape HTML đặt trong chuỗi JS) → `data-ma-nv`.
Done when: `rg -n "\son(click|change|submit)=" views components public/assets/js` = 0; tạo báo cáo, đóng modal, xem chi tiết lịch sử, mở khoá, gỡ user–LINE chạy như cũ. (AC4)

### S10 [sonnet] Xoá component/module chết + cắt CSS chết
Files: xoá 9 file `components/` (AC5) và `public/assets/js/modules/modal.js`; `public/assets/css/style.css`, `public/assets/css/login.css`
Do: xoá theo danh sách đã chứng minh không include (`modal-base` chỉ được `demo-modal` gọi; `line-preset-table` gọi `window.onAddLines`/`onRemoveLine` không tồn tại). style.css: xoá 30 selector không có nơi dùng (danh sách khảo sát, trừ `status-completed`, `status-submitted` sinh từ `status-${…}` ở app.js:350, history.js:123) sau khi `rg -w` từng class trên `views components public/assets/js app`; xoá khối tự ghi là đã để Tailwind lo (style.css:73, 170-181) nếu ảnh không đổi. Bỏ `resetCsrfToken`, `formatGio` không ai import.
Done when: build lại, ảnh 6 trang khớp; mỗi class đã xoá có dòng `rg` 0 kết quả trong báo cáo step. (AC5)

### S11 [sonnet] config.js + một escapeHtml
Files: `public/assets/js/modules/config.js`, `api.js`, `admin-api.js`, `admin/import.js`, `login.js`, `realtime-service.js`, `utils.js`, `grid.js`, `admin-utils.js`, `app.js`, `bieu-do.js`, `views/partials/navbar.php`
Do: `BASE_PATH` đọc `<meta name="bns-base">`, `API_BASE = BASE_PATH + '/api'`. Thay 4 định nghĩa `API_BASE`, đường dẫn Worker (realtime-service.js:13), import tuyệt đối ở navbar.php:243 (dùng đường dẫn tương đối `assets/js/...`). `escapeHtml` duy nhất ở utils.js; xoá bản ở app.js:9, bieu-do.js:802 (bản này không escape nháy); grid.js và admin-utils.js re-export.
Done when: AC9 hai lệnh `rg` đạt; trang chạy đúng ở `localhost/baonangsuat`. (AC9)

### S12 [sonnet] Escape các điểm chèn dữ liệu thô
Files: `public/assets/js/app.js`, `modules/history.js`, `modules/admin/import-history.js`, `modules/admin/permissions.js`, `bieu-do.js`, `components/toast.php`
Do: đổi `session.role === 'admin'` thành `session.vai_tro === 'admin'` ở app.js:298, 332, 772 (Scope mục 9). Escape tại app.js:279, 344-350 (cả `data-*` ở 344), 378-379, 386, 397; history.js:115-117; import-history.js:206-207; permissions.js:75; bieu-do.js:1067. toast.php:92 dựng node bằng `textContent` thay vì chèn `${message}`. Không đổi nội dung hiển thị với dữ liệu thường.
Done when: dữ liệu chứa `<b>x</b>` hoặc `"` hiển thị nguyên văn ở danh sách báo cáo, lịch sử, toast; không còn điểm nào trong danh sách trên chèn thô. (AC9)

### S13 [sonnet] Tách app.js
Files: `public/assets/js/app.js`, `public/assets/js/modules/nhap/{report-list,create-modal,editor,po-editor,autosave,workflow}.js`
Do: theo nhóm phương thức đã khảo sát: danh sách (283-362), modal tạo (363-581), editor/header (582-675, 753-820), PO (676-752), auto-save + version (821-927), workflow (928-994). `NangSuatApp` còn init, context, điều hướng tab, phím tắt; `window.app` giữ `closeModal`, `createReport`, `router`. Chỉ di chuyển, không đổi logic.
Done when: `app.js` < 500 dòng; thử tay: tải context, tạo báo cáo có/không PO, nhập ô và auto-save, đổi PO/subart, submit, complete, reopen, Ctrl+S, mất mạng rồi có lại. (AC6)

### S14 [sonnet] Tách bieu-do.js
Files: `public/assets/js/bieu-do.js`, `public/assets/js/modules/bieu-do/{filters,data,charts,matrix}.js`
Do: filters/URL (39-395), gọi API (396-558, 896-940, 1031-1059), vẽ chart (597-837, 1098-1412), bảng/ma trận (559-596, 941-1097, 1413-1468). Chỉ di chuyển.
Done when: `bieu-do.js` < 500 dòng; 3 kiểu xem (tổng quan, công đoạn, ma trận), đổi loại biểu đồ, back/forward trình duyệt giữ bộ lọc. (AC6)

### S15 [opus] Container autowire + Router
Files: `app/Support/Container.php`, `app/Http/Router.php`, `tests/php/container_test.php`, `tests/php/structure_test.php`
Do: `Container::get(class)` dựng đệ quy theo type constructor, tham số có default dùng default, singleton theo class, lỗi rõ khi không dựng được. Router dùng Container ở 114, 139, 177. Giữ lười: service chỉ dựng khi controller của route được gọi. `structure_test` thêm khoá: không `new \w+Service(` trong `app/Controllers` (bật sau S19). Orchestrator làm vì thay đổi xuyên mọi request.
Done when: `router_test` + `container_test` PASS; smoke khớp. (AC7, AC8)

### S16 [sonnet] Tách LuyKeCalculator + DanhMucLookup
Files: `app/Services/LuyKeCalculator.php`, `app/Services/DanhMucLookup.php`, `app/Services/NangSuatService.php`, `tests/php/luy_ke_test.php`
Do: `LuyKeCalculator` thuần: 512-575 và phần dựng kết quả 583-660 (nhận `$baoCao`, `$now`). `DanhMucLookup`: `getContext`, `getLine`, `getCaList`, `getMocGioList`, `getMaHangList`, `getRouting` (23-154). `NangSuatService` uỷ quyền sang hai class.
Done when: golden test S1 PASS không sửa test; test tích hợp S3 PASS. (AC6, AC8)

### S17 [sonnet] Tách BaoCaoService, BaoCaoWorkflowService, BaoCaoPoService
Files: 3 class mới trong `app/Services/`, `app/Services/NangSuatService.php`
Do: Po: `chuanHoaPoSubart` (173-225), `getKhoiPo` (380-491), nhận `DatchiPoService` qua constructor (thay `new` ở 202, 392). BaoCao: create/get/list/bulk/delete, `getEntries`, `updateEntries`, `updateHeader`, `daCoBaoCao`, `preGenerateEntries`. Workflow: submit/approve/unlock/complete/reopen, `changeTrangThai` (841-962). Giữ nguyên transaction, thứ tự truy vấn, message, key `status`, mặc định `so_lao_dong`/`ctns` = 0 của `updateHeader` (KNOWLEDGE).
Done when: không file nào > 500 dòng; test S1, S3 PASS không sửa. (AC6)

### S18 [sonnet] Controller nghiệp vụ nhận service qua constructor
Files: `BaoCaoController`, `ContextController`, `DanhMucController`, `BieuDoController`, `MocGioSetsController`, `BaoCaoHistoryController`, `PoSubartController`; xoá `NangSuatService.php`
Do: constructor property promotion, bỏ mọi `new XxxService()`. `AdminController::bulkCreateBaoCao` (AdminController.php:303) chuyển sang `BaoCaoService` ở S19.
Done when: `rg "new \w+Service\(" ` trên 7 file = 0; smoke đăng nhập khớp baseline. (AC7)

### S19 [sonnet] Bỏ facade AdminService
Files: `app/Controllers/AdminController.php`, `app/Services/LineService.php`, xoá `app/Services/AdminService.php`
Do: AdminController nhận Line/User/MaHang/CongDoan/Routing/MocGio service + `BaoCaoService`. `lines()` gọi `LineService::getListSynced()` (đồng bộ rồi list, giữ side-effect). Bật khoá `structure_test` của S15.
Done when: AC7 đạt cho AdminController; `GET /api/admin/*` trong smoke đăng nhập khớp baseline. (AC7)

### S20 [sonnet] Tách ImportService
Files: `app/Services/ImportExcelParser.php`, `app/Services/ImportHistoryService.php`, `app/Services/ImportService.php`, `ImportController.php`, `ImportHistoryController.php`, `tests/php/import_parser_test.php`
Do: Parser: 470-622 + `formatMaCongDoan`. History: `saveImportHistory`, `getImportHistoryList`, `getImportHistoryDetail`, `buildDetailsArray` (803-1064). Giữ: lịch sử ghi sau commit (409 → 420), `preview_stats`/`preview_errors` do client gửi (ImportController.php:70-82). Test parser gọi thẳng class mới, bỏ reflection.
Done when: `ImportService.php` < 500 dòng; test parser PASS; người dùng thử tay preview + confirm một file. (AC6)

### S21 [sonnet] UserPermissionService
Files: `app/Services/UserPermissionService.php`, `app/Controllers/UserPermissionsController.php`
Do: chuyển SQL của `grant` (dòng 43, 54, 66) và `revoke` (85) sang service, `ma_nv` vẫn `strtoupper(trim())`; response và mã HTTP giữ nguyên.
Done when: `rg "mysqli_|->prepare\(" app/Controllers` = 0; smoke đăng nhập `/api/user-permissions` khớp. (AC7)

### S22 [sonnet] Dọn tests/, gốc repo, .gitignore
Files: xoá `tests/{test-import-confirm,test-import-preview,verify-import,list-admin}.php`, `tests/router.spec.js`, `tailwind.config.js`, `tailwind.no-line.config.js`; `git mv tests/{generate-test-excel,check-routing-snapshot}.php tools/`; `.gitignore`
Do: `test-import-confirm.php` ghi DB thật → xoá; `generate-test-excel.php` sửa đường ghi sang `tests/fixtures/`. `.gitignore` thêm `.playwright-mcp/`. Sửa tham chiếu docs/how-routing-snapshot.md:156.
Done when: `ls tests` = `fixtures/ php/ smoke/`; test + smoke PASS. (AC5, AC10)

### S23 [sonnet] (doc) Lưu trữ docs cũ + cập nhật tài liệu sống
Files: `docs/archive/` (git mv các design/plan đã xong: admin-js-migration-plan, ajax-partial-update-plan, bao-cao-*-design, import-blocking-design, import-excel-design, ket-qua-luy-ke-design, lich-su-*-design, luy-ke-status-design, moc-gio-per-line-design, modal-system-design, nhap-nang-suat-router-plan, notification-toast-migration, router-architecture-design, tailwind-offline-design, plans/, superpowers/); `CLAUDE.md`, `AGENTS.md`, `README.md`, `.claude/rules/{tailwind-js,target-architecture,single-entry-point-routing,psr4-autoload}.md`, `docs/{deploy-checklist,how-routing-snapshot,import-excel-feature}.md`
Do: giữ ở `docs/`: `adr/`, `agents/`, `deploy-checklist.md`, `admin-setup-checklist.md`, `huong-dan-su-dung.md` (smoke URL), `how-routing-snapshot.md`, `import-excel-feature.md`. Sửa đường dẫn `classes/`, `includes/`, `api/index.php`; CLAUDE.md: shape `ket_qua_luy_ke` theo code thật, bảng service mới, layout, build một file, quy ước `data-action`; deploy-checklist thêm "dist đã commit, không cần build trên server".
Done when: AC10 lệnh `rg` = 0; mọi link nội bộ trong file đã sửa trỏ tới file tồn tại. (AC10)

### S24 [opus] Kiểm cuối
Do: gate đầy đủ (lint, cs-fixer intersection, unit, tích hợp với `BNS_DB_CONFIG`, smoke anon + đăng nhập), chụp lại bộ ảnh S4 và so, kiểm Network offline (chặn mọi host ngoài) trên 6 trang, chạy `verifier` trên toàn diff.
Done when: AC1…AC11 đạt hoặc khác biệt được liệt kê cho người dùng.

### S25 [opus] Update brain
Orchestrator cập nhật REGISTRY, SYSTEM (module mới, FE component, bỏ AdminService/NangSuatService), DECISIONS (các mục durable), KNOWLEDGE, BACKLOG (các mục Out).

## 8. Verification
- Commands: `php tests/php/run.php` · `BNS_DB_CONFIG=<config test> php tests/php/run.php` · `vendor/bin/php-cs-fixer fix --dry-run --path-mode=intersection <files>` · `php tools/smoke.php` · `php tools/smoke.php --cookie=<HTSSOSESSID>` · `tools\build-css.bat` rồi `git diff --exit-code public/assets/tailwind/dist`.
- Không tự động hoá được: so ảnh giao diện, thao tác ghi (tạo/nộp báo cáo, import confirm, cấp quyền) — rule cấm chạy bằng phiên thật, người dùng thử tay theo danh sách ở S8, S9, S13, S14, S20.
- Rủi ro verifier phải soi riêng:
  - Thứ tự layer: rule CSS tay specificity cao nay thua utility (modal, sticky column của grid, navbar).
  - Class Tailwind sinh động bị scanner bỏ sót (`z-[101]`, class truyền qua biến PHP).
  - Khác biệt v3→v4 không báo lỗi (shadow, outline, border, ring).
  - Tách service làm đổi thứ tự truy vấn/transaction, mất `status` trong mảng trả về, mất side-effect đồng bộ LINE.
  - Container dựng service sớm làm mở kết nối DB cho route không cần (so với hiện tại).
  - `BNS_DB_CONFIG` không được phép có hiệu lực qua request web (chỉ đọc `getenv` của tiến trình), helper test phải chặn DB không phải `*_test`.
  - Baseline smoke bị cập nhật che mất hồi quy: mỗi lần cập nhật phải kèm lý do và chỉ cho URL asset đã đổi.

## Open questions — đã chốt 2026-11-25 (người dùng trả lời)
1. Phiên thật: không cấp cookie. Agent chỉ kiểm tự động trang chưa đăng nhập và smoke anon; người dùng tự so bằng mắt các trang sau đăng nhập. S4 không chụp được ảnh sau đăng nhập → S24 giao danh sách kiểm tay; smoke `--cookie` bỏ qua, ghi rõ trong báo cáo.
2. DB test: được phép tạo `nang_suat_test` trên MySQL local + file config test ngoài repo.
3. `session.role`: sửa trong F-016 (S12).
4. `index.php`: viết lại bằng Tailwind, chấp nhận khác nhẹ.

Amended (verify): AC9 — `login.js` nạp dạng module và import `API_BASE` từ config.js. AC3 kiểm bằng `<head>` (navbar có `<header`). AC10 — còn 1 dòng có chủ đích ở docs/deploy-checklist.md (hướng dẫn xoá thư mục cũ trên server); `docs/tailwind-offline-design.md` viết lại và giữ ở docs/. S14 thêm state.js, format.js, chart-type.js. `View::page` nhận `string $layout`.
