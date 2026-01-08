# CHECKLIST SETUP HỆ THỐNG CHO ADMIN

> **Mục đích:** Hướng dẫn Admin setup hệ thống từ đầu theo đúng thứ tự  
> **Thời gian ước tính:** 30-60 phút (tùy số lượng dữ liệu)

---

## 📋 TỔNG QUAN

Setup hệ thống **BẮT BUỘC** theo thứ tự sau. Nếu bỏ qua bước nào, hệ thống sẽ **KHÔNG HOẠT ĐỘNG** đúng.

```
✅ Bước 1 → ✅ Bước 2 → ✅ Bước 3 → ✅ Bước 4 → ✅ Bước 5 → ✅ Bước 6 → ✅ Bước 7
   LINE       Mã hàng   Công đoạn    Routing      Preset     User-LINE    Quyền
```

---

## ✅ BƯỚC 1: TẠO LINE

### Mục tiêu
Tạo tất cả các LINE sản xuất trong nhà máy.

### Checklist

- [ ] Vào tab **"Quản lý LINE"**
- [ ] Liệt kê tất cả LINE cần tạo:

| STT | Mã LINE | Tên LINE | Đã tạo? |
|:---:|---------|----------|:-------:|
| 1 | | | ☐ |
| 2 | | | ☐ |
| 3 | | | ☐ |
| 4 | | | ☐ |
| 5 | | | ☐ |

- [ ] Nhấn **[+ Thêm LINE]** cho mỗi LINE
- [ ] Điền Mã LINE và Tên LINE
- [ ] Nhấn **[Lưu]**

### Xác nhận hoàn thành
- [ ] Tất cả LINE đã hiển thị trong bảng
- [ ] Trạng thái đều là "Đang hoạt động"

---

## ✅ BƯỚC 2: TẠO MÃ HÀNG

### Mục tiêu
Tạo tất cả mã sản phẩm đang/sẽ sản xuất.

### Checklist

- [ ] Vào tab **"Quản lý Mã hàng"**
- [ ] Liệt kê tất cả mã hàng cần tạo:

| STT | Mã hàng | Tên hàng | Đã tạo? |
|:---:|---------|----------|:-------:|
| 1 | | | ☐ |
| 2 | | | ☐ |
| 3 | | | ☐ |
| 4 | | | ☐ |
| 5 | | | ☐ |

- [ ] Nhấn **[+ Thêm Mã hàng]** cho mỗi sản phẩm
- [ ] Điền Mã hàng và Tên hàng
- [ ] Nhấn **[Lưu]**

### Xác nhận hoàn thành
- [ ] Tất cả mã hàng đã hiển thị trong bảng
- [ ] Trạng thái đều là "Đang hoạt động"

---

## ✅ BƯỚC 3: TẠO CÔNG ĐOẠN

### Mục tiêu
Tạo tất cả công đoạn trong quy trình sản xuất.

### Checklist

- [ ] Vào tab **"Quản lý Công đoạn"**
- [ ] Liệt kê tất cả công đoạn cần tạo:

| STT | Mã CĐ | Tên công đoạn | Thành phẩm? | Đã tạo? |
|:---:|-------|---------------|:-----------:|:-------:|
| 1 | | | ☐ | ☐ |
| 2 | | | ☐ | ☐ |
| 3 | | | ☐ | ☐ |
| 4 | | | ☐ | ☐ |
| 5 | | | ☐ | ☐ |

- [ ] Nhấn **[+ Thêm Công đoạn]** cho mỗi công đoạn
- [ ] Điền Mã công đoạn và Tên công đoạn
- [ ] Đánh dấu **"Là công đoạn thành phẩm"** nếu là bước cuối
- [ ] Nhấn **[Lưu]**

### Xác nhận hoàn thành
- [ ] Tất cả công đoạn đã hiển thị trong bảng
- [ ] Công đoạn thành phẩm có đánh dấu đúng

---

## ✅ BƯỚC 4: TẠO ROUTING

### Mục tiêu
Kết nối mỗi mã hàng với các công đoạn theo đúng thứ tự.

### ⚠️ QUAN TRỌNG
**Nếu không có routing, nhân viên KHÔNG THỂ tạo báo cáo cho mã hàng đó!**

### Checklist cho mỗi mã hàng

#### Mã hàng: ________________

- [ ] Vào tab **"Quản lý Routing"**
- [ ] Chọn mã hàng từ dropdown
- [ ] Thêm từng công đoạn:

| STT | Công đoạn | LINE | Tính lũy kế | Bắt buộc | Đã thêm? |
|:---:|-----------|------|:-----------:|:--------:|:--------:|
| 1 | | Tất cả | ☐ | ☐ | ☐ |
| 2 | | Tất cả | ☐ | ☐ | ☐ |
| 3 | | Tất cả | ☐ | ☐ | ☐ |
| 4 | | Tất cả | ☐ | ☐ | ☐ |
| 5 | | Tất cả | ✅ | ☐ | ☐ |

> **Ghi chú:** Đánh dấu "Tính lũy kế" cho công đoạn THÀNH PHẨM (thường là bước cuối)

- [ ] Nhấn **[+ Thêm Công đoạn]** cho mỗi bước
- [ ] Điền thông tin và nhấn **[Lưu]**

### Xác nhận hoàn thành
- [ ] Mỗi mã hàng đều có routing đầy đủ
- [ ] Thứ tự công đoạn đúng logic sản xuất
- [ ] Công đoạn cuối có đánh dấu "Tính lũy kế"

---

## ✅ BƯỚC 5: TẠO PRESET MỐC GIỜ

### Mục tiêu
Thiết lập các mốc thời gian nhập số liệu cho từng ca.

### Checklist

#### 5.1. Tạo Preset

- [ ] Vào tab **"Quản lý Preset Mốc Giờ"**
- [ ] Liệt kê preset cần tạo:

| STT | Tên Preset | Ca | Mặc định? | Đã tạo? |
|:---:|------------|------|:---------:|:-------:|
| 1 | Ca Sáng - Chuẩn | Ca sáng | ✅ | ☐ |
| 2 | Ca Chiều - Chuẩn | Ca chiều | ✅ | ☐ |
| 3 | | | ☐ | ☐ |

- [ ] Nhấn **[+ Thêm Preset]** cho mỗi preset
- [ ] Điền thông tin và nhấn **[Lưu]**

#### 5.2. Thêm mốc giờ vào Preset

##### Preset: Ca Sáng (Ví dụ 7:00 - 11:30)

- [ ] Nhấn **[Chi tiết]** của preset Ca Sáng
- [ ] Thêm các mốc giờ:

| STT | Giờ | Phút lũy kế | Đã thêm? |
|:---:|:---:|:-----------:|:--------:|
| 1 | 07:30 | 30 | ☐ |
| 2 | 08:00 | 60 | ☐ |
| 3 | 08:30 | 90 | ☐ |
| 4 | 09:00 | 120 | ☐ |
| 5 | 09:30 | 150 | ☐ |
| 6 | 10:00 | 180 | ☐ |
| 7 | 10:30 | 210 | ☐ |
| 8 | 11:00 | 240 | ☐ |
| 9 | 11:30 | 270 | ☐ |

##### Preset: Ca Chiều (Ví dụ 13:00 - 17:30)

- [ ] Nhấn **[Chi tiết]** của preset Ca Chiều
- [ ] Thêm các mốc giờ:

| STT | Giờ | Phút lũy kế | Đã thêm? |
|:---:|:---:|:-----------:|:--------:|
| 1 | 13:30 | 30 | ☐ |
| 2 | 14:00 | 60 | ☐ |
| 3 | 14:30 | 90 | ☐ |
| 4 | 15:00 | 120 | ☐ |
| 5 | 15:30 | 150 | ☐ |
| 6 | 16:00 | 180 | ☐ |
| 7 | 16:30 | 210 | ☐ |
| 8 | 17:00 | 240 | ☐ |
| 9 | 17:30 | 270 | ☐ |

### Xác nhận hoàn thành
- [ ] Mỗi ca có ít nhất 1 preset mặc định
- [ ] Các mốc giờ đủ và đúng thứ tự
- [ ] Phút lũy kế tính đúng (đã trừ thời gian nghỉ)

---

## ✅ BƯỚC 6: GÁN NHÂN VIÊN VÀO LINE

### Mục tiêu
Phân công nhân viên vào các LINE tương ứng.

### ⚠️ QUAN TRỌNG
**Nhân viên chưa được gán LINE sẽ KHÔNG THỂ đăng nhập vào hệ thống nhập liệu!**

### Checklist

- [ ] Vào tab **"Quản lý User-LINE"**
- [ ] Liệt kê nhân viên cần gán:

| STT | Mã NV | Họ tên | LINE | Đã gán? |
|:---:|-------|--------|------|:-------:|
| 1 | | | | ☐ |
| 2 | | | | ☐ |
| 3 | | | | ☐ |
| 4 | | | | ☐ |
| 5 | | | | ☐ |
| 6 | | | | ☐ |
| 7 | | | | ☐ |
| 8 | | | | ☐ |

- [ ] Nhấn **[+ Thêm Mapping]** cho mỗi nhân viên
- [ ] Chọn User và LINE
- [ ] Nhấn **[Thêm]**

### Xác nhận hoàn thành
- [ ] Mỗi LINE đều có ít nhất 1 nhân viên được gán
- [ ] Tất cả Tổ Trưởng đều đã được gán LINE

---

## ✅ BƯỚC 7: CẤP QUYỀN CHO NHÂN VIÊN

### Mục tiêu
Cấp các quyền cần thiết cho từng nhân viên.

### Checklist

- [ ] Vào tab **"Quản lý Quyền"**
- [ ] Cấp quyền theo vai trò:

#### Quyền cho Tổ Trưởng

| Mã NV | Họ tên | Xem lịch sử | Tạo BC | Tạo BC chọn LINE |
|-------|--------|:-----------:|:------:|:----------------:|
| | | ☐ | ✅ | ☐ |
| | | ☐ | ✅ | ☐ |
| | | ☐ | ✅ | ☐ |

#### Quyền cho Quản Đốc

| Mã NV | Họ tên | Xem lịch sử | Tạo BC | Tạo BC chọn LINE |
|-------|--------|:-----------:|:------:|:----------------:|
| | | ✅ | ✅ | ✅ |
| | | ✅ | ✅ | ✅ |

- [ ] Bật/tắt switch quyền cho từng người

### Xác nhận hoàn thành
- [ ] Tổ Trưởng có quyền tạo báo cáo
- [ ] Quản Đốc có đầy đủ quyền cần thiết

---

## 🧪 KIỂM TRA SAU SETUP

### Test Case 1: Đăng nhập Tổ Trưởng

- [ ] Đăng nhập bằng tài khoản Tổ Trưởng
- [ ] Hệ thống hỏi chọn LINE (nếu gán nhiều LINE)
- [ ] Vào được trang Nhập năng suất

### Test Case 2: Tạo báo cáo

- [ ] Nhấn **[+ Tạo báo cáo mới]**
- [ ] Dropdown Mã hàng có dữ liệu
- [ ] Tạo báo cáo thành công
- [ ] Bảng lưới hiển thị đúng công đoạn và mốc giờ

### Test Case 3: Nhập số liệu

- [ ] Click vào ô và nhập số
- [ ] Số liệu được lưu (không báo lỗi)
- [ ] Cột Cộng tự động tính tổng

### Test Case 4: Chốt báo cáo

- [ ] Nhấn **[Chốt báo cáo]**
- [ ] Xác nhận chốt
- [ ] Trạng thái chuyển thành "Đã chốt"
- [ ] Không thể sửa số liệu

### Test Case 5: Xem lịch sử (nếu có quyền)

- [ ] Tab Lịch sử hiển thị (nếu có quyền)
- [ ] Tìm kiếm theo ngày hoạt động
- [ ] Xem chi tiết báo cáo cũ

---

## 📝 GHI CHÚ BỔ SUNG

```
__________________________________________________________________

__________________________________________________________________

__________________________________________________________________

__________________________________________________________________

__________________________________________________________________
```

---

## ✅ XÁC NHẬN HOÀN THÀNH SETUP

| Bước | Nội dung | Hoàn thành |
|:----:|----------|:----------:|
| 1 | Tạo LINE | ☐ |
| 2 | Tạo Mã hàng | ☐ |
| 3 | Tạo Công đoạn | ☐ |
| 4 | Tạo Routing | ☐ |
| 5 | Tạo Preset Mốc giờ | ☐ |
| 6 | Gán User vào LINE | ☐ |
| 7 | Cấp quyền cho User | ☐ |
| - | Kiểm tra sau setup | ☐ |

**Người thực hiện:** ____________________

**Ngày hoàn thành:** ____/____/________

**Chữ ký:** ____________________

---

> **© 2026 - Hệ Thống Báo Năng Suất - Công Ty May Hòa Thọ Điện Bàn**
