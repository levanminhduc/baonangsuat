# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Quick Reference

**See [AGENTS.md](AGENTS.md) for critical configuration, non-obvious behaviors, and API routes reference.**

## Development Commands

```bash
composer install                 # vendor/ is gitignored (autoload App\, PhpSpreadsheet, php-cs-fixer)
tools\build-css.bat              # Tailwind CSS → public/assets/tailwind/dist/ (gitignored)
php tests/php/run.php            # unit + structure tests
vendor/bin/php-cs-fixer fix --dry-run
php tools/smoke.php [--cookie=<HTSSOSESSID>]   # compare every URL with tests/smoke baseline

# Local: http://localhost/baonangsuat/   Deploy: docs/deploy-checklist.md
```

Structure rules live in `.claude/rules/` (target-architecture, psr4-autoload, psr12-code-style,
single-entry-point-routing, refactor-verification-gates, tailwind-js).

## Architecture Overview

### Single Entry Point (ADR 0001)

```
.htaccess (root)      → rewrites everything into public/, blocks code dirs + dotfiles (403)
public/index.php      → the only PHP entry; Router + config/routes.php
bootstrap/app.php     → BNS_ROOT, Composer autoload, authV2 shared session
app/                  → PSR-4 App\: Auth, Controllers, Http (Router, Middleware), Services, Support
views/ + partials/    → page HTML; components/ → shared UI components
```

URLs are unchanged: pages stay `/baonangsuat/<page>.php`, API stays `/baonangsuat/api/<group>/...`.

| Page URL | PageController → view |
|---|---|
| `/`, `index.php` | `IndexPageController` → LINE picker (login itself is in authV2) |
| `nhap-nang-suat.php` | `NhapNangSuatPageController` → main data entry (needs LINE, or admin / any-LINE permission) |
| `admin.php` | `AdminPageController` → admin dashboard (admin + whitelist) |
| `bieu-do.php` | `BieuDoPageController` → charts |
| `huong-dan.php` | `HuongDanPageController` → user guide |
| `no-line.php` | `NoLinePageController` → fallback for users without LINE |

API routes: one line each in `config/routes.php` (METHOD + path → `App\Controllers\XxxController::method` + middleware).

### Multi-Database Pattern

```
Database::getMysqli()   → mysqli database     → user table, user_permissions
Database::getNangSuat() → nang_suat database  → all productivity data
Database::getNhanSu()   → quan_ly_nhan_su     → employee info (read-only)
```

Config file: `C:/xampp/config/db.php` (outside project root)

### Frontend Module System

```
public/assets/js/app.js          → Main app (NangSuatApp class)
public/assets/js/admin.js        → Admin SPA with lazy-loaded modules
public/assets/js/modules/        → Shared modules (api, router, grid, utils)
public/assets/js/modules/admin/  → Admin-specific modules (lazy-loaded)
```

Key patterns:

- ES6 modules with dynamic imports for admin tabs
- Hash-based routing (`Router` class in router.js)
- CSRF token auto-fetched and attached to POST/PUT/DELETE requests
- Auto-save with debounce for grid inputs

### Service Layer (`app/Services`)

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
- Required for all POST/PUT/DELETE; stored in session key `bns_csrf_token`
- Header: `X-CSRF-Token`
- Fetch via: `GET /api/csrf-token`

### Authentication (authV2 SSO)

Login, logout, password check and brute-force throttling live in authV2 (`../authV2`). `Auth.php` opens the shared session via `authV2/bootstrap.php`, rebuilds role/employee/LINE context from DB each request, and only writes `bns_line` and `bns_csrf_token` to the session. Pages get the `auth` middleware (`Auth::requirePageLogin()` → authV2 login with `?redirect=`); API returns JSON 401.

## Data Normalization Rules

### ma_nv (Employee Code)

Always uppercase before any operation:

```php
$ma_nv = strtoupper(trim($ma_nv));
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

## Agent skills

### Issue tracker

Issues and specs live as local markdown files under `.scratch/<feature>/`. See `docs/agents/issue-tracker.md`.

### Triage labels

Default five-role vocabulary (`needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`). See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `GLOSSARY.md` + `docs/adr/` at the repo root (created lazily). See `docs/agents/domain.md`.
# --- Repo brain (dán vào cuối CLAUDE.md của repo) ---

## You are the orchestrator of this repo
`.brain/` is this repo's shared memory; you are its only writer. A snapshot of it is injected at the start of each session.

**Route every request before acting.** Pick one lane, say it in one line, then follow it. The full table and rules are in `.claude/commands/do.md`.

| Lane | When | Procedure |
|---|---|---|
| ask | the user wants to understand | answer from the brain, confirm in code with `Explore` |
| quick | ≤ 2 code files, no shared contract, obvious | do it, run the narrowest check |
| fix | something is broken | `/fix` |
| feature | new or changed behaviour, any contract change, refactor, migration | `/feature` → approval → `/go` |
| doc | a document or diagram | `doc-writer` (existing behaviour) or the feature lane stopped at DRAFT (future behaviour) |
| review | judge a diff, branch or PR | `/check` |
| spike | a decision is needed first | `researcher` + `Explore`, then record the decision |
| brain | status, backlog, sync, retro | `/brain` · `/sync` · `/retro` |

Rules that always hold:
- Escalate the lane the moment the work outgrows it. Never shrink a lane to avoid an approval.
- Before creating any API, table, event or shared component, grep `.brain/SYSTEM.md` for an existing one.
- Before touching a module, grep `.brain/KNOWLEDGE.md` and `.brain/DECISIONS.md` for it. Do not contradict an `Active` decision without asking.
- Keep your context for decisions. Searching → `Explore` (Haiku). Long command output → `runner` (Haiku). Outside reading → `researcher`. Never read raw logs yourself.
- Small models only where mistakes are cheap to catch: `chore` needs an Example and a Check. Code that needs judgement stays with `implementer` or you.
- A failing step climbs the ladder in `/go` one rung at a time (`chore` → `implementer` → `implementer-high` → you), always carrying the failure text. Do not jump straight to doing it yourself.
- Never switch `/model` mid-session. Start a new session with the model the spec asks for.
- After any lane, leave the brain better: learned → KNOWLEDGE, noticed-not-done → BACKLOG, decided → DECISIONS, map wrong → SYSTEM. One line each.

## Autonomy  <!-- chỉnh bảng này theo ý bạn -->
| Việc | Trước khi sửa code |
|---|---|
| ask · review · spike · status | không cần duyệt (không sửa code) |
| quick | làm luôn, rồi báo lại |
| fix nhỏ (≤ 2 file, không đụng contract, Risk normal) | làm luôn sau khi nêu nguyên nhân gốc |
| fix lớn · feature · thay đổi khó | DỪNG, chờ `Status: APPROVED` trong spec |
| commit · push · mở PR | chỉ khi người dùng yêu cầu (`/ship <ID>`, hoặc `/ship quick` cho sửa nhỏ) |
| sửa agent, command, hook, settings | chỉ qua `/retro apply`, và luôn hỏi lại |

## Project commands
- Install:   composer install
- Test:      php tests/php/run.php   (+ php tools/smoke.php khi có server local)
- Lint:      vendor/bin/php-cs-fixer fix --dry-run
- Typecheck: (không có)
- Build:     tools\build-css.bat

## Conventions  <!-- ĐIỀN: quy ước đặt tên, xử lý lỗi, cấu trúc thư mục, style test -->
