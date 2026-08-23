# Sub-Plan 4: Community Learning, Messaging & Notifications Inbox

**Parallel-safe with:** 1,2,3,5,6,7
**Files owned exclusively by this plan:** (new pages follow the master plan's Page Folder Convention)
- `public/learning/index.php` (new; URL `/learning/`)
- `public/messages/index.php` (new; URL `/messages/`)
- `public/notifications/index.php` (new; URL `/notifications/`)
- `public/learning/js/learning.js` (new)
- `public/messages/js/messages.js` (new)
- `public/notifications/js/notifications.js` (new)
- `src/Controllers/ElearningController.php` (minor additions if needed)
- `src/Controllers/MessageController.php` (read-only, verify endpoints)
- `src/Controllers/NotificationController.php` (read-only, verify endpoints)

---

## 1. E-Learning Lesson Viewer (`public/learning/index.php`)

**Current state:** `ElearningController` has `modules`, `module`, `progress`, `upsertProgress`. No resident UI.

**Actions:**
1. Verify endpoints:
   - `GET /elearning/modules?published_status=published` returns modules for residents.
   - `GET /elearning/modules/{id}` returns single module with content.
   - `GET /elearning/progress` returns resident's progress.
   - `POST /elearning/progress` upserts progress.
   If any are missing, add them (minimal changes).
2. Create `public/learning/index.php`:
   - Include `resident-shell.php`.
   - Module list: cards with category badge, title, description, "Start" button.
   - Lesson view: title, category, content body, "Mark Complete" button.
   - Progress tracker: show completed/in-progress modules.
   - Load the page module with an absolute path: `<script type="module" src="/learning/js/learning.js">`.
3. Create `public/learning/js/learning.js`:
   - Fetch modules, render list.
   - On module click, fetch detail and show lesson view.
   - On "Mark Complete", call `POST /elearning/progress` with `module_id` and `status=completed`.
   - Update progress bar.

---

## 2. Messaging UI (`public/messages/index.php`)

**Current state:** `MessageController` has `send`, `thread`, `markRead`. No UI. Sidebar "Messages" link has no target.

**Actions:**
1. Create `public/messages/index.php`:
   - Include `resident-shell.php`.
   - Two-pane layout: conversation list (left) + thread view (right).
   - Conversation list: fetch recent threads (need a threads endpoint or group by `related_type` + `related_id`).
   - Thread view: fetch `GET /api/v1/messages?related_type={type}&related_id={id}`.
   - Send message form: text input, send button.
   - Mark messages as read on open.
   - Load the page module with an absolute path: `<script type="module" src="/messages/js/messages.js">`.
2. Create `public/messages/js/messages.js`:
   - Fetch and render conversation list with last message preview.
   - Fetch and render thread chronologically.
   - Handle send via `POST /api/v1/messages`.
   - Auto-scroll to bottom on new message.

**Note:** If `MessageController` lacks a threads/grouping endpoint, add a minimal one or group client-side by `related_type` + `related_id`.

---

## 3. Resident Notification Inbox (`public/notifications/index.php`)

**Current state:** `GET /api/v1/notifications` returns notifications. No resident inbox page.

**Actions:**
1. Create `public/notifications/index.php`:
   - Include `resident-shell.php`.
   - Fetch `GET /api/v1/notifications?is_read=false` for unread, and all for history.
   - List notifications: type icon, message, created_at, related link (e.g., view case, view adoption).
   - "Mark all as read" button calling `POST /api/v1/notifications/read-all`.
   - Badge count in resident shell topbar.
   - Load the page module with an absolute path: `<script type="module" src="/notifications/js/notifications.js">`.
2. Create `public/notifications/js/notifications.js`:
   - Fetch unread + all notifications.
   - Render list with empty state.
   - Handle mark-all-read and individual mark-read.

---

## Acceptance Criteria

- [ ] `/learning/` shows published modules and allows reading + progress tracking.
- [ ] `/messages/` shows conversation list and threaded messages with send working.
- [ ] `/notifications/` shows resident's notifications with mark-read working.
- [ ] All pages responsive at 375px, 768px, 1440px.
