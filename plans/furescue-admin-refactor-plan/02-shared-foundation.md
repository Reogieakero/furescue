# Phase 1 --- Shared Admin Foundation

## Scope

This phase owns shared infrastructure only.

Do not migrate individual admin pages except where required to keep the
application functioning.

## Files owned by this phase

Primary:

-   `public/includes/admin-nav.php`
-   `public/includes/admin-shell.php`
-   `public/admin/js/layout/sidebar.js`

Potentially related shared files only when required:

-   `public/admin/css/admin.css`
-   shared admin JS imports
-   shared UI components identified as necessary by the audit

## Navigation

Create `public/includes/admin-nav.php` as the single navigation
configuration.

It should provide PHP data only. It must not contain page HTML.

Navigation entries should contain enough metadata for the shell to
render:

-   key
-   label
-   href
-   icon
-   group

Required primary routes include:

-   `/admin/`
-   `/admin/animals/`
-   `/admin/cases/`
-   `/admin/reports/`
-   `/admin/rescuers/`
-   `/admin/health-records/`
-   `/admin/analytics/`
-   `/admin/notifications/`

Correct the analytics path to `/admin/analytics/`.

## Admin shell

Refactor `admin-shell.php` to consume `admin-nav.php`.

The shell must render real links:

``` html
<a href="/admin/animals/">
```

Do not use `href="#"` or `data-nav` as the navigation mechanism.

Active navigation must be determined server-side from `$activeNav`.

## Sidebar JavaScript

Remove the duplicate navigation configuration from
`public/admin/js/layout/sidebar.js`.

If sidebar behavior is still needed, keep only behavior such as
responsive open/close and interaction with already-rendered DOM.

## Shared component safety

Do not rewrite shared components merely because the architecture is
changing.

Modify a shared component only when the audit demonstrates a
compatibility problem or a direct requirement.

Do not create page-specific copies of shared UI components.

## Acceptance criteria

-   `/admin/` still loads.
-   Existing pages still load.
-   Sidebar links are real URLs.
-   Active navigation works without JS.
-   No duplicate `NAV_GROUPS` remains.
-   Shared UI imports continue to resolve.
-   No API behavior changes.
