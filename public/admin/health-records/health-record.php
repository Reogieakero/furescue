<?php

declare(strict_types=1);

require __DIR__ . '/includes/health-record-data.php';
require __DIR__ . '/partials/record-head.php';
require __DIR__ . '/partials/record-profile.php';
require __DIR__ . '/partials/record-overview.php';
require __DIR__ . '/partials/record-history.php';
require __DIR__ . '/partials/record-vaccinations.php';
require __DIR__ . '/partials/record-reminders.php';
require __DIR__ . '/partials/record-vitals.php';
require __DIR__ . '/partials/record-documents.php';
require __DIR__ . '/partials/record-stats.php';

$children = $pageHeadHtml
    . '<div class="hr-grid">' . $profilePanelHtml . $overviewPanelHtml . '</div>'
    . '<div class="hr-trio">
        ' . $historyPanelHtml . '
        <div class="hr-trio-col">' . $vaxPanelHtml . $remindersPanelHtml . '</div>
        <div class="hr-trio-col">' . $vitalsPanelHtml . $documentsPanelHtml . '</div>
      </div>'
    . $statsPanelHtml;

$currentUser = (new \App\Repositories\UserRepository($pdo))->find($uid);
$currentUserData = $currentUser ? $currentUser->toArray() : [];
$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => '',
];
$activeNav = 'health records';
$navBadges = ['notifications' => 3];
$adminChildren = $children;

ob_start();
require __DIR__ . '/../../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

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
    'record' => $record,
];

$pageTitle = 'FurEscue — Health Record';
$pageDescription = 'FurEscue admin — dedicated health record for a rescued animal.';
$pageCss = ['/admin/css/admin.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
$importMapExtras = [];
require __DIR__ . '/../../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/health-records/js/health-record.js"></script>
  </body>
</html>
