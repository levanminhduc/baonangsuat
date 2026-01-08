# HƯỚNG DẪN SỬ DỤNG HỆ THỐNG BÁO NĂNG SUẤT

> **Phiên bản:** 1.0  
> **Cập nhật:** Tháng 1/2026  
> **Đối tượng:** Người dùng cuối (Admin, Tổ Trưởng, Quản Đốc)

---

## MỤC LỤC

1. [Giới Thiệu Hệ Thống](#1-giới-thiệu-hệ-thống)
2. [Hướng Dẫn Đăng Nhập](#2-hướng-dẫn-đăng-nhập)
3. [Phân Quyền Và Chức Năng](#3-phân-quyền-và-chức-năng)
4. [Hướng Dẫn Cho ADMIN](#4-hướng-dẫn-cho-admin)
5. [Hướng Dẫn Cho TỔ TRƯỞNG](#5-hướng-dẫn-cho-tổ-trưởng)
6. [Hướng Dẫn Cho QUẢN ĐỐC](#6-hướng-dẫn-cho-quản-đốc)
7. [Xử Lý Sự Cố Thường Gặp](#7-xử-lý-sự-cố-thường-gặp)
8. [Câu Hỏi Thường Gặp (FAQ)](#8-câu-hỏi-thường-gặp-faq)

---

## 1. GIỚI THIỆU HỆ THỐNG

### 1.1. Hệ Thống Là Gì?

Hệ thống **Báo Năng Suất** là ứng dụng web giúp theo dõi và quản lý năng suất sản xuất của các LINE trong nhà máy. 

Hệ thống cho phép:
- ✅ Nhập số liệu năng suất theo từng mốc giờ trong ngày
- ✅ Theo dõi tiến độ sản xuất theo thời gian thực
- ✅ Xem lịch sử báo cáo các ngày trước
- ✅ Quản lý dữ liệu sản xuất (mã hàng, công đoạn, routing)

### 1.2. Các Thuật Ngữ Quan Trọng

| Thuật ngữ | Giải thích |
|-----------|------------|
| **LINE** | Đơn vị sản xuất (dây chuyền may), mỗi LINE có nhiều công nhân |
| **Mã hàng** | Mã sản phẩm đang sản xuất (VD: áo sơ mi mã ABC) |
| **Công đoạn** | Bước trong quy trình sản xuất (VD: Cắt → May → Đóng gói) |
| **Routing** | Quy trình sản xuất = Mã hàng + Các công đoạn theo thứ tự |
| **Mốc giờ** | Thời điểm nhập số liệu (VD: 7:30, 8:30, 9:30...) |
| **Ca làm** | Ca sáng hoặc ca chiều |
| **CTNS** | Chỉ tiêu năng suất - mục tiêu sản lượng cần đạt |
| **Preset** | Bộ cài đặt sẵn các mốc giờ cho một ca |

### 1.3. Cách Truy Cập Hệ Thống

**Cách 1:** Qua trình duyệt web
- Mở trình duyệt (Chrome, Firefox, Edge...)
- Nhập địa chỉ: `http://[địa-chỉ-server]/baonangsuat/`

**Cách 2:** Qua ứng dụng LINE
- Mở ứng dụng LINE trên điện thoại
- Nhấn vào đường link được chia sẻ

---

## 2. HƯỚNG DẪN ĐĂNG NHẬP

### 2.1. Màn Hình Đăng Nhập

Khi truy cập hệ thống, bạn sẽ thấy màn hình đăng nhập với:
- Logo công ty
- Tiêu đề "HỆ THỐNG BÁO NĂNG SUẤT"
- Ô nhập **Mã nhân viên**
- Ô nhập **Mật khẩu**
- Nút **Đăng nhập**

### 2.2. Các Bước Đăng Nhập

```
Bước 1: Nhập Mã nhân viên (VD: NV001)
        ⬇️
Bước 2: Nhập Mật khẩu
        ⬇️
Bước 3: Nhấn nút "Đăng nhập"
        ⬇️
Bước 4: Nếu có nhiều LINE → Chọn LINE làm việc
        ⬇️
Bước 5: Vào trang chính của hệ thống
```

### 2.3. Chọn LINE Làm Việc

Nếu bạn được gán nhiều LINE, sau khi đăng nhập sẽ hiện popup yêu cầu chọn LINE:

1. Danh sách các LINE bạn được phân công sẽ hiện ra
2. Nhấn vào tên LINE bạn muốn làm việc
3. Hệ thống sẽ chuyển đến trang nhập liệu

### 2.4. Ghi Nhớ Đăng Nhập

- Đánh dấu ô **"Ghi nhớ đăng nhập"** để hệ thống nhớ mã nhân viên
- Lần sau bạn chỉ cần nhập mật khẩu
- **Lưu ý:** Hệ thống KHÔNG lưu mật khẩu, chỉ lưu mã nhân viên

### 2.5. Đăng Xuất

1. Nhấn vào menu góc phải trên cùng (icon 3 gạch)
2. Chọn **"Đăng xuất"**
3. Xác nhận đăng xuất

---

## 3. PHÂN QUYỀN VÀ CHỨC NĂNG

### 3.1. Bảng Tổng Hợp Phân Quyền

| Chức năng | Admin | Quản Đốc | Tổ Trưởng |
|-----------|:-----:|:--------:|:---------:|
| **QUẢN TRỊ HỆ THỐNG** ||||
| Quản lý LINE | ✅ | ❌ | ❌ |
| Quản lý Mã hàng | ✅ | ❌ | ❌ |
| Quản lý Công đoạn | ✅ | ❌ | ❌ |
| Quản lý Routing | ✅ | ❌ | ❌ |
| Quản lý Mốc giờ/Preset | ✅ | ❌ | ❌ |
| Gán User vào LINE | ✅ | ❌ | ❌ |
| Cấp quyền cho User | ✅ | ❌ | ❌ |
| Tạo báo cáo hàng loạt | ✅ | ❌ | ❌ |
| **BÁO CÁO NĂNG SUẤT** ||||
| Tạo báo cáo mới | ✅ | ⭕* | ⭕* |
| Nhập số liệu năng suất | ✅ | ⭕* | ⭕* |
| Chốt báo cáo | ✅ | ⭕* | ⭕* |
| Mở khóa báo cáo đã chốt | ✅ | ❌ | ❌ |
| **XEM VÀ DUYỆT** ||||
| Xem báo cáo hôm nay | ✅ | ✅ | ✅ |
| Xem lịch sử báo cáo | ✅ | ⭕** | ⭕** |
| Duyệt báo cáo | ✅ | ⭕*** | ❌ |

**Chú thích:**
- ⭕* = Cần có quyền `tao_bao_cao` hoặc `tao_bao_cao_cho_line`
- ⭕** = Cần có quyền `can_view_history`
- ⭕*** = Cần được cấp quyền duyệt

### 3.2. Chi Tiết Từng Quyền

#### 🔹 Quyền `tao_bao_cao` (Quyền tạo báo cáo)
- **Ý nghĩa:** Cho phép tạo báo cáo cho LINE mà user được gán
- **Ai cần:** Tổ Trưởng, Quản Đốc phụ trách nhập liệu
- **Phạm vi:** Chỉ tạo được cho LINE mình được phân công

#### 🔹 Quyền `tao_bao_cao_cho_line` (Quyền tạo báo cáo cho LINE khác)
- **Ý nghĩa:** Cho phép tạo báo cáo cho BẤT KỲ LINE nào
- **Ai cần:** Quản Đốc quản lý nhiều LINE, Admin backup
- **Phạm vi:** Toàn bộ hệ thống

#### 🔹 Quyền `can_view_history` (Quyền xem lịch sử)
- **Ý nghĩa:** Cho phép xem báo cáo các ngày trước đó
- **Ai cần:** Quản Đốc, Tổ Trưởng cần tra cứu lịch sử
- **Phạm vi:** Báo cáo của LINE mình được phân công

### 3.3. Trang Mặc Định Sau Đăng Nhập

| Vai trò | Trang mặc định |
|---------|----------------|
| Admin | Trang Quản trị (admin.php) |
| Quản Đốc | Trang Nhập năng suất |
| Tổ Trưởng | Trang Nhập năng suất |
| User không có LINE | Trang thông báo "Chưa được phân LINE" |

---

## 4. HƯỚNG DẪN CHO ADMIN

### 4.1. Tổng Quan Trang Quản Trị

Sau khi đăng nhập, Admin sẽ vào trang **Quản trị** với các tab chức năng:

| Tab | Chức năng |
|-----|-----------|
| 📋 Quản lý LINE | Thêm, sửa, xóa các LINE sản xuất |
| 👥 Quản lý User-LINE | Gán nhân viên vào LINE |
| 🔐 Quản lý Quyền | Cấp quyền cho nhân viên |
| 📊 Tạo Báo Cáo | Tạo báo cáo hàng loạt cho nhiều LINE |
| 🏷️ Quản lý Mã hàng | Thêm, sửa, xóa mã sản phẩm |
| ⚙️ Quản lý Công đoạn | Thêm, sửa, xóa công đoạn sản xuất |
| 🔀 Quản lý Routing | Thiết lập quy trình cho từng mã hàng |
| ⏰ Quản lý Preset Mốc Giờ | Thiết lập các mốc giờ nhập liệu |

### 4.2. ⚠️ THỨ TỰ SETUP HỆ THỐNG (RẤT QUAN TRỌNG)

Để hệ thống hoạt động đúng, Admin **BẮT BUỘC** phải setup theo thứ tự sau:

```
┌─────────────────────────────────────────────────────────────┐
│  BƯỚC 1: Tạo LINE                                            │
│     ↓                                                        │
│  BƯỚC 2: Tạo Mã hàng                                         │
│     ↓                                                        │
│  BƯỚC 3: Tạo Công đoạn                                       │
│     ↓                                                        │
│  BƯỚC 4: Tạo Routing (kết nối Mã hàng ↔ Công đoạn)           │
│     ↓                                                        │
│  BƯỚC 5: Tạo Preset Mốc Giờ                                  │
│     ↓                                                        │
│  BƯỚC 6: Gán Nhân viên vào LINE                              │
│     ↓                                                        │
│  BƯỚC 7: Cấp quyền cho Nhân viên                             │
└─────────────────────────────────────────────────────────────┘
```

**⚠️ Nếu bỏ qua bước nào, nhân viên sẽ KHÔNG THỂ tạo báo cáo!**

---

### 4.3. BƯỚC 1: Quản Lý LINE

#### Mục đích
LINE là đơn vị sản xuất cơ bản. Mỗi LINE có:
- Một nhóm công nhân
- Các báo cáo riêng
- Có thể áp dụng mốc giờ riêng

#### Cách thêm LINE mới

1. Vào tab **"Quản lý LINE"**
2. Nhấn nút **[+ Thêm LINE]** (góc phải)
3. Điền thông tin:

   | Trường | Mô tả | Ví dụ |
   |--------|-------|-------|
   | Mã LINE | Mã ngắn gọn, không trùng | L01, L02, LINE-A |
   | Tên LINE | Tên đầy đủ | Line may 1, Line cắt A |

4. Nhấn **[Lưu]**

#### Cách sửa LINE

1. Trong bảng danh sách, tìm LINE cần sửa
2. Nhấn nút **[Sửa]** ở cột Thao tác
3. Sửa thông tin trong popup
4. Nhấn **[Lưu]**

#### Cách vô hiệu hóa LINE

1. Nhấn **[Sửa]** LINE cần vô hiệu
2. Bỏ đánh dấu **"Đang hoạt động"**
3. Nhấn **[Lưu]**

> **Lưu ý:** Vô hiệu hóa LINE không xóa dữ liệu, chỉ ẩn khỏi danh sách chọn.

---

### 4.4. BƯỚC 2: Quản Lý Mã Hàng

#### Mục đích
Mã hàng là sản phẩm mà nhà máy sản xuất. Mỗi mã hàng có routing riêng.

#### Cách thêm Mã hàng mới

1. Vào tab **"Quản lý Mã hàng"**
2. Nhấn nút **[+ Thêm Mã hàng]**
3. Điền thông tin:

   | Trường | Mô tả | Ví dụ |
   |--------|-------|-------|
   | Mã hàng | Mã sản phẩm | MH001, SP-ABC-01 |
   | Tên hàng | Tên sản phẩm | Áo sơ mi nam, Quần jean nữ |

4. Nhấn **[Lưu]**

---

### 4.5. BƯỚC 3: Quản Lý Công Đoạn

#### Mục đích
Công đoạn là các bước trong quy trình sản xuất. Được dùng chung cho nhiều mã hàng.

#### Cách thêm Công đoạn mới

1. Vào tab **"Quản lý Công đoạn"**
2. Nhấn nút **[+ Thêm Công đoạn]**
3. Điền thông tin:

   | Trường | Mô tả | Ví dụ |
   |--------|-------|-------|
   | Mã công đoạn | Mã ngắn gọn | CD01, CUT, SEW |
   | Tên công đoạn | Tên đầy đủ | Cắt, May thân, Đóng gói |
   | Là công đoạn thành phẩm | Đánh dấu nếu là bước cuối | ✓ cho Đóng gói |

4. Nhấn **[Lưu]**

#### Công đoạn thành phẩm là gì?
- Là công đoạn cuối cùng trong quy trình
- Số liệu ở công đoạn này = Sản phẩm hoàn thành
- Được dùng để tính năng suất cuối ngày

---

### 4.6. BƯỚC 4: Quản Lý Routing

#### Mục đích
Routing xác định quy trình sản xuất: **Mã hàng nào đi qua những công đoạn nào, theo thứ tự nào**.

#### ⚠️ QUAN TRỌNG
**Nếu không có Routing, nhân viên sẽ KHÔNG THỂ tạo báo cáo cho mã hàng đó!**

#### Cách tạo Routing

1. Vào tab **"Quản lý Routing"**
2. Chọn **Mã hàng** từ dropdown phía trên
3. Bảng routing của mã hàng đó sẽ hiện ra
4. Nhấn **[+ Thêm Công đoạn]** để thêm từng bước

5. Điền thông tin cho mỗi công đoạn:

   | Trường | Mô tả |
   |--------|-------|
   | Công đoạn | Chọn từ danh sách công đoạn đã tạo |
   | Thứ tự | Số thứ tự (1, 2, 3...) |
   | LINE | Để trống = áp dụng cho tất cả LINE |
   | Bắt buộc | ✓ = Phải nhập số liệu |
   | Tính lũy kế | ✓ = Được tính vào kết quả lũy kế |

6. Nhấn **[Lưu]**

#### Ví dụ Routing hoàn chỉnh cho "Áo sơ mi nam"

| STT | Công đoạn | LINE | Tính lũy kế | Bắt buộc |
|:---:|-----------|------|:-----------:|:--------:|
| 1 | Cắt | Tất cả | ❌ | ✅ |
| 2 | May thân trước | Tất cả | ❌ | ✅ |
| 3 | May thân sau | Tất cả | ❌ | ✅ |
| 4 | May cổ | Tất cả | ❌ | ✅ |
| 5 | May tay | Tất cả | ❌ | ✅ |
| 6 | Ráp hoàn chỉnh | Tất cả | ❌ | ✅ |
| 7 | Đóng gói | Tất cả | ✅ | ✅ |

#### "Tính lũy kế" nghĩa là gì?
- Chỉ công đoạn có **Tính lũy kế = ✓** mới được tính vào kết quả cuối
- Thường đánh dấu cho công đoạn **thành phẩm** (đóng gói)
- Các công đoạn trung gian thường **không đánh dấu**

---

### 4.7. BƯỚC 5: Quản Lý Preset Mốc Giờ

#### Mục đích
Preset mốc giờ xác định các thời điểm nhân viên phải nhập số liệu trong ca làm việc.

#### Cách tạo Preset mới

1. Vào tab **"Quản lý Preset Mốc Giờ"**
2. Nhấn **[+ Thêm Preset]**
3. Điền thông tin:

   | Trường | Mô tả | Ví dụ |
   |--------|-------|-------|
   | Tên Preset | Tên mô tả | Ca Sáng - Chuẩn |
   | Áp dụng cho Ca | Chọn ca | Ca sáng |
   | Đặt làm mặc định | ✓ nếu là preset chính | ✓ |

4. Nhấn **[Lưu]**

#### Cách thêm mốc giờ vào Preset

1. Nhấn **[Chi tiết]** của preset vừa tạo
2. Trong popup Chi tiết, nhấn **[+ Thêm mốc giờ]** (ở phần Mốc giờ thiết lập)
3. Điền thông tin:

   | Trường | Mô tả | Ví dụ |
   |--------|-------|-------|
   | Giờ | Thời điểm nhập liệu | 07:30 |
   | Số phút lũy kế | Tổng phút làm việc tính đến mốc này | 30 |
   | Thứ tự | Thứ tự hiển thị | 1 |

4. Nhấn **[Lưu]**
5. Lặp lại cho các mốc giờ khác

#### Ví dụ đầy đủ các mốc giờ Ca Sáng (7:00 - 11:30)

| STT | Giờ | Phút lũy kế | Giải thích |
|:---:|:---:|:-----------:|------------|
| 1 | 07:30 | 30 | Sau 30 phút làm việc |
| 2 | 08:00 | 60 | Sau 1 giờ |
| 3 | 08:30 | 90 | Sau 1.5 giờ |
| 4 | 09:00 | 120 | Sau 2 giờ |
| 5 | 09:30 | 150 | Sau 2.5 giờ (đã trừ 30p nghỉ) |
| 6 | 10:00 | 180 | Sau 3 giờ |
| 7 | 10:30 | 210 | Sau 3.5 giờ |
| 8 | 11:00 | 240 | Sau 4 giờ |
| 9 | 11:30 | 270 | Cuối ca (4.5 giờ) |

#### Gán Preset cho LINE cụ thể

Mặc định, preset sẽ áp dụng cho tất cả LINE trong ca đó. Nếu muốn LINE nào đó dùng preset riêng:

1. Trong **Chi tiết Preset**, cuộn xuống phần **"Danh sách LINE áp dụng"**
2. Nhấn **[+ Gán thêm LINE]**
3. Đánh dấu các LINE muốn gán
4. Nhấn **[Gán đã chọn]**

---

### 4.8. BƯỚC 6: Gán Nhân Viên Vào LINE

#### Mục đích
Xác định nhân viên nào được phép làm việc với LINE nào.

#### ⚠️ QUAN TRỌNG
**Nhân viên chưa được gán LINE sẽ KHÔNG THỂ tạo báo cáo!**

#### Cách gán nhân viên vào LINE

1. Vào tab **"Quản lý User-LINE"**
2. Nhấn **[+ Thêm Mapping]**
3. Trong popup:
   - **Chọn User:** Tìm và chọn nhân viên (có thể gõ tìm kiếm)
   - **Chọn LINE:** Chọn LINE muốn gán
4. Nhấn **[Thêm]**

#### Xem danh sách theo LINE

- Sử dụng dropdown **"Lọc theo LINE"** phía trên bảng
- Chọn LINE để xem nhân viên được gán vào LINE đó

#### Xóa mapping

1. Tìm dòng cần xóa trong bảng
2. Nhấn **[Xóa]** ở cột Thao tác
3. Xác nhận xóa

---

### 4.9. BƯỚC 7: Cấp Quyền Cho Nhân Viên

#### Mục đích
Cấp các quyền đặc biệt cho nhân viên ngoài quyền mặc định của vai trò.

#### Các quyền có thể cấp

| Quyền | Mô tả | Ai nên có |
|-------|-------|-----------|
| **Quyền xem Lịch sử** | Xem báo cáo các ngày trước | Quản Đốc, Tổ Trưởng |
| **Quyền tạo báo cáo** | Tạo báo cáo cho LINE mình | Tổ Trưởng |
| **Quyền tạo báo cáo (chọn LINE)** | Tạo báo cáo cho LINE bất kỳ | Quản Đốc |

#### Cách cấp/thu hồi quyền

1. Vào tab **"Quản lý Quyền"**
2. Tìm nhân viên bằng ô **"Tìm kiếm user..."**
3. Bật/tắt các switch quyền tương ứng:
   - **Switch BẬT (màu xanh)** = Có quyền
   - **Switch TẮT (màu xám)** = Không có quyền
4. Thay đổi được lưu tự động

---

### 4.10. Tạo Báo Cáo Hàng Loạt

#### Mục đích
Admin có thể tạo sẵn báo cáo cho nhiều LINE cùng lúc, giúp Tổ Trưởng chỉ cần nhập số liệu.

#### Cách tạo báo cáo hàng loạt

1. Vào tab **"Tạo Báo Cáo"**
2. Điền thông tin:

   | Trường | Mô tả |
   |--------|-------|
   | Ngày | Chọn ngày báo cáo |
   | Ca | Chọn ca làm việc |
   | Mã hàng | Chọn sản phẩm |
   | CTNS | Chỉ tiêu năng suất (số lượng mục tiêu) |
   | Số lao động | Số công nhân làm việc |

3. Đánh dấu các **LINE** cần tạo báo cáo
   - Hoặc nhấn **"Chọn tất cả"** để chọn hết

4. Đánh dấu **"Bỏ qua nếu đã tồn tại"** (khuyến nghị)
   - Tránh lỗi khi báo cáo đã được tạo trước đó

5. Nhấn **[Tạo báo cáo hàng loạt]**

6. Xem kết quả:
   - **Đã tạo thành công:** Số báo cáo mới
   - **Đã bỏ qua:** Số báo cáo đã tồn tại

---

### 4.11. Mở Khóa Báo Cáo Đã Chốt

#### Khi nào cần mở khóa?
- Tổ Trưởng đã chốt nhưng cần sửa số liệu
- Phát hiện sai sót sau khi chốt

#### Cách mở khóa (Chỉ Admin)

1. Vào trang **Nhập năng suất** (từ menu)
2. Tìm báo cáo cần mở khóa
3. Nhấn vào báo cáo để xem chi tiết
4. Nhấn nút **[Mở khóa]** (chỉ hiện với Admin)
5. Xác nhận mở khóa
6. Báo cáo trở về trạng thái "Nháp" để có thể sửa

---

## 5. HƯỚNG DẪN CHO TỔ TRƯỞNG

### 5.1. Tổng Quan

Tổ Trưởng là người trực tiếp nhập số liệu năng suất cho LINE mình phụ trách.

**Các việc Tổ Trưởng cần làm hàng ngày:**
1. Đăng nhập và chọn LINE
2. Tạo báo cáo mới (nếu chưa có)
3. Nhập số liệu theo từng mốc giờ
4. Cuối ca: Chốt báo cáo

### 5.2. Màn Hình Chính

Sau khi đăng nhập, Tổ Trưởng sẽ thấy:

```
┌────────────────────────────────────────────────────────────┐
│ [Logo] NHẬP NĂNG SUẤT THEO GIỜ    [Đồng hồ] [User] [Menu] │
├────────────────────────────────────────────────────────────┤
│ [Nhập năng suất] | [Lịch sử]                [+ Tạo BC]    │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  Danh sách báo cáo hôm nay         [+ Tạo báo cáo mới]    │
│                                                            │
│  ┌──────┬─────────┬────────┬──────┬───────┬──────────┐    │
│  │ Ngày │ Mã hàng │ LĐ     │ CTNS │ CT/Giờ│ Trạng thái│   │
│  ├──────┼─────────┼────────┼──────┼───────┼──────────┤    │
│  │ ...  │ ...     │ ...    │ ...  │ ...   │ ...      │    │
│  └──────┴─────────┴────────┴──────┴───────┴──────────┘    │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### 5.3. Tạo Báo Cáo Mới

#### Khi nào cần tạo?
- Đầu ca làm việc
- Khi bắt đầu sản xuất mã hàng mới

#### Các bước tạo báo cáo

**Bước 1:** Nhấn nút **[+ Tạo báo cáo mới]**

**Bước 2:** Điền thông tin trong popup:

| Trường | Mô tả | Ghi chú |
|--------|-------|---------|
| Ngày báo cáo | Ngày sản xuất | Mặc định là hôm nay |
| Mã hàng | Sản phẩm đang sản xuất | Chọn từ danh sách |
| Số lao động | Số công nhân làm việc | Nhập số |
| CTNS | Chỉ tiêu năng suất | Mục tiêu sản lượng |

**Bước 3:** Nhấn **[Tạo báo cáo]**

**Bước 4:** Hệ thống tự động chuyển đến bảng nhập liệu

### 5.4. Nhập Số Liệu Năng Suất

#### Giao diện bảng nhập liệu

```
┌────────────────────────────────────────────────────────────────┐
│ Ngày: 08/01/2026 | Ca: Sáng | Mã: MH001 | LĐ: 30 | CTNS: 500  │
├────────────────────────────────────────────────────────────────┤
│ Trạng thái: [Nháp]              [Lưu] [Chốt báo cáo] [← Quay lại]│
├────────────────────────────────────────────────────────────────┤
│          │ 07:30 │ 08:00 │ 08:30 │ 09:00 │ ... │ Cộng │        │
├──────────┼───────┼───────┼───────┼───────┼─────┼──────┤        │
│ Cắt      │  [50] │  [45] │  [55] │  [48] │ ... │  198 │        │
│ May thân │  [40] │  [42] │  [38] │  [44] │ ... │  164 │        │
│ Đóng gói │  [35] │  [38] │  [36] │  [40] │ ... │  149 │        │
└──────────┴───────┴───────┴───────┴───────┴─────┴──────┴────────┘
```

#### Cách nhập số liệu

1. **Click** vào ô cần nhập (ô có viền)
2. **Gõ** số lượng sản phẩm
3. **Nhấn Enter** hoặc **Tab** để chuyển ô tiếp theo
4. Hệ thống **tự động lưu** sau mỗi thay đổi

#### Phím tắt hữu ích

| Phím | Chức năng |
|------|-----------|
| **Enter** | Chuyển xuống ô phía dưới |
| **Tab** | Chuyển sang ô bên phải |
| **Shift + Tab** | Chuyển sang ô bên trái |
| **Ctrl + S** | Lưu tất cả |
| **Mũi tên** | Di chuyển lên/xuống/trái/phải |

#### Lưu ý quan trọng

- ✅ Số liệu được **lưu tự động** sau mỗi thay đổi
- ✅ Cột **"Cộng"** tự động tính tổng
- ✅ Có thể quay lại sửa bất cứ lúc nào (khi chưa chốt)
- ⚠️ Chỉ nhập số nguyên dương
- ⚠️ Ô màu xám = Không thể sửa

### 5.5. Chốt Báo Cáo

#### Khi nào chốt?
- Cuối ca làm việc
- Khi đã nhập đủ số liệu

#### Cách chốt báo cáo

1. Kiểm tra lại số liệu đã nhập
2. Nhấn nút **[Chốt báo cáo]** (góc phải phía trên bảng)
3. Xác nhận trong popup
4. Báo cáo chuyển sang trạng thái **"Đã chốt"**

#### ⚠️ Sau khi chốt

- **KHÔNG THỂ** sửa số liệu
- **KHÔNG THỂ** thêm hoặc xóa dữ liệu
- Chỉ **Admin** mới có thể mở khóa

### 5.6. Xem Lịch Sử (Nếu Có Quyền)

1. Nhấn vào tab **[Lịch sử]**
2. Chọn khoảng ngày:
   - **Từ ngày:** Ngày bắt đầu
   - **Đến ngày:** Ngày kết thúc
3. Nhấn **[Tìm kiếm]**
4. Danh sách báo cáo hiện ra
5. Nhấn vào dòng bất kỳ để xem chi tiết

---

## 6. HƯỚNG DẪN CHO QUẢN ĐỐC

### 6.1. Tổng Quan

Quản Đốc có thể giám sát nhiều LINE và có các quyền mở rộng tùy theo cấu hình.

### 6.2. Xem Báo Cáo Của Nhiều LINE

Nếu có quyền **tạo báo cáo cho LINE khác**:

1. Khi tạo báo cáo mới, sẽ thấy thêm trường **"Chọn LINE"**
2. Chọn LINE muốn xem/tạo báo cáo
3. Tiếp tục như bình thường

### 6.3. Duyệt Báo Cáo

> **Lưu ý:** Chức năng duyệt phụ thuộc vào quyền được Admin cấp.

#### Các trạng thái báo cáo

| Trạng thái | Màu | Ý nghĩa | Có thể sửa? |
|------------|:---:|---------|:-----------:|
| **Nháp** | ⬜ Xám | Đang nhập liệu | ✅ Có |
| **Đã chốt** | 🟨 Vàng | Tổ Trưởng đã chốt, chờ duyệt | ❌ Không |
| **Đã duyệt** | 🟩 Xanh lá | Quản Đốc đã duyệt | ❌ Không |
| **Đã khóa** | 🟥 Đỏ | Không thể thay đổi | ❌ Không |

#### Quy trình duyệt

1. Mở báo cáo có trạng thái **"Đã chốt"**
2. Kiểm tra số liệu
3. Nhấn **[Duyệt báo cáo]**
4. Xác nhận
5. Báo cáo chuyển sang **"Đã duyệt"**

### 6.4. Xem Lịch Sử Toàn Bộ

Với quyền `can_view_history`:

1. Vào tab **[Lịch sử]**
2. Tìm kiếm theo ngày
3. Xem báo cáo của các LINE được phân quyền
4. Có thể xuất báo cáo (nếu có tính năng)

---

## 7. XỬ LÝ SỰ CỐ THƯỜNG GẶP

### 7.1. Không Thể Đăng Nhập

| Vấn đề | Nguyên nhân | Cách xử lý |
|--------|-------------|------------|
| "Sai mã nhân viên hoặc mật khẩu" | Nhập sai thông tin | Kiểm tra lại, chú ý CHỮ HOA/thường |
| "Tài khoản bị khóa" | Đăng nhập sai 5 lần | Đợi 15 phút hoặc liên hệ Admin |
| Trang trống | Lỗi mạng | Kiểm tra kết nối internet, thử lại |

### 7.2. Không Thể Tạo Báo Cáo

| Vấn đề | Nguyên nhân | Cách xử lý |
|--------|-------------|------------|
| "Chưa được phân LINE" | Chưa gán LINE | Liên hệ Admin gán LINE |
| Không thấy mã hàng | Chưa có routing | Liên hệ Admin tạo routing |
| "Không có mốc giờ" | Chưa tạo preset | Liên hệ Admin tạo preset |
| "Báo cáo đã tồn tại" | Đã có báo cáo cho ngày/ca/mã hàng này | Tìm và sửa báo cáo hiện có |

### 7.3. Không Thể Sửa Số Liệu

| Vấn đề | Nguyên nhân | Cách xử lý |
|--------|-------------|------------|
| Ô bị khóa | Báo cáo đã chốt | Liên hệ Admin mở khóa |
| Không nhập được | Ô không thuộc routing | Kiểm tra routing mã hàng |

### 7.4. Không Thấy Tab Lịch Sử

| Vấn đề | Nguyên nhân | Cách xử lý |
|--------|-------------|------------|
| Tab Lịch sử ẩn | Không có quyền xem lịch sử | Liên hệ Admin cấp quyền `can_view_history` |

---

## 8. CÂU HỎI THƯỜNG GẶP (FAQ)

### 8.1. Câu Hỏi Về Đăng Nhập

**Q: Tôi quên mật khẩu, phải làm sao?**
> A: Liên hệ Admin để được đặt lại mật khẩu. Admin có thể reset mật khẩu về mặc định.

**Q: Tại sao tôi bị khóa tài khoản?**
> A: Hệ thống tự động khóa sau 5 lần đăng nhập sai trong 15 phút. Đợi 15 phút hoặc liên hệ Admin.

**Q: Có thể đăng nhập trên nhiều thiết bị không?**
> A: Có, nhưng mỗi lần đăng nhập mới sẽ tạo phiên làm việc riêng.

### 8.2. Câu Hỏi Về Báo Cáo

**Q: Tôi đã chốt nhầm, có thể mở lại không?**
> A: Có, nhưng chỉ Admin mới có quyền mở khóa báo cáo đã chốt.

**Q: Số liệu có tự động lưu không?**
> A: Có, hệ thống tự động lưu mỗi khi bạn thay đổi và rời khỏi ô nhập.

**Q: Có thể tạo báo cáo cho ngày trước không?**
> A: Có, khi tạo báo cáo bạn có thể chọn ngày bất kỳ.

**Q: Tại sao tôi không thấy mã hàng trong danh sách?**
> A: Có thể mã hàng chưa được tạo routing. Liên hệ Admin để kiểm tra.

### 8.3. Câu Hỏi Về Phân Quyền

**Q: Làm sao để được xem lịch sử?**
> A: Liên hệ Admin để được cấp quyền "Xem lịch sử".

**Q: Tại sao tôi không thể tạo báo cáo cho LINE khác?**
> A: Bạn cần có quyền "Tạo báo cáo cho LINE khác". Liên hệ Admin nếu cần.

---

## LIÊN HỆ HỖ TRỢ

Nếu gặp vấn đề không thể tự giải quyết, vui lòng liên hệ:

- 📧 **Email:** [support@hoatho.com]
- 📞 **Điện thoại:** [Số điện thoại hỗ trợ]
- 👤 **Người phụ trách:** [Tên người phụ trách]

---

> **© 2026 - Hệ Thống Báo Năng Suất - Công Ty May Hòa Thọ Điện Bàn**
