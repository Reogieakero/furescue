<?php

declare(strict_types=1);

require __DIR__ . '/includes/index-data.php';
require __DIR__ . '/partials/index-kpis.php';
require __DIR__ . '/partials/index-charts.php';
require __DIR__ . '/partials/index-queue.php';
require __DIR__ . '/partials/index-table.php';
require __DIR__ . '/partials/index-head.php';

$adminChildren = $pageHead . "\n"
    . $controlsPanel . "\n"
    . "<div id=\"hr-kpis\">{$hrKpisHtml}</div>\n"
    . "<div class=\"cols cols--vax\"><div id=\"hr-vax-dog\">{$dogCard}</div><div id=\"hr-vax-cat\">{$catCard}</div><div id=\"hr-conditions\">{$conditionsPanel}</div></div>\n"
    . "<div id=\"hr-trend\">{$trendPanel}</div>\n"
    . "<div class=\"cols cols--two hr-split-row\"><div id=\"hr-stacked\">{$stackedPanel}</div><div id=\"hr-queue\">{$queuePanel}</div></div>\n"
    . "<div id=\"hr-records\">{$recordsPanel}</div>";

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
    'filter' => 'all',
    'query' => '',
    'sort' => 'newest',
    'range' => '30d',
    'species' => 'all',
    'page' => 1,
    'queueExpanded' => false,
    'records' => $records,
    'activity' => $daily,
];

$stmtUser = $pdo->prepare('SELECT id, full_name, email, role, profile_photo_url FROM users WHERE id = ?');
$stmtUser->execute([(string) $_SESSION['user']['id']]);
$currentUserData = $stmtUser->fetch(\PDO::FETCH_ASSOC) ?: [];

$adminUser = [
    'id' => (string) $_SESSION['user']['id'],
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($currentUserData['profile_photo_url'] ?? ''),
];
$activeNav = 'health records';
$navBadges = [
    'health' => $attentionCount,
];

ob_start();
require __DIR__ . '/../../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Health Records';
$pageDescription = 'FurEscue admin health records — vaccinations, checkups, conditions, and vitals across the shelter population.';
$pageCss = ['/admin/css/admin.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
$importMapExtras = ['chart.js' => 'https://esm.sh/chart.js@4.4.4/auto'];
require __DIR__ . '/../../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/health-records/js/health-records.js"></script>
  </body>
</html>
