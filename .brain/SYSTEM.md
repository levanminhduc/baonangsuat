# System map
<!-- Bản đồ sống của hệ thống. Orchestrator đọc file này trước mọi việc có đụng tới contract.
     /brain-init dựng lần đầu; /go cập nhật khi đóng việc; /sync cập nhật theo các commit không đi qua /go.
     Giữ dạng bảng, mỗi mục một dòng. Lý do nằm ở DECISIONS.md, mẹo nằm ở KNOWLEDGE.md. -->

Status: READY
Last updated: 2026-11-14 by /brain-init
Synced at commit: 9765f3d

## Modules
| Module | Path | Trách nhiệm (1 dòng) | Owner |
|---|---|---|---|
| front-controller | public/index.php, .htaccess, bootstrap/ | Entry PHP duy nhất; nạp autoload + phiên authV2 (`../authV2/bootstrap.php`) | F-014 |
| http | app/Http/ (+Middleware/) | Router METHOD+path, Request/Response, Cors, CsrfToken, middleware auth/line/role/csrf/report/history/import/security-headers | F-014 |
| routes | config/routes.php | Bảng route trang + nhóm API + middleware | F-014 |
| auth | app/Auth/ | Phiên authV2, vai trò/quyền dựng lại mỗi request, whitelist admin, chọn LINE (`LineSelection`) | F-001 |
| pages | app/Controllers/*PageController, views/, components/ | Kiểm quyền/LINE rồi render 6 trang `*.php` | (legacy) |
| bao-cao | BaoCaoController, NangSuatService | Báo cáo năng suất: tạo, nhập entries, header, submit/approve/unlock/complete/reopen | F-002, F-003 |
| context-danh-muc | ContextController, DanhMucController | Ngữ cảnh LINE hiện tại + danh mục ca/mã hàng/mốc giờ/routing | F-002 |
| history | BaoCaoHistoryController, HistoryService | Lịch sử báo cáo có phân trang (quyền `can_view_history`) | F-004 |
| admin | AdminController, AdminService (facade) + Line/User/MaHang/CongDoan/Routing/MocGio services | CRUD danh mục, gán user–LINE, bulk-create báo cáo, đồng bộ LINE từ HR | F-005…F-008, F-011 |
| moc-gio-sets | MocGioSetsController, MocGioSetService | Preset mốc giờ theo ca, gán LINE, fallback về preset mặc định | F-009 |
| import | ImportController, ImportHistoryController, ImportService | Import Excel mã hàng + routing + LĐ/CTNS (PhpSpreadsheet), lịch sử import | F-010 |
| bieu-do | BieuDoController, ChartService | Dữ liệu biểu đồ so sánh năng suất | F-012 |
| permissions | UserPermissionsController | Cấp/thu quyền user (`user_permissions`) — controller truy DB trực tiếp | F-013 |
| support | app/Support/ | `Database` (3 kết nối mysqli singleton), `View::render` | (legacy) |
| fe-app | public/assets/js/app.js + modules/ | NangSuatApp: màn nhập năng suất, grid auto-save, lịch sử, lũy kế | F-002 |
| fe-admin | public/assets/js/admin.js + modules/admin/ | Admin SPA, module lazy-load theo `deps` | F-005 |
| fe-chart | public/assets/js/bieu-do.js | Trang biểu đồ | F-012 |
| tests-tools | tests/php, tests/smoke, tools/ | Unit/structure test, smoke so baseline HTTP, build CSS | (legacy) |

## Shared contracts

### HTTP APIs
Nguồn sự thật: `config/routes.php` (prefix `/baonangsuat/api`). Shape `{success, message?, data?}`; lỗi CSRF kèm `csrf_error: true`. Mọi POST/PUT/DELETE có `csrf` (trừ `POST user-permissions/bulk` chỉ đọc). Cột "Used by" là suy luận theo tên module JS, `?` = chưa kiểm.
| Method Path | Module | Defined by | Used by |
|---|---|---|---|
| GET /api/csrf-token | http | (legacy) | api.js, admin-api.js |
| POST /api/auth/select-line · * /api/auth/session | auth | F-001 | index view ?, app.js ? |
| GET /api/context (`report`) | context-danh-muc | F-002 | app.js |
| GET,POST /api/bao-cao · GET,DELETE /api/bao-cao/{id} · GET …/{id}/routing | bao-cao | F-002 | app.js |
| PUT /api/bao-cao/{id}/entries · …/header | bao-cao | F-002 | app.js (grid auto-save) |
| POST /api/bao-cao/{id}/submit · approve · unlock · complete · reopen | bao-cao | F-003 | app.js |
| GET /api/danh-muc/{ca,ma-hang,moc-gio,routing} | context-danh-muc | F-002 | app.js ? |
| /api/moc-gio-sets[/copy,/unassigned-lines,/resolve,/{id},/{id}/lines] | moc-gio-sets | F-009 | admin/presets.js ? |
| GET /api/bao-cao-history[/{id}] | history | F-004 | modules/history.js ? |
| POST /api/import/{preview,confirm} | import | F-010 | admin/import.js |
| GET /api/import-history[/{id}] | import | F-010 | admin/import-history.js |
| GET /api/bieu-do/{so-sanh,ma-hang-list,lines,ca-list,so-sanh-chi-tiet,so-sanh-matrix} | bieu-do | F-012 | bieu-do.js |
| /api/admin/{lines,users,user-lines,ma-hang,cong-doan,routing,moc-gio}[/{id}] | admin | F-005…F-008 | modules/admin/*.js |
| POST /api/admin/bao-cao/bulk-create | admin | F-011 | admin/bulk-create.js |
| /api/user-permissions[/bulk,/{id},/{id}/{quyen}] | permissions | F-013 | admin/permissions.js ? |
| Trang: / · index.php · nhap-nang-suat.php · admin.php · bieu-do.php · huong-dan.php · no-line.php | pages | D-001 | trình duyệt, authV2 `?redirect=` |

### Data (tables / collections)
DB `nang_suat` trừ khi ghi khác. Schema: `database/nang_suat_schema.sql` (+ `database/migrations/`). Writer/reader lấy từ grep SQL trong `app/`; cột khoá đánh `?` là chưa kiểm.
| Table | Key columns | Defined by | Written by | Read by |
|---|---|---|---|---|
| bao_cao_nang_suat | id, line_id ?, ca_id ?, ma_hang_id ?, version, routing_snapshot, ket_qua_luy_ke | (legacy) | NangSuatService | NangSuat, History, Chart, Import, Line, MaHang, CongDoan services |
| nhap_lieu_nang_suat | bao_cao_id, cong_doan_id, moc_gio_id, so_luong | (legacy) | NangSuatService | NangSuat, History, Chart, Import, CongDoan, MocGio(Set) services |
| nhap_lieu_nang_suat_audit | ? | (legacy) | NangSuatService | NangSuatService |
| line | id, ma_line, ten_line, is_active | (legacy) | LineService (sync từ HR) | Auth + hầu hết service |
| phong_ban_line | phong_ban_ma, line_id | (legacy) | LineService (INSERT IGNORE) | Auth |
| user_line | ma_nv, line_id | (legacy) | UserService | Auth, Line, User services |
| ca_lam | id | (legacy) | — (seed) | Import, History, NangSuat, MocGio(Set), Chart services |
| ma_hang | id | (legacy) | MaHangService, ImportService | nhiều service |
| cong_doan | id | (legacy) | CongDoanService, ImportService | nhiều service |
| ma_hang_cong_doan | ma_hang_id ?, cong_doan_id ?, la_cong_doan_tinh_luy_ke, version | (legacy) | RoutingService, ImportService | nhiều service |
| ma_hang_line_dinh_muc | ? (LĐ/CTNS theo mã hàng × LINE) | migration 012 | ImportService | NangSuatService |
| moc_gio | id, ca_id ?, line_id ? | (legacy) | MocGioService, MocGioSetService | nhiều service |
| moc_gio_set | id, ca_id ?, is_default | migration 003 | MocGioSetService | MocGio, MocGioSet services |
| line_moc_gio_set | line_id, set_id ? | migration 003 | MocGioSetService, LineService | MocGioSet, Line services |
| import_history | id | migration 011 | ImportService | ImportService |
| user_permissions (DB mysqli) | ma_nv ?, quyen | migration 004 | UserPermissionsController | Auth, UserPermissionsController |
| user (DB mysqli) | ma_nv | authV2 | authV2 | Auth |
| nhan_vien, phong_ban (DB quan_ly_nhan_su, chỉ đọc) | ma_nv, phong_ban_ma / ma, ten | hệ thống HR | — | Auth, Import, User, Line services |

### Events / queues / jobs
| Name | Producer | Consumers | Payload ref |
|---|---|---|---|
| (không có queue/cron) Đồng bộ LINE từ HR chạy đồng bộ khi gọi `GET /api/admin/lines` | AdminService::getLineList | LineService::syncFromPhongBan | app/Services/AdminService.php:41 |

### Shared FE components / stores
| Name | Path | Defined by | Used by |
|---|---|---|---|
| api / admin-api (fetch + CSRF auto-refresh, `API_BASE` hard-code) | public/assets/js/modules/api.js, admin-api.js | (legacy) | app.js, admin.js, modules/admin/* |
| Router (hash) | modules/router.js | (legacy) | app.js, admin.js ? |
| grid (auto-save debounce) | modules/grid.js | (legacy) | app.js |
| report-view, history | modules/report-view.js, history.js | (legacy) | app.js (màn nhập + chi tiết lịch sử dùng chung) |
| luy-ke-calculator / luy-ke-config | modules/luy-ke-*.js | (legacy) | app.js, history ? |
| realtime-service + worker | modules/realtime-service.js, workers/realtime-worker.js | (legacy) | các trang có đồng hồ server |
| modal, utils, admin-utils, time-manager, network-monitor | modules/*.js | (legacy) | app.js, admin.js |
| admin state | modules/admin/state.js | (legacy) | modules/admin/* |
| PHP UI components | components/*.php (toast, modal-base, confirm-modal, tabs, status-badge, line-preset-table…) | (legacy) | views/ |

## Architecture rules
- Request: `.htaccess` → `public/index.php` → Router (`config/routes.php`) → middleware → Controller → Service → `Database::get*()`. Chi tiết: `.claude/rules/`.
- Controller gọi service, không truy DB (ngoại lệ hiện có: `UserPermissionsController`). View không truy DB.
- Quyền/đăng nhập/CSRF chỉ qua middleware, không kiểm tay trong controller.
- Không thêm file PHP ở gốc hay `public/` (structure_test khoá). Một class một file dưới `app/` (PSR-4).
- Đổi URL/shape JSON phải sửa JS cùng commit.

## Glossary (thuật ngữ nghiệp vụ)
| Term | Meaning | Not to confuse with |
|---|---|---|
| LINE | Tổ sản xuất, mã XT/XN/DK + 2 số, đồng bộ từ `phong_ban` HR | — |
| Báo cáo (bao_cao) | Báo cáo năng suất của một LINE theo ngày/ca/mã hàng | Lịch sử (báo cáo đã nộp) |
| Mã hàng (MH) | Sản phẩm; có routing riêng | — |
| Công đoạn | Bước gia công trong routing của mã hàng | — |
| Routing | Danh sách công đoạn của mã hàng; snapshot vào báo cáo lúc tạo | Router HTTP / Router hash JS |
| Mốc giờ | Mốc thời gian trong ca để nhập sản lượng | Preset mốc giờ |
| Preset mốc giờ (moc_gio_set) | Bộ mốc giờ theo ca, gán cho LINE; fallback về set `is_default=1` | — |
| Lũy kế | Sản lượng cộng dồn theo mốc, chỉ công đoạn `la_cong_doan_tinh_luy_ke=1` | Tổng mọi công đoạn |
| Kết quả lũy kế | JSON chốt lúc submit (`ket_qua_luy_ke`): chỉ tiêu vs thực tế từng mốc | Tính lại lúc xem (fallback khi null) |
| LĐ / CTNS | Lao động / chỉ tiêu năng suất; import theo LINE, tự điền khi tạo báo cáo | — |
| ma_nv | Mã nhân viên, luôn UPPER(TRIM) | — |
| to_truong / quan_doc / admin | Vai trò ghi/duyệt/quản trị | Quyền trong `user_permissions` |
