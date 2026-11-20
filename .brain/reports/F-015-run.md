# Run report — F-015 Chọn PO – Mã hàng / PO – Subart khi tạo báo cáo, so lũy kế với số lượng
Spec: ../features/F-015-po-ma-hang-so-luong.md
Mode: auto
Started: 2026-11-14 16:32   Finished: 2026-11-14 19:10
Base: b8169df   Dirty at start: .brain/REGISTRY.md, .brain/features/F-015-po-ma-hang-so-luong.md, .playwright-mcp/
Result: PASS

## Summary
Báo cáo năng suất gắn được PO (và subart) từ master data datchi (Postgres, đọc trực tiếp, chỉ đọc, timeout 3s, chết thì `available:false`). Màn nhập: chọn PO/subart khi tạo, sửa khi draft, hiện SL PO / lũy kế PO / còn lại (cả subart), cập nhật sống theo grid. Admin có tab SL Subart nhập tay, Σ subart ≤ SL PO–mã hàng (khoá dòng). Khoá trùng báo cáo thêm PO + subart; biểu đồ chọn theo báo cáo. Lũy kế = số ở mốc muộn nhất đã nhập (sửa spec). Migration 013 đã áp DB local; db.php + php.ini Laragon đã cấu hình (có backup).

## Steps
| Step | Title | Agent (model) | Status | Files changed | Check | Note |
|---|---|---|---|---|---|---|
| S1 | Acceptance tests (không cần Postgres) | tester (sonnet) | DONE | 2 | 13 fail như mong đợi, test cũ pass | |
| S2 | Kết nối datchi + migration + service | orchestrator (opus) | DONE | 9 | po_tien_do 13/13 pass; migration OK trên DB tạm | đường có dữ liệu datchi chưa kiểm |
| S3 | API danh mục / admin / báo cáo | implementer (sonnet) | DONE | 6 | tests 46/46, php -l OK | trùng báo cáo giữ shape cũ 200 success:false |
| S4 | FE admin tab SL Subart | implementer (sonnet) | DONE | 4 | node --check, php -l, tests 46/46, build-css OK | thêm mục navbar (amend cục bộ) |
| S5 | FE màn nhập PO/Subart | implementer (sonnet) | DONE | 3 | node --check, php -l, tests 46/46, build-css OK | orchestrator sửa lũy kế PO = số ở mốc cuối đã nhập |
| S5b | Biểu đồ chọn theo báo cáo | implementer (sonnet) | DONE | 3 | tests 46/46, php -l, node --check OK | chạy song song S3 |
| S6 | Deploy checklist datchi | doc-writer (sonnet) | DONE | 1 | đủ 5 mục + rollback | |
| S7 | Update brain | orchestrator (opus) | DONE | 7 (.brain) | — | |

## Step log
### S1 — 16:40 — tester (sonnet) — DONE
- Files: tests/php/po_tien_do_test.php (hash 8319530e90607c3df688a000fd4ffd5c7e153bb9), tests/php/router_test.php (hash a18d4b00807cd0fcfbed81898067d269e392da07)
- Check: `php tests/php/run.php po_tien_do` → 12 fail (class chưa có) + 1 skip-placeholder; router 1 fail (route chưa có); test cũ pass
- Notes: tester thêm `SubartSoLuongFactory::fromConfig()` (make() đọc config gọi fromConfig) — trong phạm vi mục 4.

### S2 — 17:05 — orchestrator (opus) — DONE
- Files: app/Support/Database.php (+getOptionalConfig, +getDatchi, closeAll chỉ đóng mysqli), app/Services/{PoTienDo,DatchiPoService,PoSubartService}.php, app/Services/SubartSoLuong/{SubartSoLuongSource,ManualSubartSource,SubartSoLuongFactory}.php, database/migrations/013_add_po_subart.sql, database/install.sql
- Check: po_tien_do 13/13 pass; full 45 pass / 1 fail (route S3); php -l sạch; cs-fixer 0/6 file mới; install.sql + migration 013 chạy trên DB tạm MySQL 8.4.3, khoá trùng đúng 6/6 ca (DB tạm đã DROP).
- Notes: không có config `datchi_pg` → `available:false` trong 6ms. Thử kết nối Postgres datchi thật bị chặn (không được đọc credential trong .env datchi) → đường có dữ liệu để người dùng kiểm tay sau khi thêm `datchi_pg` vào db.php. DatchiPoService nhận PDO qua constructor để test. Lưu subart song song: deadlock 1213 → 409 "thử lại" (ràng buộc vẫn giữ).

### S3 — 17:20 — implementer (sonnet) — DONE
- Files: config/routes.php, app/Controllers/{PoSubartController (mới),DanhMucController,BaoCaoController}.php, app/Services/{PoSubartService,NangSuatService}.php
- Check: `php tests/php/run.php` 46 pass 0 fail; php -l OK; cs-fixer: file mới sạch, file cũ chỉ CRLF có sẵn
- Notes: thêm `po-subart` vào negative-lookahead catch-all `/api/admin/{resource}`; BaoCaoController::withStatus bóc `status` khỏi body; trùng báo cáo giữ shape cũ (200, success:false); getBaoCao → getKhoiPo 2 query + fallback routing; lỗi datchi bắt Throwable.

### S5b — 17:20 — implementer (sonnet) — DONE
- Files: app/Services/ChartService.php (findReportForChart chung cho 3 hàm), app/Controllers/BieuDoController.php, public/assets/js/bieu-do.js
- Check: tests 46/46; php -l; node --check OK; chưa thử trình duyệt
- Notes: URL cũ chỉ có ma_hang_id+ca_id → lấy báo cáo đầu tiên khớp. Query mới cần migration 013.

### S6 — 17:15 — doc-writer (sonnet) — DONE
- Files: docs/deploy-checklist.md (mục F-015: pdo_pgsql, role chỉ đọc, mạng, db.php, migration 013 + rollback)
- Notes: giả định lệnh mysqldump không kèm -u/-p, pg_hba scram-sha-256.

### S4 — 17:45 — implementer (sonnet) — DONE
- Files: public/assets/js/modules/admin/po-subart.js (mới), public/assets/js/admin.js (module 'po-subart', deps ['ma-hang']), views/admin.php (#po-subartTab), views/partials/navbar.php (mục "SL Subart" nhóm Mã hàng & Công đoạn)
- Check: node --check, php -l, tests 46/46, toolsuild-css.bat OK; chưa thử trình duyệt
- Notes: Amended (local): thêm mục navbar — spec chỉ nói tab admin, navbar là đường vào bắt buộc.

### S5 — 18:00 — implementer (sonnet) — DONE (+ sửa của orchestrator)
- Files: views/nhap-nang-suat.php, public/assets/js/app.js, public/assets/js/modules/report-view.js
- Check: node --check, php -l, tests 46/46, build-css OK; chưa thử trình duyệt, không gửi request ghi
- Notes: modal PO/subart cascade; khối PO/Subart trên dải thông tin; sửa PO/subart trên màn nhập khi draft (PUT header gửi lại so_lao_dong, ctns). Implementer NOTICED: ô nhập là lũy kế tại mốc (grid.rowTotal, calculateLuyKeThucTe, commit 9d8efbf) nhưng spec + getKhoiPo + FE cộng mọi mốc → thổi phồng. Orchestrator sửa: getKhoiPo lấy so_luong ở mốc muộn nhất (thu_tu, gio) có so_luong>0 của công đoạn thành phẩm; FE dùng gridManager.rowTotal / thanhPhamTuEntries lấy số mốc cuối. Amended spec mục lũy kế.
- Datchi thật (php -d extension=pdo_pgsql): kết nối OK; 3 style lấy được PO/SL/subart, getPoQuantity khớp; ghi bị chặn SQLSTATE 25006.

## Verification
| Round | Runner (haiku) | Verifier (opus) | Blockers |
|---|---|---|---|
| 1 | tests 46/46 (+pdo_pgsql 46/46, 0 skip); php -l sạch; cs-fixer 0 vi phạm; build-css OK; node --check OK; smoke 117 URL khớp status/JSON, 5 lệch hash body (app.js, main.css do F-015; api.js, realtime-worker.js, logoht.svg không sửa — CRLF autocrlf, có từ trước) | FAIL | 3 |
| 2 | tests 46/46 (± pdo_pgsql); php -l OK; node --check OK; smoke anon 120/120 khớp (baseline chụp lại, +3 URL) | verifier treo (watchdog 600s) → orchestrator soát 3 chỗ sửa: PASS | 0 |

### Blockers & how they were fixed
- R1-B1 [S2] Database::connectDatchi: `connect_timeout` trong DSN bị pdo_pgsql ghi đè bằng PDO::ATTR_TIMEOUT (mặc định 30) → datchi chết làm GET báo cáo treo ~21s. → implementer-high: thêm PDO::ATTR_TIMEOUT=3.
- R1-B2 [S3] tests/smoke/urls.txt thiếu 3 URL GET mới (Done when S3). → implementer-high: thêm URL + chụp lại baseline-anon.
- R1-B3 [S5] app.js editor PO: datchi chết → select chỉ còn "Không chọn PO", Lưu gửi null → xoá PO khỏi báo cáo. → implementer-high: giữ lựa chọn hiện tại, khoá nút Lưu.

## Escalations
| Step | Ladder | Reason |
|---|---|---|
| Verify R1 | verifier → implementer-high | 3 blocker (timeout datchi, smoke URL, mất PO khi datchi chết) |

## Learned & noticed
- LEARNED (orchestrator): `git core.autocrlf=true` → file PHP cũ checkout CRLF, `php-cs-fixer fix --dry-run` báo 54/60 file (có từ trước F-015); kiểm file mới bằng `--config=.php-cs-fixer.dist.php --path-mode=intersection <files>`.
- LEARNED (orchestrator): PHP CLI = XAMPP 8.2.12 (C:/xampp/php/php.ini), có php_pdo_pgsql.dll nhưng chưa bật; thử bằng `php -d extension=pdo_pgsql`. MySQL local 8.4.3.
- LEARNED (S3): `JsonResponse` final + strict_types; service trả `status` thì controller phải bóc (BaoCaoController::withStatus).
- LEARNED (S5b): file CRLF làm Edit tool không khớp chuỗi nhiều dòng — sửa bằng script giữ CRLF.
- NOTICED (S3): các method cũ NangSuatService (updateEntries…) vẫn trả 200 success:false khi lỗi.
- NOTICED (orchestrator): code mới (ChartService, NangSuatService) đọc cột po_number → DB local/prod phải áp migration 013 trước khi deploy code.
- LEARNED (tester): test route thật bằng `new Router(require BNS_ROOT . '/config/routes.php')->match($method, $path)` → `[status, route, params]`; filter runner là substring tên file.

- LEARNED (S5): `NangSuatService::updateHeader` mặc định so_lao_dong/ctns = 0 khi thiếu → mọi PUT header phải gửi lại cả hai.
- LEARNED (S5): ô nhập grid là lũy kế tại mốc; tổng của báo cáo = số ở mốc muộn nhất đã nhập, KHÔNG SUM các mốc.
- LEARNED (orchestrator): Laragon Apache = php 8.3.28 (php.ini bật pdo_pgsql, backup php.ini.bak-f015); db.php backup db.php.bak-f015.
- NOTICED (orchestrator): HistoryService::getReportList cột thuc_te SUM mọi mốc của công đoạn lũy kế → thổi phồng (có từ trước F-015).
- NOTICED (S5): warning chỉ có CSS var --bns-warning, không có class .bns-text-warning.

- LEARNED (implementer-high): pdo_pgsql nối `connect_timeout` từ PDO::ATTR_TIMEOUT (mặc định 30) sau DSN → `connect_timeout` trong DSN vô hiệu; phải đặt ATTR_TIMEOUT. Đo: 21s → 3s.
- NOTICED (implementer-high): cs-fixer --dry-run báo mọi file CRLF working tree là đổi (false positive).

## Retro notes
- S5/spec: lũy kế PO thiết kế SUM các mốc, sai với ngữ nghĩa ô nhập (lũy kế tại mốc) — spec-quality / project-knowledge (đã ghi KNOWLEDGE).
- R1-B1 timeout datchi: hành vi pdo_pgsql ghi đè connect_timeout — project-knowledge (đã ghi KNOWLEDGE); S2 chỉ đo đường không cấu hình, chưa đo host không tới được — spec-quality.
- R1-B2 smoke URL: implementer S3 bỏ sót một "Done when" — agent-prompt.
- R1-B3 mất PO khi datchi chết: implementer S5 không xét đường available:false của editor — agent-prompt.
- Verifier R2 treo 600s — one-off.

## Cost check
- Steps by agent: haiku 0 · sonnet 7 (tester 1, implementer 4, doc-writer 1, implementer-high 1) · orchestrator 2 (S2, S7) · runner 1 · verifier 1 (+1 treo)
- Usage (tuỳ chọn, bạn điền): before ...   after ...

## Contract changes
- + GET /api/danh-muc/po, GET /api/danh-muc/po-subart; + GET/PUT/DELETE /api/admin/po-subart
- ~ POST /api/bao-cao, PUT /api/bao-cao/{id}/header (po_number, sub_art_code; 422/503); ~ GET /api/bao-cao/{id} (+ khối `po`)
- ~ GET /api/bieu-do/ma-hang-list (mỗi báo cáo một dòng + bao_cao_id/po_number/sub_art_code), so-sanh* (+ bao_cao_id)
- + bao_cao_nang_suat.{po_number, sub_art_code, po_key, sub_art_key}; ~ uk_bao_cao; + idx_po_ma_hang; + po_subart_so_luong (migration 013)
- + Database::getDatchi, getOptionalConfig; + SubartSoLuongSource/Manual/Factory; + config `datchi_pg`, `subart_so_luong_source`

## Brain updates
- REGISTRY F-015 DONE; SYSTEM: module po-subart, support/bieu-do, 3 API, 2 bảng + bảng datchi, FE admin po-subart, glossary Lũy kế/PO
- DECISIONS D-002 (đọc datchi PDO), D-003 (SL subart nhập tay + interface nguồn), D-004 (khoá trùng PO/subart cột sinh)
- KNOWLEDGE +7; BACKLOG +14; SCORECARD run 1

## Open items
- Reload Apache (Laragon) để web nạp pdo_pgsql.
- Thử tay trên trình duyệt: modal tạo báo cáo chọn PO/subart, editor PO, tab admin SL Subart (lưu/xoá, vượt tổng), biểu đồ nhiều báo cáo cùng mã hàng.
- Prod: áp migration 013 TRƯỚC khi deploy code; bật pdo_pgsql; tạo role chỉ đọc `bns_reader` (docs/deploy-checklist.md).
- Smoke --cookie (đăng nhập) chưa chạy lại.
