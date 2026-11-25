# Kế hoạch router cho nhap-nang-suat (hash routes)

## Overview

Mục tiêu: refactor trang nhập năng suất để dùng hash-based routing tương tự admin, đồng thời bổ sung các route sâu cho lịch sử. Tài liệu tham chiếu kiến trúc router hiện có: [`docs/router-architecture-design.md`](docs/router-architecture-design.md:1).

## Analysis: Router pattern hiện có (admin)

- Khởi tạo: đọc hash ban đầu và chuyển tab nếu hợp lệ, qua [`getTabFromHash()`](assets/js/admin.js:269) và [`switchTab()`](assets/js/admin.js:275).
- Đồng bộ URL: khi chuyển tab, cập nhật hash bằng [`history.pushState(null, null, `#${tabName}`)`](assets/js/admin.js:285), đồng thời lắng nghe thay đổi hash qua [`window.addEventListener('hashchange', ...)`](assets/js/admin.js:50).
- Router = “hash tab”: danh sách hợp lệ được lọc trong [`getTabFromHash()`](assets/js/admin.js:269), UI state được bật/tắt trong [`switchTab()`](assets/js/admin.js:275).

Điểm chính: không có router class tách riêng; logic tối giản trong một file, hash chỉ chứa “tab key”.

## Analysis: Tab switching hiện tại (nhap-nang-suat)

- Tab input/history chỉ là toggling DOM (không cập nhật URL) qua [`switchMainTab()`](assets/js/app.js:146).
- Lịch sử dùng module riêng [`HistoryModule`](assets/js/modules/history.js:5), hiển thị detail bằng [`showDetail()`](assets/js/modules/history.js:165) và quay lại list bằng [`hideDetail()`](assets/js/modules/history.js:309).
- Có routing dạng query params cho báo cáo hiện tại: đọc URL trong [`handleInitialRoute()`](assets/js/app.js:36), cập nhật URL bằng [`history.pushState({ reportId }, '', newUrl)`](assets/js/app.js:316), và xử lý back/forward bằng [`window.addEventListener('popstate', ...)`](assets/js/app.js:27).

Điểm chính: đã có history state cho “report detail” nhưng không liên kết với tab history hay hash routes.

## Options

1) Hash router tối giản (giống admin)
- Hash chỉ chứa route, parsing thủ công, switch view bằng DOM.
- Ưu: ít thay đổi, dễ hiểu, phù hợp pattern admin.
- Nhược: router logic rải rác trong [`assets/js/app.js`](assets/js/app.js:1), khó mở rộng.

2) Router module nhỏ (recommended)
- Tạo module router nhỏ (ví dụ `assets/js/modules/router.js`) để parse hash, điều phối giữa input/history/detail.
- Ưu: rõ ràng, dễ test, giảm coupling.
- Nhược: thêm file/module mới.

3) Kết hợp query params + hash (giữ nguyên query, thêm hash)
- Hash chỉ để chọn tab; detail vẫn dùng query như hiện tại.
- Ưu: ít thay đổi hơn.
- Nhược: không đạt yêu cầu route `/lich-su/:id`.

Khuyến nghị: chọn Option 2 để đạt route `/lich-su/:id` và vẫn gần với admin pattern.

## Proposed Route Structure (hash routes)

- `#/nhap-bao-cao`
  - Hiển thị view nhập báo cáo (list + editor tuỳ state), tương đương tab “input”.
- `#/lich-su`
  - Hiển thị list lịch sử (HistoryModule list), tương đương tab “history”.
- `#/lich-su/:id`
  - Hiển thị detail một báo cáo lịch sử (HistoryModule detail).

Mapping UI:
- `#/nhap-bao-cao` -> gọi [`switchMainTab()`](assets/js/app.js:146) với `input`, ẩn lịch sử detail nếu đang mở.
- `#/lich-su` -> gọi [`switchMainTab()`](assets/js/app.js:146) với `history`, gọi [`HistoryModule.loadHistoryList()`](assets/js/modules/history.js:35).
- `#/lich-su/:id` -> gọi [`switchMainTab()`](assets/js/app.js:146) với `history`, gọi [`showDetail()`](assets/js/modules/history.js:165) với `id`.

Tương thích query params hiện có:
- Giữ logic [`handleInitialRoute()`](assets/js/app.js:36) để mở report bằng query params (line, ma_hang, ngay).
- Khi mở report từ query params, tự điều hướng hash về `#/nhap-bao-cao` (đảm bảo route chính đồng bộ UI).

## Architecture

- Router hash nằm ở front-end, không thay đổi backend.
- Thành phần chính:
  - Parser hash -> route object `{ name, params }`.
  - Dispatcher -> gọi UI actions (switch tab, show list/detail).
  - History sync -> cập nhật `window.location.hash` khi người dùng click tab hoặc mở detail.
- Module đề xuất:
  - `assets/js/modules/router.js` (mới) chứa parse/navigate/handleHashChange.
  - `assets/js/app.js` gọi router trong init, thay vì tự handle tab click.
  - `assets/js/modules/history.js` giữ nguyên API, router chỉ gọi công khai.

## Data Flow

1) App init -> fetch context -> router handle hash ban đầu.
2) Người dùng click tab:
   - UI click handler gọi `router.navigate('#/lich-su')` hoặc `#/nhap-bao-cao`.
   - Router cập nhật hash -> handler -> switch tab.
3) Người dùng click lịch sử row:
   - Row handler gọi `router.navigate('#/lich-su/:id')`.
   - Router gọi [`showDetail()`](assets/js/modules/history.js:165).
4) Người dùng đóng detail:
   - Close handler gọi `router.navigate('#/lich-su')`.
   - Router gọi [`hideDetail()`](assets/js/modules/history.js:309) + list.

## API Contracts

Không thay đổi API. Dùng lại các endpoints hiện có:
- `GET /bao-cao-history` (list)
- `GET /bao-cao-history/:id` (detail)
- `GET /bao-cao/:id` (report input detail)

## Data Model

Router state tối giản (frontend only):
- `route.name`: `nhap-bao-cao` | `lich-su`
- `route.params.id`: number (optional, cho `lich-su/:id`)

Không thay đổi schema DB.

## Security

- Router chỉ thay đổi UI state, không ảnh hưởng auth.
- Vẫn tuân thủ CSRF cho API write (không đổi).
- Hash route không gửi lên server, không thay đổi guards PHP.

## Observability

- Thêm log debug tùy chọn trong router khi bật flag (không bắt buộc).
- Không thêm telemetry mới.

## Rollout/Migration

1) Thêm router module và wiring trong [`assets/js/app.js`](assets/js/app.js:1).
2) Cập nhật tab click handlers để gọi router.navigate.
3) Cập nhật history row onclick để gọi router.navigate.
4) Kiểm tra backward compatibility với query params hiện tại.
5) Không cần migration DB.

## Testing

Checklist:
- Hash route `#/nhap-bao-cao` mở đúng tab input.
- Hash route `#/lich-su` mở đúng tab history và hiển thị list.
- Hash route `#/lich-su/:id` mở detail đúng báo cáo.
- Back/forward browser hoạt động với lịch sử hash.
- Query params (line/ma_hang/ngay) vẫn mở report và đồng bộ về `#/nhap-bao-cao`.
- Không có vòng lặp giữa router và history state.

## Risks & Open Questions

- Ưu tiên route khi vừa có hash vừa có query params? Đề xuất: ưu tiên hash nếu hợp lệ; nếu không có hash thì xử lý query params.
- Khi mở report detail (input) có cần thêm route `#/nhap-bao-cao/:id`? Hiện chưa yêu cầu, nhưng có thể cân nhắc để đồng bộ với `popstate` hiện hữu.
- Cần xác nhận UX: khi không có quyền lịch sử, router xử lý `#/lich-su` ra sao (redirect về `#/nhap-bao-cao` hay ẩn tab).

## Implementation Plan (checklist + estimates)

- [ ] (0.5d, frontend) Thiết kế router module mới `assets/js/modules/router.js` với parse/navigate/handleHashChange.
- [ ] (0.5d, frontend) Wiring router trong [`assets/js/app.js`](assets/js/app.js:1): init, hashchange, click handlers.
- [ ] (0.25d, frontend) Update handlers trong [`assets/js/modules/history.js`](assets/js/modules/history.js:1) để gọi router.navigate khi mở/đóng detail.
- [ ] (0.5d, frontend) Điều chỉnh UI state: đảm bảo switch tab + show/hide list/detail đồng bộ với route.
- [ ] (0.25d, QA) Thực hiện manual test theo checklist.

Hand-off gợi ý:
- Code mode: triển khai router module + wiring.
- Frontend mode: điều chỉnh UI state và CSS nếu cần.
- Backend mode: không cần.
