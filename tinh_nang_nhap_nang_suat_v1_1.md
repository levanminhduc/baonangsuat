# Tính năng: Nhập năng suất theo giờ theo công đoạn (Excel-like) — LINE auto theo login + Routing theo Mã hàng

**Phiên bản**: 1.1  
**Mục tiêu**: Tóm tắt đầy đủ phạm vi **DB + Backend + Frontend + Luồng xử lý + Quyền + Validation + Tính CTNS/CT-Giờ/Lũy kế** theo mẫu Excel.

---

## 1) Tổng quan & mục tiêu

### 1.1. Bài toán
- Ghi nhận **số lượng theo từng mốc giờ** cho **từng công đoạn** (giống bảng Excel).
- **LINE tự nhận diện** dựa theo user đăng nhập (tổ trưởng), không cho nhập sai line.
- **Mỗi Mã hàng (ma_hang) có routing công đoạn khác nhau** → khi chọn mã hàng sẽ tự load đúng danh sách công đoạn.
- Có các chỉ tiêu/kpi trên header:
  - **LĐ (Lao Động)**: số người của LINE/ca/mã hàng (nhập).
  - **CTNS (Chỉ tiêu năng suất)**: chỉ tiêu tổng (nhập).
  - **CT/Giờ (Chỉ tiêu theo giờ)**: có thể tính tự động hoặc cho phép nhập/override tùy quyền.
- **Lũy kế theo mã hàng từng mốc giờ**: tổng sản lượng thành phẩm lũy kế tại các checkpoint giờ (09:00, 11:00, 14:30, 17:00…).

### 1.2. Màn hình chính
- **Desktop (Excel-like grid)**: hiển thị dạng bảng giống file Excel bạn gửi (header + grid).
- (Tuỳ chọn) **Tablet/Mobile**: “Nhập theo mốc giờ” tối ưu thao tác.

---

## 2) Thuật ngữ & quy ước

- **Report/Báo cáo** = 1 phiếu cho 1 tổ trưởng (line) theo: `ngay_bao_cao` + `line` + `ca` + `ma_hang` *(khuyến nghị)*.
- **Mốc giờ** = các checkpoint trong ca (ví dụ: 09:00, 11:00, 14:30, 17:00), cấu hình theo ca.
- **Routing** = danh sách công đoạn áp dụng cho `ma_hang` (có thứ tự).
- **Entry** = dữ liệu nhập của 1 công đoạn tại 1 mốc giờ.
- **Cảnh báo quan trọng về “Lũy kế”**  
  - **Lũy kế theo mã hàng từng giờ** phải phản ánh **thành phẩm** (đầu ra line), tránh double-count nếu cộng tất cả công đoạn.
  - Vì vậy hệ thống cần xác định **công đoạn dùng để tính “thành phẩm/lũy kế tổng”** (thường là “KCS thành phẩm”).

---

## 3) Quyền & vai trò (Roles)

- `to_truong`: tạo báo cáo, nhập số liệu, sửa trong phạm vi line được phân, submit/chốt.
- `quan_doc`: xem nhiều line, có thể approve, (tuỳ chính sách) chỉnh sửa/sửa sai.
- `van_phong`/`admin`: quản trị danh mục (line, ca, mốc giờ, mã hàng, công đoạn, routing), mở khóa, export.

> Vai trò **lấy từ** `mysqli.user.role`.

---

## 4) Xác định LINE tự động khi login (2 database)

### 4.1. Hai database
1) DB **`mysqli`** (đăng nhập)
- Bảng `user`: `id`, `name`, `password`, `role`
  - `name` = **mã nhân viên** (`ma_nv`)

2) DB **`quan_ly_nhan_su`** (nhân sự)
- Bảng `nhan_vien`: `ma_nv`, `ho_ten`, `phong_ban_ma`
- Bảng `phong_ban`: `ma`, `ten`

### 4.2. Quy trình login (tóm tắt)
1) Query `mysqli.user` theo `name = username`
2) Xác thực password
3) Lấy `user.name` => `ma_nv`
4) Query `quan_ly_nhan_su.nhan_vien` join `phong_ban` => `phong_ban_ma`, `phong_ban_ten`
5) Lưu session:
   - `ma_nv`, `ho_ten`
   - `phong_ban_ma`, `phong_ban_ten`
   - `vai_tro`

### 4.3. Mapping để ra LINE (bắt buộc)
Trong DB năng suất, tạo mapping (ưu tiên theo user, fallback theo phòng ban):

1) `user_line(ma_nv, line_id)` — **ưu tiên**
2) `phong_ban_line(phong_ban_ma, line_id)` — **fallback**

Kết quả:
- 0 line: báo “chưa được phân LINE”
- 1 line: set `session.line_id`
- >1 line: cho chọn 1 line (dropdown), set `session.line_id`

---

## 5) Database (DB ứng dụng năng suất)

> Thiết kế theo hướng: **mốc giờ là dòng dữ liệu (row)**, không hardcode mốc giờ thành cột DB.

### 5.1. Danh mục LINE & mapping
**`line`**
- `id` (PK)
- `ma_line` (unique) — ví dụ `LINE_5`
- `ten_line`
- `is_active` (0/1)

**`phong_ban_line`**
- `phong_ban_ma`
- `line_id` (FK → line.id)
- PK: (`phong_ban_ma`, `line_id`)

**`user_line`**
- `ma_nv`
- `line_id` (FK → line.id)
- PK: (`ma_nv`, `line_id`)

### 5.2. Danh mục mã hàng & công đoạn
**`ma_hang`**
- `id` (PK)
- `ma_hang` (unique) — ví dụ `6175`
- `ten_hang` (optional)
- `is_active`

**`cong_doan`**
- `id` (PK)
- `ma_cong_doan` (optional/unique)
- `ten_cong_doan`
- `is_active`
- `la_cong_doan_thanh_pham` (0/1) *(optional nhưng hữu ích, ví dụ “KCS thành phẩm” = 1)*

### 5.3. Routing công đoạn theo mã hàng
**`ma_hang_cong_doan`**
- `id` (PK)
- `ma_hang_id` (FK)
- `line_id` (nullable, FK → line.id) — NULL = dùng chung
- `cong_doan_id` (FK)
- `thu_tu` (int) — thứ tự hiển thị
- `bat_buoc` (0/1) (optional)
- `la_cong_doan_tinh_luy_ke` (0/1) *(khuyến nghị)*  
  - đánh dấu công đoạn dùng để tính “Lũy kế theo mã hàng từng giờ” (thường 1 công đoạn duy nhất)
- `hieu_luc_tu`, `hieu_luc_den` (optional)
- `ghi_chu` (optional)

> Nếu routing thay đổi theo thời gian: dùng `hieu_luc_tu/den` để không sai dữ liệu quá khứ.

### 5.4. Ca làm & mốc giờ
**`ca_lam`**
- `id` (PK)
- `ma_ca` (unique)
- `ten_ca`

**`moc_gio`**
- `id` (PK)
- `ca_id` (FK)
- `gio` (TIME) — ví dụ `09:00`, `11:00`, `14:30`, `17:00`
- `thu_tu` (int)
- `is_active`

*(Tuỳ chọn nâng cao)* **`moc_gio_hieu_dung`** (nếu muốn cấu hình “giờ hiệu dụng” vì có nghỉ)
- `moc_gio_id`
- `so_phut_hieu_dung_luy_ke` (int) — số phút làm việc hiệu dụng tính đến mốc đó (cumulative)
> Nếu không có bảng này, backend tự tính theo cấu hình giờ làm/giờ nghỉ của ca.

### 5.5. Báo cáo năng suất (header) — bổ sung LĐ, CTNS, CT/Giờ
**`bao_cao_nang_suat`**
- `id` (PK)
- `ngay_bao_cao` (DATE)
- `line_id` (FK)
- `ca_id` (FK)
- `ma_hang_id` (FK)

**Thông tin header nhập / tính toán:**
- `so_lao_dong` (INT) — **LĐ** (nhập)
- `ctns` (INT) — **CTNS** (nhập)
- `ct_gio` (DECIMAL(10,2)) — **CT/Giờ** (khuyến nghị: tính tự động; có thể cho override theo role)
- `tong_phut_hieu_dung` (INT) — tổng phút làm việc hiệu dụng của ca (để tính CT/Giờ & lũy kế chỉ tiêu)

**Khác:**
- `ghi_chu` (TEXT) (optional)
- `trang_thai` (`draft`, `submitted`, `approved`, `locked`)
- `version` (INT) — optimistic locking
- `tao_boi` (ma_nv), `tao_luc`, `cap_nhat_luc`

**Unique đề xuất:**
- unique (`ngay_bao_cao`, `line_id`, `ca_id`, `ma_hang_id`)  
  *(Nếu 1 ca có thể chạy nhiều mã hàng song song, cần thêm `so_phieu/lan` để phân biệt.)*

### 5.6. Dữ liệu nhập theo công đoạn/mốc giờ
**`nhap_lieu_nang_suat`**
- `id` (PK)
- `bao_cao_id` (FK)
- `cong_doan_id` (FK)
- `moc_gio_id` (FK)
- `so_luong` (INT) — số lượng nhập tại mốc giờ
- `kieu_nhap` (`tang_them` | `luy_ke`) — khuyến nghị fix 1 kiểu cho toàn hệ thống
- `ghi_chu` (optional)
- `nhap_boi` (ma_nv), `nhap_luc`

**Ràng buộc chống trùng (bắt buộc):**
- unique (`bao_cao_id`, `cong_doan_id`, `moc_gio_id`)

### 5.7. Audit (khuyến nghị)
**`nhap_lieu_nang_suat_audit`**
- `id`, `entry_id`, `old_value`, `new_value`, `updated_by`, `updated_at`, `reason`

---

## 6) Logic chỉ tiêu: CTNS, CT/Giờ, lũy kế chỉ tiêu theo mốc giờ

### 6.1. Nhập CTNS & tính CT/Giờ
Khuyến nghị:
- User nhập **CTNS** và **LĐ**
- Hệ thống tính **CT/Giờ**:
  - `ct_gio = ctns / (tong_phut_hieu_dung / 60)`

> Nếu doanh nghiệp muốn nhập CT/Giờ theo chuẩn cố định, có thể cho phép nhập `ct_gio` và tính ngược `ctns` (nhưng nên chọn 1 cách để giảm sai).

### 6.2. Lũy kế chỉ tiêu (target) theo từng mốc giờ
Để hiển thị các số giống dòng “88 / 206 / 324 / 500” dưới header giờ:

- Gọi `phut_hieu_dung_luy_ke[moc]` = tổng phút làm việc hiệu dụng tính đến mốc đó.
- `tong_phut_hieu_dung` = tổng phút hiệu dụng cả ca.

Công thức:
- `chi_tieu_luy_ke[moc] = round(ctns * phut_hieu_dung_luy_ke[moc] / tong_phut_hieu_dung)`

Ràng buộc làm tròn:
- Đảm bảo mốc cuối cùng luôn bằng đúng `ctns`.
- Nếu làm tròn lệch, ưu tiên điều chỉnh ở mốc cuối hoặc phân bổ sai số theo mốc lớn nhất.

---

## 7) Lũy kế THỰC TẾ theo mã hàng từng mốc giờ (yêu cầu của bạn)

### 7.1. Định nghĩa
**Lũy kế thực tế theo mã hàng tại mốc giờ** = sản lượng thành phẩm lũy kế của line đến thời điểm đó.

### 7.2. Tránh double-count
Không được cộng tất cả công đoạn (vì 1 sản phẩm đi qua nhiều công đoạn sẽ bị tính nhiều lần).

➡️ Cách đúng:
- Xác định **1 công đoạn “thành phẩm”** để làm chuẩn tính lũy kế (thường là “KCS thành phẩm”)
- Có 2 cách cấu hình:
  1) `cong_doan.la_cong_doan_thanh_pham = 1` (global)
  2) `ma_hang_cong_doan.la_cong_doan_tinh_luy_ke = 1` (theo routing mã hàng/line) — **khuyến nghị**

### 7.3. Công thức lũy kế thực tế
Giả sử `cong_doan_thanh_pham_id` là công đoạn dùng để tính lũy kế:

- Nếu `kieu_nhap = tang_them`:
  - `luy_ke_thuc_te[moc] = sum(so_luong của cong_doan_thanh_pham_id với slot <= moc)`
- Nếu `kieu_nhap = luy_ke`:
  - `luy_ke_thuc_te[moc] = so_luong tại moc`

> Lũy kế thực tế này sẽ hiển thị ở **header dưới các mốc giờ** (giống Excel).

---

## 8) Backend: API & luồng xử lý

### 8.1. API ngữ cảnh (line auto)
- `GET /api/nang-suat/context`
  - Trả: `line_id`, `line_ten`, các `ca`, các `moc_gio` theo ca, quyền user

### 8.2. Tạo report (header) — có LĐ, CTNS
- `POST /api/nang-suat/bao-cao`
  - Input: `ngay_bao_cao`, `ca_id`, `ma_hang_id`, `so_lao_dong`, `ctns`, `ghi_chu` (optional)
  - Server:
    - lấy `line_id` từ session
    - load routing theo `ma_hang_id + line_id`
    - tính `tong_phut_hieu_dung`, `ct_gio`, `chi_tieu_luy_ke[]`
    - tạo `bao_cao_nang_suat`
    - **pre-generate** `nhap_lieu_nang_suat` cho (routing × moc_gio) để UI load nhanh

### 8.3. Lấy report để render Excel grid
- `GET /api/nang-suat/bao-cao/{id}`
  - Trả:
    - header (line, LĐ, ma_hang, CTNS, CT/Giờ, ngày, ghi chú)
    - danh sách routing công đoạn (theo `thu_tu`)
    - mốc giờ
    - entries (so_luong)
    - computed:
      - `chi_tieu_luy_ke[moc]`
      - `luy_ke_thuc_te[moc]` (từ công đoạn thành phẩm)

### 8.4. Cập nhật số liệu (batch)
- `PUT /api/nang-suat/bao-cao/{id}/entries`
  - Input: list patch `{cong_doan_id, moc_gio_id, so_luong}` + `version`
  - Validate:
    - thuộc line session
    - report chưa lock
    - `so_luong` integer >= 0
    - nếu `kieu_nhap=luy_ke`: không giảm theo mốc trước (server kiểm)
  - Lưu + audit
  - Recompute `luy_ke_thuc_te[moc]` (hoặc compute on read)

### 8.5. Chốt/khóa
- `POST /api/nang-suat/bao-cao/{id}/submit` (to_truong)
- `POST /api/nang-suat/bao-cao/{id}/approve` (quan_doc/admin)
- `POST /api/nang-suat/bao-cao/{id}/unlock` (admin)

### 8.6. Concurrency
- Optimistic locking bằng `version`:
  - update kèm `version`
  - mismatch => báo user reload

---

## 9) Frontend (FE): hiển thị Excel-like trên Desktop (giống hình)

### 9.1. Layout giống Excel
**Header (trên cùng):**
- `LINE: {ma_line}`
- `LĐ: {so_lao_dong}` (input)
- `MH: {ma_hang}` (select)
- `CTNS: {ctns}` (input)
- `CT/Giờ: {ct_gio}` (read-only hoặc editable theo quyền)
- `Date: {ngay_bao_cao}`
- `Ghi chú`

**Grid (bảng):**
- Cột: `Ghi chú` | `STT` | `TÊN CÔNG ĐOẠN` | `{mốc giờ 1}` | `{mốc giờ 2}` | ... | `LŨY KẾ`
- Hàng: danh sách công đoạn theo routing

### 9.2. Header ô mốc giờ dạng “chéo” (diagonal)
Để giống Excel:
- Trong mỗi ô header mốc giờ:
  - Góc trên phải: hiển thị thời gian (`9h`, `11h`, `14h30`, `17h00`)
  - Góc dưới trái: hiển thị **chi_tieu_luy_ke** tại mốc đó (ví dụ 88/206/324/500)
  - (Tuỳ chọn) thêm 1 dòng nhỏ/tooltip cho **luy_ke_thuc_te**

> Nếu muốn hiển thị đúng như hình (chỉ 1 con số dưới mỗi mốc giờ), ưu tiên hiển thị `chi_tieu_luy_ke`. Lũy kế thực tế có thể hiển thị ở tooltip/ màu trạng thái/ hoặc một dòng phía dưới header.

### 9.3. Cột “LŨY KẾ” bên phải (theo từng công đoạn)
- Đây là **lũy kế theo công đoạn** (khác với lũy kế theo mã hàng):
  - Nếu `tang_them`: `sum(so_luong theo các mốc giờ)`
  - Nếu `luy_ke`: lấy `so_luong` tại mốc cuối
- Cột này giúp nhìn nhanh công đoạn nào đang đạt/thiếu.

### 9.4. Tối ưu nhập liệu kiểu Excel
- Enter/Tab/Arrow để di chuyển ô
- Copy/paste range
- Autosave theo ô hoặc theo batch (debounce)
- Highlight ô chưa nhập tại mốc giờ hiện tại
- Lock ô nếu báo cáo đã submit/approve

**Khuyến nghị dùng thư viện grid**
- Handsontable / AG Grid / React Data Grid (tuỳ tech stack) để có trải nghiệm Excel thật.

---

## 10) Validation & cảnh báo
- `so_lao_dong` > 0
- `ctns` > 0
- `so_luong` integer >= 0
- Cảnh báo ngưỡng bất thường (config)
- Nếu nhập `luy_ke`: cấm giảm theo mốc trước
- Audit log khi sửa

---

## 11) Tiêu chí nghiệm thu (Acceptance Criteria)
- [ ] Login tổ trưởng → tự xác định `line_id` theo `user_line`/`phong_ban_line`.
- [ ] Khi tạo report phải nhập được **LĐ** và **CTNS**.
- [ ] Hệ thống tính được **CT/Giờ** (hoặc cho nhập theo quyền) và hiển thị header.
- [ ] Hiển thị được **chi_tieu_luy_ke** dưới mỗi mốc giờ giống Excel.
- [ ] Lũy kế thực tế theo mã hàng từng mốc giờ tính đúng theo công đoạn thành phẩm (không double-count).
- [ ] Trang desktop hiển thị dạng Excel-like, nhập nhanh, auto-save.
- [ ] Unique constraint chống trùng entry (report+cong_doan+moc_gio).
- [ ] Chốt/khóa theo role, có audit (nếu bật).

---

## 12) SQL tham khảo (rút gọn)

### 12.1. Lấy routing + xác định công đoạn tính lũy kế
```sql
SELECT 
  mhd.cong_doan_id,
  cd.ten_cong_doan,
  mhd.thu_tu,
  mhd.la_cong_doan_tinh_luy_ke
FROM ma_hang_cong_doan mhd
JOIN cong_doan cd ON cd.id = mhd.cong_doan_id
WHERE mhd.ma_hang_id = ?
  AND (mhd.line_id = ? OR mhd.line_id IS NULL)
ORDER BY 
  CASE WHEN mhd.line_id = ? THEN 0 ELSE 1 END,
  mhd.thu_tu;
```

### 12.2. Tính lũy kế thực tế theo mã hàng tại các mốc (kieu_nhap=tang_them)
```sql
SELECT 
  e.moc_gio_id,
  SUM(e.so_luong) AS luy_ke
FROM nhap_lieu_nang_suat e
WHERE e.bao_cao_id = ?
  AND e.cong_doan_id = :cong_doan_thanh_pham_id
  AND e.moc_gio_id IN (:list_moc_gio_id_toi_moc)
GROUP BY e.moc_gio_id;
```

---

## 13) Ghi chú triển khai
- Nên chuẩn hóa `ma_nv` (trim/upper) để tránh mismatch `user.name` ↔ `nhan_vien.ma_nv`.
- Password lưu hash (bcrypt/argon2), dùng prepared statements.
- Nếu ca có nghỉ, cần định nghĩa “phút hiệu dụng” để tính CT/Giờ và chi_tieu_luy_ke đúng.

