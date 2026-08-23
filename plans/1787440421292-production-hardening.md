# Sub-Plan 7: Production Hardening

**Parallel-safe with:** 1,2,3,4,5,6
**Files owned exclusively by this plan:**
- `src/Http/Response.php`
- `dbtool/index.php`
- `tests/*` (new test files)
- `docs/technical/*`

---

## 1. CORS Restriction

**Current state:** `Response::json` sets `Access-Control-Allow-Origin: *`.

**Actions:**
1. In `src/Http/Response.php`:
   - Replace wildcard origin with whitelist from env `CORS_ALLOWED_ORIGINS` (comma-separated).
   - Fallback to `*` only if env is unset (with a warning log).
   - Example:
     ```php
     $allowedOrigins = array_filter(array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '')));
     $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
     if (in_array($origin, $allowedOrigins, true)) {
         header('Access-Control-Allow-Origin: ' . $origin);
         header('Vary: Origin');
     }
     ```
   - Also restrict `Access-Control-Allow-Methods` to actual used methods: `GET, POST, PATCH, DELETE, OPTIONS`.
   - Keep `Access-Control-Allow-Headers: Authorization, Content-Type, X-Device-Key`.

---

## 2. Dev DB Viewer Auth

**Current state:** `dbtool/index.php` is unauthenticated.

**Actions:**
1. Add session guard to `dbtool/index.php`:
   - Require `guard.php` with `$requiredRole = 'admin'`.
   - Or add a simple shared secret check: `?key=DEVTOOL_KEY` matching `DEVTOOL_KEY` env var.
2. Update `.env.example` to include `DEVTOOL_KEY` placeholder.

---

## 3. Test Coverage

**Actions:**
1. Add `tests/ReportControllerTest.php`:
   - Test report creation with valid/invalid coordinates (Mati bounds).
   - Test dedup flagging.
   - Test resident scoping (`/reports/me`).
2. Add `tests/CaseControllerTest.php`:
   - Test case assignment with on-duty validation.
   - Test proof photo endpoint (once Plan 1 adds it).
   - Test status transitions.
3. Add `tests/ApiIntegrationTest.php`:
   - Boot `Router` and dispatch requests against it (like existing `JwtServiceTest` pattern).
   - Cover at least: login, register, create report, list animals, create adoption.

---

## 4. Documentation Drift

**Actions:**
1. Update `docs/technical/SYSTEM_REPORT.md`:
   - Replace `frontend/` + `backend/` paths with `public/` + `src/`.
   - Correct API endpoint table to match `public/index.php` routes.
   - Remove references to `POST /auth/logout`, `GET /auth/me` (actual: `GET /api/v1/users/me`).
   - Fix paginated envelope description (`data` is array, `meta` is sibling).
2. Update `docs/technical/HOW_TO_RUN.md`:
   - Ensure it references `public/auth/login.php`, `public/admin/index.php`.
   - Update curl examples to use `tokens.access_token`.
3. Update `docs/technical/ARCHITECTURE_AUDIT.md`:
   - Add note that codebase has been migrated to PHP-rendered pages (`public/*.php`).

---

## Acceptance Criteria

- [ ] CORS header is origin-specific, not `*`.
- [ ] `dbtool/index.php` redirects to login or requires key.
- [ ] `php vendor\phpunit\phpunit\phpunit` passes with new tests.
- [ ] Docs reflect actual repo layout and endpoints.
