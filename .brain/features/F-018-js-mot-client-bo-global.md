# F-018 JS: một API client, một bộ utils, bỏ global window.*, bỏ script inline
Status: APPROVED   <!-- bạn đổi thành APPROVED để /go chạy. DOC = chỉ giữ làm tài liệu, không chạy -->
Related: F-016 extends (tách JS, data-fn) · F-017 depends on (chạy sau khi đã LF) · F-019 depends on this
Complexity: hard   <!-- refactor ~40 file JS/view, đổi CSP -->
Risk: normal   <!-- đụng header CSP và luồng CSRF phía client, không đụng xác thực/dữ liệu; verifier soi riêng 2 điểm này -->
Session model: fable

## 1. Problem & goal
Tầng JS còn 4 bản logic fetch+CSRF hành vi khác nhau, 2 bộ utils trùng, 3 bản `showConfirmModal`, 26 hàm gán lên `window` rồi lại liệt kê trong whitelist, 8 khối `<script>` inline buộc CSP giữ `'unsafe-inline'`, và `confirm()` gốc lẫn với modal.
"Xong": thêm một nút admin chỉ sửa một chỗ; mọi request ghi đi qua một client tự lấy và tự làm mới CSRF token; không còn script inline thực thi; CSP `script-src 'self'`. Người dùng không thấy khác biệt ngoài hộp xác nhận thống nhất kiểu modal.

## 2. Scope
- In:
  1. Gộp `modules/api.js` + `modules/admin-api.js` + CSRF/upload trong `modules/admin/import.js:10-33` + fetch trong `login.js:8-22,57-62`.
  2. Gộp `modules/utils.js` + `modules/admin-utils.js`; một `showConfirmModal`; thay `confirm()`/`alert()`.
  3. Registry hành động admin thay `window.*` + `DATA_FN_ALLOWED`.
  4. Bỏ global `window.appContext / app / historyModule / bieuDoApp / initialParams / appConfig / closeModal / toast / bnsNav`.
  5. Chuyển 8 khối script inline thành module; siết CSP `script-src`.
  6. Tách `admin/import.js` (525 dòng) và `admin/presets.js` (503 dòng).
- Out (cố ý không làm): đổi URL/shape API; đổi hành vi nghiệp vụ; soát ~90 chỗ `innerHTML` (việc riêng); mẫu mixin của `NangSuatApp`; `style-src 'unsafe-inline'` (F-019); gộp `getStatusClass` ở `admin/import-history.js:348` với `luy-ke-config.js:42` (khác nghĩa, chưa kiểm).

## 3. Acceptance criteria
- AC1 Một API client.
  - `rg -n "\bfetch\(" public/assets/js -g "!vendor/**"` chỉ ra dòng trong `modules/api.js`.
  - Không còn `public/assets/js/modules/admin-api.js`; `rg -n "admin-api" public/assets/js` = 0.
  - Given token hết hạn, When gửi POST/PUT/DELETE và server trả `csrf_error: true`, Then client lấy token mới và gửi lại đúng một lần; lần hai vẫn lỗi thì trả kết quả lỗi cho nơi gọi.
  - Given chưa có token, When gọi request ghi đầu tiên (kể cả upload import), Then client tự lấy token trước khi gửi.
- AC2 Một bộ utils.
  - Không còn `modules/admin-utils.js`; `rg -n "admin-utils" public/assets/js` = 0.
  - `rg -n "function (showLoading|hideLoading|showToast|closeModal|showConfirmModal)\b|^\s+showConfirmModal\(.*\{" public/assets/js` = mỗi tên đúng 1 định nghĩa, đều trong `modules/utils.js`.
  - `rg -n "\b(confirm|alert)\(" public/assets/js -g "!vendor/**"` = 0.
  - Là tổ trưởng, khi bấm Chốt / Hoàn tất / Mở lại báo cáo hoặc (admin) Mở khoá ở lịch sử, tôi thấy modal xác nhận của app; bấm Hủy, nút X hoặc nền thì không gọi API.
- AC3 Admin không dùng global.
  - `rg -n "window\.[A-Za-z_]+\s*=" public/assets/js -g "!vendor/**"` = 0.
  - `rg -n "DATA_FN_ALLOWED|window\[" public/assets/js` = 0.
  - Given một tên `data-fn` chưa đăng ký, When click, Then không có gì chạy và không lỗi.
  - Cả 26 `data-fn` / `data-change-fn` hiện có (bảng mục 5) vẫn chạy đúng hàm cũ.
- AC4 Không còn script inline thực thi.
  - Trong `views/` và `components/`, mọi thẻ `<script` đều có `src=` hoặc `type="application/json"` (test cấu trúc kiểm).
  - Header CSP của trang: `script-src 'self'` (không `'unsafe-inline'`).
  - Mở 6 trang (index, nhap-nang-suat, admin đủ 12 tab, bieu-do, huong-dan, no-line): console 0 lỗi, 0 vi phạm CSP; sidebar thu/mở và nhớ trạng thái sau reload không nháy; đồng hồ server chạy; toast hiện; tab ở hướng dẫn đổi được.
- AC5 Không file JS nguồn nào trong `public/assets/js` (trừ `vendor/`) vượt 500 dòng.
- AC6 Không hồi quy: `php tests/php/run.php` 0 fail; cs-fixer 0 file; `php tools/smoke.php` khớp baseline (chụp lại trong cùng commit đổi CSP và commit đổi HTML trang, ghi lý do); `tools\build-css.bat` rồi `git diff --stat public/assets/tailwind/dist` không mất class đang dùng.

## 4. Contracts
- API: `=` toàn bộ `config/routes.php` (URL, method, shape, `csrf_error` giữ nguyên).
- Data: `=` mọi bảng.
- HTTP header: `~ Content-Security-Policy` của trang HTML (owner F-014/F-016, `app/Http/Middleware/SecurityHeaders.php:19`): `script-src 'self' 'unsafe-inline'` → `script-src 'self'`. `style-src` giữ nguyên.
- FE (đều `~` owner legacy/F-016 trừ khi ghi `+`):
  - `~ modules/api.js`: export `api(method, endpoint, data = null)`, `apiUpload(endpoint, formData)`, `fetchCsrfToken()`. Bỏ `getCsrfToken` (không nơi nào import — chỉ có định nghĩa ở `api.js:21`). Hành vi: `credentials: 'same-origin'`; POST/PUT/DELETE tự bảo đảm có token; có `data` thì gửi body JSON cho POST/PUT/DELETE (DELETE có body đang dùng ở `admin/user-lines.js:146`, `admin/presets.js:380`, `admin/po-subart.js:159`); `csrf_error` → làm mới token, gửi lại 1 lần; trả JSON đã parse.
  - `- modules/admin-api.js` (13 nơi import chuyển sang `api.js`).
  - `~ modules/utils.js`: giữ `escapeHtml, getStatusText, updateStatusBar`; `showLoading/hideLoading` = bản của admin-utils (khoá control trong modal đang mở, `admin-utils.js:22-66`) có kiểm null như `utils.js:30-33`; `showToast(message, type)` gọi module toast; `+ closeModal(id)`; `+ showConfirmModal(message, callback, title, variant, confirmText, cancelText)` (bỏ tham số `isHtml` nếu S4 xác nhận không nơi nào truyền); `+ confirmDialog(message, {title, variant, confirmText}) → Promise<boolean>`.
  - `- modules/admin-utils.js` (13 nơi import chuyển sang `utils.js`).
  - `+ modules/toast.js`: export `toast` ({show, success, error, warning, info, dismiss}) — nội dung từ `components/toast.php:10-153`, tự tạo `#toast-container` khi chưa có. `- components/toast.php` và 3 chỗ include (`views/partials/navbar.php:251`, `views/bieu-do.php:318`, `views/index.php:43`).
  - `+ modules/admin/actions.js`: `registerActions(map)`, `runAction(name, args) → boolean`.
  - `+ modules/page-data.js`: `readPageData(id = 'bnsPageData') → object` đọc `<script type="application/json">` (mẫu: `views/index.php:41` + `login.js:80-87`).
  - `+ modules/nav.js`: export `setActiveNav(key, updateTitle)`; tự gắn sự kiện sidebar và khởi động đồng hồ từ `data-server-time`. `+ public/assets/js/nav-early.js` (script thường, chạy trước khi vẽ).
  - `+ modules/tabs.js`: gắn sự kiện cho `components/tabs.php`.
  - `~ views/layouts/app.php` (owner F-016): tự thêm `assets/js/modules/nav.js` vào danh sách module khi trang có navbar.
  - `+ modules/admin/import-preview.js`, `+ modules/admin/preset-lines.js` (tách file).
- Test: `+ tests/php/fe_structure_test.php`.

## 5. Overlap check
| Việc liên quan | Chồng ở đâu | Quyết định |
|---|---|---|
| F-016 / D-007 (`data-action`/`data-fn`, không `on*=`) | Registry thay cách gọi hàm sau `data-fn` | extend, giữ nguyên thuộc tính HTML và `data-args`; không trái D-007 |
| F-016 (whitelist `DATA_FN_ALLOWED`, đang chưa commit ở `admin.js:254-285`) | Registry chính là whitelist | thay thế; yêu cầu commit bản hiện tại trước (F-017 Open question 1) |
| F-016 (CSP đã bỏ host CDN, `SecurityHeaders.php:19`) | Siết tiếp `script-src` | extend |
| F-016 (`views/layouts/app.php`, `View::page`) | Layout tự nạp `nav.js` | extend, không đổi khoá `$page` |
| F-002/F-004 (fe-app), F-012 (fe-chart), F-005 (fe-admin) | Đổi import, bỏ global | extend không đổi hành vi |
| SYSTEM "Shared FE components": dòng api/admin-api, utils/admin-utils | Hai dòng gộp lại | cập nhật khi đóng việc |
| KNOWLEDGE "[fe] confirm-modal.php dùng chung admin + nhập; mỗi trang tự bắt close-modal" | `confirmDialog` phải tự biết khi modal bị đóng | route: lắng nghe click trên chính `#confirmModal` (xem S4) |
| KNOWLEDGE "[fe] API trả session.vai_tro" | Thay `window.appContext` → `this.context` giữ nguyên khoá `vai_tro` | reuse |
| BACKLOG "(nit) window.toast render message qua innerHTML — toast.php:92" | Đã là `textContent` (`toast.php` dòng `el.querySelector('.break-words').textContent = message`) | đóng mục khi chuyển sang `toast.js` |
| BACKLOG "(debt) toast.php include 2 lần ở bieu-do; initialParams, historyModule không ai đọc; popstate nhánh chết" | Trùng | đưa vào scope (S5, S8) |
| BACKLOG "(debt) history.js showConfirmModal(..., isHtml) gán innerHTML" | Trùng | đưa vào scope (S4) |
| BACKLOG "(nit) updatePoSubart khi auto-save đang chạy dùng version cũ" | Cùng file nhưng là lỗi nghiệp vụ | không gộp |

Bảng 26 hành động admin phải còn chạy (nguồn `rg "data-(change-)?fn=" public/assets/js`):
| Module | data-fn | Dòng sinh HTML |
|---|---|---|
| lines.js | editLine, deleteLine | 32-33 |
| user-lines.js | deleteUserLine | 38 |
| permissions.js | toggleHistoryPermission, toggleCreateReportPermission, toggleCreateReportAnyLinePermission, toggleImportPermission (`data-change-fn`, nhận thêm `checked`) | 81-108 |
| ma-hang.js | editMaHang, deleteMaHang | 33-34 |
| cong-doan.js | editCongDoan, deleteCongDoan | 33-34 |
| routing.js | editRouting, deleteRouting | 55-56 |
| presets.js | viewPresetDetail, editPreset, showCopyPresetModal, deletePreset, unassignLine | 35-38, 278 |
| presets.js | showAssignLinesModal (qua `data-action="show-assign-lines"`, `admin.js:302-306`) | — |
| moc-gio.js | editMocGio, deleteMocGio | 124-125 |
| import-history.js | viewImportDetail, loadImportHistory, closeImportHistoryModal | 117, 146-158, 184, 312 |

## 6. Decisions
- Chọn: gặp `csrf_error` thì làm mới token và gửi lại 1 lần (durable). An toàn vì middleware `csrf` chặn trước khi vào controller nên request đầu chưa ghi gì. Bác bỏ: chỉ làm mới token rồi trả lỗi như `api.js:45-47` hiện nay (người dùng phải bấm lại; admin-api còn không làm mới).
- Chọn: `showLoading` thống nhất theo bản admin (khoá control của modal đang mở). Trang nhập vì thế cũng khoá nút trong modal Tạo báo cáo khi đang gửi — chống bấm đúp. Bác bỏ: hai bản; thêm cờ tuỳ chọn (không ai cần tắt).
- Chọn: registry do từng module tự đăng ký lúc nạp (durable): `registerActions({ editLine, deleteLine })` ở cuối mỗi module; `admin.js` chỉ gọi `runAction`. Bác bỏ: giữ `window.*` + whitelist (3 chỗ phải sửa khi thêm nút); gắn listener riêng từng nút (trái mẫu uỷ quyền D-007).
- Chọn: `confirmDialog()` trả Promise để `workflow.js` giữ luồng tuần tự (lưu → hỏi → gọi API). Bác bỏ: callback lồng.
- Chọn: toast thành ES module, tự tạo container (durable); không còn `window.toast`. Bác bỏ: giữ script thường + global (vẫn là script inline nếu để trong component).
- Chọn: dữ liệu PHP → JS qua `<script type="application/json">` + `readPageData()` (durable), như `index.php:41`. Bác bỏ: `window.appConfig = {...}` inline (chặn CSP, `views/bieu-do.php:323` còn in `line_id` không qua json_encode).
- Chọn: script chống nháy sidebar là file thường `nav-early.js` nạp đồng bộ ngay đầu `<body>` (vị trí `navbar.php:97`). Bác bỏ: module (chạy sau khi vẽ → nháy); đặt class từ PHP (trạng thái thu gọn nằm ở localStorage).
- Chọn: giữ lệnh gọi `fetchCsrfToken()` làm nóng ở `app.js:59`, `bieu-do.js:19`. Bác bỏ: bỏ đi (tăng độ trễ lần lưu đầu tiên).

## 7. Plan
Thứ tự bắt buộc S1 → S13. Mỗi bước xong: `node --check` từng file JS đã chạm (Node 22; nếu báo lỗi cú pháp `import` ở file .js thì chạy `node --input-type=module --check < file` — chưa kiểm cách nào đúng trên máy này, bước S1 xác định và ghi vào test).

### S1 [sonnet] (test) Test cấu trúc FE cho mục 3
Files: `tests/php/fe_structure_test.php` (mới)
Do: theo kiểu `tests/php/structure_test.php` (hàm `test()`, `assertSame([], $found)`). Quét `public/assets/js` (bỏ `vendor/`), `views/`, `components/`. Mỗi tiêu chí một test: (AC1) `fetch(` chỉ trong `modules/api.js`; không có file `admin-api.js`. (AC2) không có `admin-utils.js`; không `confirm(`/`alert(` (regex `\b(confirm|alert)\(`). (AC3) không dòng nào khớp `window\.[A-Za-z_]+\s*=` hoặc `window\[` trong JS. (AC4) mọi `<script` trong views/components có `src=` hoặc `type="application/json"`; `SecurityHeaders.php` không chứa `'unsafe-inline'` trong đoạn `script-src`. (AC5) không file JS > 500 dòng. Thêm test chạy `node --check` cho mọi file JS, `skip()` khi không có `node`.
Done when: `php tests/php/run.php fe_structure` chạy được và FAIL ở cả 5 nhóm vì code chưa đổi.

### S2 [sonnet] API client hợp nhất
Files: `public/assets/js/modules/api.js`, `modules/admin/import.js` (dòng 4, 10-34), `public/assets/js/login.js` (dòng 6-22, 52-62)
Do: viết lại `api.js` theo mục 4. `ensureToken()` nội bộ (lấy khi chưa có), `fetchCsrfToken()` ép lấy mới. `apiUpload` không đặt `Content-Type` (để trình duyệt tự đặt boundary). `import.js`: xoá `getCsrfToken` và thân fetch của `uploadForPreview`, gọi `apiUpload('/import/preview', formData)`; bỏ import `API_BASE` nếu hết dùng. `login.js`: bỏ `fetchLoginCsrfToken`/`loginCsrfToken`, gọi `api('POST', '/auth/select-line', { line_id })`; giữ xử lý `result.redirect_url` và thông báo lỗi. CHƯA xoá `admin-api.js`.
Done when: test AC1 phần `fetch(` còn fail đúng vì `admin-api.js` (2 dòng), các chỗ khác hết; mở trang index chọn LINE được; nhập 1 ô ở trang nhập lưu được (DB test).

### S3 [haiku] Chuyển 13 nơi import admin-api → api, xoá admin-api.js
Files: `public/assets/js/admin.js:2`, `modules/admin/{bulk-create,cong-doan,import-history,import,lines,ma-hang,moc-gio,permissions,po-subart,presets,routing,user-lines}.js:1`, xoá `modules/admin-api.js`
Example: `public/assets/js/modules/nhap/editor.js:1` (`import { api } from '../api.js';`)
Do: đổi đúng chuỗi đường dẫn `admin-api.js` → `api.js` ở dòng import; `git rm` file cũ. Không sửa gì khác.
Check: `rg -n "admin-api" public/assets/js` = 0 VÀ `php tests/php/run.php fe_structure` nhóm AC1 pass.

### S4 [sonnet] Utils hợp nhất, một modal xác nhận, bỏ confirm()
Files: `modules/utils.js`, `modules/history.js` (273, 299-391), `modules/nhap/workflow.js` (12, 33, 54), `modules/admin/import.js` (390, chỉ để kiểm tham số), `components/confirm-modal.php` (chỉ đọc)
Do: chuyển vào `utils.js`: `closeModal` (`admin-utils.js:68-70`), `showConfirmModal` (`admin-utils.js:72-133`), `showLoading/hideLoading` bản admin + kiểm null `.transform`. Kiểm lệnh gọi nhiều dòng ở `admin/import.js:390`: nếu không truyền `isHtml = true` thì bỏ hẳn tham số và nhánh `innerHTML`; nếu có thì giữ và ghi chú nơi gọi phải escape. Thêm `confirmDialog`: mở modal, resolve `true` khi bấm `#confirmBtn` (đồng thời đóng modal), resolve `false` khi có click vào phần tử `[data-action="close-modal"][data-target="confirmModal"]` bên trong `#confirmModal` (nút Hủy, nút X, nền — xem `components/confirm-modal.php:1,5,15`); gỡ listener sau khi resolve. `history.js`: xoá method `showConfirmModal` + `closeConfirmModal` (324-391), `deleteReport` (299-322) và `unlockReport` (272-275) dùng hàm của utils. `workflow.js`: ba `confirm(...)` → `await confirmDialog(...)`, giữ nguyên thứ tự "lưu thay đổi trước rồi mới hỏi" ở `submitReport` (dòng 8-12) và giữ nguyên câu chữ.
Done when: test AC2 phần `confirm(` chỉ còn fail vì `login.js:28` (xử lý ở S5); trên trang nhập bấm Chốt → modal hiện, Hủy/X/nền không gọi API, Xác nhận gọi đúng 1 lần.

### S5 [sonnet] Toast thành module
Files: `modules/toast.js` (mới), `modules/utils.js` (`showToast`), `public/assets/js/login.js` (24-30), `modules/network-monitor.js` (126-127), `components/toast.php` (xoá) + `views/partials/navbar.php:251`, `views/bieu-do.php:318`, `views/index.php:43`
Do: chép nguyên logic `components/toast.php:11-153` vào `toast.js` (gộp toast trùng, badge đếm, `textContent` cho message), thêm tạo `#toast-container` với đúng class ở `toast.php:6` khi chưa có. `utils.showToast` gọi `toast.show`; xoá nhánh dự phòng tạo `div.toast` (`utils.js:60-70`). `login.js`: `notifyError` gọi `toast.error`, bỏ `alert`. `network-monitor.js` import `toast`. Xoá component và 3 dòng include. Chạy `tools\build-css.bat` (class toast đổi nơi chứa nhưng vẫn trong `@source` `public/assets/js`).
Done when: test AC2 `confirm|alert` pass; `rg -n "window\.toast|components/toast" public/assets/js views components` = 0; toast hiện ở cả 4 trang có dùng (index, nhập, admin, biểu đồ); chụp lại baseline smoke (HTML trang đổi).

### S6 [haiku] Chuyển 13 nơi import admin-utils → utils, xoá admin-utils.js
Files: `public/assets/js/admin.js:3`, `modules/admin/{bulk-create,cong-doan,import-history,import,lines,ma-hang,moc-gio,permissions,po-subart,presets,routing,user-lines}.js:2`, xoá `modules/admin-utils.js`
Example: `public/assets/js/modules/nhap/create-modal.js:2` (`from '../utils.js'`)
Do: đổi đúng chuỗi `admin-utils.js` → `utils.js` ở dòng import; `git rm` file cũ.
Check: `rg -n "admin-utils" public/assets/js` = 0 VÀ nhóm AC2 của `fe_structure` pass VÀ `node --check` 13 file.

### S7 [sonnet] Registry hành động admin (phần 1: khung + 3 module)
Files: `modules/admin/actions.js` (mới), `public/assets/js/admin.js` (7, 13-150, 254-307), `modules/admin/{lines,cong-doan,ma-hang}.js`
Do: `actions.js`: `Map` nội bộ; `registerActions(map)`; `runAction(name, args = [])` trả `false` khi chưa đăng ký. Ba module gọi `registerActions({...})` ở cuối file với đúng các tên trong bảng mục 5. `admin.js`: `callDataFn` thử `runAction` trước, chưa có thì dùng đường cũ (tạm, gỡ ở S8); bỏ các dòng `window.editLine…` của 3 module đó.
Done when: tab LINE, Công đoạn, Mã hàng: nút Sửa/Xoá chạy; các tab khác vẫn chạy qua đường cũ.

### S8 [sonnet] Registry hành động admin (phần 2) + bỏ global còn lại của admin
Files: `public/assets/js/admin.js`, `modules/admin/{user-lines,permissions,routing,presets,moc-gio,import-history}.js`
Do: 6 module còn lại đăng ký theo bảng mục 5 (`import-history.js`: thay 3 dòng 414-416). `admin.js`: xoá `DATA_FN_ALLOWED`, đường cũ `window[name]`, `window.closeModal` (dòng 7), mọi `window.x = m.x`; nhánh `show-assign-lines` (302-306) gọi `runAction('showAssignLinesModal', [detail.id])`; `data-change-fn` vẫn nối thêm `checked` làm tham số cuối.
Done when: nhóm AC3 của `fe_structure` chỉ còn fail vì file ngoài admin; đi đủ 12 tab, mỗi hành động trong bảng mục 5 chạy; click `data-fn="khongCo"` (sửa tạm DOM) không lỗi.

### S9 [sonnet] Bỏ global ở trang nhập và biểu đồ
Files: `public/assets/js/app.js` (99-117, 121, 191-205, 267), `modules/nhap/{create-modal,report-list,editor}.js`, `modules/history.js` (118, 259), `views/nhap-nang-suat.php` (196-202); `public/assets/js/bieu-do.js:31`, `modules/bieu-do/{state.js:15,filters.js:290}`, `views/bieu-do.php` (320-326), `modules/page-data.js` (mới)
Do: mixin (`create-modal`, `report-list`, `editor`, `app.js`): `window.appContext` → `this.context` (đã gán ở `app.js:192`); `history.js`: → `this.app.context`. Xoá `window.appContext =`, `window.historyModule =`, `window.app =` (giữ `new NangSuatApp()`), `window.bieuDoApp =`. Xoá khối `window.initialParams` ở view (không nơi nào đọc; khối này còn đọc `$_GET` trong view). Rút gọn nhánh `popstate` `app.js:102-116` thành một điều kiện tương đương, bỏ comment. `views/bieu-do.php`: thay khối inline bằng `<script id="bnsPageData" type="application/json">` in `json_encode(['isAdmin' => …, 'lineId' => …, 'lineTen' => …], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)`; `state.js` và `filters.js` đọc qua `readPageData()` (giữ mặc định `isAdmin = false` khi thiếu). Hai nhóm file (nhập / biểu đồ) độc lập, làm lần lượt trong một lượt.
Done when: nhóm AC3 pass; trang nhập: danh sách, tạo báo cáo, mở báo cáo, lịch sử chạy; trang biểu đồ: người không phải admin vẫn bị khoá bộ lọc LINE đúng LINE của mình.

### S10 [sonnet] Navbar, tabs, loading-overlay: script inline → module
Files: `modules/nav.js`, `public/assets/js/nav-early.js`, `modules/tabs.js` (mới); `views/partials/navbar.php` (97-101, 170, 178-248), `views/layouts/app.php`, `components/tabs.php` (60-85), `components/loading-overlay.php` (21-24), `views/huong-dan.php` (khai `modules`), `public/assets/js/admin.js:239`
Do: `nav-early.js` = 2 lệnh ở `navbar.php:99-100`, nạp bằng `<script src>` đồng bộ đúng vị trí cũ (dùng closure `$asset` của layout, navbar được include trong cùng scope). `nav.js` = IIFE `navbar.php:179-241` + khởi động đồng hồ; `#server-clock` thêm `data-server-time="<?= time() ?>"` thay cho `realtimeService.init(<?php echo time(); ?>)` (243-248); export `setActiveNav`. Layout: khi `($page['nav'] ?? true) !== false` thì thêm `assets/js/modules/nav.js` vào đầu danh sách module. `admin.js:239` import `setActiveNav`. `tabs.js`: logic `tabs.php:61-84` áp cho mọi container có `data-tabs` (thêm thuộc tính vào thẻ gốc của component); `huong-dan.php` thêm `'modules' => ['assets/js/modules/tabs.js']`. Xoá khối script rỗng trong `loading-overlay.php`.
Done when: test "mọi `<script` có src hoặc json" pass; reload trang khi sidebar đang thu gọn không nháy; mục menu đang xem được tô đúng ở trang nhập (hash `#/lich-su`) và admin; đồng hồ chạy; chụp lại baseline smoke.

### S11 [opus] Siết CSP script-src và kiểm trên trình duyệt
Files: `app/Http/Middleware/SecurityHeaders.php:19`, `tests/smoke/baseline-anon.json`
Do: bỏ `'unsafe-inline'` khỏi `script-src`. Dựng trang đã đăng nhập theo KNOWLEDGE ([test] PHP built-in server + phiên giả + `BNS_DB_CONFIG` DB `*_test`), dùng Playwright mở 6 trang ở 1366 và 390, đọc console. Chụp lại baseline, ghi lý do. (Orchestrator làm: header bảo mật, sai là trắng trang mà test PHP không bắt.)
Done when: AC4 đủ; nhóm AC4 của `fe_structure` pass.

### S12 [sonnet] Tách import.js và presets.js
Files: `modules/admin/import.js` → `+ import-preview.js` (`renderPreview`, dòng 110-345); `modules/admin/presets.js` → `+ preset-lines.js` (`showAssignLinesModal`, `handleAssignLines`, `unassignLine`, `renderAssignedLinesSection`, `showCopyPresetModal`, `handleCopyPreset`: dòng 252-458 và listener liên quan trong `bindEvents` 460-498); `public/assets/js/admin.js` (khai module nếu cần)
Do: chuyển nguyên văn, chỉ thêm import/export. `preset-lines.js` tự `registerActions` cho `showAssignLinesModal, unassignLine, showCopyPresetModal`; `presets.js` import nó (không thêm mục `deps` mới trong `admin.js` trừ khi cần thứ tự nạp).
Done when: AC5 pass; tab Preset (xem chi tiết, gán/bỏ gán LINE, copy) và tab Import (chọn file → xem trước) chạy.

### S13 [sonnet] (doc) Cập nhật tài liệu FE
Files: `CLAUDE.md` (mục Frontend Module System, Security Patterns), `AGENTS.md`, `.claude/rules/tailwind-js.md` (mục JavaScript), `.claude/rules/single-entry-point-routing.md` (nếu nhắc CSP)
Do: sửa các câu nói về `api.js / admin-api.js`, `admin-utils`, `window.toast`, `bindGlobalEvents` + `data-fn` (thêm: đăng ký qua `modules/admin/actions.js`), script inline (cấm; dữ liệu qua `application/json`), CSP.
Done when: `rg -n "admin-api|admin-utils|window\.toast" CLAUDE.md AGENTS.md .claude/rules` = 0.

### S14 [opus] Update brain
REGISTRY, SYSTEM (bảng Shared FE components, dòng Architecture rules về script inline), DECISIONS (các mục durable), KNOWLEDGE (bỏ bẫy "mỗi trang tự bắt close-modal" nếu hết đúng), BACKLOG (đóng 3 mục đã gộp).

## 8. Verification
- Commands: `php tests/php/run.php` · `vendor/bin/php-cs-fixer fix --dry-run` · `php tools/smoke.php` · `tools\build-css.bat` · `node --check` từng file JS.
- Thao tác ghi chỉ thử trên DB `*_test` với phiên giả; không dùng phiên thật (rule refactor-verification-gates).
- Rủi ro verifier phải soi riêng:
  1. Gửi lại sau `csrf_error` đúng 1 lần, không lặp vô hạn; upload import gửi lại được (FormData dùng lại).
  2. `confirmDialog` luôn resolve (kể cả đóng bằng nền/X) và không để lại listener cũ khiến lần sau bấm một lần gọi API hai lần.
  3. `showLoading` bản mới ở trang nhập: control bị khoá trong modal Tạo báo cáo được mở lại sau `hideLoading` kể cả khi API lỗi.
  4. CSP: không còn script inline nào bị bỏ sót (kể cả HTML sinh từ JS); khối `application/json` không bị chặn.
  5. Thứ tự nạp: `nav-early.js` chạy trước khi vẽ; `nav.js` có trước khi `admin.js` gọi `setActiveNav`.
  6. Bộ lọc LINE ở trang biểu đồ cho người không phải admin (trước đây lấy từ `window.appConfig`).

## Open questions
Không còn. Đã chốt với người dùng 2026-11-25:
1. Chốt / Hoàn tất / Mở lại / Mở khoá dùng modal xác nhận của app thay `confirm()` (AC2, S4).
2. `showLoading` ở trang nhập khoá control trong modal đang mở, như admin (S4).
