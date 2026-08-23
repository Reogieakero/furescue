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

$activeNav = 'messages';
$navBadges = [];
$residentShellTitle = 'Messages';

$residentChildren = <<<HTML
<div class="rpage-head">
  <div>
    <h2 class="rpage-title">Messages</h2>
    <p class="rpage-sub">Talk with the rescue team about your reports, cases, and adoption applications.</p>
  </div>
</div>

<div class="rcard msg-shell" id="msg-shell">
  <aside class="msg-list" aria-label="Conversations">
    <div class="msg-list-head">
      <h3 class="rcard-title"><i data-lucide="message-square"></i> Conversations</h3>
    </div>
    <div class="msg-list-items" id="msg-threads">
      <div class="rempty"><p class="rempty-text">Loading conversations&hellip;</p></div>
    </div>
  </aside>

  <section class="msg-thread" aria-live="polite">
    <div class="rempty msg-thread-empty" id="msg-empty">
      <i data-lucide="messages-square"></i>
      <p class="rempty-title">No conversation selected</p>
      <p class="rempty-text">Pick a conversation on the left, or start one from a report, case, or adoption.</p>
    </div>

    <header class="msg-thread-head is-hidden" id="msg-thread-head">
      <button type="button" class="rbtn rbtn--ghost rbtn--sm msg-back" id="msg-back" aria-label="Back to conversations">
        <i data-lucide="arrow-left"></i>
      </button>
      <span class="msg-avatar" id="msg-peer-avatar">&nbsp;</span>
      <div class="msg-thread-title">
        <strong id="msg-peer-name">&nbsp;</strong>
        <span class="rchip rchip--sky" id="msg-context-chip"></span>
      </div>
    </header>

    <div class="msg-thread-scroll is-hidden" id="msg-scroll"></div>

    <form class="msg-composer is-hidden" id="msg-form">
      <label class="visually-hidden" for="msg-input">Message</label>
      <input type="text" id="msg-input" class="input" placeholder="Write a message&hellip;" autocomplete="off" maxlength="4000">
      <button type="submit" class="rbtn rbtn--solid msg-send" id="msg-send">
        <i data-lucide="send-horizontal"></i><span class="msg-send-label">Send</span>
      </button>
    </form>
  </section>
</div>
HTML;

ob_start();
require __DIR__ . '/../includes/resident-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Messages';
$pageDescription = 'Message rescuers and administrators about your animal reports, rescue cases, and adoption applications.';
$pageCss = ['/messages/css/messages.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require __DIR__ . '/../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script type="module" src="/messages/js/messages.js"></script>
  </body>
</html>
