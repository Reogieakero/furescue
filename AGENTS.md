# AGENTS.md

Vanilla PHP 8.1+ REST API (no framework, custom router, PDO + MySQL) + frontend of PHP-rendered pages with ES-module islands. One server serves both: `php -S 127.0.0.1:8000 -t public public\index.php` from repo root.

The frontend is mid-migration from static `.html` pages to PHP pages using shared partials in `public/includes/` (`site-head.php`, `admin-shell.php`, ...). Admin pages are now `.php`; expect both variants during transition.

## Responsive frontend (hard rule)

- Every UI/layout change MUST work at all three breakpoints before it counts as done: **375px** mobile, **768px** tablet, **1440px** desktop.
- Write mobile-first: base styles target the smallest screen; enhance upward with `min-width` media queries.
- No fixed-width containers or grid columns. Use fluid sizing: `max-width`, `%`, `clamp()`, `fr`/`minmax()` grid tracks, `flex-wrap`. Zero horizontal overflow at 375px.
- Images/media scale with their container (`max-width: 100%`; never distort). Navigation must remain usable at 375px (collapse to the hamburger/stacked pattern used elsewhere in the repo).
- Verify by actually loading the page from the running server and resizing (devtools device toolbar) at all three widths — assumption is not verification.

## Frontend design system (hard rule)

- **Follow the existing system design first.** All frontend styling must come from the shared design tokens, never ad-hoc values:
  - Colors, radius, shadows, fonts: CSS variables in `:root` / `.dark` in `public/css/input.css` (e.g. `--primary`, `--border`, `--radius`, `--shadow-md`, brand colors `--brand-1/2`, palette `paper`/`ink`/`jungle`/`coral`/...), mapped to Tailwind classes in `tailwind.config.js`.
  - Icons: Lucide only (`lucide.createIcons()` via the esm.sh import map), sized with the existing `.lucide` conventions — never inline SVG or emoji as icons.
  - Shared components/patterns (`.input`, `.toast`, `.loader-overlay`, `.logo-mark`, `.badge-icon`, admin shell partials) already exist in `input.css` and `public/includes/` — reuse them instead of restyling from scratch.
- **New reusable style = add it to the system.** If you introduce a new border radius, color, shadow, spacing scale, font, icon set, or component pattern that could be used by other pages, promote it into `public/css/input.css` (as a `:root` variable and/or shared class) plus a mapping in `tailwind.config.js` if it needs Tailwind utilities — then use the token everywhere. Do not hardcode one-off values in page-level styles.
- Never hardcode raw hex/hsl colors, radii, or font stacks in markup or JS when a token exists; if no suitable token exists, create one first.
- After editing `input.css` or `tailwind.config.js`, run `npm run build` (and remember the Tailwind `content:` globs gotcha below for `.php` files).
- The responsive rule above still applies: any new token/component must be verified at 375px / 768px / 1440px.

## Folder architecture (overhaul)

Target tree and CSS cascade: `docs/technical/FOLDER_ARCHITECTURE.md`.  
Keep-list, URL freeze, workstreams: `docs/technical/FOLDER_OVERHAUL_SPEC.md`.  
Until that overhaul lands, the live layout is still `public/<page>/` + `public/includes/` + `public/css/input.css` as described below.

## Modular files, no monoliths (hard rule)

- Never bottleneck logic or markup in one long file. One concern per file; split when a file grows past ~300 lines, accumulates a second responsibility, or duplicates markup/logic used elsewhere.
- Frontend: new pages live in their own folder as `public/<page>/index.php` (URL `/page/`; sub-pages are sibling `.php` files in that folder). Page-specific assets live inside the page folder (`public/<page>/js/`, `public/<page>/css/`, page-local partials) and are referenced with absolute paths (`/page/js/app.js`). Shared markup goes into partials in `public/includes/` (`include`d by pages); shared libs/components stay in `public/js/lib|components/`; reusable styles become tokens/classes in `public/css/input.css` (see design-system rule).
- Backend: controllers stay thin; business rules belong in `src/*` service classes, DB access in repositories/PDO helpers — not inline in route closures.
- When creating a shared piece (partial, module, class), reuse the existing ones first; only extract new ones when the duplication is real.

## Commands

```bat
composer install
php vendor\phpunit\phpunit\phpunit          :: tests (no composer test script; vendor/bin/phpunit is a bash script, use this on Windows)
php vendor\phpunit\phpunit\phpunit --filter DedupServiceTest   :: single class
php bin\migrate.php                          :: apply migrations/*.sql
php seeders\seed.php                         :: idempotent demo data
php -S 127.0.0.1:8000 -t public public\index.php   :: run everything
npm run build                                :: compile Tailwind after editing CSS
```

- Tests are pure unit tests (`tests/`) — no database or server needed.
- Setup details and demo accounts (`Password123!`): `docs/technical/HOW_TO_RUN.md`.

## Gotchas

- **DB driver defaults to pgsql**: `src/Database.php` falls back to Postgres when `DB_DRIVER` is unset. The schema (e.g. `DEFAULT (UUID())`) requires MySQL 8.0.13+. Always have `.env` (copy `.env.example`) before migrate/serve.
- **Migrations are plain SQL files** in `migrations/`, applied once by filename order and logged in `migrations_log`. Add new ones as `YYYY_MM_DD_NNNNNN_name.sql`; never edit applied files.
- **CSS**: edit `public/css/input.css`, never the compiled `public/css/style.css` (both are git-tracked; rebuild with `npm run build`). Tailwind `content:` globs in `tailwind.config.js` only cover `public/{landing,auth,admin}/*.{html,js}` and `public/js/` — classes used only inside `.php` pages/includes get purged on rebuild; add globs or reuse classes already emitted by the JS/HTML files.
- **Frontend deps don't come from npm**: browser pages load `clsx`/`tailwind-merge`/`cva`/`lucide` via esm.sh import maps in the HTML; Leaflet from unpkg CDN. `node_modules` is build-time only.

## Architecture

- Entry point `public/index.php`: serves real files under `public/`, special-cases `/uploads/`, otherwise dispatches to `App\Http\Router`. All API paths start `/api/v1`.
- Routes are registered per-domain in `src/Http/Routes/*Routes.php` and wired via `RouteLoader::register()`. Adding an endpoint = new route line there + controller method; shared deps (PDO, JWT, middleware) are passed in the `$deps` array built in `public/index.php`.
- Responses use the JSON envelope `{ success, data, error }` from `App\Http\Response`.
- Auth: Bearer JWT (`AuthMiddleware`) + role gates (`RoleMiddleware`: `admin` vs staff `rescuer|admin`). Exception: `POST /api/v1/vitals` authenticates with the `X-Device-Key` header against `DEVICE_API_KEY` instead of JWT.
- Geovalidation clamps reports to the Mati City bounding box env vars; report dedup tuning lives in `DEDUP_*` env vars.
- `dbtool/index.php` is a standalone dev SQL viewer, not wired into the app.

## Docs caveat

`docs/technical/ARCHITECTURE_AUDIT.md` still describes the pre-refactor `backend/` + `frontend/` tree and `.html` URLs. Code now lives at the repo root (`src/`, `public/`, etc.) and pages are `.php`. Trust the code over that audit. Onboarding for humans is `README.md` + `docs/technical/HOW_TO_RUN.md`.
