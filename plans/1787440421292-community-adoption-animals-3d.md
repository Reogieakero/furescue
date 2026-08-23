# Sub-Plan 3: Community Adoption, Animals & 3D

**Parallel-safe with:** 1,2,4,5,6,7
**Files owned exclusively by this plan:** (new pages follow the master plan's Page Folder Convention)
- `public/animals/index.php` (new; URL `/animals/` -- gallery)
- `public/animals/detail.php` (new; URL `/animals/detail.php?id={id}` -- sub-page in same folder)
- `public/adoptions/index.php` (new; URL `/adoptions/`)
- `public/listings/index.php` (new; URL `/listings/`)
- `public/animals/js/animals.js` (new)
- `public/animals/js/animal-detail.js` (new)
- `public/animals/js/3d-viewer.js` (new)
- `public/adoptions/js/adoptions.js` (new)
- `public/listings/js/listings.js` (new)
- `src/Controllers/AnimalController.php`
- `src/Controllers/AdoptionController.php`
- `src/Controllers/AdoptionListingController.php`
- `src/Http/Routes/AnimalRoutes.php`
- `src/Http/Routes/AdoptionRoutes.php`
- `src/Http/Routes/AdoptionListingRoutes.php`

---

## 1. Public Animal Gallery (`public/animals/index.php`)

**Current state:** `GET /api/v1/animals` supports filters but requires auth and is admin-only. No public gallery.

**Actions:**
1. Verify `AnimalController::index` allows unauthenticated or resident access. If not, add a guest token or relax the auth middleware for `index`/`show` when `adoption_status = 'available'`.
   - **Preferred:** Keep auth but resident JWT works. `authMw` allows any active user (resident, rescuer, admin).
2. Create `public/animals/index.php`:
   - Include `resident-shell.php`.
   - Fetch `GET /api/v1/animals?adoption_status=available&per_page=50`.
   - Grid of animal cards: photo_url (first image), name, species, breed_type, sex, age (if available), short description.
   - Filters: species (dog/cat), sex, breed_type, size (if available).
   - Search by name.
   - Click card -> `/animals/detail.php?id={id}` detail view.
   - Load the page module with an absolute path: `<script type="module" src="/animals/js/animals.js">`.
3. Create `public/animals/js/animals.js`:
   - Fetch, filter, render grid.
   - Handle pagination or "Load more".
   - Responsive grid: 1 col mobile, 2 col tablet, 3-4 col desktop.

---

## 2. Animal Detail & Adoption Apply

**Actions:**
1. Create `public/animals/detail.php` (sub-page of the animals folder, NOT a separate top-level page):
   - Fetch `GET /api/v1/animals/{id}`.
   - Show full details: photo_urls gallery, medical summary (vaccination_status, health_status), field-status history.
   - "Apply to Adopt" button (only if `adoption_status = 'available'`).
   - Load `<script type="module" src="/animals/js/animal-detail.js">`.
2. Create `public/animals/js/animal-detail.js`.
3. Create adoption apply flow in `public/adoptions/index.php`:
   - Form: animal_id (hidden), applicant notes.
   - POST to `POST /api/v1/adoptions` with `animal_id` and optional `message`.
   - Guard: only if animal is available (API returns 409 otherwise).
   - After apply: show "Application submitted" with status tracking.

---

## 3. My Adoption Applications

**Actions:**
1. In `public/adoptions/index.php` add "My Applications" tab:
   - Fetch `GET /api/v1/adoptions` (resident-scoped).
   - Show applicant_name, animal_name, status (pending/approved/rejected/completed), created_at.
   - Allow cancellation only if `pending`.

---

## 4. Resident Adoption Listings Management

**Actions:**
1. Create `public/listings/index.php`:
   - "Post for Adoption" button: opens modal to select an existing animal the resident owns (or a new animal entry flow).
   - POST to `POST /api/v1/adoption-listings` with `animal_id`.
   - List resident's own listings with status (`pending_review`, `approved`, `rejected`).
   - Show admin review notes if rejected.
   - Load the page module with an absolute path: `<script type="module" src="/listings/js/listings.js">`.
2. Create `public/listings/js/listings.js`.

---

## 5. 3D Profiling Viewer & Upload (OBJ-9)

**Current state:** `animals.model_3d_url` and `animals.photo_360_set` are accepted on create/update. No UI.

**Actions:**
1. In `public/admin/animals.php` (admin animal create/edit):
   - Add input fields: `model_3d_url` (text), `photo_360_set` (JSON textarea or file upload for zip of images).
   - These are already accepted by `AnimalController::create`/`update`; no backend change needed unless whitelist missing.
2. In `public/animals/index.php` (resident gallery) and `public/animals/detail.php`:
   - If `model_3d_url` exists, add "View in 3D" button.
   - Create `public/animals/js/3d-viewer.js` (new, lives inside the animals page folder) using Three.js (add import map entry or CDN) to render the model.
   - If `photo_360_set` exists, add a 360 image carousel viewer.
   - **Fallback if 3D is too complex:** show "3D model available at shelter" text and link.

---

## Acceptance Criteria

- [ ] `/animals/` shows available animals without admin login; `/animals/detail.php?id={id}` shows one animal.
- [ ] Animal detail shows full info and adoption apply works.
- [ ] `/adoptions/` shows my applications with statuses.
- [ ] `/listings/` lets residents post and view their listings.
- [ ] 3D viewer renders when `model_3d_url` is present.
- [ ] Responsive at 375px, 768px, 1440px.
