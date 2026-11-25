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
      Không đặt biến môi trường `BNS_DB_CONFIG` trên server (chỉ dùng cho test, đổi đường dẫn `db.php`).

## Chép code và build

- [ ] `composer install --no-dev --optimize-autoloader` (`vendor/` không nằm trong git; PhpSpreadsheet
      + autoload `App\`).
- [ ] Không cần build CSS trên server: `public/assets/tailwind/dist/app.css` đã nằm trong git. Chỉ chạy
      `tools\build-css.bat` trên máy dev khi đổi class trong `views/`, `components/`, `public/assets/js/`,
      rồi commit file `dist/app.css` cùng thay đổi đó.
- [ ] Xoá trên server các file/thư mục cũ không còn trong repo nếu chép đè: `classes/`, `includes/`,
      `api/`, `csrf.php`, `config/Database.php`, các trang `*.php` ở gốc. File trang cũ còn sót ở
      gốc vẫn bị `.htaccess` đưa vào front controller, nhưng nên dọn.

## Sau khi deploy

- [ ] `http://<host>/baonangsuat/` chuyển sang login authV2 (chưa đăng nhập) với `?redirect=` đúng.
- [ ] `/baonangsuat/app/`, `/baonangsuat/vendor/autoload.php`, `/baonangsuat/.htaccess` → 403.
- [ ] Đăng nhập: chọn LINE, nhập năng suất (auto-save), admin các tab, biểu đồ, import preview.
- [ ] Response API có header CORS như trên local (`curl -I .../api/csrf-token`).
- [ ] Nếu có thể: `php tools/smoke.php --base=<url>` so với baseline (chỉ GET).
- [ ] Offline: rút dây mạng / tắt Internet của máy chủ và máy trạm, mở lần lượt 6 trang (`index.php`,
      `nhap-nang-suat.php`, `admin.php`, `bieu-do.php`, `huong-dan.php`, `no-line.php`): giao diện và
      biểu đồ vẫn đúng, tab Network không có request tới host ngoài.
- [ ] Trình duyệt trạm tối thiểu (Tailwind v4): Chrome/Edge 111+, Safari 16.4+, Firefox 128+. Bản cũ hơn
      hiển thị vỡ màu và khoảng cách.

## F-015: PO / Subart từ datchi

Màn nhập chọn PO + Subart đọc master data từ Postgres `datchi` (chỉ đọc). Làm theo thứ tự dưới đây;
bước 1–4 là cấu hình, bước 5 đổi schema MySQL.
Migration 013 (bước 5) phải chạy TRƯỚC khi deploy code mới: `ChartService` và `BaoCaoService` đọc cột
`po_number`, thiếu cột thì các màn nhập/biểu đồ lỗi SQL.

- [ ] **1. PHP bật `pdo_pgsql`** (PHP 8.2 production). Trong `php.ini` bỏ dấu `;` dòng
      `extension=pdo_pgsql` (Windows: `ext/php_pdo_pgsql.dll`; cần `libpq.dll` nằm trong thư mục PHP
      hoặc trong `PATH`, bản PHP Windows thường kèm sẵn). Restart Apache. Kiểm tra:
      `php -r "var_dump(extension_loaded('pdo_pgsql'));"` → `bool(true)` (hoặc `php -m | findstr pdo_pgsql`).
      Lưu ý `php.ini` của CLI và của Apache có thể là hai file khác nhau (`php --ini`, `phpinfo()`).
- [ ] **2. Role Postgres chỉ đọc** trên database `datchi` (chạy bằng tài khoản quản trị Postgres):
      ```sql
      CREATE ROLE bns_reader LOGIN PASSWORD '<mật-khẩu>';
      GRANT CONNECT ON DATABASE datchi TO bns_reader;
      GRANT USAGE ON SCHEMA public TO bns_reader;
      GRANT SELECT ON purchase_orders, po_items, styles, sub_arts TO bns_reader;
      ALTER ROLE bns_reader SET default_transaction_read_only = on;
      ```
- [ ] **3. Mạng**: server baonangsuat phải tới được `<host>:<port>` của Postgres datchi. Thêm dòng
      `pg_hba.conf` cho đúng IP server + role (vd `host datchi bns_reader <ip-server>/32 scram-sha-256`),
      reload Postgres, mở firewall cổng 5432. Thử từ server baonangsuat:
      `psql -h <host> -U bns_reader -d datchi -c "select 1"`.
- [ ] **4. Cấu hình ngoài repo** `C:/xampp/config/db.php`, thêm hai khoá tuỳ chọn:
      ```php
      'datchi_pg' => ['host' => '<host>', 'port' => 5432, 'dbname' => 'datchi', 'user' => 'bns_reader', 'password' => '<mật-khẩu>'],
      'subart_so_luong_source' => 'manual',
      ```
      Thiếu `datchi_pg` thì app vẫn chạy, chỉ vô hiệu hoá ô chọn PO/Subart và báo
      "Không kết nối được dữ liệu PO". Kết nối timeout 3s, statement timeout 3s.
- [ ] **5. Migration thủ công** `database/migrations/013_add_po_subart.sql` (không có công cụ migrate).
      Sao lưu trước: `mysqldump nang_suat bao_cao_nang_suat > bao_cao_nang_suat.bak.sql`. Cần MySQL 8 /
      MariaDB 10.4+ (cột sinh `STORED`). Migration thêm 4 cột (`po_number`, `sub_art_code`, `po_key`,
      `sub_art_key`), đổi UNIQUE `uk_bao_cao` thêm `po_key`, `sub_art_key`, và tạo bảng
      `po_subart_so_luong`. Chạy: `mysql nang_suat < database/migrations/013_add_po_subart.sql`.
      Kiểm tra: `SHOW INDEX FROM bao_cao_nang_suat WHERE Key_name='uk_bao_cao';` → 6 dòng
      (6 cột: `ngay_bao_cao`, `line_id`, `ca_id`, `ma_hang_id`, `po_key`, `sub_art_key`).
- [ ] **Rollback**: `DROP TABLE po_subart_so_luong;` rồi khôi phục khoá cũ
      `ALTER TABLE bao_cao_nang_suat DROP INDEX uk_bao_cao, DROP INDEX idx_po_ma_hang, ADD UNIQUE KEY uk_bao_cao (ngay_bao_cao, line_id, ca_id, ma_hang_id);`
      (chỉ làm được khi chưa có hai báo cáo trùng ngày/line/ca/mã hàng, nếu không phải xử lý trước),
      cuối cùng `DROP COLUMN` 4 cột `po_key`, `sub_art_key`, `po_number`, `sub_art_code`.
