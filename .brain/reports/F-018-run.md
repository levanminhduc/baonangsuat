# Run report — F-018 JS: một API client, một bộ utils, bỏ global window.*, bỏ script inline
Spec: ../features/F-018-js-mot-client-bo-global.md
Mode: auto
Started: 2026-11-26 09:30   Finished: 2026-11-26 13:00
Base: 0f40c38   Dirty at start: .brain/features/F-018-js-mot-client-bo-global.md, .brain/features/F-019-css-mot-nguon-token.md, .brain/team/AGENT_CHANGELOG.md, .brain/team/LESSONS.md, .brain/team/SCORECARD.md
Result: PASS

## Summary
- Một client API `modules/api.js` (`api`, `apiUpload`, `fetchCsrfToken`): tự lấy token trước khi ghi, `csrf_error` → làm mới + gửi lại đúng 1 lần; xoá admin-api.js.
- Một `modules/utils.js` (loading khoá modal, `closeModal`, `showConfirmModal`, `confirmDialog` → Promise); toast thành ES module; xoá admin-utils.js, components/toast.php.
- Bỏ mọi `window.*` và script inline: registry `data-fn` admin (24 tên), `readPageData`, `nav.js` / `nav-early.js` / `tabs.js`; CSP còn `script-src 'self'`.
- Tách import.js, presets.js dưới 500 dòng. `fe_structure_test` khoá các luật; verifier PASS vòng 1, 0 blocker.

## Steps
| Step | Title | Agent (model) | Status | Files changed | Check | Note |
|---|---|---|---|---|---|---|
| S1 | (test) Test cấu trúc FE | tester (sonnet) | DONE | tests/php/fe_structure_test.php | 10 fail (AC1–AC5) / 1 pass (node --check) như mong đợi | |
| S2 | API client hợp nhất | implementer (sonnet) | DONE | api.js, login.js, admin/import.js | fetch( chỉ còn api.js + admin-api.js; run.php 73 pass / 10 fail (chỉ fe_structure) | kiểm trình duyệt dồn S11 |
| S3 | admin-api → api, xoá admin-api.js | chore (haiku) | DONE | admin.js + 12 modules/admin/*.js, -admin-api.js (git rm) | AC1 pass; node --check 13 file | |
| S4 | Utils hợp nhất, một modal xác nhận | implementer (sonnet) | DONE | utils.js, admin-utils.js (re-export), history.js, nhap/workflow.js, bieu-do/{charts,data}.js; test regex (orchestrator) | AC2 chỉ còn admin_utils (S6) + alert login.js (S5); run.php 75 pass, lỗi chỉ fe_structure | test regex sửa bởi orchestrator |
| S5 | Toast thành module | implementer (sonnet) | DONE | +modules/toast.js, utils.js, login.js, network-monitor.js, -components/toast.php, 3 view, dist/app.css, baseline-anon.json | run.php 77 pass / 6 fail (fe_structure, bước sau) | smoke --update: chỉ đổi body asset |
| S6 | admin-utils → utils, xoá admin-utils.js | chore (haiku) | DONE | admin.js + 12 modules/admin/*.js, -admin-utils.js | AC2 pass; node --check 13 file | |
| S7 | Registry hành động admin (1) | implementer (sonnet) | DONE | +modules/admin/actions.js, admin.js, lines/cong-doan/ma-hang.js | gộp chung lượt với S8 (bỏ đường tạm window[name]) | |
| S8 | Registry hành động admin (2) + bỏ global admin | implementer (sonnet) | DONE | admin.js, user-lines/permissions/routing/presets/moc-gio/import-history.js | admin 0 window.x=; run.php 79 pass / 4 fail (fe_structure) | 24 tên đăng ký đủ |
| S9 | Bỏ global trang nhập + biểu đồ | implementer (sonnet) | DONE | app.js, bieu-do.js, login.js, history.js, nhap/{create-modal,report-list,editor}.js, bieu-do/{state,filters}.js, +page-data.js, views/{bieu-do,nhap-nang-suat}.php, baseline-anon.json | AC3 pass; run.php 80 pass / 3 fail | smoke --update: body app.js |
| S10 | Navbar, tabs, loading-overlay → module | implementer (sonnet) | DONE | +nav-early.js, +modules/nav.js, +modules/tabs.js, navbar.php, layouts/app.php, tabs.php, loading-overlay.php, huong-dan.php, admin.js | ac4 script pass; run.php 81 pass / 2 fail; smoke khớp | nav.js nạp không ?v= (tránh 2 instance) + cờ bnsSidebarReady |
| S11 | Siết CSP + kiểm trình duyệt | orchestrator (opus) | DONE | SecurityHeaders.php, baseline-anon.json | CSP script-src 'self'; 6 trang ×2 cỡ: 0 lỗi console, 0 vi phạm CSP; run.php (DB test) chỉ còn ac5 | kiểm Playwright trên server phiên giả |
| S12 | Tách import.js, presets.js | implementer (sonnet) | DONE | import.js 266, +import-preview.js 238, presets.js 272, +preset-lines.js 243 | run.php 83 pass / 0 fail; trình duyệt preset + import pass | |
| S13 | (doc) Cập nhật tài liệu FE | doc-writer (sonnet) | DONE | CLAUDE.md, AGENTS.md, .claude/rules/tailwind-js.md | rg admin-api/admin-utils/window.toast trong tài liệu = 0 | orchestrator sửa 3 câu login.js/non-module cũ |
| S14 | Update brain | orchestrator (opus) | DONE | .brain/{REGISTRY,SYSTEM,DECISIONS,KNOWLEDGE,BACKLOG}.md, spec | — | |

## Step log
### S1 — 09:35 — tester (sonnet) — DONE
- Files: tests/php/fe_structure_test.php (11 test) — hash eff21d14188a13463e8229640241a5da64ffab03
- Check: `php tests/php/run.php fe_structure` → 10 fail đúng AC1–AC5, node --check pass (node 22 check trực tiếp ES module, không cần --input-type)
- UNTESTABLE (kiểm tay/trình duyệt ở S11 + verifier): retry csrf_error, chưa có token; hành vi modal xác nhận; data-fn chưa đăng ký + 24 hành động; 6 trang trình duyệt; header CSP sống (test chỉ soi nguồn SecurityHeaders.php)
- Notes: `bieu-do/charts.js:240` cũng định nghĩa `showLoading` (trạng thái biểu đồ, khác nghĩa) → amend S4: đổi tên `showChartLoading` trong charts.js + data.js.

### S2 — 09:45 — implementer (sonnet) — DONE
- Files: public/assets/js/modules/api.js, public/assets/js/login.js, public/assets/js/modules/admin/import.js
- Check: node --check pass; `rg fetch(` chỉ api.js + admin-api.js; `php tests/php/run.php` 73 pass / 10 fail (đều fe_structure) / 6 skip
- Notes: login.js vốn đã nạp dạng module (views/index.php:6) nên import thẳng api.js. Orchestrator soát api.js: ensureToken, retry 1 lần, DELETE có body, same-origin — khớp contract.

### S3 — 09:50 — chore (haiku) — DONE
- Files: admin.js, modules/admin/{bulk-create,cong-doan,import-history,import,lines,ma-hang,moc-gio,permissions,po-subart,presets,routing,user-lines}.js; git rm modules/admin-api.js
- Check: `rg admin-api` = 0; node --check 13 file; fe_structure: 2 test AC1 pass (3 pass / 8 fail)

### S4 — 10:05 — implementer (sonnet) — DONE
- Files: modules/utils.js (+closeModal, +showConfirmModal, +confirmDialog, showLoading bản admin), modules/admin-utils.js (chỉ re-export từ utils, để S6 là đổi đường dẫn thuần), modules/history.js, modules/nhap/workflow.js, modules/bieu-do/charts.js + data.js (showChartLoading)
- Check: node --check pass; confirm/alert chỉ còn login.js; isHtml/confirmBtn.onclick = 0; run.php 75 pass / 8 fail (đều fe_structure)
- Cơ chế confirm: openConfirm() gắn 1 listener click lên #confirmModal, pendingConfirm toàn module; settleConfirm gỡ listener, ẩn modal, resolve; mở mới khi đang chờ → settle(false) bản cũ. Thay đổi hành vi: modal xác nhận ẩn TRƯỚC khi chạy callback (trước đây callback tự closeModal).
- Orchestrator sửa test: regex AC2 `^s+showConfirmModal(.*{` khớp cả lời gọi trong modules/admin → `^s+showConfirmModal([^)]*)s*{`; amend AC2 trong spec. Hash mới fe_structure_test.php: 3e4e43f56d8d5f7ac49a56c1755f6119b5a0e369. fe_structure 4 pass / 7 fail.

### S5 — 10:20 — implementer (sonnet) — DONE
- Files: +modules/toast.js; modules/utils.js, login.js, modules/network-monitor.js; git rm components/toast.php; views/partials/navbar.php, views/bieu-do.php, views/index.php; dist/app.css (build); tests/smoke/baseline-anon.json
- Check: node --check, php -l sạch; window.toast/components/toast = 0; confirm/alert = 0; run.php 77 pass / 6 fail (fe_structure của S6, S8–S12)
- Smoke: 2/122 khác, đều body asset (api.js, app.css) — trang ẩn danh chỉ redirect; đã `--update` baseline-anon. Lý do chụp lại: đổi JS/CSS asset.

### S6 — 10:25 — chore (haiku) — DONE
- Files: admin.js, modules/admin/{bulk-create,cong-doan,import-history,import,lines,ma-hang,moc-gio,permissions,po-subart,presets,routing,user-lines}.js; git rm -f modules/admin-utils.js
- Check: `rg admin-utils` = 0; node --check 13 file; fe_structure 6 pass / 5 fail (AC3–AC5)

### S7+S8 — 10:35 — implementer (sonnet) — DONE
- Files: +modules/admin/actions.js; admin.js; modules/admin/{lines,user-lines,permissions,ma-hang,cong-doan,routing,presets,moc-gio,import-history}.js
- Check: node --check pass; `window.x=|window[|DATA_FN_ALLOWED` trong admin = 0; 24 tên mỗi tên 1 registerActions; run.php 79 pass / 4 fail (ac3 chỉ app.js, bieu-do.js; ac4 ×2; ac5 import.js 502, presets.js 505)
- Thứ tự nạp: mọi data-fn sinh bởi chính module đăng ký; data-action show-assign-lines chỉ bấm được sau viewPresetDetail → không cần nạp-khi-click. Orchestrator soát diff admin.js: khớp.

### S9 — 10:50 — implementer (sonnet) — DONE
- Files: app.js, bieu-do.js, login.js, modules/history.js, modules/nhap/{create-modal,report-list,editor}.js, modules/bieu-do/{state,filters}.js, +modules/page-data.js, views/bieu-do.php, views/nhap-nang-suat.php, tests/smoke/baseline-anon.json
- Check: node --check, php -l sạch; global cũ = 0; `window.x=` toàn JS = 0; run.php 80 pass / 3 fail (ac4 ×2, ac5); smoke 1 khác (body app.js) → --update
- Notes: popstate rút thành `e.state === null && !search.includes('line=')`; bnsPageData: isAdmin bool, lineId int|null; login.js dùng readPageData('allowedLines') (id cũ của index.php).

### S10 — 11:05 — implementer (sonnet) — DONE
- Files: +public/assets/js/nav-early.js, +modules/nav.js, +modules/tabs.js; views/partials/navbar.php, views/layouts/app.php, components/tabs.php, components/loading-overlay.php, views/huong-dan.php, admin.js
- Check: node --check, php -l sạch; bnsNav = 0; run.php 81 pass / 2 fail (ac4 CSP, ac5); smoke ẩn danh khớp 122/122
- Notes: bẫy 2 instance module (layout `nav.js?v=` ≠ import `./modules/nav.js`) → layout nạp nav.js không cache-buster + cờ `data-bns-sidebar-ready`. Đồng hồ đọc `data-server-time`.

### S11 — 11:55 — orchestrator (opus) — DONE
- Files: app/Http/Middleware/SecurityHeaders.php (bỏ 'unsafe-inline' khỏi script-src), tests/smoke/baseline-anon.json (`--update`; lý do: 7 trang chỉ khác header CSP)
- Check: `BNS_DB_CONFIG=db.test.php php tests/php/run.php` 88 pass / 1 fail (ac5 → S12); smoke anon 122/122 khớp sau update
- Trình duyệt (Playwright, PHP built-in + phiên giả `scratchpad/devserver.php`, DB nang_suat_test + bảng HR giả phong_ban/nhan_vien/user/user_permissions):
  - 6 trang × 1366/390: 0 lỗi JS, 0 securitypolicyviolation, 0 script inline; header CSP `script-src 'self'`; đồng hồ chạy.
  - Admin 12 tab: tiêu đề + mục menu đúng (setActiveNav); 22/24 hành động mở đúng modal/gọi đúng API qua runAction; editRouting/editMocGio không có dòng id=1 trong state nên không mở (đúng hàm cũ); data-fn `khongCo` không chạy gì, không lỗi.
  - Modal xác nhận (admin deleteLine + nhập Chốt + lịch sử Mở khoá/Xoá): click thân không đóng; X/Hủy/nền đóng, 0 request; mở-huỷ×3 rồi xác nhận → đúng 1 request; toast hiện.
  - CSRF: giả `csrf_error` 1 lần → POST, GET token, POST (1 lần); 2 lần → dừng, trả lỗi; upload retry được; trang mới upload đầu tiên lấy token trước.
  - showLoading ở modal Tạo báo cáo: khoá 10 control lúc chờ, mở lại sau API lỗi.
  - Sidebar: thu gọn nhớ qua reload, `data-bns-collapsed` có trước khi #bnsSidebar được parse (không nháy); menu mobile mở/Esc đóng; `#/lich-su` tô mục lich-su; tab hướng dẫn đổi được; nav.js chỉ tải 1 bản.
  - Không phải admin ở biểu đồ: bộ lọc LINE disabled, khoá LINE 1. index: chọn LINE → POST select-line có token → trang nhập Tổ 2. Nhập 1 ô → PUT entries 200, version 2.

### S12 — 12:10 — implementer (sonnet) — DONE
- Files: modules/admin/import.js, +modules/admin/import-preview.js, modules/admin/presets.js, +modules/admin/preset-lines.js
- Check: node --check pass; mọi file < 500 dòng; `php tests/php/run.php` 83 pass / 0 fail / 6 skip; smoke khớp
- Notes: không vòng import (presets → preset-lines một chiều; `renderPresetsTable` truyền vào bằng callback); state ở state.js. Orchestrator kiểm trình duyệt: xem chi tiết preset, gán LINE (POST 200), bỏ gán (DELETE có body 200), copy preset (POST 200, thêm 1 dòng), import xem trước file fixtures/import-test.xlsx (POST import/preview 200, hiện bảng) — 0 lỗi console.

### S13 — 12:30 — doc-writer (sonnet) — DONE
- Files: CLAUDE.md, AGENTS.md (doc-writer); .claude/rules/tailwind-js.md (orchestrator — subagent không ghi .claude/)
- Check: `rg -n "admin-api|admin-utils|window\.toast" CLAUDE.md AGENTS.md .claude/rules` → 0
- Notes: orchestrator bỏ 2 câu sai "login.js là script thường / tự đọc meta" (login.js đã là module), sửa "the only non-module script" thành "only non-module app script (besides vendored Chart.js)".

## Verification
| Round | Runner (haiku) | Verifier (opus) | Blockers |
|---|---|---|---|
| 1 | PASS — php -l OK; cs-fixer 0 file; run.php 83/0/6 skip, DB test 89/0/0; smoke 122/122; Tailwind 0 selector mất; node --check OK | PASS (10 nits) | none |

### Blockers & how they were fixed

## Escalations
| Step | Ladder | Reason |
|---|---|---|

## Learned & noticed
- LEARNED (S1): một `?>` trong comment `//` của file test PHP kết thúc chế độ PHP → lỗi parse.
- LEARNED (S4): `#confirmModal` (.modal) tự mang `data-action="close-modal"`; handler đóng phải kiểm `e.target === closeEl || !closeEl.classList.contains('modal')`.
- LEARNED (S5): `cmd //c toolsuild-css.bat` từ Git Bash mất dấu `\`; chạy thẳng `tools/tailwindcss.exe -i ... -o ... --minify`.
- LEARNED (S10): module script là deferred → nav.js gắn sidebar sau khi parse; module nạp bằng thẻ có `?v=` và được import không `?v=` chạy 2 instance.
- LEARNED (S11): kiểm trình duyệt có phiên giả cần bảng HR giả (phong_ban, nhan_vien, user, user_permissions) trong nang_suat_test, nếu không /api/admin/lines 500.
- NOTICED (S1): `charts.js` có `showLoading` riêng — đã đổi thành `showChartLoading` ở S4 (spec amended).
- NOTICED (S8): `admin.js` import `api` có thể không dùng; `bulk-create` không có trong `legacyAliases`.
- NOTICED (S9): `views/bieu-do.php` còn dữ liệu `lineTen` không JS nào đọc.
- NOTICED (S10): `views/partials/navbar.php` còn `date_default_timezone_set` không dùng.
- NOTICED (S12): `import-preview.js` còn comment tiếng Anh chép nguyên từ file cũ.
- NOTICED (S4): `utils.js` `applyConfirmContent` tra `#cancelConfirmBtn` không tồn tại (có từ trước).

## Retro notes
- S4 test regex — spec-quality: regex AC2 trong spec (`\(.*\{`) khớp cả call site; orchestrator sửa test + spec Amended.
- S4 `charts.js` `showLoading` — project-knowledge: spec không biết biểu đồ có `showLoading` riêng; tester NOTICED nên kịp Amended.
- S13 — one-off: doc-writer giữ câu cũ "login.js là script thường"; orchestrator sửa.

## Cost check
- Steps by agent: haiku 2 (chore S3, S6) · sonnet 10 (tester S1, implementer S2 S4 S5 S7 S8 S9 S10 S12, doc-writer S13) · orchestrator 2 (S11, S14) · verifier 1 lượt
- Usage (tuỳ chọn, bạn điền): before ...   after ...

## Contract changes
- ~ CSP `script-src 'self' 'unsafe-inline'` → `script-src 'self'` (SecurityHeaders.php:19)
- ~ modules/api.js: +apiUpload, retry csrf_error 1 lần, DELETE gửi body JSON, -getCsrfToken · - modules/admin-api.js
- ~ modules/utils.js: +closeModal, +showConfirmModal (bỏ isHtml), +confirmDialog · - modules/admin-utils.js
- + modules/toast.js · - components/toast.php · + modules/admin/actions.js · + modules/page-data.js · + modules/nav.js · + nav-early.js · + modules/tabs.js · + admin/import-preview.js · + admin/preset-lines.js
- ~ bieu-do/charts.js showLoading → showChartLoading · ~ views/layouts/app.php nạp nav.js không ?v= · ~ views/bieu-do.php window.appConfig → bnsPageData JSON

## Brain updates
- REGISTRY: F-018 → DONE · SYSTEM: Shared FE components (+toast, actions, page-data, nav, tabs; api/utils gộp), Architecture rules (+CSP/no global), csrf-token Used by · DECISIONS: D-009…D-012 · KNOWLEDGE: +4, 2 dòng cũ sửa (API_BASE obsolete, confirm-modal) · BACKLOG: +12, đóng 3 (toast innerHTML, toast include 2 lần/initialParams, history isHtml)

## Open items
- Người dùng chụp lại `tests/smoke/baseline-auth.json`: `php tools/smoke.php --cookie=<HTSSOSESSID> --update` (không commit).
- Thao tác ghi bằng phiên thật (tạo/nộp/duyệt báo cáo, import confirm, xoá) để người dùng thử tay; đã kiểm trên DB test với phiên giả.
- 10 nit của verifier → BACKLOG.
