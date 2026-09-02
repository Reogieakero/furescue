# FurEscue folder-overhaul requirements

**Job:** Reorganize folders, CSS, and JS to match [`FOLDER_ARCHITECTURE.md`](FOLDER_ARCHITECTURE.md).  
**Not the job:** New features, visual redesign, API rewrite, dropping Tailwind, copying PGSO colors.

Read the architecture file first. This file is the keep-list, URL freeze, inventory, and parallel workstream contract so nothing is dropped.

---

## 1. Goal

FurEscue keeps every current screen, URL, API, session/JWT behavior, and token **value**. Files move into a PGSO-shaped tree: `public/assets/` for shared CSS/JS, `views/` for markup/layouts, flattened page JS, one locked CSS cascade.

---

## 2. Hard constraints

### MUST

- Preserve every URL in §4 (including leftover 302 stubs until inbound links are gone).
- Preserve `__PAGE_STATE__`, `requireAuth`, `guard.php` role checks, `window.FURESCUE_API_BASE_URL`.
- Preserve Leaflet + leaflet.heat script/CSS tags on pages that have them.
- Preserve import maps (clsx, tailwind-merge, CVA, lucide) in `site-head`.
- Preserve Lucide-only icons.
- After CSS moves: `npm run build` and update `package.json` + `tailwind.config.js` `content` globs so **no utility class purges**.
- One concern per file. Split past ~300 lines. No new monoliths.
- Verify 375 / 768 / 1440 on every page whose CSS or layout include path changed. Zero horizontal overflow at 375px.
- Ground progress claims in tool output (grep, build, tests).

### MUST NOT

- Change `src/` API contracts, migrations, seeders, or `dbtool/` (unless a frontend path string is hardcoded there — then fix the string only).
- Change risk/business rules, auth hashing, or env names.
- Restyle landing, login, or admin chrome “while we are here”.
- Introduce React, Vue, Alpine, a second CSS framework, or PGSO navy/yellow.
- Delete a file that still has inbound references.
- Rewrite page SQL into controllers in this pass (out of scope).
- Edit `.env` or commit secrets.
- Skip hooks or force-push.

### Stop and ask

- Deleting leftover 302 stubs after grep is clean (confirm once).
- Adding a new npm/Composer dependency.
- Changing a public URL instead of adding a shim.
- Moving `public/uploads/`.

---

## 3. Compatibility shims (required during the move)

| Old path | Shim |
|----------|------|
| `/css/style.css` | Either keep compiling here **or** leave a file that `@import`s `/assets/css/style.css` until every `<link>` is updated in the same change set |
| `/css/input.css` | Source moves to `public/assets/css/input.css`; do not edit compiled `style.css` by hand |
| `/js/lib/…`, `/js/components/…` | Re-export files at the old path until grep shows zero importers, then delete |
| `/admin/css/admin.css` | Re-export or redirect `$pageCss` to the cascade; do not leave pages unstyled |
| `/admin/*.php` stubs | Keep 302 until §4.2 greps are clean |

---

## 4. URL freeze

### 4.1 Canonical pages (must still work)

**Guest / resident**

| URL | Entry today |
|-----|-------------|
| `/` | `public/index.php` → `public/includes/homepage.php` |
| `/auth/login.php` | `public/auth/login.php` |
| `/auth/signup.php` | `public/auth/signup.php` |
| `/auth/logout.php` | `public/auth/logout.php` |
| `/account/` | `public/account/index.php` |
| `/adoptions/` | `public/adoptions/index.php` |
| `/animals/` | `public/animals/index.php` |
| `/animals/detail.php` | `public/animals/detail.php` |
| `/cases/` | `public/cases/index.php` |
| `/cases/detail.php` | `public/cases/detail.php` |
| `/listings/` | `public/listings/index.php` |
| `/learning/` | `public/learning/index.php` |
| `/messages/` | `public/messages/index.php` |
| `/notifications/` | `public/notifications/index.php` |
| `/report/` | `public/report/index.php` |
| `/reports/` | `public/reports/index.php` |

**Admin** (nav in `public/includes/admin-nav.php`)

| URL | Entry today |
|-----|-------------|
| `/admin/` | `public/admin/index.php` |
| `/admin/analytics/` | `public/admin/analytics/index.php` |
| `/admin/analytics/view.php` | `public/admin/analytics/view.php` |
| `/admin/reports/` | `public/admin/reports/index.php` |
| `/admin/cases/` | `public/admin/cases/index.php` |
| `/admin/cases/case-detail.php` | `public/admin/cases/case-detail.php` |
| `/admin/rescuers/` | `public/admin/rescuers/index.php` |
| `/admin/animals/` | `public/admin/animals/index.php` |
| `/admin/health-records/` | `public/admin/health-records/index.php` |
| `/admin/health-records/health-record.php` | `public/admin/health-records/health-record.php` |
| `/admin/listings/` | `public/admin/listings/index.php` |
| `/admin/applications/` | `public/admin/applications/index.php` |
| `/admin/elearning/` | `public/admin/elearning/index.php` |
| `/admin/messages/` | `public/admin/messages/index.php` |
| `/admin/notifications/` | `public/admin/notifications/index.php` |

**Other**

| URL | Role |
|-----|------|
| `/api/v1/*` | Unchanged |
| `/uploads/*` | Unchanged |
| `/favicon.png` | Unchanged |
| `/dbtool/` | Unchanged (not wired into the app) |

### 4.2 Leftover 302 stubs (keep until unused)

| Stub | Redirects to |
|------|----------------|
| `public/admin/reports.php` | `/admin/reports/` |
| `public/admin/animals.php` | `/admin/animals/` |
| `public/admin/cases.php` | `/admin/cases/` |
| `public/admin/rescuers.php` | `/admin/rescuers/` |
| `public/admin/health-records.php` | `/admin/health-records/` |
| `public/admin/health-record.php` | `/admin/health-records/health-record.php` |
| `public/admin/case-detail.php` | `/admin/cases/case-detail.php` |

Before deleting a stub: grep the repo (PHP, JS, docs) for the old filename and the old URL. If anything still links it, keep the stub.

---

## 5. Inventory that must be accounted for

Every path below must exist at a **new** location or remain with a documented shim. Do not invent “unused” and delete without grep.

### 5.1 Shared PHP (today → target)

| Today | Target |
|-------|--------|
| `public/includes/site-head.php` | `views/components/site-head.php` |
| `public/includes/admin-shell.php` | `views/layouts/admin.php` |
| `public/includes/resident-shell.php` | `views/layouts/resident.php` |
| `public/includes/admin-nav.php` | `views/components/admin-nav.php` |
| `public/includes/admin-page-auth-state.php` | `views/components/admin-page-auth-state.php` |
| `public/includes/guard.php` | `views/components/guard.php` |
| `public/includes/homepage.php` | `views/home/landing.php` |
| `public/includes/header.php` | `views/components/guest-header.php` |
| `public/includes/footer.php` | `views/components/guest-footer.php` |
| `public/auth/partials/google-button.php` | `views/auth/google-button.php` |
| `public/landing/partials/copy.php` | `views/home/copy.php` |
| `public/landing/partials/hero-art.php` | `views/home/hero-art.php` |
| `public/admin/includes/dashboard-data.php` | stay with `/admin/` bootstrap **or** `views/admin/dashboard/data.php` |
| `public/admin/includes/dashboard-insights.php` | same |
| `public/admin/includes/ui-helpers.php` | `views/components/admin-ui-helpers.php` (shared) |
| `public/admin/partials/*.php` | `views/admin/dashboard/partials/` |
| `public/admin/<page>/partials/*` | `views/admin/<page>/partials/` |
| `public/admin/<page>/includes/*` | `views/admin/<page>/` or keep beside thin `index.php` if data-only |

### 5.2 Shared JS (today → target)

| Today | Target |
|-------|--------|
| `public/js/lib/api.js` | `public/assets/js/lib/api.js` |
| `public/js/lib/page-auth.js` | `public/assets/js/lib/page-auth.js` |
| `public/js/lib/swr.js` | `public/assets/js/lib/swr.js` |
| `public/js/lib/format.js` | `public/assets/js/lib/format.js` |
| `public/js/lib/csv.js` | `public/assets/js/lib/csv.js` |
| `public/js/lib/utils.js` | `public/assets/js/lib/utils.js` |
| `public/js/lib/notification-stream.js` | `public/assets/js/lib/notification-stream.js` |
| `public/js/components/kpi-card.js` | `public/assets/js/components/kpi-card.js` |
| `public/js/components/resident-shell.js` | `public/assets/js/components/resident-shell.js` |
| `public/js/components/ui/*` (17 files) | `public/assets/js/components/ui/*` |
| `public/admin/js/lib/admin-data.js` | `public/assets/js/admin/admin-data.js` |
| `public/admin/js/lib/location-drawer.js` | `public/assets/js/admin/location-drawer.js` |
| `public/admin/js/layout/app-shell.js` | `public/assets/js/admin/app-shell.js` |
| `public/admin/js/layout/sidebar.js` | `public/assets/js/admin/sidebar.js` |
| `public/admin/js/layout/topbar.js` | `public/assets/js/admin/topbar.js` |

`ui/*` today: `badge`, `button`, `card`, `checkbox`, `date-picker`, `dialog`, `drawer`, `dropdown-menu`, `input`, `label`, `loader`, `marker`, `pagination`, `select`, `separator`, `skeleton`, `spinner`, `toast`, `tooltip`.

### 5.3 CSS files (today → cascade)

**Build**

| Today | Target |
|-------|--------|
| `public/css/input.css` | Split: `public/assets/css/input.css` (entry) + `tokens.css` + `base.css` + component/page files |
| `public/css/style.css` | `public/assets/css/style.css` (compiled) + shim at old URL if needed |

**Admin shared**

| Today | Target |
|-------|--------|
| `public/admin/css/admin.css` | Imports folded into `input.css` cascade |
| `partials/01_body.css` | `base.css` or `shell.css` |
| `partials/02_btn.css` | `components/buttons.css` |
| `partials/03_health-carousel.css` | `pages/dashboard-health-carousel.css` |
| `partials/04_table-wrap.css` | `components/tables.css` |
| `partials/05_audit.css` | `pages/dashboard-audit.css` or `components/` if reused |
| `partials/06_drawer-location.css` | `components/drawer.css` |
| `partials/07_donut-wrap.css` | `components/chart.css` |
| `partials/08_case-detail.css` | `pages/case-detail.css` |
| `partials/09_skeleton.css` | `components/skeleton.css` |
| `partials/10_rescuer-split.css` | `pages/rescuers.css` |
| `partials/11_animal-grid.css` | `pages/animals.css` |
| `partials/12_health-records.css` | `pages/health-records.css` |
| `partials/13_animals-flyout.css` | `pages/animals.css` (same page) |
| `partials/14_health-record.css` | `pages/health-record.css` |
| `partials/15_dashboard.css` | `pages/dashboard.css` |

**Page CSS**

| Today | Target `pages/` or landing/auth layer |
|-------|----------------------------------------|
| `public/admin/analytics/css/analytics.css` | `pages/analytics.css` |
| `public/admin/animals/css/animals-list.css` | merge into `pages/animals.css` |
| `public/admin/applications/css/applications.css` | `pages/applications.css` |
| `public/admin/cases/css/kpis.css` | `pages/cases.css` or shared kpi |
| `public/admin/elearning/css/elearning.css` | `pages/elearning.css` |
| `public/admin/health-records/css/health-records-list.css` | merge `pages/health-records.css` |
| `public/admin/messages/css/messages.css` | `pages/admin-messages.css` |
| `public/admin/messages/css/thread.css` | `pages/admin-messages-thread.css` |
| `public/auth/css/auth.css` + `partials/*` | `auth.css` |
| `public/landing/css/landing.css` + `partials/*` | `landing.css` (may keep numbered section files under `assets/css/landing/`) |
| `public/learning/css/learning.css` | `pages/learning.css` |
| `public/messages/css/messages.css` | `pages/resident-messages.css` |
| `public/notifications/css/notifications.css` | `pages/notifications.css` |

### 5.4 Admin page JS trees (flatten)

Remove the extra `js/pages/<name>/` segment everywhere.

| Page | Today root | Flatten to |
|------|------------|------------|
| Dashboard | `public/admin/js/dashboard.js` + `js/pages/dashboard/**` | `public/admin/js/dashboard.js` + `js/components/` + `js/state.js` + helpers (no `pages/dashboard/`) |
| Reports | `public/admin/reports/js/reports.js` + `js/pages/reports/**` | `public/admin/reports/js/` |
| Cases | `public/admin/cases/js/` + `pages/cases/**` + `pages/case-detail/**` | `public/admin/cases/js/` with `case-detail/` sibling, not `pages/` |
| Rescuers | `public/admin/rescuers/js/` + `pages/rescuers/**` | `public/admin/rescuers/js/` |
| Animals | `public/admin/animals/js/` + `pages/animals/**` | `public/admin/animals/js/` |
| Health list + record | `public/admin/health-records/js/` + `pages/health-records/**` + `pages/health-record/**` | `js/health-records/` and `js/health-record/` (no `pages/`) |
| Listings | `public/admin/listings/js/` + `pages/listings/**` | `public/admin/listings/js/` |
| Applications | `public/admin/applications/js/` + `pages/applications/**` | `public/admin/applications/js/` |
| E-learning | `public/admin/elearning/js/` + `pages/elearning/**` | `public/admin/elearning/js/` |
| Messages | `public/admin/messages/js/` + `pages/messages/**` | `public/admin/messages/js/` |
| Notifications | `public/admin/notifications/js/` | flatten if nested |
| Analytics | `public/admin/analytics/js/` + `pages/analytics/**` | `public/admin/analytics/js/` |

Resident page JS (`public/account/js`, `adoptions/js`, `animals/js`, `cases/js`, `listings/js`, `learning/js`, `messages/js`, `notifications/js`, `report/js`, `reports/js`, `auth/js`, `landing/js` + `landing/components`) stays **page-local**. Only rewrite imports that pointed at `/js/…` so they point at `/assets/js/…`.

### 5.5 Config that must change when files move

| File | Change |
|------|--------|
| `package.json` | `-i ./public/assets/css/input.css -o ./public/assets/css/style.css` |
| `tailwind.config.js` | `content` must include `./public/**/*.{php,js,html}`, `./views/**/*.{php,js}`, `./public/assets/js/**/*.js` |
| `AGENTS.md` | CSS path, includes → views, modular-files paragraph |
| `README.md` | layout blurb |
| `docs/technical/HOW_TO_RUN.md` | build paths |
| `docs/study/DESIGN_SYSTEM.md` | token file path |

---

## 6. Workstreams (parallel)

One **lead agent** owns the cascade order, import style, and merge. **Subagents** take one workstream only. Do not two-edit the same file.

| ID | Workstream | Owns | Do not touch |
|----|------------|------|----------------|
| W0 | Lead / inventory | Path decisions, helper `views_path` if added, conflict resolution, final grep | Implementing a whole page family |
| W1 | CSS cascade | Split `input.css`; fold admin/landing/auth partials; `package.json`; `tailwind.config.js`; `site-head` stylesheet href | Page JS logic |
| W2 | Shared JS | Move `public/js/**` and `admin/js/lib|layout` → `assets/js`; rewrite imports; temporary re-exports | CSS token values |
| W3 | Views / layouts | Move includes → `views/layouts` + `views/components`; update every `require` | JS flatten inside a page |
| W4a | Admin: dashboard | Flatten `admin/js/pages/dashboard`; move dashboard partials to `views/admin/dashboard` | Other admin pages |
| W4b | Admin: reports, cases, rescuers | Flatten those three JS trees + their views | Health, animals |
| W4c | Admin: animals, health-records | Flatten + views (list + record) | Reports |
| W4d | Admin: listings, applications | Flatten + views | — |
| W4e | Admin: elearning, messages, notifications, analytics | Flatten + views | — |
| W5 | Resident + auth + landing | Import rewrites; move homepage/header/footer; page CSS into cascade | Admin JS |
| W6 | Stubs + docs | Keep 302s; update AGENTS/README/HOW_TO_RUN/DESIGN_SYSTEM | Feature copy in FEATURES.md unless a path is cited |
| W7 | Verify | PHPUnit, `npm run build`, path grep, URL smoke | New structure decisions |

**Suggested parallel batches**

1. W1 + W2 (after W0 publishes the locked paths — already this spec).  
2. W3 (serial or one agent — every page `require`s includes).  
3. W4a–W4e + W5 in parallel.  
4. W6 + W7.

If Multitask Mode is on, launch W4a–W4e and W5 together after W1–W3 land.

---

## 7. Per-page move checklist

Copy this for each URL in §4.1:

1. Page still opens at the same URL (200, or 302 stub then 200).  
2. Guard still redirects anonymous users to `/auth/login.php`.  
3. Role gate still sends the wrong role home.  
4. `$pageCss` / compiled CSS still applies (no unstyled flash that is a missing file).  
5. Entry `<script type="module">` 200s; DevTools has no failed module imports.  
6. `__PAGE_STATE__` still hydrates where it did before.  
7. Lucide icons still `createIcons`.  
8. No `js/pages/<page>/` left in that tree.  
9. No `../../../js/lib` imports left in that tree.

---

## 8. Acceptance criteria (done when)

- [ ] Target tree in `FOLDER_ARCHITECTURE.md` §4 exists.
- [ ] `input.css` is an entry file; tokens live in `tokens.css`; components are one file each; cascade order matches §5 of the architecture doc.
- [ ] `site-head` (wherever it lives) links the compiled sheet; `npm run build` succeeds.
- [ ] Tailwind `content` globs cover `public/` and `views/`.
- [ ] Shared JS lives under `public/assets/js/{lib,components,admin}`.
- [ ] No path matching `js/pages/` remains under `public/`.
- [ ] Every URL in §4.1 still resolves.
- [ ] Stubs in §4.2 still 302 or were deleted only after grep was empty.
- [ ] `php vendor\phpunit\phpunit\phpunit` passes.
- [ ] Grep for old paths is empty or only lists documented shims:  
      `public/css/input.css` (except shim), `admin/css/partials`, `includes/admin-shell.php`, `from "/js/lib/`, `js/pages/`.
- [ ] `AGENTS.md`, `README.md`, `HOW_TO_RUN.md`, `DESIGN_SYSTEM.md` paths match the new tree.
- [ ] No visual redesign and no API contract change.

---

## 9. Verification commands

From repo root (Windows):

```bat
php vendor\phpunit\phpunit\phpunit
npm run build
rg -n "js/pages/" public
rg -n "from [\"']/js/lib/" public views
rg -n "admin/css/partials" public views
rg -n "includes/admin-shell.php" public views
```

URL smoke (server already running): open `/`, `/auth/login.php`, `/admin/`, `/admin/reports/`, `/admin/cases/`, `/admin/health-records/`, `/animals/`, `/report/`. Confirm network tab: CSS 200, page JS 200, no 404 modules.

---

## 10. Out of scope (later)

- Moving page SQL from `index.php` into `src/Controllers` / `src/Services`.
- PGSO-style `routes/web.php` for HTML pages.
- Rewriting `ARCHITECTURE_AUDIT.md` / `FEATURES.md` historical paths (unless a link 404s).
- Moving uploads to `storage/` (PGSO does this; FurEscue URLs are `/uploads/…` — do not break them here).
