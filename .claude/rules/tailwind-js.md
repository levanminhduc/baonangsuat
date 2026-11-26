# Chuẩn: Tailwind CSS + JavaScript

## Tailwind

- Không CDN. Mọi trang nạp một file `public/assets/tailwind/dist/app.css` qua `views/layouts/app.php`
  (file dist ĐƯỢC commit; server không cần build).
- Build: `tools\build-css.bat` chạy `tools\tailwindcss.exe` (v4) với `public/assets/tailwind/src/app.css`.
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
- Gọi API chỉ qua `modules/api.js` (`api`, `apiUpload`, `fetchCsrfToken`; `fetch(` chỉ được nằm ở đây):
  request ghi tự lấy token khi chưa có, gắn `X-CSRF-Token`; gặp `csrf_error: true` thì làm mới token
  và gửi lại đúng 1 lần.
- Base path lấy từ `<meta name="bns-base">` (layout in ra): `modules/config.js` export `BASE_PATH`,
  `API_BASE`. Không viết cứng `/baonangsuat` trong JS.
- Không dùng thuộc tính `on*=` trong HTML (kể cả HTML sinh từ JS): gắn `data-action` / `data-fn` +
  `data-args` và listener uỷ quyền (`admin.js` `bindGlobalEvents`, `app.js` `bindEvents`, `history.js`).
  Hàm sau `data-fn` của admin đăng ký ở cuối module bằng `registerActions({...})`
  (`modules/admin/actions.js`); `admin.js` chỉ gọi `runAction`. Không gán `window.*`.
- Không `<script>` inline thực thi trong `views/`, `components/` (CSP `script-src 'self'`): code vào file
  JS; dữ liệu PHP → JS qua `<script type="application/json" id="...">` + `readPageData(id)`
  (`modules/page-data.js`).
- Một bộ helper `modules/utils.js` (`escapeHtml`, `showLoading/hideLoading`, `showToast`, `closeModal`,
  `showConfirmModal`, `confirmDialog`); toast là module `modules/toast.js`. Không `confirm()`/`alert()`.
- Chèn dữ liệu vào `innerHTML` phải qua `escapeHtml` — chỉ có một bản trong `modules/utils.js`.
- Module mà layout nạp bằng thẻ `<script>` và module khác cũng `import` thì thẻ phải trỏ đúng URL import
  (không `?v=`), nếu không trình duyệt chạy module hai lần (vd. `modules/nav.js`).
- Trang nhập: `app.js` (entry) + `modules/nhap/`; biểu đồ: `bieu-do.js` (entry) + `modules/bieu-do/`.
  Chart.js nạp từ `assets/vendor/chart.umd.min.js`.
- Đồng hồ server qua Web Worker (`workers/realtime-worker.js`), `modules/nav.js` khởi tạo từ
  `data-server-time` của `#server-clock`. Sidebar: `nav-early.js` (script thường, chống nháy) + `nav.js`
  (`setActiveNav`), layout tự nạp `nav.js` khi trang có navbar.
- Đăng xuất là điều hướng trang tới `Auth::logoutUrl()` (authV2 từ chối `fetch`).

## Asset

- Asset tĩnh nằm trong `public/` (`assets/`, `img/`); URL giữ nguyên dạng `/baonangsuat/assets/...`.
- Đường dẫn ảnh/asset trong view viết tương đối (`img/logoht.svg`), không thêm `public/`.
