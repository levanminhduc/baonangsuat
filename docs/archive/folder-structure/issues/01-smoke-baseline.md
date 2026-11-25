# 01: Smoke test + baseline trước refactor

**What to build:** Một lệnh smoke test gọi qua Apache thật mọi URL của app (6 trang, mọi route API theo method, vài asset gồm css/js/worker/img, và các thư mục code sẽ bị chặn) rồi so kết quả với baseline. Có hai chế độ:

- Chưa đăng nhập: trang so mã HTTP và đích `Location`, API so mã HTTP và JSON shape.
- Đăng nhập bằng cookie `HTSSOSESSID` truyền qua tham số: so mã HTTP và JSON body của các GET API, mã HTTP của trang. Không gọi POST/PUT/DELETE.

Baseline được chụp trên code hiện tại (chưa refactor) rồi commit, làm thước đo "hành vi không đổi" cho mọi ticket sau. Ticket này cũng tạo branch `refactor/folder-structure` và sửa `.gitignore` để `tests/`, `tools/` (trừ file `.exe`) và `.claude/rules/` được track.

Xem spec: `.scratch/folder-structure/spec.md` (Testing Decisions), ADR `docs/adr/0001-single-entry-point-path-based-urls.md`.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] Branch `refactor/folder-structure` tồn tại, mọi commit của đợt refactor nằm trên đó
- [ ] `.gitignore` không còn ignore `tests/`, `tools/`, `.claude/rules/`; vẫn ignore tailwind `.exe`
- [ ] Smoke chạy được ở cả hai chế độ, in diff rõ URL nào lệch baseline, exit code ≠ 0 khi lệch
- [ ] Danh sách URL phủ đủ 6 trang, mọi route API hiện có (đối chiếu 11 hàm handle + `csrf-token`), asset, và các thư mục `classes/ config/ database/ docs/ tests/ tools/ vendor/`
- [ ] Baseline cả hai chế độ được chụp trên code chưa refactor và commit; chạy lại smoke ngay sau đó cho diff = 0
- [ ] `php tests/php/run.php` vẫn xanh
