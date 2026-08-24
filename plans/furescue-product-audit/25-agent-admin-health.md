# Agent 25 --- Admin Health Records

## Ownership

Owned routes:

-   `/admin/health-records/`
-   `/admin/health-records/health-record.php`

Owned files:

-   `public/admin/health-records/**`

Legacy 302: `public/admin/health-records.php`,
`public/admin/health-record.php`.

Do not edit shared nav/shell. Do not restyle. If a bugfix requires
editing `health-record.php` or `health-record/page.js` (already over
~300 lines), split the concern you touch rather than growing the
monolith.

## Design system

Reuse `.panel`, `.input`, `.toast`, `.loader-overlay`, Lucide.
Tokens: `--primary`, `--destructive`, `--border`, `--radius`. After
CSS edits: `npm run build`. Verify 375 / 768 / 1440.

## File size / split

Over ~300: `health-record/page.js` (~1288), `index.php` (~655),
`health-record.php` (~589–647). **Must split** any of these this
agent edits. Extract editor sections (vaccinations, vitals,
documents) into `health-records/js/pages/health-record/` modules and
PHP `partials/`. Size here blocks later audits — split if the file
must be opened.

## Interaction checklist

Roster (`index.php`):

-   [ ] KPIs
-   [ ] charts
-   [ ] queue
-   [ ] table / filters / tabs
-   [ ] animals flyout
-   [ ] open record

Editor (`health-record.php`):

-   [ ] PHP first paint (do not require JS to see the page chrome)
-   [ ] save demographics / status
-   [ ] vaccinations add/edit/list
-   [ ] vitals add/edit/list
-   [ ] documents upload/edit
-   [ ] post-for-adoption
-   [ ] delete
-   [ ] missing id → error, not a silent blank

## Viewport checklist

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/admin/health-records/` | | | | |
| `/admin/health-records/health-record.php` | | | | editor sections stack |

## Known debt

-   `health-record.php` ~647 lines; `page.js` >1000 lines — document,
    split only if editing.
-   Dual `app.innerHTML` in `health-records.js` / `health-record.js`.

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| | | working / broken-fixed / stub-documented | |
