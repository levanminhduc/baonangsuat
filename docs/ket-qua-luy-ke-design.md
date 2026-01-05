# Thiet ke ket_qua_luy_ke cho bao_cao_nang_suat

## Overview
Muc tieu la luu ket qua luy ke vao bao cao de khi xem lich su, ket qua va trang thai Dat/Chua dat giong het thoi diem chot bao cao, khong bi anh huong boi thay doi routing/moc gio/CTNS sau nay. Concept duoc thong nhat la "ket_qua" (khong dung "snapshot").

Pham vi: thiet ke schema, JSON, migration, va specification cho backend + frontend. Khong thay doi route API.

## Architecture
- Luu ket_qua_luy_ke (JSON) tren bang bao_cao_nang_suat trong DB nang_suat.
- Ket qua duoc tinh tai thoi diem submit va luu vao report (ghi de khi submit lai sau unlock).
- Lich su su dung ket_qua_luy_ke neu co; neu khong co thi fallback tinh lai tu du lieu hien tai.

## Options va lua chon
### Option A: Luu chi tong + trang_thai o muc bao cao
- Uu: JSON nho, don gian.
- Nhuoc: khong du thong tin chi tiet theo cong doan, kho giai thich khi xem history.

### Option B: Luu tong + trang_thai + chi tiet theo cong doan (khong theo moc gio)
- Uu: cap du thong tin cho history, khong phu thuoc routing/moc gio tuong lai, dung voi yeu cau scope.
- Nhuoc: khong phuc dung duoc neu UI sau nay can hien trang thai theo tung moc gio.

### Option C: Luu day du theo cong doan va theo moc gio
- Uu: do chinh xac cao nhat cho history.
- Nhuoc: JSON lon, vuot scope hien tai.

Khuyen nghi: Option B.

## Data flow
1) Khi user submit, [classes/NangSuatService.php::submitBaoCao()](classes/NangSuatService.php:1) tinh ket_qua_luy_ke tu du lieu hien tai (routing + entries + CTNS + tong phut) va luu vao bao_cao_nang_suat.
2) Khi xem history detail, [classes/services/HistoryService.php::getReportDetail()](classes/services/HistoryService.php:1) doc ket_qua_luy_ke; neu co thi tra ve ket qua nay; neu khong co thi fallback tinh lai (co co canh bao fallback).
3) Frontend [assets/js/modules/history.js](assets/js/modules/history.js) uu tien dung ket_qua_luy_ke; neu fallback thi hien "tinh lai".

## API contracts
Khong doi route. Mo rong payload o detail history:
- GET /bao-cao-history/{id}
  - them truong `ket_qua_luy_ke` (JSON) neu co
  - them `ket_qua_luy_ke_is_fallback` (0/1) neu phai tinh lai

## Data model
### Bang: bao_cao_nang_suat (DB nang_suat)
- Them cot `ket_qua_luy_ke` JSON NULL.

### JSON schema (v1)
```
{
  "version": 1,
  "generated_at": "YYYY-MM-DDTHH:MM:SSZ",
  "source": {
    "bao_cao_id": 0,
    "ngay": "YYYY-MM-DD",
    "line_id": 0,
    "ca_id": 0,
    "ma_hang_id": 0
  },
  "inputs": {
    "so_lao_dong": 0,
    "ctns": 0,
    "tong_phut_hieu_dung": 0
  },
  "tong_hop": {
    "ct_gio": 0,
    "luy_ke_thuc_te": 0,
    "chi_tieu_luy_ke": 0,
    "trang_thai": "dat|chua_dat|na"
  },
  "cong_doan": [
    {
      "cong_doan_id": 0,
      "cong_doan_ten": "",
      "la_cong_doan_tinh_luy_ke": 0,
      "luy_ke_thuc_te": 0,
      "chi_tieu_luy_ke": 0,
      "trang_thai": "dat|chua_dat|na"
    }
  ]
}
```
Quy uoc:
- `luy_ke_thuc_te` tong cac entry `kieu_nhap = 'nhap'` va chi cong doan co `la_cong_doan_tinh_luy_ke = 1`.
- `chi_tieu_luy_ke` tinh theo cong thuc CT/gio da co (moc gio cuoi lay = CTNS).
- `trang_thai`: dat neu thuc_te >= chi_tieu, chua_dat neu thuc_te < chi_tieu, na neu thieu du lieu hoac chi_tieu = 0.

## Rollout/Migration
### Migration SQL
File can tao: [database/migrations/005_add_ket_qua_luy_ke.sql](database/migrations/005_add_ket_qua_luy_ke.sql)
```
ALTER TABLE bao_cao_nang_suat
ADD COLUMN ket_qua_luy_ke JSON NULL;
```

## Specification Backend
### [classes/NangSuatService.php::submitBaoCao()](classes/NangSuatService.php:1)
- Buoc moi:
  1) Tinh ket_qua_luy_ke theo JSON schema v1.
  2) Update bao_cao_nang_suat: set trang_thai='submitted', ket_qua_luy_ke=JSON, tang version.
- Ghi de neu bao cao da unlock va submit lai.
- Su dung transaction nhu quy dinh hien tai neu co cap nhat entries hang loat.

### [classes/services/HistoryService.php::getReportDetail()](classes/services/HistoryService.php:1)
- Lay ket_qua_luy_ke tu bao_cao_nang_suat.
- Neu null: fallback tinh lai tu du lieu hien tai va gan `ket_qua_luy_ke_is_fallback = 1`.
- Neu co: tra ve ket_qua_luy_ke va `ket_qua_luy_ke_is_fallback = 0`.

## Specification Frontend
### [assets/js/modules/history.js](assets/js/modules/history.js)
- Khi render chi tiet:
  - Neu co `ket_qua_luy_ke`: dung du lieu nay de hien trang thai Dat/Chua dat.
  - Neu fallback: dung ket qua tinh lai nhung gan nhan "tinh lai".
- Khong thay doi [assets/js/modules/luy-ke-calculator.js](assets/js/modules/luy-ke-calculator.js).

## Security
- Khong doi auth/permission.
- Chi luu ket qua tinh toan, khong luu thong tin nhay cam.

## Observability
- Log o backend khi fallback tinh lai (bao_cao_id, reason=missing_ket_qua_luy_ke).

## Backward compatibility
- Bao cao cu khong co ket_qua_luy_ke: HistoryService fallback tinh lai.
- Frontend can the hien ro fallback de giam nham lan.

## Testing
- Submit bao cao moi: ket_qua_luy_ke duoc luu va history hien dung.
- Unlock -> submit lai: ket_qua_luy_ke bi ghi de.
- Bao cao cu: history dung fallback, hien nhan fallback.

## Risks & open questions
- Neu UI sau nay can trang thai theo moc gio, schema v1 chua ho tro (can mo rong JSON).
- Can xac nhan co can luu them thong tin routing/moc gio snapshot hay khong.

## Implementation plan (est.)
1) Tao migration SQL (0.25d) -> Code mode
2) Backend tinh ket_qua_luy_ke khi submit (0.5d) -> Backend/Code mode
3) Backend tra ket_qua_luy_ke o history detail + fallback (0.5d) -> Backend/Code mode
4) Frontend history render theo ket_qua_luy_ke (0.5d) -> Frontend/Code mode
5) QA kiem thu submit/history/lock/unlock (0.5d) -> QA
