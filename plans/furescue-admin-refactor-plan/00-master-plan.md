# FurEscue Admin Frontend Refactor --- Master Plan

## Objective

Refactor `public/admin/` from the current mixed PHP/client-rendered
architecture into a consistent folder-per-page architecture.

Core rule:

-   PHP renders the initial page HTML.
-   JavaScript provides behavior only.
-   Existing shared UI components remain shared.
-   Page-specific behavior lives with its page.
-   `public/includes/admin-nav.php` becomes the single source of admin
    navigation.
-   Existing `/api/v1` contracts remain unchanged.
-   Existing visual design and design tokens remain intact.

## Current architecture

The admin area currently contains flat PHP pages, two already-folderized
pages, duplicated navigation, page-level JavaScript renderers, shared JS
UI components, admin-specific JS components, and shared/page-specific
CSS.

Important existing shared layers:

-   `public/js/components/ui/`
-   `public/js/lib/`
-   `public/admin/includes/ui-helpers.php`
-   `public/admin/css/admin.css`
-   `public/includes/admin-shell.php`

## Target structure

``` text
public/
├── includes/
│   ├── admin-nav.php
│   ├── admin-shell.php
│   ├── guard.php
│   └── site-head.php
├── js/
│   ├── components/
│   │   └── ui/
│   └── lib/
└── admin/
    ├── index.php
    ├── includes/
    │   └── ui-helpers.php
    ├── css/
    │   └── admin.css
    ├── animals/
    │   ├── index.php
    │   ├── partials/
    │   ├── js/
    │   └── css/
    ├── cases/
    │   ├── index.php
    │   ├── case-detail.php
    │   ├── partials/
    │   ├── js/
    │   └── css/
    ├── reports/
    │   ├── index.php
    │   ├── partials/
    │   ├── js/
    │   └── css/
    ├── rescuers/
    │   ├── index.php
    │   ├── partials/
    │   ├── js/
    │   └── css/
    ├── health-records/
    │   ├── index.php
    │   ├── health-record.php
    │   ├── partials/
    │   ├── js/
    │   └── css/
    ├── analytics/
    │   ├── index.php
    │   ├── js/
    │   └── css/
    └── notifications/
        ├── index.php
        └── js/
```

`public/admin/index.php` remains `/admin/`.

## Execution model

``` text
Phase 0: Read-only audit
        ↓
Phase 1: Shared foundation
        ↓
Phase 2: Parallel page migrations
        ↓
Phase 3: Integration cleanup
        ↓
Phase 4: Component regression audit
        ↓
Phase 5: Final browser verification
```

Phase 2 is the parallelizable portion. All other phases should be
serialized.

## Non-goals

Do not:

-   change the router
-   redesign the API
-   change JSON envelopes
-   introduce a JS framework
-   introduce a new component library
-   rewrite the visual design
-   duplicate shared UI components per page
-   move business logic into frontend page files
-   perform unrelated backend cleanup

## Completion criteria

The refactor is complete when all primary admin routes work by direct
navigation and hard refresh, navigation is server-rendered from one
configuration, PHP renders initial page HTML, page JavaScript contains
behavior rather than full-page rendering, shared components continue
working, and the existing API/design system remain intact.
