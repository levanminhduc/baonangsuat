# Kiến trúc: một web root, một front controller, PSR-4

> Bức tranh chung và từ vựng cho 5 rule còn lại. Mâu thuẫn với `CLAUDE.md` gốc → CLAUDE.md thắng.
> Cùng khuôn với nhansuhtdb, lichkehoach, khsanxuat; sửa ở đây thì xem bên kia có cần sửa theo.
> Quyết định nền: `docs/adr/0001-single-entry-point-path-based-urls.md`.

## Cây thư mục

```
baonangsuat/
├── .htaccess        rewrite mọi request vào public/, chặn thư mục code + dotfile (403)
├── public/          web root: index.php (front controller duy nhất), assets/, img/
├── bootstrap/       autoload.php (hằng BNS_ROOT + Composer), app.php (+ phiên authV2)
├── config/          routes.php (bảng route trang + API)
├── app/             PSR-4 App\ : Auth/, Controllers/, Http/ (Router, Request, Response,
│                    Middleware), Services/, Support/ (Database, View)
├── views/           HTML trang + partials/ (navbar)
├── components/      UI component dùng chung (toast, modal, tabs...)
├── database/        schema + migrations
├── tests/           php/ (runner + test), smoke/ (baseline URL)
├── tools/           build-css.bat, smoke.php, script phụ
└── docs/            adr/, plans/, tài liệu thiết kế
```

## Luồng một request

```
URL cũ (/baonangsuat/admin.php, /baonangsuat/api/bao-cao/5)
  → .htaccess gốc → public/index.php
  → bootstrap/autoload.php; API: Cors (OPTIONS dừng ở đây, chưa mở phiên)
  → bootstrap/app.php (phiên authV2)
  → App\Http\Router + config/routes.php → middleware → Controller::method
  → JsonResponse | HtmlResponse (View::render) | RedirectResponse → send()
```

## Từ vựng

- **Front controller**: `public/index.php`, chỗ duy nhất nhận request PHP.
- **Route**: một dòng trong `config/routes.php` — METHOD + path → Controller::method + middleware.
- **Nhóm API**: `/api/<group>`; middleware nhóm chạy trước khi khớp route, giữ message lỗi cũ.
- **Trang**: route trong `pages`, URL `*.php` giữ nguyên; PageController lo quyền/LINE rồi render view.
- **Baseline smoke**: mã HTTP + Location/JSON của mọi URL, chụp trước refactor (`tests/smoke/`).

## Cấm

Thư mục code ngoài cây trên; trang `.php` ở gốc repo hay trong `public/` (`structure_test` khoá).
