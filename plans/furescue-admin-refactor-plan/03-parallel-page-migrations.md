# Phase 2 --- Parallel Admin Page Migrations

## Purpose

Migrate each admin page independently after Phase 1 is complete.

Each page agent owns only its page directory and its known legacy
dependencies.

## Parallel agents

Run these independently:

1.  Animals
2.  Cases
3.  Reports
4.  Rescuers
5.  Health Records
6.  Analytics and Notifications

Do not let page agents modify shared navigation or shared UI components
unless explicitly approved by the integration owner.

## Universal migration rules

For each page:

1.  Read `AGENTS.md`.
2.  Read the component audit.
3.  Inspect the existing PHP.
4.  Inspect existing page JS.
5.  Inspect relevant CSS.
6.  Identify reusable shared UI components.
7.  Create the page directory.
8.  Move page rendering into `index.php` and `partials/`.
9.  Move page-specific behavior into `js/`.
10. Keep shared behavior in `public/js/components/` and
    `public/js/lib/`.
11. Reduce `window.__PAGE_STATE__`.
12. Update asset paths to absolute `/admin/<page>/...`.
13. Update internal links.
14. Preserve the existing UI and API behavior.
15. Test the page independently.

## PHP rendering rule

PHP must render:

-   cards
-   tables
-   forms
-   headings
-   empty states
-   buttons
-   modal/dialog markup
-   drawer markup
-   initial loading/error states where appropriate

Do not recreate these as large JavaScript templates.

## JS behavior rule

JavaScript may:

-   bind event handlers
-   call `/api/v1`
-   open/close dialogs
-   open/close drawers
-   submit forms
-   update small existing DOM regions
-   manage interaction state

JavaScript must not rebuild the entire page.

## Shared component rule

Use existing shared UI components when appropriate.

Do not duplicate:

-   dialogs
-   cards
-   buttons
-   drawers
-   pagination
-   toasts
-   tooltips
-   selectors

A shared JS component may provide behavior while page PHP provides
page-specific markup.

## Compatibility

If old flat URLs need to remain valid, replace old page implementations
with thin redirects to the new directory URLs.

Do not leave duplicate page implementations in both locations.
