# 02: Dọn file thừa và gom tài liệu/tool

**What to build:** Repo không còn file rác, tài liệu và tool nằm đúng chỗ, app chạy y như cũ.

- Xoá `indexdept.php`, `contdb.php`, file icon helper mà chỉ `indexdept.php` dùng, `demo-components.php`, file `$null`.
- Bỏ track file bundle backup 46MB và chuyển nó ra ngoài repo. Không viết lại lịch sử git.
- Gộp `plans/` vào `docs/plans/`.
- Chuyển `scripts/` và `build-css.bat` vào `tools/`, sửa đường dẫn trong script build để vẫn build được CSS.

Xem spec: `.scratch/folder-structure/spec.md` (Dọn rác).

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] Các file rác nêu trên không còn trong repo; xác nhận lại 0 tham chiếu trước khi xoá
- [ ] Bundle 46MB không còn được git track và không còn trong thư mục repo
- [ ] `plans/` và `scripts/` không còn ở gốc; nội dung đã nằm ở `docs/plans/` và `tools/`
- [ ] Lệnh build Tailwind chạy từ vị trí mới vẫn tạo đủ 2 file CSS như trước
- [ ] Smoke 2 chế độ: chỉ lệch baseline ở các URL rác đã xoá (cập nhật baseline có chủ đích, ghi rõ trong commit)
- [ ] `php -l` toàn repo (trừ vendor) và `php tests/php/run.php` xanh
