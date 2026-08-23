# Sub-Plan 6: Analytics Exports

**Parallel-safe with:** 1,2,3,4,5,7
**Files owned exclusively by this plan:**
- `src/Controllers/AnalyticsController.php`
- `src/Http/Routes/AnalyticsRoutes.php`
- `public/admin/analytics/index.php` (new; URL `/admin/analytics/` -- Page Folder Convention from master plan)
- `public/admin/analytics/js/analytics.js` (new)

---

## 1. Export Endpoints (OBJ-5, REQ-A5)

**Current state:** `AnalyticsController::overview`, `adoptionTrends`, `healthUpdates` return JSON. No export formats.

**Actions:**
1. Add routes in `src/Http/Routes/AnalyticsRoutes.php`:
   ```php
   $router->add('GET', '/api/v1/analytics/overview/export', ..., [$authMw, $adminMw]);
   $router->add('GET', '/api/v1/analytics/adoption-trends/export', ..., [$authMw, $adminMw]);
   $router->add('GET', '/api/v1/analytics/health-updates/export', ..., [$authMw, $adminMw]);
   ```
2. Add export methods in `src/Controllers/AnalyticsController.php`:
   - `exportOverview`: query same data as `overview`, return CSV with headers: `Metric,Value,Date`.
   - `exportAdoptionTrends`: query same as `adoptionTrends`, return CSV with `Date,Count`.
   - `exportHealthUpdates`: query same as `healthUpdates`, return CSV.
   - Set response headers: `Content-Type: text/csv`, `Content-Disposition: attachment; filename="furescue-{metric}-{date}.csv"`.
   - **Optional stretch:** add PDF export using a lightweight approach (HTML table -> print dialog, or TCPDF if available). If no PDF library, skip to CSV only.

---

## 2. Admin Analytics Page

**Actions:**
1. Create `public/admin/analytics/index.php`:
   - Include `admin-shell.php`.
   - Date range picker (start, end).
   - Metric cards (reuse dashboard KPIs).
   - Export buttons: "Export Overview CSV", "Export Adoption Trends CSV", "Export Health CSV".
   - Table previews of each dataset.
   - Load the page module with an absolute path: `<script type="module" src="/admin/analytics/js/analytics.js">`.
2. Create `public/admin/analytics/js/analytics.js`:
   - Fetch data from existing endpoints.
   - Render tables.
   - On export click, open endpoint URL in new tab or trigger download via hidden iframe.

---

## Acceptance Criteria

- [ ] CSV exports download with correct headers and data.
- [ ] `/admin/analytics/` loads with date range filter and export buttons.
- [ ] No JS console errors.
