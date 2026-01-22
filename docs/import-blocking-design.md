# Import Blocking Rules - Thiết Kế Chi Tiết

## Tổng Quan

Tài liệu này mô tả thiết kế chi tiết cho tính năng **chặn import routing** khi có báo cáo đang sử dụng mã hàng đó.

### Mục Tiêu

1. **Bảo vệ dữ liệu**: Không cho phép thay đổi routing khi đã có báo cáo đang sử dụng
2. **Thông báo rõ ràng**: Người dùng biết chính xác lý do bị chặn và cách khắc phục
3. **Liệt kê chi tiết**: Hiển thị danh sách các báo cáo đang chặn

---

## Điều Kiện Chặn Import

### Điều Kiện 1: Báo Cáo Đã Chốt/Hoàn Thành

Báo cáo với trạng thái sau sẽ **CHẶN IMPORT**:
- `submitted` - Đã nộp
- `approved` - Đã duyệt  
- `locked` - Đã khóa
- `completed` - Đã hoàn thành

**Lý do**: Các báo cáo này đã được xác nhận, không nên thay đổi routing.

### Điều Kiện 2: Báo Cáo Đang Nhập Dữ Liệu

Báo cáo `draft` (nháp) có ít nhất một entry với `so_luong > 0` sẽ **CHẶN IMPORT**.

**Lý do**: Người dùng đang nhập liệu, thay đổi routing sẽ gây mất dữ liệu hoặc không khớp.

---

## Cấu Trúc Dữ Liệu

### BlockingReport - Thông tin báo cáo chặn

```php
[
    'id' => int,                    // ID báo cáo
    'ngay_bao_cao' => string,       // Ngày báo cáo (Y-m-d)
    'line_id' => int,               // ID line
    'ten_line' => string,           // Tên line  
    'ma_ca' => string,              // Mã ca làm
    'trang_thai' => string,         // Trạng thái hiện tại
    'trang_thai_display' => string, // Trạng thái hiển thị tiếng Việt
    'reason' => string,             // Lý do chặn (key)
    'reason_display' => string,     // Lý do hiển thị tiếng Việt
    'total_entries' => int,         // Tổng số entries có dữ liệu (chỉ với draft)
    'tao_boi' => string,            // Người tạo
    'tao_luc' => string             // Thời điểm tạo
]
```

### BlockingCheckResult - Kết quả kiểm tra

```php
[
    'is_blocked' => bool,                    // Có bị chặn không
    'blocking_reports' => BlockingReport[], // Danh sách báo cáo chặn
    'summary' => [
        'total_blocking' => int,             // Tổng số báo cáo chặn
        'locked_count' => int,               // Số báo cáo đã chốt
        'draft_with_data_count' => int       // Số báo cáo nháp có dữ liệu
    ],
    'message' => string,                     // Thông báo tổng hợp (tiếng Việt)
    'actions' => string[]                    // Các hành động khắc phục
]
```

---

## SQL Queries

### Query 1: Tìm Báo Cáo Đã Chốt (Locked/Completed)

```sql
SELECT 
    bc.id,
    bc.ngay_bao_cao,
    bc.line_id,
    l.ten_line,
    ca.ma_ca,
    bc.trang_thai,
    bc.tao_boi,
    bc.tao_luc,
    'LOCKED_REPORT' as reason
FROM bao_cao_nang_suat bc
JOIN line l ON l.id = bc.line_id
JOIN ca_lam ca ON ca.id = bc.ca_id
WHERE bc.ma_hang_id = ?
  AND bc.trang_thai IN ('submitted', 'approved', 'locked', 'completed')
ORDER BY bc.ngay_bao_cao DESC, l.ten_line
LIMIT 50
```

### Query 2: Tìm Báo Cáo Nháp Có Dữ Liệu (Draft with Data)

```sql
SELECT 
    bc.id,
    bc.ngay_bao_cao,
    bc.line_id,
    l.ten_line,
    ca.ma_ca,
    bc.trang_thai,
    bc.tao_boi,
    bc.tao_luc,
    'DRAFT_WITH_DATA' as reason,
    (SELECT COUNT(*) FROM nhap_lieu_nang_suat nl 
     WHERE nl.bao_cao_id = bc.id AND nl.so_luong > 0) as total_entries
FROM bao_cao_nang_suat bc
JOIN line l ON l.id = bc.line_id
JOIN ca_lam ca ON ca.id = bc.ca_id
WHERE bc.ma_hang_id = ?
  AND bc.trang_thai = 'draft'
  AND EXISTS (
      SELECT 1 FROM nhap_lieu_nang_suat nl
      WHERE nl.bao_cao_id = bc.id AND nl.so_luong > 0
  )
ORDER BY bc.ngay_bao_cao DESC, l.ten_line
LIMIT 50
```

### Query 3 (Optional): Đếm Nhanh Để Kiểm Tra

```sql
-- Quick check without details
SELECT 
    (SELECT COUNT(*) FROM bao_cao_nang_suat 
     WHERE ma_hang_id = ? 
       AND trang_thai IN ('submitted', 'approved', 'locked', 'completed')) as locked_count,
    (SELECT COUNT(*) FROM bao_cao_nang_suat bc
     WHERE bc.ma_hang_id = ? 
       AND bc.trang_thai = 'draft'
       AND EXISTS (SELECT 1 FROM nhap_lieu_nang_suat nl 
                   WHERE nl.bao_cao_id = bc.id AND nl.so_luong > 0)) as draft_with_data_count
```

---

## PHP Method Signatures

### Method: checkBlockingReports

```php
/**
 * Kiểm tra các báo cáo chặn import cho một mã hàng
 * 
 * @param int $maHangId ID của mã hàng cần kiểm tra
 * @return array BlockingCheckResult
 */
public function checkBlockingReports(int $maHangId): array
```

**Return Value:**

```php
[
    'is_blocked' => true,
    'blocking_reports' => [
        [
            'id' => 123,
            'ngay_bao_cao' => '2026-01-20',
            'line_id' => 5,
            'ten_line' => 'Line 1',
            'ma_ca' => 'S1',
            'trang_thai' => 'submitted',
            'trang_thai_display' => 'Đã nộp',
            'reason' => 'LOCKED_REPORT',
            'reason_display' => 'Báo cáo đã chốt',
            'total_entries' => null,
            'tao_boi' => 'NV001',
            'tao_luc' => '2026-01-20 08:00:00'
        ],
        [
            'id' => 124,
            'ngay_bao_cao' => '2026-01-21',
            'line_id' => 5,
            'ten_line' => 'Line 1',
            'ma_ca' => 'S1',
            'trang_thai' => 'draft',
            'trang_thai_display' => 'Nháp',
            'reason' => 'DRAFT_WITH_DATA',
            'reason_display' => 'Đang nhập dữ liệu (15 entries)',
            'total_entries' => 15,
            'tao_boi' => 'NV002',
            'tao_luc' => '2026-01-21 08:00:00'
        ]
    ],
    'summary' => [
        'total_blocking' => 2,
        'locked_count' => 1,
        'draft_with_data_count' => 1
    ],
    'message' => 'Không thể import vì có 2 báo cáo đang sử dụng mã hàng này: 1 báo cáo đã chốt và 1 báo cáo đang nhập dữ liệu.',
    'actions' => [
        'Xóa hoặc hoàn thành các báo cáo nháp đang có dữ liệu',
        'Liên hệ quản trị viên nếu cần mở khóa báo cáo đã chốt'
    ]
]
```

### Method: formatBlockingMessage

```php
/**
 * Tạo thông báo lỗi chi tiết cho response
 * 
 * @param array $blockingResult Kết quả từ checkBlockingReports
 * @param string $maHang Mã hàng đang import
 * @return string Thông báo đầy đủ
 */
private function formatBlockingMessage(array $blockingResult, string $maHang): string
```

---

## Integration Points

### 1. Tích Hợp Vào `preview()` Method

**Vị trí**: Sau khi tìm thấy mã hàng existing (line 70)

**Thay đổi**:
```php
// BEFORE (current)
$reportStats = $this->checkExistingReports(intval($existingMaHang['id']));
if ($reportStats['locked_reports'] > 0) {
    $hasWarning = true;
    $warningMessage = "Mã hàng {$maHang} có {$reportStats['locked_reports']} báo cáo đã chốt...";
}

// AFTER (new)
$blockingCheck = $this->checkBlockingReports(intval($existingMaHang['id']));
if ($blockingCheck['is_blocked']) {
    $hasError = true;
    $isBlocked = true;
    $errorMessage = $blockingCheck['message'];
    $blockingReports = $blockingCheck['blocking_reports'];
}
```

**Preview Response Enhancement**:
```php
$data[] = [
    'sheet_name' => $sheetName,
    'ma_hang' => $maHang,
    'ten_hang' => 'Mã hàng ' . $maHang,
    'is_new' => $isNewMaHang,
    'existing_id' => $existingMaHang['id'] ?? null,
    
    // NEW fields
    'is_blocked' => $isBlocked,
    'blocking_check' => $blockingCheck,  // Full blocking details
    'has_error' => $hasError,
    'error_message' => $errorMessage,
    
    // Keep existing
    'has_warning' => $hasWarning,
    'warning_message' => $warningMessage,
    'cong_doan_list' => $congDoanList
];
```

**Preview Stats Enhancement**:
```php
$stats = [
    // existing...
    'total_blocked' => 0,  // NEW: count of blocked ma_hang
    'blocked_by_locked' => 0,
    'blocked_by_draft_data' => 0
];
```

### 2. Tích Hợp Vào `confirm()` Method

**Vị trí**: Trước khi bắt đầu transaction (line 181)

**Logic**:
```php
public function confirm($maHangList, $acknowledgeDeletion = false) {
    // ... existing validation ...
    
    // NEW: Check blocking reports for ALL ma_hang
    $blockedMaHangList = [];
    
    foreach ($maHangList as $maHangData) {
        $maHang = strtoupper(trim($maHangData['ma_hang'] ?? ''));
        if (!empty($maHang)) {
            $existing = $this->findMaHangByCode($maHang);
            if ($existing !== null) {
                $blockingCheck = $this->checkBlockingReports(intval($existing['id']));
                if ($blockingCheck['is_blocked']) {
                    $blockedMaHangList[] = [
                        'ma_hang' => $maHang,
                        'blocking_check' => $blockingCheck
                    ];
                }
            }
        }
    }
    
    // Block if any ma_hang is blocked
    if (!empty($blockedMaHangList)) {
        return [
            'success' => false,
            'message' => 'Không thể import vì một số mã hàng đang có báo cáo sử dụng',
            'error_code' => 'IMPORT_BLOCKED',
            'blocked_ma_hang' => $blockedMaHangList
        ];
    }
    
    // Continue with existing logic...
}
```

---

## Error Response Format

### API Response khi bị chặn (preview)

```json
{
    "success": true,
    "message": "Phân tích file hoàn tất với một số mã hàng bị chặn",
    "data": [
        {
            "sheet_name": "Sheet1",
            "ma_hang": "1234",
            "is_new": false,
            "existing_id": 45,
            "is_blocked": true,
            "blocking_check": {
                "is_blocked": true,
                "blocking_reports": [
                    {
                        "id": 123,
                        "ngay_bao_cao": "2026-01-20",
                        "ten_line": "Line 1",
                        "ma_ca": "S1",
                        "trang_thai": "submitted",
                        "trang_thai_display": "Đã nộp",
                        "reason": "LOCKED_REPORT",
                        "reason_display": "Báo cáo đã chốt"
                    }
                ],
                "summary": {
                    "total_blocking": 1,
                    "locked_count": 1,
                    "draft_with_data_count": 0
                },
                "message": "Mã hàng 1234 không thể import vì có 1 báo cáo đã chốt.",
                "actions": [
                    "Liên hệ quản trị viên nếu cần mở khóa báo cáo đã chốt"
                ]
            },
            "has_error": true,
            "error_message": "Mã hàng 1234 không thể import vì có 1 báo cáo đã chốt.",
            "cong_doan_list": [...]
        }
    ],
    "stats": {
        "total_sheets": 5,
        "total_ma_hang_new": 3,
        "total_ma_hang_existing": 2,
        "total_blocked": 1,
        "blocked_by_locked": 1,
        "blocked_by_draft_data": 0
    },
    "errors": []
}
```

### API Response khi bị chặn (confirm)

```json
{
    "success": false,
    "message": "Không thể import vì một số mã hàng đang có báo cáo sử dụng",
    "error_code": "IMPORT_BLOCKED",
    "blocked_ma_hang": [
        {
            "ma_hang": "1234",
            "blocking_check": {
                "is_blocked": true,
                "blocking_reports": [...],
                "summary": {...},
                "message": "...",
                "actions": [...]
            }
        }
    ]
}
```

---

## Vietnamese Messages

### Status Display Map

```php
private const STATUS_DISPLAY = [
    'draft' => 'Nháp',
    'submitted' => 'Đã nộp',
    'approved' => 'Đã duyệt',
    'locked' => 'Đã khóa',
    'completed' => 'Đã hoàn thành'
];
```

### Reason Display Map

```php
private const REASON_DISPLAY = [
    'LOCKED_REPORT' => 'Báo cáo đã chốt',
    'DRAFT_WITH_DATA' => 'Đang nhập dữ liệu'
];
```

### Message Templates

| Tình huống | Message |
|------------|---------|
| Chỉ có locked | `Mã hàng {ma_hang} không thể import vì có {count} báo cáo đã chốt.` |
| Chỉ có draft_with_data | `Mã hàng {ma_hang} không thể import vì có {count} báo cáo đang nhập dữ liệu.` |
| Có cả hai | `Mã hàng {ma_hang} không thể import vì có {total} báo cáo đang sử dụng: {locked_count} báo cáo đã chốt và {draft_count} báo cáo đang nhập dữ liệu.` |

### Action Messages

| Reason | Action |
|--------|--------|
| `LOCKED_REPORT` | `Liên hệ quản trị viên nếu cần mở khóa báo cáo đã chốt` |
| `DRAFT_WITH_DATA` | `Xóa hoặc hoàn thành các báo cáo nháp đang có dữ liệu` |

---

## User Experience

### Preview Screen Changes

1. **Blocked Ma Hang Indicator**:
   - Icon: Dấu chấm than đỏ (⛔)
   - Color: Red background row
   - Tooltip: Chi tiết blocking

2. **Blocking Details Panel**:
   ```
   ┌─────────────────────────────────────────────────────────────┐
   │ ⛔ MÃ HÀNG 1234 - KHÔNG THỂ IMPORT                          │
   ├─────────────────────────────────────────────────────────────┤
   │ Lý do: Có 2 báo cáo đang sử dụng mã hàng này                │
   │                                                             │
   │ Danh sách báo cáo chặn:                                     │
   │ ┌───────────────────────────────────────────────────────┐   │
   │ │ # │ Ngày       │ Line    │ Ca  │ Trạng thái │ Lý do    │  │
   │ ├───────────────────────────────────────────────────────┤   │
   │ │ 1 │ 2026-01-20 │ Line 1  │ S1  │ Đã nộp     │ Đã chốt  │  │
   │ │ 2 │ 2026-01-21 │ Line 1  │ S1  │ Nháp       │ 15 entries│  │
   │ └───────────────────────────────────────────────────────┘   │
   │                                                             │
   │ Cách khắc phục:                                             │
   │ • Xóa hoặc hoàn thành các báo cáo nháp đang có dữ liệu      │
   │ • Liên hệ quản trị viên nếu cần mở khóa báo cáo đã chốt     │
   └─────────────────────────────────────────────────────────────┘
   ```

3. **Import Button State**:
   - Nếu có bất kỳ ma_hang bị blocked → Disable button "Import"
   - Tooltip: "Không thể import vì có {n} mã hàng bị chặn"
   - Hiển thị button "Bỏ qua mã hàng bị chặn" để import các mã hàng khác

### Confirm Screen Changes

1. **Pre-confirm Validation**:
   - Trước khi gửi request confirm, frontend kiểm tra `is_blocked`
   - Không cho chọn các mã hàng bị blocked

2. **Error Toast** (nếu API trả về IMPORT_BLOCKED):
   ```
   ❌ Không thể import
   Một số mã hàng đang có báo cáo sử dụng. Vui lòng kiểm tra lại preview.
   ```

---

## Implementation Phases

### Phase 1: Backend Core (Ưu tiên cao)
1. Implement `checkBlockingReports()` method
2. Update `preview()` to use new blocking check
3. Update `confirm()` to validate blocking before import
4. Add new error codes and response format

### Phase 2: Frontend Updates
1. Update preview rendering to show blocked status
2. Add blocking details panel
3. Disable import button when blocked
4. Add "skip blocked" functionality (optional)

### Phase 3: Testing & Polish
1. Unit tests for `checkBlockingReports()`
2. Integration tests for import flow
3. Edge cases: ma_hang không tồn tại, ma_hang mới, etc.

---

## Edge Cases

| Case | Behavior |
|------|----------|
| Mã hàng mới (chưa có trong DB) | `is_blocked = false`, cho phép import |
| Mã hàng có draft với `so_luong = 0` | `is_blocked = false`, cho phép import |
| Mã hàng chỉ có draft rỗng | `is_blocked = false`, cho phép import |
| Mã hàng đã bị xóa soft-delete | Cần kiểm tra thêm (out of scope) |
| Nhiều mã hàng, một bị blocked | Chặn import toàn bộ hoặc cho phép bỏ qua |

---

## Backward Compatibility

1. **checkExistingReports()** vẫn giữ nguyên (deprecated)
2. Thêm method mới `checkBlockingReports()` 
3. Response format mở rộng, không breaking change
4. Frontend cần update để hiển thị blocking UI

---

## Appendix: Complete PHP Implementation Skeleton

```php
class ImportService {
    // ... existing code ...
    
    private const STATUS_DISPLAY = [
        'draft' => 'Nháp',
        'submitted' => 'Đã nộp',
        'approved' => 'Đã duyệt',
        'locked' => 'Đã khóa',
        'completed' => 'Đã hoàn thành'
    ];
    
    private const BLOCKING_STATUSES = ['submitted', 'approved', 'locked', 'completed'];
    
    /**
     * Kiểm tra các báo cáo chặn import
     */
    public function checkBlockingReports(int $maHangId): array {
        $blockingReports = [];
        $lockedCount = 0;
        $draftWithDataCount = 0;
        
        // Query 1: Locked/completed reports
        // Query 2: Draft reports with data
        // ... implementation ...
        
        $isBlocked = !empty($blockingReports);
        
        return [
            'is_blocked' => $isBlocked,
            'blocking_reports' => $blockingReports,
            'summary' => [
                'total_blocking' => count($blockingReports),
                'locked_count' => $lockedCount,
                'draft_with_data_count' => $draftWithDataCount
            ],
            'message' => $this->formatBlockingMessage($blockingReports, $lockedCount, $draftWithDataCount),
            'actions' => $this->getBlockingActions($lockedCount, $draftWithDataCount)
        ];
    }
    
    private function formatBlockingMessage(array $reports, int $locked, int $draft): string {
        if (empty($reports)) {
            return '';
        }
        
        $total = count($reports);
        
        if ($locked > 0 && $draft > 0) {
            return "Có {$total} báo cáo đang sử dụng: {$locked} đã chốt và {$draft} đang nhập dữ liệu.";
        } elseif ($locked > 0) {
            return "Có {$locked} báo cáo đã chốt đang sử dụng mã hàng này.";
        } else {
            return "Có {$draft} báo cáo đang nhập dữ liệu.";
        }
    }
    
    private function getBlockingActions(int $locked, int $draft): array {
        $actions = [];
        if ($draft > 0) {
            $actions[] = 'Xóa hoặc hoàn thành các báo cáo nháp đang có dữ liệu';
        }
        if ($locked > 0) {
            $actions[] = 'Liên hệ quản trị viên nếu cần mở khóa báo cáo đã chốt';
        }
        return $actions;
    }
}
```

---

## Document Info

- **Version**: 1.0
- **Created**: 2026-01-21
- **Author**: System Design
- **Status**: Design Review
