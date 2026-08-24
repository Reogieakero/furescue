# Agent 13 --- Resident Comms + Learning

## Ownership

Owned routes:

-   `/learning/`
-   `/messages/`
-   `/notifications/`

Owned files:

-   `public/learning/index.php`
-   `public/learning/js/learning.js`
-   `public/learning/css/learning.css`
-   `public/messages/index.php`
-   `public/messages/js/messages.js`
-   `public/messages/css/messages.css`
-   `public/notifications/index.php`
-   `public/notifications/js/notifications.js`
-   `public/notifications/css/notifications.css`

Do not edit `resident-shell.php`. Do not build admin `/admin/messages/`
or `/admin/elearning/`.

## Design system

Reuse `.rcard`, `.rchip`, `.rbtn`, `.rlist`, `.input`, `.toast`.
Tokens: `--primary`, `--muted`, `--border`, `--radius`. Lucide only.
Page CSS (`learning.css`, `messages.css`, `notifications.css`) must
not invent hex/radius; promote to `input.css` if a new reusable
pattern appears, then `npm run build`. Verify 375 / 768 / 1440.

## File size / split

Keep learning / messages / notifications in their page folders. Flag
owned files over ~300 lines. Split on edit; otherwise document as
candidates. Do not merge the three apps into one script.

This run edited JS/PHP only (all under ~300 lines). Did not edit CSS.

| File | Lines | Action |
| --- | --- | --- |
| `learning/js/learning.js` | 269 | under limit |
| `messages/js/messages.js` | 273 | under limit |
| `notifications/js/notifications.js` | 215 | under limit |
| `messages/css/messages.css` | 311 | split candidate; not edited |
| `learning/css/learning.css` | ~219 | under limit; not edited |
| `notifications/css/notifications.css` | ~185 | under limit; not edited |

Page CSS leftovers (cannot promote: `input.css` is Phase 02 / 90):

-   `messages.css` `#fff` on avatar gradient and unread badge (~86, ~142)
-   `notifications.css` raw `hsl(150 70% …)` success-chip leftovers (~112–113, ~184–185) matching existing `input.css` success-chip leftovers

No `href="#"` on these three pages. Learning deep-links with
`history.replaceState(..., #moduleId)` (real hash, not a stub). Message
cards and notification rows are `<button>` / `data-href` list URLs.

## Interaction checklist

`/learning/` (Chrome CDP, `juan@furescue.local`, innerWidth 375 / 768 / 1440):

-   [x] module grid loads
-   [x] progress bar / chip update
-   [x] open module (drawer/dialog/page)
-   [x] mark complete / progress persist
-   [x] empty / error

`/messages/`:

-   [x] thread list
-   [ ] open thread — blocked (API 403)
-   [ ] send message — blocked (API 403; composer stays hidden)
-   [ ] read/unread if present — blocked (list never loads)
-   [x] empty / error
-   [x] compose/new thread if a control exists — none on page

`/notifications/`:

-   [x] list loads
-   [x] mark one read
-   [x] mark all read if present
-   [x] click-through to related entity if href is real
-   [x] empty / error
-   [x] unread badge on shell updates (read-only check; shell JS is
    Phase 02)

## Viewport checklist

Method: Chrome headless CDP + `Emulation.setDeviceMetricsOverride` so
`window.innerWidth` is exactly 375 / 768 / 1440. No Browser MCP in this
session. Overflow = `document.documentElement.scrollWidth > innerWidth`.

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/learning/` | pass | pass | pass | Grid stacks; 0 overflow. Open card → in-page lesson; Mark Complete → chip `1 of 8 completed`; Back → grid `Read again`. |
| `/messages/` | pass | pass | pass | Error empty (403), not infinite loading. Composer `#msg-form` stays `is-hidden`. Thread pane never opens. 0 overflow. |
| `/notifications/` | pass | pass | pass | 3 items; Unread/All tabs; `[data-mark]` and `#notif-mark-all`; clickable row → `/adoptions/`. 0 overflow. |

## Known debt

-   Admin E-Learning and Messages sidebar items 404 — not this agent.
    Confirmed still 404; not built.
-   Real-time push is out of scope; polling/SWR is enough. Messages
    poll every 15s; silent poll does not clobber the error empty.
-   `GET /api/v1/elearning/progress` 500 (`Unknown column 'created_at'`
    on `elearning_progress`; schema has `completed_at`). Cannot change
    `/api/v1`. Page toasts and still renders the module grid. POST
    `/elearning/progress` 200; in-session chip updates. Reload cannot
    restore saved progress until the API is fixed.
-   Resident role in `src/Auth/Permissions.php` has no `messages.read`
    / `messages.send` / `messages.mark_read`. `GET /api/v1/messages/threads`
    403 `Permission not permitted: messages.read`. Cannot change
    Permissions.php. Send / open thread / unread chips are unreachable
    for `juan@furescue.local`.
-   No compose / new-thread control on `/messages/`. Composer exists
    only after a thread is selected.
-   Notification related hrefs are list URLs (`/reports/`, `/adoptions/`),
    not entity-id URLs.
-   Full “no modules” / “no notifications (all)” empty states are
    markup-present; not forced in this seed (8 modules, 3 notifications).
    Unread-empty **was** reached after Mark all. Module load-error
    markup exists; not forced (modules GET 200).

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| Page JWT + bell → inbox | `/notifications/` `/learning/` `/messages/` | broken-fixed | PHP password login is session-only. Pages now mint `__PAGE_STATE__.accessToken` via `JwtService` and call `bootstrapPageAuth()` before `requireAuth()`. CDP: cold `/notifications/` with empty localStorage stays logged in (`token: true`, 3 items). Bell `a.rtop-bell` from `/animals/` lands on `/notifications/` without login bounce. Bug was in owned PHP/JS, not `guard.php` / shell. Phase 02 logout (`/auth/logout.php`) not reverted. |
| Module grid | `/learning/` | broken-fixed | Was blank when progress GET 500. Now loads modules then progress separately. 8 published cards at 375/768/1440. Toast: “Saved progress is temporarily unavailable. You can still read modules.” |
| Open lesson | `/learning/` | broken-fixed | `const done` was scoped inside `if (statusEl)` and threw before unhiding the lesson; Lucide could replace the icon `span`. Hoisted `done`; unhide first; null-safe label. CDP: card click → hash `#66aeb612-…`, lesson visible, title “Puppy Socialization”. |
| Mark complete + chip | `/learning/` | broken-fixed | Same scope bug + missing re-bootstrap. CDP 375/768/1440: `#learn-complete` → chip `1 of 8 completed`, button `Completed`, toast “Module completed. Nice work!”. POST `/elearning/progress` 200. GET progress still 500 so reload cannot restore (API, not owned). |
| Back to grid | `/learning/` | working | `#learn-back` (“All modules”) → list, card CTA `Read again`. |
| Learning empty / error | `/learning/` | working | Distinct `loadError` vs “No modules yet” markup. Progress-error toast shown live. Full empty grid not reached (8 modules). |
| Thread list | `/messages/` | broken-fixed | Was infinite “Loading conversations…”. Now error empty: “Could not load conversations” + 403 toast. Silent poll does not clobber. CDP all three widths. |
| Open thread | `/messages/` | stub-documented | Handler + thread pane exist. Unreachable: resident 403 on `GET /messages/threads`. No thread rows to click. |
| Send message | `/messages/` | stub-documented | `#msg-form` submit → `POST /messages` is wired. Composer stays `is-hidden` until a thread is open. Cannot click-test send. |
| Thread unread chip | `/messages/` | stub-documented | `unread_count` badge in thread-item markup. List never loads for resident. |
| Compose / new thread | `/messages/` | stub-documented | No new-thread control on the page. Composer is reply-only. |
| Messages empty (no threads) | `/messages/` | working | “No conversations yet” markup present; not reached (403 error path instead). |
| Notification list | `/notifications/` | working | 3 items load at 375/768/1440 after page token mint. |
| Unread / All tabs | `/notifications/` | working | `.notif-tab` `data-filter` switches list. Unread empty after mark-all: “You're all caught up”. |
| Mark one read | `/notifications/` | working | `[data-mark]` click; `PATCH /notifications/{id}/read`. Unread chip updates. |
| Mark all read | `/notifications/` | working | `#notif-mark-all` → `POST /notifications/read-all`; toast “All notifications marked as read.” |
| Click-through | `/notifications/` | working | `.notif-item.is-clickable` `data-href="/adoptions/"` (and `/reports/` in map). CDP click → `/adoptions/`. List URLs, not entity ids. |
| Notifications empty / error | `/notifications/` | working | Unread-empty clicked. All-empty markup exists, not forced. Load-error toasts; not forced (list GET 200). |
| Shell unread badge | `/notifications/` | working | Page calls `setResidentNavBadge("notifications", count)` after load/mark. Shell JS is Phase 02; not re-styled. Read-only check. |
| Admin E-Learning | `/admin/elearning/` | stub-documented | 404. Not built (forbidden). |
| Admin Messages | `/admin/messages/` | stub-documented | 404. Not built (forbidden). |

## Files changed this run

-   `public/learning/index.php` — mint `__PAGE_STATE__` JWT
-   `public/learning/js/learning.js` — bootstrap auth; decouple progress 500; fix lesson `done` scope / complete
-   `public/messages/index.php` — mint `__PAGE_STATE__` JWT
-   `public/messages/js/messages.js` — bootstrap auth after hydrate; `loadError` empty; silent poll
-   `public/notifications/index.php` — mint `__PAGE_STATE__` JWT
-   `public/notifications/js/notifications.js` — `bootstrapPageAuth()` before `requireAuth()`
-   `plans/furescue-product-audit/13-agent-resident-comms-learn.md` — this file

Not edited: shells, `guard.php`, `input.css`, `/api/v1`, router,
`Permissions.php`. No commit.

## Unverified

-   Open thread / send / mark-read on `/messages/` — resident 403; no
    compose control. Would need `messages.read`/`messages.send` on the
    role (not owned).
-   Learning progress restore after full reload — GET progress 500
    (`created_at`). POST + in-session UI work.
-   Learning “No modules yet” and notifications all-empty with zero
    rows — markup only; seed has data.
-   Keyboard Enter/Space on notification rows — code-wired, not
    CDP-keyed.
-   Real-time push — out of scope.
