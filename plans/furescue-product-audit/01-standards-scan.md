# Phase 01 --- Standards Scan (read-only)

## Purpose

Produce a repo-wide inventory so page agents do not independently
re-interpret shared chrome, `href="#"`, or architecture debt.

This phase performs **no production edits**.

Refreshed 2026-08-24 against the live `public/` tree (folder admin
URLs). Prior curl pass is not repeated; HTTP 404s for stub URLs rest
on missing folders. Do not revert prior href/split fixes.

## Hard standards

-   **Design system:** inventory raw hex/hsl, one-off radii, and font
    stacks that ignore live tokens (`--primary`, `--paper`, `--ink`,
    `--jungle`, `--jungle2`, `--coral`, `--teal`, `--stamp`,
    `--brand-1/2`, `--radius`, `--shadow-sm/md`, `--font-sans` /
    `--font-display` / `--font-mono`). Cite
    `docs/study/DESIGN_SYSTEM.md` roles but flag study hex pasted into
    pages as violations. Flag non-Lucide icons (inline SVG / emoji).
    Live `input.css` wins: `--font-sans` Nunito, `--font-display`
    Fraunces, `--font-mono` IBM Plex Mono. Study Primary Green
    `#3D7432` and DM Sans must not be pasted into pages.
-   **File size:** inventory every owned `public/` PHP/JS file over
    ~300 lines as a split candidate. Note second-responsibility mixes.

## Read first

-   `AGENTS.md`
-   `docs/study/DESIGN_SYSTEM.md`
-   `public/css/input.css` (`:root` / `.dark`)
-   `tailwind.config.js`
-   `public/includes/admin-nav.php`
-   `public/includes/admin-shell.php`
-   `public/includes/resident-shell.php`
-   `public/includes/header.php`
-   `public/includes/footer.php`
-   `public/includes/guard.php`
-   `public/includes/site-head.php`
-   `public/js/components/ui/*`
-   `public/js/components/resident-shell.js`
-   `public/admin/js/layout/*`
-   `public/css/input.css`

## Search the whole `public/` tree for

-   `href="#"` and `href="#google"`
-   sidebar/profile targets that 404
-   leftover `NAV_GROUPS` / old `/admin/*.php` (legacy files already 302
    to folder URLs)
-   files over ~300 lines
-   ad-hoc colors vs tokens in `public/css/input.css`
-   JS that still rebuilds whole-page `innerHTML` after PHP already
    rendered it (`document.getElementById("app").innerHTML =`)

## Inventory (filled during execution)

### `href="#"` classification seed

| Location | Snippet | Seed class |
| --- | --- | --- |
| `public/auth/login.php`, `signup.php` | `href="#google"` + `data-google-signin` | wired `data-action` (Google GSI) |
| `public/includes/resident-shell.php` | logout `href="#"` + `data-action="logout"` | wired `data-action` |
| `public/includes/header.php` | `href="#home"`, `#audiences`, `#features`, `#how` | in-page anchors — IDs exist on homepage |
| `public/includes/homepage.php` | hero + CTA `href="/animals/"`, `href="/report/"` | **resolved** (no longer `#adopt` / `#report`) |
| `public/includes/homepage.php` | audience `href="#rescuers|#vets|#community"` | in-page anchors — card `id`s match `copy.php` |
| `public/includes/footer.php` | Report / Browse / How it works | **resolved** live URLs (`/report/`, `/animals/`, `#how`) |
| `public/includes/footer.php` | remaining column `href="#"` (Find rescuers, Map view, For-audience, Safety, Contact, FAQ) | unimplemented marketing links |
| `public/admin/index.php` | View all reports / Open full map / View all cases / View record / Export | **resolved** folder URLs |
| `public/admin/index.php` | “View all … applications” `href="#"` (line 221) | unimplemented stub → `/admin/applications/` 404 |
| `public/admin/index.php` | “Read module” `href="#"` (line 407) | unimplemented stub → no `/admin/elearning/` |
| `public/admin/index.php` | queue `data-action="verify|dismiss|approve-rescuer|…"` | wired `data-action` (P1-5: not click-tested) |
| `public/admin/js/pages/dashboard/components/queues.js` + `cards.js` | same applications / Read module / queue actions | JS fallback mirrors PHP |
| `public/admin/analytics/view.php` | `href="#"` + `data-export="overview|adoption-trends|health-updates"` | wired `data-action` |
| `public/landing/components/footer.js` / `audiences.js` / `navbar.js` | `href="#"` / section hashes | unused if PHP landing is the renderer |

Owning agents must confirm each row in the browser.

### 404 nav (do not build)

From `public/includes/admin-nav.php` (grep; **no** matching folders
under `public/admin/`):

-   `/admin/listings/`
-   `/admin/applications/`
-   `/admin/elearning/`
-   `/admin/messages/`

Resident nav in `resident-shell.php` points at live folders:
`/index.php`, `/report/`, `/reports/`, `/animals/`, `/adoptions/`,
`/learning/`, `/messages/`, `/notifications/`. Community `/listings/`
is a **live** resident route (`public/listings/index.php`) but is
**not** in the resident sidebar (linked from `/adoptions/` copy).
That is not the admin listings 404.

Admin profile menu (shell + `topbar.js`): Analytics
`/admin/analytics/` (live, not sidebar), Reports & Exports
`/admin/reports/`, Users `/admin/rescuers/`, Log Out
`/auth/logout.php`. Bell → `/admin/notifications/` (`app-shell.js`).
Topbar search Enter → `/admin/cases/?q=`.

### `NAV_GROUPS`

Search result this pass: **no matches** under `public/` (also none in
`public/admin/js/layout/sidebar.js`). Navigation data lives in
`admin-nav.php` (`$adminNav`) and `resident-shell.php`
(`$residentNavGroups`).

Legacy `public/admin/*.php` files are 302 redirects to folder URLs
(except `public/admin/index.php`, which **is** the live dashboard):

| File | Location |
| --- | --- |
| `public/admin/animals.php` | `/admin/animals/` |
| `public/admin/cases.php` | `/admin/cases/` |
| `public/admin/case-detail.php` | `/admin/cases/case-detail.php` |
| `public/admin/reports.php` | `/admin/reports/` |
| `public/admin/rescuers.php` | `/admin/rescuers/` |
| `public/admin/health-records.php` | `/admin/health-records/` |
| `public/admin/health-record.php` | `/admin/health-records/health-record.php` |

### Full-page `innerHTML` (PHP already rendered `#app`)

Page JS still assigns `app.innerHTML = …` as a fallback when
`window.__PAGE_STATE__` is missing or `#app` is empty. PHP pages render
into `#app`. Compatibility fallback is allowed; overwriting a filled
`#app` on the happy path is a bug for the owning agent.

Known entry files — **all still exist** at folder paths (not
`public/admin/js/` except dashboard). Happy-path guard is
`if (window.__PAGE_STATE__)` then `if (app && !app.childElementCount)`
before assign:

| File | Overwrite | Guard when `__PAGE_STATE__` |
| --- | --- | --- |
| `public/admin/js/dashboard.js` | `app.innerHTML = DashboardPage(…)` | skip if `#app` has children |
| `public/admin/animals/js/animals.js` | `AnimalsPage` | skip if children |
| `public/admin/cases/js/cases.js` | `CasesPage` | skip if children |
| `public/admin/cases/js/case-detail.js` | `CaseDetailPage` / not-found HTML | skip if children |
| `public/admin/reports/js/reports.js` | `ReportsPage` | skip if children |
| `public/admin/rescuers/js/rescuers.js` | `RescuersPage` | skip if children |
| `public/admin/health-records/js/health-records.js` | `HealthRecordsPage` | skip if children |
| `public/admin/health-records/js/health-record.js` | `paint(html)` | skip if children |

Post-load remounts (owning agents): `health-record/page.js` `paint()`
always replaces `#app` after editor actions; `case-detail/components/events.js`
`mountCaseDetail()` remounts after proof/assign/resolve.

**Broken import (agent 23):** `public/admin/rescuers/js/rescuers.js`
is loaded by `rescuers/index.php` and does
`import { initShell } from "./layout/app-shell.js"` →
`/admin/rescuers/js/layout/app-shell.js` (**file missing**). Sibling
pages use `/admin/js/layout/app-shell.js`. PHP still renders; queue
JS on this page [uncertain] until the module loads. `P1-6`.

### Files over ~300 lines (inventory)

Physical line counts (`Get-Content`.Count, including blanks) this
pass. Prior scan used non-blank counts (e.g. `page.js` 1288 / this
1382).

| Lines | File | Split rule |
| --- | --- | --- |
| 1382 | `public/admin/health-records/js/pages/health-record/page.js` | **P1-2** — split if agent 25 edits; blocking monolith |
| 709 | `public/admin/health-records/index.php` | **P1-2** — candidate unless 25 edits |
| 646 | `public/admin/health-records/health-record.php` | **P1-2** — split if 25 edits (editor sections) |
| 546 | `public/admin/index.php` | **P1-1** — split view into `public/admin/partials/` |
| 424 | `public/admin/js/pages/dashboard/queue.js` | split if 20 edits |
| 422 | `public/admin/cases/case-detail.php` | split if 22 edits |
| 372 | `public/admin/reports/index.php` | split if 21 edits |
| 372 | `public/admin/cases/index.php` | split if 22 edits |
| 328 | `public/admin/analytics/js/analytics.js` | **new vs prior** — split if 26 edits |
| 317 | `public/report/js/report.js` | **new vs prior** — split if 11 edits |
| 304 | `public/admin/rescuers/js/pages/rescuers/components/detail.js` | **new vs prior** — split if 23 edits |
| 302 | `public/admin/health-records/js/pages/health-records/state.js` | **new vs prior** — candidate for 25 |

Near the line (do not grow): `public/admin/animals/js/pages/animals/components/modal.js` (296),
`public/admin/animals/index.php` (294).

**Dropped vs prior scan:** `public/includes/homepage.php` is **221**
lines after `landing/partials/{copy,hero-art}.php` extract (was 307).
Off the split list unless agent 10 grows it.

If an agent is already editing a row, **split as part of the fix**.
Do not grow these files. Document-only if the audit never opens them
for a functional bug — unless the size blocks the audit, then split.

### Token / icon violations (inventory)

`public/css/input.css` `:root` / `.dark` is the token home. Allowed
there: `hsl(var(--…))`, a few `#fff` on branded marks, sidebar
`rgb(255 255 255 / …)` on jungle surfaces, and
`hsl(150 70% 32%)` toast-success green (no `--success` token yet —
flag as promote-or-document, do not restyle in this audit). Live
type: Nunito / Fraunces / IBM Plex Mono (`tailwind.config.js`
`font-sans` / `font-display` / `font-mono`). Study hex `#3D7432`
**not** found in `public/` pages.

`site-head.php` default font URL is **Nunito only**. Admin pages and
`resident-shell.php` override `$fontsHref` to Nunito + Fraunces +
IBM Plex Mono. Auth pages use the Nunito-only default.

Page-level violations to confirm in owned files:

-   **P1-3 remaining:** `public/includes/homepage.php` `$fontsHref`
    still loads study **DM Sans**. `public/landing/css/partials/00_tokens.css`
    still sets `--font-sans: "DM Sans"` and overrides `--primary` toward
    study green (`110 40% 33%`). Agent 10 must align to live Nunito /
    Fraunces / IBM Plex Mono — not a Phase 01 production fix.
-   raw hex/hsl in PHP/JS when tokens exist: Chart.js / Leaflet palettes
    in `cases/.../kpi.js`, `map.js`, `health-records/.../charts.js`,
    `health-records/index.php` condition colors, `report/js/report.js`
    marker color (token-equivalent hsl, not study hex). `#fff` on
    jungle/coral surfaces in admin CSS partials. `#d6e2ea` /
    `#bfdcf2` in `05_audit.css` / `01_body.css` / `06_drawer-location.css`.
-   Google brand SVG (inline `<path fill="#FFC107"|…>`) on
    `auth/login.php` + `signup.php` — brand mark, not Lucide.
-   Decorative SVG in `landing/partials/hero-art.php` (and unused
    `landing/components/hero.js`).
-   Shared UI compatibility: `date-picker.js` and `select.js` use
    inline SVG chevrons/calendar — Phase 02 must **not** rewrite these
    because they generate DOM.
-   one-off radii in `input.css` itself (`6px`, `0.9rem`, `999px`
    pills) — token home; do not restyle pages around them this audit.

### UI modules (`public/js/components/ui/`)

19 modules present (Phase 02 verifies in use; compatibility only):

`badge.js`, `button.js`, `card.js`, `checkbox.js`, `date-picker.js`,
`dialog.js`, `drawer.js`, `dropdown-menu.js`, `input.js`, `label.js`,
`loader.js`, `marker.js`, `pagination.js`, `select.js`,
`separator.js`, `skeleton.js`, `spinner.js`, `toast.js`, `tooltip.js`.

Layout JS: `public/admin/js/layout/app-shell.js` (`AppShell`,
`initShell` hamburger/overlay/Escape/search/bell), `sidebar.js`
(`Sidebar` factory, no `NAV_GROUPS`), `topbar.js` (profile menu
URLs). Resident: `public/js/components/resident-shell.js`
(`#rmenu-toggle`, overlay, Escape, `data-action="logout"`, bell
badge).

### Live vs stub routes

Resident / public (folders or PHP files exist):

| Route | File |
| --- | --- |
| `/` | `public/index.php` → `includes/homepage.php` |
| `/auth/login.php`, `/auth/signup.php`, `/auth/logout.php` | `public/auth/*.php` |
| `/report/`, `/reports/` | `public/report/index.php`, `public/reports/index.php` |
| `/animals/`, `/animals/detail.php` | `public/animals/` |
| `/adoptions/`, `/listings/` | `public/adoptions/`, `public/listings/` |
| `/learning/`, `/messages/`, `/notifications/` | matching `public/<page>/index.php` |

Admin live (folder URLs):

| Route | File |
| --- | --- |
| `/admin/` | `public/admin/index.php` |
| `/admin/reports/` | `public/admin/reports/index.php` |
| `/admin/cases/` | `public/admin/cases/index.php` |
| `/admin/cases/case-detail.php` | `public/admin/cases/case-detail.php` |
| `/admin/rescuers/` | `public/admin/rescuers/index.php` |
| `/admin/animals/` | `public/admin/animals/index.php` |
| `/admin/health-records/` | `public/admin/health-records/index.php` |
| `/admin/health-records/health-record.php` | `public/admin/health-records/health-record.php` |
| `/admin/analytics/` | `public/admin/analytics/index.php` + `view.php` partial |
| `/admin/notifications/` | `public/admin/notifications/index.php` |

Known 404 (no folder; do not build): `/admin/listings/`,
`/admin/applications/`, `/admin/elearning/`, `/admin/messages/`.

### Leftover files

-   `public/admin/index.php.bak` — backup, not a route
-   `public/landing/components/*.js` — historical client renderers;
    landing PHP + `landing.js` is the live path (`#menu-toggle` /
    `#mobile-menu`)

## Deliverable

This file plus `FINDINGS.md` section “Phase 01”. No production refactor
in this phase.
