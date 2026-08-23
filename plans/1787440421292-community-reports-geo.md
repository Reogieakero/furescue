# Sub-Plan 2: Community Reports & Geo

**Parallel-safe with:** 1,3,4,5,6,7
**Files owned exclusively by this plan:** (new pages follow the master plan's Page Folder Convention)
- `public/report/index.php` (new; URL `/report/`)
- `public/reports/index.php` (new; URL `/reports/`)
- `public/report/js/report.js` (new)
- `public/reports/js/reports.js` (new)
- `public/includes/resident-shell.php` (new -- shared chrome, stays in `includes/`)
- `src/Controllers/ReportController.php`
- `src/Http/Routes/ReportRoutes.php`
- `public/css/input.css` (ONLY if new tokens/classes are needed; reuse existing Tailwind classes first, then rebuild with `npm run build`)

---

## 1. Resident Shell

Create `public/includes/resident-shell.php` as a lightweight shared chrome for all resident pages.

**Contract -- define BEFORE including:**
- `$pageTitle` string
- `$pageDescription` string
- `$activeNav` string (e.g. "reports", "animals")
- `$residentUser` array (id, full_name, email, role)
- `$navBadges` array (badgeKey => value)
- `$content` string (pre-rendered HTML for `<main>`)

**Layout:**
- Topbar: brand left, "My Reports" / "Animals" / "Adoptions" / "Learning" / "Messages" / "Notifications" nav links, user avatar + dropdown (Profile, Log out).
- Main: scrollable content slot.
- Footer: simple copyright.
- Mobile: hamburger menu that toggles nav links.

---

## 2. Public Report Submission (`public/report/index.php`)

**Current state:** `POST /api/v1/reports` accepts `animal_description`, `latitude`, `longitude`, `address_text`, `photo_urls`. No UI.

**Actions:**
1. Create `public/report/index.php`:
   - Include `resident-shell.php`.
   - Require login via session guard (reuse `guard.php` with `$requiredRole = 'resident'` or similar).
   - Form fields:
     - Animal description (textarea, max 2000 chars).
     - Location: map picker using Leaflet (same library as admin). Center on Mati City bounds. Click to place marker. Show lat/lng inputs populated by click. Also add "Use my current location" button using `navigator.geolocation.getCurrentPosition`.
     - Address text (optional, pre-filled by reverse geocode if possible, or manual input).
     - Photo URLs (textarea, one per line, max 4000 chars total). **Do NOT implement file upload here** -- that is Plan 2's media upload addition below. Start with URL-only to match current API.
   - On submit: POST to `/api/v1/reports` with JWT Bearer token from localStorage (`furescue_access_token`).
   - On success: redirect to `/reports/` with flash-style message.
   - Validation: show inline errors for missing description or location.
   - Load the page module with an absolute path: `<script type="module" src="/report/js/report.js">`.
2. Create `public/report/js/report.js`:
   - Initialize Leaflet map with Mati bounds.
   - Handle marker placement, reverse geocode via `/api/v1/geo/reverse` to fill address.
   - Handle form submission with `apiFetchFull`.

---

## 3. My Reports Tracking (`public/reports/index.php`)

**Actions:**
1. Create `public/reports/index.php`:
   - Include `resident-shell.php`.
   - Fetch `GET /api/v1/reports/me` (scoped to resident).
   - Render list of reports with status badges: `pending_verification`, `verified`, `dismissed`, `flagged_duplicate`.
   - Show description, barangay (address_text), date.
   - Empty state: "You haven't submitted any reports yet."
   - Load the page module with an absolute path: `<script type="module" src="/reports/js/reports.js">`.
2. Create `public/reports/js/reports.js`:
   - Fetch and render report list.
   - Add refresh button.

---

## 4. Report Media Upload (REQ-C3)

**Current state:** `ReportController::create` accepts `photo_urls` as JSON array of URL strings. `DocumentsController` has multipart upload logic for animal documents.

**Actions:**
1. In `src/Controllers/ReportController.php`:
   - Add `uploadMedia` method handling `multipart/form-data` with `$_FILES['photos']`.
   - Save files to `public/uploads/reports/{year}/{month}/` using same pattern as `DocumentsController`.
   - Return array of `/uploads/reports/...` URLs.
2. In `src/Http/Routes/ReportRoutes.php`:
   - Add `POST /api/v1/reports/{id}/media` route with `[$authMw]`.
3. In `public/report/index.php` / `public/report/js/report.js`:
   - Replace URL textarea with file input (`<input type="file" multiple accept="image/*,video/*">`).
   - On file select, upload each file to `/api/v1/reports/{id}/media` after report is created, or bundle with report creation.
   - **Simpler approach:** create report first (with placeholder), then upload media and PATCH report with new `photo_urls`.

---

## Acceptance Criteria

- [ ] `/report/` loads for logged-in residents (and `/report` redirects to `/report/`).
- [ ] Map picker places marker on click and fills lat/lng inputs.
- [ ] Form submits to `POST /api/v1/reports` and creates a report.
- [ ] `/reports/` shows the resident's own reports with correct statuses.
- [ ] Photo upload works via new `/api/v1/reports/{id}/media` endpoint.
- [ ] Responsive at 375px, 768px, 1440px.
