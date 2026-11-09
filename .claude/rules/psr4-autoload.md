# Rule: PSR-4 autoload cho class trong `app/`

> Từ vựng xem `target-architecture.md`.

## Bản đồ namespace

`composer.json` là nguồn sự thật: `App\` → `app/`. Một class một file, tên file = tên class.

| Namespace | Thư mục | Chứa |
|---|---|---|
| `App\Auth` | `app/Auth` | `Auth` (phiên authV2, vai trò, quyền), `LineSelection` |
| `App\Controllers` | `app/Controllers` | `XxxController` (API), `XxxPageController` (trang) |
| `App\Http` | `app/Http` | Router, Request, Cors, CsrfToken, các Response |
| `App\Http\Middleware` | `app/Http/Middleware` | `MiddlewareInterface` + middleware |
| `App\Services` | `app/Services` | logic nghiệp vụ (NangSuatService, AdminService...) |
| `App\Support` | `app/Support` | Database, View |

## Cách nạp

- Chỉ `bootstrap/autoload.php` gọi `vendor/autoload.php`. Không `require_once` file class ở bất kỳ đâu
  (`tests/php/structure_test.php` khoá trong `app/`).
- Entry (front controller, test runner, tool CLI) nạp `bootstrap/app.php` hoặc `bootstrap/autoload.php`.
- Thêm class mới không cần `composer dump-autoload` (PSR-4); thêm namespace gốc mới thì cần.

## Dùng class

- `use App\...;` ở đầu file, không viết FQCN trong thân code.
- View dùng class thì thêm `<?php use App\Auth\Auth; ?>` ở dòng đầu view.

## Cấm

- Class ngoài `app/`; namespace không khớp thư mục; hai class trong một file.
- Gọi `vendor/autoload.php` trực tiếp ngoài `bootstrap/autoload.php`.
