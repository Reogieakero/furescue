# Agent 11 --- Resident Reports

## Ownership

Owned routes:

-   `/report/`
-   `/reports/`

Owned files:

-   `public/report/index.php`
-   `public/report/js/report.js`
-   `public/report/js/map.js` (split out this run)
-   `public/report/js/photos.js` (split out this run)
-   `public/reports/index.php`
-   `public/reports/js/reports.js`

Do not edit `resident-shell.php` or shared UI.

## Design system

Reuse `.resident-shell`, `.rpage-title`, `.rbtn`, `.input`, `.field`,
`.toast`, `.loader-overlay`. Tokens: `--primary`, `--border`,
`--card`, `--radius`. Lucide only. No raw hex/hsl in page JS. New
reusable style → `input.css` first, then `npm run build`. Verify
375 / 768 / 1440.

Map rectangle stroke uses `hsl(var(--jungle2))` via computed token
`--jungle2` (not a new token). No `input.css` edit this run.

## File size / split

`public/report/js/report.js` was ~317 lines and mixed map, photos, and
submit. This run split it:

| File | Lines (approx) |
| --- | --- |
| `public/report/js/report.js` | 167 |
| `public/report/js/map.js` | 266 |
| `public/report/js/photos.js` | 65 |
| `public/reports/js/reports.js` | 220 |
| `public/report/index.php` | 133 |
| `public/reports/index.php` | 201 |

All under ~300. No CSS rebuild.

## Interaction checklist

`/report/`:

-   [x] page HTML present without JS rebuilding the form
-   [x] map loads (Leaflet)
-   [x] geotag / click-to-place marker
-   [x] marker stays inside Mati bounds (`__PAGE_STATE__.bounds`)
-   [x] reverse geocode fills address unless user edited it
-   [x] photo picker, max files / size errors
-   [x] required-field errors
-   [x] successful submit → confirmation or `/reports/`
-   [x] unauthenticated → login guard

`/reports/`:

-   [x] list of own reports
-   [x] empty state
-   [x] error state
-   [x] row / detail navigation if present (none — stub)
-   [x] filters if present (none — stub)
-   [x] status chips match API data

## Viewport checklist

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/report/` | pass | pass | pass | innerWidth exact; no overflow. Map 341×256 / 734×320 / 436×320. Form stacks at 375/768; two columns at 1440. `#rmenu-toggle` visible and opens `#rside` at 375/768; `display:none` at 1440. |
| `/reports/` | pass | pass | pass | innerWidth exact; no overflow. 4 cards readable. Hamburger same as `/report/`. |

Chrome CDP (`Emulation.setDeviceMetricsOverride`, headless). Browser MCP not in this session.

## Known debt

-   Leaflet from CDN — failed asset is a P0 if the map never appears.
    **This run:** map appeared (Leaflet attribution + zoom controls). Fallback
    CDNs + manual lat/lng if `window.L` is missing.
-   Geovalidation is server-side; client bounds must match env box.
-   Phase 02: `/reports/` `requireAuth` without page token lived in **owned**
    `public/reports/index.php` + `reports.js` (and the same pattern on
    `/report/`). Fixed here with `$pageState.accessToken` + `bootstrapPageAuth()`.
    Did not edit `guard.php` / `resident-shell.php`.

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| PHP form HTML (no JS rebuild) | `/report/` | working | `#report-form` in first paint; CDP 375/768/1440 |
| Unauthenticated guard | `/report/`, `/reports/` | working | fetch redirect:manual **302** `Location: /auth/login.php` |
| Page JWT bootstrap | `/report/`, `/reports/` | broken-fixed | Phase 02: `requireAuth()` with no `__PAGE_STATE__.accessToken` sent session-only users to login. Both PHP pages now mint `accessToken` + `user`; JS calls `bootstrapPageAuth()` then `requireAuth()`. CDP: `hasPageToken` + `hasLsToken` true; stayed on `/report/` and `/reports/` after PHP login (`juan@furescue.local`) |
| Leaflet map | `/report/` | working | Not P0. `#report-map` visible; text `+− Leaflet \| © OpenStreetMap contributors`. `window.L` true |
| Click-to-pin / geotag | `/report/` | working | CDP click map center → lat `6.949975` lng `126.199865` (inside `__PAGE_STATE__.bounds`) |
| Reverse geocode | `/report/` | working | Address filled: `Bilawan 1, Tagawisan, Central, Badas, Mati, Davao Oriental…` |
| Empty required fields | `/report/` | broken-fixed | CDP empty submit: description error **and** location `Drop a pin on the map first.` (was false "outside Mati City" from `Number("") === 0`). `getLatLng()` treats blank as missing. Stays on `/report/` (no native GET) |
| Photo oversize | `/report/` | working | 11 MB file → toast `"a11-big.bin" is larger than 10 MB.`; list stayed 0 |
| Photo picker accept | `/report/` | working | 1×1 PNG → `#photo-list` count **1** |
| Submit → `/reports/` | `/report/` | broken-fixed | Empty submit used to native-GET (listener bound after `await initMap`). Now bind submit first; form `method="post"`. CDP pin+desc → POST `/api/v1/reports` **201** → `href` `/reports/`, count **7 reports submitted**, 7 cards |
| Use my location | `/report/` | working | CDP `Emulation.setGeolocationOverride` inside box → lat `6.950000` lng `126.210000`. Outside box → toast stay on previous pin |
| Own reports list | `/reports/` | working | SSR + refresh. After create: **7** cards, `7 reports submitted` |
| Refresh | `/reports/` | working | Click `#refresh-reports` → toast `Reports refreshed.` |
| Empty state | `/reports/` | working | PHP empty markup + JS. CDP fetch mock `[]` → `#reports-empty` |
| Error state | `/reports/` | working | PHP try/catch + `#reports-error` / `#reports-retry`. CDP fetch mock 500 → error + retry |
| Status chips | `/reports/` | working | Chips `Verified` / `Pending verification` from `verified` / `pending_verification` |
| Row / detail | `/reports/` | stub-documented | Cards have `data-report-id`, no href / detail route. Not built |
| Filters | `/reports/` | stub-documented | No filter UI. Not built |
| Hamburger | `/report/`, `/reports/` | working | 375/768: click `#rmenu-toggle` opens sidebar. 1440: toggle hidden |

## Unverified

-   8-file photo cap (1 file + oversize tested).
-   Dragging a pin outside the box (map `maxBounds` + clamp on place; Leaflet instance not hooked in CDP).
-   Flash toast on `/reports/` after create (landed on list with 7 cards; toast node empty in the snapshot — likely already dismissed).
-   Reverse-geocode fill when submit is clicked immediately after pin (in-flight PHP geo is aborted so POST is not blocked). Pin-then-wait still fills address (CDP: Mati street string).

## Files changed

-   `public/report/index.php` — JWT `accessToken`; map status; form `method="post"`
-   `public/report/js/report.js` — split composer; `bootstrapPageAuth`; bind submit before map; pause reverse on submit
-   `public/report/js/map.js` — Leaflet, clamp, geocode, geolocate, CDN fallback, abort reverse
-   `public/report/js/photos.js` — picker, size/count toasts
-   `public/reports/index.php` — JWT `accessToken`; list try/catch; empty/error
-   `public/reports/js/reports.js` — `bootstrapPageAuth`; empty/error; retry
-   `plans/furescue-product-audit/11-agent-resident-reports.md` — this file
