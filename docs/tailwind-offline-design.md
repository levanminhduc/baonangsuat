# Tailwind CSS offline

Mọi trang chạy không cần Internet: không CDN Tailwind, Bootstrap hay Font Awesome; Chart.js nằm local.
Giao diện dùng đúng một file CSS đã build và commit: `public/assets/tailwind/dist/app.css`.

## Cách build

- Công cụ: Tailwind v4.1 standalone CLI (`tools/tailwindcss.exe`, file exe bị gitignore, không cần Node).
- Lệnh: `tools\build-css.bat`, tương đương
  `tools/tailwindcss.exe -i public/assets/tailwind/src/app.css -o public/assets/tailwind/dist/app.css --minify`.
- `dist/app.css` được commit vì server production không có công cụ build. Server không phải build gì khi deploy.
- Đổi class trong `views/`, `components/`, `public/assets/js/` thì chạy lại build và commit `dist/app.css`
  cùng thay đổi đó. Kiểm: build lại xong `git diff --exit-code public/assets/tailwind/dist`.
- Quét class bằng các dòng `@source` trong `src/app.css` (`views/`, `components/`, `public/assets/js/`).
  Class ghép chuỗi lúc chạy không được quét: `z-[101]` (`network-monitor.js`) được khai bằng `@source inline`.
  Không có `tailwind.config.js` (v4 không đọc; hai file config v3 cũ đã xoá).

## Cấu trúc `src/app.css`

Một entry duy nhất, `@import` theo thứ tự sau, tất cả KHÔNG nằm trong `@layer`:

1. `v3-palette.css`: bảng màu v3 dạng hex (v4 đổi sang oklch nên màu lệch nhẹ).
2. CSS tay cũ: `public/assets/css/{style,layout,luy-ke-status,entry,admin}.css`.
3. CSS riêng trang: `guide.css` (huong-dan), `chart.css` (bieu-do).
4. Tailwind preflight.
5. `v3-compat.css`: các mặc định preflight v3 mà view còn dựa vào (màu border, màu placeholder, con trỏ nút,
   nền input, ô ngày).
6. Utilities.
7. `v3-space.css`: `space-x/space-y` theo selector của v3.

Thứ tự là hợp đồng. Dưới Play CDN trước đây, style của Tailwind được chèn sau các `<link>`, nên CSS tay có
specificity cao hơn (vd input trong `.form-group`, `.modal-content`) vẫn thắng utility. Bundle giữ đúng cascade
đó; thêm `layer()` vào các `@import` sẽ đổi giao diện hàng chục control.

Theme token: `primary #143583`, `primary-dark #0f2a66`, `success #4CAF50`, `warning #ff9800`,
`danger #f44336`, cùng thang `shadow-sm`, `blur-sm`, `radius-sm` và line-height chữ theo v3.

## Trang no-line

Không có build thứ hai. `no-line.php` đặt `bodyClass = 'theme-no-line'`; class này ghi đè `--color-primary`
thành `#2196F3` và `--color-primary-dark` thành `#1976D2`.

## Chart.js

`public/assets/vendor/chart.umd.min.js` (4.6.2, ghim version + sha256 trong `public/assets/vendor/README.md`),
nạp bởi `bieu-do.php`.

## Layout

`views/layouts/app.php` là nơi duy nhất viết `<head>`: meta `bns-base`, một `<link>` tới `dist/app.css`,
navbar, script. View khai `$page` và gọi qua `View::page()`.

## Trình duyệt tối thiểu

Output Tailwind v4 cần Chrome/Edge 111+, Safari 16.4+, Firefox 128+.

## Kiểm tra offline

Rút mạng, mở `index.php`, `nhap-nang-suat.php`, `admin.php`, `bieu-do.php`, `huong-dan.php`, `no-line.php`:
tab Network không có request tới host ngoài. `rg -n "https?://" views components public/assets/js` chỉ còn
chuỗi namespace `http://www.w3.org/2000/svg`.
