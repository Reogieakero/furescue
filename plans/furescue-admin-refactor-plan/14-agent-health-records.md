# Agent Plan --- Health Records

## Ownership

Primary target:

-   `public/admin/health-records/`

Legacy sources:

-   `public/admin/health-records.php`
-   `public/admin/health-record.php`
-   `public/admin/js/health-records.js`
-   `public/admin/js/health-record.js`
-   `public/admin/js/pages/health-records/*`
-   `public/admin/js/pages/health-record/*`
-   health-record CSS

## Target

Create:

``` text
public/admin/health-records/
├── index.php
├── health-record.php
├── partials/
├── js/
└── css/
```

## Work

Preserve health-record page/detail behavior.

Server-render initial HTML.

Move only page-specific interaction logic to page JS.

Reuse shared date picker, dialog, drawer, card, table, and other UI
behavior where applicable.

## Verify

-   records list
-   health record detail
-   health carousel/history
-   forms
-   dialogs
-   date picker
-   API calls
-   responsive behavior
