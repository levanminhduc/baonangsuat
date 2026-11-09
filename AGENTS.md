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
- **Authentication via authV2 (SSO)**: No own login/password check. `bootstrap/app.php` opens the shared session via `../authV2/bootstrap.php` (cookie `HTSSOSESSID`); login/logout live in authV2. Pages use `Auth::requirePageLogin()` (redirects to authV2 login); API keeps JSON 401
- **Session keys prefixed `bns_`**: Only `bns_line` (selected LINE + owner) and `bns_csrf_token` are written to the shared session. Role/employee context is rebuilt from DB on every request (`Auth::boot()`), never stored in session
- **LINE selection reconciled each request**: `LineSelection::reconcile()` drops a stored LINE if it belongs to another user or is no longer allowed; auto-picks when a non-admin has exactly one allowed LINE. `auth/select-line` only accepts allowed LINEs (403 otherwise)
- **Logout is a page navigation** to `Auth::logoutUrl()` (authV2 `logout.php` rejects `fetch`), and logs out all projects
- **Hardcoded base path**: `API_BASE = '/baonangsuat/api'` is hardcoded in JS (`api.js`, `admin-api.js`, `admin/import.js`, `login.js`). PHP redirects are relative (`admin.php`), so PHP itself works under a vhost
- **Pre-generated entries**: Creating a report auto-generates ALL entries with `so_luong = 0`
- **Entry key format**: `{cong_doan_id}_{moc_gio_id}` - composite key for entry lookup
- **`la_cong_doan_tinh_luy_ke` flag**: Only stages with this flag=1 are cumulative-calculated
- **CSRF protection for API**: All POST/PUT/DELETE endpoints require X-CSRF-Token header. Token fetched via GET /api/csrf-token
- **CSRF token persistence**: Token created once per session, reused until session expires
- **Security headers**: pages get the `security-headers` middleware (nosniff, DENY, CSP...); API gets nosniff + DENY from `App\Http\Cors`
- **CORS is loose (known risk)**: API sends `Access-Control-Allow-Origin: *` (what clients effectively got before, via the old `api/.htaccess`); `Allow-Credentials: true` only for localhost origins. See `App\Http\Cors`
- **Permission-based history access**: `can_view_history` permission required for non-admin users to view report history
- **Moc Gio Set fallback**: LINE-specific preset → default preset (is_default=1) for same ca_id
- **`moc_gio_is_fallback` flag**: Context response indicates if using fallback preset
- **Admin bypass**: Admins (and users allowed to report for any LINE) don't need LINE selection for `context` and `bao-cao` (`report` middleware)
- **Transaction wraps bulk updates**: Multiple entry updates wrapped in single transaction
- **Routing Snapshot**: Reports store routing snapshot at creation time (JSON in `routing_snapshot` column) to preserve historical data when routing changes
- **Kết Quả Lũy Kế**: Calculated at submit time and stored in `ket_qua_luy_ke` JSON column. History viewer uses stored result; fallback calculation if null
- **Version Control (Optimistic Locking)**: Reports have `version` column, incremented on each update for conflict detection
- **Excel Import**: PhpSpreadsheet library for importing ma_hang and routing from Excel. Expected format: MH code in C2, công đoạn list from C5 down. Max 10MB
- **Bulk Create Reports**: Admin can create reports in bulk via POST /admin/bao-cao/bulk-create with `skip_existing` option
- **Admin Panel Whitelist**: Only admins with `ma_nv` in `ADMIN_WHITELIST` array (from `C:/xampp/config/admin_whitelist.php`) can access admin.php (`AdminPageController`). If whitelist is empty or file doesn't exist, all admins can access. Admins not in whitelist are redirected to nhap-nang-suat.php but can still use Nhập Năng Suất and Lịch Sử features.

## Key Files

- [`public/index.php`](public/index.php) - Front controller (only PHP entry)
- [`config/routes.php`](config/routes.php) - Route table: pages, API groups, API routes + middleware
- [`app/Http/Router.php`](app/Http/Router.php) - METHOD + path router, group middleware, error messages
- [`app/Support/Database.php`](app/Support/Database.php) - Multi-DB singleton with 3 static methods
- [`app/Auth/Auth.php`](app/Auth/Auth.php) - Authentication + LINE user mapping + Permissions
- [`app/Services/NangSuatService.php`](app/Services/NangSuatService.php) - Core business logic
- [`app/Services/MocGioSetService.php`](app/Services/MocGioSetService.php) - Moc gio preset management
- [`app/Services/HistoryService.php`](app/Services/HistoryService.php) - Report history with pagination
- [`app/Services/ImportService.php`](app/Services/ImportService.php) - Excel import with PhpSpreadsheet
- [`public/assets/js/app.js`](public/assets/js/app.js) - Main frontend app (NangSuatApp class)
- [`public/assets/js/admin.js`](public/assets/js/admin.js) - Admin SPA with lazy-loaded modules
- [`public/assets/js/modules/router.js`](public/assets/js/modules/router.js) - Hash-based router

## Frontend Architecture

- **ES6 Modules**: Frontend uses ES6 modules with dynamic imports
- **Hash-based Routing**: Router class in `public/assets/js/modules/router.js` handles hash-based navigation
- **Admin Lazy Loading**: Admin modules in `public/assets/js/modules/admin/` are lazy-loaded with declared dependencies
- **Auto-save**: Grid inputs auto-save with debounce
- **Realtime Clock**: Web Worker (`public/assets/js/workers/realtime-worker.js`) for server-time-synced clock display
- **CSRF Auto-refresh**: API client auto-fetches new CSRF token when `csrf_error: true` in response

## API Routes

All in `config/routes.php`; URL prefix `/baonangsuat/api/`. Group middleware runs before route matching.

| Group | Controller | Group middleware | Purpose |
|---|---|---|---|
| `csrf-token` | `CsrfTokenController` | - | Get CSRF token |
| `auth/*` | `AuthController` | - (per route `auth`) | select-line, session (login/logout are in authV2) |
| `context` | `ContextController` | - (route `report`) | App context for current line |
| `bao-cao/*` | `BaoCaoController` | `report` | Report CRUD + submit/approve/unlock/complete/reopen |
| `danh-muc/*` | `DanhMucController` | `auth` | Catalog: ca, ma-hang, moc-gio, routing |
| `admin/*` | `AdminController` | `role:admin` | Lines, users, user-lines, ma-hang, cong-doan, routing, moc-gio, bao-cao/bulk-create |
| `moc-gio-sets/*` | `MocGioSetsController` | `role:admin` | Moc gio preset management |
| `bao-cao-history/*` | `BaoCaoHistoryController` | `history` | Report history with pagination |
| `import/*` | `ImportController` | `import` | Excel import preview/confirm |
| `import-history/*` | `ImportHistoryController` | `role:admin` | Import history |
| `user-permissions/*` | `UserPermissionsController` | `role:admin` | User permission management |
| `bieu-do/*` | `BieuDoController` | `auth` | Chart data |

Writes (POST/PUT/DELETE) carry the `csrf` middleware (exception: read-only `POST user-permissions/bulk`).
