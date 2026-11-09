# Checklist deploy lên XAMPP (sau refactor cấu trúc thư mục)

Áp dụng lần deploy đầu tiên của nhánh `refactor/folder-structure` và mọi lần deploy sau.

## Trước khi chép code

- [ ] PHP ≥ 8.2 (`php -v`). Code không dùng cú pháp 8.3+.
- [ ] Apache bật `mod_rewrite` (`httpd -M | findstr rewrite`). `.htaccess` gốc cố ý không bọc
      `IfModule`: thiếu mod_rewrite → 500 ngay, không lộ `app/`, `vendor/`...
- [ ] `<Directory>` của htdocs có `AllowOverride All` (hoặc ít nhất `FileInfo Options Indexes`), nếu
      không `.htaccess` bị bỏ qua và thư mục code lộ qua web.
- [ ] Kiểm `headers_module`: trước refactor, `api/.htaccess` dùng `Header set` (bọc `IfModule`) ghi đè
      CORS. Giờ PHP tự gửi giá trị đó (`App\Http\Cors`) nên không còn phụ thuộc mod_headers, nhưng nếu
      production trước đây TẮT mod_headers thì client từng nhận ACAO theo allowlist localhost, nay nhận `*`.
      Ghi nhận và báo nếu khác.
- [ ] `authV2` nằm cạnh thư mục dự án (`htdocs/authV2`), cùng host — `bootstrap/app.php` nạp
      `../authV2/bootstrap.php`.
- [ ] File cấu hình ngoài repo còn nguyên: `C:/xampp/config/db.php`, `C:/xampp/config/admin_whitelist.php`.

## Chép code và build

- [ ] `composer install --no-dev --optimize-autoloader` (`vendor/` không nằm trong git; PhpSpreadsheet
      + autoload `App\`).
- [ ] (Tuỳ chọn) `tools\build-css.bat` (`public/assets/tailwind/dist/` không nằm trong git). Hiện các
      trang vẫn nạp Tailwind CDN, chưa trang nào link `dist/`, nên bước này chưa ảnh hưởng giao diện.
- [ ] Xoá trên server các file/thư mục cũ không còn trong repo nếu chép đè: `classes/`, `includes/`,
      `api/`, `csrf.php`, `config/Database.php`, các trang `*.php` ở gốc. File trang cũ còn sót ở
      gốc vẫn bị `.htaccess` đưa vào front controller, nhưng nên dọn.

## Sau khi deploy

- [ ] `http://<host>/baonangsuat/` chuyển sang login authV2 (chưa đăng nhập) với `?redirect=` đúng.
- [ ] `/baonangsuat/app/`, `/baonangsuat/vendor/autoload.php`, `/baonangsuat/.htaccess` → 403.
- [ ] Đăng nhập: chọn LINE, nhập năng suất (auto-save), admin các tab, biểu đồ, import preview.
- [ ] Response API có header CORS như trên local (`curl -I .../api/csrf-token`).
- [ ] Nếu có thể: `php tools/smoke.php --base=<url>` so với baseline (chỉ GET).
