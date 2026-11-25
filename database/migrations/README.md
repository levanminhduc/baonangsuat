# Migrations

Không có công cụ chạy migration: áp dụng thủ công, theo thứ tự dưới đây, bằng
`mysql <db> < database/migrations/<file>.sql`. Chỉ cần khi nâng cấp DB cũ.
DB mới: nạp `database/schema.sql`, file đó đã gộp sẵn tất cả migration (trừ bảng của 004).

| File | DB đích | Nội dung |
|---|---|---|
| 001_add_line_id_to_moc_gio | `nang_suat` | `moc_gio.line_id` |
| 002_update_total_minutes_to_510 | `nang_suat` | đổi tổng phút hiệu dụng lên 510 |
| 003_moc_gio_preset | `nang_suat` | `moc_gio_set`, `line_moc_gio_set`, `moc_gio.set_id` |
| 004_add_user_permissions | `mysqli` | `user_permissions` (không nằm trong schema.sql) |
| 005_add_ket_qua_luy_ke | `nang_suat` | `bao_cao_nang_suat.ket_qua_luy_ke` |
| 006_add_routing_snapshot | `nang_suat` | `bao_cao_nang_suat.routing_snapshot` |
| 009_rollback_lenh_san_xuat | `nang_suat` | hoàn tác `lenh_san_xuat` / `lenh_sx_id` |
| 010a_add_completed_status | `nang_suat` | trạng thái `completed`, `hoan_tat_luc/boi` |
| 010b_add_routing_version | `nang_suat` | `ma_hang_cong_doan.version` |
| 011_add_import_history | `nang_suat` | `import_history` |
| 012_add_ma_hang_line_dinh_muc | `nang_suat` | `ma_hang_line_dinh_muc` |
| 013_add_po_subart | `nang_suat` | PO / Subart, `uk_bao_cao` mới, `po_subart_so_luong` |

Ghi chú:

- 007 và 008 chưa từng có trong repo; số được bỏ qua.
- 009 là bản hoàn tác của `lenh_san_xuat` (tính năng đã gỡ), không tạo bảng mới.
- Hai migration số 010 đã đổi thành 010a (completed) rồi 010b (routing version); không phụ thuộc nhau.
- Migration mới đặt số tiếp theo (014...) và cập nhật `database/schema.sql` trong cùng commit.
