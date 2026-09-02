<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\ReportRepository;
use App\Repositories\UserRepository;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require_once dirname(__DIR__, 3) . '/views/path.php';
require views_path('components/guard.php');

require views_path('components/admin-ui-helpers.php');

$pdo = Database::connect();
$uid = (string) $_SESSION['user']['id'];

/**
 * Mirrors UserController::indexRescuers serialization EXACTLY:
 * u.* sans password_hash + COALESCE(d.status,'off_duty') AS duty_status,
 * ORDER BY created_at DESC, LIMIT 100 OFFSET 0 (client fetches per_page=100).
 */
$rescuersByStatus = static function (string $accountStatus) use ($pdo): array {
    $stmt = $pdo->prepare(
        "SELECT u.*, COALESCE(d.status, 'off_duty') AS duty_status
         FROM users u
         LEFT JOIN rescuer_duty_status d ON d.user_id = u.id
         WHERE u.role = 'rescuer' AND u.account_status = ?
         ORDER BY u.created_at DESC
         LIMIT 100 OFFSET 0"
    );
    $stmt->execute([$accountStatus]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return array_map(static function (array $u) {
        unset($u['password_hash']);
        return $u;
    }, $rows);
};

$rescuersActive = $rescuersByStatus('active');
$rescuersSuspended = $rescuersByStatus('suspended');
$pendingItems = $rescuersByStatus('pending');

$allReportsResult = (new ReportRepository($pdo))->paginate(1, 100, []);
$reportItems = array_map(static fn($r) => $r->toArray(), $allReportsResult['items']);

$rescuerRowsAll = array_values(array_merge($rescuersActive, $rescuersSuspended));

// State keys mirror public/admin/rescuers/js/state.js exactly.
// selectedId/selectedRescuer/selectedRescuerCases/caseActivity stay runtime-only:
// JSON cannot express `selectedRescuer: undefined`, and the server never has a selection.
// accessToken + user feed bootstrapPageAuth() so JS API calls carry the bearer token.
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
    'rescuers' => $rescuerRowsAll,
    'pending' => $pendingItems,
    'reports' => $reportItems,
    'caseActivity' => new \stdClass(),
    'filter' => 'all',
    'query' => '',
    'page' => 1,
];

require views_path('admin/rescuers/index.php');

$currentUser = (new UserRepository($pdo))->find($uid);
$currentUserData = $currentUser ? $currentUser->toArray() : [];
$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($currentUserData['profile_photo_url'] ?? ''),
];
$activeNav = 'rescuers';
$navBadges = [
    'notifications' => null,
    'rescuers' => $pendingCount,
];

ob_start();
require views_path('layouts/admin.php');
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Rescuers';
$pageDescription = 'FurEscue admin rescuers — manage rescuers, duty status, and review applications for City of Mati.';
$pageCss = [
    '/admin/css/admin.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require views_path('components/site-head.php');
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/rescuers/js/rescuers.js"></script>
  </body>
</html>

