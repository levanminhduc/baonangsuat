# Backlog — thấy rồi nhưng chưa làm
<!-- Nguồn: dòng NOTICED của agent, NITS của verifier, Open items trong báo cáo, ý tưởng của bạn.
     Mỗi mục: - [ ] (loại) mô tả — path:line · nguồn · ngày        loại: debt | bug? | idea | nit | test | doc
     Xong hoặc bỏ: đổi [ ] thành [x] kèm ID đã xử lý, hoặc "wontfix: lý do". /sync dọn các mục [x] cũ hơn 30 ngày.
     Agent KHÔNG tự lấy việc từ đây. Chỉ làm khi bạn yêu cầu, ví dụ: /do làm các mục debt của module auth
     Ví dụ:
     - [ ] (debt) OrderService.calcTotal lặp logic với InvoiceService — src/order/service.ts:120 · F-012 verifier · 2026-11-14
-->
- [ ] (bug?) Danh sách lịch sử cột thuc_te SUM mọi mốc của công đoạn lũy kế (ô đã là lũy kế tại mốc) → thổi phồng — app/Services/HistoryService.php:25 · F-015 orchestrator · 2026-11-14
- [ ] (idea) Bulk-create báo cáo chưa chọn được PO/subart — admin/bulk-create · F-015 · 2026-11-14
- [ ] (idea) Phân quyền nhập SL subart theo user (hiện chỉ admin) — /api/admin/po-subart · F-015 · 2026-11-14
- [ ] (idea) DatchiSubartSource khi datchi import SL theo subart (factory đang luôn trả manual) — app/Services/SubartSoLuong/SubartSoLuongFactory.php · F-015 · 2026-11-14
- [ ] (debt) Các method cũ NangSuatService (updateEntries, trùng báo cáo…) trả 200 success:false khi lỗi — app/Services/NangSuatService.php · F-015 S3 · 2026-11-14
- [ ] (debt) Prod: datchi_pg nên dùng role chỉ đọc `bns_reader` (local đang dùng postgres superuser) — C:/xampp/config/db.php · F-015 · 2026-11-14
- [ ] (nit) getKhoiPo chạy trong mọi getBaoCao (cả submit, /routing) → gọi datchi thừa; chỉ cần ở GET show — app/Services/NangSuatService.php:374 · F-015 verifier · 2026-11-14
- [x] F-018 (nit) window.toast render message qua innerHTML; message lỗi F-015 phản chiếu po_number/sub_art_code client gửi — components/toast.php:92 · F-015 verifier · 2026-11-14
- [ ] (nit) PUT header luôn kiểm lại PO với datchi → PO bị huỷ sau này chặn đổi subart; ghi_chu bị xoá vì PUT header không gửi — NangSuatService::updateHeader · F-015 verifier · 2026-11-14
- [ ] (nit) updatePoSubart khi auto-save đang chạy có thể dùng version cũ → báo xung đột — public/assets/js/app.js · F-015 verifier · 2026-11-14
- [ ] (nit) ManualSubartSource join UPPER(TRIM(ma_hang)) không dùng index, gộp nếu 2 mã hàng trùng code; lọc theo ma_hang_id — app/Services/SubartSoLuong/ManualSubartSource.php:22 · F-015 verifier · 2026-11-14
- [ ] (nit) FE nhận diện công đoạn thành phẩm chỉ 1/'1', PHP intval; FE bỏ entry có mốc ngoài moc_gio_list — app.js, report-view.js · F-015 verifier · 2026-11-14
- [ ] (test) Chưa có test getKhoiPo (mốc cuối, báo cáo khác, lọc subart) và khoá trùng po_key; test skip pdo_pgsql chỉ là placeholder — tests/php/po_tien_do_test.php:100 · F-015 verifier · 2026-11-14
- [ ] (test) Smoke --cookie (đăng nhập) chưa chạy lại cho URL bieu-do cũ sau S5b; UI F-015 chưa thử trình duyệt — tests/smoke · F-015 verifier · 2026-11-14
- [ ] (bug?) Khoá version không chặn được ghi đè đồng thời: updateEntries SELECT không FOR UPDATE, updateHeader UPDATE không kèm version và không transaction — app/Services/NangSuatService.php:679,799 · F-016 khảo sát · 2026-11-25
- [ ] (bug?) submitBaoCao tính ket_qua_luy_ke rồi đổi trạng thái không trong transaction — app/Services/NangSuatService.php:841 · F-016 khảo sát · 2026-11-25
- [x] F-017 (không còn: `rg "session\.role" public/assets/js` = 0) (bug?) app.js đọc session.role (không tồn tại, key đúng là vai_tro) → nhánh admin không bao giờ chạy — public/assets/js/app.js:298,332,772 · F-016 khảo sát · 2026-11-25
- [ ] (debt) ImportController nhận preview_stats/preview_errors từ client rồi ghi vào lịch sử; lịch sử ghi sau commit — app/Controllers/ImportController.php:70, app/Services/ImportService.php:409 · F-016 khảo sát · 2026-11-25
- [ ] (debt) ChartService có bản sao riêng getMocGioList/getRouting và 3 khối lookup lặp — app/Services/ChartService.php:204,252 · F-016 khảo sát · 2026-11-25
- [ ] (debt) Auth.php hard-code /baonangsuat/ ở loginUrl/logoutUrl và chứa SQL — app/Auth/Auth.php:81,88 · F-016 khảo sát · 2026-11-25
- [ ] (doc) install.sql ghi DB production là bao_nang_suat, README/AGENTS ghi nang_suat; docs/agents/domain.md trỏ GLOSSARY.md không tồn tại — database/install.sql:1 · F-016 khảo sát · 2026-11-25
- [x] (security) CSP còn cho phép cdn.tailwindcss.com, cdn.jsdelivr.net, Google Fonts dù không còn dùng; siết lại (đổi baseline smoke) — app/Http/Middleware/SecurityHeaders.php:19 · F-016 verifier · 2026-11-25
- [x] (security) `callDataFn` gọi được mọi hàm global; nên whitelist các hàm đã đăng ký trước khi bỏ unsafe-inline — public/assets/js/admin.js:254 · F-016 verifier · 2026-11-25
- [x] (security) `cd.ma_cong_doan` chèn innerHTML không escape — public/assets/js/modules/admin/import.js:334 · F-016 verifier · 2026-11-25
- [ ] (security) tests/router.spec.js (đã xoá) còn tài khoản hard-code trong lịch sử git; đổi mật khẩu nếu là thật · F-016 verifier · 2026-11-25
- [ ] (debt) Router chỉ bắt `Exception`; TypeError ra HTML fatal thay vì JSON 500 — app/Http/Router.php · F-016 verifier · 2026-11-25
- [ ] (debt) AdminController nhận 7 service nên bulk-create mở cả kết nối mysqli + nhân sự; ImportController mở DB trước khi kiểm file — app/Controllers/AdminController.php:24 · F-016 · 2026-11-25
- [x] F-018 (debt) toast.php bị include 2 lần ở bieu-do (view + navbar); `window.initialParams`, `window.historyModule` không ai đọc; popstate trong app.js còn nhánh chết — views/bieu-do.php:318 · F-016 · 2026-11-25
- [ ] (debt) khối "Tailwind handles it" trong style.css chưa cắt (network-monitor.js, time-manager.js KHÔNG phải code chết: app.js:6, modules/nhap/autosave.js:3, modules/luy-ke-calculator.js:2 import — sửa ở F-017) — public/assets/css/style.css · F-016 · 2026-11-25
- [x] F-018 (debt) history.js `showConfirmModal(..., isHtml)` vẫn gán innerHTML, chưa soát caller — public/assets/js/modules/history.js:301 · F-016 · 2026-11-25
- [ ] (doc) README còn "PHP 7.4+", "13 bảng", mục mật khẩu/đăng nhập cũ — README.md · F-016 · 2026-11-25
- [x] F-017 (cleanup) tailwindcss-windows-x64.exe 129 MB ở gốc repo (đã ignore) trùng tools/tailwindcss.exe · F-016 · 2026-11-25
- [ ] (debt) Build Tailwind không tất định: tự dò source quét cả `.brain/`, docs → thêm `source(none)` cho import utilities hoặc tương đương — public/assets/tailwind/src/app.css:19 · F-017 · 2026-11-25
- [ ] (nit) `.gitattributes` có thể khai thêm binary `*.xls *.jpg *.ico *.gif *.woff2` (text=auto đã tự nhận) — .gitattributes:3 · F-017 verifier · 2026-11-25
- [ ] (nit) Nhiều request ghi cùng lúc khi chưa có token, mỗi cái tự gọi /csrf-token; dùng chung một promise đang chờ — public/assets/js/modules/api.js:23 · F-018 verifier · 2026-11-26
- [ ] (nit) Làm mới token thất bại vẫn gửi lại với token cũ (upload gửi lại file) — public/assets/js/modules/api.js:51 · F-018 verifier · 2026-11-26
- [ ] (debt) `modules/nav.js` không có cache-buster (để tránh 2 instance) → sau deploy trình duyệt có thể giữ bản cũ — views/layouts/app.php:37 · F-018 verifier · 2026-11-26
- [ ] (nit) `applyConfirmContent` tra `#cancelConfirmBtn` không tồn tại → tham số cancelText chết — public/assets/js/modules/utils.js:160 · F-018 verifier · 2026-11-26
- [ ] (nit) Callback của showConfirmModal còn gọi closeModal('confirmModal') thừa (modal đã ẩn trước callback) — public/assets/js/modules/admin/import.js:155 · F-018 verifier · 2026-11-26
- [ ] (nit) `closeModal(id)` ném lỗi khi id không tồn tại — public/assets/js/modules/utils.js:65 · F-018 verifier · 2026-11-26
- [ ] (nit) `admin.js` import `api` không dùng; `bulk-create` thiếu trong `legacyAliases` — public/assets/js/admin.js:2 · F-018 · 2026-11-26
- [ ] (nit) `lineTen` trong bnsPageData không JS nào đọc; dòng trống có khoảng trắng thừa — views/bieu-do.php:317 · F-018 · 2026-11-26
- [ ] (nit) Method `closeModal()` của create-modal (ẩn mọi .modal) trùng tên `utils.closeModal(id)` — public/assets/js/modules/nhap/create-modal.js:150 · F-018 verifier · 2026-11-26
- [ ] (nit) Comment tiếng Anh chép nguyên từ import.js cũ — public/assets/js/modules/admin/import-preview.js · F-018 · 2026-11-26
- [ ] (nit) `date_default_timezone_set` thừa trong navbar — views/partials/navbar.php · F-018 · 2026-11-26
- [ ] (ops) Chụp lại `tests/smoke/baseline-auth.json` (body asset/HTML đổi): `php tools/smoke.php --cookie=<HTSSOSESSID> --update` — người dùng chạy · F-018 · 2026-11-26
