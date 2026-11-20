# F-015 Chọn PO – Mã hàng / PO – Subart khi tạo báo cáo, so lũy kế với số lượng
Status: DONE   <!-- bạn đổi thành APPROVED để /go chạy. DOC = chỉ giữ làm tài liệu, không chạy -->
Related: F-002 extends (tạo báo cáo, màn nhập) · F-004 (report-view dùng chung với lịch sử) · F-005 (admin SPA)
Complexity: standard   <!-- migration chỉ thêm cột nullable + 1 bảng mới, không chuyển dữ liệu -->
Risk: normal
Session model: opus

## 1. Problem & goal
Số lượng đơn hàng PO – Style nằm ở master data datchi (PostgreSQL). Datchi có danh sách subart theo mã hàng
(`sub_arts`) nhưng CHƯA có số lượng theo PO – Subart. Báo cáo năng suất không biết đang may cho PO/subart nào,
nên không ai thấy đã may được bao nhiêu, còn lại bao nhiêu.
Xong khi:
- Admin nhập tay số lượng PO – Subart; tổng các subart của một PO – Style không vượt `po_items.quantity`.
- Tổ trưởng chọn PO (và subart nếu mã hàng có subart) lúc tạo báo cáo; màn nhập hiện số lượng, lũy kế
  thành phẩm (mọi ngày, mọi LINE) và phần còn lại cho cả PO – Style và PO – Subart, cập nhật ngay khi nhập.

## 2. Scope
- In:
  - Kết nối chỉ đọc PHP → Postgres `datchi` (pdo_pgsql), cấu hình tuỳ chọn trong `C:/xampp/config/db.php`.
  - Danh sách PO chứa mã hàng + `po_items.quantity`; danh sách subart của mã hàng (`sub_arts`).
  - Bảng MySQL `po_subart_so_luong` lưu số lượng PO – Subart nhập tay; tab admin "SL Subart" để xem/nhập/sửa/xoá.
  - Ràng buộc: Σ số lượng subart của (PO, mã hàng) ≤ SL PO – Style, kiểm ở server mỗi lần lưu.
  - Lưu `po_number` + `sub_art_code` vào báo cáo khi tạo; sửa được qua header khi báo cáo còn sửa được.
  - Màn nhập: dải thông tin hiện PO, SL PO, Lũy kế PO, Còn lại PO; khi có subart thêm Subart, SL Subart,
    Lũy kế Subart, Còn lại Subart. Cập nhật sau auto-save.
  - Datchi không kết nối được / chưa cấu hình → màn nhập chạy như cũ, ô PO/Subart tắt kèm lý do.
  - Khoá trùng báo cáo đổi thành (ngày, LINE, ca, mã hàng, PO, subart): một LINE được may cùng mã hàng cho
    nhiều PO/subart trong cùng ngày, ca.
  - Biểu đồ (F-012) chọn đúng báo cáo khi một (LINE, ngày, ca, mã hàng) có nhiều báo cáo.
- Out (cố ý không làm):
  - Ghi bất cứ gì sang datchi; đồng bộ SL subart về datchi.
  - Import SL subart bằng Excel (chỉ nhập tay).
  - Tạo báo cáo hàng loạt chọn PO (F-011 vẫn tạo báo cáo không PO), chi tiết lịch sử hiện PO/Subart → BACKLOG.
  - Phân quyền nhập SL subart theo user (`user_permissions`) → BACKLOG; F-015 để admin.

## 3. Acceptance criteria
- Là admin, tôi muốn nhập số lượng PO – Subart để theo dõi tiến độ từng subart.
  - Given PO A – mã hàng `X` có SL 10.000, `X` có subart S1, S2 (datchi)
    When chọn PO A + `X` trong tab "SL Subart" Then thấy S1, S2 với SL đã nhập (chưa nhập = trống), tổng đã phân bổ, còn được phân bổ.
  - When lưu S1 = 6.000, S2 = 4.000 Then lưu thành công (tổng 10.000 = SL PO).
  - When sửa S2 = 4.001 Then API trả 422 "Tổng SL subart (10.001) vượt SL PO – mã hàng (10.000)", không lưu.
  - When lưu subart không thuộc `X` theo datchi Then 422.
  - Given datchi không kết nối được When lưu Then 503, không lưu (không kiểm được ràng buộc).
  - Given số lượng âm hoặc không phải số nguyên Then 422. Xoá một dòng SL subart được phép.
  - Given hai admin cùng lưu subart của cùng (PO, mã hàng) Then tổng sau cả hai lần lưu vẫn ≤ SL PO.
- Là người vận hành, tôi muốn sau này chuyển SL subart sang lấy từ datchi mà không sửa màn nhập/báo cáo.
  - Given `subart_so_luong_source` thiếu hoặc `'manual'` Then API trả `nguon:'manual'`, `editable:true`.
  - Given một source giả `laEditable()=false` (test) When `PUT/DELETE /api/admin/po-subart` Then 409, không ghi;
    tab admin ẩn nút lưu/xoá, hiện "SL subart lấy từ datchi".
  - Báo cáo chỉ lưu `po_number` + `sub_art_code`, không lưu SL → đổi nguồn không cần migrate báo cáo.
- Là tổ trưởng, tôi muốn chọn PO (và subart) khi tạo báo cáo để biết đang may cho đơn nào.
  - Given mã hàng `X` có trong PO A (10.000), PO B (5.000), PO C đã huỷ
    When chọn mã hàng `X` Then ô PO liệt kê A, B kèm SL, không có C, có "Không chọn PO".
  - Given chọn PO A và `X` có subart Then ô Subart liệt kê S1, S2 kèm SL đã nhập ("chưa nhập" nếu trống) và có "Không chọn subart".
    `X` không có subart → ô Subart ẩn.
  - Given chọn PO A + S1 và tạo báo cáo Then `po_number='A'`, `sub_art_code='S1'`.
  - Given gửi `po_number` không chứa `X`, hoặc `sub_art_code` không thuộc `X`, hoặc có `sub_art_code` mà không có `po_number` Then API lỗi 422, không tạo.
  - Given không chọn PO Then tạo như cũ (cả hai cột NULL).
  - Given LINE 1 đã có báo cáo ngày D, ca 1, mã `X`, PO A
    When tạo thêm cùng ngày/ca/mã với PO B, hoặc PO A + subart S1 Then tạo được.
    When tạo lại đúng PO A (không subart) Then lỗi "Báo cáo đã tồn tại cho ngày/ca/mã hàng/PO/subart này".
    Hai báo cáo cùng ngày/ca/mã đều không PO → vẫn bị chặn như cũ.
  - Given đổi PO/subart qua header thành bộ khoá đã có báo cáo khác Then từ chối, không lưu.
- Là người xem biểu đồ, tôi muốn chọn đúng báo cáo khi một mã hàng có nhiều PO trong cùng ca.
  - Given LINE 1, ngày D, ca 1, mã `X` có 2 báo cáo (PO A, PO B)
    When mở danh sách mã hàng của biểu đồ Then thấy 2 dòng `X - tên (Ca 1) · PO A` và `… · PO B`;
    chọn dòng nào thì biểu đồ dùng đúng báo cáo đó.
  - Given chỉ một báo cáo (dữ liệu cũ, không PO) Then nhãn và biểu đồ như hiện tại.
- Là tổ trưởng, tôi muốn thấy đã may được bao nhiêu để biết còn lại bao nhiêu.
  - Given PO A – `X` = 10.000; các báo cáo khác cùng PO A + `X` có thành phẩm 3.000 (trong đó 1.000 cho S1);
    báo cáo đang mở (S1) có thành phẩm 200
    When mở màn nhập Then PO: SL 10.000, Lũy kế 3.200, Còn lại 6.800; Subart S1: SL 6.000, Lũy kế 1.200, Còn lại 4.800.
  - When nhập thêm 50 ở công đoạn tính lũy kế và auto-save xong Then cả hai lũy kế +50, còn lại −50 (không tải lại trang).
  - Given lũy kế vượt SL Then Còn lại hiện 0, cảnh báo vượt (màu warning) kèm số vượt.
  - Given S1 chưa nhập SL Then SL Subart "—", Còn lại Subart "—", lũy kế subart vẫn hiện.
  - "Thành phẩm" mỗi báo cáo = tổng `so_luong` của công đoạn `la_cong_doan_tinh_luy_ke = 1` theo
    `routing_snapshot` của chính báo cáo đó (fallback routing hiện tại khi snapshot rỗng).
- Là người dùng, tôi muốn màn nhập không hỏng khi datchi lỗi.
  - Given chưa cấu hình datchi hoặc Postgres không kết nối được (timeout ≤ 3 giây)
    Then API danh sách trả `available: false`, modal hiện ô PO tắt "Không kết nối được dữ liệu PO";
    báo cáo đã có PO vẫn hiện số PO/subart, SL PO "—"; SL Subart vẫn hiện (nằm ở MySQL).
- Là tổ trưởng, tôi muốn gắn PO/subart cho báo cáo tạo trước khi có tính năng này.
  - Given báo cáo chưa duyệt/khoá/hoàn tất When đổi PO/subart qua header Then lưu và cập nhật dải thông tin;
    approved/locked/completed thì từ chối như các trường header khác.

## 4. Contracts
- Config: `+ datchi_pg` (tuỳ chọn) trong `C:/xampp/config/db.php` — `['host','port','dbname','user','password']`; KHÔNG thêm vào `requiredKeys`.
- Support: `+ Database::getDatchi(): ?PDO` — PDO pgsql, `ERRMODE_EXCEPTION`, `connect_timeout=3`; trả `null` khi thiếu config / thiếu ext / lỗi kết nối (không throw).
- Service: `+ App\Services\DatchiPoService`
  - `getPoListForStyle(string $styleCode): array{available, items: list<{po_number, customer_name, quantity}>}`
  - `getPoQuantity(string $poNumber, string $styleCode): array{available, quantity: ?int}`
  - `getSubArtsForStyle(string $styleCode): array{available, items: list<string>}`
- Service: `+ App\Services\SubartSoLuong\SubartSoLuongSource` (interface) — `nguon(): string`, `laEditable(): bool`, `getSoLuong(string $poNumber, string $styleCode): array{available, items: array<sub_art_code, int>}`. Mọi chỗ ĐỌC SL subart (danh mục, GET báo cáo, tab admin) đi qua interface này, không truy bảng trực tiếp.
- Service: `+ App\Services\SubartSoLuong\ManualSubartSource` (MySQL `po_subart_so_luong`, `nguon()='manual'`, `laEditable()=true`). Chỗ cắm sau này: `DatchiSubartSource` (`nguon()='datchi'`, `laEditable()=false`) — KHÔNG làm trong F-015.
- Service: `+ App\Services\SubartSoLuong\SubartSoLuongFactory::make(): SubartSoLuongSource` — đọc khoá tuỳ chọn `subart_so_luong_source` trong `db.php` (`'manual'` mặc định; giá trị lạ → `manual`).
- Service: `+ App\Services\PoSubartService` (ghi tay) — `list($poNumber, $maHangId)`, `save($poNumber, $maHangId, $subArtCode, $soLuong, $ma_nv)`, `delete($poNumber, $maHangId, $subArtCode)`; `save`/`delete` từ chối 409 khi nguồn hiện tại `laEditable()=false`; `save` chạy trong transaction, `SELECT … FOR UPDATE` các dòng cùng (po, mã hàng) trước khi kiểm tổng.
- Service: `+ App\Services\PoTienDo` (thuần, không DB) — `thanhPhamCongDoanId(?string $routingSnapshotJson): ?int`, `tinh(?int $soLuong, int $luyKe): array{con_lai: ?int, vuot: int}`, `kiemTongSubart(array $soLuongKhac, int $soLuongMoi, int $soLuongPo): array{ok: bool, tong: int}`.
- API (nhóm `danh-muc`, middleware `auth`):
  - `+ GET /api/danh-muc/po?ma_hang_id={id}` → `{success, data: {available, items: [{po_number, customer_name, quantity}]}}`.
  - `+ GET /api/danh-muc/po-subart?ma_hang_id={id}&po_number={po}` → `{success, data: {available, nguon: 'manual'|'datchi', items: [{sub_art_code, so_luong: ?int}]}}` (danh sách subart từ datchi, SL từ nguồn hiện tại).
  - `ma_hang_id` không tồn tại → 404 JSON.
- API (nhóm `admin`, `role:admin`):
  - `+ GET /api/admin/po-subart?ma_hang_id&po_number` → `{success, data: {nguon, editable, so_luong_po: ?int, tong_da_phan_bo, items: [{sub_art_code, so_luong: ?int, updated_by, updated_at}]}}`.
  - `+ PUT /api/admin/po-subart` (`csrf`) body `{po_number, ma_hang_id, sub_art_code, so_luong}` → 200 / 409 (nguồn không cho sửa) / 422 (vượt, sai subart, sai số) / 503 (datchi lỗi).
  - `+ DELETE /api/admin/po-subart` (`csrf`) body `{po_number, ma_hang_id, sub_art_code}`.
- API: `~ POST /api/bao-cao` (owner F-002) — body thêm `po_number?`, `sub_art_code?`; kiểm theo mục 3; datchi không sẵn sàng mà có `po_number` → 503 `Không kết nối được dữ liệu PO`.
- API: `~ PUT /api/bao-cao/{id}/header` (owner F-002) — body thêm `po_number?: string|null`, `sub_art_code?: string|null`, cùng kiểm; vẫn tăng `version`.
- API: `~ GET /api/bao-cao/{id}` (owner F-002) — `data` thêm
  `po: null | {po_number, available, so_luong_po: ?int, luy_ke_bao_cao_khac: int,
  sub_art: null | {sub_art_code, so_luong: ?int, luy_ke_bao_cao_khac: int}}` (FE tự cộng thành phẩm báo cáo đang mở).
- Data (DB `nang_suat`, migration `013_add_po_subart.sql`, cập nhật `database/install.sql`):
  - `+ bao_cao_nang_suat.po_number VARCHAR(50) NULL`, `+ bao_cao_nang_suat.sub_art_code VARCHAR(100) NULL`, `+ KEY idx_po_ma_hang (po_number, ma_hang_id, sub_art_code)`.
  - `+ po_key VARCHAR(50) AS (IFNULL(po_number,'')) STORED`, `+ sub_art_key VARCHAR(100) AS (IFNULL(sub_art_code,'')) STORED` (vì UNIQUE coi NULL là khác nhau).
  - `~ UNIQUE uk_bao_cao (ngay_bao_cao, line_id, ca_id, ma_hang_id)` → `(ngay_bao_cao, line_id, ca_id, ma_hang_id, po_key, sub_art_key)` (owner F-002). Khoá mới rộng hơn khoá cũ → dữ liệu hiện có không vi phạm.
  - `+ po_subart_so_luong (po_number VARCHAR(50), ma_hang_id INT FK ma_hang ON DELETE CASCADE, sub_art_code VARCHAR(100), so_luong INT UNSIGNED NOT NULL, updated_by VARCHAR(50), updated_at TIMESTAMP)` PK `(po_number, ma_hang_id, sub_art_code)`, utf8mb4_unicode_ci.
- API: `~ GET /api/bieu-do/ma-hang-list` (owner F-012) — mỗi phần tử thêm `bao_cao_id, po_number, sub_art_code`; một phần tử cho mỗi báo cáo (trước là DISTINCT mã hàng + ca).
- API: `~ GET /api/bieu-do/{so-sanh,so-sanh-chi-tiet,so-sanh-matrix}` (owner F-012) — thêm query tuỳ chọn `bao_cao_id`; có thì lấy đúng báo cáo đó (vẫn kiểm khớp `line_id`), không có thì giữ hành vi cũ (`LIMIT 1`).
- FE: `~ public/assets/js/bieu-do.js` — value option chứa `bao_cao_id`, nhãn thêm `· PO · subart` khi có.
- Data ngoài (chỉ đọc, owner datchi): `= purchase_orders(po_number, customer_name, status, deleted_at)`, `= po_items(po_id, style_id, quantity, deleted_at)`, `= styles(id, style_code)`, `= sub_arts(style_id, sub_art_code)`.
- FE:
  - `~ views/nhap-nang-suat.php` modal tạo báo cáo — `+ <select id="modalPo">`, `+ <select id="modalSubart">` sau `modalMaHang`.
  - `~ public/assets/js/app.js` (populateModalSelects, tạo báo cáo, sau auto-save, đổi PO/subart qua header) · `~ modules/report-view.js reportInfoHtml` (field PO/Subart chỉ khi `bc.po`) · `= modules/api.js`.
  - `+ public/assets/js/modules/admin/po-subart.js` (deps `['ma-hang']`), `~ public/assets/js/admin.js` (đăng ký module + tab), `~ views/admin.php` (tab + bảng).

## 5. Overlap check
| Việc liên quan | Chồng ở đâu | Quyết định |
|---|---|---|
| F-002 tạo báo cáo / header / GET báo cáo | `NangSuatService::createBaoCao`, `updateHeader`, `getBaoCao`; `BaoCaoController` | **extend**: thêm field tuỳ chọn, field cũ giữ nguyên; JS sửa cùng commit |
| F-002 danh mục | nhóm `/api/danh-muc` | **extend**: thêm 2 route trong nhóm sẵn có |
| F-004 report-view dùng chung với lịch sử | `reportInfoHtml` | **reuse**: field PO/Subart chỉ render khi `bc.po` có → lịch sử không đổi |
| F-005/F-007 admin SPA, tab mã hàng | `admin.js` module map, nhóm `/api/admin` (`role:admin`) | **extend**: thêm module lazy-load `po-subart`, deps `ma-hang` |
| F-011 bulk-create | INSERT bao_cao riêng + check trùng theo khoá cũ (NangSuatService ~L804) | Không đụng logic; báo cáo không PO có `po_key=''` nên check cũ vẫn đúng nghĩa. Chọn PO khi bulk → BACKLOG |
| F-012 biểu đồ | `ChartService` 3 query `WHERE line, ma_hang, ngay, ca LIMIT 1`; `getMaHangListForChart` DISTINCT | **extend**: thêm `bao_cao_id` tuỳ chọn, giữ hành vi cũ khi thiếu; JS sửa cùng commit |
| F-013 user_permissions | Phân quyền nhập SL subart sau này | Chưa dùng; route nằm trong nhóm `admin` nên đổi sang quyền user chỉ là đổi middleware (BACKLOG) |
| `support` Database | 3 kết nối mysqli | **extend**: thêm kết nối thứ 4 PDO pgsql, tuỳ chọn |
| migration 009 `rollback_lenh_san_xuat` | Từng có `lenh_san_xuat` + `bao_cao.lenh_sx_id` rồi bị gỡ | Khái niệm gần giống; người dùng không nhớ lý do, không có ghi chép → chấp nhận, F-015 chỉ thêm cột tuỳ chọn nên gỡ lại được |
| DECISIONS D-001 | URL path-based | Tuân theo |
| KNOWLEDGE: migration áp tay, số trùng | database/migrations | Dùng số 013, áp tay, ghi vào deploy-checklist |

## 6. Decisions
- Chọn: PHP đọc thẳng Postgres datchi bằng PDO pgsql, chỉ đọc. Bác bỏ: HTTP API datchi (phải thêm service token bên datchi); import/sync sang MySQL (SL PO lệch thời gian). (durable)
- Chọn: SL PO – Subart nhập tay, lưu ở MySQL baonangsuat (`po_subart_so_luong`), danh sách subart vẫn lấy từ datchi. Bác bỏ: tự do gõ mã subart (lệch master data). (durable)
- Chọn: đọc SL subart qua interface `SubartSoLuongSource` + factory theo khoá config `subart_so_luong_source`, vì datchi sẽ có import SL theo subart. Khi đó chỉ thêm `DatchiSubartSource` + đổi config; API, màn nhập, báo cáo giữ nguyên; tab admin tự thành chỉ đọc (`editable:false`); bảng `po_subart_so_luong` giữ làm lịch sử/fallback, ràng buộc tổng ≤ SL PO do datchi chịu. Khoá subart bằng `sub_art_code` (không dùng id) để hai nguồn khớp nhau. Bác bỏ: đọc thẳng bảng MySQL ở từng chỗ (đổi nguồn phải sửa nhiều nơi); làm `DatchiSubartSource` ngay (datchi chưa có schema để đọc). (durable)
- Chọn: ràng buộc Σ subart ≤ SL PO – Style kiểm ở server lúc lưu, trong transaction có khoá dòng; datchi lỗi thì từ chối lưu. Nếu sau này SL PO giảm dưới tổng đã phân bổ: không tự sửa, tab admin hiện cảnh báo "vượt" để admin chỉnh. (durable)
- Chọn: lưu `po_number` + `sub_art_code` (chuỗi), không lưu id datchi, vì `po_number` UNIQUE và `sub_art_code` UNIQUE theo mã hàng, ổn định giữa hai hệ thống; không snapshot SL PO. (durable)
- Chọn: khớp mã hàng bằng `UPPER(TRIM(ma_hang.ma_hang)) = UPPER(TRIM(styles.style_code))` — người dùng xác nhận hai bên trùng mã. (durable)
- Chọn: khoá trùng báo cáo = (ngày, LINE, ca, mã hàng, PO, subart) qua cột sinh `po_key`/`sub_art_key`, vì một LINE may nhiều PO/subart của cùng mã trong một ca khi số lượng ít. Bác bỏ: giữ khoá cũ (chặn nghiệp vụ thật); UNIQUE trực tiếp trên cột NULL (MySQL coi NULL khác nhau → báo cáo không PO bị trùng). (durable)
- Chọn: biểu đồ thêm `bao_cao_id` tuỳ chọn thay vì gộp nhiều báo cáo, vì mỗi PO có routing snapshot/CTNS riêng. Bác bỏ: cộng dồn các báo cáo cùng ca (lẫn chỉ tiêu).
- Chọn: Lũy kế = tổng thành phẩm mọi báo cáo cùng `po_number` + `ma_hang_id` (+ `sub_art_code` cho subart), mọi LINE/ngày/trạng thái, thành phẩm theo `routing_snapshot` từng báo cáo. Bác bỏ: `ket_qua_luy_ke` (chỉ có sau submit); routing hiện tại (sai khi routing đổi).
- Chọn: API trả `luy_ke_bao_cao_khac`, FE cộng thành phẩm báo cáo đang mở → cập nhật tức thì sau auto-save.
- Chọn: F-015 chỉ admin nhập SL subart (nhóm `/api/admin` có `role:admin`); sau này phân quyền theo user qua `user_permissions` (F-013) → BACKLOG.
- Chọn: datchi lỗi thì suy giảm mềm ở màn nhập. PO huỷ: `UPPER(status) <> 'CANCELLED'`; bỏ dòng `deleted_at IS NOT NULL`.

## 7. Plan
### S1 [sonnet] (test) Acceptance tests phần kiểm được không cần Postgres
Files: tests/php/po_tien_do_test.php (mới), tests/php/router_test.php
Do: test `PoTienDo::thanhPhamCongDoanId` (snapshot có/không có công đoạn tính lũy kế, JSON rỗng/hỏng → null); `PoTienDo::tinh` (10000/3200 → còn 6800, vượt 0; 10000/10500 → còn 0, vượt 500; SL null → con_lai null); `PoTienDo::kiemTongSubart` ([6000], 4000, 10000 → ok; [6000], 4001, 10000 → không ok, tong 10001); route `GET /api/danh-muc/po`, `GET /api/danh-muc/po-subart`, `GET|PUT|DELETE /api/admin/po-subart` khớp controller; `SubartSoLuongFactory::make()` trả `ManualSubartSource` khi config thiếu/`'manual'`/giá trị lạ; `PoSubartService` với source giả `laEditable()=false` từ chối ghi. Test cần Postgres/MySQL thật thì bỏ qua khi `!extension_loaded('pdo_pgsql')` hoặc `Database::getDatchi() === null`, in lý do.
Done when: `php tests/php/run.php po_tien_do` chạy và FAIL vì chưa có class/route.

### S2 [opus] Kết nối datchi + migration + service đọc/ghi
Files: app/Support/Database.php, app/Services/DatchiPoService.php, app/Services/PoTienDo.php, app/Services/PoSubartService.php, app/Services/SubartSoLuong/{SubartSoLuongSource,ManualSubartSource,SubartSoLuongFactory}.php (mới), database/migrations/013_add_po_subart.sql, database/install.sql
Do: migration 013 thêm 2 cột + 2 cột sinh + bảng mới, DROP/ADD `uk_bao_cao` theo mục 4 (chạy được trên MySQL 8 và MariaDB 10.4+; kiểm trên bản sao DB local trước). `getDatchi()` theo mục 4 (cache cả null trong request); SQL Postgres prepared, `LIMIT 500`; interface + factory theo mục 4 (PoSubartService nhận source qua constructor để test thay được); `PoSubartService::save` kiểm `laEditable()` → transaction + `FOR UPDATE` + `kiemTongSubart` + kiểm subart thuộc mã hàng qua datchi. Không thêm thư mục ngoài `app/` (PSR-4 `App\Services\SubartSoLuong`). (Orchestrator làm: DB ngoài, credential, timeout, khoá dòng.)
Done when: S1 test PoTienDo PASS; `php -l` sạch; với ext + config local thì các hàm datchi trả dữ liệu, tắt Postgres thì `available:false` trong ≤ 3 giây; lưu vượt tổng trả lỗi.

### S3 [sonnet] API: danh mục PO/subart, admin SL subart, báo cáo nhận và trả PO/subart
Files: app/Controllers/DanhMucController.php, app/Controllers/PoSubartController.php (mới), config/routes.php, app/Services/NangSuatService.php, app/Controllers/BaoCaoController.php
Do: route theo mục 4. `createBaoCao`/`updateHeader`: chuẩn hoá (`trim`, rỗng → null), kiểm theo mục 3, thêm 2 cột vào INSERT/UPDATE; check trùng dùng khoá mới (`IFNULL(po_number,'') = ?` …), `updateHeader` loại trừ chính nó; bắt lỗi duplicate key (1062) → message trùng thay vì 500. `getBaoCao`: khối `po` (+ `sub_art`) — lấy `id, routing_snapshot, sub_art_code` các báo cáo khác cùng `po_number`+`ma_hang_id` một query, SUM `so_luong` theo `bao_cao_id, cong_doan_id` một query, ghép bằng `PoTienDo::thanhPhamCongDoanId`; lũy kế subart lọc thêm `sub_art_code`. Controller gọi service, không truy DB.
Done when: S1 test route PASS; `vendor/bin/php-cs-fixer fix --dry-run` 0 file; smoke chưa đăng nhập khớp baseline (thêm URL GET mới vào `tests/smoke/urls.txt` + chụp lại baseline, ghi lý do).

### S4 [sonnet] FE admin: tab "SL Subart"
Files: public/assets/js/modules/admin/po-subart.js (mới), public/assets/js/admin.js, views/admin.php
Example: public/assets/js/modules/admin/ma-hang.js (pattern load/render/modal/showToast)
Do: chọn mã hàng → chọn PO (gọi `/danh-muc/po`) → bảng subart: mã, SL (input), người sửa, ngày; dòng tổng: SL PO, đã phân bổ, còn được phân bổ (đỏ khi vượt). Lưu từng dòng `PUT`, xoá `DELETE` có confirm; hiện message 409/422/503 từ server. `editable:false` → input readonly, ẩn nút lưu/xoá, hiện "SL subart lấy từ datchi". Escape chuỗi datchi.
Done when: thao tác tay đủ các tiêu chí admin ở mục 3 trên local; `tools\build-css.bat` chạy, CSS không mất class.

### S5 [sonnet] FE màn nhập: ô PO/Subart + field dải thông tin + cập nhật sau auto-save
Files: views/nhap-nang-suat.php, public/assets/js/app.js, public/assets/js/modules/report-view.js
Do: `modalPo` (option đầu "-- Không chọn PO --", option `PO — khách — SL 10.000`); đổi mã hàng → `/danh-muc/po`; đổi PO → `/danh-muc/po-subart`, không có subart thì ẩn `modalSubart`; `available:false` → disabled + chú thích. Gửi `po_number`, `sub_art_code` khi tạo. `reportInfoHtml`: field PO (SL, Lũy kế, Còn lại) và Subart (mã, SL, Lũy kế, Còn lại) khi có; vượt → token `warning`. Sau auto-save tính lại = `luy_ke_bao_cao_khac` + thành phẩm hiện tại (cùng nguồn với `luy-ke-calculator`). Đổi PO/subart trên màn nhập khi báo cáo còn sửa được → `PUT …/header`.
Done when: thao tác tay theo mục 3 đúng trên local; `tools\build-css.bat` chạy, CSS không mất class; HTML trang trước/sau chỉ khác đồng hồ + phần mới.

### S5b [sonnet] Biểu đồ chọn theo báo cáo
Files: app/Services/ChartService.php, app/Controllers/BieuDoController.php, public/assets/js/bieu-do.js
Do: `getMaHangListForChart` trả một dòng mỗi báo cáo kèm `bao_cao_id, po_number, sub_art_code`; 3 hàm so sánh nhận `$bao_cao_id = null` — có thì `WHERE bc.id = ? AND bc.line_id = ?`, không thì giữ query cũ. `bieu-do.js`: value option `${bao_cao_id}`, vẫn set `ma_hang_id`/`ca_id` từ dòng đã chọn, gửi `bao_cao_id`; nhãn thêm `· PO · subart` khi có.
Done when: tiêu chí biểu đồ ở mục 3 đúng trên local; smoke `--cookie` các URL `bieu-do` cũ (không `bao_cao_id`) khớp baseline.

### S6 [sonnet] (doc) Deploy checklist cho datchi
Files: docs/deploy-checklist.md
Do: bật `extension=pdo_pgsql` (PHP 8.2 prod), role Postgres chỉ `SELECT` trên `purchase_orders, po_items, styles, sub_arts`, mở mạng từ server baonangsuat tới Postgres datchi, khoá `datchi_pg` trong `db.php`, áp migration 013.
Done when: checklist đủ 5 mục, mỗi mục có lệnh/cấu hình mẫu (không chứa mật khẩu thật).

### S7 [opus] Update brain
REGISTRY (F-015 DONE), SYSTEM (module datchi-po/po-subart, API, bảng + cột mới, kết nối thứ 4), DECISIONS (các mục durable), KNOWLEDGE (pdo_pgsql local chưa bật; status datchi trộn hoa/thường), BACKLOG (bulk-create + PO, lịch sử hiện PO, quyền user nhập SL subart qua `user_permissions`, `DatchiSubartSource` khi datchi có import SL subart — kèm quyết định chuyển dữ liệu nhập tay cũ sang datchi hay bỏ). KNOWLEDGE: đổi nguồn SL subart = thêm class + đổi `subart_so_luong_source`.

## 8. Verification
- Commands: `php tests/php/run.php` · `vendor/bin/php-cs-fixer fix --dry-run` · gate lint `php -l` toàn repo · `php tools/smoke.php` (+ `--cookie` chỉ GET) · `tools\build-css.bat`.
- Local chưa có `pdo_pgsql`: phải bật trong Laragon trước S2 để kiểm đường có dữ liệu; nếu không chỉ kiểm được đường `available:false`.
- Thao tác ghi (PUT/DELETE admin, tạo báo cáo) không chạy bằng phiên thật — người dùng thử tay.
- Rủi ro verifier phải soi riêng: (1) credential datchi không lọt vào repo/log/response lỗi; (2) datchi chậm/chết không treo `GET /api/bao-cao/{id}` quá 3 giây; (3) shape JSON cũ của 3 API extend không đổi (chỉ thêm field); (4) ràng buộc tổng subart không lách được bằng 2 request song song; (5) `po_number`/`sub_art_code` từ client luôn qua prepared statement + kiểm tồn tại; (6) chuỗi từ datchi được escape khi render.

## Open questions
Không còn. Đã chốt với người dùng (2026-11-14):
1. Mã hàng hai bên trùng nhau → khớp trực tiếp theo mã.
2. Một LINE may được nhiều PO/subart của cùng mã trong một ngày/ca → đổi khoá trùng + sửa biểu đồ (S5b).
3. Lý do gỡ `lenh_san_xuat` không ai nhớ → chấp nhận.
4. Prod kết nối được Postgres datchi → giữ phương án PDO pgsql (vẫn cần bật ext, xem S6).
5. Phân quyền nhập SL subart theo user làm sau → F-015 để admin.

Amended (2026-11-14, /go S5): lũy kế thành phẩm của một báo cáo = số ở mốc muộn nhất đã nhập của công đoạn `la_cong_doan_tinh_luy_ke=1` (ô nhập là lũy kế tại mốc), không SUM các mốc. Shape contract không đổi.
