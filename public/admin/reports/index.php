<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\ReportRepository;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require_once dirname(__DIR__, 3) . '/views/path.php';
require views_path('components/guard.php');

require views_path('components/admin-ui-helpers.php');

$pdo = Database::connect();
$uid = (string) $_SESSION['user']['id'];

$countAll = static function (string $table) use ($pdo): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table}");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
};
$countWhere = static function (string $table, string $where) use ($pdo): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
};

$overview = [
    'reports' => $countAll('reports'),
    'reports_verified' => $countWhere("reports", "status = 'verified'"),
    'cases' => $countAll('cases'),
    'cases_resolved' => $countWhere("cases", "status = 'resolved'"),
    'animals' => $countAll('animals'),
    'animals_adopted' => $countWhere("animals", "adoption_status = 'adopted'"),
    'adoptions_pending' => $countWhere("adoptions", "status = 'pending'"),
    'adoptions_completed' => $countWhere("adoptions", "status = 'completed'"),
    'rescuers_on_duty' => $countWhere(
        "rescuer_duty_status d JOIN users u ON u.id = d.user_id",
        "d.status = 'on_duty' AND u.account_status = 'active' AND u.role = 'rescuer'"
    ),
    'residents' => $countWhere("users", "role = 'resident'"),
];

$reportRepo = new ReportRepository($pdo);
$allReportsResult = $reportRepo->paginate(1, 100, []);
$reports = array_map(static fn($r) => $r->toArray(), $allReportsResult['items']);

$caseStmt = $pdo->prepare(
    "SELECT c.*, r.animal_description, r.address_text, u.full_name AS assigned_rescuer_name
     FROM cases c
     LEFT JOIN reports r ON r.id = c.report_id
     LEFT JOIN users u ON u.id = c.assigned_rescuer_id
     ORDER BY c.updated_at DESC
     LIMIT 100 OFFSET 0"
);
$caseStmt->execute();
$cases = $caseStmt->fetchAll(\PDO::FETCH_ASSOC);

$rescuerStmt = $pdo->prepare(
    "SELECT u.*, COALESCE(d.status, 'off_duty') AS duty_status
     FROM users u
     LEFT JOIN rescuer_duty_status d ON d.user_id = u.id
     WHERE u.account_status = ?
     ORDER BY u.created_at DESC
     LIMIT 100 OFFSET 0"
);
$rescuerStmt->execute(['active']);
$rescuers = array_map(static function (array $u) {
    unset($u['password_hash']);
    return $u;
}, $rescuerStmt->fetchAll(\PDO::FETCH_ASSOC));

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
    'overview' => $overview,
    'reports' => $reports,
    'cases' => $cases,
    'rescuers' => $rescuers,
];

require views_path('admin/reports/index.php');

$currentUserData = (new \App\Repositories\UserRepository($pdo))->find($uid);
$currentUserData = $currentUserData ? $currentUserData->toArray() : [];
$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($currentUserData['profile_photo_url'] ?? ''),
];
$activeNav = 'reports';
$navBadges = [
    'notifications' => null,
    'reports' => $counts['all'],
];

ob_start();
require views_path('layouts/admin.php');
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Reports';
$pageDescription = 'FurEscue admin reports — verify, dismiss, assign rescuers and track case workflow for City of Mati.';
$pageCss = [
    '/admin/css/admin.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require views_path('components/site-head.php');
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script type="module" src="/admin/reports/js/reports.js"></script>
  </body>
</html>

