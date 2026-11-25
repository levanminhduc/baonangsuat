# 03: PSR-4 + bootstrap một chỗ nạp

**What to build:** Mọi class của app có namespace `App\...` và được Composer autoload từ `app/`. Mọi file thực thi (trang, router API, test, tool) mở đầu bằng đúng một dòng require `bootstrap/app.php`, không còn `require_once` nạp class lẻ. Thư mục `classes/` không còn.

- Database helper thành `App\Support\Database`, vẫn đọc cấu hình DB và admin whitelist nằm ngoài repo.
- Helper CSRF thành class trong `App\Http`, giữ nguyên session key và hành vi.
- Bootstrap nạp một lần duy nhất: autoload, hằng đường dẫn gốc, timezone, authV2 bootstrap. Không dùng `auto_prepend_file`.
- Đổi tên một lần, không dùng `class_alias`.
- Composer khoá PHP 8.2 (platform 8.2.12). Thêm cấu hình php-cs-fixer PSR-12 chép từ nhansuhtdb, áp dụng cho `app/ bootstrap/ config/`.
- Commit chỉ di chuyển file (`git mv`) tách khỏi commit sửa code.

Xem spec: `.scratch/folder-structure/spec.md` (Class cũ → PSR-4, Bootstrap, Composer, PSR-12).

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] `classes/` không còn; mọi class nằm trong `app/` (Auth/, Services/, Support/, Http/) với namespace khớp PSR-4
- [ ] `composer dump-autoload` không cảnh báo PSR-4; `composer.json` khai `App\` → `app/`, PHP >=8.2, platform 8.2.12
- [ ] Không còn `require_once`/`require` nạp class nào ngoài dòng require bootstrap (kiểm bằng rg)
- [ ] Chỉ còn một chỗ require authV2 bootstrap
- [ ] php-cs-fixer dry-run xanh trên `app/ bootstrap/ config/`
- [ ] `line_selection_test` nạp class qua autoload
- [ ] Smoke 2 chế độ diff = 0; `php -l` và `php tests/php/run.php` xanh
