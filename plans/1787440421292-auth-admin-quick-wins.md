# Sub-Plan 1: Auth & Admin UX Quick Wins

**Parallel-safe with:** 2,3,4,5,6,7
**Files owned exclusively by this plan:**
- `public/auth/login.php`
- `public/auth/signup.php` (new)
- `public/auth/js/auth.js`
- `public/admin/js/layout/app-shell.js`
- `public/admin/js/layout/sidebar.js`
- `public/admin/js/layout/topbar.js`
- `public/includes/admin-shell.php`
- `src/Http/Routes/CaseRoutes.php`
- `src/Controllers/CaseController.php`
- `src/Http/Routes/UserRoutes.php`
- `src/Controllers/UserController.php`

---

## 1. Registration UI (REQ-C1)

**Current state:** `POST /api/v1/auth/register` exists (`AuthController::register`). No UI calls it. Login page "Sign up" link is `href="#"`.

**Actions:**
1. Create `public/auth/signup.php`: (the `public/auth/` folder is already a section folder with its own `css/`+`js/`, so signup stays flat beside `login.php`; the Page Folder Convention from the master plan applies to top-level page sections, and auth is one section.)
   - Use `site-head.php` include.
   - Form fields: full_name, email, password (min 8), phone_number (optional), address (optional), role (resident|rescuer, default resident).
   - POST to `POST /api/v1/auth/register` via `apiFetchFull` from `public/js/lib/api.js`.
   - On success: store tokens in localStorage (`furescue_access_token`, `furescue_refresh_token`) and redirect to `/index.php`.
   - On 409 email taken: show inline error.
   - On rescuer role: show note "Your account will require admin approval."
   - Link back to login: "Already have an account? Sign in"
2. Update `public/auth/login.php`:
   - Change "Sign up" link from `href="#"` to `href="/auth/signup.php"`.
   - Wire "Continue with Google" button `href="#google"` to call `POST /api/v1/auth/google`:
     - Use Google Identity Services `google.accounts.id.initialize` + `google.accounts.id.prompt`.
     - On credential response, send `id_token` to `/api/v1/auth/google`.
     - On success, same token storage + redirect as signup.
   - Wire "Forgot password?" to a placeholder page or remove link.

---

## 2. Admin Nav Targets (Fix orphan sidebar labels)

**Current state:** `sidebar.js` NAV_GROUPS lists Listings, Applications, E-Learning, Messages, Notifications. `app-shell.js` NAV_TARGETS only has dashboard/reports/cases/rescuers/animals/health records. Clicking orphans does nothing.

**Actions:**
1. Update `public/admin/js/layout/app-shell.js` NAV_TARGETS:
   ```js
   const NAV_TARGETS = {
     dashboard: "/admin/index.php",
     reports: "/admin/reports.php",
     cases: "/admin/cases.php",
     rescuers: "/admin/rescuers.php",
     animals: "/admin/animals.php",
     "health records": "/admin/health-records.php",
     listings: "/admin/listings/",        // new page from Plan 3 (folder convention)
     applications: "/admin/applications/", // new page from Plan 3 (folder convention)
     "e-learning": "/admin/elearning/",    // new page from Plan 4 (folder convention)
     messages: "/admin/messages/",         // new page from Plan 4 (folder convention)
     notifications: "/admin/notifications/", // new page from Plan 5 (folder convention)
   };
   ```
   These admin targets may not exist yet when this plan runs (other plans own them) -- pointing at the folder URLs is enough; stubs land later.
2. Update `public/admin/js/layout/sidebar.js`:
   - Remove hardcoded badge values (`badge: "14"`, `badge: "9"`, etc.) so badges are purely dynamic from `getNavBadges()`.
   - Ensure `data-nav` values match NAV_TARGETS keys exactly (lowercase).
3. Update `public/includes/admin-shell.php`:
   - Same NAV_GROUPS structure as sidebar.js (keep in sync).
    - Remove dead profile menu items OR wire them:
      - "Analytics" -> `/admin/analytics/` (Plan 6, folder convention)
      - "Reports & Exports" -> `/admin/reports.php`
      - "Users" -> `/admin/rescuers.php`
      - "Settings" -> remove or point to stub
    - Wire topbar search input to a search handler that filters the current page or redirects to `/admin/cases.php?q=...`.
    - Wire bell button to `/admin/notifications/` (Plan 5, folder convention).

---

## 3. Resolution Proof Photos (Case Routes)

**Current state:** `public/admin/js/pages/case-detail/components/events.js` calls `POST /cases/{id}/proof` but no route exists. `cases.resolution_photos` column exists (migration 7) but is never read/written.

**Actions:**
1. Add route in `src/Http/Routes/CaseRoutes.php`:
   ```php
   $router->add('POST', '/api/v1/cases/{id}/proof', fn(Request $r) => (new CaseController($pdo))->proof($r), [$authMw, $staffMw]);
   ```
2. Add method in `src/Controllers/CaseController.php`:
   ```php
   public function proof(Request $req): void
   {
       $v = new \App\Validation\Validator($req->body);
       $v->required('url')->string(2000);
       if (!$v->passes()) {
           Response::error('VALIDATION_ERROR', $v->firstError(), 400);
           return;
       }
       $case = $this->cases->find($req->params['id']);
       if (!$case) {
           Response::error('NOT_FOUND', 'Case not found', 404);
           return;
       }
       $existing = $case->resolutionPhotos() ?? [];
       if (!is_array($existing)) {
           $existing = json_decode((string) $existing, true) ?: [];
       }
       $existing[] = $req->body['url'];
       $this->cases->update($case->id(), ['resolution_photos' => json_encode(array_values(array_unique($existing)))]);
       Response::success(['proof' => $existing]);
   }
   ```
   - Note: Add `resolution_photos` to `CaseRepository` whitelist/columns if needed.
3. Update `events.js`:
   - Ensure it reads `state.proof` from the case detail API response and renders the proof list.

---

## 4. Rescuer Duty Toggle

**Current state:** `rescuer_duty_status` table exists. `CaseController::assign` enforces `on_duty`. No endpoint or UI toggles it.

**Actions:**
1. Add route in `src/Http/Routes/UserRoutes.php`:
   ```php
   $router->add('PATCH', '/api/v1/rescuers/{id}/duty', fn(Request $r) => (new UserController($pdo))->toggleDuty($r), [$authMw, $adminMw]);
   ```
2. Add method in `src/Controllers/UserController.php`:
   ```php
   public function toggleDuty(Request $req): void
   {
       $v = new \App\Validation\Validator($req->body);
       $v->required('status')->in('status', ['on_duty', 'off_duty']);
       if (!$v->passes()) {
           Response::error('VALIDATION_ERROR', $v->firstError(), 400);
           return;
       }
       $user = $this->users->find($req->params['id']);
       if (!$user || $user->role() !== 'rescuer') {
           Response::error('NOT_FOUND', 'Rescuer not found', 404);
           return;
       }
       $this->pdo->prepare("
           INSERT INTO rescuer_duty_status (id, user_id, status, updated_at)
           VALUES (?, ?, ?, NOW())
           ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()
       ")->execute([Database::uuidV4(), $user->id(), $req->body['status']]);
       Response::success(['duty_status' => $req->body['status']]);
   }
   ```
   - Note: Use MySQL `ON DUPLICATE KEY UPDATE` or `REPLACE INTO` depending on migration schema.
3. Add duty toggle UI in `public/admin/rescuers.php`:
   - Add a toggle button/slider per rescuer row calling `PATCH /api/v1/rescuers/{id}/duty`.
   - Use `admin-data.js` pattern: add `toggleRescuerDuty(id, status)` export.

---

## 5. Topbar Search & Profile

**Actions:**
1. In `public/admin/js/layout/topbar.js` (or inline in `admin-shell.php`):
   - Search input: on Enter, redirect to `/admin/cases.php?q=` + value, or show a dropdown with matching cases/reports/animals.
   - Bell button: redirect to `/admin/notifications/`.
   - Profile menu items: wire to real URLs or remove `<a href="#">` stubs.

---

## Acceptance Criteria

- [ ] `/auth/signup.php` loads, validates, creates user via API, redirects.
- [ ] Google Sign-In button opens GIS prompt and completes auth.
- [ ] Sidebar Listings/Applications/E-Learning/Messages/Notifications links navigate to real URLs (even if pages are stubs from other plans).
- [ ] `POST /api/v1/cases/{id}/proof` returns 200 and persists JSON array.
- [ ] `PATCH /api/v1/rescuers/{id}/duty` toggles status in DB.
- [ ] Topbar search redirects; profile menu items are live.
