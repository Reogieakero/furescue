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

$activeNav = 'learning hub';
$navBadges = [];
$residentShellTitle = 'Learning Hub';

$residentChildren = <<<HTML
<div class="rpage-head">
  <div>
    <h2 class="rpage-title">Responsible Pet Ownership, One Lesson at a Time</h2>
    <p class="rpage-sub">Short guides from the City Veterinary Office &mdash; read a module, mark it complete, and track your progress.</p>
  </div>
</div>

<section class="learn-progress rcard" aria-label="Your progress">
  <div class="rcard-head">
    <h3 class="rcard-title"><i data-lucide="graduation-cap"></i> Your progress</h3>
    <span class="rchip" id="learn-progress-chip">Loading&hellip;</span>
  </div>
  <div class="rcard-pad learn-progress-body">
    <div class="learn-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="learn-progress-bar">
      <span class="learn-progress-fill" style="width:0%"></span>
    </div>
    <p class="learn-progress-note" id="learn-progress-note">Fetching your modules&hellip;</p>
  </div>
</section>

<section id="learn-list-section" aria-label="Modules">
  <div class="learn-grid" id="learn-grid"></div>
</section>

<section id="learn-lesson-section" class="is-hidden" aria-label="Lesson">
  <button type="button" class="rbtn rbtn--ghost rbtn--sm learn-back" id="learn-back">
    <i data-lucide="arrow-left"></i><span>All modules</span>
  </button>
  <article class="rcard learn-lesson">
    <header class="rcard-head learn-lesson-head">
      <div>
        <span class="rchip rchip--brand" id="learn-lesson-category">Module</span>
        <h3 class="learn-lesson-title" id="learn-lesson-title">&nbsp;</h3>
      </div>
      <span class="rchip" id="learn-lesson-status"></span>
    </header>
    <div class="rcard-pad learn-lesson-body prose-module" id="learn-lesson-content"></div>
    <footer class="learn-lesson-foot">
      <p class="learn-lesson-hint" id="learn-lesson-hint">Finished reading? Mark it complete to track your progress.</p>
      <button type="button" class="rbtn rbtn--solid" id="learn-complete">
        <i data-lucide="check-circle-2"></i><span>Mark Complete</span>
      </button>
    </footer>
  </article>
</section>
HTML;

ob_start();
require __DIR__ . '/../includes/resident-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Learning Hub';
$pageDescription = 'Free e-learning modules on responsible pet ownership, dog and cat behavior, and basic training for residents of Mati.';
$pageCss = ['/learning/css/learning.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require __DIR__ . '/../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script type="module" src="/learning/js/learning.js"></script>
  </body>
</html>
