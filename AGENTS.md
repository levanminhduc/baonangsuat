# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Critical Configuration

- **Database config is EXTERNAL**: `C:/xampp/config/db.php` - outside project root
- **Multi-database pattern**: 3 separate databases with different purposes:
  - `mysqli` → User authentication + permissions (table `user`, `user_permissions`)
  - `nang_suat` → Productivity data (reports, routing, moc_gio, etc.)
  - `quan_ly_nhan_su` → Employee information (read-only)

## Non-Obvious Behaviors

- **`ma_nv` MUST be UPPERCASE**: All code uses `strtoupper(trim($ma_nv))` before queries
- **Authentication via authV2 (SSO)**: No own login/password check. `classes/Auth.php` and `csrf.php` open the shared session via `../authV2/bootstrap.php` (cookie `HTSSOSESSID`); login/logout live in authV2. Pages use `Auth::requirePageLogin()` (redirects to authV2 login); API keeps JSON 401
- **Session keys prefixed `bns_`**: Only `bns_line` (selected LINE + owner) and `bns_csrf_token` are written to the shared session. Role/employee context is rebuilt from DB on every request (`Auth::boot()`), never stored in session
- **LINE selection reconciled each request**: `LineSelection::reconcile()` drops a stored LINE if it belongs to another user or is no longer allowed; auto-picks when a non-admin has exactly one allowed LINE. `auth/select-line` only accepts allowed LINEs (403 otherwise)
- **Logout is a page navigation** to `Auth::logoutUrl()` (authV2 `logout.php` rejects `fetch`), and logs out all projects
- **Hardcoded base path**: `/baonangsuat/` is hardcoded in PHP redirects and JS fetch calls
- **Pre-generated entries**: Creating a report auto-generates ALL entries with `so_luong = 0`
- **Entry key format**: `{cong_doan_id}_{moc_gio_id}` - composite key for entry lookup
- **`la_cong_doan_tinh_luy_ke` flag**: Only stages with this flag=1 are cumulative-calculated
- **CSRF protection for API**: All POST/PUT/DELETE endpoints (except login) require X-CSRF-Token header. Token fetched via GET /api/csrf-token
- **CSRF token persistence**: Token created once per session, reused until session expires
- **Security headers**: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` applied globally
- **CORS restricted**: API only allows localhost origins (127.0.0.1, localhost with any port)
- **Permission-based history access**: `can_view_history` permission required for non-admin users to view report history
- **Moc Gio Set fallback**: LINE-specific preset → default preset (is_default=1) for same ca_id
- **`moc_gio_is_fallback` flag**: Context response indicates if using fallback preset
- **Admin bypass**: Admins don't need LINE selection for `handleContext()` and `handleBaoCao()`
- **Transaction wraps bulk updates**: Multiple entry updates wrapped in single transaction

## Key Files

- [`config/Database.php`](config/Database.php) - Multi-DB singleton with 3 static methods
- [`classes/Auth.php`](classes/Auth.php) - Authentication + LINE user mapping + Permissions
- [`api/index.php`](api/index.php) - Single-file API router
- [`classes/NangSuatService.php`](classes/NangSuatService.php) - Core business logic
- [`classes/services/MocGioSetService.php`](classes/services/MocGioSetService.php) - Moc gio preset management
- [`classes/services/HistoryService.php`](classes/services/HistoryService.php) - Report history with pagination

## API Routes

| Route | Handler | Purpose |
|-------|---------|---------|
| `csrf-token` | inline | Get CSRF token |
| `auth/*` | `handleAuth()` | select-line, session (login/logout are in authV2) |
| `context` | `handleContext()` | Get app context for current line |
| `bao-cao/*` | `handleBaoCao()` | Report CRUD + submit/approve/unlock |
| `danh-muc/*` | `handleDanhMuc()` | Catalog: ca, ma-hang, moc-gio, routing |
| `admin/*` | `handleAdmin()` | Admin CRUD for lines, users, ma-hang, cong-doan, routing, moc-gio |
| `moc-gio-sets/*` | `handleMocGioSets()` | Moc gio preset management |
| `bao-cao-history/*` | `handleBaoCaoHistory()` | Report history with pagination |
| `user-permissions/*` | `handleUserPermissions()` | User permission management |
