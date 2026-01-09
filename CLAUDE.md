# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Quick Reference

**See [AGENTS.md](AGENTS.md) for critical configuration, non-obvious behaviors, and API routes reference.**

## Development Commands

```bash
# Build Tailwind CSS (Windows)
build-css.bat

# Run local server (XAMPP)
# Access via: http://localhost/baonangsuat/

# No npm/composer install required for basic development
# PhpSpreadsheet vendor is pre-bundled for Excel import
```

## Architecture Overview

### Entry Points

- [`index.php`](index.php) - Login page
- [`nhap-nang-suat.php`](nhap-nang-suat.php) - Main data entry page (requires LINE)
- [`admin.php`](admin.php) - Admin dashboard (requires admin role)
- [`no-line.php`](no-line.php) - Fallback for users without LINE assignment
- [`api/index.php`](api/index.php) - Single-file API router (all endpoints)

### Multi-Database Pattern

```
Database::getMysqli()   → mysqli database     → user table, user_permissions
Database::getNangSuat() → nang_suat database  → all productivity data
Database::getNhanSu()   → quan_ly_nhan_su     → employee info (read-only)
```

Config file: `C:/xampp/config/db.php` (outside project root)

### Frontend Module System

```
assets/js/app.js          → Main app (NangSuatApp class)
assets/js/admin.js        → Admin SPA with lazy-loaded modules
assets/js/modules/        → Shared modules (api, router, grid, utils)
assets/js/modules/admin/  → Admin-specific modules (lazy-loaded)
```

Key patterns:

- ES6 modules with dynamic imports for admin tabs
- Hash-based routing (`Router` class in router.js)
- CSRF token auto-fetched and attached to POST/PUT/DELETE requests
- Auto-save with debounce for grid inputs

### Service Layer

```
NangSuatService     → Report CRUD, entry updates, submission workflow
AdminService        → Facade for all admin services
MocGioSetService    → Moc gio preset management with LINE fallback
HistoryService      → Report history with pagination
ImportService       → Excel import with PhpSpreadsheet
```

## Critical Business Logic

### Entry Key Format

Entries are keyed as `{cong_doan_id}_{moc_gio_id}` composite key:

```javascript
const key = congDoanId + "_" + mocGioId;
const entry = baoCao.entries[key];
```

### Pre-Generated Entries

When creating a report, ALL entries are pre-generated with `so_luong = 0`:

```php
private function preGenerateEntries($bao_cao_id, $routing, $mocGioList, $ma_nv)
```

### Lũy Kế (Cumulative) Calculation

Only stages with `la_cong_doan_tinh_luy_ke = 1` are included in cumulative totals:

```php
foreach ($baoCao['routing'] as $cd) {
    if ($cd['la_cong_doan_tinh_luy_ke'] == 1) {
        $congDoanThanhPhamId = $cd['cong_doan_id'];
        break;
    }
}
```

### Mốc Giờ Set Fallback

LINE-specific preset → default preset (is_default=1) for same ca_id:

```php
public function resolveSetForLine($ca_id, $line_id)
// Returns: { set_id, ten_set, is_fallback: true/false }
```

Context response includes `moc_gio_is_fallback` flag.

### Routing Snapshot

Reports store routing snapshot at creation time (JSON) to preserve historical data:

```php
$routingSnapshot = json_encode([
    'version' => 1,
    'created_at' => date('c'),
    'routing' => $routing
], JSON_UNESCAPED_UNICODE);
```

### Kết Quả Lũy Kế

Calculated at submit time and stored in `ket_qua_luy_ke` JSON column:

```json
{
  "version": 1,
  "tong_phut_hieu_dung": 510,
  "ctns": 600,
  "moc_gio": {
    "1": {
      "chi_tieu_luy_ke": 100,
      "luy_ke_thuc_te": 98,
      "trang_thai": "chua_dat"
    }
  }
}
```

History viewer uses stored result; fallback calculation if null.

### Version Control (Optimistic Locking)

Reports have `version` column, incremented on each update:

```php
$newVersion = intval($version) + 1;
// UPDATE ... SET version = ? WHERE id = ?
```

## Security Patterns

### CSRF Protection

- Token generated once per session, reused until expiry
- Required for all POST/PUT/DELETE except login
- Header: `X-CSRF-Token`
- Fetch via: `GET /api/csrf-token`

### Rate Limiting

5 failed login attempts per IP in 15 minutes → HTTP 429

### Password Authentication

```php
if (!password_verify($password, $user['password'])) {
    if (!ALLOW_PLAINTEXT_PASSWORD || $user['password'] !== $password) {
        return ['success' => false, 'message' => 'Mật khẩu không đúng'];
    }
}
```

`ALLOW_PLAINTEXT_PASSWORD` constant controls plaintext fallback.

## Data Normalization Rules

### ma_nv (Employee Code)

Always uppercase before any operation:

```php
$ma_nv = strtoupper(trim($ma_nv));
```

### Username Login

Case-insensitive query:

```php
"SELECT ... FROM user WHERE UPPER(name) = ?"
```

## Frontend State Management

### Admin Module Dependencies

Modules declare dependencies and are lazy-loaded:

```javascript
const modules = {
    'user-lines': {
        path: './modules/admin/user-lines.js',
        deps: ['lines', 'permissions'],  // loaded first
        ...
    }
}
```

### Realtime Clock Service

Web Worker for server-time-synced clock display:

```javascript
realtimeService.init(serverTimestamp); // PHP: time()
realtimeService.startClock("server-clock");
```

## API Response Patterns

All responses follow:

```json
{
  "success": true|false,
  "message": "...",
  "data": {...}
}
```

Error responses include `csrf_error: true` when CSRF fails (triggers token refresh).

## File Upload Constraints

Excel import:

- Max size: 10MB
- Formats: .xlsx, .xls
- Parser: PhpSpreadsheet
- Expected format: MH code in C2, công đoạn list from C5 down
