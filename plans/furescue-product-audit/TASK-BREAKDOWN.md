# Task breakdown (Multitask spawn)

Orchestrator runs phases 01, 02, 90, 91 itself. Spawn **one subagent per row** only after Phase 02 completes. Paste `CONSTRAINTS.md` plus the agent file path into each subagent. Do not let two agents own the same path.

## Serialized (orchestrator)

| Phase | File | Mode |
| --- | --- | --- |
| 01 | `01-standards-scan.md` | read-only inventory |
| 02 | `02-shared-ui.md` | shared chrome only; real browser 375/768/1440 |
| 90 | `90-integration.md` | leftovers after all page agents return |
| 91 | `91-final-verification.md` | full live-route browser matrix |

## Parallel page agents

Each subagent: read its `.md` + owned source → click the interaction checklist at 375/768/1440 → fix owned broken actions → split if editing a 300+ line file → fill findings + viewport tables → return evidence.

| ID | Agent file | Routes | Owned glob | Remaining this run |
| --- | --- | --- | --- | --- |
| 10 | `10-agent-landing-auth.md` | `/`, `/auth/login.php`, `/auth/signup.php`, `/auth/logout.php` | `public/includes/homepage.php`, `header.php`, `footer.php`, `public/landing/**`, `public/auth/**` | Align landing font to Nunito/Fraunces (`$fontsHref` + `landing/css/partials/00_tokens.css`); click hamburger + auth |
| 11 | `11-agent-resident-reports.md` | `/report/`, `/reports/` | `public/report/**`, `public/reports/**` | Empty findings; Leaflet + submit click pass |
| 12 | `12-agent-resident-adoption.md` | `/animals/`, `/animals/detail.php`, `/adoptions/`, `/listings/` | `public/animals/**`, `public/adoptions/**`, `public/listings/**` | Empty findings; Apply modal; do not build admin listings |
| 13 | `13-agent-resident-comms-learn.md` | `/learning/`, `/messages/`, `/notifications/` | `public/learning/**`, `public/messages/**`, `public/notifications/**` | Empty findings; no admin elearning/messages pages |
| 20 | `20-agent-admin-dashboard.md` | `/admin/` | `public/admin/index.php`, `js/dashboard.js`, `js/pages/dashboard/**`, `includes/dashboard-data.php`, `partials/**` | Split `index.php` into partials; click queue `data-action`s |
| 21 | `21-agent-admin-reports.md` | `/admin/reports/` | `public/admin/reports/**` | Empty findings; drawer verify/dismiss/assign; split `index.php` if edited |
| 22 | `22-agent-admin-cases.md` | `/admin/cases/`, `case-detail.php` | `public/admin/cases/**` | Empty findings; real case id; proof POST to live API from owned JS only |
| 23 | `23-agent-admin-rescuers.md` | `/admin/rescuers/` | `public/admin/rescuers/**` | Empty findings; approve/reject/suspend/duty clicks |
| 24 | `24-agent-admin-animals.md` | `/admin/animals/` | `public/admin/animals/**` | Empty findings; keep grid/side/modal/edit/health modules separate |
| 25 | `25-agent-admin-health.md` | `/admin/health-records/`, `health-record.php` | `public/admin/health-records/**` | Split `health-record.php`, `page.js`, roster `index.php`; click editor sections |
| 26 | `26-agent-admin-analytics-notifications.md` | `/admin/analytics/`, `/admin/notifications/` | `public/admin/analytics/**`, `public/admin/notifications/**` | Empty findings; CSV download + broadcast send; no sidebar add |

## Subagent prompt skeleton

```
Read plans/furescue-product-audit/CONSTRAINTS.md and [AGENT FILE].
You own only the files listed in that agent file. Do not edit shared chrome.
Log in with the demo account for your surface (admin@furescue.local or juan@furescue.local, Password123!).
Click every checklist item at 375, 768, and 1440. Fix broken existing actions in owned files. Split if you edit a 300+ line file.
Fill the findings table and viewport checklist in the agent file.
Do not commit. Do not change /api/v1 or the router.
Return: classifications, files changed, unverified items with reason.
```
