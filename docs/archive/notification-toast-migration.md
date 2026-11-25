# Ke hoach chuyen thong bao sang Toast component

## Overview
Muc tieu la chuyen tat ca thong bao hien tai (showToast + alert) sang Toast component de dong nhat UI/UX va giam phan tan logic. Pham vi gom cac thong bao trong app.js, admin.js, login.js va cac trang co alert native.

## Architecture
- Frontend UI thong bao duoc centralize qua Toast component (window.toast.show).
- Cac ham showToast hien co se tro thanh wrapper goi window.toast.show, giu API cu de giam chi phi refactor.
- Toast component duoc include 1 lan tai layout chung (shared header/navbar) de phu tren tat ca trang.

## Data flow
1. Nguoi dung thuc hien hanh dong (tao bao cao, cap nhat, dang nhap, ...).
2. JS module goi showToast(message, type).
3. showToast wrapper chuyen sang window.toast.show(message, type, duration).
4. Toast component render UI thong bao vao toast-container.

## API contracts
- Khong thay doi API backend.
- Giua cac module JS: giu signature showToast(message, type) de tranh sua nhieu noi.

## Data model
- Khong thay doi data model.

## Security
- Khong thay doi co che bao mat.
- Dam bao message tu server duoc sanitize truoc khi render neu dung innerHTML.

## Observability
- Khong can them logging. Nếu can theo doi, dung console log tai wrapper trong giai doan rollout.

## Rollout/Migration
- Buoc 1: include toast component vao layout dung chung.
- Buoc 2: update wrapper showToast trong utils.js de delegate sang window.toast.
- Buoc 3: thay the alert() trong login.js va cac noi con lai sang showToast.
- Buoc 4: test tren cac man hinh chinh (login, nhap nang suat, admin).

## Testing
- Kiem tra thu cong cac luong:
  - Login fail/success
  - Tao bao cao, cap nhat, chot bao cao
  - Admin CRUD (LINE, mapping, routing, moc gio)
- Kiem tra toast truong hop trung message (badge tang dan).
- Kiem tra toast auto dismiss va nut close.

## Risks & open questions
- Layout chung nao dang duoc dung de include toast component? (navbar.php hay file khac)
- Co trang nao khong dung layout chung (vd: login) can include rieng?
- Bat ky thong bao nao render tu PHP can chuyen sang JS hay giu nguyen?

## Implementation plan
1. Xac dinh layout chung de include toast component (0.5h) - Frontend/Code
2. Include Toast component vao layout chung (0.5h) - Frontend/Code
3. Sua showToast trong utils.js de goi window.toast.show (0.5h) - Code
4. Thay alert() trong login.js va cac noi con lai (0.5h) - Code
5. Sanity test UI thong bao tren cac man hinh chinh (1h) - Frontend/Code
