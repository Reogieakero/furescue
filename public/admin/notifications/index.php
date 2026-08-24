<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\UserRepository;
use App\Services\NotificationService;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../../includes/guard.php';

require __DIR__ . '/../includes/ui-helpers.php';

$pdo = Database::connect();
$uid = (string) $_SESSION['user']['id'];

$notificationService = new NotificationService($pdo);
$unreadCount = $notificationService->unreadCount($uid);
$broadcasts = $notificationService->recentBroadcasts(20);

$audienceOptions = [
    ['value' => 'all', 'label' => 'Everyone'],
    ['value' => 'role:resident', 'label' => 'Residents'],
    ['value' => 'role:rescuer', 'label' => 'Rescuers'],
    ['value' => 'staff', 'label' => 'Staff (admins & rescuers)'],
];

$broadcastRows = '';
foreach ($broadcasts as $b) {
    $broadcastRows .= '
    <tr>
      <td class="table-cell table-cell--strong">' . e(truncate_text($b['message'] ?? null, 64)) . '</td>
      <td class="table-cell"><span class="stamp stamp--sm stamp--accent">' . e($b['recipients'] ?? 0) . ' sent</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">' . e(time_ago($b['created_at'] ?? null)) . '</td>
    </tr>';
}
if ($broadcastRows === '') {
    $recentInner = '<div id="broadcast-list" class="queue-empty">' . empty_state('megaphone', 'No broadcasts yet. Compose your first announcement.') . '</div>';
} else {
    $recentInner = '
    <div id="broadcast-list" class="table-wrap">
      <table class="table">
        ' . table_head(['Message', 'Recipients', 'Sent']) . '
        <tbody id="broadcast-rows">' . $broadcastRows . '</tbody>
      </table>
    </div>';
}

$composeCard = '
  <div class="panel panel--padded" id="broadcast-panel">
    <div class="panel-title-wrap"><i data-lucide="megaphone"></i><h2 class="panel-title">New broadcast</h2></div>
    <form id="broadcast-form" class="space-y-3" novalidate>
      <div class="field">
        <label class="field-label" for="broadcast-message">Message</label>
        <textarea id="broadcast-message" name="message" class="input input--area" rows="4" maxlength="1000"
          placeholder="Type your announcement&hellip;" aria-describedby="broadcast-hint"></textarea>
        <span class="field-hint" id="broadcast-hint"><span id="broadcast-count">0</span>/1000 characters</span>
      </div>
      <div class="field">
        <span class="field-label" id="broadcast-target-label">Audience</span>
        ' . select_control('broadcast-target', $audienceOptions, 'all', 'Audience', '', '', 'w-full') . '
      </div>
      ' . button_html('Send broadcast', 'default', 'default', '', 'send', 'id="broadcast-send"', 'submit') . '
    </form>
  </div>';

$recentCard = '
  <div class="panel" id="recent-broadcasts">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="radio"></i><h2 class="panel-title panel-title--sm">Recent broadcasts</h2></div>
      <span class="stamp stamp--sm stamp--muted" id="broadcast-total">' . e(count($broadcasts)) . '</span>
    </div>
    ' . $recentInner . '
  </div>';

$greeting = '
  <div class="greeting">
    <div>
      <span class="stamp stamp--coral">Communication</span>
      <h1 class="greeting-title">Notifications &amp; Broadcasts</h1>
      <p class="greeting-sub">Compose announcements for the FurEscue community and review what went out recently.</p>
    </div>
  </div>';

$children = $greeting . "\n" . '<div class="cols cols--two">' . $composeCard . "\n" . $recentCard . '</div>';

$currentUser = (new UserRepository($pdo))->find($uid);
$currentUserData = $currentUser ? $currentUser->toArray() : [];
$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($currentUserData['profile_photo_url'] ?? ''),
];
$activeNav = 'notifications';
$navBadges = ['notifications' => $unreadCount];
$uid = (string) $_SESSION['user']['id'];
$role = (string) ($_SESSION['user']['role'] ?? '');
$state = [
    'accessToken' => (new \App\Auth\JwtService())->issueAccessToken(['id' => $uid, 'role' => $role]),
    'user' => [
        'id' => $uid,
        'full_name' => (string) ($_SESSION['user']['full_name'] ?? ''),
        'email' => (string) ($_SESSION['user']['email'] ?? ''),
        'role' => $role,
    ],
    'unreadCount' => $unreadCount,
    'broadcasts' => $broadcasts,
];
$adminChildren = $children;
ob_start();
require __DIR__ . '/../../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Notifications & Broadcasts';
$pageDescription = 'Compose and send FurEscue announcements to residents, rescuers, or everyone, and review recent broadcasts.';
$pageCss = ['/admin/css/admin.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require __DIR__ . '/../../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/notifications/js/broadcast.js"></script>
  </body>
</html>
