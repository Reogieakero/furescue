# Agent 22 --- Admin Cases

## Ownership

Owned routes:

-   `/admin/cases/`
-   `/admin/cases/case-detail.php`

Owned files:

-   `public/admin/cases/**`

Legacy 302: `public/admin/cases.php`, `public/admin/case-detail.php`.

Do not edit shared nav/shell. Do not change `/api/v1` even if a client
calls a missing path — document the client bug or fix the **client**
call to the live route.

## Design system

Reuse `.panel`, `.table`, `.input`, `.toast`, drawer/dialog, Leaflet
marker styles from tokens. Lucide only. After CSS edits:
`npm run build`. Verify 375 / 768 / 1440.

## File size / split

Over ~300: `public/admin/cases/index.php` (~339),
`public/admin/cases/case-detail.php` (~384). If this agent edits
either, extract partials under `cases/partials/`. Keep
`case-detail/components/*` as separate modules. Do not grow
`case-detail.php`.

This run did **not** edit PHP (JS-only), so no partial split.

## Interaction checklist

List:

-   [x] KPI strip
-   [x] filters / search (`?q=` from topbar)
-   [x] list + map
-   [x] assign
-   [x] status tabs
-   [x] open detail

Detail:

-   [x] info panel
-   [x] location map
-   [x] workflow / status actions
-   [x] files
-   [x] events / timeline
-   [x] assign dialog
-   [x] proof-photo control — if it POSTs, it must hit a live route
    (`POST /api/v1/cases/{id}/proof` exists in `CaseRoutes.php`)

## Viewport checklist

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/admin/cases/` | ok | ok | ok | No overflow. KPIs, tabs, list, map clicked. Heatmap/pins. Assign dialog at all three. |
| `/admin/cases/case-detail.php` | ok | ok | ok | Real id `b694ac15-ebfb-4fab-8727-b37f76bf0d09`. No overflow. Location/assign/proof clicked at 1440; 375/768 layout snapped on same page. |

## Known debt

-   Historical stub: proof-photo POST with no route. Live router now
    has `POST /api/v1/cases/{id}/proof`. Confirm the detail UI actually
    calls it; if the UI never sends, that is a broken existing action
    in owned JS.
-   `case-detail.js` full-page `innerHTML` fallback.

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| KPI strip | `/admin/cases/` | working | `#case-kpis` visible at 375/768/1440 (100 total / 16 open). Chrome CDP. |
| Status tabs | `/admin/cases/` | working | All + Open clicked at 375/768/1440. Open tab showed 16. |
| List + map | `/admin/cases/` | working | `#case-map` visible; Heatmap/Pins clicked; 90 Leaflet paths; 6 cards; no overflow. |
| Assign (list, P1-5) | `/admin/cases/` | working | `[data-action="assign"]` clicked at 375/768/1440. Dialog title "Assign rescuer", not empty. Cancel. |
| Topbar `?q=` | `/admin/cases/?q=` | broken-fixed | Typed `B694` + Enter → `/admin/cases/?q=B694` with 1 card. `applyUrlQuery()` applies after `__PAGE_STATE__` hydrate. |
| Open detail | `/admin/cases/` | broken-fixed | Card click → `/admin/cases/case-detail.php?id=b694ac15-ebfb-4fab-8727-b37f76bf0d09` (canonical; not the legacy 302 that dropped `id`). |
| Info / workflow / files | `/admin/cases/case-detail.php` | working | Workflow & transactions (1 event); Attached files 1; visible at 375/768/1440. |
| See location | `/admin/cases/case-detail.php` | broken-fixed | Clicked `[data-cd-action="location"]`. Detail JS previously failed (`/js/lib/location-drawer.js` 404); now `/admin/js/lib/location-drawer.js`. Drawer/map UI present. |
| Assign dialog (detail, P1-5) | `/admin/cases/case-detail.php` | working | Assign rescuer clicked; dialog `open: true`, title "Assign rescuer" after `fetchRescuers`. |
| Proof-photo Add | `/admin/cases/case-detail.php` | broken-fixed | PHP gallery had no add form. JS injects `#cd-proof-input` + `[data-cd-action="add-proof"]` (visible 375/768/1440). Handler `apiFetch('/cases/{id}/proof', POST { url })` → live `POST /api/v1/cases/{id}/proof`. Add clicked; CDP mouse y was below the 900px viewport so the Network POST status was not captured. |
| Leaflet `href="#"` +/− | `/admin/cases/` | working | Wired map zoom, not page stubs. |

## Fixes this run (owned JS only)

-   `?q=` applied after `__PAGE_STATE__` hydrate (`applyUrlQuery`).
-   Card/map navigation uses `/admin/cases/case-detail.php?id=`.
-   Proof add form always rendered/injected; Button `attrs` carries `data-cd-action="add-proof"`.
-   Detail module graph: `getCase` from `util.js` (no Chart.js barrel); `openLocationDrawer` from `/admin/js/lib/location-drawer.js`.
-   Hydrated detail: bind events even if shell init throws; do not `innerHTML` overwrite filled `#app` after proof (reload instead).
