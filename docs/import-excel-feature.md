# Tính Năng Import Excel - Tài Liệu Kỹ Thuật

## 1. Tổng Quan

### 1.1 Mục Đích
Tính năng Import Excel cho phép admin import danh sách công đoạn và routing từ file Excel vào hệ thống. Workflow gồm 2 bước:
1. **Preview**: Upload file → Parse → Hiển thị preview để user xác nhận
2. **Confirm**: User xác nhận → Lưu vào database

### 1.2 Thư Viện Sử Dụng
- **PhpSpreadsheet 4.2.0**: Thư viện PHP để đọc/ghi file Excel
- Đã có sẵn trong `vendor/` (cài qua Composer)

---

## 2. Định Dạng File Excel

### 2.1 Cấu Trúc File
- Mỗi **sheet** = 1 mã hàng
- Có thể có nhiều sheet trong 1 file

### 2.2 Vị Trí Dữ Liệu
| Ô | Nội dung | Ví dụ |
|---|----------|-------|
| `C2` | Mã hàng (format: `MH: XXXX`) | `MH: 6175` |
| `C5` trở đi | Danh sách tên công đoạn (mỗi dòng 1 công đoạn) | `NHẬN HÀNG`, `MÍ DIỄU TÚI TREO`, ... |

### 2.3 Ví Dụ Layout Excel
```
     A          B          C
1    ...        ...        ...
2    ...        ...        MH: 6175
3    ...        ...        ...
4    ...        ...        ...
5    ...        ...        NHẬN HÀNG
6    ...        ...        MÍ DIỄU TÚI TREO
7    ...        ...        QUAY TÚI DAO
8    ...        ...        TRA DK
...
```

### 2.4 Giới Hạn
- **File size**: Tối đa 10MB
- **Định dạng**: `.xlsx`, `.xls`
- **Số dòng công đoạn**: Tối đa 200 dòng (từ C5 đến C204)
- **Empty rows**: Dừng đọc sau 5 dòng trống liên tiếp

---

## 3. Kiến Trúc Hệ Thống

### 3.1 Các File Liên Quan

| File | Mô tả |
|------|-------|
| [`classes/services/ImportService.php`](../classes/services/ImportService.php) | Service xử lý logic import |
| [`assets/js/modules/admin/import.js`](../assets/js/modules/admin/import.js) | Frontend module cho tab Import |
| [`api/index.php`](../api/index.php) | API router (route `import/*`) |

### 3.2 Luồng Dữ Liệu

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Frontend      │     │   API Router    │     │  ImportService  │
│   (import.js)   │────▶│  (index.php)    │────▶│                 │
└─────────────────┘     └─────────────────┘     └────────┬────────┘
                                                         │
                        ┌────────────────────────────────┼────────────────────────────────┐
                        ▼                                ▼                                ▼
               ┌─────────────────┐              ┌─────────────────┐              ┌─────────────────┐
               │  ma_hang table  │              │  cong_doan      │              │ ma_hang_cong_doan│
               │                 │              │  table          │              │ (routing)        │
               └─────────────────┘              └─────────────────┘              └─────────────────┘
```

---

## 4. API Endpoints

### 4.1 Preview Import

**Endpoint**: `POST /api/import/preview`

**Headers**:
```
Content-Type: multipart/form-data
X-CSRF-Token: {csrf_token}
```

**Request Body** (multipart/form-data):
| Field | Type | Description |
|-------|------|-------------|
| `file` | File | Excel file (.xlsx, .xls) |

**Response thành công**:
```json
{
  "success": true,
  "message": "Phân tích file thành công",
  "data": [
    {
      "sheet_name": "Sheet1",
      "ma_hang": "6175",
      "ten_hang": "Mã hàng 6175",
      "is_new": false,
      "existing_id": 1,
      "report_stats": {
        "has_reports": true,
        "total_reports": 5,
        "locked_reports": 3,
        "draft_reports": 2
      },
      "has_warning": true,
      "warning_message": "Mã hàng 6175 có 3 báo cáo đã chốt...",
      "cong_doan_list": [
        {
          "thu_tu": 1,
          "ten_cong_doan": "NHẬN HÀNG",
          "ma_cong_doan": "CD012",
          "is_new": false,
          "existing_id": 12
        },
        {
          "thu_tu": 2,
          "ten_cong_doan": "MÍ DIỄU TÚI TREO",
          "ma_cong_doan": "CD013",
          "is_new": true,
          "existing_id": null
        }
      ]
    }
  ],
  "stats": {
    "total_sheets": 1,
    "total_ma_hang_new": 0,
    "total_ma_hang_existing": 1,
    "total_cong_doan_new": 5,
    "total_cong_doan_existing": 12,
    "total_routing_new": 17,
    "routing_to_delete": 10
  },
  "errors": []
}
```

### 4.2 Confirm Import

**Endpoint**: `POST /api/import/confirm`

**Headers**:
```
Content-Type: application/json
X-CSRF-Token: {csrf_token}
```

**Request Body**:
```json
{
  "ma_hang_list": [
    {
      "ma_hang": "6175",
      "ten_hang": "Mã hàng 6175",
      "cong_doan_list": [
        {
          "thu_tu": 1,
          "ten_cong_doan": "NHẬN HÀNG",
          "ma_cong_doan": "CD012",
          "existing_id": 12
        }
      ]
    }
  ],
  "acknowledge_deletion": true
}
```

**Response thành công**:
```json
{
  "success": true,
  "message": "Import thành công",
  "stats": {
    "ma_hang_created": 0,
    "ma_hang_updated": 1,
    "cong_doan_created": 5,
    "routing_created": 17,
    "routing_deleted": 10
  }
}
```

---

## 5. Business Logic

### 5.1 Quy Tắc Matching Công Đoạn

1. **Normalize text**: `trim()` + loại bỏ multiple spaces
2. **Case-insensitive**: So sánh bằng `UPPER()`
3. **Tái sử dụng**: Nếu công đoạn đã tồn tại → dùng lại, không tạo mới

```php
// Ví dụ matching
$normalized = $this->normalizeText($tenCongDoan);
$upperName = mb_strtoupper($normalized, 'UTF-8');

$stmt = mysqli_prepare($this->db, 
    "SELECT id, ma_cong_doan, ten_cong_doan 
     FROM cong_doan 
     WHERE UPPER(TRIM(ten_cong_doan)) = ?");
```

### 5.2 Quy Tắc Tạo Mã Công Đoạn

- Format: `CD{NNN}` (VD: CD001, CD002, CD015)
- Auto-increment từ số lớn nhất hiện có

```php
// Tìm số lớn nhất
SELECT MAX(CAST(SUBSTRING(ma_cong_doan, 3) AS UNSIGNED)) 
FROM cong_doan 
WHERE ma_cong_doan REGEXP '^CD[0-9]+$';

// Ví dụ: max = 14 → next = CD015
```

### 5.3 Xử Lý Mã Hàng Đã Tồn Tại

Khi import mã hàng đã có trong database:

1. **Kiểm tra báo cáo**: Đếm số báo cáo đã chốt (locked/submitted/approved)
2. **Hiển thị cảnh báo**: Nếu có báo cáo đã chốt
3. **Xóa routing cũ**: Routing không có trong file Excel mới sẽ bị xóa
4. **Yêu cầu xác nhận**: User phải acknowledge trước khi xóa

### 5.4 Routing Snapshot

**Quan trọng**: Khi tạo báo cáo, hệ thống lưu snapshot của routing tại thời điểm đó vào cột `routing_snapshot` (JSON). Điều này đảm bảo:
- Báo cáo cũ vẫn hiển thị đúng công đoạn tại thời điểm tạo
- Import routing mới không ảnh hưởng báo cáo đã chốt (nếu có snapshot)

---

## 6. Database Schema

### 6.1 Bảng `ma_hang`
```sql
CREATE TABLE ma_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_hang VARCHAR(50) NOT NULL,
    ten_hang VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 6.2 Bảng `cong_doan`
```sql
CREATE TABLE cong_doan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_cong_doan VARCHAR(20) NOT NULL,
    ten_cong_doan VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    la_cong_doan_thanh_pham TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 6.3 Bảng `ma_hang_cong_doan` (Routing)
```sql
CREATE TABLE ma_hang_cong_doan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_hang_id INT NOT NULL,
    cong_doan_id INT NOT NULL,
    thu_tu INT NOT NULL,
    bat_buoc TINYINT(1) DEFAULT 1,
    la_cong_doan_tinh_luy_ke TINYINT(1) DEFAULT 0,
    line_id INT DEFAULT NULL,
    hieu_luc_tu DATE,
    hieu_luc_den DATE DEFAULT NULL,
    FOREIGN KEY (ma_hang_id) REFERENCES ma_hang(id),
    FOREIGN KEY (cong_doan_id) REFERENCES cong_doan(id)
);
```

---

## 7. Frontend Implementation

### 7.1 Module Structure

File: [`assets/js/modules/admin/import.js`](../assets/js/modules/admin/import.js)

```javascript
// Các function chính
export function init()           // Khởi tạo module, bindEvents
function renderUploadUI()        // Render giao diện upload
function handleFileSelect(file)  // Xử lý khi chọn file
async function processFile(file) // Upload và preview
function renderPreview(response) // Render kết quả preview
async function handleConfirmImport() // Xác nhận import
function renderImportResult(response) // Render kết quả import
```

### 7.2 UI Components

1. **Upload Zone**: Drag & drop hoặc click để chọn file
2. **Preview Section**: Hiển thị thống kê và danh sách mã hàng
3. **Warning Alerts**: Cảnh báo khi có báo cáo đã chốt
4. **Result Section**: Hiển thị kết quả sau khi import

### 7.3 Validation Frontend

```javascript
// Kiểm tra file type
const allowedTypes = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel'
];
const allowedExtensions = ['.xlsx', '.xls'];

// Kiểm tra file size
if (file.size > 10 * 1024 * 1024) {
    showToast('File vượt quá 10MB', 'error');
    return;
}
```

---

## 8. Error Handling

### 8.1 Error Codes

| Code | HTTP Status | Mô tả |
|------|-------------|-------|
| `NO_FILE_UPLOADED` | 400 | Không có file được upload |
| `INVALID_FILE_TYPE` | 400 | File không phải Excel |
| `FILE_TOO_LARGE` | 400 | File vượt quá 10MB |
| `INVALID_MA_HANG_FORMAT` | 200 (partial) | Ô C2 không đúng format |
| `EMPTY_CONG_DOAN_LIST` | 200 (partial) | Không có công đoạn từ C5 |
| `VALIDATION_FAILED` | 400 | Dữ liệu confirm không hợp lệ |
| `DELETION_WARNING` | 400 | Cần xác nhận xóa routing |
| `IMPORT_FAILED` | 500 | Lỗi database |

### 8.2 Transaction Handling

Import sử dụng transaction để đảm bảo atomicity:

```php
mysqli_begin_transaction($this->db);
try {
    // ... import logic ...
    mysqli_commit($this->db);
} catch (Exception $e) {
    mysqli_rollback($this->db);
    // return error
}
```

---

## 9. Security

### 9.1 Authentication & Authorization
- Yêu cầu đăng nhập với role `admin`
- CSRF token bắt buộc cho cả preview và confirm

### 9.2 File Upload Security
- Chỉ chấp nhận `.xlsx`, `.xls`
- Validate MIME type
- Giới hạn file size: 10MB
- File tạm được xóa sau khi parse

### 9.3 Data Validation
- Sanitize input từ Excel
- Escape HTML entities
- Validate format mã hàng

---

## 10. Hướng Dẫn Phát Triển

### 10.1 Thêm Cột Mới Trong Excel

Nếu cần đọc thêm cột khác (VD: cột D cho thời gian chuẩn):

1. Sửa [`ImportService::extractCongDoanList()`](../classes/services/ImportService.php:390):
```php
private function extractCongDoanList($sheet) {
    // ...
    $tenCongDoan = trim((string)$sheet->getCell('C' . $row)->getValue());
    $thoiGianChuan = trim((string)$sheet->getCell('D' . $row)->getValue());
    // ...
}
```

2. Cập nhật response structure trong `preview()`
3. Cập nhật frontend để hiển thị cột mới

### 10.2 Thay Đổi Vị Trí Ô

Sửa trong [`ImportService::extractMaHang()`](../classes/services/ImportService.php:366):
```php
private function extractMaHang($sheet) {
    $cellValue = $sheet->getCell('C2')->getValue(); // Đổi 'C2' thành ô khác
    // ...
}
```

### 10.3 Thêm Validation Mới

Thêm trong [`ImportService::preview()`](../classes/services/ImportService.php:15):
```php
// Ví dụ: validate tên công đoạn không quá 100 ký tự
if (mb_strlen($tenCongDoan) > 100) {
    $errors[] = [
        'sheet_name' => $sheetName,
        'cell' => 'C' . $row,
        'error_code' => 'CONG_DOAN_TOO_LONG',
        'message' => 'Tên công đoạn không được quá 100 ký tự'
    ];
}
```

### 10.4 Thêm Tính Năng Export Template

Tạo endpoint mới để download file Excel mẫu:

```php
// Trong api/index.php
case 'import/template':
    if ($method === 'GET') {
        // Generate và return file Excel mẫu
    }
    break;
```

---

## 11. Troubleshooting

### 11.1 Lỗi "Không tìm thấy mã hàng trong ô C2"

**Nguyên nhân**:
- Ô C2 trống
- Format không đúng (cần `MH: XXXX`)
- Có ký tự ẩn hoặc space thừa

**Giải pháp**:
- Kiểm tra ô C2 trong Excel
- Đảm bảo format: `MH: ` + 4 số (VD: `MH: 6175`)

### 11.2 Lỗi "Không tìm thấy công đoạn nào từ C5"

**Nguyên nhân**:
- Cột C từ dòng 5 trở đi trống
- Dữ liệu nằm ở cột khác

**Giải pháp**:
- Kiểm tra dữ liệu công đoạn nằm đúng cột C
- Bắt đầu từ dòng 5

### 11.3 Import Không Cập Nhật Routing

**Nguyên nhân**:
- Routing đã tồn tại với cùng `ma_hang_id` + `cong_doan_id`
- Chỉ cập nhật `thu_tu` nếu khác

**Giải pháp**:
- Kiểm tra log để xem routing nào đã tồn tại
- Xóa routing cũ trước nếu cần thay đổi hoàn toàn

### 11.4 Báo Cáo Cũ Hiển Thị Công Đoạn Sai

**Nguyên nhân**:
- Báo cáo không có `routing_snapshot`
- Báo cáo được tạo trước khi tính năng snapshot được triển khai

**Giải pháp**:
- Báo cáo mới sẽ tự động có snapshot
- Báo cáo cũ sẽ dùng routing hiện tại (có thể khác với lúc tạo)

---

## 12. Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-12 | Initial implementation |
| 1.1 | 2026-01 | Thêm warning cho báo cáo đã chốt |
| 1.2 | 2026-01 | Thêm acknowledge_deletion flow |

---

## 13. Tài Liệu Liên Quan

- [Design Document gốc](./import-excel-design.md)
- [Routing Snapshot Design](./ket-qua-luy-ke-design.md)
- [Admin Setup Checklist](./admin-setup-checklist.md)
