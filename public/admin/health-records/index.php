<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/views/path.php';
require views_path('admin/health-records/index-data.php');
require views_path('admin/health-records/index.php');

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
require views_path('layouts/admin.php');
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Health Records';
$pageDescription = 'FurEscue admin health records — vaccinations, checkups, conditions, and vitals across the shelter population.';
$pageCss = ['/admin/css/admin.css', '/admin/health-records/css/health-records-list.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
$importMapExtras = ['chart.js' => 'https://esm.sh/chart.js@4.4.4/auto'];
require views_path('components/site-head.php');
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/health-records/js/health-records.js"></script>
  </body>
</html>
