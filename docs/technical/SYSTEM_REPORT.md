# FurEscue (Spot) — System Report & Documentation

**Version:** 1.0.0 (work in progress)
**Date:** August 2026
**Environment:** Windows · PHP 8.1+ · MySQL 8.x · Tailwind CSS (static frontend)

---

## 1. Overview

FurEscue is a rescue-management system for the **Mati City** area. It pairs a public-facing landing site with an **admin Command Center** dashboard. The dashboard is wired to a live REST API — **no mock or hardcoded data** — and currently focuses on the admin overview screens.

Two user-facing surfaces exist:

| Surface | File | Purpose |
|---------|------|---------|
| Landing page | `frontend/landing/index.html` | Public marketing page |
| Auth | `frontend/auth/login.html` | Login / register |
| Admin dashboard | `frontend/admin/index.html` | Command Center (this report's focus) |

---

## 2. Technology stack

| Layer | Technology |
|-------|------------|
| Frontend | Static HTML/CSS/JS — **no framework**, Tailwind CLI build, shadcn-style UI primitives |
| Styling | Tailwind CSS 3 (CLI, `npm run build`), plus plain CSS for admin-only components |
| Admin UI primitives | Custom shadcn-style `pagination.js`, `select.js`, `dropdown-menu.js` |
| Maps | Leaflet 1.9.4 + leaflet.heat 0.2.0 (OSM tiles, heatmap) |
| Backend | Vanilla PHP 8.1+ REST API (PDO + MySQL), no framework |
| Auth | JWT (`firebase/php-jwt`), HS256, access + refresh tokens, Google Sign-In support |
| Database | MySQL 8.0.13+ (uses `DEFAULT (UUID())`), UTF-8mb4 |
| Config | `vlucas/phpdotenv` (`.env`) |
| Tests | PHPUnit 10 (backend) |

---

## 3. Architecture

```
browser ── static pages ─────────────► API (JSON)   ──► MySQL
frontend/                    backend/public/index.php
  admin/index.html      Router → Controllers → Services → PDO
  landing/index.html
  auth/login.html
  js/lib/api.js  (base: http://localhost:8000/api/v1)
```

- **Frontend** is fully static; the Tailwind CSS is pre-compiled to `frontend/css/style.css`. The API base URL defaults to `http://localhost:8000/api/v1` and can be overridden via `window.FURESCUE_API_BASE_URL`.
- **Backend** is a single-entry `public/index.php` router. Responses are uniform:
  - Success: `{ success: true, data: ... }`
  - Paginated: `{ success: true, data: { items: [], meta: { page, per_page, total } } }`
  - Error: `{ success: false, error: { code, message } }`
- CORS is open (`Access-Control-Allow-Origin: *`), so the frontend can be opened directly or served separately.

---

## 4. Database schema (16 tables)

Applied via `backend/bin/migrate.php` from `backend/migrations/*.sql` (tracked in `migrations_log`).

| Migration | Tables |
|-----------|--------|
| `2024_01_01_000001_users.sql` | `users`, `rescuer_approvals`, `rescuer_duty_status` |
| `2024_01_01_000002_reports_cases.sql` | `reports`, `cases`, `case_activity_log` |
| `2024_01_01_000003_animals_health.sql` | `animals`, `animal_field_status`, `animal_medical_records`, `vitals_log` |
| `2024_01_01_000004_adoption.sql` | `adoption_listings`, `adoptions` |
| `2024_01_01_000005_messaging_learning.sql` | `messages`, `notifications`, `elearning_modules`, `elearning_progress` |

Key business tables & statuses:
- **reports** — `status` (pending_verification / verified / rejected), `validation_status` (pending / validated)
- **cases** — `status` (open / assigned / in_progress / resolved / closed)
- **animals** — `adoption_status` (available / pending_adoption / adopted), `health_status`
- **adoptions** — `status` (pending / approved / rejected / completed)
- **rescuer_duty_status** — `status` (off_duty / on_duty)
- **elearning_modules** — `published_status` (draft / published)
- **notifications** — `type`, `is_read`

---

## 5. API endpoints

Router-based REST API at `/api/v1`:

| Module | Endpoints |
|--------|-----------|
| Auth | `POST /auth/login`, `POST /auth/register`, `POST /auth/refresh`, `POST /auth/google`, `POST /auth/logout`, `GET /auth/me` |
| Reports | `GET/POST /reports`, `GET/PUT/DELETE /reports/{id}`, `GET /reports/map/heatmap`, `GET /reports?status=…` |
| Cases | `GET/POST /cases`, `GET/PUT/DELETE /cases/{id}`, case activity log |
| Animals | `GET/POST /animals`, `GET/PUT/DELETE /animals/{id}`, health records, field status, medical records |
| Adoption | `GET /adoptions`, listings, `POST /adoptions` (apply), status updates |
| Analytics | `GET /analytics/overview`, adoption trends, heatmap, etc. |
| Messaging | `GET/POST /messages`, conversations |
| Notifications | `GET /notifications`, mark read |
| E-Learning | `GET/POST /elearning`, modules, progress |
| Users | `GET/POST /users`, approvals, duty status |
| Vitals (IoT) | `POST /vitals` with `X-Device-Key` header |

---

## 6. Admin dashboard — what was built

The Command Center (`frontend/admin/index.html`) is wired to the live API. All numbers, lists, and charts come from seeded real data.

### 6.1 Dashboard layout (current)

```
┌──────────────────────────────────────────────────────────┐
│ Topbar (56px, sticky) — brand · search · notifications · │
│                          avatar profile dropdown         │
├──────────────────────────────────────────────────────────┤
│ Sidebar (192px, fixed)  ┃  Map Card (full width)        │
│  Dashboard / Reports    ┃   · case-density heat map      │
│  Cases / Animals        ┃   · Mati City bounds + expand  │
│  Adoptions / Users      ┃   · heat-intensity Select      │
│  E-Learning / Messages  ┃────────────────────────────────│
│  Analytics / Settings   ┃  Row 1 (equal): ChartCard │ ElearningCard │
│                          ┃  Row 2 (equal): ActivityTable │ AuditLogCard │
└──────────────────────────────────────────────────────────┘
```

### 6.2 Components & features

- **Case-density map** (`map.js`) — Leaflet + OSM tiles bounded to Mati City (`minZoom 11`, `maxBounds` + viscosity). Heat layer renders **verified + validated** reports only. A shadcn **Select** switches heat intensity (`normal`/`high`/`low`). **Expand** button toggles a 75vh map with `fitBounds`/`invalidateSize` reflow.
- **Charts** (`chart-card`) — adoption trend chart fed from analytics endpoints.
- **Health / E-Learning carousels** (`carousel.js`) — clone-first-slide infinite right-to-left loop (`INTERVAL_MS = 4000`, `TRANSITION_MS = 500`), dots navigation, centered empty states.
- **Queues** — Reports / Rescuers (pending) / Adoptions (pending) with **shadcn pagination** (`QUEUE_PAGE_SIZE = 7`), "View all N" links (shown only when N > 0), and centered empty states.
- **Recent case activity** — paginated table (`ACTIVITY_PAGE_SIZE = 5`).
- **Notifications** — topbar + Recent notifications list from `/notifications`.
- **Profile dropdown** (`dropdown-menu.js`) — avatar-only trigger with menu groups (Insights, System, Log Out), full keyboard + outside-click support.
- **Layout shell** — fixed 192px sidebar, sticky 56px topbar, scrollable `admin-main`, 16px gap system.

### 6.3 UI conventions

- System font Nunito; admin display font Fraunces; mono IBM Plex Mono.
- 16px for block-level gaps, 8px for micro gaps.
- Non-shared admin components use plain CSS in `admin/css/admin.css`; shared `frontend/js/components/ui/*` keep Tailwind classes.

---

## 7. Seed data (idempotent)

`backend/seeders/seed.php` — safe to re-run any number of times (every row is guarded by an existence/content-hash check; it only fills gaps).

Verified current state after seeding:

| Dataset | Count |
|---------|-------|
| Residents | 6 |
| Active rescuers (on duty) | 5 |
| Pending rescuer applicants | 2 |
| Reports (verified / pending) | 17 (13 / 4) |
| Heatmap points (verified + validated) | 13 |
| Cases (3 resolved) | 13 |
| Animals (4 adopted) | 10 |
| Health/field-status updates | 8 |
| Adoption listings | 7 |
| Adoptions (2 pending, 2 completed) | 8 |
| E-learning modules (5 published, 1 draft) | 6 |
| Notifications (all unread) | 24 |

Accounts (password `Password123!`): `admin@furescue.local`, `rescuer@furescue.local`–`rescuer7@furescue.local`, `juan@`/`maria@`/`ana@`/`pedro@`/`rosa@`/`miguel@furescue.local`.

---

## 8. Current status & next steps

### Done
- Full backend schema, migrations runner, REST API with JWT auth.
- Landing page, login/register flow.
- Admin dashboard with map, queues, charts, carousels, pagination, empty states, profile dropdown.
- Seeder producing a full realistic demo dataset; verified against the live DB.
- `HOW_TO_RUN.md` setup guide.

### Suggested next steps
- Resident / rescuer app screens (report submission form, case tracking).
- Settings page (user management UI, system config).
- Messaging module UI (conversation list + chat).
- Analytics reports & export screens.
- Integration tests for the new API endpoints.
- Production hardening (HTTPS, CORS allow-list, rate limiting).

---

*See `HOW_TO_RUN.md` for the full installation and run guide.*
