# Thiet ke: Gan nhieu LINE vao preset moc gio

**Ngay tao:** 2025-12-30  
**Phien ban:** 2.0  
**Trang thai:** Draft

---

## 1. Tong quan (Overview)

### 1.1 Boi canh
He thong nang suat hien dang cho phep gan moc gio rieng theo tung LINE bang cot `line_id` trong bang `moc_gio`. Cach nay gay trung lap du lieu khi nhieu LINE can cung bo moc gio (preset).

### 1.2 Muc tieu
- Ho tro gan **nhieu LINE vao mot preset moc gio** theo **ca_lam**.
- Moi LINE chi duoc gan **01 preset/ca**.
- Giua cac LINE, preset co the chia se (khong duplicate).
- **Fallback** ve preset mac dinh cua ca neu LINE chua gan.
- Bao toan du lieu cu va khong gay giu doan.

---

## 2. Options va Trade-offs

### Option A: Tiep tuc dung `line_id` trong `moc_gio` (hien tai)
- **Uu diem:** Don gian, da co san, khong can doi logic nhieu.
- **Nhuoc diem:** Trung lap du lieu lon, kho quan tri preset chia se, khong dap ung yeu cau moi.

### Option B: Them bang preset va bang mapping (DE XUAT)
- **Y tuong:** Tao **preset** theo ca, gan nhieu LINE vao preset bang bang mapping.
- **Uu diem:** Khong trung lap, de quan ly, dung nhu yeu cau moi.
- **Nhuoc diem:** Them bang, can migration va doi API.

### Option C: Bang override rieng + preset
- **Uu diem:** Giu data cu, co the dua dần sang preset.
- **Nhuoc diem:** Logic phuc tap, hai nguon du lieu, kho kiem soat.

**Khuyen nghi:** Chon **Option B** vi dap ung dung yeu cau gan nhieu LINE vao preset, giam duplicate, de scale.

---

## 3. Kien truc (Architecture)

- **Admin UI** quan ly preset va gan LINE.
- **API** cung cap preset CRUD, gan LINE, va truy van moc gio theo LINE.
- **Service layer** (MocGioService) thuc hien: tim preset theo line_id + ca_id, fallback default, lay danh sach moc gio theo preset.
- **DB nang_suat** luu preset, mapping, va moc gio.

---

## 4. Data model

### 4.1 Bang moi

**moc_gio_set** (preset theo ca)
- id (PK)
- ca_id (FK -> ca_lam)
- ten_set (VARCHAR)
- is_default (TINYINT, 0/1)
- is_active (TINYINT, 0/1)
- created_at, updated_at (optional)

**line_moc_gio_set** (mapping LINE -> preset theo ca)
- id (PK)
- line_id (FK -> line)
- ca_id (FK -> ca_lam)
- set_id (FK -> moc_gio_set)
- is_active (TINYINT, 0/1)
- UNIQUE (line_id, ca_id)

### 4.2 Bang hien co can dieu chinh

**moc_gio**
- Them cot `set_id` (FK -> moc_gio_set)
- Giu lai `line_id` de tuong thich du lieu cu
- Them index: (set_id, thu_tu)

### 4.3 Quy tac du lieu
- Moi ca co **01 preset default** (moc_gio_set.is_default = 1).
- Moi LINE chi duoc gan **01 preset/ca** qua line_moc_gio_set.
- `moc_gio` se lien ket **preset** qua set_id (uu tien).`line_id` se duoc coi la du lieu cu, khong su dung trong truy van moi.

---

## 5. Data flow

### 5.1 Lay danh sach moc gio theo LINE

Input: ca_id, line_id
1) Tim mapping trong line_moc_gio_set theo (line_id, ca_id)
2) Neu co set_id -> lay danh sach moc_gio theo set_id
3) Neu khong co -> fallback lay preset default cua ca
4) Tra ve danh sach moc gio + is_fallback

### 5.2 Quan ly preset
- Admin tao preset (moc_gio_set)
- Admin tao danh sach moc gio (moc_gio) theo preset
- Admin gan nhieu LINE vao preset (line_moc_gio_set)

---

## 6. API Contracts (de xuat)

### 6.1 Public
- GET /api/danh-muc/moc-gio?ca_id=1&line_id=2
  - Tra ve danh sach moc gio theo preset giong data flow.
  - Neu khong co mapping -> dung preset default.

### 6.2 Admin - Preset
- GET /api/admin/moc-gio-set?ca_id=1
- POST /api/admin/moc-gio-set
- PUT /api/admin/moc-gio-set/{id}
- DELETE /api/admin/moc-gio-set/{id}

### 6.3 Admin - Preset items (moc gio)
- GET /api/admin/moc-gio-set/{id}/items
- POST /api/admin/moc-gio-set/{id}/items
- PUT /api/admin/moc-gio-items/{id}
- DELETE /api/admin/moc-gio-items/{id}

### 6.4 Admin - Gan LINE vao preset
- GET /api/admin/moc-gio-set/{id}/lines
- POST /api/admin/moc-gio-set/{id}/lines
- DELETE /api/admin/moc-gio-set/{id}/lines

---

## 7. UI/UX de xuat

### 7.1 Man hinh quan ly preset
- Chon ca -> hien danh sach preset
- Tao preset moi, dat ten, danh dau default
- Quan ly danh sach moc gio trong preset (grid)

### 7.2 Gan LINE vao preset
- Chon preset -> danh sach LINE (multi-select)
- Luu gan nhieu LINE mot luc
- Hien thi nhung LINE da gan

### 7.3 Hanh vi fallback
- LINE chua gan -> UI co nhan “Dang dung preset mac dinh cua ca”.

---

## 8. Migration Strategy

### 8.1 Buoc 1: Them schema moi
- Tao bang moc_gio_set
- Tao bang line_moc_gio_set
- Them set_id vao moc_gio

### 8.2 Buoc 2: Tao preset default theo ca
- Moi ca tao 1 preset default
- Gan cac dong moc_gio co line_id IS NULL vao preset default

### 8.3 Buoc 3: Convert du lieu line_id cu
- Tao 1 preset rieng cho moi (ca_id, line_id) neu ton tai
- Gan set_id cho cac dong moc_gio tuong ung
- Tao mapping line_moc_gio_set cho line_id do

### 8.4 Buoc 4: Van hanh song song
- Cho phep doc du lieu theo set_id
- Neu chua co set_id (data chua migrate) -> fallback ve logic line_id hien tai

### 8.5 Buoc 5: Don dep
- Sau khi migrate day du -> bo su dung line_id trong logic moi

---

## 9. Security

- CRUD va mapping chi danh cho admin
- CSRF bat buoc cho POST/PUT/DELETE
- Validate line_id, ca_id, set_id ton tai

---

## 10. Observability

- Log thao tac tao/sua/xoa preset
- Log thao tac gan LINE vao preset
- Can nhac audit table neu can truy vet

---

## 11. Testing

- Unit: resolve preset theo line_id + ca_id
- Unit: fallback default khi khong co mapping
- Integration: CRUD preset + items + mapping
- Regression: Bao cao cu van tinh dung

---

## 12. Risks & Open Questions

### 12.1 Risks
- Migration phuc tap neu nhieu du lieu line_id cu
- Can xu ly conflict neu 1 LINE dang gan nhieu preset trong cung ca

### 12.2 Open Questions
- Can tu dong deduplicate preset giong nhau khong?
- Co can giao dien so sanh preset truoc khi gan hang loat?

---

## 13. Implementation Plan (Estimate)

1) Thiet ke migration SQL + chinh sach fallback (2h) - Backend
2) Cap nhat service lay moc gio theo preset (2h) - Backend
3) Cap nhat API admin preset + mapping (4h) - Backend
4) Cap nhat UI admin (4h) - Frontend
5) Test va kiem thu (2h) - QA

Tong: ~14h
