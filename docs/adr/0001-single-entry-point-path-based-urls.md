# Một entry point, URL dạng đường dẫn (không dùng `?page=` / `?action=`)

Khi chuẩn hoá cây thư mục theo nhansuhtdb (`public/` + `bootstrap/` + `app/` PSR-4 + Router), mọi request đi qua `public/index.php` nhưng baonangsuat **giữ nguyên URL cũ**: trang vẫn là `/baonangsuat/nhap-nang-suat.php`, API vẫn là REST `/baonangsuat/api/<resource>/...`. `.htaccess` gốc rewrite về `public/`, Router khớp theo `METHOD + đường dẫn` khai trong `config/routes.php`. Lý do: bookmark, link `?redirect=` của authV2, navbar và ~4 file JS gọi API không phải đổi, và không thêm một lượt redirect 302 như shim của nhansuhtdb.

## Considered Options

- **`index.php?page=x` + `?action=module.method` như nhansuhtdb**: đồng bộ tuyệt đối với nhansuhtdb, nhưng phải đổi toàn bộ URL API REST, viết shim redirect cho từng trang cũ, và Router copy về vẫn phải giữ nhánh path-based cho API. Bị loại.

## Consequences

- `App\Http\Router` là bản copy có chỉnh của nhansuhtdb: thêm khớp theo đường dẫn. Khi đồng bộ code Router giữa hai repo, đừng "sửa" phần khớp đường dẫn về `?page=`.
- Không được thêm `RewriteBase` vào `.htaccess` gốc (cùng lý do lichkehoach: phải chạy cả dưới `localhost/baonangsuat/` lẫn vhost).
