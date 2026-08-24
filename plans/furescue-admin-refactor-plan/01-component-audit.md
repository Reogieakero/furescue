# Phase 0 --- Shared Component and Architecture Audit

## Purpose

Perform a read-only audit before changing production code.

This phase prevents page agents from independently interpreting or
duplicating the existing shared UI architecture.

## Read first

-   `AGENTS.md`
-   `public/includes/admin-shell.php`
-   `public/admin/includes/ui-helpers.php`
-   `public/js/components/ui/*`
-   `public/js/lib/*`
-   `public/admin/js/layout/*`
-   `public/admin/js/lib/*`
-   `public/admin/js/pages/*`
-   `public/admin/*.php`
-   `public/admin/analytics/*`
-   `public/admin/notifications/*`
-   `public/admin/css/*`

## Audit goals

For every relevant JS component determine:

1.  Is it behavior-only?
2.  Does it generate DOM?
3.  Is it used by multiple pages?
4.  Does it overlap with an admin page component?
5.  Should it remain shared?
6.  Should its rendering responsibility move to PHP?
7.  Does it have dependencies that page agents must preserve?

Important shared components include:

-   button
-   card
-   dialog
-   drawer
-   dropdown-menu
-   input
-   label
-   loader
-   pagination
-   select
-   skeleton
-   spinner
-   toast
-   tooltip
-   checkbox
-   date-picker

## Page JS classification

Classify existing code under `public/admin/js/pages/` as:

-   PHP rendering candidate
-   shared behavior
-   page-specific behavior
-   API/data utility
-   state that can remain client-side
-   obsolete/redundant

## CSS classification

Classify `public/admin/css/partials/*` as:

-   shared admin CSS
-   page-specific CSS
-   component CSS
-   obsolete CSS

Do not move CSS yet unless necessary for the audit.

## Navigation audit

Identify every navigation definition and every old admin URL reference.

Search for:

-   `NAV_GROUPS`
-   `data-nav`
-   `href="#"`
-   `/admin/*.php`
-   hardcoded sidebar links

## Deliverable

Produce an audit report containing:

-   component inventory
-   dependency map
-   page migration map
-   CSS classification
-   navigation findings
-   risks/blockers
-   recommendations for Phase 1 and Phase 2

No production refactor should be performed in this phase.
