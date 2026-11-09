# 06: Chuyển API nghiệp vụ sang controller

**What to build:** Mọi API nghiệp vụ (nhập và xem năng suất) đi qua Router. Các nhóm `bao-cao`, `danh-muc`, `moc-gio-sets`, `bao-cao-history`, `import`, `import-history`, `bieu-do` thành controller trong `App\Controllers`, route và middleware khai trong bảng route. Import Excel (upload, preview, confirm qua PhpSpreadsheet) chạy như cũ. Sau ticket này router API cũ chỉ còn các nhóm admin.

Xem spec: `.scratch/folder-structure/spec.md` (Controllers).

**Blocked by:** 05

**Status:** ready-for-agent

- [ ] 7 nhóm trên có controller + route; router API cũ không còn nhánh tương ứng
- [ ] Middleware login/LINE/role/CSRF áp đúng như kiểm tra cũ của từng endpoint (đối chiếu từng hàm handle)
- [ ] Import Excel thử tay một file mẫu: preview + confirm thành công
- [ ] Smoke 2 chế độ diff = 0; `php -l`, php-cs-fixer dry-run, `php tests/php/run.php` xanh
