# FurEscue (Spot) — System Report & Documentation

**Version:** 1.1.0
**Date:** August 2026
**Environment:** Windows · PHP 8.1+ · MySQL 8.x · PHP-rendered pages + Tailwind CSS

---

## 1. Overview

FurEscue is a rescue-management system for the **Mati City** area. It pairs a public-facing landing site with an **admin Command Center** dashboard. The dashboard is wired to a live REST API — **no mock or hardcoded data**.

**One server serves everything**: a single PHP entry point serves both the JSON API (`/api/v1/...`) and all pages/assets from the web root `public/`. Pages are PHP-rendered using shared partials in `public/includes/` (with ES-module islands for interactivity).

| Surface | File | Purpose |
|---------|------|---------|
| Landing page | `public/includes/homepage.php` (served at `/`) | Public marketing page |
| Auth | `public/auth/login.php` | Login / register |
| Admin dashboard | `public/admin/index.php` | Command Center |

---

## 2. Technology stack

| Layer | Technology |
|-------|------------|
| Frontend | PHP-rendered pages (no framework), shared partials, ES-module islands |
| Styling | Tailwind CSS 3 (CLI, `npm run build`: `public/css/input.css` → `style.css`), design tokens as CSS variables |
| Maps | Leaflet 1.9.4 + leaflet.heat 0.2.0 (OSM tiles, heatmap) |
| Backend | Vanilla PHP 8.1+ REST API (PDO + MySQL), no framework, custom router |
| Auth | JWT (`firebase/php-jwt`), HS256, access + refresh tokens, Google Sign-In support; session-based auth for PHP pages (`src/Auth/SessionAuth.php`) |
| Database | MySQL 8.0.13+ (uses `DEFAULT (UUID())`), UTF-8mb4 |
| Config | `vlucas/phpdotenv` (`.env`) |
| Tests | PHPUnit 10 (`tests/`, pure unit tests on an in-memory SQLite schema where a DB is needed) |

---

## 3. Architecture

```
browser ── pages + fetch ──► php -S 127.0.0.1:8000 -t public public\index.php
                              ├─ real files under public/ served statically
                              ├─ /uploads/* short-circuit
                              └─ everything else → App\Http\Router (JSON API)
                                   Router → Middleware → Controllers → Services → Repositories → PDO → MySQL
```

- **Code layout:** backend lives at the repo root — `src/` (PSR-4 `App\`), `bin/migrate.php`, `migrations/*.sql`, `seeders/seed.php`, `tests/`, plus web root `public/`.
- **Routing:** one route line per endpoint in `src/Http/Routes/*Routes.php`, all wired by `RouteLoader::register($router, $deps)` from `public/index.php`. Shared dependencies (PDO, JWT, middleware) travel in `$deps`.
- **Responses** are a uniform JSON envelope:
  - Success: `{ success: true, data: ..., error: null }`
  - Paginated: `{ success: true, data: [ ...items ], meta: { page, per_page, total }, error: null }` — **`data` is the item array; `meta` is a sibling key**
  - Error: `{ success: false, data: null, error: { code, message } }`
- **CORS** is restricted by default policy: `Response::json` reads a comma-separated origin allow-list from env `CORS_ALLOWED_ORIGINS` and echoes back only origins on the list (adding `Vary: Origin`). If the variable is unset, it falls back to `Access-Control-Allow-Origin: *` **and logs a warning** — set the variable before exposing the API beyond localhost. Allowed methods reflect the routes actually registered; allowed headers are `Authorization, Content-Type, X-Device-Key`.
- **Auth model:** Bearer JWT access token + refresh token for `/api/v1`; role gates via `RoleMiddleware` (`admin` vs staff `rescuer|admin`). Exception: `POST /api/v1/vitals` authenticates with the `X-Device-Key` header against `DEVICE_API_KEY`.
- **Geovalidation:** reports outside the Mati City bounding box (`MATI_LAT_MIN/MAX`, `MATI_LNG_MIN/MAX`) are rejected with `OUT_OF_BOUNDS`.

---

## 4. Database schema (17 tables, 13 migrations)

Applied via `php bin/migrate.php` from `migrations/*.sql` (applied files tracked in `migrations_log`; never edit applied migrations).

| Migration | Tables / changes |
|-----------|------------------|
| `2024_01_01_000001_users.sql` | `users`, `rescuer_approvals`, `rescuer_duty_status` |
| `2024_01_01_000002_reports_cases.sql` | `reports`, `cases`, `case_activity_log` |
| `2024_01_01_000003_animals_health.sql` | `animals`, `animal_field_status`, `animal_medical_records`, `vitals_log` |
| `2024_01_01_000004_adoption.sql` | `adoption_listings`, `adoptions` |
| `2024_01_01_000005_messaging_learning.sql` | `messages`, `notifications`, `elearning_modules`, `elearning_progress` |
| `000006`–`000013` | ALTERs + `animal_documents` table (case `open` status, resolution photos, soft deletes, health-record fields, documents, vaccine protocols, animal birth date) |

Key business tables & statuses:

- **reports** — `status` (pending_verification / verified / dismissed), `validation_status` (pending / validated / flagged_duplicate / invalid)
- **cases** — `status` (open / assigned / in_progress / resolved)
- **animals** — `adoption_status` (not_listed / available / pending / adopted), soft delete via `deleted_at`
- **adoptions** — `status` (pending / approved / rejected / completed)
- **rescuer_duty_status** — `status` (off_duty / on_duty); case assignment requires an on-duty rescuer
- **elearning_modules** — published/draft flag
- **notifications** — `type`, `is_read`

---

## 5. API endpoints

All endpoints are prefixed `/api/v1` and registered under `src/Http/Routes/`. `(admin)` / `(staff)` marks role gates beyond authentication.

| Module | Endpoints |
|--------|-----------|
| Auth | `POST /auth/register`, `POST /auth/login`, `POST /auth/google`, `POST /auth/refresh` |
| Users | `GET /users/me`, `GET /users` (admin), `GET /users/{id}`, `PATCH /users/{id}`, `POST /admin/rescuers/{id}/approve`, `POST /admin/rescuers/{id}/reject` (both admin). There is no logout/me endpoint pair — logout is client-side token discard, profile is `GET /users/me` |
| Reports | `POST /reports`, `GET /reports`, `GET /reports/me` (resident-scoped), `GET /reports/{id}`, `GET /reports/map/heatmap`, `POST /reports/{id}/verify` (admin), `POST /reports/{id}/dismiss` (admin) |
| Cases | `GET /cases`, `GET /cases/{id}`, `GET /cases/{id}/activity`, `POST /cases/{id}/assign` (admin, requires on-duty rescuer), `PATCH /cases/{id}/status` (staff) |
| Animals | `POST /animals` (admin), `GET /animals`, `GET /animals/{id}`, `PATCH /animals/{id}` (admin), `DELETE /animals/{id}` (admin, soft delete), `POST+GET /animals/{id}/field-status` (POST staff), `GET+PUT /animals/{id}/medical` (admin), `GET /animals/{id}/health-record` (admin) |
| Vitals (IoT) | `POST /vitals` with `X-Device-Key` header (no JWT), `GET /animals/{id}/vitals` (staff), `POST /animals/{id}/vitals` (admin) |
| Documents | `POST /animals/{id}/documents` (admin, multipart upload), `PATCH /documents/{id}` (admin), `DELETE /documents/{id}` (admin) |
| Adoption | `POST /adoption-listings`, `GET /adoption-listings`, `GET /adoption-listings/{id}`, `POST /adoption-listings/{id}/approve` or `/reject` (admin), `POST /adoptions` (apply), `GET /adoptions`, `GET /adoptions/{id}`, `POST /adoptions/{id}/approve` or `/reject` or `/complete` (admin) |
| Messaging | `POST /messages`, `GET /messages`, `PATCH /messages/{id}/read` |
| Notifications | `GET /notifications`, `PATCH /notifications/{id}/read`, `POST /notifications/read-all`, `GET /notifications/unread-count`, `POST /admin/notifications` (admin broadcast), `DELETE /admin/notifications/{id}` (admin) |
| E-Learning | `GET /elearning/modules`, `POST /elearning/modules` (admin), `GET /elearning/modules/{id}`, `PATCH /elearning/modules/{id}` (admin), `GET+POST /elearning/progress` |
| Analytics & Health | `GET /analytics/overview` (admin), `GET /analytics/adoption-trends` (admin), `GET /health/updates` (admin), `GET /health/records` (admin), `GET /health/activity` (admin) |
| Geo | `GET /geo/reverse?lat=…&lng=…` (Nominatim reverse geocode) |

---

## 6. Admin dashboard

The Command Center (`public/admin/index.php`, shell built from `public/includes/admin-shell.php`) is wired to the live API. All numbers, lists, and charts come from seeded real data.

### 6.1 Components & features

- **Case-density map** — Leaflet + OSM tiles bounded to Mati City. Heat layer renders verified + validated reports only. Select switches heat intensity (`normal`/`high`/`low`). Expand toggles a larger map with `fitBounds`/`invalidateSize` reflow.
- **Charts** — adoption trend chart fed from analytics endpoints.
- **Queues** — Reports / Rescuers (pending) / Adoptions (pending) with pagination and empty states.
- **Recent case activity** — paginated table.
- **Notifications** — topbar + list from `/notifications`.
- **Layout shell** — sidebar + sticky topbar built from shared partials, responsive down to mobile widths.

### 6.2 Design system

- Tokens (colors, radius, shadows, fonts) live as CSS variables in `public/css/input.css` (`:root` / `.dark`) and map to Tailwind classes in `tailwind.config.js`.
- Icons: Lucide only, loaded via esm.sh import maps.
- Shared primitives (`pagination.js`, `select.js`, `dropdown-menu.js`) stay in `public/js/components/`.

---

## 7. Seed data (idempotent)

`php seeders/seed.php` — safe to re-run any number of times (every row is guarded by an existence check; it only fills gaps). Demo accounts use password `Password123!`: `admin@furescue.local`, `rescuer@furescue.local`–`rescuer7@furescue.local`, and residents `juan@`/`maria@`/`ana@`/`pedro@`/`rosa@`/`miguel@furescue.local`. Full dataset details: see `HOW_TO_RUN.md`.

---

## 8. Current status & next steps

### Done
- Full backend schema, migrations runner, REST API with JWT auth, role gates, device-key ingest.
- Landing, login/register, admin dashboard, and resident/rescuer flows as PHP-rendered pages sharing partials.
- Seeder producing a full realistic demo dataset; idempotent.
- PHPUnit suite covering JWT, validation, dedup hashing, geo bounds, notifications broadcast, report/case controller flows, and router-level API integration tests.
- Configurable CORS origin allow-list with wildcard fallback warning; keyed access gate for the dev DB viewer (`dbtool/?key=<DEVTOOL_KEY>`).
- `HOW_TO_RUN.md` setup guide kept current with the single-server layout.

### Suggested next steps
- Rate limiting on auth + public submission endpoints.
- HTTPS termination guidance for production deploys (no `.htaccess`/nginx config exists in-repo yet).
- Proof-photo upload endpoint for cases (`POST /cases/{id}/proof`) to close the gap with the case-detail UI.

---

*See `HOW_TO_RUN.md` for the full installation and run guide.*
