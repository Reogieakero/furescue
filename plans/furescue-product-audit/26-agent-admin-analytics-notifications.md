# Agent 26 --- Admin Analytics + Notifications

## Ownership

Owned routes:

-   `/admin/analytics/`
-   `/admin/notifications/`

Owned files:

-   `public/admin/analytics/**`
-   `public/admin/notifications/**`

Analytics is profile-menu only (not a sidebar item). Do not add a
sidebar entry in this audit.

Do not edit shared nav/shell.

## Design system

Reuse `.panel`, `.table`, `.input`, `.input--area`, `.field`,
`.toast`, `.btn-link`. Tokens: `--primary`, `--muted`, `--border`,
`--radius`. Lucide only. `data-export` links stay token-styled.
After CSS edits: `npm run build`. Verify 375 / 768 / 1440.

Page-local `analytics.css` only (no `input.css`). `.range-note` now
uses `hsl(var(--muted-foreground))`. No Tailwind rebuild.

## File size / split

Keep `analytics/js/analytics.js`, `analytics/view.php`, and
`notifications/js/broadcast.js` separate. Flag owned files over ~300
lines; split on edit. Do not merge analytics + broadcast.

Edited `analytics.js` (was 328). Split to:

-   `analytics/js/analytics.js` (~199) entry / export / range
-   `analytics/js/pages/analytics/format.js` (~61)
-   `analytics/js/pages/analytics/render.js` (~72)

`broadcast.js` ~164. `view.php` unchanged and under the line.

## Interaction checklist

Analytics:

-   [x] overview table
-   [x] adoption trends
-   [x] health updates
-   [x] empty states
-   [x] `data-export` CSV downloads actually download
-   [x] date range if present
-   [x] charts render without console errors

Notifications:

-   [x] compose broadcast
-   [x] character count
-   [x] audience select
-   [x] send → recent table updates
-   [x] empty recent list
-   [x] validation (empty message)
-   [x] unread count if shown

## Viewport checklist

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/admin/analytics/` | clicked | clicked | clicked | `.table-wrap` `overflow-x: auto`; page/main `scrollWidth` = viewport. KPI 2-col / 2-col / 6-col. Analytics **not** in sidebar. |
| `/admin/notifications/` | clicked | clicked | clicked | `.cols--two` stacks (1 col) at 375/768; 2-col `600px 600px` at 1440. No page overflow. |

Chrome CDP (`chrome.exe` headless, device metrics 375 / 768 / 1440)
after `admin@furescue.local` / `Password123!`. Browser MCP not in
this session.

## Known debt

-   `href="#"` + `data-export` is **wired `data-action`** (not a
    missing URL). Header export buttons have no href; panel links
    stay `#` + `data-export`.
-   Broadcast is not real-time; list refresh is enough.
-   No canvas / Chart.js on analytics — KPI tiles + tables only.
-   Profile Escape is handled on the dropdown trigger (shared UI);
    this page inits `initDropdownMenu` but does not edit that module.

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| Overview table | `/admin/analytics/` | working | PHP-first 10 metric rows (e.g. Total reports 600). Clicked at 375/768/1440. |
| Adoption trends | `/admin/analytics/` | working | 24 day rows default. Clicked at 375/768/1440. |
| Health updates | `/admin/analytics/` | working | 50 latest rows default. `.table-wrap` scrolls; page does not overflow. |
| Empty states | `/admin/analytics/` | working | Apply `2000-01-01`–`2000-01-02` → “No completed adoptions in this range.” / “No health updates in this range.” Reset restores default label + 10 overview rows. |
| `data-export` CSV | `/admin/analytics/` | broken-fixed | Was dead: `analytics.js` imported missing `../../js/lib/page-auth.js` so the module never loaded. Now `../../../js/lib/page-auth.js`. Click: overview/trends/health export `200` `text/csv` + blob `a.download` + toast “CSV downloaded.” Header `#export-overview` mouse-clicked at 375/768/1440; panel `a[data-export]` mouse at 768/1440 and DOM click at 375. `href="#"` is wired `data-export`, not a missing URL. |
| Date range Apply / Reset | `/admin/analytics/` | working | `#range-apply` / `#range-reset` clicked. Label updates; JSON `200` for overview/trends/updates. |
| Charts / console | `/admin/analytics/` | working | `canvas` count 0 (tables + KPI only). Runtime console errors `[]` at all three widths. |
| Profile dropdown | `/admin/analytics/` | broken-fixed | Owned JS called `initShell()` but not `initDropdownMenu`. Now inits dropdown. Click `#profile-menu [data-dropdown-trigger]` → `dropdownHidden: false`; items Analytics `/admin/analytics/`, Reports `/admin/reports/`, Users `/admin/rescuers/`, Log Out `/auth/logout.php`. Did not edit `topbar.js` / layout. Sidebar still has **no** analytics href. |
| Compose + send | `/admin/notifications/` | broken-fixed | Send was `type="button"` so `#broadcast-send` never ran `send()`. Now `type="submit"` + click handler. 375 click → `POST /api/v1/admin/notifications/broadcast` **201**, toast “Announcement sent to 250 users”. |
| Character count | `/admin/notifications/` | working | `#broadcast-count` `0` then `23` after typing at 375/768. |
| Audience select | `/admin/notifications/` | working | Opened `#broadcast-target`, clicked Residents; label “Residents” at 375/768/1440. |
| Send → recent table | `/admin/notifications/` | broken-fixed | Empty PHP markup had no `#broadcast-rows`, so first send could not refresh. Now `#broadcast-list`. After send: empty megaphone state → table “Agent 26 audit ping 375 / 250 sent”, total `1`. |
| Empty recent list | `/admin/notifications/` | working | Before send: “No broadcasts yet. Compose your first announcement.” `total` 0. |
| Empty-message validation | `/admin/notifications/` | broken-fixed | Same send-button bug. After fix, empty click → toast “Please enter a message”, focus `#broadcast-message` (375 and 768). |
| Unread count | `/admin/notifications/` | working | Sidebar notifications item shows badge `6` (`$navBadges`). No in-page unread list. |
| Profile dropdown | `/admin/notifications/` | broken-fixed | `broadcast.js` did not call `initShell` / `initDropdownMenu`. Now does. Trigger click opens menu at 375/768/1440. |
