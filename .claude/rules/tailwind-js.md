# Chuẩn: Tailwind CSS + JavaScript

## Tailwind

- Tailwind 3 offline, không npm: `tools\build-css.bat` dùng binary trong repo, input/output ở
  `public/assets/tailwind/`.
- Content glob (`tailwind.config.js`): `views/**`, `components/**`, `public/assets/js/**`, `app/**`.
  Thêm thư mục chứa class Tailwind → thêm vào glob, build lại, kiểm CSS không mất class.
- Màu dùng token trong config (`primary`, `success`, `warning`...), không hard-code hex trong view.

## JavaScript

- ES6 module, không bundler. Trang chính `public/assets/js/app.js` (NangSuatApp), admin
  `public/assets/js/admin.js` lazy-load module trong `modules/admin/` theo `deps`.
- Gọi API qua module `api.js` / `admin-api.js`: tự gắn `X-CSRF-Token` cho POST/PUT/DELETE và
  lấy token mới khi nhận `csrf_error: true`. Không `fetch` API trực tiếp từ view.
- `API_BASE = '/baonangsuat/api'` đang hard-code trong `api.js`, `admin-api.js`, `admin/import.js`, `login.js` —
  chạy dưới vhost (không có `/baonangsuat`) cần sửa chỗ này trước.
- Đồng hồ server qua Web Worker (`workers/realtime-worker.js`), khởi tạo bằng `time()` của PHP.
- Đăng xuất là điều hướng trang tới `Auth::logoutUrl()` (authV2 từ chối `fetch`).

## Asset

- Asset tĩnh nằm trong `public/` (`assets/`, `img/`); URL giữ nguyên dạng `/baonangsuat/assets/...`.
- Đường dẫn ảnh/asset trong view viết tương đối (`img/logoht.svg`), không thêm `public/`.
