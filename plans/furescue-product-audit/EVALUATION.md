# Evaluation (Done When)

Binary checks for Phase 91 / orchestrator. Tick only with tool-backed evidence (browser click, curl status, or diff). Uncertain → leave unchecked and record in `FINDINGS.md`.

## Process

- [ ] 01 ran first, read-only
- [ ] 02 ran second; admin + resident hamburger/dropdown clicked at 375 / 768 / 1440
- [ ] Page agents 10–13 and 20–26 ran in parallel with owned-file locks (no shared-chrome edits)
- [ ] 90 then 91 ran serialized after page agents returned

## Remaining P1s from prior pass

- [ ] P1-1 `public/admin/index.php` is a thin composer; queues/cards/activity live in `public/admin/partials/`
- [ ] P1-2 `health-record.php` and `health-record/page.js` split into modules/partials (roster `index.php` split if edited)
- [ ] P1-3 Landing no longer loads DM Sans; live Nunito/Fraunces tokens
- [ ] P1-4 Every live route in `91-final-verification.md` has 375 / 768 / 1440 filled from a real browser (or an explicit tools-unavailable gap)
- [ ] P1-5 Dashboard/reports/cases/rescuers queue actions click-tested

## Product rules

- [ ] `/admin/listings/`, `/admin/applications/`, `/admin/elearning/`, `/admin/messages/` documented only (still 404, not built)
- [ ] No `/api/v1`, router, or visual-redesign changes
- [ ] No commit created by this run
- [ ] `href="#"` leftovers classified (wired `data-action` / should-be-URL / unimplemented stub)
- [ ] No new raw hex/radius/font stack where a token exists; Lucide only
- [ ] `npm run build` ran if CSS changed
- [ ] Edited 300+ line files were split

## Pack completeness

- [ ] `01-standards-scan.md` inventory current
- [ ] `02-shared-ui.md` chrome checklists filled
- [ ] Agents 10–13 and 20–26 findings tables filled
- [ ] `90-integration.md` leftover search done
- [ ] `91-final-verification.md` matrix Method column is `browser` where clicked
- [ ] `FINDINGS.md` rollup matches agent files
