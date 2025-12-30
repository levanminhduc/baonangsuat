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
- **Plaintext password fallback**: Auth accepts BOTH bcrypt AND plaintext passwords
- **Hardcoded base path**: `/baonangsuat/` is hardcoded in PHP redirects and JS fetch calls
- **Pre-generated entries**: Creating a report auto-generates ALL entries with `so_luong = 0`
- **`la_cong_doan_tinh_luy_ke` flag**: Only stages with this flag=1 are cumulative-calculated
- **CSRF for login only**: API endpoints use session-based auth, no CSRF tokens

## Key Files

- [`config/Database.php`](config/Database.php) - Multi-DB singleton with 3 connections
- [`classes/Auth.php`](classes/Auth.php) - Authentication + LINE user mapping
- [`api/index.php`](api/index.php) - Single-file API router
- [`classes/NangSuatService.php`](classes/NangSuatService.php) - Core business logic
