# Decisions — vì sao hệ thống như hiện tại
<!-- ADR rút gọn.
     GHI khi: chọn thư viện, kiến trúc hoặc pattern; có đánh đổi chủ ý; đặt quy ước mới; hoặc bác bỏ một hướng mà sau này dễ có người đề xuất lại.
     KHÔNG ghi: chi tiết triển khai, thứ đọc code là thấy.
     Trước khi thiết kế: grep file này theo module. Muốn làm trái một quyết định Active thì nêu rõ và hỏi người dùng.
     Đổi quyết định: thêm mục mới, sửa mục cũ thành "Status: Superseded by D-NNN". Không xoá.
     Ví dụ:
     ### D-001 Dùng cursor pagination cho mọi API danh sách
     Date: 2026-11-14 · Status: Active · Scope: api · From: F-012
     Why: offset chậm trên bảng orders hơn 5 triệu dòng.
     Rejected: offset/limit (chậm); keyset tự viết cho từng endpoint (không nhất quán).
-->

### D-001 Một entry point, URL dạng đường dẫn (giữ URL cũ, không `?page=`/`?action=`)
Date: 2026-11-14 (ghi nhận ADR có sẵn) · Status: Active · Scope: http, routes, front-controller · From: docs/adr/0001-single-entry-point-path-based-urls.md
Why: giữ bookmark, `?redirect=` của authV2, navbar và JS gọi API; tránh thêm redirect 302.
Rejected: `index.php?page=x` + `?action=` như nhansuhtdb (phải đổi toàn bộ URL API và viết shim redirect).

### D-002 Đọc master data PO/Style/Subart thẳng từ Postgres datchi (PDO pgsql, chỉ đọc)
Date: 2026-11-14 · Status: Active · Scope: support, po-subart · From: F-015
Why: datchi là nguồn sự thật PO; không đồng bộ/copy dữ liệu. Kết nối tuỳ chọn: thiếu cấu hình/ext/lỗi → `available:false`, màn nhập chạy như cũ. Session `default_transaction_read_only`, timeout 3s qua PDO::ATTR_TIMEOUT (không phải DSN).
Rejected: API HTTP của datchi (phải viết thêm endpoint + auth); đồng bộ định kỳ sang MySQL (lệch dữ liệu, cần cron).

### D-003 SL subart nhập tay trong MySQL, sau interface nguồn để đổi sang datchi
Date: 2026-11-14 · Status: Active · Scope: po-subart · From: F-015
Why: datchi chưa có SL theo subart; sau này import bên datchi. `SubartSoLuongSource` + factory theo config `subart_so_luong_source` (hiện chỉ `manual`); nguồn không editable → PUT/DELETE 409. Ràng buộc Σ subart ≤ SL PO–mã hàng kiểm trong transaction `SELECT … FOR UPDATE`.
Rejected: ghi SL subart ngược vào datchi (vi phạm chỉ đọc).

### D-004 Báo cáo lưu chuỗi po_number/sub_art_code; khoá trùng gồm PO + subart qua cột sinh
Date: 2026-11-14 · Status: Active · Scope: bao-cao, db · From: F-015
Why: một LINE may nhiều PO/subart của cùng mã hàng trong một ca. Lưu chuỗi (không id datchi) để báo cáo đọc được khi datchi chết. UNIQUE coi NULL là khác nhau → `po_key`/`sub_art_key` = IFNULL(…,'') STORED. Mã hàng ↔ style khớp UPPER(TRIM).
Rejected: giữ khoá cũ (chặn nghiệp vụ thật); FK sang id datchi (khác DB).

### D-005 Tailwind offline: một bundle v4 không layer, giữ cascade của thời CDN
Date: 2026-11-25 · Status: Active · Scope: pages, fe-*, css · From: F-016
Why: xưởng mất mạng là vỡ giao diện. Đo computed style cho thấy dưới Play CDN, CSS tay có specificity cao đang thắng utility; đưa CSS tay vào `layer(components)` làm đổi 30+ control. Nên bundle giữ thứ tự CSS tay → preflight → v3-compat → utility, tất cả không layer, kèm bảng màu/line-height/space của v3. `dist/app.css` được commit để server không cần build.
Rejected: `layer(components)` (đổi giao diện); CLI v3 (user đã duyệt v4); build trên server (thêm bước deploy).
Hệ quả: trình duyệt tối thiểu Chrome 111 / Safari 16.4 / Firefox 128.

### D-006 Container autowire thay facade và `new` trong controller
Date: 2026-11-25 · Status: Active · Scope: http, support, services · From: F-016
Why: NangSuatService/AdminService phình, controller tự `new` nên không test được. Container reflection ~60 dòng, mỗi class một instance mỗi request, không cần file cấu hình.
Rejected: thư viện DI ngoài (thừa cho quy mô này); giữ facade AdminService (chỉ chuyển tiếp).

### D-007 Sự kiện giao diện qua `data-action` / `data-fn`, không `on*=`
Date: 2026-11-25 · Status: Active · Scope: fe-app, fe-admin, views · From: F-016
Why: handler inline buộc hàm phải global và chặn việc siết CSP; HTML sinh từ JS nối chuỗi tham số dễ XSS. Tham số đi qua `data-args` (JSON đã escape).
Rejected: giữ inline cho HTML sinh từ JS.

### D-008 Xuống dòng LF cho mọi file text qua `.gitattributes`
Date: 2026-11-25 · Status: Active · Scope: repo · From: F-017
Why: `core.autocrlf=true` (cấu hình máy) làm working tree lẫn CRLF/LF trong khi index toàn LF → cs-fixer báo sai, Edit không khớp chuỗi, smoke lệch hash. `* text=auto eol=lf` + `*.bat text eol=crlf` + binary xlsx/exe/png; index không đổi nội dung.
Rejected: giữ autocrlf (mỗi máy một kiểu); `eol=crlf` (PSR-12 đòi LF).

### D-009 Client API: gặp `csrf_error` thì làm mới token và gửi lại đúng 1 lần
Date: 2026-11-26 · Status: Active · Scope: fe-app, fe-admin, http · From: F-018
Why: middleware `csrf` chặn trước controller nên request đầu chưa ghi gì; người dùng không phải bấm lại khi token hết hạn. Một client duy nhất `modules/api.js`.
Rejected: chỉ làm mới rồi trả lỗi (bản cũ api.js); hai client api/admin-api.

### D-010 Hàm `data-fn` của admin tự đăng ký vào registry khi module nạp
Date: 2026-11-26 · Status: Active · Scope: fe-admin · From: F-018
Why: bỏ `window.*`; thêm nút chỉ sửa một chỗ (`registerActions({...})` cuối module); tên chưa đăng ký không làm gì.
Rejected: `window.*` + whitelist (3 chỗ sửa mỗi nút); listener riêng từng nút (trái D-007).

### D-011 Toast là ES module tự tạo container, không `window.toast`
Date: 2026-11-26 · Status: Active · Scope: fe-app, fe-admin, components · From: F-018
Why: component PHP chứa script inline bị CSP chặn và bị include 2 lần; module chỉ nạp ở trang cần.
Rejected: script thường + global.

### D-012 Dữ liệu PHP → JS qua `<script type="application/json">` + `readPageData()`
Date: 2026-11-26 · Status: Active · Scope: views, fe-app, fe-chart · From: F-018
Why: CSP `script-src 'self'` chặn `window.appConfig = {...}` inline; JSON qua `json_encode` (JSON_HEX_TAG|JSON_HEX_AMP) không lỗi escape.
Rejected: script inline gán global.
