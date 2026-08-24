# Agent Plan --- Animals

## Ownership

Primary target:

-   `public/admin/animals/`

Legacy sources:

-   `public/admin/animals.php`
-   `public/admin/js/animals.js`
-   `public/admin/js/pages/animals/*`
-   relevant animal-specific CSS

## Target

Create:

``` text
public/admin/animals/
├── index.php
├── partials/
├── js/
└── css/
```

## Work

Move initial page rendering into PHP.

Classify existing JS into:

-   shared behavior
-   page-specific behavior
-   rendering logic
-   API/data logic

Move page-specific behavior into `animals/js/`.

Move page markup into `index.php` or `partials/`.

Use existing shared UI components rather than creating duplicates.

## Verify

-   animal grid/table
-   filters
-   flyout/drawer
-   dialogs
-   pagination
-   actions
-   API calls
-   responsive behavior

Do not modify shared navigation.
