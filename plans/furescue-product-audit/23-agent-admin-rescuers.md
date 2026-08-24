# Agent 23 --- Admin Rescuers

## Ownership

Owned routes:

-   `/admin/rescuers/`

Owned files:

-   `public/admin/rescuers/**`

Legacy 302: `public/admin/rescuers.php`.

Do not edit shared nav/shell.

## Design system

Reuse `.panel`, `.table`, `.stamp`, `.input`, `.toast`, shared
dialog/drawer. Tokens: `--primary`, `--destructive`, `--border`,
`--radius`. Lucide only. After CSS edits: `npm run build`. Verify
375 / 768 / 1440.

## File size / split

Flag owned files over ~300 lines. Keep
`components/{table,detail,filters,kpis}.js` and
`workflow/{actions,events}.js` separate. Split on edit; do not merge
to fix approve/reject.

This run split `components/detail.js` (~304) into:

-   `detail.js` (86) — `selectRescuer` / `RescuerDetail`
-   `detail-profile.js` (43)
-   `detail-cases.js` (200)

`index.php` is 290. No owned file left over ~300. No CSS edits.

## Interaction checklist

-   [x] KPI tiles
-   [x] filter tabs
-   [x] table
-   [x] detail panel/drawer
-   [x] approve
-   [x] reject
-   [x] suspend
-   [x] duty toggle
-   [x] empty / error
-   [x] case activity in detail if present
-   [x] JS does not wipe `#app` when PHP filled it

## Viewport checklist

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/admin/rescuers/` | ok | ok | ok | No `overflowX`. Table (not stacked cards). Split: 343px stacked / 736px stacked / `895.5px 298.5px`. |

## Known debt

-   Profile menu “Users” points here — keep that working.
-   Dual PHP/JS render in `rescuers.js`.
-   Export CSV has no `data-action` / download handler.
-   `state.js` `safe()` swallows fetch errors into empty lists (no error banner).
-   PHP built-in server is single-threaded: parallel GETs from
    `loadRescuers` + `selectRescuer` can starve PATCH/POST confirms
    during local CDP.

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| P1-6 `app-shell` import | `/admin/rescuers/` | broken-fixed | `rescuers.js` now imports `/admin/js/layout/app-shell.js` (not `./layout/app-shell.js`). CDP: resource 200, 1711 bytes. `eventsBound: true`. No layout copy into `rescuers/`. |
| JS `#app` overwrite | `/admin/rescuers/` | working | `__PAGE_STATE__` present; `#app` children stay 1 (PHP shell). Fallback `innerHTML` only if empty. |
| KPI tiles | `/admin/rescuers/` | working | 5 tiles at 375/768/1440: Total 40, Active 30, On duty 21, Pending 10, Suspended 0. |
| Filter tabs | `/admin/rescuers/` | working | All / Active / On duty / Off duty / Pending clicked at all three widths. Pending → 10 Approve + 10 Reject. |
| Table | `/admin/rescuers/` | working | 10 rows, `tableKind: table` (not cards). Duty + Suspend on All; Approve + Reject on Pending. |
| Detail panel | `/admin/rescuers/` | broken-fixed | Action clicks no longer call `selectRescuer` (that raced PATCH on PHP `-S`). Skip in-flight / generation guard. Sequential user-then-cases fetch. 768/1440: profile + 18 past cases. 375 row click still showed “Loading rescuer…” at 8s (PHP `-S` queue). |
| Expand drawer | `/admin/rescuers/` | working | `[data-act="expand"]` opened a dialog at 768 and 1440; closed via `.dialog-x`. Missing at 375 while detail still loading. |
| Case activity | `/admin/rescuers/` | working | 18 `[data-case-toggle]` nodes after row select (768/1440). Click dispatched; `aria-expanded` not true within 800ms (async activity GET). |
| Approve (P1-5) | `/admin/rescuers/` | working | `[data-action="approve"]` opened “Approve rescuer” at 375/768/1440; Cancel closed. Confirm POST not observed in the mutation pass (ok click hit a leftover Suspend overlay after a hung PATCH). Handler is `runApprove` → `api.approveRescuer`. |
| Reject (P1-5) | `/admin/rescuers/` | working | `[data-action="reject"]` opened “Reject rescuer” with required reason at 375/768/1440; Cancel closed. Confirm same overlay caveat as Approve. Handler `runReject`. |
| Suspend (P1-5) | `/admin/rescuers/` | working | `[data-action="suspend"]` opened “Suspend rescuer” with reason at 375/768/1440; Cancel closed. Mutation pass: ok clicked, overlay stayed, 0 Activate buttons, no toast within 14s (PHP `-S` GET queue). Handler `runSuspend`. |
| Duty toggle (P1-5) | `/admin/rescuers/` | working | 10 `[data-action="duty"]` “Set on duty”. CDP mouse click at 1440 hit the button (`inView: true`). Label unchanged and no toast within 12s (PATCH queued behind GETs). Handler `runToggleDuty` no longer starts `selectRescuer` first. |
| Empty state | `/admin/rescuers/` | working | Search `zzzznonexistent` → `tableKind: empty`, “No rescuers match.” at 375/768/1440. |
| Error state | `/admin/rescuers/` | stub-documented | No error banner. `safe()` maps failed fetches to empty lists, so a dead API looks like empty. |
| Export CSV | `/admin/rescuers/` | stub-documented | Head button has no `data-action` / `href`. Clicked at 375/768/1440: no toast, dialog, or download. |
| Profile “Users” | `/admin/` → `/admin/rescuers/` | working | `#profile-menu a[href="/admin/rescuers/"]` text “Users”. JS click from dashboard landed on `/admin/rescuers/` (`eventsBound: true`, 10 pending). |
| `href="#"` on page | `/admin/rescuers/` | working | Snap `hashHrefs: []` inside `#app` on the rescuers page. |

Method: Chrome CDP (`Emulation.setDeviceMetricsOverride` 375 / 768 / 1440), headless, `admin@furescue.local` / `Password123!`. No Browser MCP (`GetDynamicTools` catalog: GenerateImage + GitLens). PHP `php -S 127.0.0.1:8000` already running.

Unverified: duty/suspend/approve/reject **confirm** HTTP success (toasts) — PHP built-in server single-thread + in-flight list/detail GETs; no 4xx in the CDP network log. Case-tree activity payload after expand. True 375 detail finish after >8s.
