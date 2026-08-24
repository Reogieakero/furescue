# Agent Plan --- Rescuers

## Ownership

Primary target:

-   `public/admin/rescuers/`

Legacy sources:

-   `public/admin/rescuers.php`
-   `public/admin/js/rescuers.js`
-   `public/admin/js/pages/rescuers/*`
-   rescuer-specific CSS

## Work

Create the folder-based page.

Server-render initial HTML.

Move only rescuer-specific behavior into `rescuers/js/`.

Reuse shared UI components.

Preserve existing API behavior.

## Verify

-   rescuer list/layout
-   split layout
-   actions
-   dialogs/drawers
-   forms
-   API calls
-   responsive behavior
