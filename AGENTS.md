# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Critical Configuration

- **Database config is EXTERNAL**: `C:/xampp/config/db.php` - outside project root
- **Admin whitelist config is EXTERNAL**: `C:/xampp/config/admin_whitelist.php` - controls which admin users can access admin panel
- **Multi-database pattern**: 3 separate databases with different purposes:
  - `mysqli` → User authentication + permissions (table `user`, `user_permissions`)
  - `nang_suat` → Productivity data (reports, routing, moc_gio, etc.)
  - `quan_ly_nhan_su` → Employee information (read-only)

## Non-Obvious Behaviors

- **`ma_nv` MUST be UPPERCASE**: All code uses `strtoupper(trim($ma_nv))` before queries
- **Username case-insensitive login**: Query uses `UPPER(name) = ?` for login
- **Configurable plaintext password fallback**: `ALLOW_PLAINTEXT_PASSWORD` constant (default: `true`) controls whether plaintext password comparison is allowed after bcrypt fails
- **Session regeneration on login**: Prevents session fixation attacks
- **Hardcoded base path**: `/baonangsuat/` is hardcoded in PHP redirects and JS fetch calls
- **Pre-generated entries**: Creating a report auto-generates ALL entries with `so_luong = 0`
- **Entry key format**: `{cong_doan_id}_{moc_gio_id}` - composite key for entry lookup
- **`la_cong_doan_tinh_luy_ke` flag**: Only stages with this flag=1 are cumulative-calculated
- **CSRF protection for API**: All POST/PUT/DELETE endpoints (except login) require X-CSRF-Token header. Token fetched via GET /api/csrf-token
- **CSRF token persistence**: Token created once per session, reused until session expires
- **Security headers**: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` applied globally
- **CORS restricted**: API only allows localhost origins (127.0.0.1, localhost with any port)
- **Remember username only**: Login remembers username in localStorage, never password
- **Login rate limiting**: 5 failed attempts per IP within 15 minutes triggers lockout. Returns HTTP 429. Counter resets on successful login.
- **Permission-based history access**: `can_view_history` permission required for non-admin users to view report history
- **Moc Gio Set fallback**: LINE-specific preset → default preset (is_default=1) for same ca_id
- **`moc_gio_is_fallback` flag**: Context response indicates if using fallback preset
- **Admin bypass**: Admins don't need LINE selection for `handleContext()` and `handleBaoCao()`
- **Transaction wraps bulk updates**: Multiple entry updates wrapped in single transaction
- **Routing Snapshot**: Reports store routing snapshot at creation time (JSON in `routing_snapshot` column) to preserve historical data when routing changes
- **Kết Quả Lũy Kế**: Calculated at submit time and stored in `ket_qua_luy_ke` JSON column. History viewer uses stored result; fallback calculation if null
- **Version Control (Optimistic Locking)**: Reports have `version` column, incremented on each update for conflict detection
- **Excel Import**: PhpSpreadsheet library for importing ma_hang and routing from Excel. Expected format: MH code in C2, công đoạn list from C5 down. Max 10MB
- **Bulk Create Reports**: Admin can create reports in bulk via POST /admin/bao-cao/bulk-create with `skip_existing` option
- **Admin Panel Whitelist**: Only admins with `ma_nv` in `ADMIN_WHITELIST` array (from `C:/xampp/config/admin_whitelist.php`) can access admin.php. If whitelist is empty or file doesn't exist, all admins can access. Admins not in whitelist are redirected to nhap-nang-suat.php but can still use Nhập Năng Suất and Lịch Sử features.

## Key Files

- [`config/Database.php`](config/Database.php) - Multi-DB singleton with 3 static methods
- [`classes/Auth.php`](classes/Auth.php) - Authentication + LINE user mapping + Permissions
- [`api/index.php`](api/index.php) - Single-file API router
- [`classes/NangSuatService.php`](classes/NangSuatService.php) - Core business logic
- [`classes/services/MocGioSetService.php`](classes/services/MocGioSetService.php) - Moc gio preset management
- [`classes/services/HistoryService.php`](classes/services/HistoryService.php) - Report history with pagination
- [`classes/services/ImportService.php`](classes/services/ImportService.php) - Excel import with PhpSpreadsheet
- [`assets/js/app.js`](assets/js/app.js) - Main frontend app (NangSuatApp class)
- [`assets/js/admin.js`](assets/js/admin.js) - Admin SPA with lazy-loaded modules
- [`assets/js/modules/router.js`](assets/js/modules/router.js) - Hash-based router

## Frontend Architecture

- **ES6 Modules**: Frontend uses ES6 modules with dynamic imports
- **Hash-based Routing**: Router class in `assets/js/modules/router.js` handles hash-based navigation
- **Admin Lazy Loading**: Admin modules in `assets/js/modules/admin/` are lazy-loaded with declared dependencies
- **Auto-save**: Grid inputs auto-save with debounce
- **Realtime Clock**: Web Worker (`assets/js/workers/realtime-worker.js`) for server-time-synced clock display
- **CSRF Auto-refresh**: API client auto-fetches new CSRF token when `csrf_error: true` in response

## API Routes

| Route                       | Handler                   | Purpose                                                           |
| --------------------------- | ------------------------- | ----------------------------------------------------------------- |
| `csrf-token`                | inline                    | Get CSRF token                                                    |
| `auth/*`                    | `handleAuth()`            | Login, logout, select-line, session                               |
| `context`                   | `handleContext()`         | Get app context for current line                                  |
| `bao-cao/*`                 | `handleBaoCao()`          | Report CRUD + submit/approve/unlock                               |
| `danh-muc/*`                | `handleDanhMuc()`         | Catalog: ca, ma-hang, moc-gio, routing                            |
| `admin/*`                   | `handleAdmin()`           | Admin CRUD for lines, users, ma-hang, cong-doan, routing, moc-gio |
| `moc-gio-sets/*`            | `handleMocGioSets()`      | Moc gio preset management                                         |
| `bao-cao-history/*`         | `handleBaoCaoHistory()`   | Report history with pagination                                    |
| `user-permissions/*`        | `handleUserPermissions()` | User permission management                                        |
| `admin/bao-cao/bulk-create` | `handleAdmin()`           | Bulk create reports                                               |
| `admin/import/*`            | `handleAdmin()`           | Excel import preview/confirm                                      |
