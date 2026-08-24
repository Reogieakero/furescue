# Phase 5 --- Final Admin Verification

## Routes

Verify:

-   `/admin/`
-   `/admin/animals/`
-   `/admin/cases/`
-   `/admin/reports/`
-   `/admin/rescuers/`
-   `/admin/health-records/`
-   `/admin/analytics/`
-   `/admin/notifications/`

## Viewports

Verify:

-   375px
-   768px
-   1440px

## For each route

Check:

-   direct navigation
-   hard refresh
-   trailing slash behavior
-   authorization guard
-   page HTML is present without JS rendering the whole page
-   sidebar rendering
-   active navigation
-   all sidebar links
-   profile/account links
-   page-specific links
-   cards
-   dialogs/modals
-   drawers
-   dropdowns
-   forms
-   filters
-   pagination
-   loading states
-   empty states
-   error states
-   API requests
-   browser console
-   network errors
-   asset loading

## Build

If CSS was changed:

``` bash
npm run build
```

Also run any existing project test/lint commands required by
`AGENTS.md`.

## Architecture checks

Confirm:

-   one navigation source
-   no duplicate `NAV_GROUPS`
-   no `href="#"` sidebar links
-   no unnecessary full-page DOM reconstruction
-   minimal `window.__PAGE_STATE__`
-   shared components are not duplicated
-   no API envelope changes
-   no router changes
-   no unrelated backend changes
