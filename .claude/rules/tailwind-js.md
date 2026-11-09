# Chuẩn: Tailwind CSS + JavaScript

## Tailwind

- Hiện các trang nạp Tailwind Play CDN (`<script src="https://cdn.tailwindcss.com">` + `tailwind.config`
  inline trong view); không trang nào link `public/assets/tailwind/dist/`. Bản build offline đang chuẩn
  bị cho việc bỏ CDN (`docs/tailwind-offline-design.md`).
- Build offline: `tools\build-css.bat` chạy `tools\tailwindcss.exe` (v4) với
  `public/assets/tailwind/src/{main,no-line}.css` → `dist/` (gitignored).
- v4 quét class theo các dòng `@source` trong file src (đường dẫn tính từ `src/`): `views/`,
  `components/`, `public/assets/js/`. `tailwind.config.js` ở gốc là cấu hình v3 cũ, v4 không đọc.
  Thêm thư mục chứa class → thêm `@source`, build lại, so CSS trước/sau không mất class.
- Màu dùng token (`primary`, `success`, `warning`...), không hard-code hex trong view.

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
