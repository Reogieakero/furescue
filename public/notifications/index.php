<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

require __DIR__ . '/../includes/guard.php';

$residentShellMode = 'partial';

$sessionUser = $_SESSION['user'] ?? [];
$residentUser = [
    'id' => (string) ($sessionUser['id'] ?? ''),
    'full_name' => (string) ($sessionUser['full_name'] ?? ''),
    'email' => (string) ($sessionUser['email'] ?? ''),
    'role' => (string) ($sessionUser['role'] ?? ''),
    'profile_photo_url' => (string) ($sessionUser['profile_photo_url'] ?? ''),
];

$activeNav = 'notifications';
$navBadges = [];
$residentShellTitle = 'Notifications';

$residentChildren = <<<HTML
<div class="rpage-head">
  <div>
    <h2 class="rpage-title">Notifications</h2>
    <p class="rpage-sub">Updates about your reports, adoptions, messages, and announcements.</p>
  </div>
  <div class="rpage-actions">
    <button type="button" class="rbtn rbtn--ghost rbtn--sm" id="notif-mark-all">
      <i data-lucide="check-check"></i><span>Mark all as read</span>
    </button>
  </div>
</div>

<div class="rcard notif-card">
  <div class="rcard-head notif-tabs-wrap">
    <div class="notif-tabs" role="tablist" aria-label="Filter notifications">
      <button type="button" role="tab" class="notif-tab is-active" data-filter="all" aria-selected="true">All</button>
      <button type="button" role="tab" class="notif-tab" data-filter="unread" aria-selected="false">
        Unread <span class="rchip rchip--alert notif-unread-chip" id="notif-unread-count" hidden>0</span>
      </button>
    </div>
  </div>

  <ul class="notif-list" id="notif-list" aria-live="polite">
    <li class="rempty"><p class="rempty-text">Loading notifications&hellip;</p></li>
  </ul>
</div>
HTML;

ob_start();
require __DIR__ . '/../includes/resident-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Notifications';
$pageDescription = 'Your FurEscue notification inbox — report updates, adoption decisions, messages, and city announcements.';
$pageCss = ['/notifications/css/notifications.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require __DIR__ . '/../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script type="module" src="/notifications/js/notifications.js"></script>
  </body>
</html>
