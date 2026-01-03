# Thiet ke tinh nang Luy ke Dat/Chua dat

## Overview
- Muc tieu: thay cot "Luy ke" trong trang Nhap bao cao tu hien thi so luong sang hien thi trang thai Dat/Chua dat theo tung moc gio.
- Co che: so sanh luy ke thuc te voi chi tieu luy ke tai cung moc gio, gan mau xanh/ do va hien thi tooltip chi tiet.
- Pham vi: chi frontend, khong thay doi backend API hay migration.

## Architecture
- UI layer: GridManager trong assets/js/modules/grid.js render bang va xu ly input.
- Modules moi:
  - luy-ke-config.js: cau hinh tinh nang, nguong, nhan, format, feature flag.
  - luy-ke-calculator.js: tinh toan trang thai Dat/Chua dat/N A theo moc gio.
  - time-manager.js: tien ich sap xep moc gio, tra thu_tu, map moc gio.
- Style layer: luy-ke-status.css dinh nghia token mau, style nhan, tooltip.

## Options va lua chon

### Option A: Dung du lieu luy_ke_thuc_te tu backend neu co, fallback tinh tren client
- Uu: giong logic backend, dong bo voi tuong lai, giam sai khac trong tinh luy ke.
- Nhuoc: can logic fallback neu payload thieu hoac payload khong phan anh thay doi khi nguoi dung nhap.

### Option B: Luon tinh tren client tu inputs va entries
- Uu: phan anh realtime khi nhap, doc lap backend.
- Nhuoc: co the khac logic backend (kieu_nhap = luy_ke), nguy co sai so lieu.

### Lua chon de xuat
- Chon Option A + bo sung tinh realtime tu inputs neu che do edit.
- Logic: neu co input dang edit thi tinh tu inputs theo moc thu_tu; neu khong co input thi dung luy_ke_thuc_te tu payload.

## Data Flow
1) API tra ve baoCao voi:
   - moc_gio_list[]
   - chi_tieu_luy_ke[moc_id]
   - luy_ke_thuc_te[moc_id]
   - routing[] voi la_cong_doan_tinh_luy_ke
2) Grid render header va body.
3) time-manager sap thu tu moc gio.
4) luy-ke-calculator tinh trang thai Dat/Chua dat/N A theo moc gio.
5) grid.js gan label, class mau, tooltip cho tung cell Luy ke theo moc gio.
6) Khi input thay doi: cap nhat tinh toan va cap nhat UI.

## API Contracts
- Xac minh payload baoCao da co cac field:
  - chi_tieu_luy_ke: map moc_id -> number
  - luy_ke_thuc_te: map moc_id -> number
  - routing[].la_cong_doan_tinh_luy_ke
- Neu thieu chi_tieu_luy_ke hoac luy_ke_thuc_te: tinh tren client, tra ve N A neu du lieu khong du.
- Khong thay doi endpoint hay schema.

## Data Model
- moc_gio: id, gio, thu_tu, so_phut_hieu_dung_luy_ke
- ma_hang_cong_doan: la_cong_doan_tinh_luy_ke
- nhap_lieu_nang_suat: so_luong, kieu_nhap (tang_them, luy_ke)

## Module Specs

### luy-ke-config.js
Muc dich: gom cau hinh ve label, mau, tooltips, feature flag.

Function signatures:
- export function getLuyKeConfig()
- export function isLuyKeStatusEnabled()
- export function formatLuyKeStatusLabel(status)
- export function buildLuyKeTooltip(chiTieu, thucTe, status)

### time-manager.js
Muc dich: tien ich thao tac moc gio va thu_tu.

Function signatures:
- export function buildMocIndex(mocGioList)
- export function getMocThuTu(mocIndex, mocId)
- export function sortMocGioList(mocGioList)

### luy-ke-calculator.js
Muc dich: tinh trang thai Dat/Chua dat theo moc gio.

Function signatures:
- export function computeLuyKeStatus(params)
- export function computeLuyKeFromInputs(params)
- export function computeLuyKeFromPayload(params)

Params de xuat:
- computeLuyKeStatus({
  mocGioList,
  chiTieuLuyKeMap,
  luyKeThucTeMap,
  inputValuesByMoc,
  isEditable
}) => { statusByMocId, detailByMocId }

statusByMocId[moc_id] = 'dat' | 'chua_dat' | 'na'

detailByMocId[moc_id] = { thucTe, chiTieu, status }

## CSS Tokens
- :root tokens
  - --luy-ke-status-pass: #0a7a3a
  - --luy-ke-status-fail: #b00020
  - --luy-ke-status-na: #666666
  - --luy-ke-status-bg-pass: #e7f6ed
  - --luy-ke-status-bg-fail: #fde9ec
- Class de xuat
  - .luy-ke-status-pass
  - .luy-ke-status-fail
  - .luy-ke-status-na
  - .luy-ke-status-cell
  - .luy-ke-tooltip
- Contrast AA: dam bao mau chu va nen dat ti le toi thieu 4.5:1.

## Integration Points
- assets/js/modules/grid.js
  - Header: co the them row status hoac giu cot Luy ke va render label Dat/Chua dat.
  - Body: thay text luy ke so luong bang nhan trang thai.
  - updateRowLuyKe: goi tinh toan status thay vi tong so luong.
  - updateHieuSuat: khong doi.
- assets/css: them file luy-ke-status.css va import vao bundle.

## Security
- Khong thay doi backend.
- Khong luu them du lieu nhay cam.
- Chi tinh toan tren client tu payload duoc cap.

## Observability
- Khong can log audit o frontend.
- Co the dung console debug trong dev neu can, nhung khong dua vao production.

## Testing
### Unit
- luy-ke-calculator:
  - chi tieu = 0 => status N A
  - thuc te >= chi tieu => Dat
  - thuc te < chi tieu => Chua dat
  - thieu du lieu => N A

### Integration
- Grid render dung label va class mau.
- On input change: status update theo moc gio.
- Tooltip hien dung thuc te va chi tieu.

### UI
- Kiem tra contrast mau.
- Kiem tra tren mobile va desktop.

## Edge Cases
- khong co moc_gio_list => khong render status.
- chi_tieu_luy_ke[moc_id] null hoac 0 => N A.
- luy_ke_thuc_te[moc_id] null => tinh tu input neu co, neu khong thi N A.
- nhieu cong doan co la_cong_doan_tinh_luy_ke = 1: backend chon cong doan dau tien; frontend theo payload.
- kieu_nhap = luy_ke: payload da tinh dung, frontend can dung payload neu khong co input.

## Rollout/Migration
- Khong can migration.
- Rollout theo 2 buoc:
  1) Deploy CSS va modules moi, feature flag off.
  2) Bat feature flag sau khi QA xong.

## Implementation Plan
1) Frontend: tao 3 modules va CSS moi, export function theo spec (2-3h) -> Code mode.
2) Frontend: update grid.js integrate tinh nang (1-2h) -> Code mode.
3) Frontend: them test manual/visual checklist (1h) -> Code mode.
4) QA: verify contrast, tooltip, realtime update (1-2h) -> frontend-dev.

## Risks & open questions
- Payload luy_ke_thuc_te co luon ton tai khong? Neu khong, can de fallback tinh tu entries.
- Cac trang thai bao cao khong phai draft co cap nhat status theo realtime input? Neu readonly thi chi dung payload.
- Style tokens co can dong bo voi he thong mau hien tai? Can xac nhan neu co design system.
