# F-019 CSS: một nguồn token màu, cắt style.css, một cách ẩn phần tử
Status: APPROVED   <!-- bạn đổi thành APPROVED để /go chạy. DOC = chỉ giữ làm tài liệu, không chạy -->
Related: F-016 extends (bundle Tailwind v4, D-005) · F-017 depends on (`tools/computed-style-dump.js`) · F-018 depends on (toast thành module, bỏ nhánh `div.toast`)
Complexity: hard   <!-- đụng hợp đồng cascade D-005; phải chứng minh bằng computed style -->
Risk: normal
Session model: fable

## 1. Problem & goal
Màu thương hiệu `#143583` được khai ở ba nơi (`@theme` `--color-primary`, `style.css:8` `--primary-color`, `layout.css:4` `--bns-primary`) và viết cứng thêm ở 9 chỗ; `style.css` 747 dòng còn reset trùng preflight, khối chết và `.hidden` khai hai lần; phần tử được ẩn bằng hai cách (`.hidden` và `style="display:none"`) nên JS phải bật tắt cả hai.
"Xong": đổi màu thương hiệu chỉ sửa một dòng trong `src/app.css`; một cách ẩn duy nhất; không còn thuộc tính `style=`; giao diện không đổi (computed style trước/sau giống nhau, trừ các khác biệt liệt kê ở mục 3).

## 2. Scope
- In: gom token màu thương hiệu; cắt reset, khối chết, comment thừa trong `style.css`/`admin.css`/`entry.css`; `.hidden` còn một định nghĩa; bỏ thanh tab chết `#mainTabs`; chuyển mọi `style=` và `style.display` sang class; khoá thứ tự `@import` bằng test; siết CSP `style-src` nếu sạch.
- Out (cố ý không làm): thêm `@layer` (trái D-005); đổi tên/tách file CSS; bảng màu ngữ nghĩa riêng của màn nhập (`entry.css:4-9` `--bns-success`… là sắc độ khác, không phải bản sao); màu của trang chọn LINE (`views/index.php:9,15,20,26`, `login.js:44` — giao diện theo authV2); ~100 mã hex không phải màu thương hiệu; số lượng `!important` còn lại; nâng Tailwind.

## 3. Acceptance criteria
- AC1 Một nguồn màu thương hiệu.
  - `rg -n -i "#143583|#0f2a66|#0e255c|#4caf50|#ff9800|#f44336" public/assets/css public/assets/js views components -g "!vendor/**"` = 0; trong `public/assets/tailwind/src` chỉ còn ở khối `@theme` và `.theme-no-line` của `app.css`.
  - `style.css` `:root`: 5 biến `--primary-color, --primary-dark, --success-color, --warning-color, --danger-color` trỏ `var(--color-*)`; `layout.css`: `--bns-primary`, `--bns-primary-hover` trỏ `var(--color-primary)`, `var(--color-primary-dark)`.
  - Trang `no-line.php` (`<body class="theme-no-line">`) trước/sau giống nhau: thanh trên và nút "Đăng xuất" vẫn xanh đậm, utility `primary` vẫn xanh dương.
- AC2 `.hidden` có đúng một định nghĩa.
  - `rg -n "^\.hidden\b" public/assets/css` = 0; `rg -n "^\.hidden\b" public/assets/tailwind/src` = 1 (`display: none !important`, đặt sau `utilities.css`).
- AC3 Một cách ẩn, không còn inline style.
  - `rg -n "style=\"" views components public/assets/js -g "!vendor/**"` = 0.
  - `rg -n "\.style\.display" public/assets/js -g "!vendor/**"` = 0.
  - Mọi phần tử trước đây ẩn/hiện bằng `style.display` vẫn ẩn/hiện đúng lúc (danh sách ở S5, S6).
- AC4 `style.css` gọn.
  - Không còn: reset `* { margin: 0; padding: 0; box-sizing }` (dòng 1-5), khối `.toast*` + `@keyframes slideIn` (388-421), các khối S3 xác nhận không còn nơi dùng, các comment "Tailwind…" (78, 269-307), CSS của `#mainTabs`/`.main-tabs-container`/`.tab-btn`.
  - `rg -n "mainTabs|main-tabs-container|tabInput|tabHistory" views public/assets/js public/assets/css` = 0; chuyển Nhập ↔ Lịch sử qua sidebar vẫn chạy.
- AC5 Thứ tự import được khoá: test đọc `public/assets/tailwind/src/app.css` và so đúng dãy `@import` ở dòng 6-20 hiện tại.
- AC6 Giao diện không đổi: `tools/computed-style-dump.js` trên 5 trang (nhap-nang-suat, admin, bieu-do, huong-dan, no-line) × 2 bề rộng (1366, 390), trước và sau, khác biệt = rỗng, ngoại trừ được phép:
  - nền tiêu đề modal xác nhận khi rê chuột: `#0e255c` → `#0f2a66` (`style.css:638` dùng `--color-primary-dark`);
  - toast loại info trên trang no-line theo `primary` của theme (xanh dương) thay vì xanh đậm.
- AC7 `public/assets/tailwind/dist/app.css` build lại và commit; kích thước không tăng; `git diff --exit-code` sau khi chạy lại `tools\build-css.bat`.
- AC8 Nếu AC3 đạt và trình duyệt không báo vi phạm: CSP `style-src 'self'` (không `'unsafe-inline'`). Có vi phạm không gỡ được trong phạm vi → giữ nguyên CSP, ghi BACKLOG kèm nguồn vi phạm.
- AC9 Không hồi quy: `php tests/php/run.php` 0 fail; `php tools/smoke.php` khớp baseline (chụp lại trong commit đổi HTML/CSP, ghi lý do).

## 4. Contracts
- API, Data, Events: `=` không đổi.
- HTTP header: `~ Content-Security-Policy` `style-src` (owner F-016/F-018), chỉ khi AC8 đạt.
- CSS:
  - `= public/assets/tailwind/src/app.css` thứ tự `@import` (D-005) — giữ nguyên; `~` thêm một rule `.hidden` sau `utilities.css` (owner F-016).
  - `~ @theme` `--color-primary|primary-dark|success|warning|danger` (owner F-016): thành nguồn duy nhất; giá trị không đổi.
  - `~ style.css :root` (legacy): 5 biến màu thành bí danh; 4 biến còn lại (`--border-color, --header-bg, --cell-focus, --grid-border`) giữ nguyên.
  - `~ layout.css :root` (owner DESIGN.md / F-016): 2 biến `--bns-primary*` thành bí danh.
  - `- .hidden` ở `style.css:384` và `admin.css:37`; `- .toast*`; `- .main-tabs-container`, `.tab-btn`, `#mainTabs`.
  - `~ public/assets/tailwind/dist/app.css` (build lại).
- FE:
  - `- views/nhap-nang-suat.php:15-34` (thanh tab `#mainTabs`, `#tabInput`, `#tabHistory`) — đã bị `layout.css:237` ẩn vĩnh viễn; sidebar thay thế (DESIGN.md).
  - `~ app.js switchMainTab` (218-244): chỉ còn bật/tắt `#tabContentInput` / `#tabContentHistory` và thanh trên.
  - `~ modules/utils.js showLoading/hideLoading` (owner F-018): `overlay.style.pointerEvents` → class.
- Test: `+ tests/php/css_structure_test.php`.

## 5. Overlap check
| Việc liên quan | Chồng ở đâu | Quyết định |
|---|---|---|
| D-005 (bundle không layer, thứ tự import là hợp đồng) | Mọi thay đổi ở đây | reuse nguyên vẹn; thêm test khoá thứ tự (AC5); rule `.hidden` đặt sau utility, không layer |
| F-016 AC3 (no-line giữ primary `#2196F3` qua class theme) | Gom token dễ làm no-line đổi màu | route: dùng bí danh khai ở `:root` (xem Decisions) → no-line không đổi |
| F-018 (toast module, `utils.js`, `app.js`, CSP) | Cùng file `utils.js`, `app.js`, `SecurityHeaders.php` | depends: chạy sau F-018 |
| DESIGN.md (class `bns-` CSS thuần, sidebar thay tab) | `--bns-primary` và việc bỏ thanh tab | reuse: giữ tên `--bns-*`; bỏ thanh tab đúng theo DESIGN.md |
| KNOWLEDGE "[css] style.css định nghĩa .modal, .hidden, .line-list, .login-*" | `.hidden` chuyển chỗ | cập nhật khi đóng việc |
| KNOWLEDGE "[css] @utility trùng tên built-in thì cả hai cùng sinh" | Cách khai `.hidden` | route: viết CSS thường sau utilities, không dùng `@utility hidden` |
| BACKLOG "(debt) khối 'Tailwind handles it' trong style.css chưa cắt" | Trùng | đưa vào scope |
| Phát hiện sai ở bản đánh giá | `status-submitted`, `status-completed`, `bns-badge-ok/bad` KHÔNG chết: sinh bằng ghép chuỗi ở `nhap/report-list.js:116`, `history.js:146`, `report-view.js:139` | không xoá; S3 phải loại trừ tiền tố `status-`, `bns-badge-`, `hieu-suat-` |
| `components/tabs.php` | Không chết: `views/huong-dan.php:370` đang dùng | chỉ đổi 3 chỗ hex (dòng 33, 72, 80) sang `border-primary text-primary` |

Hàm/khối chỉ tới được qua thứ bị bỏ (P-006):
| Bị bỏ | Thứ phụ thuộc | Sau đó ở đâu |
|---|---|---|
| Thanh tab `#mainTabs` | `app.js:198-201` (hiện `#tabHistory`, `#mainTabs` khi có quyền lịch sử) | xoá 4 dòng; việc tạo `HistoryModule` ở 203-206 giữ nguyên |
| | `switchMainTab` đổi class `#tabInput/#tabHistory` (219-220, 225-228, 235-238) | xoá các dòng đó; phần bật/tắt nội dung và `renderAppBar/resetAppBar` giữ |
| | kiểm quyền xem lịch sử | vẫn ở `checkHistoryPermission` (`app.js:120`), không đổi |
| `.toast` CSS | nhánh dự phòng `div.toast` trong utils | đã bị F-018 S5 xoá |

## 6. Decisions
- Chọn: nguồn duy nhất là `@theme`; biến cũ thành bí danh khai ở `:root` (`--primary-color: var(--color-primary)`, `--bns-primary: var(--color-primary)`) (durable). Biến tuỳ biến được tính tại phần tử khai báo rồi mới kế thừa, nên `.theme-no-line` (đổi `--color-primary` trên `body`) không làm đổi các bí danh đã tính ở `:root` → giao diện no-line giữ nguyên. Bác bỏ: thay thẳng 30 chỗ `var(--primary-color)` bằng `var(--color-primary)` (no-line đổi sang xanh dương ở thanh trên, nút, tiêu đề); xoá `theme-no-line` (trái AC3 của F-016).
- Chọn: `.hidden { display: none !important; }` khai một lần trong `src/app.css` sau utilities (durable). Nhiều phần tử mang cả `hidden` lẫn `flex` (ví dụ `components/confirm-modal.php:1`, `components/loading-overlay.php:1`) và đang dựa vào `!important` của `style.css:385` để ẩn. Bác bỏ: chỉ dùng `.hidden` của Tailwind (thứ tự so với `.flex` trong utilities không bảo đảm → modal hiện ra).
- Chọn: thay `style=` bằng utility; chỗ nào utility thua specificity của CSS tay (ví dụ `style="font-weight:400"` trên `td.is-stick-r` ở `grid.js:114`) thì thêm rule có tên vào file CSS tay sở hữu thành phần đó. Bác bỏ: thêm `!important`; giữ inline.
- Chọn: xoá hẳn thanh tab `#mainTabs` thay vì giữ markup bị `display:none !important`. Bác bỏ: giữ "phòng khi cần" (sidebar đã thay, DESIGN.md).
- Chọn: không đặt chỉ tiêu số dòng cho `style.css`; tiêu chí là "không còn rule không nơi dùng" + computed style không đổi. Bác bỏ: chỉ tiêu dòng (dễ cắt nhầm).

## 7. Plan
Mỗi bước đụng CSS/view/JS: chạy `tools\build-css.bat`, rồi so computed style với bản "trước" (S2). Khác biệt ngoài danh sách AC6 → bước đó chưa xong.

### S1 [sonnet] (test) Test cấu trúc CSS cho mục 3
Files: `tests/php/css_structure_test.php` (mới)
Do: theo kiểu `tests/php/structure_test.php`. Test: (AC1) regex 6 mã hex trên `public/assets/css`, `public/assets/js` (bỏ vendor), `views`, `components` = rỗng; trong `tailwind/src` chỉ `app.css` có. (AC2) số dòng khớp `^\.hidden\b`: 0 trong `public/assets/css`, 1 trong `tailwind/src`. (AC3) không `style="` trong views/components/JS; không `.style.display` trong JS. (AC4) không chuỗi `mainTabs|main-tabs-container|tabInput|tabHistory`. (AC5) dãy `@import` của `src/app.css` đúng bằng 13 dòng hiện có (dòng 6-20).
Done when: `php tests/php/run.php css_structure` chạy; AC5 PASS (khoá hiện trạng), AC1-AC4 FAIL.

### S2 [opus] Chụp computed style "trước"
Files: ngoài repo (thư mục scratch), dùng `tools/computed-style-dump.js`
Do: dựng trang đã đăng nhập theo KNOWLEDGE ([test] PHP built-in server + phiên giả + DB `*_test`; DB test cần bảng giả HR cho tab LINE/User). Chạy dump 5 trang × 2 bề rộng; với admin chụp thêm từng tab (12) và 1 modal sửa; với trang nhập chụp danh sách, màn nhập một báo cáo, lịch sử. Lưu JSON làm mốc. (Orchestrator làm: đây là thước đo của cả việc, phải chắc trạng thái trang giống nhau giữa hai lần chụp.)
Done when: chạy dump hai lần liên tiếp trên code chưa đổi cho khác biệt rỗng (thước đo ổn định).

### S3 [opus] Cắt style.css / admin.css / entry.css
Files: `public/assets/css/style.css`, `admin.css`, `entry.css`, `public/assets/tailwind/src/app.css`
Do: (1) Xoá reset dòng 1-5 của `style.css` (preflight ở sau đã đặt `margin/padding/box-sizing` cho `*`). (2) Chuyển `.hidden`: xoá `style.css:384-386` và `admin.css:36-39`, thêm rule ở `src/app.css` ngay sau `@import "./v3-space.css"` kèm một dòng comment lý do. (3) Xoá `.toast*` + `@keyframes slideIn` (388-421). (4) Với từng class còn lại trong 3 file: tìm nơi dùng theo ngữ cảnh class (`class="…"`, `classList.*`, `className`, `querySelector`, chuỗi ghép) trong `views/`, `components/`, `public/assets/js/`; loại trừ tiền tố ghép `status-`, `bns-badge-`, `hieu-suat-`, `luy-ke-`. Ứng viên đã thấy không có `class="…"` nào: `.alert` (137), `.header` + `.header h1` (152-164 và trong 2 khối `@media`), `.user-info` (166), `.loading-overlay` (243), `.spinner` (256), `excel-grid`, `report-header` (entry.css), `user-info-bar`, `user-name`, `separator` (admin.css). Xoá cái nào xác nhận không nơi dùng; `.user-info` còn được `app.js:247` `querySelector` → xoá luôn `renderHeader` (246-251) và lời gọi ở `app.js:193` nếu không view nào có phần tử đó. Ghi danh sách đã xoá / giữ lại kèm lý do vào báo cáo chạy. (5) Xoá comment "Tailwind…" (78, 269-307) và tiêu đề khối rỗng (143, 301, 627-632, 662-664). (Orchestrator làm: phán đoán cascade, sai là vỡ giao diện.)
Done when: AC2, AC4 (trừ phần mainTabs); so computed style: rỗng.

### S4 [sonnet] Gom token màu thương hiệu
Files: `public/assets/css/style.css` (7-12, 634-639), `layout.css` (4-5), `chart.css:28`, `guide.css:2,15`, `components/tabs.php` (33, 72, 80), `public/assets/js/modules/toast.js` (4 class `bg-[#…]` chép từ `components/toast.php:72-85`)
Do: `style.css :root`: 5 biến màu → `var(--color-primary)`, `var(--color-primary-dark)`, `var(--color-success)`, `var(--color-warning)`, `var(--color-danger)`; giữ khai báo tại `:root` (không dời xuống selector khác — xem Decisions). `layout.css`: 2 biến `--bns-primary*` tương tự. `.bg-navbar-theme` / `:hover` → `var(--primary-color)` / `var(--primary-dark)` (giữ `!important`). `chart.css:28`, `guide.css:2,15` → `var(--primary-color)`. `tabs.php`: `border-[#143583] text-[#143583]` → `border-primary text-primary` ở cả 3 chỗ (PHP dòng 33, JS dòng 72 và 80 — sau F-018 phần JS nằm ở `modules/tabs.js`). `toast.js`: `bg-[#4CAF50]` → `bg-success`, `bg-[#f44336]` → `bg-danger`, `bg-[#ff9800]` → `bg-warning`, `bg-[#143583]` → `bg-primary`.
Done when: test AC1 PASS; so computed style: chỉ 2 khác biệt được phép ở AC6; trang no-line trước/sau giống nhau.

### S5 [sonnet] Một cách ẩn — admin
Files: `views/admin/{modal-cong-doan.php:26,modal-line.php:20,modal-ma-hang.php:20,modal-moc-gio.php:28,modal-preset.php:28,tab-moc-gio.php:21,25,29,tab-routing.php:12}`; `modules/admin/{cong-doan.js:52,63,lines.js:51,61,ma-hang.js:63,73,moc-gio.js:50-83,152,163,presets.js:64,75,490,493,routing.js:11,23,94,106}`
Do: view: `style="display:none"` → thêm class `hidden`. JS: mỗi cặp `style.display = 'block'|'inline-block'|'flex'` / `'none'` → `classList.remove('hidden')` / `classList.add('hidden')` (hoặc `toggle('hidden', cond)`). Kiểm kiểu hiển thị tự nhiên của từng phần tử sau khi bỏ `hidden` đúng bằng giá trị cũ: `#copyDefaultBtn` (`tab-moc-gio.php:25`, có class `btn` → inline-block); `.checkbox-item` (`presets.js:490` đang ép `flex` — nếu class gốc không cho `display:flex` thì thêm `flex` vào nơi sinh HTML). `presets.js:489-493` đang làm cả hai cách → chỉ còn class.
Done when: `rg -n "style=\"|\.style\.display" views/admin public/assets/js/modules/admin` = 0; thêm mới / sửa ở 5 modal: nhóm "Hoạt động" chỉ hiện khi sửa; tab Mốc giờ: bảng, nút "Copy từ Default", thông báo fallback hiện đúng theo LINE chọn; tab Routing: bảng hiện sau khi chọn mã hàng, nhóm công đoạn ẩn khi sửa; ô tìm LINE trong modal gán lọc đúng.

### S6 [sonnet] Một cách ẩn — trang nhập, biểu đồ, navbar, HTML sinh từ JS
Files: `views/nhap-nang-suat.php:150,158`, `views/bieu-do.php:129,137,257`, `views/partials/navbar.php:111`, `components/loading-overlay.php:1`; `modules/nhap/{create-modal.js:33,46,editor.js:69,po-editor.js:78,report-list.js:105,110}`, `modules/grid.js:114,198,200`, `modules/report-view.js:35`, `modules/utils.js` (`overlay.style.pointerEvents`), `public/assets/css/entry.css` (rule có tên khi utility thua)
Do: ẩn/hiện như S5 (`#modalLineGroup`, footer ở `editor.js:69`; 3 khối "tạm ẩn" của biểu đồ chỉ đổi sang class `hidden`, không xoá). Inline khác → utility: `min-width:0` → `min-w-0`; `pointer-events: none` → class `pointer-events-none` và `showLoading/hideLoading` bật tắt class đó; `flex-basis: 100%` → `basis-full`; `text-align:center;padding:30px` → `text-center p-[30px]`; `cursor: pointer` → `cursor-pointer`; `padding-right:6px` → `pr-1.5`; `vertical-align:middle` → `align-middle`; `font-weight:400` (`grid.js:114`) và `color: var(--bns-warning)` (`report-view.js:35`) → rule có tên trong `entry.css` nếu utility không thắng CSS tay. Sau mỗi chỗ: computed style của phần tử đó bằng trước.
Done when: test AC3 PASS; so computed style: rỗng; overlay loading chặn click khi hiện và không chặn khi ẩn.

### S7 [sonnet] Bỏ thanh tab chết #mainTabs
Files: `views/nhap-nang-suat.php` (15-34), `public/assets/js/app.js` (198-201, 218-244), `public/assets/css/layout.css:237`, `public/assets/css/style.css` (425-427, 641-660)
Do: theo bảng P-006 ở mục 5. Xoá markup, 4 dòng hiện tab, các dòng đổi class tab trong `switchMainTab`, và CSS `#mainTabs`, `.main-tabs-container` (cả trong `@media`), `.tab-btn`, `.tab-btn.active::after`.
Done when: test AC4 PASS; sidebar "Nhập năng suất" ↔ "Lịch sử" chuyển đúng nội dung và thanh trên; người không có quyền lịch sử vào `#/lich-su` vẫn bị báo "Bạn không có quyền xem lịch sử".

### S8 [opus] Build, so computed style cuối, thử siết style-src
Files: `public/assets/tailwind/dist/app.css`, `app/Http/Middleware/SecurityHeaders.php:19`, `tests/smoke/baseline-anon.json`
Do: build, commit dist; so toàn bộ với mốc S2 (AC6). Đổi `style-src` thành `'self'`, mở 6 trang + đủ tab admin + vẽ biểu đồ (Chart.js) + toast + modal; có vi phạm CSP → tìm nguồn; gỡ được trong phạm vi file của spec thì gỡ, không thì hoàn tác dòng CSP và ghi BACKLOG (AC8). Chụp lại baseline smoke, ghi lý do. (Orchestrator làm: header bảo mật + phán quyết cuối về giao diện.)
Done when: AC6, AC7, AC8, AC9.

### S9 [sonnet] (doc) Cập nhật tài liệu CSS
Files: `CLAUDE.md` (mục "Page layout and CSS"), `.claude/rules/tailwind-js.md` (mục Tailwind), `DESIGN.md` (dòng nhắc `#143583`), `docs/tailwind-offline-design.md`
Do: ghi: nguồn màu duy nhất là `@theme`, biến cũ là bí danh khai ở `:root` và vì sao no-line không đổi; `.hidden` khai ở `src/app.css`; cấm `style=` và `style.display`, dùng class `hidden`; test khoá thứ tự import.
Done when: các file trên không còn câu trái với hiện trạng (`rg -n "style.css định nghĩa .*\.hidden" .claude/rules CLAUDE.md` = 0).

### S10 [opus] Update brain
REGISTRY, SYSTEM (Architecture rules: thêm "không `style=`; một nguồn màu"), DECISIONS (2 mục durable), KNOWLEDGE (sửa mục `.hidden` / `.login-*`), BACKLOG (đóng mục "Tailwind handles it"; thêm mục màu trang chọn LINE và `!important` còn lại).

## 8. Verification
- Commands: `php tests/php/run.php` · `tools\build-css.bat && git diff --exit-code public/assets/tailwind/dist` · `php tools/smoke.php` · dump computed style trước/sau.
- Rủi ro verifier phải soi riêng:
  1. Phần tử mang cả `hidden` và `flex/grid/block` vẫn ẩn (modal xác nhận, overlay loading, modal Tạo báo cáo, các modal admin).
  2. Trang no-line: thanh trên, nút Đăng xuất, tiêu đề không đổi màu.
  3. Class sinh bằng ghép chuỗi không bị xoá CSS (`status-*`, `bns-badge-*`, `hieu-suat-*`, `luy-ke-*`).
  4. Thước đo computed style có chụp cả trạng thái sinh từ JS (lưới nhập, danh sách báo cáo, tab admin), không chỉ HTML tĩnh.
  5. Reset bị xoá: phần tử `::before/::after` và control form không đổi `box-sizing`/`margin`.

## Open questions
Đã chốt với người dùng 2026-11-25:
1. Hai khác biệt màu ở AC6 được đồng bộ theo token (`.bg-navbar-theme:hover` dùng `--color-primary-dark`; toast info dùng `bg-primary`) — không giữ hex riêng.
Còn mặc định, chưa được trả lời:
2. S8 thử siết `style-src` ngay trong việc này (có vi phạm không gỡ được thì hoàn tác và ghi BACKLOG, theo AC8).
