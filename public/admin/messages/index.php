<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\UserRepository;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require_once dirname(__DIR__, 3) . '/views/path.php';
require views_path('components/guard.php');

require views_path('components/admin-ui-helpers.php');

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

require views_path('admin/messages/index.php');

$activeNav = 'messages';
$navBadges = [
    'notifications' => null,
];

ob_start();
require views_path('layouts/admin.php');
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Messages';
$pageDescription = 'FurEscue staff inbox — message reporters and adoption applicants about reports, cases, and applications.';
$pageCss = [
    '/admin/css/admin.css',
    '/admin/messages/css/messages.css',
    '/admin/messages/css/thread.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require views_path('components/site-head.php');
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/messages/js/messages.js"></script>
  </body>
</html>
