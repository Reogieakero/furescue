# Agent Plan --- Reports

## Ownership

Primary target:

-   `public/admin/reports/`

Legacy sources:

-   `public/admin/reports.php`
-   `public/admin/js/reports.js`
-   `public/admin/js/pages/reports/*`
-   relevant report CSS

## Work

Create `reports/index.php`, `partials/`, `js/`, and `css/` as needed.

Move initial report markup into PHP.

Retain shared UI components.

Move report-specific behavior into page JS.

Do not duplicate card/dialog/table components.

## Verify

-   report rendering
-   filters
-   tables/cards
-   actions
-   dialogs/drawers
-   API calls
-   responsive behavior
