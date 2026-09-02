# FurEscue folder architecture

**Status:** IMPLEMENTATION target for the folder-structure overhaul.  
**Not a visual redesign.** Jungle / paper tokens, Tailwind, Leaflet, and page behavior stay.  
**Reference system:** PGSO-PSMS (`../pgso` on this machine). Copy **folder discipline**, not PGSO navy/yellow chrome.

This file is the architecture write-up. Execution rules, URL freeze, and parallel workstreams live in [`FOLDER_OVERHAUL_SPEC.md`](FOLDER_OVERHAUL_SPEC.md). Paste-ready Cursor brief: [`../plans/FOLDER_OVERHAUL_PROMPT.md`](../plans/FOLDER_OVERHAUL_PROMPT.md).

---

## 1. Why PGSO’s tree is the model

PGSO keeps three things separate:

| Concern | Lives where | Rule |
|---------|-------------|------|
| Web root | `public/` | Front controller + static assets only. No fat page trees. |
| Markup | `views/` | Layouts, components, one folder per module. Not web-served. |
| CSS | `public/assets/css/` | One entry file. Locked cascade. One concern per file. |
| Shared JS | `public/assets/js/` | Named by job (`pg-ui-select.js`, `reports-desk.js`). No `js/pages/foo/pages/foo/` nesting. |
| Domain PHP | `src/` | Controllers → Services → Repositories. Views do not query the DB. |

FurEscue already has a decent `src/` API. The mess is **frontend placement**: pages, CSS, and JS all live under `public/`, often twice (legacy `.php` stub + folder), with CSS split across a 1,700-line `input.css` plus per-page `@import` trees, and JS nested as `public/admin/reports/js/pages/reports/workflow/events.js`.

The overhaul applies PGSO’s **placement rules** to FurEscue’s **existing stack** (PHP pages + Tailwind + ES-module islands + JWT/session). It does not turn FurEscue into a second PGSO product.

---

## 2. PGSO reference (as built)

Observed in `C:/Users/nicol/OneDrive/Documents/reagan/pgso`. Study text: `pgso/docs/06-architecture.md`. Live CSS entry: `pgso/public/assets/css/app.css`.

### 2.1 Logical layers

```text
Browser (HTML + CSS + vanilla JS)
        │
public/index.php          front controller
        │
   Controller             parse input, call one Service, return view or JSON
        │
   Service                business rules
        │
   Repository             the only place SQL/PDO is written
        │
   MySQL
```

- Controllers do not write SQL.
- Services do not print HTML.
- Repositories do not decide who may act.
- Views do not query the database.

FurEscue’s **API** already follows this (`src/Controllers`, `src/Services`, `src/Repositories`). FurEscue **pages** currently break it: many `public/**/index.php` files connect PDO and run SQL. Phase 1 of this overhaul does **not** move that SQL into controllers. It only relocates files. SQL extraction is a later job unless a move forces a thin bootstrap.

### 2.2 PGSO folder tree

```text
pgso/
├── public/                 web root (only this is served)
│   ├── index.php           front controller
│   ├── router.php          PHP built-in server helper
│   └── assets/
│       ├── css/            single cascade (see §3)
│       └── js/             shared widgets + vendor/
├── views/
│   ├── layouts/            app.php, guest.php, print.php
│   ├── components/         one widget per file
│   └── <module>/           index / show / create / …
├── src/
│   ├── Core/               router, PDO, View, bootstrap, CSRF
│   ├── Controllers/
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   ├── Validation/
│   └── Support/
├── routes/web.php
├── config/
├── database/migrations/
├── storage/                not web-accessible
├── tests/                  Unit / Integration / Http
└── docs/
```

`View::render('reports/index', $data, 'layouts/app')` loads `views/reports/index.php` and wraps it in `views/layouts/app.php`. Layouts link **one** stylesheet: `/assets/css/app.css`.

### 2.3 PGSO CSS cascade (the part to copy)

`public/assets/css/app.css` is an entry file only. Comment in repo: *“Cascade order is locked.”*

```text
tokens.css
base.css
brand.css
guest.css
landing.css
auth.css
components/buttons.css
components/cards.css
components/forms.css
components/badges.css
components/chips.css
components/misc.css
components/tables.css
components/alerts.css
components/toast.css
components/header.css
components/toolbar.css
components/link.css
components/ui-btn.css
components/modal.css
components/popover.css
components/surface.css
components/need.css
components/stat.css
components/chart.css
components/sheet.css
components/empty.css
components/daterange.css
components/calendar.css
components/pager.css
components/select.css
shell.css
dashboard.css
reports.css
responsive.css
print.css
```

Rules that made this tree work:

1. **Tokens first.** Primitive hex → semantic purpose → component chrome. Views use `var(--…)`, not raw hex.
2. **One concern per file.** Select styles live in `components/select.css`, not inside `reports.css`.
3. **Page layers after components.** `dashboard.css` / `reports.css` only hold page-unique layout.
4. **Responsive and print last.**
5. **Layouts keep linking one URL.** Adding a component means a new file + one `@import` line, not a new `<link>` on every page.
6. **Do not restyle shared chrome** unless asked. Scope page work under a page class (`body.is-login`, `.landing-page`).

PGSO still has a small Tailwind compile (`tw-input.css` → `tw-forms.css`) for form utilities. The **app look** is the vanilla cascade, not Tailwind-as-app-style.

### 2.4 PGSO JS

Shared files under `public/assets/js/`: `pg-ui.js`, `pg-ui-select.js`, `pg-ui-sheet.js`, `pg-ui-calendar.js`, `flash-toast.js`, `reports-desk.js`, `dashboard-charts.js`, `vendor/chart.umd.min.js`.

No `js/pages/<page>/pages/<page>/workflow/` tunnel. Page-specific behavior is a short named file. Widgets used on two pages live in the shared folder.

### 2.5 PGSO views

| Folder | Role |
|--------|------|
| `views/layouts/` | App chrome (sidebar + main), guest, print |
| `views/components/` | `select-filter`, `page-header`, `need-card`, `pager`, `toolbar`, … |
| `views/<module>/` | That module’s screens only |

---

## 3. FurEscue today (facts)

Single server: `php -S 127.0.0.1:8000 -t public public\index.php`.

`public/index.php` (1) serves `/` via `includes/homepage.php`, (2) returns real files under `public/`, (3) requires `public/<dir>/index.php` for directory URLs, (4) streams `/uploads/…`, (5) else dispatches `/api/v1` through `App\Http\Router`.

### 3.1 Current tree (compressed)

```text
furescue/
├── public/
│   ├── index.php
│   ├── css/                    Tailwind input.css + compiled style.css (monolith)
│   ├── js/                     shared lib + ui components
│   ├── includes/               site-head, shells, guard, homepage
│   ├── admin/
│   │   ├── index.php           dashboard (fat)
│   │   ├── *.php               leftover 302 stubs (reports.php, cases.php, …)
│   │   ├── css/admin.css       @import of 15 numbered partials
│   │   ├── js/                 dashboard + layout + admin-data
│   │   ├── includes/           dashboard-data, ui-helpers
│   │   ├── partials/           dashboard sections
│   │   └── <page>/             index.php + js/pages/<page>/… + local css
│   ├── landing/                css/partials + js + components
│   ├── auth/                   login.php, signup.php, css/partials
│   ├── account|adoptions|animals|cases|listings|
│   │   learning|messages|notifications|report|reports/
│   └── uploads/
├── src/                        API (keep)
├── migrations/  seeders/  tests/  bin/  dbtool/
└── docs/
```

### 3.2 What is already good

- One web root. Same-origin `/api/v1`.
- Folder-per-page started: `/admin/reports/`, `/admin/cases/`, resident `/animals/`, etc.
- Shared chrome: `site-head.php`, `admin-shell.php`, `resident-shell.php`, `guard.php`.
- Shared JS primitives: `public/js/lib/*`, `public/js/components/ui/*`.
- `src/Http/Routes/*` per domain.
- Design tokens exist (`:root` / `.dark` in `input.css`). Lucide only.

### 3.3 What is wrong (folder only)

| Smell | Example |
|-------|---------|
| CSS monolith | `public/css/input.css` (~1,700 lines): tokens + reset + `.input` + toast + resident shell + more |
| Second CSS trees | `admin/css/partials/01_…15_`, `landing/css/partials/`, `auth/css/partials/`, plus page CSS files |
| JS tunnel | `admin/reports/js/pages/reports/workflow/events.js` |
| Split homes | Dashboard JS still in `admin/js/`; other admin pages in `admin/<page>/js/` |
| Dual entries | `admin/reports.php` 302 → `admin/reports/`; same for animals, cases, rescuers, health-records, health-record, case-detail |
| Views in the web root | Markup, data PHP, and assets mixed under `public/` |
| Tailwind glob drift | `tailwind.config.js` must list every page glob or classes purge |
| Stale docs | `ARCHITECTURE_AUDIT.md` still describes `backend/` + `frontend/` |

---

## 4. FurEscue target tree

Adapt PGSO. Keep FurEscue’s file-serve pages and Tailwind build.

```text
furescue/
├── public/                              web root
│   ├── index.php                        front controller (behavior unchanged)
│   ├── favicon.png
│   ├── assets/
│   │   ├── css/
│   │   │   ├── input.css                BUILD ENTRY (Tailwind + locked @imports)
│   │   │   ├── style.css                compiled, git-tracked
│   │   │   ├── tokens.css               :root / .dark only (FurEscue values)
│   │   │   ├── base.css                 reset, html/body, lucide, images
│   │   │   ├── brand.css                logo-mark, brand wordmark
│   │   │   ├── guest.css                marketing/guest chrome
│   │   │   ├── landing.css              landing page layer (or thin re-export)
│   │   │   ├── auth.css
│   │   │   ├── shell.css                admin-shell + resident-shell
│   │   │   ├── components/              one widget per file (see §5)
│   │   │   ├── pages/                   page-unique layers only
│   │   │   ├── responsive.css
│   │   │   └── print.css
│   │   ├── js/
│   │   │   ├── lib/                     api, page-auth, swr, format, csv, utils, notification-stream
│   │   │   ├── components/              kpi-card, resident-shell, ui/*
│   │   │   └── admin/                   app-shell, sidebar, topbar, admin-data, location-drawer
│   │   └── img/                         if/when shared images leave page folders
│   ├── admin/<page>/                    thin bootstrap + page JS (flattened)
│   │   ├── index.php
│   │   ├── <subpage>.php                e.g. case-detail.php, health-record.php, view.php
│   │   └── js/                          <page>.js, components/, workflow/, state.js
│   ├── <resident-page>/                 same pattern
│   ├── auth/                            login.php, signup.php, logout.php
│   └── uploads/                         runtime files (do not relocate in this overhaul)
├── views/
│   ├── layouts/                         admin.php, resident.php, guest.php
│   ├── components/                      site-head, admin-nav, flash, …
│   ├── admin/<page>/                    page markup + partials
│   ├── home/                            landing body (from includes/homepage.php)
│   └── <resident-page>/
├── src/                                 DO NOT re-layer unless a path breaks
├── migrations/  seeders/  tests/  bin/  dbtool/
├── tailwind.config.js                   content globs updated
├── package.json                         -i/-o paths updated
└── docs/
```

### 4.1 What stays put

| Path | Why |
|------|-----|
| `src/**` | API already layered |
| `migrations/`, `seeders/`, `bin/`, `tests/`, `dbtool/` | Not a frontend-folder job |
| `public/uploads/` | Runtime URLs `/uploads/…` |
| `public/index.php` dispatch rules | File serve + dir `index.php` + API |
| Token **values** | `docs/study/DESIGN_SYSTEM.md` |

### 4.2 Page-folder contract (after overhaul)

Canonical URL remains `/admin/reports/` (directory + `index.php`).

```text
public/admin/reports/index.php     thin: guard, data, include view, site-head, script tags
views/admin/reports/index.php      markup
views/admin/reports/partials/      if the page has sections
public/admin/reports/js/reports.js entry module
public/admin/reports/js/components/
public/admin/reports/js/workflow/
public/admin/reports/js/state.js
```

Forbidden after overhaul:

```text
public/admin/reports/js/pages/reports/workflow/events.js
```

Shared CSS is **not** stored inside the page folder. Page-unique CSS goes in `public/assets/css/pages/<name>.css` and is imported from `input.css` or listed once in `$pageCss` as `/assets/css/pages/<name>.css`.

### 4.3 Import style (locked)

Shared JS: **absolute** from web root.

```js
import { requireAuth } from "/assets/js/lib/api.js";
import { initShell } from "/assets/js/admin/app-shell.js";
```

Page-local JS: **relative**.

```js
import { ReportsPage } from "./components.js";
import { initReportsEvents } from "./workflow.js";
```

Do not keep `../../../js/lib/api.js` after the move.

---

## 5. FurEscue CSS cascade (target)

Build stays Tailwind CLI:

```bat
npx tailwindcss -i ./public/assets/css/input.css -o ./public/assets/css/style.css --minify
```

`views` + `public` PHP/JS must appear in `tailwind.config.js` `content`, or utilities purge.

### 5.1 `input.css` (entry only)

Order is locked. New shared CSS = new file + one import line.

```text
@tailwind base;
@tailwind components;
@tailwind utilities;

tokens.css              FurEscue :root / .dark (move off the monolith; same variable names)
base.css
brand.css
guest.css
landing.css             landing sections (today landing/css/partials/*)
auth.css                auth/css/partials/*
components/*.css        extracted from input.css + admin partials that are widgets
shell.css               admin sidebar/topbar + resident-shell (.rside, .rtop, …)
pages/*.css             dashboard, reports, cases, health-record, animals, …
responsive.css
print.css
```

### 5.2 Component files to extract from today’s `input.css`

Minimum split (names can match classes):

| File | Classes / block |
|------|-----------------|
| `components/icons.css` | `.lucide`, `.badge-icon`, `.icon` |
| `components/forms.css` | `.input`, `.field`, `.file-attach` |
| `components/toast.css` | `.toast*` |
| `components/loader.css` | `.loader-overlay`, `.loader-box` |
| `components/kpi.css` | `.kpi-grid`, `.kpi-card*` (keep API in DESIGN_SYSTEM.md) |
| `components/buttons.css` | shared `.btn` if not Tailwind-only |
| `components/badges.css` | `.badge`, `.stamp` |
| `components/cards.css` | shared card surfaces |
| `components/tables.css` | table wrap / admin tables |
| `components/drawer.css` | location drawer |
| `components/skeleton.css` | from admin `09_skeleton.css` |
| `components/dialog.css` | if present as raw CSS |

Admin numbered partials that are **page-unique** (health carousel, case-detail, animals flyout) become `pages/…`, not `components/`.

### 5.3 `$pageCss` after the move

`site-head.php` always links `/assets/css/style.css`. Extra CSS is only for **vendor** (Leaflet) or a page layer not yet folded into the cascade.

During the move, a shim is allowed:

- Keep `public/css/style.css` as a one-line `@import` of `/assets/css/style.css`, **or**
- Update every `href="/css/style.css"` in the same change set.

Do not leave two competing compiled sheets.

### 5.4 What CSS must not do

- Do not copy PGSO `--navy` / `--brand-yellow` into FurEscue.
- Do not invent a second palette.
- Do not restyle `/` or admin chrome unless a path change forces a selector update.
- Do not drop Tailwind. FurEscue pages use utility classes; PGSO’s “no Tailwind-as-app-style” rule does **not** apply here.

---

## 6. Views vs public (target)

| Today | Target |
|-------|--------|
| `public/includes/site-head.php` | `views/components/site-head.php` |
| `public/includes/admin-shell.php` | `views/layouts/admin.php` |
| `public/includes/resident-shell.php` | `views/layouts/resident.php` |
| `public/includes/admin-nav.php` | `views/components/admin-nav.php` |
| `public/includes/guard.php` | `views/components/guard.php` (or `src` later; keep include API) |
| `public/includes/homepage.php` | `views/home/landing.php` (`/` still special-cased in `public/index.php`) |
| `public/includes/header.php` / `footer.php` | `views/components/guest-header.php` / `guest-footer.php` |
| `public/admin/partials/*` | `views/admin/dashboard/partials/` |
| `public/admin/<page>/partials/*` | `views/admin/<page>/partials/` |
| `public/admin/<page>/includes/*` | `views/admin/<page>/` data helpers **or** stay next to thin bootstrap if they only run PHP |

`public/index.php` must keep serving `/` by requiring the landing view. Do not change the `/` URL.

Thin bootstrap example (target, not a rewrite of business rules):

```php
<?php
$requiredRole = 'admin';
require dirname(__DIR__, 3) . '/views/components/guard.php';
// … existing data load …
ob_start();
require dirname(__DIR__, 3) . '/views/admin/reports/index.php';
$adminChildren = ob_get_clean();
require dirname(__DIR__, 3) . '/views/layouts/admin.php';
```

Exact require depth depends on the page. Prefer a small helper (`views_path('admin/reports/index')`) if more than three pages need the same join — only if it already exists or the lead agent adds one tiny function. Do not add a framework.

---

## 7. JS target map (shared)

| Today | Target |
|-------|--------|
| `public/js/lib/*` | `public/assets/js/lib/*` |
| `public/js/components/*` | `public/assets/js/components/*` |
| `public/admin/js/lib/admin-data.js` | `public/assets/js/admin/admin-data.js` |
| `public/admin/js/lib/location-drawer.js` | `public/assets/js/admin/location-drawer.js` |
| `public/admin/js/layout/app-shell.js` | `public/assets/js/admin/app-shell.js` |
| `public/admin/js/layout/sidebar.js` | `public/assets/js/admin/sidebar.js` |
| `public/admin/js/layout/topbar.js` | `public/assets/js/admin/topbar.js` |
| `public/admin/js/dashboard.js` + `pages/dashboard/*` | `public/admin/js/` flattened: `dashboard.js`, `components/`, `workflow/` if needed, `state.js` — dashboard is the `/admin/` page, so `public/admin/js/` is the page folder (no extra `pages/dashboard/`) |
| `public/admin/<page>/js/pages/<page>/*` | `public/admin/<page>/js/*` |
| `public/landing/js`, `public/landing/components` | `public/landing/js/` (page-local) or `assets/js` only if reused |

One-release shims: `public/js/lib/api.js` may re-export `/assets/js/lib/api.js` until every import is rewritten. Delete shims when grep is clean.

---

## 8. Backend / API

Out of scope for folder overhaul unless a moved page breaks a hardcoded path.

Keep:

- `/api/v1` envelope `{ success, data, error }`
- `src/Http/Routes/*` + `RouteLoader`
- Auth: session pages + JWT `localStorage` islands
- `POST /api/v1/vitals` device key
- `DEDUP_*` and Mati bounds env

Do not add Laravel, React, a second CSS framework, or PGSO’s `routes/web.php` page router in this pass.

---

## 9. Docs that must be updated when the tree moves

| File | Update |
|------|--------|
| `AGENTS.md` | Paths: CSS entry, page folders, includes → views |
| `README.md` | Repository layout |
| `docs/technical/HOW_TO_RUN.md` | `npm run build` paths |
| `docs/study/DESIGN_SYSTEM.md` | Token file path (`assets/css/tokens.css`) |
| This file | “today” section becomes historical if needed |

`ARCHITECTURE_AUDIT.md` is a 2026-08-22 snapshot of a deleted `backend/` + `frontend/` tree. Do not treat it as the live map. Do not rewrite it unless asked.

---

## 10. Success picture

A new page is added by:

1. `public/admin/<page>/index.php` — thin bootstrap  
2. `views/admin/<page>/index.php` — markup  
3. `public/admin/<page>/js/<page>.js` — entry  
4. Shared look → `public/assets/css/components/<widget>.css` + one `@import`  
5. Page-only look → `public/assets/css/pages/<page>.css`  
6. `npm run build`  
7. URLs and `__PAGE_STATE__` / `requireAuth` behavior unchanged  

That is the PGSO lesson: **placement is obvious; cascade is locked; one concern per file.**
