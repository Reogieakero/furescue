# Phase 90 --- Integration Cleanup

## Purpose

Serialized pass after all page agents finish. Cleanup and
classification only. Do not redesign.

Mirrored from `plans/furescue-admin-refactor-plan/04-integration-cleanup.md`.

## Search for leftover architecture

-   `NAV_GROUPS` (should remain zero)
-   `data-nav` used as the only navigation mechanism
-   unclassified `href="#"`
-   `/admin/animals.php` etc. as in-page links (redirects are OK)
-   `document.body.innerHTML` / happy-path full `#app` overwrite
-   stale relative asset paths (`rescuers.html`, `../js/…`)
-   duplicate component implementations
-   `public/admin/index.php.bak`

## Validate live page structure

Confirm these exist and are the canonical URLs:

-   `public/includes/homepage.php` via `/`
-   `public/auth/{login,signup,logout}.php`
-   `public/report/index.php`
-   `public/reports/index.php`
-   `public/animals/index.php`, `detail.php`
-   `public/adoptions/index.php`
-   `public/listings/index.php`
-   `public/learning/index.php`
-   `public/messages/index.php`
-   `public/notifications/index.php`
-   `public/admin/index.php`
-   `public/admin/animals/index.php`
-   `public/admin/cases/index.php`
-   `public/admin/cases/case-detail.php`
-   `public/admin/reports/index.php`
-   `public/admin/rescuers/index.php`
-   `public/admin/health-records/index.php`
-   `public/admin/health-records/health-record.php`
-   `public/admin/analytics/index.php`
-   `public/admin/notifications/index.php`

## Dead nav

Keep 404 sidebar entries documented. Do not remove them in this audit
unless a later product plan owns those pages. Record HTTP status in
`FINDINGS.md`.

## Dual PHP/JS render

Every admin page JS that assigns `app.innerHTML` must no-op when PHP
already filled `#app` and `window.__PAGE_STATE__` is present. Fix
stragglers in the **owning page files**, not by rewriting shared UI.

## Design system

Confirm leftover raw hex/hsl, non-Lucide icons, and one-off radii
were not introduced. Shared chrome still uses `--primary` / `--paper`
/ `--ink` / `--jungle` / `--radius` / `--shadow-md` / `.input` /
`.toast` / `.logo-mark`. After any CSS edit: `npm run build`.

## File size / split

Confirm page agents did not grow files already over ~300 lines.
New splits land as page-local `partials/` or `js/` modules — not a
second monolith. Record any remaining split candidates.

## Do not

-   change API behavior, UI design, database code, or routing
-   build the four missing admin pages
-   refresh `FEATURES.md` unless findings are already written (optional,
    not a blocker)
-   invent new hex/radius/font stacks instead of tokens
-   leave an edited 300+ line file unsplit

## Findings

| Item | Classification | Evidence |
| --- | --- | --- |
| One admin nav source | working | `admin-nav.php` only |
| No `NAV_GROUPS` | working | grep empty |
| Admin 404 sidebar | stub-documented | listings/applications/elearning/messages curl 404 |
| Dual PHP/JS `#app` | working | happy path uses `__PAGE_STATE__` and skips wipe |
| `index.php.bak` | stub-documented | leftover backup, not a route |
| Dashboard data split | working | `dashboard-data.php`; view still oversized |
| Design tokens | working | no new raw hex introduced |
