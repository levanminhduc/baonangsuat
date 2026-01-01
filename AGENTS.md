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
- **Configurable plaintext password fallback**: `ALLOW_PLAINTEXT_PASSWORD` constant (default: `true`) controls whether plaintext password comparison is allowed after bcrypt fails
- **Session regeneration on login**: Prevents session fixation attacks
- **Hardcoded base path**: `/baonangsuat/` is hardcoded in PHP redirects and JS fetch calls
- **Pre-generated entries**: Creating a report auto-generates ALL entries with `so_luong = 0`
- **`la_cong_doan_tinh_luy_ke` flag**: Only stages with this flag=1 are cumulative-calculated
- **CSRF protection for API**: All POST/PUT/DELETE endpoints (except login) require X-CSRF-Token header. Token fetched via GET /api/csrf-token
- **CORS restricted**: API only allows localhost origins (127.0.0.1, localhost with any port)
- **Remember username only**: Login remembers username in localStorage, never password
- **Login rate limiting**: 5 failed attempts per IP within 15 minutes triggers lockout. Returns HTTP 429. Counter resets on successful login.
- **Permission-based history access**: `can_view_history` permission required for non-admin users to view report history
- **Moc Gio Set fallback**: LINE-specific preset → default preset (is_default=1) for same ca_id

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
| `auth/*` | `handleAuth()` | Login, logout, select-line, session |
| `context` | `handleContext()` | Get app context for current line |
| `bao-cao/*` | `handleBaoCao()` | Report CRUD + submit/approve/unlock |
| `danh-muc/*` | `handleDanhMuc()` | Catalog: ca, ma-hang, moc-gio, routing |
| `admin/*` | `handleAdmin()` | Admin CRUD for lines, users, ma-hang, cong-doan, routing, moc-gio |
| `moc-gio-sets/*` | `handleMocGioSets()` | Moc gio preset management |
| `bao-cao-history/*` | `handleBaoCaoHistory()` | Report history with pagination |
| `user-permissions/*` | `handleUserPermissions()` | User permission management |
