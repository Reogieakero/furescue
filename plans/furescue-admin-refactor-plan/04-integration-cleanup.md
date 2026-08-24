# Phase 3 --- Integration Cleanup

## Purpose

Perform a repository-wide cleanup after all page agents finish.

This phase is serialized.

## Search for obsolete architecture

Search for:

-   `NAV_GROUPS`
-   `data-nav`
-   `href="#"`
-   `/admin/animals.php`
-   `/admin/cases.php`
-   `/admin/reports.php`
-   `/admin/rescuers.php`
-   `/admin/health-records.php`
-   `document.body.innerHTML`
-   full-page `innerHTML =`
-   old `public/admin/js/pages/` imports
-   stale relative asset paths
-   obsolete page JS imports
-   duplicate component implementations

## Validate page structure

Confirm primary pages exist as:

-   `public/admin/index.php`
-   `public/admin/animals/index.php`
-   `public/admin/cases/index.php`
-   `public/admin/reports/index.php`
-   `public/admin/rescuers/index.php`
-   `public/admin/health-records/index.php`
-   `public/admin/analytics/index.php`
-   `public/admin/notifications/index.php`

## Remove obsolete files

Only remove old files after verifying:

-   no references remain
-   redirects are not required
-   no page depends on them

Prefer compatibility redirects for legacy `.php` URLs when appropriate.

## Do not redesign

This phase is cleanup and integration only.

Do not change API behavior, UI design, database code, or routing.
