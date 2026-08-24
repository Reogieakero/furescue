# Agent 20 --- Admin Dashboard

## Ownership

Owned routes:

-   `/admin/`

Owned files:

-   `public/admin/index.php`
-   `public/admin/js/dashboard.js`
-   `public/admin/js/pages/dashboard/**`
-   `public/admin/includes/dashboard-data.php`
-   `public/admin/partials/**`

Legacy 302 (do not delete unless 90 confirms unused): none for index.
Do not edit `admin-nav.php`, `admin-shell.php`, or layout JS.

## Design system

Reuse `.panel`, `.table`, `.stamp`, `.btn-link`, `.input`, `.toast`,
`.loader-overlay`, admin shell. Tokens: `--primary`, `--coral`,
`--jungle`, `--border`, `--radius`, `--shadow-md`. Lucide only. No
raw hex in `index.php` or dashboard JS. After CSS edits:
`npm run build`. Verify 375 / 768 / 1440.

## File size / split

P1-1 closed: `public/admin/index.php` is a thin composer (~92 lines).
View markup lives in:

-   `public/admin/partials/queues.php` (~196)
-   `public/admin/partials/cards.php` (~206)
-   `public/admin/partials/activity.php` (~64)

`public/admin/js/pages/dashboard/queue.js` remains ~424 lines and was
**not edited** this run, so it was not split.

## Interaction checklist

-   [x] KPI cards render from PHP (or documented JS hydrate)
-   [x] heatmap / case density map
-   [x] queue tabs: reports, rescuers, health, adoptions
-   [x] queue actions: details / verify / dismiss / approve / reject
-   [x] health “View record”
-   [x] activity table pagination
-   [x] “View all N reports” footer
-   [x] “View all N applications” footer
-   [x] “Open full map”
-   [x] e-learning “Read module”
-   [x] “View all cases”
-   [x] duty / rescuer “View all” (PHP already uses `/admin/rescuers/`)
-   [x] announce dialog if present
-   [x] JS fallback does not overwrite PHP `#app` when filled
-   [x] `cards.js` must not still point at `rescuers.html`

## Viewport checklist

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/admin/` | clicked* | clicked* | clicked | KPI wrap, queue tabs/actions, map, announce, pagination. \*Chrome `innerWidth` drifted to ~1130–1193 after `/admin/` load despite `Emulation.setDeviceMetricsOverride` + `Browser.setWindowBounds` (same Phase 02 quirk). 1440 held. `overflowX` false at drifted 375/768; true at 1440 (admin CSS not owned). |

## Known debt

-   Several PHP footers are `href="#"` with no `data-action`. Classify:
    -   View all reports → `/admin/reports/` (**fixed**)
    -   View all cases → `/admin/cases/` (**fixed**)
    -   Open full map → `/admin/cases/` (**fixed**)
    -   View all applications → `/admin/applications/` **404**
        (`stub-documented`) — left `href="#"`
    -   Read module → `/admin/elearning/` **404** (`stub-documented`) —
        left `href="#"`
    -   View record → `/admin/health-records/health-record.php?id={animal_id}`
        (**fixed**)
-   Dual PHP/JS render: `dashboard.js` only fills `#app` when empty
    (`!app.childElementCount` while `window.__PAGE_STATE__` is set).

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| `/admin/` after login | `/admin/` | working | Chrome CDP login `admin@furescue.local`; landed `/admin/index.php`; Command Center KPIs; `#app` `childElementCount` 1 |
| KPI cards | `/admin/` | working | PHP `id="kpi-grid"`; CDP `kpi: true` |
| Queue tabs reports/rescuers/health/adopt | `/admin/` | working | CDP click `.q-btn[data-q]`; `#queue-*` panels toggle `is-hidden` |
| Queue details | `/admin/` | working | `data-action="details"` handled in `queue.js` (same listener as verify) |
| Queue verify | `/admin/` | working | CDP `[data-action="verify"]` → dialog “Verify report”; cancelled |
| Queue dismiss | `/admin/` | working | CDP `[data-action="dismiss"]` → “Dismiss report”; cancelled |
| Queue approve/reject rescuer | `/admin/` | working | CDP → “Approve rescuer” / “Reject rescuer”; cancelled |
| Queue approve/decline adoption | `/admin/` | working | CDP → “Approve adoption” / “Decline adoption”; cancelled |
| Heatmap / case density map | `/admin/` | working | `#case-density-map` + Leaflet pane (768/1440; 375 pane sometimes missed after later clicks) |
| Activity pagination | `/admin/` | working | `button[data-page]` count 24; CDP clicked a non-current page |
| View all reports | `/admin/` | broken-fixed | PHP + CDP `reportsHref` `/admin/reports/` |
| Open full map / View all cases | `/admin/` | broken-fixed | PHP + CDP `mapHref`/`casesHref` `/admin/cases/` |
| View record | `/admin/` | broken-fixed | `/admin/health-records/health-record.php?id={animal_id}` (PHP + `queues.js`) |
| Export Report | `/admin/` | broken-fixed | PHP `button_anchor_html('/admin/analytics/')`; JS `page.js` `href: "/admin/analytics/"`; CDP `exportHref` |
| New Announcement | `/admin/` | working | CDP `#announce-btn` → “New Announcement”; close `[data-act="close"]` |
| JS `rescuers.html` | `/admin/` | broken-fixed | `cards.js` View all → `/admin/rescuers/`; no `rescuers.html` in dashboard JS |
| Duty / rescuer View all | `/admin/` | working | `partials/cards.php` `/admin/rescuers/` |
| View all applications | `/admin/` | stub-documented | footer `href="#"`; `/admin/applications/` 404 — not built |
| Read module / Manage content | `/admin/` | stub-documented | `href="#"`; `/admin/elearning/` 404 — not built |
| JS fallback does not wipe `#app` | `/admin/` | working | `dashboard.js` innerHTML only if `!childElementCount`; CDP `hasPageState` + `appChildren` 1 |
| P1-1 index.php split | `/admin/` | working | composer ~92 lines; queues/cards/activity partials; `php -l` clean |

## Unverified

-   True CSS layout at **375** and **768** device widths: Chrome still
    reported `innerWidth` ~1130–1193 after dashboard load even after
    re-applying `Emulation.setDeviceMetricsOverride` and
    `Browser.setWindowBounds` (Phase 02 same quirk). Clicks still ran.
-   Completing verify/dismiss/approve/reject through the API (dialogs
    opened, then cancelled so seed queues were not drained).
-   Horizontal overflow at a true 1440 layout (`overflowX` true in CDP
    while `innerWidth` was 1440) — admin CSS is not owned by this agent.

## Files changed

-   `public/admin/index.php` (thin composer)
-   `public/admin/partials/queues.php` (new)
-   `public/admin/partials/cards.php` (new)
-   `public/admin/partials/activity.php` (new)
-   `public/admin/js/pages/dashboard/components/page.js` (JS fallback Export href)
-   `plans/furescue-product-audit/20-agent-admin-dashboard.md` (this file)

No commit. No `/api/v1` changes. `queue.js` not edited (still ~424).
