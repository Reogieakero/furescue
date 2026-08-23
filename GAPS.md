# GAPS.md — FurEscue Categorized Gap Inventory

Generated from: `docs/technical/IMPLEMENTATION_AUDIT.md`, `docs/technical/FEATURES.md`, `docs/technical/ARCHITECTURE_AUDIT.md`, `docs/technical/SYSTEM_REPORT.md`

---

## 1. Auth & Sessions

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | No registration UI; sign-up/Google/forgot-password links are dead stubs | 🎨 UI-only stub | REQ-C1 | doc:FEATURES.md:24 · doc:IMPLEMENTATION_AUDIT.md:30 · code:public/auth/login.php | P1 | Build sign-up screen wired to `POST /api/v1/auth/register`; wire Google button to `POST /api/v1/auth/google` |
| 2 | Admin login redirects to nonexistent `/admin/index.php` (only `.html` exists) | Bug: redirect mismatch | REQ-C1 | doc:FEATURES.md:24 · doc:IMPLEMENTATION_AUDIT.md:30 · code:public/auth/login.php | P0 | Change redirect target to `/admin/index.html` |
| 3 | Parallel unintegrated auth systems (session login vs JWT APIs) with no coherent user flow | 🟡 Partially implemented | REQ-C1 | doc:IMPLEMENTATION_AUDIT.md:30 · doc:FEATURES.md:24 | P2 | Unify admin on JWT session or migrate fully to token-based auth |

---

## 2. Community (Resident) Experience

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | No resident-facing report submission or tracking UI | ❌ Not implemented | REQ-C2, OBJ-2 | doc:IMPLEMENTATION_AUDIT.md:16 · doc:FEATURES.md:42 · doc:ARCHITECTURE_AUDIT.md:159 | P0 | Build public report form + my-reports list consuming `POST /api/v1/reports` and `GET /reports/me` |
| 2 | No public animal browsing/adoption gallery | ❌ Not implemented | REQ-C4, OBJ-1 | doc:IMPLEMENTATION_AUDIT.md:15 · doc:FEATURES.md:83 | P0 | Build resident animal grid consuming `GET /api/v1/animals` filtered by `adoption_status` |
| 3 | No resident adoption application or status-tracking UI | ❌ Not implemented | REQ-C5, OBJ-1 | doc:IMPLEMENTATION_AUDIT.md:34 · doc:FEATURES.md:84 | P0 | Build adoption apply/track screens consuming `/adoptions` endpoints |
| 4 | No e-learning lesson viewer or progress tracker for residents | ❌ Not implemented | REQ-C7, OBJ-8 | doc:IMPLEMENTATION_AUDIT.md:22 · doc:FEATURES.md:108 | P1 | Build resident learning UI consuming `/elearning/modules` and `/elearning/progress` |

---

## 3. Admin UX / Admin Pages

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | Resolution proof photo button always errors (no backend route) | 🎨 UI-only stub | n/a | doc:FEATURES.md:57 · doc:IMPLEMENTATION_AUDIT.md:79 · code:public/admin/js/pages/case-detail/components/events.js | P0 | Implement `POST /api/v1/cases/{id}/proof` and persist to `cases.resolution_photos` |
| 2 | Sidebar links for Listings/Applications/E-Learning/Messages/Notifications and topbar search/profile are dead | 🎨 UI-only stub | n/a | doc:FEATURES.md:141 · doc:IMPLEMENTATION_AUDIT.md:79 · code:public/admin/js/layout/sidebar.js · code:public/admin/js/layout/app-shell.js | P1 | Wire `NAV_TARGETS` to real pages or remove orphan labels; implement search handler and profile actions |

---

## 4. Notifications

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | No real-time notification delivery (no WebSocket/SSE/poll) | ❌ Not implemented | OBJ-4, REQ-S4 | doc:IMPLEMENTATION_AUDIT.md:18 · doc:FEATURES.md:92 | P1 | Add polling or SSE to notification endpoints for live badge/inbox updates |
| 2 | No admin-initiated notification compose or broadcast | ❌ Not implemented | REQ-A6 | doc:IMPLEMENTATION_AUDIT.md:81 · doc:FEATURES.md:93 | P1 | Add admin broadcast endpoint + compose UI in admin shell |
| 3 | No resident notification inbox surface | ❌ Not implemented | REQ-C6 | doc:IMPLEMENTATION_AUDIT.md:36 · doc:FEATURES.md:91 | P1 | Build resident inbox page consuming `GET /api/v1/notifications` (see also: Community Experience) |

---

## 5. Messaging

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | No messaging UI (sidebar link has no target; zero frontend callers) | ❌ Not implemented | OBJ-6 | doc:IMPLEMENTATION_AUDIT.md:20 · doc:FEATURES.md:100 · code:public/admin/js/layout/sidebar.js | P1 | Build conversation list + chat UI consuming `GET/POST /api/v1/messages` |

---

## 6. E-Learning

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | No module authoring CMS for admins | 🎨 UI-only stub | OBJ-8 | doc:IMPLEMENTATION_AUDIT.md:22 · doc:FEATURES.md:108 | P2 | Build admin module editor consuming `/elearning/modules` CRUD |
| 2 | No lesson viewer or progress tracker for residents | 🎨 UI-only stub | REQ-C7, OBJ-8 | doc:IMPLEMENTATION_AUDIT.md:22 · doc:FEATURES.md:108 | P1 | Build resident learning UI consuming `/elearning/modules` and `/elearning/progress` (see also: Community Experience) |

---

## 7. Reports & Analytics

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | No exportable/downloadable reports (CSV/PDF/print) | ❌ Not implemented | OBJ-5, REQ-A5 | doc:IMPLEMENTATION_AUDIT.md:19 · doc:FEATURES.md:126 | P2 | Add export endpoints and print/export buttons in dashboard/analytics views |

---

## 8. Media & Uploads

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | Reports accept only photo URL strings; no multipart upload endpoint or video support | 🟡 Partially implemented | REQ-C3 | doc:IMPLEMENTATION_AUDIT.md:32 · doc:FEATURES.md:47 · code:src/Controllers/ReportController.php | P1 | Wire multipart upload to reports (reuse `DocumentsController` pattern) and scope video support |

---

## 9. Lifecycle / Workflow

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | No endpoint or UI to toggle rescuer `on_duty` status | 🟡 Partially implemented | n/a | doc:IMPLEMENTATION_AUDIT.md:83 · doc:FEATURES.md:36 · code:src/Http/Routes/CaseRoutes.php | P2 | Add `PATCH /api/v1/rescuers/{id}/duty` and a toggle in the Rescuers page |

---

## 10. 3D & Profiling

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | No 3D viewer/360 renderer, upload flow, or admin inputs for `model_3d_url`/`photo_360_set` | ⚙️ Backend-only | OBJ-9 | doc:IMPLEMENTATION_AUDIT.md:23 · doc:FEATURES.md:65 · code:migrations/2024_01_01_000003_animals_health.sql | P2 | Build 3D capture/upload pipeline or descope OBJ-9 |

---

## 11. Geo / Maps

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | No geolocation capture UI for reporters (coordinates arrive only as JSON body fields) | 🟡 Partially implemented | OBJ-3 | doc:IMPLEMENTATION_AUDIT.md:17 · doc:FEATURES.md:42 | P1 | Add map-picker/geolocation input to public report form |

---

## 12. Production Hardening

| # | Gap (one line) | Status | Requirement ref(s) | Evidence (doc + file/line or route) | Priority | Suggested fix (one line) |
|---|---|---|---|---|---|---|
| 1 | CORS open wildcard (`Access-Control-Allow-Origin: *`) | ⚙️ Backend-only | n/a | doc:FEATURES.md:147 · doc:ARCHITECTURE_AUDIT.md:57 · doc:SYSTEM_REPORT.md:55 | P1 | Restrict CORS to known origins and tighten allowed methods/headers |
| 2 | Unauthenticated dev DB viewer (`dbtool/index.php`) reachable in production | Bug: unauthenticated dev tool | n/a | doc:FEATURES.md:153 · doc:ARCHITECTURE_AUDIT.md:219 · code:dbtool/index.php | P1 | Require auth for `dbtool` or exclude it from production deployments |
| 3 | PHPUnit coverage limited to 6 unit classes; no endpoint/integration tests | 🟡 Partially implemented | n/a | doc:FEATURES.md:152 · doc:IMPLEMENTATION_AUDIT.md:86 | P2 | Add integration tests for controllers/routes and expand unit coverage |
| 4 | Doc drift: `SYSTEM_REPORT.md` and `HOW_TO_RUN.md` reference non-existent `frontend/`+`backend/` split and wrong endpoints | Bug: doc drift | n/a | doc:ARCHITECTURE_AUDIT.md:5 · doc:IMPLEMENTATION_AUDIT.md:86 · doc:SYSTEM_REPORT.md:42 | P2 | Update docs to reflect actual `src/`+`public/` layout and correct endpoint inventory |
| 5 | Default DB driver is `pgsql` when `DB_DRIVER` is unset, but migrations/docs target MySQL | Bug: env hygiene | n/a | doc:ARCHITECTURE_AUDIT.md:21 · doc:SYSTEM_REPORT.md:174 | P1 | Default `DB_DRIVER` to `mysql` in `Database.php` or enforce presence in `.env` |
| 6 | `.env.example` lacks secrets hygiene guidance | ⚙️ Backend-only | n/a | doc:IMPLEMENTATION_AUDIT.md:86 | P2 | Add `.env.example` placeholders with comments for rotation and `.env` gitignore enforcement |

---

Total gaps: 22 across 12 categories

Top 3 P0 risks:
1. No resident-facing application surface (reporting, adoption, notifications, e-learning, messaging) — blocks 5 objectives
2. Admin login redirect mismatch to `/admin/index.php`
3. Resolution proof photo button always errors due to missing backend route
