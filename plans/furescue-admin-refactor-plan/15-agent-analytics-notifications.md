# Agent Plan --- Analytics and Notifications

## Ownership

Primary targets:

-   `public/admin/analytics/`
-   `public/admin/notifications/`

These pages are already folder-based and should be normalized rather
than unnecessarily rewritten.

## Analytics

Inspect:

-   `index.php`
-   `helpers.php`
-   `view.php`
-   `css/analytics.css`
-   `js/analytics.js`

Ensure PHP remains responsible for initial rendering and JS remains
behavior-only.

Preserve analytics-specific helpers when they are genuinely useful.

## Notifications

Inspect:

-   `index.php`
-   `js/broadcast.js`

Ensure notification markup is server-rendered and JavaScript only
handles behavior/API interaction.

## Verify

-   direct navigation
-   hard refresh
-   active navigation
-   charts/visualizations
-   notification interactions
-   API calls
-   responsive behavior

Do not modify shared navigation.
