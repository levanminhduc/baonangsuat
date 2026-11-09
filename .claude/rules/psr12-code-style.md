# Rule: PSR-12 code style

## Công cụ

- `.php-cs-fixer.dist.php`: @PSR12 + vài rule an toàn (array ngắn, trailing comma, bỏ import thừa,
  sắp import, nháy đơn). Phạm vi: `app/`, `bootstrap/`, `config/`.
- Sửa: `vendor/bin/php-cs-fixer fix`. Kiểm: `vendor/bin/php-cs-fixer fix --dry-run` phải ra 0 file.
- Cố ý KHÔNG bật `strict_comparison`, `declare_strict_types` cho cả cây: đổi hành vi so sánh/ép kiểu
  của code nghiệp vụ cũ. `app/Http/` (code mới) có `declare(strict_types=1);`, controller và service
  cũ thì không — đừng tự thêm khi sửa bug.

## Quy ước

- PHP 8.2 (production). Local chạy 8.3 nhưng không dùng cú pháp 8.3+.
- Khung file class: `<?php`, dòng trống, `namespace`, dòng trống, các `use`, class.
- SQL dùng prepared statement (mysqli), không nối chuỗi đầu vào.
- `ma_nv` luôn `strtoupper(trim(...))` trước khi dùng.
- Comment viết tiếng Việt, ngắn, nói "vì sao"; không PHPDoc lặp lại kiểu đã khai.

## Template (`views/`, `components/`)

- Không thuộc PSR-12, không chạy cs-fixer. HTML chuyển gần như nguyên văn từ trang cũ.
- Xuất dữ liệu luôn qua `htmlspecialchars()`; JSON nhúng trong `<script>` dùng `JSON_HEX_TAG | JSON_HEX_AMP`.
- Không truy vấn DB trong view; dữ liệu do PageController truyền qua `View::render()`.
