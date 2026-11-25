# Chuẩn: Tailwind CSS + JavaScript

## Tailwind

- Không CDN. Mọi trang nạp một file `public/assets/tailwind/dist/app.css` qua `views/layouts/app.php`
  (file dist ĐƯỢC commit; server không cần build).
- Build: `toolsuild-css.bat` chạy `tools	ailwindcss.exe` (v4) với `public/assets/tailwind/src/app.css`.
  Đổi class trong `views/`, `components/`, `public/assets/js/` → build lại và commit `dist/app.css`.
- Thứ tự `@import` trong `src/app.css` là hợp đồng, tất cả KHÔNG nằm trong layer: CSS tay
  (`public/assets/css/*.css`) → preflight → `v3-compat.css` → utility → `v3-space.css`. CSS tay có
  specificity cao phải thắng utility như thời CDN; đừng thêm `layer()`.
- `v3-palette.css`, `v3-compat.css`, `v3-space.css` giữ giao diện v3 (bảng màu hex, line-height rem,
  nền input, `space-x/y`). Trang no-line đổi tông bằng class `theme-no-line` trên `<body>`.
- v4 quét class theo các dòng `@source` trong `src/app.css`. Thêm thư mục chứa class → thêm `@source`.
  Class ghép chuỗi trong JS phải nằm trong `@source inline(...)`.
- Màu dùng token (`primary`, `success`, `warning`...), không hard-code hex trong view.
- View mới không dùng lại tên class của CSS tay (`.modal`, `.hidden`, `.line-list`, `.login-*`...) trừ khi
  muốn đúng style đó.
- Trình duyệt tối thiểu của CSS v4: Chrome 111, Safari 16.4, Firefox 128.

## JavaScript

- ES6 module, không bundler. Trang chính `public/assets/js/app.js` (NangSuatApp), admin
  `public/assets/js/admin.js` lazy-load module trong `modules/admin/` theo `deps`.
- Gọi API qua module `api.js` / `admin-api.js`: tự gắn `X-CSRF-Token` cho POST/PUT/DELETE và
  lấy token mới khi nhận `csrf_error: true`. Không `fetch` API trực tiếp từ view.
- Base path lấy từ `<meta name="bns-base">` (layout in ra): `modules/config.js` export `BASE_PATH`,
  `API_BASE`. Không viết cứng `/baonangsuat` trong JS (`login.js` là script thường nên tự đọc meta).
- Không dùng thuộc tính `on*=` trong HTML (kể cả HTML sinh từ JS): gắn `data-action` / `data-fn` +
  `data-args` và listener uỷ quyền (`admin.js` `bindGlobalEvents`, `app.js` `bindEvents`, `history.js`).
- Chèn dữ liệu vào `innerHTML` phải qua `escapeHtml` — chỉ có một bản trong `modules/utils.js`.
- Trang nhập: `app.js` (entry) + `modules/nhap/`; biểu đồ: `bieu-do.js` (entry) + `modules/bieu-do/`.
  Chart.js nạp từ `assets/vendor/chart.umd.min.js`.
- Đồng hồ server qua Web Worker (`workers/realtime-worker.js`), khởi tạo bằng `time()` của PHP.
- Đăng xuất là điều hướng trang tới `Auth::logoutUrl()` (authV2 từ chối `fetch`).

## Asset

- Asset tĩnh nằm trong `public/` (`assets/`, `img/`); URL giữ nguyên dạng `/baonangsuat/assets/...`.
- Đường dẫn ảnh/asset trong view viết tương đối (`img/logoht.svg`), không thêm `public/`.
