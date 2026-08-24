# FurEscue Product Audit Plans

Run `00-master-plan.md` first. Then execute Phase 01 and Phase 02
sequentially. After the standards inventory and shared-UI pass are
stable, the page-agent plans can run in parallel with **strict file
ownership**. Finish with integration and final verification.

Do not invent a new process. Follow `AGENTS.md`, the checklists in
`plans/furescue-admin-refactor-plan/05-component-regression.md` and
`06-final-verification.md`, and the live routes in `public/`.
`docs/technical/FEATURES.md` and `IMPLEMENTATION_AUDIT.md` are stale
(Aug 22) — trust the code.

Hard standards on every file in this pack: (1) style follows
`docs/study/DESIGN_SYSTEM.md` + live tokens in `public/css/input.css`
and `tailwind.config.js`; (2) no file bottlenecks — split past ~300
lines / a second concern instead of growing monoliths.

## Run order

``` text
00-master-plan.md
        ↓
01-standards-scan.md          (serialized, read-only)
        ↓
02-shared-ui.md               (serialized; shared chrome only)
        ↓
10–13 resident + 20–26 admin  (parallel; owned files only)
        ↓
90-integration.md
        ↓
91-final-verification.md
```

## Files

-   `00-master-plan.md`
-   `01-standards-scan.md`
-   `02-shared-ui.md`
-   `10-agent-landing-auth.md`
-   `11-agent-resident-reports.md`
-   `12-agent-resident-adoption.md`
-   `13-agent-resident-comms-learn.md`
-   `20-agent-admin-dashboard.md`
-   `21-agent-admin-reports.md`
-   `22-agent-admin-cases.md`
-   `23-agent-admin-rescuers.md`
-   `24-agent-admin-animals.md`
-   `25-agent-admin-health.md`
-   `26-agent-admin-analytics-notifications.md`
-   `90-integration.md`
-   `91-final-verification.md`
-   `FINDINGS.md` — rollup filled during execution

## Fix vs document

-   **Fix** broken existing actions: wrong/missing `href`, handlers that
    never attach, 404 assets, console errors, clickable controls that
    do nothing.
-   **Document, do not build** missing product surfaces (admin Listings,
    Applications, E-Learning, Messages).
-   `href="#"` is not automatically a bug. Classify: wired
    `data-action`, should be a real URL, or unimplemented feature.
-   Do not change `/api/v1` contracts, the router, or visual design.
-   Do not commit unless asked.

## Demo accounts

Password for all: `Password123!`

-   admin: `admin@furescue.local`
-   resident: `juan@furescue.local`

Server (from repo root):

``` bat
php -S 127.0.0.1:8000 -t public public\index.php
```

## Shared files (serialized — do not edit in parallel)

Page agents must not edit these unless Phase 02 or 90 owns the change:

-   `public/includes/admin-nav.php`
-   `public/includes/admin-shell.php`
-   `public/includes/resident-shell.php`
-   `public/includes/header.php`
-   `public/includes/footer.php`
-   `public/includes/site-head.php`
-   `public/includes/guard.php`
-   `public/js/components/ui/*`
-   `public/js/components/resident-shell.js`
-   `public/css/input.css`
-   `tailwind.config.js`
-   `public/admin/js/layout/*`
