# Modal System Design

## Overview
- Goal: unify modal patterns, add accessibility, consistent z-index, and reusable APIs.
- Scope includes component + utilities; no DB changes or runtime code changes in this doc.

## Context
- Current confirm modal: [`includes/components/confirm-modal.php`](includes/components/confirm-modal.php:1)
- Admin inline modals: [`admin.php`](admin.php:271)
- Nhap nang suat modal: [`nhap-nang-suat.php`](nhap-nang-suat.php:161)
- Loading overlay: [`includes/components/loading-overlay.php`](includes/components/loading-overlay.php)
- Modal CSS overrides: [`assets/css/style.css`](assets/css/style.css:496)

## Requirements (confirmed)
- Backdrop click closes by default; per-modal opt-out.
- Focus trap fully cycles; restore focus on close.
- Escape closes except loading variant.
- No nested modals.
- Size scale: sm 24rem, md 32rem, lg 48rem, xl 64rem.
- Dark mode uses existing tokens.
- New animation set allowed.

## Options
### Option A: Data-attribute wiring only
- Pros: minimal markup changes, simple to migrate.
- Cons: harder to enforce accessibility, weak API for loading state and focus control.

### Option B: Base component + controller (recommended)
- Pros: consistent structure, accessibility centralized, supports variants and lifecycle events.
- Cons: requires standardized markup and small client module.

### Option C: Native dialog element
- Pros: built-in focus handling in modern browsers.
- Cons: inconsistent styling and polyfill needs; harder to integrate with Tailwind patterns.

Recommendation: Option B.

## Architecture
Components and utilities:
- Base component: [`includes/components/modal-base.php`](includes/components/modal-base.php)
- Variant wrappers: confirm, form, loading (thin includes that pass params).
- Utility module: [`assets/js/modules/modal.js`](assets/js/modules/modal.js)

Component architecture diagram:
```
Page
  └─ base modal component (overlay + panel + slots)
       ├─ header slot (title + close)
       ├─ body slot
       └─ footer slot (actions)
Client modal controller
  ├─ open/close + focus trap
  ├─ esc/backdrop handlers
  ├─ scroll lock
  └─ events
```

## Data flow
1) Server renders base modal markup with data attributes and ARIA wiring.
2) Controller scans DOM and registers modal instances.
3) User action opens modal, enabling overlay, focus trap, and scroll lock.
4) Close action restores focus, removes scroll lock, and emits lifecycle events.

## API contracts
### Component API (base modal)
Params (all optional unless noted):
- [`id`](docs/modal-system-design.md:67) (required): unique modal id string.
- [`title`](docs/modal-system-design.md:68): text for header title.
- [`describedById`](docs/modal-system-design.md:69): id of description element for [`aria-describedby`](docs/modal-system-design.md:152).
- [`labelledById`](docs/modal-system-design.md:70): id of heading for [`aria-labelledby`](docs/modal-system-design.md:152).
- [`size`](docs/modal-system-design.md:71): sm | md | lg | xl (default md).
- [`variant`](docs/modal-system-design.md:72): confirm | form | loading | custom.
- [`closeOnBackdrop`](docs/modal-system-design.md:73): boolean (default true).
- [`closeOnEsc`](docs/modal-system-design.md:74): boolean (default true except loading).
- [`initialFocus`](docs/modal-system-design.md:75): selector or element id to focus on open.
- [`showClose`](docs/modal-system-design.md:76): boolean (default true except loading).
- [`footer`](docs/modal-system-design.md:77): slot content (actions).
- [`body`](docs/modal-system-design.md:78): slot content.

Slots:
- header slot (optional override).
- body slot.
- footer slot (optional).

### JS module API
Functions:
- [`modal.init(root)`](docs/modal-system-design.md:87) -> void
- [`modal.open(id, options)`](docs/modal-system-design.md:88) -> void
- [`modal.close(id, reason)`](docs/modal-system-design.md:89) -> void
- [`modal.closeAll(reason)`](docs/modal-system-design.md:90) -> void
- [`modal.setLoading(id, isLoading)`](docs/modal-system-design.md:91) -> void
- [`modal.isOpen(id)`](docs/modal-system-design.md:92) -> boolean
- [`modal.on(eventName, handler)`](docs/modal-system-design.md:93) -> unsubscribe

Options for [`modal.open(id, options)`](docs/modal-system-design.md:88):
- [`focusTarget`](docs/modal-system-design.md:96): selector or element id.
- [`closeOnBackdrop`](docs/modal-system-design.md:97): boolean override.
- [`closeOnEsc`](docs/modal-system-design.md:98): boolean override.
- [`returnFocusTo`](docs/modal-system-design.md:99): element or selector.

Events:
- [`modal:before-open`](docs/modal-system-design.md:102)
- [`modal:after-open`](docs/modal-system-design.md:103)
- [`modal:before-close`](docs/modal-system-design.md:104)
- [`modal:after-close`](docs/modal-system-design.md:105)
- [`modal:esc`](docs/modal-system-design.md:106)
- [`modal:backdrop`](docs/modal-system-design.md:107)

## Data model
- No database changes. State is in-memory (open modal id, previous focus element).

## Security
- Preserve existing CSRF and auth flows; modal system is UI-only.
- Ensure content is still server-escaped; no new injection surfaces.

## Observability
- Emit lifecycle events for analytics or debugging hooks.
- Optional console logs in dev mode only (implementation decision).

## z-index scale
- Base content: 0-10
- Sticky headers and tables: 20-40
- Backdrop overlay: 50
- Modal panel: 60
- Toasts: 70
- Loading overlay: 80
Update CSS override in [`assets/css/style.css`](assets/css/style.css:496) to avoid forcing z-index 1000.

## Tailwind extensions
- Update [`tailwind.config.js`](tailwind.config.js) with z-index scale 50/60/70/80.
- Add keyframes for modal enter and exit.
- Add animation utilities for enter/exit.
- Add width utilities for modal sizes (24rem/32rem/48rem/64rem).

## Rollout / Migration
1) Add base component and controller module (new files).
2) Update confirm modal to use base component.
3) Migrate admin modals group-by-group (lines, user-lines, permissions, ma-hang, cong-doan, routing, presets).
4) Migrate nhap-nang-suat create modal.
5) Remove legacy CSS overrides for .modal and .modal-content where safe.
6) QA for focus trap, ESC, backdrop, and scroll lock.

## Testing
- Keyboard navigation: Tab/Shift+Tab loops inside modal.
- ESC: closes non-loading; does nothing for loading.
- Backdrop click: closes by default; respects opt-out.
- Focus return: back to trigger element.
- z-index stacking with status bar and toast.

## Accessibility checklist
- Role and modal: [`role=dialog`](docs/modal-system-design.md:151) and [`aria-modal=true`](docs/modal-system-design.md:151).
- Name and description: [`aria-labelledby`](docs/modal-system-design.md:152) and [`aria-describedby`](docs/modal-system-design.md:152).
- Focus trap cycles within modal.
- Initial focus and focus return are deterministic.
- Close button has accessible label.

## Risks & open questions
- Ensure no nested modals; enforce by controller.
- Confirm existing dark mode tokens are sufficient for overlay and text.
- Confirm any modal inside scrollable containers does not break focus trap.

## Implementation plan (estimates, handoff)
- [ ] Design file and checklist (this doc) - 0.5d - Architect
- [ ] Implement base component - 1d - Code
- [ ] Implement modal utilities - 1d - Code
- [ ] Tailwind config extensions - 0.5d - Code
- [ ] Migrate admin modals - 1.5d - Code
- [ ] Migrate nhap-nang-suat modal - 0.5d - Code
- [ ] Remove legacy CSS overrides - 0.5d - Code
- [ ] QA checklist + fixes - 1d - Code
