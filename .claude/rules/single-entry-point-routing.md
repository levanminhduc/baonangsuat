# Rule: Một entry point, route theo METHOD + path

> Quyết định: `docs/adr/0001-single-entry-point-path-based-urls.md`. URL trang `*.php` và URL API
> REST giữ nguyên; không dùng `?page=` / `?action=`, không thêm redirect 302 mới.

## Front controller (`public/index.php`)

1. Nạp `bootstrap/autoload.php`, dựng `Request::fromGlobals()` (path đã bỏ base `/baonangsuat`).
2. API: `Cors::send()`; `OPTIONS` thoát ngay, trước khi mở phiên.
3. Nạp `bootstrap/app.php` (phiên authV2), `Router::dispatch()` → `send()`.

`.htaccess` gốc đưa `/api/...` và mọi `<tên>.php` ở gốc vào đây; phần còn lại phục vụ tĩnh từ `public/`.

## Bảng route (`config/routes.php`)

```php
'pages'  => ['middleware' => ['security-headers', 'auth'], 'routes' => [['*', '/admin.php', AdminPageController::class, 'show']]],
'groups' => ['bao-cao' => ['middleware' => ['report'], 'not_found' => [404, 'Endpoint không hợp lệ']]],
'routes' => [['PUT', '/api/bao-cao/{id:\d+}/entries', BaoCaoController::class, 'updateEntries', [$writer, 'csrf']]],
```

- Route khai trước thắng. Khớp path sai method → 405 (hoặc message của nhóm).
- Tham số `{name}` hoặc `{name:regex}`; regex khớp trong MỘT đoạn và không được chứa `/`.
- Middleware: `auth`, `line`, `role:a,b`, `csrf`, `report`, `history`, `import`, `security-headers`
  (`Router::MIDDLEWARE`). Thêm middleware mới = class trong `app/Http/Middleware` + khoá trong map.
- Nhóm `/api/<group>` không khai → 404 'API không tồn tại'. Trang không khai → 404 HTML.

## Controller

- API: method nhận `Request`, trả `JsonResponse`. Shape `{success, message?, data?}` giữ như cũ;
  lỗi dùng `JsonResponse::error($message, $status)`. Exception → Router trả 500 kèm message.
- Trang: `XxxPageController::show()` kiểm role/LINE/whitelist, trả `RedirectResponse('trang.php')`
  (Location tương đối như cũ) hoặc `HtmlResponse(View::render('trang', [...]))`.
- Đọc input qua `Request` (`param`, `query`, `jsonBody`, `header`), không đọc `$_GET` trong controller
  mới (ngoại lệ: `$_FILES` cho import).

## Cấm

- File PHP mới ở gốc repo hoặc `public/` (`structure_test` khoá).
- Kiểm đăng nhập/CSRF bằng tay đầu controller khi đã có middleware.
- Đổi URL hoặc shape JSON mà JS đang dùng mà không sửa JS cùng commit.
