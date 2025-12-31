# Kế hoạch AJAX Partial DOM Update cho Preset Mốc Giờ

## Overview
Mục tiêu là tối ưu thao tác ở tab Preset Mốc Giờ trong admin để cập nhật giao diện theo từng phần (partial DOM update), tránh gọi lại toàn bộ danh sách hoặc refresh toàn trang. Phạm vi chỉ gồm các thao tác preset và gán LINE như đang được triển khai trong [`assets/js/admin.js`](assets/js/admin.js).

Nguồn tham chiếu chính:
- Frontend: [`assets/js/admin.js`](assets/js/admin.js)
- API router: [`api/index.php`](api/index.php)

## Architecture
- Frontend lưu trạng thái tại chỗ (in-memory) qua `presetsData`, `currentPresetDetail`, `assignedLines` trong [`admin.js:loadPresets()`](assets/js/admin.js:1054).
- API cung cấp CRUD preset và gán LINE thông qua handler [`index.php:handleMocGioSets()`](api/index.php:586).
- UI render theo mô hình: data state -> render DOM (bảng preset, modal chi tiết, modal gán LINE) qua các hàm render trong [`admin.js`](assets/js/admin.js).

## Data flow
1) Tải danh sách preset ban đầu
- GET `/moc-gio-sets` trong [`admin.js:loadPresets()`](assets/js/admin.js:1054) -> cập nhật `presetsData` -> render lại bảng qua [`admin.js:renderPresetsTable()`](assets/js/admin.js:1066).

2) Xem chi tiết preset
- GET `/moc-gio-sets/{id}` + GET `/moc-gio-sets/{id}/lines` trong [`admin.js:viewPresetDetail()`](assets/js/admin.js:1183) -> cập nhật `currentPresetDetail`, `assignedLines` -> render modal chi tiết qua [`admin.js:renderPresetDetailModal()`](assets/js/admin.js:1202).

3) Gán / bỏ gán LINE
- POST `/moc-gio-sets/{id}/lines` trong [`admin.js:handleAssignLines()`](assets/js/admin.js:1310) và DELETE `/moc-gio-sets/{id}/lines` trong [`admin.js:unassignLine()`](assets/js/admin.js:1336) -> hiện tại gọi lại [`admin.js:viewPresetDetail()`](assets/js/admin.js:1183) để refresh modal.

4) Thêm / sửa / xóa / copy preset
- POST `/moc-gio-sets` trong [`admin.js:handlePresetSubmit()`](assets/js/admin.js:1132)
- PUT `/moc-gio-sets/{id}` trong [`admin.js:handlePresetSubmit()`](assets/js/admin.js:1132)
- DELETE `/moc-gio-sets/{id}` trong [`admin.js:deletePreset()`](assets/js/admin.js:1166)
- POST `/moc-gio-sets/copy` trong [`admin.js:handleCopyPreset()`](assets/js/admin.js:1366)
Hiện tại sau mỗi thao tác đều gọi lại [`admin.js:loadPresets()`](assets/js/admin.js:1054).

## Danh sách thao tác cần AJAX update (Partial DOM Update)
### 1) Thêm preset mới
- API: POST `/moc-gio-sets`
- Nơi gọi: [`admin.js:handlePresetSubmit()`](assets/js/admin.js:1132)
- DOM cần cập nhật:
  - `#presetsTable tbody` (thêm dòng mới)
  - Nếu modal đang mở: đóng `#presetModal`
- Logic đề xuất:
  - Từ response trả về preset mới (hoặc gọi GET theo ID nếu API chưa trả data).
  - Chèn hàng mới vào `presetsData`.
  - Gọi render cục bộ cho table (hoặc append row) thay vì `loadPresets()`.
- Ưu tiên: Cao

### 2) Sửa preset
- API: PUT `/moc-gio-sets/{id}`
- Nơi gọi: [`admin.js:handlePresetSubmit()`](assets/js/admin.js:1132)
- DOM cần cập nhật:
  - `#presetsTable tbody` (update hàng tương ứng)
  - Nếu đang mở modal chi tiết: cập nhật tiêu đề `#presetDetailTitle` và metadata nếu có
- Logic đề xuất:
  - Cập nhật object trong `presetsData` theo `id`.
  - Rerender row hoặc rerender toàn table body.
  - Nếu `currentPresetDetail.id` trùng, cập nhật `currentPresetDetail` và rerender modal.
- Ưu tiên: Cao

### 3) Xóa preset
- API: DELETE `/moc-gio-sets/{id}`
- Nơi gọi: [`admin.js:deletePreset()`](assets/js/admin.js:1166)
- DOM cần cập nhật:
  - `#presetsTable tbody` (remove row)
  - Nếu đang xem chi tiết preset bị xóa: đóng `#presetDetailModal`
- Logic đề xuất:
  - Remove preset khỏi `presetsData`.
  - Rerender table body hoặc remove row trực tiếp.
- Ưu tiên: Cao

### 4) Copy preset
- API: POST `/moc-gio-sets/copy`
- Nơi gọi: [`admin.js:handleCopyPreset()`](assets/js/admin.js:1366)
- DOM cần cập nhật:
  - `#presetsTable tbody` (thêm dòng mới)
- Logic đề xuất:
  - Dùng preset mới từ response để insert vào `presetsData`.
  - Render lại table body.
- Ưu tiên: Trung bình

### 5) Xem chi tiết preset
- API: GET `/moc-gio-sets/{id}` + GET `/moc-gio-sets/{id}/lines`
- Nơi gọi: [`admin.js:viewPresetDetail()`](assets/js/admin.js:1183)
- DOM cần cập nhật:
  - `#presetDetailTitle`
  - `#presetMocGioList`
  - `#presetAssignedLines`
- Logic đề xuất:
  - Giữ nguyên fetch, nhưng chuẩn hóa render theo 2 phân vùng: danh sách mốc giờ và danh sách LINE.
  - Tách update theo diff khi gán/bỏ gán LINE (không cần reload toàn modal).
- Ưu tiên: Trung bình

### 6) Gán thêm LINE
- API: POST `/moc-gio-sets/{id}/lines`
- Nơi gọi: [`admin.js:handleAssignLines()`](assets/js/admin.js:1310)
- DOM cần cập nhật:
  - `#presetAssignedLines` (append dòng)
  - `#unassignedLinesContainer` (remove các LINE vừa gán)
  - `#assignLinesModal` (close khi thành công)
- Logic đề xuất:
  - Dựa vào `line_ids` đã chọn, cập nhật `assignedLines` và cập nhật container trong modal chi tiết bằng cách append row mới.
  - Đồng thời remove các item đã chọn khỏi danh sách chưa gán trong modal gán.
- Ưu tiên: Cao

### 7) Bỏ gán LINE
- API: DELETE `/moc-gio-sets/{id}/lines`
- Nơi gọi: [`admin.js:unassignLine()`](assets/js/admin.js:1336)
- DOM cần cập nhật:
  - `#presetAssignedLines` (remove row)
  - Nếu modal gán đang mở: thêm LINE trở lại `#unassignedLinesContainer`
- Logic đề xuất:
  - Remove dòng LINE khỏi `assignedLines` và cập nhật DOM tương ứng.
  - Option: nếu count = 0 thì render state empty placeholder.
- Ưu tiên: Cao

## DOM targets và điểm cập nhật
- Bảng preset: `#presetsTable tbody` được render tại [`admin.js:renderPresetsTable()`](assets/js/admin.js:1066)
- Modal preset: `#presetModal` và các input `#presetId`, `#presetTenSet`, `#presetCaSelect`, `#presetIsDefault`, `#presetIsActive` được thao tác tại [`admin.js:showPresetModal()`](assets/js/admin.js:1092)
- Modal chi tiết: `#presetDetailModal`, `#presetDetailTitle`, `#presetMocGioList`, `#presetAssignedLines` được thao tác tại [`admin.js:renderPresetDetailModal()`](assets/js/admin.js:1202)
- Modal gán LINE: `#assignLinesModal`, `#assignLinesPresetId`, `#assignLinesTitle`, `#assignLinesSearch`, `#unassignedLinesContainer` được thao tác tại [`admin.js:showAssignLinesModal()`](assets/js/admin.js:1268)
- Modal copy: `#copyPresetModal`, `#copyPresetSourceId`, `#copyPresetNewName` được thao tác tại [`admin.js:showCopyPresetModal()`](assets/js/admin.js:1353)

## API contracts (Preset Mốc Giờ)
Endpoints định nghĩa trong [`index.php:handleMocGioSets()`](api/index.php:586):
- GET `/moc-gio-sets` -> list preset theo `ca_id` (optional)
- GET `/moc-gio-sets/{id}` -> chi tiết preset (bao gồm `moc_gio` trong `data`)
- POST `/moc-gio-sets` -> tạo preset
- PUT `/moc-gio-sets/{id}` -> cập nhật preset
- DELETE `/moc-gio-sets/{id}` -> xóa preset
- POST `/moc-gio-sets/copy` -> copy preset
- GET `/moc-gio-sets/{id}/lines` -> danh sách LINE đã gán
- POST `/moc-gio-sets/{id}/lines` -> gán LINE
- DELETE `/moc-gio-sets/{id}/lines` -> bỏ gán LINE
- GET `/moc-gio-sets/unassigned-lines?ca_id=...` -> LINE chưa gán

## Data model (tối thiểu theo frontend đang dùng)
- Preset:
  - `id`, `ten_set`, `ca_id`, `ma_ca`, `ten_ca`, `is_default`, `is_active`
  - `moc_gio[]`: `gio`, `thu_tu`, `so_phut_hieu_dung_luy_ke`
- Assigned line:
  - `id`, `ma_line`, `ten_line`

## Security
- CSRF bắt buộc với POST/PUT/DELETE theo [`index.php:requireCsrf()`](api/index.php:111).
- Frontend đã lấy token qua [`admin.js:ensureCsrfToken()`](assets/js/admin.js:353) và gửi `X-CSRF-Token` trong [`admin.js:api()`](assets/js/admin.js:365).

## Observability
- Chuẩn hóa thông báo lỗi/success qua [`admin.js:showToast()`](assets/js/admin.js:402).
- Khi chuyển sang partial update, cần đảm bảo mọi lỗi API vẫn hiển thị toast và không làm lệch state in-memory.

## Rollout / Migration
- Không thay đổi schema.
- Rollout theo từng thao tác ưu tiên (gán/bỏ gán LINE, CRUD preset), giữ fallback bằng cách gọi lại `loadPresets()` nếu update cục bộ thất bại.

## Testing
- Manual flow:
  - Tạo preset -> xem xuất hiện hàng mới trong `#presetsTable`.
  - Sửa preset -> cập nhật đúng hàng.
  - Xóa preset -> hàng biến mất, modal chi tiết đóng nếu đang mở.
  - Gán/bỏ gán LINE -> danh sách trong modal chi tiết cập nhật đúng, count/placeholder đúng.
  - Copy preset -> hàng mới xuất hiện.
- Regression:
  - Đảm bảo CSRF hoạt động khi POST/PUT/DELETE.

## Options & Trade-offs
### Option A: Giữ nguyên reload danh sách sau thao tác
- Ưu: đơn giản, ít lỗi state.
- Nhược: nhiều request, UI chớp.

### Option B: Partial update theo state in-memory (khuyến nghị)
- Ưu: nhanh, ít request, UI mượt.
- Nhược: cần đồng bộ state và DOM cẩn thận.

### Option C: Optimistic update + rollback
- Ưu: cảm giác tức thời.
- Nhược: phức tạp, cần xử lý rollback nhiều.

Khuyến nghị: Option B.

## Implementation plan (ordered checklist, rough estimates)
1) Frontend: định nghĩa helper để cập nhật `presetsData` theo CRUD (1.5h) — Code mode
2) Frontend: refactor [`admin.js:renderPresetsTable()`](assets/js/admin.js:1066) cho phép update row/append/remove (2h) — Code mode
3) Frontend: cập nhật flow tạo/sửa/xóa/copy preset dùng partial update thay vì `loadPresets()` (2h) — Code mode
4) Frontend: cập nhật flow gán/bỏ gán LINE để patch `assignedLines` và DOM trong modal chi tiết (2h) — Code mode
5) QA manual: kiểm tra 5 luồng chính (1h) — Orchestrator/QA

## Risks & open questions
- API hiện tại có trả về object preset mới sau POST/PUT không? Nếu không, cần gọi GET theo ID để cập nhật state.
- Khi gán LINE, API trả về danh sách LINE mới hay chỉ message? Cần xác định để update `assignedLines` chính xác.
- UI có dùng [`includes/components/line-preset-table.php`](includes/components/line-preset-table.php) hay chỉ dùng JS render? (ảnh hưởng định danh DOM target).
- Có cần update thêm badge/count preset tại nơi khác trong admin.php không?
