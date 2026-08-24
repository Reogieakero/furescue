# Product audit findings (rollup)

Filled during execution. Agent files remain the detailed source; this
file is the cross-cut summary.

Hard standards: design-system tokens (`docs/study/DESIGN_SYSTEM.md` +
`public/css/input.css`) and no file bottlenecks (~300-line split).

## Verification method

Browser MCP/Playwright tools: **not available** (`GetDynamicTools`
catalog in this session: `cursor` GenerateImage + GitLens only).

Substitutes:

-   **Phase 02:** local Chrome CDP (`chrome.exe` headless, port 9335) —
    real mouse/keyboard on landing, `/admin/`, `/animals/` at 375 / 768
    / 1440. This is a recorded MCP-tools gap, **not** curl-only success.
-   curl / cookie login (`admin@furescue.local`, `juan@furescue.local`)
-   static inspection of handlers vs markup
-   `php -l` on edited PHP

Quirk: after navigate to `/admin/`, CDP `innerWidth` sometimes stayed
~1193; hamburger still `display:block` (&lt;1024) and overlay/Escape
were clicked. `/` and `/animals/` honored 375 / 768 / 1440 exactly.

Page-agent click/Leaflet/overflow work remains for agents 10–26 / 91.

## Phase 01

See `01-standards-scan.md` (refreshed 2026-08-24 against live
`public/`). No production edits this phase.

-   `NAV_GROUPS`: still **zero** matches under `public/`. Nav data is
    `$adminNav` / `$residentNavGroups`.
-   Four admin stub URLs still have **no folders**: `/admin/listings/`,
    `/admin/applications/`, `/admin/elearning/`, `/admin/messages/`.
-   Admin pages live at folder URLs. Legacy `public/admin/*.php` (except
    dashboard `index.php`) 302 to those folders.
-   `href="#"`: hero CTAs and most dashboard “View all” links are now
    real URLs. Remaining: Google `#google`, queue
    `data-action`, analytics `data-export`, footer marketing stubs,
    dashboard “View all applications” + “Read module”. Resident logout
    is a real URL as of Phase 02.
-   All eight known `#app` `innerHTML` entry files still exist; happy
    path skips overwrite when `__PAGE_STATE__` and `#app` has children.
-   **P1-6 (new):** `public/admin/rescuers/js/rescuers.js` imports
    `./layout/app-shell.js` (missing after folder move).
-   UI modules: 19 files under `public/js/components/ui/` (see scan).
-   `/listings/` is a live resident route, not in resident sidebar;
    distinct from admin listings 404.
-   **P1-3 remaining:** landing still loads DM Sans
    (`homepage.php` `$fontsHref` + `landing/css/partials/00_tokens.css`).
    Do not paste study `#3D7432` / DM Sans into other pages.
-   Files over ~300 lines: see table in `01-standards-scan.md`.
    `homepage.php` dropped to 221 (off the list). New over-line:
    `analytics.js` 328, `report.js` 317, rescuers `detail.js` 304,
    health-records `state.js` 302.

## Phase 02

Chrome CDP clicks (not screenshots-only). Labels:
`working` / `broken-fixed` / `stub-documented`.

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| Admin hamburger / overlay / Escape | `/admin/` | working | `#menu-toggle` opened `is-open` + overlay; overlay click and Escape closed (375/768). Hidden at 1440 (≥1024). |
| Admin profile dropdown | `/admin/` | working | Analytics `/admin/analytics/`, Reports & Exports `/admin/reports/`, Users `/admin/rescuers/`, Log Out `/auth/logout.php`; Escape closed |
| Admin logout | `/admin/` | working | click → `/auth/logout.php` → `/auth/login.php` |
| Admin bell | `/admin/` | working | click → `/admin/notifications/` |
| Admin search Enter | `/admin/` | working | 768/1440 → `/admin/cases/?q=spot`. Hidden below 640px (not clickable at true 375) |
| Admin sidebar stubs | `/admin/listings/` `/admin/applications/` `/admin/elearning/` `/admin/messages/` | stub-documented | fetch 404; `admin-nav.php` not changed |
| Admin live sidebar folders | reports/cases/rescuers/animals/health-records/analytics/notifications | working | fetch 200 |
| Resident hamburger / overlay / Escape | `/animals/` | working | `#rmenu-toggle` / `#rside` / `#roverlay` at 375 and 768; hidden at 1440 |
| Resident profile | `/animals/` | working | dropdown opened; Escape closed |
| Resident logout | `/animals/` | broken-fixed | was JWT-only + `/auth/login.php` (PHP session survived). Now `href="/auth/logout.php"` + `data-action="logout"` → login; `/animals/` after that is login |
| Resident bell href | chrome | broken-fixed | `<a href="/notifications/" class="rtop-bell">` (shell, not edited). Destination was `requireAuth()` without a page JWT. Agent 13 minted `__PAGE_STATE__.accessToken` + `bootstrapPageAuth()` on `/notifications/` (also `/learning/`, `/messages/`). CDP: cold visit and bell from `/animals/` stay logged in. Phase 02 logout not reverted. |
| Resident sidebar hrefs | `/animals/` | working | `/index.php` `/report/` `/reports/` `/animals/` `/adoptions/` `/learning/` `/messages/` `/notifications/` fetch 200 |
| Landing hamburger pattern | `/` | working | `#menu-toggle` / `#mobile-menu` exist. 375 visible; 768/1440 hidden. CDP click did not open menu (`landing.js` `DOMContentLoaded`-only bind) — agent 10, not restyled |
| dropdown-menu in chrome | `/admin/`, `/animals/` | working | trigger click + Escape |
| Unused UI modules | checkbox, label, loader, separator | working | no `public/` imports; compatibility only — not rewritten |
| date-picker / select inline SVG | UI modules | working | compatibility; not rewritten |
| Chrome leftover `href="#"` | shells | working | none live on PHP chrome after logout fix. JS factory defaults remain unused when `__PAGE_STATE__` is present. Dashboard `#` is agent 20 |

`/reports/` is a bad chrome-click host: PHP session allows it, but
`reports.js` `requireAuth()` without `__PAGE_STATE__.accessToken` sends
the tab to login. Use `/animals/` (document-mode shell + token).

Not rewritten: `Topbar()` / `AppShell()` factories. P1-6
(`rescuers/js/rescuers.js` `./layout/app-shell.js`) not touched.

## Page agents

See `10`–`13` and `20`–`26`. Curl: anonymous `/` + auth pages 200;
resident/admin folders 302→login then 200 after session; admin stub
URLs 404.

## Totals (this pass)

| Classification | Count (approx) |
| --- | --- |
| working | 40+ (guards, live folder URLs, wired `data-action`, Google GSI hook, Lucide chrome) |
| broken-fixed | 16 |
| stub-documented | 12 |

### broken-fixed

-   Landing hero + CTA `#adopt` / `#report` → `/animals/` / `/report/`
-   Audience cards now have `id` so “Learn more” hashes resolve
-   Footer “Report a stray” / “Browse adoption” / “How it works”
-   Dashboard “View all reports” → `/admin/reports/`
-   Dashboard “Open full map” / “View all cases” → `/admin/cases/`
-   Dashboard “View record” → health-record editor with `animal_id`
-   Dashboard Export → `/admin/analytics/`
-   Dashboard New Announcement got `id="announce-btn"`
-   JS fallback `rescuers.html` → `/admin/rescuers/`
-   Matching JS fallbacks for reports / map / cases / view-record
-   Resident Log Out now hits `/auth/logout.php` (PHP session + JWT)
-   Resident `/learning/`, `/messages/`, `/notifications/` mint a page JWT so `requireAuth()` does not bounce a PHP session to login (agent 13)
-   Learning grid no longer blanks when `GET /elearning/progress` 500s; lesson open/complete no longer throws on `done` scope
-   Messages thread list shows a 403 error empty instead of infinite “Loading conversations…”

### stub-documented

-   `/admin/listings/`, `/admin/applications/`, `/admin/elearning/`,
    `/admin/messages/` (sidebar 404; curl 404)
-   Dashboard “View all applications” and “Read module”
-   Footer marketing links without a live page
-   Google `#google` when `GOOGLE_CLIENT_ID` is unset (toast, not a page)

## Remaining P0 / P1

| ID | Severity | Item | Owner |
| --- | --- | --- | --- |
| P1-1 | P1 | `public/admin/index.php` view still 546 lines after data extract — split into `public/admin/partials/` | 20 / 90 |
| P1-2 | P1 | Health monoliths: `health-record.php` 646, `page.js` 1382, roster `index.php` 709 | 25 |
| P1-3 | P1 | Landing `$fontsHref` still loads study **DM Sans**; `00_tokens.css` still `"DM Sans"`; live token is Nunito | 10 |
| P1-4 | P1 | Page-level 375/768/1440 click e2e still open; **shared chrome** was CDP-clicked this phase | 10–26 / 91 |
| P1-5 | P1 | Queue `data-action` handlers not click-tested (code-wired only) | 20–23 |
| P1-6 | P1 | `rescuers/js/rescuers.js` `import "./layout/app-shell.js"` 404s after folder move | 23 |

No P0 functional 500s on live routes after the dashboard split.

## Code changes

| File | Why |
| --- | --- |
| `public/landing/partials/copy.php` | Split landing copy arrays out of homepage |
| `public/landing/partials/hero-art.php` | Split decorative hero illustration |
| `public/includes/homepage.php` | Real CTA URLs + slimmer composer |
| `public/includes/footer.php` | Real hrefs where a live route exists |
| `public/admin/includes/dashboard-data.php` | Extract dashboard queries |
| `public/admin/index.php` | Require data; fix dead hrefs/buttons |
| `public/admin/js/pages/dashboard/components/{cards,queues,activity}.js` | Match PHP URL fixes in JS fallback |
| `public/includes/resident-shell.php` | Phase 02: logout `href="/auth/logout.php"` (still `data-action="logout"`) |
| `public/js/components/resident-shell.js` | Phase 02: after `clearSession()`, go to `/auth/logout.php` so the PHP session dies |
| `public/learning/index.php` + `js/learning.js` | Agent 13: page JWT; progress 500 decoupled; lesson `done` scope / complete |
| `public/messages/index.php` + `js/messages.js` | Agent 13: page JWT; 403 error empty; silent poll |
| `public/notifications/index.php` + `js/notifications.js` | Agent 13: page JWT so bell landings stay logged in |
