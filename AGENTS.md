# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Critical Configuration

- **Database config is EXTERNAL**: `C:/xampp/config/db.php` - outside project root
- **Multi-database pattern**: 3 separate databases with different purposes:
  - `mysqli` → User authentication only
  - `nang_suat` → Productivity data
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

## Key Files

- [`config/Database.php`](config/Database.php) - Multi-DB singleton with 3 connections
- [`classes/Auth.php`](classes/Auth.php) - Authentication + LINE user mapping
- [`api/index.php`](api/index.php) - Single-file API router
- [`classes/NangSuatService.php`](classes/NangSuatService.php) - Core business logic
