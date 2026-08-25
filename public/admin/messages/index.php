<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\UserRepository;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../../includes/guard.php';

require __DIR__ . '/../includes/ui-helpers.php';

$pdo = Database::connect();
$uid = (string) $_SESSION['user']['id'];
$role = (string) ($_SESSION['user']['role'] ?? '');

$currentUser = (new UserRepository($pdo))->find($uid);
$currentUserData = $currentUser ? $currentUser->toArray() : [];

$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($currentUserData['email'] ?? ($_SESSION['user']['email'] ?? '')),
    'role' => $role,
    'profile_photo_url' => (string) ($currentUserData['profile_photo_url'] ?? ''),
];

$state = [
    'accessToken' => (new \App\Auth\JwtService())->issueAccessToken(['id' => $uid, 'role' => $role]),
    'user' => [
        'id' => $uid,
        'full_name' => (string) $adminUser['full_name'],
        'email' => (string) $adminUser['email'],
        'role' => $role,
    ],
];

$composeBtn = button_html(
    'Start conversation',
    'default',
    'default',
    '',
    'plus',
    'data-action="compose" id="amsg-compose"'
);

$sendBtn = button_html(
    'Send',
    'default',
    'default',
    'amsg-send',
    'send-horizontal',
    'id="amsg-send"',
    'submit'
);

$backBtn = button_html(
    'Back',
    'ghost',
    'sm',
    'amsg-back',
    'arrow-left',
    'id="amsg-back" aria-label="Back to conversations"'
);

$adminChildren = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Communication</span>
      <h1 class="page-title">Messages</h1>
      <p class="page-sub">Staff inbox for reports, cases, and adoption applications.</p>
    </div>
    <div class="page-head-actions">
      ' . $composeBtn . '
    </div>
  </div>
  <div class="panel amsg-panel">
    <div class="amsg-shell" id="amsg-shell">
      <aside class="amsg-list" aria-label="Conversations">
        <div class="amsg-list-head">
          <i data-lucide="message-square"></i>
          <h2 class="amsg-list-title">Conversations</h2>
        </div>
        <div class="amsg-list-items" id="amsg-threads">
          <div class="amsg-empty">
            <i data-lucide="inbox"></i>
            <p class="amsg-empty-title">Loading conversations&hellip;</p>
            <p class="amsg-empty-text">Conversations appear when someone messages this admin, or after you start a conversation.</p>
          </div>
        </div>
      </aside>
      <section class="amsg-thread" aria-live="polite">
        <div class="amsg-empty" id="amsg-empty">
          <i data-lucide="messages-square"></i>
          <p class="amsg-empty-title">No conversation selected</p>
          <p class="amsg-empty-text">Pick a conversation, or start one. Threads show up when someone messages this admin, or after Start conversation.</p>
        </div>
        <header class="amsg-thread-head is-hidden" id="amsg-thread-head">
          ' . $backBtn . '
          <span class="amsg-avatar" id="amsg-peer-avatar">&nbsp;</span>
          <div class="amsg-thread-title">
            <strong id="amsg-peer-name">&nbsp;</strong>
            <span class="stamp stamp--sm stamp--accent" id="amsg-context-chip"></span>
          </div>
        </header>
        <div class="amsg-thread-scroll is-hidden" id="amsg-scroll"></div>
        <form class="amsg-composer is-hidden" id="amsg-form">
          <label class="visually-hidden" for="amsg-input">Message</label>
          <input type="text" id="amsg-input" class="input" placeholder="Write a message&hellip;" autocomplete="off" maxlength="4000">
          ' . $sendBtn . '
        </form>
      </section>
    </div>
  </div>';

$activeNav = 'messages';
$navBadges = [
    'notifications' => null,
];

ob_start();
require __DIR__ . '/../../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Messages';
$pageDescription = 'FurEscue staff inbox — message reporters and adoption applicants about reports, cases, and applications.';
$pageCss = [
    '/admin/css/admin.css',
    '/admin/messages/css/messages.css',
    '/admin/messages/css/thread.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require __DIR__ . '/../../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/messages/js/messages.js"></script>
  </body>
</html>
