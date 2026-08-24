# Phase 02 --- Shared UI Regression

## Purpose

Verify that the existing shared UI layer still works. This phase is
separate from page verification because a page can work while a shared
component is partially broken.

Mirrored from `plans/furescue-admin-refactor-plan/05-component-regression.md`.

## Design system (hard)

Source of truth: `docs/study/DESIGN_SYSTEM.md` (role names: Primary
Green, Secondary Light Gray, Tertiary Soft Gray, Dark Blue, Sage,
DM Sans) + live tokens in `public/css/input.css` `:root` / `.dark` +
`tailwind.config.js`. Live tokens win.

Required token/component names:

-   `--primary`, `--secondary`, `--destructive`, `--border`, `--card`,
    `--paper`, `--ink`, `--jungle`, `--jungle2`, `--coral`, `--teal`,
    `--stamp`, `--brand-1`, `--brand-2`, `--surface-soft`
-   `--radius`, `--shadow-sm`, `--shadow-md`
-   `--font-sans` (Nunito), `--font-display` (Fraunces), `--font-mono`
-   Classes: `.input`, `.toast` / `.toast-viewport`, `.loader-overlay`,
    `.logo-mark`, `.badge-icon`, `.field`, `.admin-shell`, `.sidebar`,
    `.topbar`, `.resident-shell`, `.rside`, `.rbtn`

Checklist:

-   [x] chrome uses tokens, not raw hex/hsl — shells use
    `--jungle`, `--card`, `--radius`, `--font-display`. Leftover
    `#fff` / `rgb(255 255 255 / …)` on `.rside` text in `input.css` and
    admin `.sidebar` / overlay rgba in
    `public/admin/css/partials/01_body.css` (admin CSS is not in the
    Phase 02 allowlist; not restyled). Not a functional break.
-   [x] Lucide only on admin/resident shells (`data-lucide` +
    `createIcons`). Landing hamburger is CSS bars, not Lucide — agent 10.
    `date-picker.js` / `select.js` still ship inline SVG chevrons; not
    rewritten (compatibility).
-   [x] reuse `.input` / `.toast` / `.loader-overlay` / `.logo-mark` /
        `.badge-icon` — no restyle-from-scratch this phase
-   [x] new reusable chrome style goes into `input.css` first — none
        added
-   [x] `npm run build` after any CSS/token edit — not run (no CSS edit)
-   [x] hamburger + dropdown at 375 / 768 / 1440 — see viewport table

## File size / split (hard)

Do not grow `admin-shell.php`, `resident-shell.php`, or UI modules
into new monoliths. If this phase must edit a file already over ~300
lines, split the concern touched. Compatibility-only: do not rewrite
a component because it generates DOM.

Touched files stay under ~300 lines (`resident-shell.js` ~85,
`resident-shell.php` ~220). No split required.

## Important rule

Do not rewrite a component merely because it generates DOM.

The goal is **compatibility**, not replacement.

If a component generates large page markup, document whether that
responsibility should eventually move to PHP. Do not perform unrelated
redesign.

## Files owned by this phase

-   `public/js/components/ui/*`
-   `public/js/components/resident-shell.js`
-   `public/includes/admin-shell.php` (behavior via layout JS only)
-   `public/includes/resident-shell.php` (hamburger / dropdown / logout
    wiring only — do not add missing admin pages)
-   `public/admin/js/layout/app-shell.js`
-   `public/admin/js/layout/sidebar.js`
-   `public/admin/js/layout/topbar.js`

Do not migrate pages. Do not change `admin-nav.php` hrefs that 404
(those are documented stubs).

## Verification method

Browser MCP/Playwright namespaces: **not available** (`GetDynamicTools`
catalog: `cursor` GenerateImage + GitLens only).

Substitute (not curl-only): local Chrome DevTools Protocol
(`chrome.exe --headless=new --remote-debugging-port=9335`). Real
mouse/keyboard: hamburger, overlay click, Escape, profile trigger,
bell, search Enter, logout. PHP server reused at
`http://127.0.0.1:8000`.

Quirk: after `Page.navigate` to `/admin/`, `innerWidth` sometimes
reported **1193** despite `Emulation.setDeviceMetricsOverride` (dashboard
scripts). Hamburger remained `display:block` (&lt;1024) and overlay/Escape
still ran. `/` and `/animals/` honored **375 / 768 / 1440** exactly.

## Components to verify

Where used by the application, verify:

| Module | In use? | Pages / callers | Phase 02 |
| --- | --- | --- | --- |
| badge | yes | landing hero/audiences; case-detail; health-records table | compatibility; not clicked (page-owned) |
| button | yes | dashboard, reports, cases, animals, health, landing, rescuers | compatibility |
| card | yes | landing features/audiences | compatibility |
| checkbox | **unused** | no `public/` import | unused — not rewritten |
| date-picker | yes | health-record editor | compatibility; inline SVG calendar/chevrons left as-is |
| dialog | yes | dashboard queue, reports actions, animals workflow, rescuers | compatibility |
| drawer | yes | reports workflow, health-record, dashboard announce, location-drawer | compatibility |
| dropdown-menu | yes | **admin + resident chrome**; dashboard, cases, animals, health, reports, rescuers | **clicked** on `/admin/` and `/animals/` profile |
| input | yes | health-record editor | compatibility |
| label | **unused** | no `public/` import | unused — not rewritten |
| loader | **unused** | no `public/` import | unused — not rewritten |
| marker | yes | landing how-it-works `Stepper` | compatibility |
| pagination | yes | dashboard queues, reports, cases, rescuers, health-records | compatibility |
| select | yes | dashboard, reports, cases, health, notifications broadcast | compatibility; inline SVG chevron left as-is |
| separator | **unused** | no `public/` import | unused — not rewritten |
| skeleton | yes | dashboard, cases, reports, rescuers, case-detail | compatibility |
| spinner | yes | reports, cases, animals, health | compatibility |
| toast | yes | many page modules + analytics + broadcast | compatibility |
| tooltip | yes | admin reports `tooltips.js` | compatibility |

For each **used** component: initial render and expected DOM are
produced by existing factories (not rewritten). Event / keyboard /
focus / mobile / page-JS integration belong to the page agents that
mount them. Chrome-level `dropdown-menu`: open, item hrefs, Escape
close, no chrome exceptions.

`analytics.js` calls `initShell()` but **not** `initDropdownMenu` —
profile on `/admin/analytics/` is page-owned (agent 26). Do not add
`initDropdownMenu` to `initShell` without making it idempotent
(double-bind opens then closes). Dashboard `/admin/` does init it;
dropdown worked there.

## Shared chrome checklist

Admin (`admin-shell.php` + `app-shell.js`) on `/admin/` after
`admin@furescue.local` / `Password123!`:

-   [x] hamburger `#menu-toggle` opens sidebar at 375px (also at 768;
        hidden at 1440 / ≥1024 by CSS — rail always visible)
-   [x] overlay / Escape closes sidebar (clicked overlay; pressed Escape)
-   [x] profile dropdown opens (Analytics `/admin/analytics/`, Reports &
        Exports `/admin/reports/`, Users `/admin/rescuers/`, Log Out
        `/auth/logout.php`)
-   [x] Log Out goes to `/auth/logout.php` then lands on
        `/auth/login.php`
-   [x] bell navigates to `/admin/notifications/`
-   [x] topbar search Enter navigates to `/admin/cases/?q=spot` (768 +
        1440). Hidden below 640px — not clickable at true 375 by CSS
-   [x] sidebar links are real `href`s (404 stubs documented, not
        removed): live 200 for reports/cases/rescuers/animals/health-records/analytics/notifications;
        **404** listings / applications / elearning / messages

Resident (`resident-shell.php` + `resident-shell.js`) on `/animals/`
after `juan@furescue.local` / `Password123!` (not `/reports/` —
`reports.js` `requireAuth()` without `__PAGE_STATE__.accessToken` JS-redirects
to login; page-owned):

-   [x] hamburger `#rmenu-toggle` opens `#rside` at 375px (and 768;
        hidden at 1440 / ≥1024)
-   [x] overlay / Escape closes
-   [x] profile dropdown opens (Notifications `/notifications/`, Log Out)
-   [x] logout `data-action="logout"` clears JWT then goes to
        `/auth/logout.php` → `/auth/login.php`; `/animals/` after that
        is login. **broken-fixed** (was `clearSession()` +
        `location.replace("/auth/login.php")`, PHP session survived)
-   [x] bell **href** is `/notifications/` (clicked). Destination page
        then `requireAuth()` JS-redirects to `/auth/login.php` (partial
        shell, no `resident-shell.js` / no `accessToken` in page state)
        — agent 13, not a chrome href bug
-   [x] all sidebar hrefs resolve to live folders (fetch 200:
        `/index.php`, `/report/`, `/reports/`, `/animals/`,
        `/adoptions/`, `/learning/`, `/messages/`, `/notifications/`)

Landing header (`header.php` + `landing.js`) is owned by agent 10, but
Phase 02 confirms the hamburger pattern exists.

-   [x] `#menu-toggle` / `#mobile-menu` present. Visible at 375;
        `display:none` at 768 / 1440. CSS bar icon (not Lucide) — do
        not restyle. CDP click at 375 did **not** set `is-open` /
        `aria-expanded` (likely `landing.js` only binds
        `DOMContentLoaded` and misses deferred `type=module` load).
        Owner: agent 10. Pattern exists; toggle not claimed working.

## Viewport checklist

| Surface | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| Admin shell hamburger / dropdown | clicked | clicked | dropdown + search + bell; hamburger hidden | 375/768 `innerWidth` sometimes 1193 after `/admin/` nav; menu still visible (&lt;1024). Overlay + Escape closed. Search hidden &lt;640. |
| Resident shell hamburger / dropdown | clicked | clicked | dropdown + bell; hamburger hidden | Exact 375/768/1440 on `/animals/`. Overlay + Escape closed. Logout clicked at 375. |

## Chrome `href="#"` leftovers

Live PHP chrome (admin-shell + resident-shell + header pattern): **no**
`href="#"` on hamburger, bell, profile items, or sidebar items.

| Location | Class |
| --- | --- |
| Resident Log Out (was `href="#"` + `data-action="logout"`) | **broken-fixed** → should-be-URL `/auth/logout.php` **and** still wired `data-action="logout"` |
| Admin Log Out | should-be-URL `/auth/logout.php` — working |
| `admin-shell.php` / `resident-shell.php` `$item['href'] ?? '#'` | defensive fallback; all current nav items have real hrefs |
| `sidebar.js` `item.href \|\| "#"` and `dropdown-menu.js` `DropdownMenuItem` default `href="#"` | JS factory default — **not live** when PHP shell + `__PAGE_STATE__` renders chrome. Do not rewrite factories |
| `/admin/listings/`, `/admin/applications/`, `/admin/elearning/`, `/admin/messages/` | unimplemented stub (real href, HTTP 404) — `stub-documented`; `admin-nav.php` unchanged |
| Dashboard Verify/Dismiss/Details/Read module `href="#"` | **not chrome** — agent 20 |
| Landing `#home` / section hashes | in-page anchors — agent 10 |

## Known debt

-   Admin sidebar 404s: listings, applications, elearning, messages
-   JS `Topbar()` / `AppShell()` still generate chrome strings for the
    no-`__PAGE_STATE__` fallback — do not rewrite
-   `topbar.js` avatar is a hardcoded pravatar in the JS factory; PHP
    shell uses `$adminAvatarSrc`
-   `analytics.js` does not call `initDropdownMenu` (agent 26)
-   Resident `/notifications/` (and `/reports/`) JS `requireAuth` without
    minted `accessToken` — agent 13 / 11
-   Unused UI modules: checkbox, label, loader, separator
-   P1-6 `rescuers/js/rescuers.js` `./layout/app-shell.js` — agent 23;
    not touched
-   Admin overlay/sidebar leftover hex in `01_body.css` — not Phase 02
    allowlist
-   Landing hamburger bind likely broken on deferred module load —
    agent 10

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| Admin hamburger / overlay / Escape | `/admin/` | working | CDP click `#menu-toggle`; overlay `is-visible`; overlay click + Escape cleared `is-open` |
| Admin profile + logout | `/admin/` | working | Items + `/auth/logout.php` → `/auth/login.php` |
| Admin bell / search | `/admin/` | working | Bell `/admin/notifications/`; Enter `/admin/cases/?q=spot` at 768/1440 |
| Admin stub nav | `/admin/listings/` etc. | stub-documented | fetch 404; `admin-nav.php` not changed |
| Resident hamburger / overlay / Escape | `/animals/` | working | `#rmenu-toggle` / `#rside` / `#roverlay` at 375 and 768 |
| Resident profile | `/animals/` | working | dropdown open; Escape closed (`hidden` back) |
| Resident logout | `/animals/` | broken-fixed | now hits `/auth/logout.php`; PHP session cleared |
| Resident bell href | chrome | working | `<a href="/notifications/">`; destination JS redirect is page-owned |
| Resident sidebar hrefs | `/animals/` | working | all eight folders fetch 200 |
| Landing hamburger pattern | `/` | working (pattern only) | `#menu-toggle` / `#mobile-menu` exist; toggle click not owned here |
| Lucide + tokens on shells | chrome | working | `.logo-mark` / `--jungle`; leftover sidebar `#fff` documented |
| UI modules rewrite | shared | working | compatibility only — not rewritten |
| dropdown-menu in chrome | `/admin/`, `/animals/` | working | trigger click + Escape |

## Deliverable

Chrome clicked at 375 / 768 / 1440 via Chrome CDP (MCP browser
unavailable). One functional fix: resident logout now destroys the PHP
session. UI modules compatibility-only. Stubs left documented.

Files changed:

-   `public/includes/resident-shell.php`
-   `public/js/components/resident-shell.js`
