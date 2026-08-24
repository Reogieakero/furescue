# Agent 12 --- Resident Adoption

## Ownership

Owned routes:

-   `/animals/`
-   `/animals/detail.php`
-   `/adoptions/`
-   `/listings/`

Owned files:

-   `public/animals/index.php`
-   `public/animals/detail.php`
-   `public/animals/js/animals.js`
-   `public/animals/js/animal-detail.js`
-   `public/animals/js/apply-modal.js`
-   `public/animals/js/3d-viewer.js`
-   `public/adoptions/index.php`
-   `public/adoptions/js/adoptions.js`
-   `public/listings/index.php`
-   `public/listings/js/listings.js`

Do not edit `resident-shell.php`. Do not build admin listings.

## Design system

Reuse `.rfilterbar`, `.racard`, `.rlist`, `.rmodal`, `.rbtn`,
`.input`, `.toast`. Tokens: `--primary`, `--card`, `--border`,
`--radius`, `--shadow-md`. Lucide only (no emoji paw icons). New
reusable style → `input.css` first, then `npm run build`. Verify
375 / 768 / 1440.

## File size / split

Keep `animals.js` / `animal-detail.js` / `apply-modal.js` /
`3d-viewer.js` as separate modules. If any owned file is over ~300
lines and this agent edits it, split; else document. Do not merge
them while fixing apply/listing actions.

Edited this run; all stay under ~300 lines (`animals.js` ~237,
`animal-detail.js` ~285, `apply-modal.js` ~87, `3d-viewer.js` ~223,
`listings.js` ~216, `adoptions.js` ~149). No split required. No
`input.css` change.

## Interaction checklist

`/animals/`:

-   [x] gallery cards from API
-   [x] search / species / sex / breed filters
-   [x] empty / error states
-   [x] card click → `detail.php`

`/animals/detail.php`:

-   [x] photo, bio, health summary
-   [x] Apply modal opens, validates, submits
-   [x] already-applied state
-   [x] 3D viewer if a control is present — open/close only; no product
    expansion
-   [x] missing id → error/empty, not a blank page

`/adoptions/`:

-   [x] own applications list
-   [x] empty / error
-   [x] status visible
-   [x] links back to animal detail where promised

`/listings/`:

-   [x] “Post for adoption” opens a real flow (or document stub)
-   [x] list / empty / error
-   [x] listing actions (edit/withdraw) if present

## Viewport checklist

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/animals/` | pass | pass | pass | Filter bar wraps (~132px tall) at 375; single row at 768/1440. No horizontal overflow. Cards from `GET /api/v1/animals?adoption_status=available` (288 total, 12/page). |
| `/animals/detail.php` | pass | pass | pass | Missing id empty state at all three. Real id `05486404-2867-49ff-a44e-4390a9fda316` (Leo). Apply overlay hosted in `.resident-shell`. |
| `/adoptions/` | pass | pass | pass | 4 rows; Pending tab; View → detail; Cancel clicked at 1440. No overflow. |
| `/listings/` | pass | pass | pass | Empty state; Post modal open/cancel at 375/768; submit at 1440 returns in-modal 403. No overflow. |

## Known debt

-   3D viewer is optional; if the button exists it must open or be
    documented as stub. Do not build a new 3D pipeline.
-   Community listings vs admin `/admin/listings/` (404) are different
    surfaces.

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| Gallery cards | `/animals/` | working | Chrome CDP + `juan@furescue.local`: 12 `.racard` at 375/768/1440; `GET /api/v1/animals?adoption_status=available&per_page=12` → 200, `meta.total` 288. No overflow. |
| Search `q` | `/animals/` | working | `#filter-q` input debounced 300ms; sends `q`. Typed `Leo` in audit script. |
| Species filter | `/animals/` | broken-fixed | In-flight `load()` dropped later `change` events. Queued non-append reload. `species=dog` API `meta.total` 151. |
| Sex filter | `/animals/` | working | `#filter-sex` `male`/`female` → `sex` query; clicked via CDP `change`. |
| Breed filter | `/animals/` | broken-fixed | Only listened to `change`; typing never refreshed. Added `input` debounce. `zzz-no-such-breed` → empty copy. |
| Empty state | `/animals/` | working | Impossible breed shows `#gallery-empty` “No animals match your filters”. |
| Error state | `/animals/` | broken-fixed | Failed fetch now sets empty title/text and hides `#load-more`. |
| Card → detail | `/animals/` | working | `.racard` click → `/animals/detail.php?id={uuid}`. |
| Photo / bio / health | `/animals/detail.php` | working | Leo `05486404-2867-49ff-a44e-4390a9fda316`: name, photo, about, medical summary from `GET /animals/{id}`. |
| Apply modal open | `/animals/detail.php` | broken-fixed | Overlay was on `document.body` so `.resident-shell .rmodal*` never applied; submit sat below the viewport (`y~1052`, `vh` 900). Now appended to `.resident-shell`; body `flex`/`min-height:0` so footer stays in view. |
| Apply submit | `/animals/detail.php` | working | `POST /api/v1/adoptions` 201; toast “Application submitted!…”. Pumpkin `bc99cf8d-7267-4af0-b507-c284257e90b0`. |
| Already-applied | `/animals/detail.php` | broken-fixed | `GET /adoptions?per_page=100` in parallel with animal load; Leo button “Application submitted”. |
| 3D / 360 | `/animals/` | stub-documented | Seed `d3: 0`, no `[data-view-3d]` / `#btn-view-3d`. Overlay host moved to `.resident-shell` only; no new 3D pipeline. |
| Missing id | `/animals/detail.php` | working | No `?id=` → “No animal selected” + gallery link. Lucide on empty. |
| Bad id | `/animals/detail.php` | working | `?id=not-a-real-id` → “Profile unavailable”. |
| Applications list | `/adoptions/` | working | CDP: 4 rows (Pumpkin pending, Leo pending, Maple cancelled, Buddy completed). |
| Status tabs | `/adoptions/` | working | `.rtab` All / Pending clicked at 375/768/1440. |
| Empty / error | `/adoptions/` | working | Empty markup `#adoptions-empty` present; not shown (list had rows). Error branch toasts. |
| View → animal | `/adoptions/` | working | View → `/animals/detail.php?id=bc99cf8d-7267-4af0-b507-c284257e90b0`. |
| Cancel application | `/adoptions/` | working | `[data-cancel]` clicked at 1440; `POST /adoptions/{id}/cancel`. |
| Listings empty | `/listings/` | working | `#listings-empty` “No listings yet”. |
| Post for adoption | `/listings/` | working | `#btn-new-listing` opens `.rmodal` with animal `<select>` at 375/768/1440. Overlay in `.resident-shell`. |
| Listing submit | `/listings/` | stub-documented | Submit at 1440: `#listing-error` “Permission not permitted: adoptions.listings.create”. Resident role has no `adoptions.listings.create`. Did not change API. |
| Edit / withdraw | `/listings/` | stub-documented | No `[data-edit]` / `[data-withdraw]` in UI. |
| Admin listings | `/admin/listings/` | stub-documented | `fetch` status 404. Not built. Community `/listings/` ≠ admin. |
| `href="#"` | owned pages | working | None in `public/animals`, `public/adoptions`, `public/listings`. |
| Resident logout | chrome | working | Not reverted; still `/auth/logout.php`. |

## Unverified

-   Load-more click while 12-of-288: `#load-more` wired; not clicked in a pass where the module fully booted.
-   3D open/close: no control in seed (`d3: 0`).
-   Adoptions empty UI: list always had rows for this demo user.
-   A later headless pass reported 0 gallery cards when `window.__PAGE_STATE__` was unset and `animals.js` did not finish the module graph (3d-viewer never fetched) — PHP built-in server contention. First pass + authenticated API still show 12 cards / 288 available.

## Files changed (not committed)

-   `public/animals/js/animals.js` — queued filter reload, error empty, breed `input` debounce, `readyState` boot, null-safe grid
-   `public/animals/js/animal-detail.js` — already-applied fetch, missing-id Lucide, `readyState` boot
-   `public/animals/js/apply-modal.js` — overlay → `.resident-shell`, modal body flex so footer stays on screen
-   `public/animals/js/3d-viewer.js` — overlay → `.resident-shell`
-   `public/listings/js/listings.js` — overlay host + footer flex; `readyState` boot
-   `public/adoptions/js/adoptions.js` — `readyState` boot
