# FurEscue architecture audit (read-only)

**Scope:** facts observed in the repository on 2026-08-22. No redesign. Claims that could not be proven from files are marked **[uncertain]**.

> **NOTE (post-audit):** The repository tree has changed since this audit was written. The `backend/` and `frontend/` directories are gone; the code now lives at the repo root (`src/`, `bin/`, `migrations/`, `seeders/`, `tests/`, `dbtool/`, with `public/` as the sole web root). The paths below describe the **pre-refactor** tree and are kept as a historical record.
>
> **NOTE (page migration):** Pages are no longer static HTML. They have been migrated to **PHP-rendered pages** (`public/*.php`) that share partials in `public/includes/` and are served by the same single server as the API (`php -S 127.0.0.1:8000 -t public public\index.php`). References below to `frontend/*.html` pages opened via a separate static server describe an obsolete setup; read them as `public/**/*.php`. Section 6 items that were fixed since: the API endpoint table in `SYSTEM_REPORT.md` now matches `src/Http/Routes/*Routes.php`, the paginated envelope is documented correctly, login curl examples use `tokens.access_token`, and CORS is configurable via `CORS_ALLOWED_ORIGINS` (wildcard only as logged fallback). `dbtool/index.php` now requires `?key=<DEVTOOL_KEY>`.

---

## 1. Stack summary

Verified from lockfiles, configs, and entry points — not copied from docs.

| Layer | What is actually in the repo |
|-------|------------------------------|
| Backend language | PHP `>=8.1` (`backend/composer.json`) |
| Backend style | Vanilla PHP REST API, no framework class or `composer.json` framework package |
| HTTP entry | `backend/public/index.php` (front controller + optional `/uploads/` file serve) |
| Routing | Custom `App\Http\Router` in `backend/src/Http/Router.php` |
| Auth | `firebase/php-jwt` **v6.11.1** (`backend/composer.lock`); HS256 via `backend/src/Auth/JwtService.php` reading `JWT_SECRET` / `JWT_REFRESH_SECRET` / `JWT_ALGO` / TTLs from env |
| Config | `vlucas/phpdotenv` **v5.6.4** (`backend/composer.lock`); loaded with `Dotenv::createImmutable(__DIR__ . '/..')` in `backend/public/index.php` |
| DB | PDO; `backend/src/Database.php` supports `mysql` and `pgsql`. **Default driver if env unset is `pgsql`**, not MySQL |
| Env template | `backend/.env.example` sets `DB_DRIVER=mysql`, MySQL 8.0.13+ comment, JWT, Google client ID, `DEVICE_API_KEY`, Mati bounds, dedup knobs |
| Tests | PHPUnit `^10.5` (`backend/composer.json`); suite `backend/phpunit.xml.dist` → `backend/tests/` (`JwtServiceTest`, `ValidatorTest`, `DedupServiceTest`, `GeoServiceTest`, `DatabaseTest`) |
| Autoload | PSR-4 `App\` → `backend/src/` (`backend/composer.json`) |
| Frontend language | Static HTML + ES modules (no React/Vue/etc. in `frontend/package.json`) |
| CSS build | Tailwind CSS **^3.4.17** CLI: `frontend/package.json` scripts `build` / `watch` / `dev` compile `frontend/css/input.css` → `frontend/css/style.css --minify` (watch without minify). `frontend/postcss.config.js` lists `tailwindcss` + `autoprefixer`. Both `input.css` and compiled `style.css` are git-tracked. |
| npm runtime deps | `clsx`, `tailwind-merge`, `class-variance-authority` in `frontend/package.json` — **browser pages do not import `node_modules`**. HTML import maps load the same packages from `https://esm.sh/...` (e.g. `frontend/admin/index.html`) |
| Maps | Leaflet 1.9.4 + leaflet.heat 0.2.0 from `unpkg.com` (`frontend/admin/index.html`, `frontend/admin/reports.html`, `frontend/admin/cases.html`) |
| Icons / UI libs | Lucide from `https://esm.sh/lucide@0.469.0` via import maps |
| Fonts | Google Fonts CDN (Nunito; admin also Fraunces + IBM Plex Mono) |
| External HTTP from PHP | Nominatim reverse geocode (`backend/src/Services/GeoService.php`); Google certs for ID tokens (`backend/src/Auth/GoogleAuthService.php`) |

**How the API is started (code + docs that match):** `HOW_TO_RUN.md` documents `php -S 127.0.0.1:8000 -t public public\index.php` from `backend/`. That sets document root to `backend/public` and uses `backend/public/index.php` as the router script for paths that are not real files under that root.

**How the UI is started:** no PHP template renders landing/admin/auth pages. `HOW_TO_RUN.md` says open HTML files directly or `php -S 127.0.0.1:8080 -t frontend`. There is **no** `.htaccess`, nginx, or Apache config in the repo.

**PHP that does emit HTML:** `backend/dbtool/index.php` is a SQL table viewer (SELECT/SHOW only). It is not wired into `backend/public/index.php`.

---

## 2. Request flow

### 2.1 API request (example: `GET /api/v1/reports` with Bearer token)

Pattern used throughout: **front controller registers closures on a custom Router; optional invokable middleware; controller methods; generic `Repository` and/or raw PDO SQL; JSON via `Response`.** There is only one repository class (`backend/src/Repositories/Repository.php`). Domain “services” are used for some logic (`DedupService`, `GeoService`, `NotificationService`, `VaccinationEngine`), not as a mandatory layer on every request.

1. **Web server** (`php -S` as above): if the path is not a file under `backend/public`, the request is handled by `backend/public/index.php`.
2. **Uploads short-circuit (skipped for `/api/`):** if `REQUEST_URI` path starts with `/uploads/`, `index.php` tries `backend/public` + URI, then repo-root `public` + URI, then `readfile` and `exit`. API paths do not hit this.
3. **Bootstrap:** `vendor/autoload.php`, dotenv `safeLoad()`, error/exception handlers that call `Response::error(..., 500)`, `Database::connect()`, construct JWT/password/Google/dedup/geo/notification objects and `AuthMiddleware` / `RoleMiddleware`.
4. **Route table:** `$router->add('GET', '/api/v1/reports', ..., [$authMw])` in `backend/public/index.php`.
5. **`Request`:** `backend/src/Http/Request.php` sets method, path (strips script dirname if present), `$_GET`, headers, JSON body from `php://input` (GET/DELETE body forced empty).
6. **`Router::dispatch`:** `backend/src/Http/Router.php` — `OPTIONS` returns `Response::success(null)` immediately (CORS preflight). Otherwise first matching method+regex wins; `{id}` compiled to named capture. Middleware array run in order; if a middleware returns a `Response` or `Response::$sent`, dispatch stops.
7. **`AuthMiddleware`:** `backend/src/Middleware/AuthMiddleware.php` — Bearer token → `JwtService::verifyAccessToken` → `SELECT * FROM users WHERE id = ?` → require `account_status === 'active'` → `$request->user`.
8. **Controller:** `ReportController::index` (`backend/src/Controllers/ReportController.php`) extends `AbstractController` (`backend/src/Controllers/AbstractController.php`), which factories `new Repository($pdo, $table, $columns)`.
9. **Repository:** `Repository::paginate` builds `WHERE` from equality/IN filters, `LIMIT`/`OFFSET`.
10. **Response:** `Response::paginated` in `backend/src/Http/Response.php` sends JSON `{ success, data: <items array>, meta, error: null }` plus CORS headers `Access-Control-Allow-Origin: *` and allowed methods/headers including `Authorization` and `X-Device-Key`.

**Role-gated variant:** e.g. `POST /api/v1/reports/{id}/verify` uses `[$authMw, $adminMw]` (`RoleMiddleware` with `['admin']` in `backend/src/Middleware/RoleMiddleware.php`).

**Exception to JSON body:** `DocumentsController::create` (`backend/src/Controllers/DocumentsController.php`) reads `$_POST` and `$_FILES['file']` because `Request::resolveBody` only JSON-decodes `php://input`.

**Unauthenticated IoT:** `POST /api/v1/vitals` has **no** `AuthMiddleware`; `VitalsController::ingest` checks header `x-device-key` against `DEVICE_API_KEY` (`backend/src/Controllers/VitalsController.php`).

### 2.2 Static page load (example: admin dashboard)

1. Browser loads `frontend/admin/index.html` (file URL, or `php -S ... -t frontend` at `/admin/index.html`). PHP is not involved.
2. HTML links `../css/style.css` (Tailwind output) and `css/admin.css` (`@import` of `frontend/admin/css/partials/*.css`).
3. Import map points `clsx` / `tailwind-merge` / `class-variance-authority` / `lucide` at esm.sh.
4. Leaflet CSS/JS from unpkg.
5. Module `js/dashboard.js` (`frontend/admin/js/dashboard.js`) runs `requireAuth(["admin"])` from `frontend/js/lib/api.js` (localStorage JWT; redirect to `../auth/login.html` if missing).
6. Shell HTML is generated in JS (`AppShell` in `frontend/admin/js/layout/app-shell.js`); `#app` is filled client-side. Same pattern on landing (`frontend/landing/index.html` → `frontend/landing/js/landing.js`) and login (`frontend/auth/login.html` → `frontend/auth/js/auth.js`).
7. Subsequent data loads use `fetch` to `http://127.0.0.1:8000/api/v1...` (`frontend/js/lib/api.js`), not same-origin as the HTML unless that origin is also port 8000.

**Favicon:** every HTML page uses `href="/public/favicon.png"`. That only resolves if the **static** server’s URL root contains a `public/favicon.png`. Under `php -S -t frontend`, that file is `frontend/public/favicon.png`. Opening HTML as `file://` or serving a parent folder without that mapping does not hit `frontend/public`.

---

## 3. Folder purpose map

| Path | Actual role | Evidence | Status |
|------|-------------|----------|--------|
| `backend/public/index.php` | API + `/uploads/` fallback serve | File itself | used |
| `backend/public/uploads/demo/*.svg` | Seeded report photo URLs `/uploads/demo/report-{i}.svg` | `backend/seeders/seed.php`; files on disk | used |
| `backend/public/.gitkeep` | Placeholder | empty file | used (keeps dir) |
| `backend/src/Http/` | Request/Response/Router | those three files | used |
| `backend/src/Controllers/` | Route handlers | imported in `index.php` | used |
| `backend/src/Middleware/` | Auth + role | `index.php` | used |
| `backend/src/Repositories/Repository.php` | Generic table CRUD | `AbstractController::repo` | used |
| `backend/src/Services/` | Dedup, geo, notifications, vaccination protocols | controllers / `index.php` | used |
| `backend/src/Auth/` | JWT, password, Google | `index.php` | used |
| `backend/src/Validation/Validator.php` | Request validation | controllers | used |
| `backend/src/Database.php` | PDO + UUID helper | everywhere | used |
| `backend/migrations/*.sql` | 13 SQL files, 17 `CREATE TABLE`s plus later ALTERs | glob + `CREATE TABLE` grep | used |
| `backend/bin/migrate.php` | Applies `migrations/*.sql`, logs in `migrations_log` | file | used |
| `backend/seeders/seed.php` | Idempotent demo data | file; `HOW_TO_RUN.md` | used |
| `backend/tests/` | Unit tests, no HTTP suite | `phpunit.xml.dist` | used |
| `backend/dbtool/index.php` | Dev DB HTML viewer, separate from API | file; not referenced by `index.php` | used if opened directly; **not an app entry** |
| `backend/vendor/` | Composer install | gitignored in `.gitignore` / `backend/.gitignore` | generated |
| `frontend/index.html` | Redirect to `landing/index.html` | meta refresh + JS | used |
| `frontend/landing/` | Marketing page | `index.html` + `js/landing.js` + components | used |
| `frontend/auth/login.html` + `js/auth.js` + `css/auth.css` | Sign-in UI | files | used |
| `frontend/auth/signup.html` | Linked from navbar and login footer | `frontend/landing/components/navbar.js`, `frontend/auth/js/auth.js` | **orphaned / missing** — file does not exist |
| `frontend/admin/*.html` | Admin shells: `index`, `reports`, `cases`, `case-detail`, `rescuers`, `animals`, `health-records`, `health-record` | glob of HTML | used |
| `frontend/admin/js/` | Page modules + `lib/admin-data.js` API wrappers | HTML `script type=module` | used (most files); see orphans below |
| `frontend/admin/js/pages/health-record/data.js` | Comment: “Mock data for … mockup”; **no importer** | grep found zero imports | **orphaned** |
| `frontend/js/lib/api.js` | API base URL, tokens, `fetch` | imported by admin + auth | used |
| `frontend/js/lib/swr.js`, `utils.js` | Cache/badges; `cn()` | admin/auth imports | used |
| `frontend/js/components/ui/*` | Shared primitives | many page imports | used |
| `frontend/css/input.css` | Tailwind source | `package.json` build | used |
| `frontend/css/style.css` | Compiled Tailwind (git-tracked) | HTML `<link>` | used |
| `frontend/tailwind.config.js` | Content globs `landing`, `auth`, `admin`, `js` | file | used |
| `frontend/postcss.config.js` | tailwind + autoprefixer | file | used by Tailwind CLI if it loads PostCSS config **[uncertain whether autoprefixer always runs]** |
| `frontend/public/favicon.png` | Favicon target of `/public/favicon.png` | HTML hrefs | used **only if static origin maps `/public/`** |
| `frontend/public/reported.png` | Placeholder in report drawers | `frontend/admin/js/pages/dashboard/queue.js`, `.../reports/workflow/drawer.js` (`src="../public/reported.png"` — relative to **admin HTML**, i.e. `frontend/public/reported.png` when page is `/admin/*.html`) | used |
| `frontend/public/report_flag_wave.gif`, `frontend/public/resolve.png` | Binary assets | git-tracked; **no JS/HTML/CSS reference** | **orphaned** |
| `public/uploads/*.png` | Four UUID-named PNGs (1.2–1.5 MB) | on disk; git-tracked; `DocumentsController` first candidate dir | used as **runtime upload store** |
| `public/` (only `uploads/` child) | Not a web document root in `HOW_TO_RUN.md` | listing | used indirectly via PHP fallback |
| `HOW_TO_RUN.md`, `SYSTEM_REPORT.md` | Human docs | files | used as docs; **partially inaccurate** (section 6) |

---

## 4. Backend ↔ frontend coupling

**There is no PHP include of frontend files and no frontend import of PHP.** Coupling is runtime HTTP + hardcoded URLs + CORS.

| Coupling | Detail |
|----------|--------|
| API origin | `frontend/js/lib/api.js`: `window.FURESCUE_API_BASE_URL \|\| "http://127.0.0.1:8000/api/v1"`. Paths concatenated onto that base (`/auth/login`, `/reports`, …). |
| CORS | `Response::json` sets `Access-Control-Allow-Origin: *` so HTML may be `file://` or another port (`HOW_TO_RUN.md` optional `:8080`). |
| Auth | Access/refresh tokens + user JSON in `localStorage` keys `furescue_*` (`api.js`). Login uses `POST /auth/login`; session shape uses nested `tokens.access_token` (`AuthController` + `setSession`). |
| Admin API facade | `frontend/admin/js/lib/admin-data.js` wraps `apiFetchFull` / `apiUpload`. |
| Uploaded/document URLs | Stored as **origin-relative** `/uploads/...`. Health record UI prefixes API host: `API_BASE_URL.replace(/\/api\/v1\/?$/, "")` (`frontend/admin/js/pages/health-record/page.js`). Case-detail galleries use API strings as `href`/`src` unchanged (`frontend/admin/js/pages/case-detail/components/files.js`) — those resolve against the **HTML origin**, not port 8000, unless the path happens to exist there. |
| Demo photos | Seed writes `"/uploads/demo/report-{$i}.svg"` (`backend/seeders/seed.php`); files live under `backend/public/uploads/demo/`. |
| Shared constants | **None compiled together.** Mati bounds live in backend env (`backend/.env.example`); frontend map JS has its own map config in admin map modules (not shared with PHP). |
| Ports | Backend assumed **8000**. Frontend optional **8080**. Changing API port without setting `window.FURESCUE_API_BASE_URL` or editing `api.js` breaks the UI (`HOW_TO_RUN.md` notes this). |
| Google | Backend `POST /api/v1/auth/google` exists. Login UI has `#google` and `signup.html` links (`frontend/auth/js/auth.js`) but **no** `apiFetch` to `/auth/google` and **no** Google GIS script in `login.html`. |

**What breaks if either tree moves**

- Move/rename `backend/public` or stop using it as `-t` document root: `php -S` command and `/uploads/demo/` static files under that root break unless rewritten.
- Move `frontend/` : relative imports (`../../js/lib/api.js`) and `../css/style.css` keep working **inside** the tree; `href="/public/favicon.png"` still depends on how the static server is rooted.
- Host frontend and API on different hosts: CORS already `*`; **upload image `src="/uploads/..."` on the frontend origin still miss the API** unless rewritten like health-record `resolveDocUrl`.
- `DocumentsController::uploadsDir` prefers **repo-root** `public/uploads` over `backend/public/uploads` (`__DIR__ . '/../../../public/uploads'` first). Moving only `backend/` without the sibling `public/` changes where new files are written vs where `index.php` looks.

---

## 5. Direct answers

### (a) Is `backend/` + `frontend/` split justified vs a single-structure layout?

**Fact of current design:** the running system is already two independently served trees: PHP JSON API (`backend/public/index.php`) and static HTML/JS (`frontend/**/*.html`). PHP does not render those pages. That split matches the process model in `HOW_TO_RUN.md` (port 8000 vs file/8080).

**Fact of coupling:** there is no compile-time or autoload link between the trees. A single document root could serve the same static files and still proxy/route `/api/` to PHP; conversely the two-folder layout is what the documented `php -S` commands assume. Whether one layout is “better” is outside this audit; **both are compatible with a vanilla PHP REST API + static JS frontend because the only contract is HTTP JSON + CORS + URL strings.**

### (b) Why do two upload directories exist?

They are **not duplicates of the same files**; they are two directories plus a **read/write fallback**.

| Directory | Contents | Write path | Read path |
|-----------|----------|------------|-----------|
| `backend/public/uploads/demo/` | 17 demo SVGs `report-0.svg` … `report-16.svg` | Seeder does **not** copy files; it only stores URL strings. SVGs are committed. | (1) PHP built-in server serves them as **real files** under document root `backend/public`. (2) `index.php` also maps `/uploads/*` to `__DIR__ . $uri` first. |
| Repo-root `public/uploads/` | Four UUID `.png` files (user-sized binaries) | `DocumentsController::uploadsDir()`: if `is_dir` on `__DIR__/../../../public/uploads` (`backend/src` → repo `public/uploads`), **that directory is chosen**. If neither candidate exists, it **creates** the first candidate (repo-root). | `index.php`: if file missing under `backend/public/uploads/...`, try `__DIR__ . '/../../public' . $uri` (repo-root `public/uploads/...`). Delete uses `uploadsDir() + basename`. |

**Why both exist in practice:** demo assets were placed where the PHP document root can serve them (`backend/public`). Document uploads were pointed at a **sibling** `public/uploads` (two levels above `backend/src`), which is **outside** the `-t public` root, so `index.php` contains an explicit fallback. If root `public/uploads` already exists (it does), new uploads never go to `backend/public/uploads`.

### (c) What is redundant?

- **Two physical upload roots** plus dual lookup in `index.php` and dual candidates in `DocumentsController`.
- **npm copies of clsx / tailwind-merge / cva** while browsers load esm.sh (`frontend/package.json` vs HTML import maps).
- **`frontend/admin/js/pages/health-record/data.js`** mock dataset unused after live API wiring.
- **`frontend/public/report_flag_wave.gif` and `resolve.png`** unused by code.
- **Sidebar labels** for Listings, Applications, E-Learning, Messages, Notifications (`frontend/admin/js/layout/sidebar.js`) with **no** matching HTML pages and **no** `NAV_TARGETS` entries (`app-shell.js` only maps dashboard/reports/cases/rescuers/animals/health records).
- **Docs describing APIs/pages that are not implemented** (section 6) — redundant/wrong relative to `index.php`.
- **Default `pgsql` in `Database.php` vs MySQL-only narrative** in `.env.example` / `HOW_TO_RUN.md` — two stories; only `.env` makes MySQL the actual driver after copy.

Not counted as redundant: `000009` and `000010` migrations are sequential ALTERs, not the same change.

---

## 6. Flagged anomalies

- **`SYSTEM_REPORT.md` API table does not match `backend/public/index.php`.** Report lists `POST /auth/logout`, `GET /auth/me`; code has `GET /api/v1/users/me` and **no logout route**. Report lists `GET/PUT/DELETE /reports/{id}` and `GET/POST /cases` plus `PUT/DELETE /cases/{id}`; router has GET/POST reports, GET report by id, verify/dismiss, GET cases (no POST create-case except as side effect of verify), PATCH case status, no PUT/DELETE cases. Report lists heatmap under analytics; heatmap is `GET /api/v1/reports/map/heatmap`.
- **`SYSTEM_REPORT.md` paginated envelope:** claims `data: { items, meta }`. Actual `Response::paginated` puts the item **array in `data`** and `meta` as a sibling (`backend/src/Http/Response.php`). Frontend `admin-data.js` `list()` correctly uses `payload.data` as array + `payload.meta.total`.
- **`HOW_TO_RUN.md` login curl:** claims `data` contains `"token"`. Login returns `tokens.access_token` / `tokens.refresh_token` (`AuthController::login` / `issueTokens`).
- **`HOW_TO_RUN.md` / `SYSTEM_REPORT.md` “16 tables” / five migrations.** Repo has **13** migration files and **17** `CREATE TABLE` statements (`users` … `elearning_progress` + `animal_documents`). Extra files `000006`–`000013` are not in the report’s table. `migrate.php` prints `apply ...` (docs say that) and also `skip` / a Done summary.
- **`SYSTEM_REPORT.md` “no mock or hardcoded data”** vs unused `health-record/data.js` mock, hardcoded sidebar badge strings (`sidebar.js`), and placeholder `../public/reported.png` instead of `photo_urls` in some drawers.
- **`SYSTEM_REPORT.md` surfaces:** only landing, login, admin index. Repo also has reports/cases/animals/rescuers/health HTML.
- **Login vs register:** `SYSTEM_REPORT.md` says login/register; only `login.html` exists; register API exists (`POST /api/v1/auth/register`) with **no** frontend caller (grep of `register(` in `frontend/` empty).
- **Broken links:** `signup.html` referenced, file absent.
- **`UserController::indexRescuers`:** triggered by `?role=rescuer` but SQL does **not** add `u.role = 'rescuer'` (`backend/src/Controllers/UserController.php`) — lists users filtered only by optional `account_status`.
- **Frontend calls missing routes:** `POST /cases/{id}/proof` (`frontend/admin/js/pages/case-detail/components/events.js`) — **not** registered in `index.php`.
- **Empty branch** in `CaseController::updateStatus` (`if ($case['assigned_rescuer_id'] !== $req->user['id'] && $req->user['role'] !== 'admin') { }` then a second rescuer check).
- **`Database::connect` default `pgsql`** while migrations/docs are MySQL (`DEFAULT (UUID())`, `ENGINE=InnoDB` in migrate helper).
- **Google Sign-In UI is a `#google` hash link**, not wired to `POST /api/v1/auth/google`.
- **`api.js` `redirectForRole`:** non-admin goes to `../landing/index.html`, not a resident/rescuer app (none in `frontend/`).
- **Relative upload URLs** vs API host (section 4).
- **Root `public/uploads` PNGs are git-tracked user binaries** (same hashes as `frontend/public/reported.png` / `resolve.png` sizes suggest copies). **[uncertain]** whether they are the same bytes as frontend placeholders.
- **`php -S ... public\index.php`:** every non-file request including unknown paths becomes JSON 404 `Route not found`, not a static 404.
- **No `.htaccess`:** production Apache/nginx layout is undocumented in-repo.
- **`composer.json` ignores advisory `PKSA-y2cr-5h3j-g3ys`.**
- **IoT vitals ingest** is unauthenticated except shared `DEVICE_API_KEY`.
- **CORS is wildcard** (documented in `SYSTEM_REPORT.md`; matches `Response.php`).

---

## 7. Handoff notes (constraints any later refactor must respect)

1. **API entry:** `backend/public/index.php` is the only REST front controller; all `/api/v1/...` routes are registered there. Do not assume a framework router elsewhere.
2. **Built-in server contract:** document root `backend/public` + router `public/index.php`. Files that exist under `backend/public` (including `uploads/demo/*.svg`) are served as static files **without** hitting the JSON router.
3. **Upload URL contract:** clients and seed data use paths `/uploads/...`. Serving must keep that URL working via (a) files in `backend/public/uploads` and/or (b) the fallback to repo-root `public/uploads` in `index.php`. `DocumentsController` currently **writes** to whichever of those two directories exists first (root preferred). Moving dirs without updating both write and read paths splits files from URLs.
4. **Deployed user files:** `public/uploads/*.png` are real binaries in git; they are not demo SVGs. Treat as data, not disposable placeholders, unless product owners confirm otherwise.
5. **Frontend entry HTML:** `frontend/index.html`, `frontend/landing/index.html`, `frontend/auth/login.html`, `frontend/admin/{index,reports,cases,case-detail,rescuers,animals,health-records,health-record}.html`. Each admin page is a separate document + module (not an SPA router).
6. **CSS pipeline:** `npm run build` in `frontend/` is required for `css/style.css` regeneration from `input.css` + Tailwind `content` globs. Admin/landing extra CSS is plain `@import` partials, not Tailwind plugins.
7. **API client:** single module `frontend/js/lib/api.js`; default host `127.0.0.1:8000`. Override is `window.FURESCUE_API_BASE_URL` (must be set **before** the module runs — currently no HTML sets it).
8. **JSON envelope:** success `{ success: true, data, error: null }`; paginated `{ success: true, data: array, meta, error: null }`; errors `{ success: false, data, error: { code, message } }`. Frontend `list()` depends on `data` being the array.
9. **Auth:** Bearer access JWT; refresh via `POST /api/v1/auth/refresh` with body `refresh_token`. Role middleware values: `admin`, `rescuer`+`admin` (`staffMw`).
10. **Migrations:** `php backend/bin/migrate.php` applies **all** `backend/migrations/*.sql` in filename order, tracked in `migrations_log`. Schema is more than the five files listed in `SYSTEM_REPORT.md`.
11. **Do not trust `HOW_TO_RUN.md` / `SYSTEM_REPORT.md` as API or table inventory** without diffing `index.php` and `migrations/`.
12. **`backend/dbtool/index.php`** is a separate PHP UI against the same `.env` database; it is not behind JWT.

---

*End of audit. No folder-structure recommendation is included.*
