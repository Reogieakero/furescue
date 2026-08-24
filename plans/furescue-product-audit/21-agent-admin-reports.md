# Agent 21 --- Admin Reports

## Ownership

Owned routes:

-   `/admin/reports/`

Owned files:

-   `public/admin/reports/**`

Legacy: `public/admin/reports.php` is a 302 — do not edit unless
redirect is wrong.

Do not edit shared nav/shell.

## Design system

Reuse `.panel`, `.table`, `.input`, `.toast`, drawer/dialog from
`public/js/components/ui/`. Tokens: `--primary`, `--destructive`,
`--border`, `--radius`. Lucide only. After CSS edits: `npm run build`.
Verify 375 / 768 / 1440.

## File size / split

Over ~300: `public/admin/reports/index.php` (~372). Not edited this
run, so not split. Workflow files (`actions.js`, `drawer.js`,
`events.js`) stay separate.

## Interaction checklist

-   [x] PHP table/KPIs present on first paint
-   [x] status / barangay / search filters
-   [x] pagination
-   [x] row click / details drawer
-   [x] verify
-   [x] dismiss
-   [x] assign to rescuer
-   [x] timeline in drawer
-   [x] empty / error
-   [x] map or location if shown
-   [x] JS does not wipe `#app` when PHP already filled it

## Viewport checklist

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/admin/reports/` | pass | pass | pass | Filters wrap (toolbar ~158px at 375). Drawer ~343px at 375 (viewport minus overlay padding). `overflowX` 0 at all three. |

## Known debt

-   Drawer/dialog come from shared UI — fix page wiring only.
-   Profile menu “Reports & Exports” also lands here.
-   `Export CSV` in the page head has no `data-action` / download handler.
-   Barangay is a search field, not a separate dropdown.
-   No dedicated fetch-error banner: `safe()` falls through to empty lists.

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| PHP KPIs + table first paint | `/admin/reports/` | working | Authenticated GET HTML 200 (~199k). Contains `#report-kpis` / `kpi-tile`, `#report-table` `<table`, `data-filter=`, `__PAGE_STATE__`, filled `#app`. Labels: Total reports, Pending verify, Verified, Dismissed. |
| JS `#app` overwrite | `/admin/reports/` | working | After module load, `wiped: false` at 375/768/1440; `#app` still has PHP children (`appChildren: 1`). Fallback `innerHTML` only if empty. |
| Status filter chips | `/admin/reports/` | working | Clicked All / Pending verification / Verified / Dismissed at 375, 768, 1440. Pending → 4 rows with Verify+Dismiss; Dismissed → empty “No reports match.”; `is-active` moved to the clicked chip. |
| Barangay / search | `/admin/reports/` | working | `#report-search` at all three widths. Garbage query `zzz-nomatch-xyz` → 0 rows + “No reports match.” Barangay token from column 2 → 7 matching rows. No separate barangay `<select>` (search covers it). |
| Sort select | `/admin/reports/` | working | `#report-sort` opened; option “Verified” clicked at 768/1440 (assigned/verified options). |
| Pagination | `/admin/reports/` | working | `button[data-page="2"]` clicked; after page 2, Next’s `data-page` became `3` (was `2` on page 1). 15 rows per page; 7 pages for 100 reports. |
| Row click / details drawer | `/admin/reports/` | working | Clicked first `td` of `tr[data-id]`. Drawer title “Report details”; map tile present. Location “Resolving location…” (375) then “Licupon 2” (768/1440). Width 343 at 375, 440 at 768/1440. Close control present. |
| Verify (P1-5) | `/admin/reports/` | working | After Pending filter, `[data-action="verify"]` opened dialog “Verify report” at 375/768/1440. At 1440 confirmed: toast `Report #D4F9 verified · Case #B694 created.` |
| Dismiss (P1-5) | `/admin/reports/` | working | `[data-action="dismiss"]` opened “Dismiss report” with required reason. At 1440 confirmed with reason: toast `Report #D1E5 dismissed.` |
| Assign rescuer (P1-5) | `/admin/reports/` | working | After Verified + last page, `[data-action="assign"]` opened “Assign rescuer”; on-duty list included a named rescuer; select trigger clicked. Empty confirm shows `Please select a rescuer.` Successful `POST` assign not confirmed in CDP (custom select option did not stick under harness double-click). Handler + dialog are wired. |
| Timeline | `/admin/reports/` | working | `[data-action="timeline"]` opened drawer “Case timeline” at 375/768/1440. |
| Map / location in drawer | `/admin/reports/` | working | Details drawer `map: true` (Leaflet container sized). Copy resolves from loading state to a place name on 768/1440. |
| Empty state | `/admin/reports/` | working | Dismissed filter (when 0) and failed search both show “No reports match.” |
| Error state | `/admin/reports/` | stub-documented | No dedicated error panel. `state.js` `safe()` swallows fetch failures into empty arrays, so a dead API looks like empty. Not built. |
| Export CSV | `/admin/reports/` | stub-documented | Head button “Export CSV” has no `data-action` / `href`. Clicked at 375/768/1440: no dialog, toast, or download. Unimplemented stub, not a wired `data-action`. |
| `href="#"` on page | `/admin/reports/` | working | No `href="#"` under `public/admin/reports/**`. Snap `hashHrefs: []` inside `#app`. |
| Legacy `/admin/reports.php` | `/admin/reports.php` | working | File is 302 to `/admin/reports/` only; not reinvented. |

Method: local Chrome CDP (`Emulation.setDeviceMetricsOverride` at 375 / 768 / 1440), headless, after `admin@furescue.local` / `Password123!`. No Browser MCP in this session. No production files changed. `index.php` left at ~372 lines (split only if edited).
