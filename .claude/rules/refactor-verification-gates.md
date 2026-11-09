# Rule: Gate kiểm chứng cho thay đổi PHP

## Gate cho MỌI commit chạm PHP

```bash
while read -r f; do php -l "$f" >/dev/null || echo "LINT FAIL $f"; done < <(rg --files -g '*.php' -g '!vendor/**')
vendor/bin/php-cs-fixer fix --dry-run          # 0 file
php tests/php/run.php                          # 0 fail (gồm structure_test)
php tools/smoke.php                            # chưa đăng nhập: khớp baseline
php tools/smoke.php --cookie=<HTSSOSESSID>     # đăng nhập: chỉ gọi GET, khớp baseline
```

- Smoke so mã HTTP + `Location` (trang) và JSON (API) với baseline trong `tests/smoke/`.
  Baseline chế độ đăng nhập (`baseline-auth.json`) chứa dữ liệu nhân viên thật → gitignore, không commit.
- Cookie phiên chỉ truyền qua tham số, không ghi vào file nào trong repo.
- Đổi URL có chủ đích: thêm URL vào `tests/smoke/urls.txt` và chụp lại baseline trong cùng commit, ghi lý do.

## Khi đổi trang / view / JS

- So header + HTML trang trước/sau (curl có cookie) — chỉ được khác đồng hồ server.
- Đổi file trong content glob của Tailwind → chạy `tools\build-css.bat`, CSS phải không mất class.
- Thao tác ghi (POST/PUT/DELETE, import confirm) không chạy bằng phiên thật; để user thử tay.

## Commit

- Commit di chuyển file (`git mv`) tách khỏi commit đổi code.
- Không `--no-verify`, không commit cookie / baseline-auth.
