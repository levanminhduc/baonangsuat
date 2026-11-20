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
- [ ] (nit) window.toast render message qua innerHTML; message lỗi F-015 phản chiếu po_number/sub_art_code client gửi — components/toast.php:92 · F-015 verifier · 2026-11-14
- [ ] (nit) PUT header luôn kiểm lại PO với datchi → PO bị huỷ sau này chặn đổi subart; ghi_chu bị xoá vì PUT header không gửi — NangSuatService::updateHeader · F-015 verifier · 2026-11-14
- [ ] (nit) updatePoSubart khi auto-save đang chạy có thể dùng version cũ → báo xung đột — public/assets/js/app.js · F-015 verifier · 2026-11-14
- [ ] (nit) ManualSubartSource join UPPER(TRIM(ma_hang)) không dùng index, gộp nếu 2 mã hàng trùng code; lọc theo ma_hang_id — app/Services/SubartSoLuong/ManualSubartSource.php:22 · F-015 verifier · 2026-11-14
- [ ] (nit) FE nhận diện công đoạn thành phẩm chỉ 1/'1', PHP intval; FE bỏ entry có mốc ngoài moc_gio_list — app.js, report-view.js · F-015 verifier · 2026-11-14
- [ ] (test) Chưa có test getKhoiPo (mốc cuối, báo cáo khác, lọc subart) và khoá trùng po_key; test skip pdo_pgsql chỉ là placeholder — tests/php/po_tien_do_test.php:100 · F-015 verifier · 2026-11-14
- [ ] (test) Smoke --cookie (đăng nhập) chưa chạy lại cho URL bieu-do cũ sau S5b; UI F-015 chưa thử trình duyệt — tests/smoke · F-015 verifier · 2026-11-14
