# Agent 24 --- Admin Animals

## Ownership

Owned routes:

-   `/admin/animals/`

Owned files:

-   `public/admin/animals/**`

Legacy 302: `public/admin/animals.php`.

Do not edit shared nav/shell. Health-record editor is agent 25.

## Design system

Reuse `.panel`, `.input`, `.toast`, dialog/drawer, Lucide. Tokens:
`--primary`, `--card`, `--border`, `--radius`. After CSS edits:
`npm run build`. Verify 375 / 768 / 1440.

## File size / split

Keep `components/{grid,side,modal,edit,health}.js` separate. Flag
owned files over ~300 lines; split on edit. Do not fold health flyout
into `animals.js`.

Owned sizes this run (none over ~300 after edits):

-   `index.php` 294 — duplicate admin-shell composer (lines ~235–281
    overwrite the first render). Not edited; split candidate for 90.
-   `modal.js` 275 after extracting `resizeImage` / `ageFromBirthDate`
    into `util.js`.
-   `side.js` 120, `health.js` 109, `edit.js` 231, `workflow.js` 107,
    `animals.js` 60.

## Interaction checklist

-   [x] grid/table from PHP first paint
-   [x] filters
-   [x] select animal → side panel
-   [x] create (add modal)
-   [x] edit
-   [x] delete confirm
-   [x] health flyout / save record
-   [x] photo preview
-   [x] pagination if present
-   [x] empty / error
-   [x] JS does not wipe `#app` when PHP filled it

## Viewport checklist

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/admin/animals/` | ok | ok | ok | 375/768: 1-col stack (`343px` / `736px`); 1440: `3fr 1fr`. No horizontal overflow. Select scrolls the side panel into view below 1024px. |

## Known debt

-   Side panel and modals still generate DOM via JS components — allowed.
-   Dual render in `animals.js` (fills `#app` only when empty).
-   `index.php` renders the admin shell twice; leftover audit animal
    `AuditPup24` may remain if a prior create was not deleted.
-   Export CSV has no `data-act` / href (stub).
-   List is first 100 only (`LIMIT 100` / API `per_page=100`); no pager.
-   Health flyout is not the health-record editor (agent 25).

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| PHP first-paint grid | `/admin/animals/` | working | Chrome CDP after login: `#app` 1 child, `__PAGE_STATE__` present, 100 `.animal-card` nodes matching PHP query. `animals.js` only `innerHTML`s `#app` when empty. |
| Filter tabs | `/admin/animals/` | working | Clicked All / Pending / Adopted / All at 375, 768, 1440. Adopted → 7 cards; nonsense search `zzzz-no-match-audit` → `.animal-empty`. |
| Select card → side panel | `/admin/animals/` | broken-fixed | Clicking a card fills `#animal-detail`. At &lt;1024px the panel sits under the grid; `side.js` now `window.scrollTo`s it below the sticky topbar. 375: detail in view, Delete confirm titled “Delete animal?”. |
| Add animal | `/admin/animals/` | working | `[data-act="open-add"]` opened dialog; created `AuditPup24c` (then renamed). Grid count 100→101, card selected, side profile shown. |
| Birth date auto-age | `/admin/animals/` | broken-fixed | Label promised “auto-computes age” with no listener. After fix, DOB 3 years ago set `#aa-age` to `3` and unit tab `yr`. |
| Photo preview | `/admin/animals/` | working | CDP `DOM.setFileInputFiles` on `#aa-photo`; preview `background-image` became `url("data:image/jpeg;base64,...")`. |
| Edit | `/admin/animals/` | broken-fixed | Workflow listened for `[data-act="edit-animal"]` but the side panel had no button. Added Edit. Clicked at 1440: dialog “Edit · AuditPup24c”, saved name `AuditPup24d` (PATCH 200). Same control present at 375/768 after select. |
| Delete confirm | `/admin/animals/` | working | 1440: dialog “Delete animal?”, Cancel left the card, Confirm removed it (99 cards, empty detail). 375: confirm opened, Cancel clicked. DELETE `/api/v1/animals/{id}` 200. |
| Health flyout save | `/admin/animals/` | broken-fixed | Flyout sent `not_vaccinated`/`up_to_date` (invalid ENUM) and a raw string into JSON `vaccination_details` (500). Now `none`/`partial`/`complete` and details as a JSON array. PUT `/medical` 200, toast “Health record saved.”, ribbon “Medical”, button “View medical records”. Editor page not opened (agent 25). |
| Export CSV | `/admin/animals/` | stub-documented | `<button>` with no `href` / `data-act`. Clicked at 1440; no download, URL unchanged. Unimplemented stub, not a `href="#"` `data-action`. |
| Pagination | `/admin/animals/` | stub-documented | No `.pagination` / pager. PHP `LIMIT 100` matches API page 1. No page-2 control. |
| Empty state | `/admin/animals/` | working | Search with no matches showed “No animals match your filters.” at 375/768/1440. |
| Network error banner | `/admin/animals/` | stub-documented | With `__PAGE_STATE__`, JS does not refetch; `.catch(() => {})` only on the no-state fallback. No error panel to click. [uncertain] without forcing API down. |
| `href="#"` on page | `/admin/animals/` | working | Snapshot `hashes: []` on the animals surface (no in-page `#` links). |
| JS wipe `#app` | `/admin/animals/` | working | `hasPageState: true`, `appChildren: 1` after all CRUD; PHP grid not replaced on happy path. |
