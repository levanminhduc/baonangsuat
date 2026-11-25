# F-017 Dọn thư mục gốc và chuẩn hoá xuống dòng
Status: APPROVED   <!-- bạn đổi thành APPROVED để /go chạy. DOC = chỉ giữ làm tài liệu, không chạy -->
Related: F-016 extends (dọn kho) · F-018, F-019 depends on (chạy sau việc này)
Complexity: standard
Risk: normal
Session model: opus

## 1. Problem & goal
Sau F-016 gốc repo còn rác local (exe 129 MB trùng, ảnh/log Playwright), tài liệu việc đã xong nằm lẫn chỗ việc đang mở, một rule bị hỏng ký tự, và working tree lẫn CRLF/LF (137 file CRLF, 15 file mixed, index toàn LF) làm cs-fixer báo sai, Edit không khớp chuỗi, smoke lệch hash.
"Xong": gốc repo chỉ còn thứ được dùng; mọi file text checkout LF; không còn cảnh báo "LF will be replaced by CRLF".

## 2. Scope
- In: xoá rác local; giữ lại script so computed-style thành tool; `.gitattributes` + renormalize; gọn `.gitignore`; chuyển `.scratch/folder-structure` vào archive; sửa `.claude/rules/tailwind-js.md`; bỏ `includes` khỏi `.htaccess`; xoá `tools/generate-project-banner.py`; sửa 2 mục BACKLOG ghi sai.
- Out (cố ý không làm): đổi quy ước `.scratch/` (CLAUDE.md:282 vẫn khai nó là issue tracker); chuyển `docs/tailwind-offline-design.md` (F-016 cố ý giữ vì mô tả hợp đồng CSS đang sống — .brain/reports/F-016-run.md:78); README lỗi thời (mục BACKLOG riêng); mọi thay đổi code PHP/JS/CSS.

## 3. Acceptance criteria
- AC1 Gốc repo không còn rác.
  - `ls -a` ở gốc không có `tailwindcss-windows-x64.exe`, `.superpowers/`; `.playwright-mcp/` rỗng hoặc không tồn tại.
  - `tools/computed-style-dump.js` tồn tại, được track (nội dung từ `.playwright-mcp/cmp.js`, đường dẫn xuất không còn viết cứng `.playwright-mcp/`).
- AC2 Xuống dòng thống nhất.
  - `git ls-files --eol | rg -c "w/crlf|w/mixed"` chỉ còn các file `*.bat`.
  - `git diff --stat` ngay sau renormalize không có thay đổi nội dung ngoài `.gitattributes` và `tests/smoke/baseline-anon.json`.
- AC3 `.gitignore` không còn mục của công cụ repo không dùng; tập file bị ignore thực tế không đổi.
  - `git ls-files -ci --exclude-standard` rỗng; `git status --short --ignored` trước/sau chỉ khác các mục đã xoá ở AC1.
- AC4 Việc đã xong không nằm trong `.scratch/`.
  - `git ls-files .scratch` rỗng; `docs/archive/folder-structure/spec.md` tồn tại; `rg -n "\.scratch/folder-structure" tests app docs/agents CLAUDE.md AGENTS.md` = 0.
- AC5 Rule và .htaccess đúng.
  - `.claude/rules/tailwind-js.md` dòng 7 chứa nguyên văn `tools\build-css.bat` và `tools\tailwindcss.exe`, không còn ký tự điều khiển.
  - `.htaccess` dòng chặn thư mục không còn `includes`; `curl -s -o NUL -w "%{http_code}" http://localhost/baonangsuat/app/Support/Database.php` = 403.
- AC6 Không hồi quy: `php tests/php/run.php` 0 fail; `vendor/bin/php-cs-fixer fix --dry-run` 0 file; `php tools/smoke.php` khớp baseline (baseline chụp lại trong cùng commit với renormalize, ghi lý do: body đổi CRLF→LF).

## 4. Contracts
- API: `=` toàn bộ `config/routes.php`.
- Data: `=` mọi bảng.
- Events: không có.
- FE: `=` không đổi.
- Repo: `+ .gitattributes`; `+ tools/computed-style-dump.js` (F-019 dùng); `~ .gitignore` (owner F-016); `~ .htaccess` (owner F-014, chỉ bỏ một tên thư mục không tồn tại); `~ tests/smoke/baseline-anon.json` (owner F-014); `- tools/generate-project-banner.py`; `~ .scratch/folder-structure → docs/archive/folder-structure`.

## 5. Overlap check
| Việc liên quan | Chồng ở đâu | Quyết định |
|---|---|---|
| F-016 (AC10 "Kho gọn") | Cùng mục tiêu dọn kho; F-016 đã giữ `docs/tailwind-offline-design.md` có chủ đích | reuse: không đụng file đó |
| F-014 (.htaccess, baseline smoke) | Sửa dòng chặn thư mục, chụp lại baseline | extend không đổi hành vi (thư mục `includes` đã không tồn tại) |
| KNOWLEDGE [git] autocrlf (3 mục) | Bẫy CRLF sẽ hết hiệu lực | cập nhật khi đóng việc |
| BACKLOG "(cleanup) tailwindcss-windows-x64.exe" | Trùng | đưa vào scope |
| BACKLOG "network-monitor.js, time-manager.js có thể là code chết" | Sai: `app.js:6`, `modules/nhap/autosave.js:3`, `modules/luy-ke-calculator.js:2` đang import | sửa mục BACKLOG |
| BACKLOG "(bug?) app.js đọc session.role" | Không còn: `rg "session\.role" public/assets/js` = 0 (chỉ còn `u.role` ở `admin/permissions.js:69`, đúng) | đóng mục BACKLOG |
| 8 file đang sửa chưa commit (CSP, whitelist data-fn…) | Renormalize cần working tree sạch | điều kiện tiên quyết: commit trước khi /go (đã chốt) |

## 6. Decisions
- Chọn: `* text=auto eol=lf` + `*.bat text eol=crlf` + binary cho `*.xlsx *.exe *.png` (durable). Vì index đã toàn LF nên không sinh diff nội dung; hết 3 bẫy trong KNOWLEDGE. Bác bỏ: giữ `core.autocrlf=true` (là cấu hình máy, mỗi máy một kiểu); `eol=crlf` (cs-fixer PSR-12 đòi LF).
- Chọn: giữ `cmp.js` thành `tools/computed-style-dump.js` vì F-019 cần đúng công cụ đã dùng ở F-016 để chứng minh không đổi giao diện. Bác bỏ: xoá cùng thư mục `.playwright-mcp/`.
- Chọn: chuyển việc đã xong ra `docs/archive/`, giữ quy ước `.scratch/` cho việc đang mở. Bác bỏ: bỏ hẳn `.scratch/` (đụng CLAUDE.md và `docs/agents/issue-tracker.md`, ngoài phạm vi).

## 7. Plan
Không có S1 (test): việc này không đổi hành vi code; AC kiểm bằng lệnh.

### S1 [opus] Xoá rác local, giữ script so computed-style
Files: `tailwindcss-windows-x64.exe`, `.superpowers/`, `.playwright-mcp/`, `tools/computed-style-dump.js` (mới)
Do: (1) chép `.playwright-mcp/cmp.js` → `tools/computed-style-dump.js`, đổi hằng `S` (dòng 2, đang là `.playwright-mcp/`) thành thư mục xuất nhận từ tham số/biến đầu file, thêm 3 dòng đầu file nói cách chạy qua Playwright `browser_run_code`. (2) Liệt kê `.superpowers/` và `.playwright-mcp/`; nếu có file không phải log/ảnh/yml/script `live*.js` tạm → DỪNG hỏi. (3) Xoá `tailwindcss-windows-x64.exe` (đã có `tools/tailwindcss.exe` — kiểm `tools\build-css.bat` chạy được trước khi xoá), `.superpowers/`, nội dung `.playwright-mcp/`. (Orchestrator làm vì là thao tác xoá file không nằm trong git.)
Done when: AC1.

### S2 [haiku] Gọn .gitignore
Files: `.gitignore`
Example: chính file này (giữ nguyên cách nhóm theo comment)
Do: xoá các dòng `node_modules/`, `npm-debug.log*`, `yarn-debug.log*`, `yarn-error.log*`, `build/`, `specs/`, `.roo/`, `.kilocode/`, `.kilocode/worktrees/` và comment "Kilo Code agent worktrees", `tailwindcss-windows-x64.exe`, mục "# Node". Giữ mọi dòng khác (kể cả `dist/` + `!public/assets/tailwind/dist/`, `/vendor/` có neo, `nul`).
Check: `git ls-files -ci --exclude-standard` rỗng VÀ `git status --short --ignored` không xuất hiện file `??` mới (AC3).

### S3 [opus] .gitattributes + renormalize + chụp lại baseline
Files: `.gitattributes` (mới), `tests/smoke/baseline-anon.json`
Do: điều kiện: `git status --short` sạch (trừ thay đổi của S1–S2 đã commit). Tạo `.gitattributes` theo mục 6. `git add --renormalize .` → kỳ vọng không có file nào đổi trong index. Ép working tree về LF: `git rm -rq --cached . && git reset -q --hard` (chỉ chạy khi tree sạch — việc duyệt spec này là đồng ý cho lệnh đó). Chạy smoke, chụp lại `baseline-anon.json`, ghi lý do trong commit. (Orchestrator làm vì có `reset --hard` và đổi baseline.)
Done when: AC2, AC6.

### S4 [haiku] Chuyển việc đã xong ra archive
Files: `.scratch/folder-structure/**` → `docs/archive/folder-structure/**`, `tests/php/structure_test.php:2`
Example: `docs/archive/superpowers/` (cách F-016 chuyển bằng `git mv`)
Do: `git mv .scratch/folder-structure docs/archive/folder-structure`; sửa comment dòng 2 của `structure_test.php` trỏ đường dẫn mới. Commit `git mv` tách khỏi commit sửa comment.
Check: `git ls-files .scratch` rỗng VÀ `rg -n "\.scratch/folder-structure" tests app docs/agents CLAUDE.md AGENTS.md` = 0 VÀ `php tests/php/run.php structure` pass (AC4).

### S5 [haiku] Sửa rule hỏng ký tự, .htaccess, xoá script banner
Files: `.claude/rules/tailwind-js.md:7`, `.htaccess:13`, `tools/generate-project-banner.py`
Example: `CLAUDE.md` dòng "tools\build-css.bat" (cách viết đúng)
Do: dòng 7 của rule: thay hai chuỗi hỏng bằng `tools\build-css.bat` và `tools\tailwindcss.exe`. `.htaccess:13`: bỏ `includes|` khỏi nhóm, không đổi gì khác. `git rm tools/generate-project-banner.py`.
Check: `rg -n -F "tools\build-css.bat" .claude/rules/tailwind-js.md` = 1 dòng; `rg -n "includes" .htaccess` = 0; `php tools/smoke.php` khớp baseline; lệnh curl ở AC5 = 403.

### S6 [opus] Update brain
REGISTRY, SYSTEM (dòng tests-tools thêm `tools/computed-style-dump.js`), DECISIONS (quyết định .gitattributes), KNOWLEDGE (đánh dấu 3 mục autocrlf hết hiệu lực), BACKLOG (đóng mục exe, sửa mục code chết, đóng mục `session.role`).

## 8. Verification
- Commands: `php tests/php/run.php` · `vendor/bin/php-cs-fixer fix --dry-run` · `php tools/smoke.php` · `git ls-files --eol`.
- Rủi ro verifier phải soi riêng: commit renormalize không được chứa thay đổi nội dung; `.htaccess` vẫn chặn đủ `app|bootstrap|components|config|database|docs|tests|tools|vendor|views`; `tools\build-css.bat` vẫn chạy sau khi xoá exe ở gốc.

## Open questions
Không còn. Đã chốt với người dùng 2026-11-25:
1. 8 file đang sửa được commit TRƯỚC khi chạy /go F-017 (điều kiện tiên quyết của S3; /go dừng ngay nếu `git status --short` chưa sạch).
2. `tools/generate-project-banner.py`: xoá (S5).
