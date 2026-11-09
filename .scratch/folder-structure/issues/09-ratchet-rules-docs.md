# 09: Ratchet cấu trúc + rule + tài liệu + nghiệm thu

**What to build:** Khoá cấu trúc mới bằng test và mô tả nó cho người và agent làm sau.

- Thêm `structure_test` vào runner `tests/php`, kiểm tra:
  - gốc repo không còn file `.php` nào;
  - `public/` chỉ có front controller và asset;
  - mọi route trỏ tới class/method có thật;
  - `app/` không còn `require_once` nạp class.
- Tạo `.claude/rules/` gồm 6 file chỉnh từ nhansuhtdb: target-architecture, psr4-autoload, psr12-code-style, single-entry-point-routing, refactor-verification-gates, tailwind-js.
- Viết lại phần Architecture, Entry Points, Frontend của `CLAUDE.md` và `AGENTS.md` theo cây mới.
- Thêm checklist trước khi deploy lên XAMPP: mod_rewrite, AllowOverride All, authV2 nằm cạnh, PHP 8.2, build CSS.
- Nghiệm thu toàn bộ đợt refactor.

Xem spec: `.scratch/folder-structure/spec.md`.

**Blocked by:** 07, 08

**Status:** ready-for-agent

- [ ] `structure_test` xanh, và đỏ khi thử thêm một file `.php` ở gốc (thử xong thì bỏ file đó)
- [ ] 6 file rule tồn tại, không còn nhắc tới `classes/`, `includes/` hay trang nằm ở gốc
- [ ] `CLAUDE.md`, `AGENTS.md`, `README.md` không còn mô tả cấu trúc cũ
- [ ] Checklist deploy nằm trong `docs/`
- [ ] Mọi bước kiểm tra xanh: `php -l`, php-cs-fixer dry-run, `php tests/php/run.php`, smoke 2 chế độ diff = 0
- [ ] User đăng nhập thử tay và xác nhận trước khi merge `refactor/folder-structure` về `main`
