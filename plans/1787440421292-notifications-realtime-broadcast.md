# Sub-Plan 5: Notifications Realtime & Broadcasts

**Parallel-safe with:** 1,2,3,4,6,7
**Files owned exclusively by this plan:**
- `src/Controllers/NotificationController.php`
- `src/Http/Routes/NotificationRoutes.php`
- `src/Services/NotificationService.php`
- `public/admin/notifications/index.php` (new; URL `/admin/notifications/` -- Page Folder Convention from master plan)
- `public/admin/notifications/js/broadcast.js` (new)
- `public/admin/js/pages/dashboard/state.js`
- `public/admin/js/lib/admin-data.js`

---

## 1. Realtime Delivery (OBJ-4, REQ-S4)

**Current state:** Notifications are DB rows only; no WebSocket/SSE/polling.

**Actions:**
1. Add SSE endpoint in `src/Http/Routes/NotificationRoutes.php`:
   ```php
   $router->add('GET', '/api/v1/notifications/stream', fn(Request $r) => (new NotificationController($pdo))->stream($r), [$authMw]);
   ```
2. Add `stream` method in `src/Controllers/NotificationController.php`:
   - Set headers: `Content-Type: text/event-stream`, `Cache-Control: no-cache`, `Connection: keep-alive`.
   - Loop: every 5 seconds, query `SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 AND created_at > ?`.
   - Send `data: {...}\n\n` for new notifications.
   - Handle client disconnect (`connection_aborted`) to exit loop.
3. In `public/admin/js/pages/dashboard/state.js`:
   - Open `EventSource('/api/v1/notifications/stream')` on dashboard load.
   - On `message`: parse event, update badge count, prepend to notifications list, show toast.
4. In `public/admin/js/lib/admin-data.js`:
   - Add `subscribeToNotifications(callback)` helper using EventSource.

---

## 2. Admin Broadcast (REQ-A6)

**Current state:** No compose/broadcast endpoint or UI. `admin-data.js` line 48 has `broadcastAnnouncement` calling `/admin/notifications` but no backend route.

**Actions:**
1. Add broadcast endpoint in `src/Http/Routes/NotificationRoutes.php`:
   ```php
   $router->add('POST', '/api/v1/admin/notifications/broadcast', fn(Request $r) => (new NotificationController($pdo))->broadcast($r), [$authMw, $adminMw]);
   ```
2. Add `broadcast` method in `src/Controllers/NotificationController.php`:
   - Validate `message` (required, string, max 1000) and optional `type`.
   - Insert notification for ALL users (or all residents + rescuers).
   - Use `NotificationService::notifyRole('resident', ...)` and `notifyRole('rescuer', ...)` or direct insert loop.
3. Create `public/admin/notifications/index.php`:
   - Include `admin-shell.php`.
   - Compose form: message textarea, target audience (All / Residents / Rescuers / Staff), send button.
   - Recent broadcasts table.
   - Load the page module with an absolute path: `<script type="module" src="/admin/notifications/js/broadcast.js">`.
4. Create `public/admin/notifications/js/broadcast.js`:
   - Handle form submit to `POST /api/v1/admin/notifications/broadcast`.
   - Fetch and render recent sent notifications.

---

## Acceptance Criteria

- [ ] Dashboard badge updates within 5 seconds of a new notification without page reload.
- [ ] Admin can compose and send broadcast to all users.
- [ ] Residents receive broadcast in their inbox.
- [ ] SSE stream disconnects cleanly on tab close.
