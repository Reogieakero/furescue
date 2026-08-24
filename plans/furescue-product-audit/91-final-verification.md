# Phase 91 --- Final Verification

Mirrored from `plans/furescue-admin-refactor-plan/06-final-verification.md`,
extended to the whole product.

## Routes

Resident / public:

-   `/`
-   `/auth/login.php`
-   `/auth/signup.php`
-   `/auth/logout.php` (redirect)
-   `/report/`
-   `/reports/`
-   `/animals/`
-   `/animals/detail.php` (with a real id when possible)
-   `/adoptions/`
-   `/listings/`
-   `/learning/`
-   `/messages/`
-   `/notifications/`

Admin:

-   `/admin/`
-   `/admin/animals/`
-   `/admin/cases/`
-   `/admin/cases/case-detail.php`
-   `/admin/reports/`
-   `/admin/rescuers/`
-   `/admin/health-records/`
-   `/admin/health-records/health-record.php`
-   `/admin/analytics/`
-   `/admin/notifications/`

Known 404 (document only):

-   `/admin/listings/`
-   `/admin/applications/`
-   `/admin/elearning/`
-   `/admin/messages/`

## Viewports

-   375px
-   768px
-   1440px

## For each live route

Check:

-   direct navigation
-   hard refresh
-   trailing slash behavior
-   authorization guard
-   page HTML is present without JS rendering the whole page
-   sidebar / landing nav rendering
-   active navigation
-   all sidebar links
-   profile/account links
-   page-specific links
-   cards
-   dialogs/modals
-   drawers
-   dropdowns
-   forms
-   filters
-   pagination
-   loading states
-   empty states
-   error states
-   API requests
-   browser console
-   network errors
-   asset loading
-   design-system: tokens (not raw hex/hsl), Lucide only, reuse
    `.input` / `.toast` / `.loader-overlay` / `.logo-mark` /
    `.badge-icon` / shells
-   file size: no new monoliths; edited 300+ line files were split
-   375 / 768 / 1440 (zero horizontal overflow)

If browser tools are unavailable, use curl + code inspection and record
the gap in `FINDINGS.md`.

## Build

If CSS was changed:

``` bat
npm run build
```

Also run any existing project test/lint commands required by
`AGENTS.md` when PHP/JS contracts were touched. Do not treat a full
PHPUnit run as a blocker for UI-only href fixes.

## Architecture checks

Confirm:

-   one admin navigation source (`admin-nav.php`)
-   one resident navigation source (`resident-shell.php`)
-   no duplicate `NAV_GROUPS`
-   leftover `href="#"` classified
-   no unnecessary full-page DOM reconstruction on the happy path
-   shared components are not duplicated
-   no API envelope changes
-   no router changes
-   no unrelated backend changes
-   live tokens (`--primary`, `--paper`, `--ink`, `--jungle`,
    `--radius`, `--font-sans`) still drive chrome
-   leftover files over ~300 lines are listed as split candidates

## Matrix (filled during execution)

| Route | 375 | 768 | 1440 | Guard | Console/assets | Method |
| --- | --- | --- | --- | --- | --- | --- |
| `/` | curl+code | curl+code | curl+code | public 200 | not in-browser | curl + markup |
| `/auth/login.php` | same | same | same | 200 | not in-browser | curl |
| `/auth/signup.php` | same | same | same | 200 | not in-browser | curl |
| `/report/` | same | same | same | 302 anon / 200 resident | not in-browser | curl |
| `/reports/` | same | same | same | 302 / 200 | not in-browser | curl |
| `/animals/` | same | same | same | 302 / 200 | not in-browser | curl |
| `/animals/detail.php` | — | — | — | not hit without id | not in-browser | code only |
| `/adoptions/` | same | same | same | 302 / 200 | not in-browser | curl |
| `/listings/` | same | same | same | 302 / 200 | not in-browser | curl |
| `/learning/` | pass (CDP) | pass (CDP) | pass (CDP) | 302 anon / 200 + page JWT | 0 overflow; open/complete/back | Chrome CDP `innerWidth` |
| `/messages/` | pass (CDP) | pass (CDP) | pass (CDP) | 302 anon / 200 + page JWT | 0 overflow; threads 403 error empty | Chrome CDP `innerWidth` |
| `/notifications/` | pass (CDP) | pass (CDP) | pass (CDP) | 302 anon / 200 + page JWT | 0 overflow; mark one/all; click-through | Chrome CDP `innerWidth` |
| `/admin/` | same | same | same | 302 / 200 admin | not in-browser | curl |
| `/admin/reports/` | same | same | same | 200 admin | not in-browser | curl |
| `/admin/cases/` | same | same | same | 200 admin | not in-browser | curl |
| `/admin/cases/case-detail.php` | — | — | — | not hit without id | not in-browser | code only |
| `/admin/rescuers/` | same | same | same | 200 admin | not in-browser | curl |
| `/admin/animals/` | same | same | same | 200 admin | not in-browser | curl |
| `/admin/health-records/` | same | same | same | 200 admin | not in-browser | curl |
| `/admin/health-records/health-record.php` | — | — | — | redirect without id | not in-browser | code |
| `/admin/analytics/` | same | same | same | 200 admin | not in-browser | curl |
| `/admin/notifications/` | same | same | same | 200 admin | not in-browser | curl |

**375 / 768 / 1440:** most routes still curl/code only. Agent 13 clicked
`/learning/`, `/messages/`, and `/notifications/` in Chrome CDP with
`innerWidth` 375 / 768 / 1440. Remaining device-toolbar pass is open
for other page agents / 91.
