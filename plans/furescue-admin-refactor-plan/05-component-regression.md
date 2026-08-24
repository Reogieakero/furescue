# Phase 4 --- Shared Component Regression Audit

## Purpose

Verify that the existing shared UI layer still works after page
migration.

This phase is separate from page verification because a page can work
while a shared component is partially broken.

## Components to verify

Where used by the application, verify:

-   badge
-   button
-   card
-   checkbox
-   date-picker
-   dialog
-   drawer
-   dropdown-menu
-   input
-   label
-   loader
-   marker
-   pagination
-   select
-   separator
-   skeleton
-   spinner
-   toast
-   tooltip

## Verification

For each component used by admin pages verify:

-   initial rendering
-   expected DOM
-   event handlers
-   open/close behavior
-   keyboard behavior where applicable
-   focus behavior where applicable
-   mobile behavior
-   integration with page JS
-   no console errors

## Important rule

Do not rewrite a component merely because it is implemented differently
than the new page architecture.

The goal is compatibility, not replacement.

If a component generates large page markup, document whether that
responsibility should eventually move to PHP. Do not perform unrelated
redesign during regression testing.

## Deliverable

Report:

-   components verified
-   pages exercising each component
-   failures
-   fixes made
-   remaining technical debt
