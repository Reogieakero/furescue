# FurEscue Product Audit --- Master Plan

## Objective

Verify every live FurEscue surface so that every real button, form, and
nav control either works or is classified. Fix broken *existing*
actions. Document missing product surfaces. Do not build new pages.

Core rule (from `AGENTS.md`):

-   PHP renders the initial page HTML.
-   JavaScript provides behavior only.
-   Folder-per-page under `public/<page>/`.
-   Lucide icons and design tokens in `public/css/input.css`.
-   Verify 375 / 768 / 1440 before a page counts as done.
-   Existing `/api/v1` contracts remain unchanged.
-   Existing visual design remains intact.

### Design system (hard)

Source of truth: `docs/study/DESIGN_SYSTEM.md` plus the **live** tokens
in `public/css/input.css` (`:root` / `.dark`) and the Tailwind map in
`tailwind.config.js`. AGENTS.md “Frontend design system” applies.

The study doc names palette roles (Primary Green `#3D7432`, Secondary
`#F6F6F6`, White, Tertiary `#E8E4E4`, Dark Blue `#1A1A2E`, Sage
`#B6C0B4`) and **DM Sans**. Those names are the role vocabulary. Live
implementation **supersedes** the study hex/font: do not paste study
hex into pages.

Live tokens agents must use (cite these in checklists):

-   Color roles: `--background`, `--foreground`, `--card`, `--primary`,
    `--secondary`, `--muted`, `--accent`, `--destructive`, `--border`,
    `--input`, `--ring`
-   Brand / paper: `--paper`, `--paperdark`, `--ink`, `--jungle`,
    `--jungle2`, `--coral`, `--teal`, `--stamp`, `--brand-1`,
    `--brand-2`, `--surface-soft`
-   Shape / elevation: `--radius`, `--shadow-sm`, `--shadow-md`
-   Type: `--font-sans` (Nunito), `--font-display` (Fraunces),
    `--font-mono` (IBM Plex Mono) — mapped in `tailwind.config.js` as
    `font-sans` / `font-display` / `font-mono`
-   Shared classes: `.input`, `.input--area`, `.field`, `.field-label`,
    `.toast`, `.loader-overlay`, `.logo-mark`, `.badge-icon`,
    `.admin-shell` / `.sidebar` / `.topbar`, `.resident-shell` /
    `.rside` / `.rbtn` / `.rcard` / `.rpage-title`

Every checklist requires:

-   Colors, radius, shadows, fonts, spacing from tokens — never raw
    hex/hsl/one-off radii/font stacks when a token exists.
-   Icons: Lucide only (`data-lucide` + `lucide.createIcons()`). No
    inline SVG or emoji as icons.
-   Reuse existing patterns — do not restyle from scratch.
-   New reusable style = add it to `input.css` first, then use the
    token (and `tailwind.config.js` if it needs a utility).
-   After CSS/token edits: `npm run build`.
-   Verify 375 / 768 / 1440.

### No file bottlenecks (hard)

`AGENTS.md`: one concern per file; split when a file grows past ~300
lines, accumulates a second responsibility, or duplicates markup/logic
used elsewhere.

Long monoliths waste tokens because later agents re-read huge files.
Prefer many small files so later work can open only the relevant
module.

Every agent must:

-   Flag owned files over ~300 lines (e.g.
    `public/admin/health-records/health-record.php` is 647 lines).
-   When touching a page, split if it is already too long or mixing
    unrelated concerns.
-   Frontend: page folder + page-local `js/` / `css/` / `partials/`;
    shared bits in `public/includes/` and `public/js/lib|components/`.
-   Do not create a new monolith while fixing buttons.
-   If a file is over the line **and** the agent is already editing it,
    split as part of the fix. If over the line but unrelated to a
    functional bug, document as a split candidate — unless the size
    blocks the audit, then split.

## What “the standard” means

This audit follows existing sources — it does not invent a new QA
process:

-   `AGENTS.md` (including Frontend design system + Modular files)
-   `docs/study/DESIGN_SYSTEM.md` + live `input.css` / `tailwind.config.js`
-   `plans/furescue-admin-refactor-plan/05-component-regression.md`
-   `plans/furescue-admin-refactor-plan/06-final-verification.md`

Trust live routes over `docs/technical/FEATURES.md` and
`IMPLEMENTATION_AUDIT.md` (stale Aug 22).

## Live route map

Resident / public:

-   `/` — landing (`public/includes/homepage.php`)
-   `/auth/login.php`, `/auth/signup.php`, `/auth/logout.php`
-   `/report/`, `/reports/`
-   `/animals/`, `/animals/detail.php`
-   `/adoptions/`, `/listings/`
-   `/learning/`, `/messages/`, `/notifications/`

Admin (folder URLs; legacy `public/admin/*.php` 302 to folders):

-   `/admin/`
-   `/admin/reports/`
-   `/admin/cases/`, `/admin/cases/case-detail.php`
-   `/admin/rescuers/`
-   `/admin/animals/`
-   `/admin/health-records/`, `/admin/health-records/health-record.php`
-   `/admin/analytics/` (profile menu, not sidebar)
-   `/admin/notifications/`

Admin sidebar targets that have **no folder** (known debt, do not
build):

-   `/admin/listings/`
-   `/admin/applications/`
-   `/admin/elearning/`
-   `/admin/messages/`

## Ownership map

| Agent | Routes | Owned files |
| --- | --- | --- |
| 01 scan | repo-wide | none (read-only) |
| 02 shared UI | chrome | `public/js/components/ui/*`, shells, `resident-shell.js`, `admin/js/layout/*` — compatibility only |
| 10 | `/`, auth | `public/includes/homepage.php`, `public/landing/**`, `public/auth/**`, `public/includes/header.php`, `public/includes/footer.php` |
| 11 | `/report/`, `/reports/` | `public/report/**`, `public/reports/**` |
| 12 | animals, adoptions, listings | `public/animals/**`, `public/adoptions/**`, `public/listings/**` |
| 13 | learning, messages, notifications | `public/learning/**`, `public/messages/**`, `public/notifications/**` |
| 20 | `/admin/` | `public/admin/index.php`, `public/admin/js/dashboard.js`, `public/admin/js/pages/dashboard/**` |
| 21 | `/admin/reports/` | `public/admin/reports/**` |
| 22 | cases + detail | `public/admin/cases/**` |
| 23 | `/admin/rescuers/` | `public/admin/rescuers/**` |
| 24 | `/admin/animals/` | `public/admin/animals/**` |
| 25 | health records | `public/admin/health-records/**` |
| 26 | analytics + admin notifications | `public/admin/analytics/**`, `public/admin/notifications/**` |
| 90 | cross-page | leftover `href="#"`, dual PHP/JS render, dead nav |
| 91 | all live routes | verification only |

Agent 10 may edit landing header/footer because those are landing-only
partials, not the admin/resident shells. Agents 11–13 must not edit
`resident-shell.php`. Agents 20–26 must not edit `admin-nav.php` or
`admin-shell.php`.

## Execution model

``` text
Phase 01: Standards scan (read-only)
        ↓
Phase 02: Shared UI regression
        ↓
Phase 10–26: Parallel page audits (fix in owned files)
        ↓
Phase 90: Integration cleanup
        ↓
Phase 91: Final browser verification
```

Phases 01, 02, 90, and 91 are serialized. Page agents run in parallel
after Phase 02.

## Non-goals

Do not:

-   change the router
-   redesign the API or JSON envelopes
-   introduce a JS framework or new component library
-   rewrite the visual design
-   duplicate shared UI components per page
-   build admin Listings / Applications / E-Learning / Messages pages
-   perform 3D-profiling product work
-   add real-time notifications
-   commit unless asked
-   create markdown outside `plans/furescue-product-audit/`

## Completion criteria

The audit is complete when:

-   every live route has a findings table (`working` /
    `broken-fixed` / `stub-documented`)
-   broken existing actions in owned files are fixed
-   missing surfaces are documented, not built
-   leftover `href="#"` is classified
-   shared components still open, close, focus, and fire events
-   design-system + file-split checklists were applied on every route
-   files this audit edited that were already over ~300 lines were
    split (or recorded as blocking split candidates)
-   375 / 768 / 1440 were attempted for every live route (browser or
    closest substitute, with gaps recorded)
-   no `/api/v1` or router changes were made

## Findings classification

Use exactly these labels in every agent file and in `FINDINGS.md`:

-   `working` — control does what the live UI promises
-   `broken-fixed` — existing action was broken; this audit repaired it
-   `stub-documented` — control is a placeholder or points at a surface
    that does not exist; documented, not built
