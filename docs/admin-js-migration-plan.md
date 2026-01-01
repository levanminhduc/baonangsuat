# Ke hoach migrate admin.js sang ES6 modules + Router

## Overview

Muc tieu: chuyen [`assets/js/admin.js`](assets/js/admin.js:1) tu vanilla JS global scope sang ES6 modules, su dung [`Router`](assets/js/modules/router.js:2) giong cach lam trong [`assets/js/app.js`](assets/js/app.js:1). Pham vi chi bao gom frontend admin, khong thay doi API va khong doi backend.

Ket qua mong muon:
- Code duoc tach module ro rang, giam global scope.
- Routing hash dua tren [`Router`](assets/js/modules/router.js:2), ho tro backward compatibility cho bookmark cu dang `#lines`.
- Trien khai theo phase, co rollback ro rang, khong big bang.

## Current state analysis

Nguon hien tai (tom tat):
- File chinh: [`assets/js/admin.js`](assets/js/admin.js:1) (~1800 LOC).
- State toan cuc: `linesData`, `usersData`, `routingData`, `mocGioData`, `presetsData`, `csrfToken`, ...
- Luong init: `DOMContentLoaded` goi `loadLines`, `loadUsers`, `loadUserLines`, `loadMaHang`, `loadCongDoan`, `loadCaList`, `loadPresets`, `bindEvents`, sau do xu ly hash tab.
- Routing hien tai: hash tab don gian `#lines`, `#user-lines`, ... thong qua `getTabFromHash()` va `switchTab()`.
- Event binding: gom `addEventListener` va nhieu handler inline `onclick` trong HTML (qua render table), can `window.*` de expose (vd `window.editLine`, `window.deleteLine`).
- API: su dung `fetch` + CSRF header tu endpoint `/csrf-token` (goi lai trong `api()` helper).
- Presets (moc gio set) co luong partial update phuc tap va binding modal rieng.

Diem can luu y:
- Dang dung hash khong co dau `/` (vd `#lines`), trong khi Router class su dung pattern `#/route`.
- Inline `onclick` dang phu thuoc vao `window.*`, can chinh sach giu alias hoac thay doi HTML sau.
- Co 2 lan `DOMContentLoaded`: init chinh va `bindPresetEvents`.

## Target architecture

### Kien truc tong the
- Mot entry module cho admin (declarative init).
- Cac module theo nhom chuc nang (lines, users, routing, moc-gio, presets, ui, api, state).
- Router class duoc dung de map hash -> tab, va giai quyet compatibility cho hash cu.

### Cau truc module de xuat
Su dung thu muc moi cho admin modules (khong dung bundler):
- [`assets/js/admin/app.js`](assets/js/admin/app.js:1) (entry)
- [`assets/js/admin/router.js`](assets/js/admin/router.js:1) (wiring voi [`Router`](assets/js/modules/router.js:2) + alias hash cu)
- [`assets/js/admin/state.js`](assets/js/admin/state.js:1) (store du lieu va getters/setters)
- [`assets/js/admin/api.js`](assets/js/admin/api.js:1) (wrapper tu [`assets/js/modules/api.js`](assets/js/modules/api.js:1) hoac copy nhe logic api/CSRF tu admin)
- [`assets/js/admin/ui.js`](assets/js/admin/ui.js:1) (showToast, showLoading, hideLoading, modals common)
- [`assets/js/admin/tabs.js`](assets/js/admin/tabs.js:1) (switchTab, tab registry, set active)
- Feature modules:
  - [`assets/js/admin/lines.js`](assets/js/admin/lines.js:1)
  - [`assets/js/admin/user-lines.js`](assets/js/admin/user-lines.js:1)
  - [`assets/js/admin/users-permissions.js`](assets/js/admin/users-permissions.js:1)
  - [`assets/js/admin/ma-hang.js`](assets/js/admin/ma-hang.js:1)
  - [`assets/js/admin/cong-doan.js`](assets/js/admin/cong-doan.js:1)
  - [`assets/js/admin/routing.js`](assets/js/admin/routing.js:1)
  - [`assets/js/admin/moc-gio.js`](assets/js/admin/moc-gio.js:1)
  - [`assets/js/admin/presets.js`](assets/js/admin/presets.js:1)

Ghi chu:
- `assets/js/admin/app.js` la noi khoi tao, goi `init()` va `router.start()`.
- Feature modules export `init()` + `bindEvents()` + `load()` + `render()`.
- `state.js` gom state hien co, de cac module dung chung, giam global.
- `ui.js` gom modal helpers va toast.

## Options va trade-offs

### Option 1: “Module wrapper” (it thay doi nhat)
- Chuyen toan bo code sang module entry, van giu logic trong mot file, chi tach `api`, `ui`, `router` nho.
- Uu: nhanh, rui ro thap.
- Nhuoc: kho bao tri ve sau, van con file lon.

### Option 2: Tách module theo chuc nang (recommended)
- Chia theo tab/feature, co `state` va `ui` chung.
- Uu: ro rang, de test, de migrate incremental theo tab.
- Nhuoc: can them nhieu file, can quan ly dependency.

### Option 3: Nua module, nua global
- Chi tach router + api + ui; giu cac feature trong file lon.
- Uu: it file moi.
- Nhuoc: van kho mo rong, khong giai quyet triet de global scope.

Khuyen nghi: Option 2 (module theo chuc nang) de ho tro migrate theo phase va tang on dinh.

## Architecture

### Router
- Dung [`Router`](assets/js/modules/router.js:2) voi pattern `#/lines`, `#/user-lines`, `#/permissions`, `#/ma-hang`, `#/cong-doan`, `#/routing`, `#/presets`, `#/moc-gio`.
- Them lop adapter de xu ly backward compatibility cho hash cu `#lines` -> `#/lines`.
- Hash khong hop le se redirect ve `#/lines`.

### Event binding
- Pha 1: giu inline `onclick` thong qua export ham vao `window` (compat).
- Pha 2: chuyen sang event delegation (data-attr) neu can, de loai bo `window.*`.

### Data init
- Pha 1: giu cach load tat ca data luc init (giam rui ro).
- Pha 2+: toi uu lazy load theo tab (tuy chon).

## Data flow

1) Entry init -> khoi tao `state`, `api`, `ui`.
2) `router.start()` -> handle hash ban dau.
3) Route handler goi `tabs.switchTab()` -> goi `feature.load()` neu can.
4) Feature module cap nhat `state` -> goi `render()`.
5) Event handler (submit/click) -> `api()` -> update `state` -> re-render.

## API contracts (khong doi)

Su dung lai cac endpoint hien co, khong thay doi:
- `GET /admin/lines`, `POST /admin/lines`, `PUT /admin/lines/:id`, `DELETE /admin/lines/:id`
- `GET /admin/user-lines`, `POST /admin/user-lines`, `DELETE /admin/user-lines`
- `GET /admin/users`
- `GET /user-permissions/:user_id`, `POST /user-permissions`, `DELETE /user-permissions/:user_id/:permission_key`
- `GET /admin/ma-hang`, `POST /admin/ma-hang`, `PUT /admin/ma-hang/:id`, `DELETE /admin/ma-hang/:id`
- `GET /admin/cong-doan`, `POST /admin/cong-doan`, `PUT /admin/cong-doan/:id`, `DELETE /admin/cong-doan/:id`
- `GET /admin/routing?ma_hang_id=`, `POST /admin/routing`, `PUT /admin/routing/:id`, `DELETE /admin/routing/:id`
- `GET /admin/moc-gio/ca-list`, `GET /admin/moc-gio?ca_id=&line_id=`, `POST /admin/moc-gio`, `PUT /admin/moc-gio/:id`, `DELETE /admin/moc-gio/:id`
- `POST /admin/moc-gio/copy-default`
- `GET /moc-gio-sets`, `GET /moc-gio-sets/:id`, `POST /moc-gio-sets`, `PUT /moc-gio-sets/:id`, `DELETE /moc-gio-sets/:id`
- `GET /moc-gio-sets/:id/lines`, `POST /moc-gio-sets/:id/lines`, `DELETE /moc-gio-sets/:id/lines`
- `GET /moc-gio-sets/unassigned-lines?ca_id=`, `POST /moc-gio-sets/copy`

## Data model

Frontend state (de xuat gom trong [`assets/js/admin/state.js`](assets/js/admin/state.js:1)):
- `lines`, `userLines`, `users`, `usersPermissions`, `maHang`, `congDoan`, `routing`, `mocGio`, `caList`, `presets`
- `selectedMaHangId`, `selectedCaId`, `selectedLineIdForMocGio`, `userLineFilterLineId`
- `currentPresetDetail`, `assignedLines`, `currentPresetMocGio`

Khong thay doi schema DB.

## Backward compatibility strategy

Yeu cau: tu dong chuyen `#lines` -> `#/lines` va giu alias 1-2 phien ban.

Chien luoc:
1) Router adapter kiem tra hash ban dau:
   - Neu hash khong co `/` va nam trong danh sach cu -> replaceState sang `#/tab`.
   - Neu hash khong hop le -> redirect `#/lines`.
2) Trong 1-2 phien ban dau, chap nhan ca `#lines` va `#/lines`.
3) Sau khi on dinh, remove alias (phase cuoi) va cap nhat docs.

## Security

- Giu CSRF token flow nhu hien tai.
- Giu `same-origin` credentials.
- Hash routing khong anh huong auth backend.

## Observability

- Khong them telemetry.
- Co the them `debug` flag trong router/admin app de log transition (optional).

## Rollout/Migration (phased plan)

### Phase 0: Doc + scaffolding (0.5d)
- Tao skeleton module files.
- Chuyen admin entry sang `type="module"` va giu `admin.js` goc lam fallback neu can.

### Phase 1: Core platform (1.0d)
- Tach `api` + `ui` + `state` + `tabs` module.
- Giup cac feature van chay, chua thay doi routing.

### Phase 2: Router migration (0.75d)
- Wiring [`Router`](assets/js/modules/router.js:2) vao admin.
- Them adapter hash cu -> hash moi.
- Dam bao tab switching thong qua router.

### Phase 3: Feature extraction theo tab (2.0d)
- Tach tung feature: lines, user-lines, users-permissions, ma-hang, cong-doan, routing, moc-gio, presets.
- Moi feature co `init/bindEvents/load/render`.
- Giup code doc lap, giam coupling.

### Phase 4: Cleanup & stabilization (0.5d)
- Giam `window.*` exposure neu co the.
- Xoa code chet, dong bo event binding.
- Cap nhat docs va note rollback.

## Testing

### Phase 1
- Load admin page: tat ca tab render du lieu nhu truoc.
- CRUD Lines/Ma hang/Cong doan/Routing/Moc gio/Presets khong regression.

### Phase 2
- `#lines` -> tu dong chuyen `#/lines`.
- `#/user-lines` mo dung tab.
- Hash invalid -> redirect `#/lines`.
- Back/forward tren browser hoat dong.

### Phase 3
- Tinh dung: filter user-lines, permissions toggle, routing filter theo ma hang.
- Moc gio: default vs line-specific, copy default hoat dong.
- Presets: create/update/delete, view detail, assign/unassign lines.

### Phase 4
- Khong con ham bi mat do `window.*` neu da remove.
- Khong co duplicate `DOMContentLoaded` side-effects.

## Risk matrix

| Risk | Impact | Likelihood | Mitigation | Rollback |
|------|--------|------------|------------|----------|
| Hash bookmark cu bi hong | Medium | Medium | Adapter `#lines` -> `#/lines`, keep alias 1-2 phien ban | Revert router wiring |
| Inline onclick phu thuoc `window.*` | Medium | High | Giu export `window` trong phase 1-3, chi remove sau khi event delegation on dinh | Revert event changes |
| Side effects do load all data luc init | Low | Medium | Giu hanh vi cu o phase dau, chi toi uu sau | Revert lazy load |
| Regression CRUD tren tab | High | Medium | Test checklist theo tab, manual QA | Revert feature module cua tab do |
| Preset flow phuc tap bi sai state | High | Medium | Tach rieng module, giu logic hien tai, test modal detail | Revert presets module |

## Risks & open questions

- Co can lazy-load data theo tab de giam load time? (defer to phase 4)
- Khi remove `window.*`, co can update HTML template de bo inline `onclick`? (defer)
- Co can them route sau (vd `#/routing/:maHangId`)? (ngoai pham vi)

## Implementation plan (checklist + estimates)

- [ ] (0.5d, Code) Tao module skeleton va entry admin module.
- [ ] (0.5d, Code) Tach `api`/`ui`/`state`/`tabs` tu admin.js.
- [ ] (0.75d, Code) Wiring [`Router`](assets/js/modules/router.js:2) + hash adapter `#lines` -> `#/lines`.
- [ ] (1.5d, Frontend) Tach feature modules theo tab, giu logic cu.
- [ ] (0.5d, Frontend) Stabilize presets & moc-gio flows, test manual.
- [ ] (0.5d, QA) Manual regression checklist.

Hand-off goi y:
- Code mode: module scaffold + router wiring.
- Frontend mode: UI event delegation va cleanup `window.*` (neu lam).
- Backend mode: khong can.
