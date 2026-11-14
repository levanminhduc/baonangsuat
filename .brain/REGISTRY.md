# Registry — mọi việc đã và đang làm
<!-- Mỗi việc một dòng. Orchestrator grep file này để tìm chồng chéo.
     ID:      F-NNN tính năng · B-NNN bug · M-NNN thay đổi thủ công do /sync ghi nhận
     Status:  DRAFT → APPROVED → IN_PROGRESS → DONE   |   STOPPED · FAIL · SUPERSEDED(by X) · DOC (spec chỉ giữ làm tài liệu)
              Người dùng duyệt bằng cách sửa dòng Status trong file spec; trạng thái trong file spec là trạng thái đúng.
     File:    đường dẫn tương đối trong .brain/, hoặc "(legacy)" / commit sha với M-
     Ví dụ:
     | F-001 | Đăng nhập | DONE | auth | +POST /auth/login, +users | features/F-001-login.md |
     | B-001 | Sai tổng tiền khi có giảm giá | DONE | order | — | bugs/B-001-wrong-total.md |
-->

| ID | Title | Status | Modules | Contracts added (+) / changed (~) | File |
|---|---|---|---|---|---|
| F-001 | Đăng nhập SSO authV2 + chọn LINE | DONE | auth | +/api/auth/*, +session bns_line | (legacy) |
| F-002 | Nhập năng suất (tạo báo cáo, grid auto-save, lũy kế) | DONE | bao-cao, context-danh-muc, fe-app | +/api/bao-cao, +/api/context, +/api/danh-muc | (legacy) |
| F-003 | Quy trình báo cáo submit/approve/unlock/complete/reopen | DONE | bao-cao | +/api/bao-cao/{id}/{submit,approve,unlock,complete,reopen} | (legacy) |
| F-004 | Lịch sử báo cáo (phân trang, chi tiết dùng giao diện màn nhập) | DONE | history, fe-app | +/api/bao-cao-history | (legacy) |
| F-005 | Admin: quản lý LINE, tự đồng bộ LINE từ HR | DONE | admin, fe-admin | +/api/admin/lines, +phong_ban_line | (legacy) |
| F-006 | Admin: gán user–LINE | DONE | admin | +/api/admin/users, +/api/admin/user-lines | (legacy) |
| F-007 | Admin: mã hàng, công đoạn, routing | DONE | admin | +/api/admin/{ma-hang,cong-doan,routing} | (legacy) |
| F-008 | Admin: mốc giờ | DONE | admin | +/api/admin/moc-gio | (legacy) |
| F-009 | Preset mốc giờ theo LINE + fallback | DONE | moc-gio-sets | +/api/moc-gio-sets, +moc_gio_set, +line_moc_gio_set | (legacy) |
| F-010 | Import Excel mã hàng/routing/LĐ/CTNS + lịch sử import | DONE | import | +/api/import, +/api/import-history, +import_history, +ma_hang_line_dinh_muc | (legacy) |
| F-011 | Admin: tạo báo cáo hàng loạt | DONE | admin | +/api/admin/bao-cao/bulk-create | (legacy) |
| F-012 | Biểu đồ so sánh năng suất | DONE | bieu-do, fe-chart | +/api/bieu-do/* | (legacy) |
| F-013 | Phân quyền user (user_permissions) | DONE | permissions | +/api/user-permissions, +user_permissions | (legacy) |
| F-014 | Một entry point + Router path-based (ADR 0001) | DONE | front-controller, http, routes | ~mọi URL giữ nguyên | (legacy) |
