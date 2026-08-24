# Agent Plan --- Cases

## Ownership

Primary target:

-   `public/admin/cases/`

Legacy sources:

-   `public/admin/cases.php`
-   `public/admin/case-detail.php`
-   `public/admin/js/cases.js`
-   `public/admin/js/case-detail.js`
-   `public/admin/js/pages/cases/*`
-   `public/admin/js/pages/case-detail/*`
-   relevant case CSS

## Target

Create:

``` text
public/admin/cases/
├── index.php
├── case-detail.php
├── partials/
├── js/
└── css/
```

## Work

Server-render existing case page and case-detail markup.

Keep API behavior unchanged.

Move page-specific interaction logic to `cases/js/`.

Use shared dialog/drawer/card/etc. components where applicable.

## Verify

-   case list
-   case detail
-   filters
-   actions
-   drawers/dialogs
-   links
-   API calls
-   responsive behavior

Do not modify shared navigation.
