# Run report — F-017 Dọn thư mục gốc và chuẩn hoá xuống dòng
Spec: ../features/F-017-don-thu-muc-goc.md
Mode: auto
Started: 2026-11-25 (session)   Finished: 2026-11-25
Base: f4811db   Dirty at start: none
Result: PASS

## Summary
Gốc repo sạch (xoá exe 129 MB trùng, .superpowers/, .playwright-mcp/; script so computed style giữ thành `tools/computed-style-dump.js`). `.gitattributes` ép LF: chỉ `tools/build-css.bat` còn CRLF, index không đổi nội dung. `.gitignore` bỏ 12 dòng thừa, tập ignore không đổi. `.scratch/folder-structure` → `docs/archive/` (git mv). Sửa rule tailwind-js.md hỏng ký tự, bỏ `includes` khỏi .htaccess, xoá script banner. Test 72/0 fail, cs-fixer 0, smoke 122 khớp (baseline-anon chụp lại), verifier PASS vòng 1.

## Steps
| Step | Title | Agent (model) | Status | Files changed | Check | Note |
|---|---|---|---|---|---|---|
| S3 | .gitattributes + renormalize + baseline | orchestrator (opus) | DONE | .gitattributes, tests/smoke/baseline-anon.json | eol ✅ smoke ✅ test ✅ cs-fixer ✅ | chạy trước tiên (amended) |
| S1 | Xoá rác local, giữ script computed-style | orchestrator (opus) | DONE | + tools/computed-style-dump.js; xoá exe gốc, .superpowers/, .playwright-mcp/ (untracked) | AC1 ✅ build-css ✅ | hỏi user về .superpowers/ |
| S2 | Gọn .gitignore | chore (haiku) | DONE | .gitignore | AC3 ✅ | |
| S4 | Chuyển .scratch/folder-structure ra archive | chore (haiku) | DONE | git mv → docs/archive/folder-structure, tests/php/structure_test.php:2, tests/smoke/urls.txt:34 (orchestrator) | AC4 ✅ (trừ baseline-auth local) | PLAN_ISSUE cục bộ → amended |
| S5 | Sửa rule, .htaccess, xoá script banner | chore (haiku) + orchestrator | DONE | .htaccess:13, - tools/generate-project-banner.py, .claude/rules/tailwind-js.md:7 (orchestrator), baseline-anon.json | AC5 ✅ smoke ✅ | smoke lệch 403→404 → amended |
| S6 | Update brain | orchestrator (opus) | DONE | .brain/* | — | |

## Step log
### S3 — orchestrator (opus) — DONE
- Files: + .gitattributes (`* text=auto eol=lf`, `*.bat text eol=crlf`, binary xlsx/exe/png); ~ tests/smoke/baseline-anon.json (5 URL body sha1)
- Amended: chạy S3 trước S1/S2/S4/S5 vì `reset --hard` xoá thay đổi chưa commit của các bước kia (/go không commit giữa bước). Sửa brain của chính run được backup ra scratchpad rồi chép lại.
- Check: `git add --renormalize .` → không file nội dung nào đổi trong index; `git ls-files --eol | rg "w/crlf|w/mixed"` → chỉ tools/build-css.bat; `php tests/php/run.php` 72 pass 0 fail 6 skip; cs-fixer 0/70; smoke trước renormalize 122 khớp, sau: 5 body lệch (style.css, admin.css, api.js, realtime-worker.js, logoht.svg) — 4 file đổi lại CRLF ra đúng sha1 baseline cũ, style.css trước là mixed; `--update` → 122 khớp.
- Notes: lần đầu `git rm -rq --cached . && git reset -q --hard` để index rỗng (reset không có tác dụng dù rc=0); chạy lại `git reset --hard` riêng thì đúng.
### S1 — orchestrator (opus) — DONE
- Files: + tools/computed-style-dump.js (từ .playwright-mcp/cmp.js; hằng `S` vốn không dùng → thay bằng `BASE`/`PAGES`/`WIDTHS` đầu hàm, 3 dòng hướng dẫn chạy); xoá `tailwindcss-windows-x64.exe` (sha1 trùng tools/tailwindcss.exe), `.playwright-mcp/` (cmp.js, live2-5.js, png, log, yml), `.superpowers/` (sổ SDD authV2 2026-11-08 — người dùng chọn "Xoá hẳn").
- Check: `tools\build-css.bat` chạy OK (161 ms) trước khi xoá exe; `ls -a` gốc không còn 3 mục; `node` parse OK.
- Notes: build lại sinh dist/app.css lớn hơn 223 byte (class `bg-[#…]`, `p-[30px]`… lấy từ `.brain/features/F-019-*.md`) → Tailwind v4 tự dò source cả repo ngoài các `@source`; đã `git checkout` dist (ngoài scope).
### S2 — chore (haiku) — DONE
- Files: .gitignore (bỏ 12 dòng Node/build/specs/.roo/.kilocode/exe gốc)
- Check: `git ls-files -ci --exclude-standard` rỗng; `git status --short --ignored` trước: 7 mục `!!`, sau: 4 — chỉ thiếu 3 mục đã xoá ở S1 (.playwright-mcp/, .superpowers/, tailwindcss-windows-x64.exe).

### S4 — chore (haiku) — DONE (amended)
- Files: `git mv .scratch/folder-structure docs/archive/folder-structure` (staged, chưa commit); tests/php/structure_test.php:2; tests/smoke/urls.txt:34 `GET /.scratch/folder-structure/spec.md` → `GET /.scratch/` (orchestrator — vẫn kiểm luật chặn dot-dir, vẫn 403); thư mục `.scratch/` rỗng xoá khỏi đĩa.
- Check: `git ls-files .scratch` rỗng; `php tests/php/run.php structure` 6 pass 0 fail; rg đường dẫn cũ trong file được track = 0; còn 1 match ở tests/smoke/baseline-auth.json (gitignored, local) — người dùng chụp lại bằng `--cookie --update`.
- Notes: chore báo PLAN_ISSUE cục bộ (urls.txt ngoài danh sách file) → orchestrator sửa, ghi Amended trong spec. Commit tách `git mv` để /ship làm.

### S5 — chore (haiku) + orchestrator — DONE (amended)
- Files: .htaccess:13 bỏ `includes|`; `git rm tools/generate-project-banner.py`; .claude/rules/tailwind-js.md:7 (orchestrator, vì subagent không ghi .claude/) — thay `toolsuild` và `tools	ailwindcss` (TAB) bằng chuỗi đúng.
- Check: `rg includes .htaccess` = 0; curl app/Support/Database.php = 403; `rg -F 'tools\build-css.bat'` = 1 dòng, không còn ký tự điều khiển.
- Notes: smoke lệch `GET /includes/navbar.php` 403 → 404 (thư mục không tồn tại, giờ rơi vào public/ → 404). Spec nói "không đổi hành vi" là sai ở chi tiết này; chấp nhận 404 (đúng ngữ nghĩa), chụp lại baseline-anon. Smoke sau: 122 khớp.

## Verification
| Round | Runner (haiku) | Verifier (opus) | Blockers |
|---|---|---|---|
| 1 | lint ✅ test ✅ (72/0/6 skip) cs-fixer ✅ smoke ✅ eol ✅ htaccess ✅ build-css ✅ | PASS | 0 |

### Blockers & how they were fixed

## Escalations
| Step | Ladder | Reason |
|---|---|---|
| S5 | chore → orchestrator | smoke lệch /includes/navbar.php 403→404 do spec sai ("không đổi hành vi"), không phải lỗi agent → amend + chụp lại baseline |
| S4 | chore → orchestrator | PLAN_ISSUE cục bộ: tests/smoke/urls.txt ngoài danh sách file |

## Learned & noticed
- LEARNED: `git rm -rq --cached . && git reset -q --hard` trong một lệnh để index rỗng dù rc=0; chạy `git reset --hard` riêng (không -q) mới đúng — luôn kiểm `git ls-files | wc -l` sau bước này.
- LEARNED: Tailwind v4 tự dò source cả repo ngoài `@source` (không có `source(none)`) → build lại nhặt class từ `.brain/features/F-019-*.md` (`bg-[#…]`, `p-[30px]`…), dist khác bản commit 223 byte.
- LEARNED: sửa byte (backslash, ký tự điều khiển) qua python heredoc trong Bash tool không ăn; viết script ra file rồi chạy thì đúng.
- LEARNED (chore): đổi/chuyển file có trong tests/smoke/urls.txt phải sửa urls.txt + chụp lại baseline cùng bước.
- NOTICED: thêm `source(none)` (hoặc tương đương) cho Tailwind để build tất định — F-019 nên làm.
- NOTICED: tests/smoke/baseline-auth.json (local) cũ sau F-017: 5 sha1 asset, /includes/navbar.php 404, key /.scratch/ — chạy `php tools/smoke.php --cookie=<HTSSOSESSID> --update`.
- NOTICED (chore): các file trong docs/archive/folder-structure/issues/ vẫn nhắc đường dẫn cũ bên trong (tài liệu lưu trữ, để nguyên).

## Retro notes
- S5 smoke 403→404 — spec-quality: spec khẳng định bỏ `includes` không đổi hành vi nhưng urls.txt có `/includes/navbar.php`; lúc viết spec nên grep urls.txt/baseline.
- S4 PLAN_ISSUE — spec-quality: Files của S4 thiếu tests/smoke/urls.txt (URL `.scratch/folder-structure/spec.md`).
- S3 thứ tự — spec-quality: S3 có `reset --hard` nhưng xếp sau các bước sửa file chưa commit; /go không commit giữa bước.

## Cost check
- Steps by agent: haiku 3 (1 lần gọi chore) · sonnet 0 · orchestrator 3 · runner 1 · verifier 1
- Usage (tuỳ chọn, bạn điền): before ...   after ...

## Contract changes
- + .gitattributes · + tools/computed-style-dump.js · - tools/generate-project-banner.py
- ~ .gitignore (−13 dòng) · ~ .htaccess:13 (bỏ `includes|`; /includes/navbar.php 403 → 404)
- ~ tests/smoke/baseline-anon.json (5 sha1 asset, /includes 404, key /.scratch/) · ~ tests/smoke/urls.txt:34
- ~ .scratch/folder-structure/** → docs/archive/folder-structure/** · ~ tests/php/structure_test.php:2 (comment) · ~ .claude/rules/tailwind-js.md:7
- = routes, bảng, FE

## Brain updates
- REGISTRY: F-017 → DONE · SYSTEM: tests-tools + computed-style-dump.js · DECISIONS: D-008 · KNOWLEDGE: 2 obsolete, +4 · BACKLOG: đóng 2, sửa 1, +2 · SCORECARD: +F-017

## Open items
- Chụp lại baseline đăng nhập (local): `php tools/smoke.php --cookie=<HTSSOSESSID> --update` (còn key cũ /.scratch/folder-structure/spec.md, 5 sha1 asset, /includes 403).
- /ship: `git add` .gitattributes, tools/computed-style-dump.js, report; commit `git mv` (.scratch → docs/archive) tách khỏi commit đổi code; baseline-anon chụp lại cùng commit renormalize, lý do "body CRLF→LF".
