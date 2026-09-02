<?php

declare(strict_types=1);

use App\Database;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require_once dirname(__DIR__, 3) . '/views/path.php';
require views_path('components/guard.php');

require views_path('components/admin-ui-helpers.php');

require __DIR__ . '/helpers.php';

$pdo = Database::connect();

$validDate = static function (mixed $raw): string {
    $raw = trim((string) $raw);
    $dt = \DateTime::createFromFormat('Y-m-d', $raw);
    return ($dt && $dt->format('Y-m-d') === $raw) ? $raw : '';
};
$start = $validDate($_GET['start'] ?? '');
$end = $validDate($_GET['end'] ?? '');
$ranged = $start !== '' && $end !== '';

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

$overviewLabels = [
    'reports' => 'Total reports',
    'reports_verified' => 'Reports verified',
    'cases' => 'Total cases',
    'cases_resolved' => 'Cases resolved',
    'animals' => 'Total animals',
    'animals_adopted' => 'Animals adopted',
    'adoptions_pending' => 'Adoptions pending',
    'adoptions_completed' => 'Adoptions completed',
    'rescuers_on_duty' => 'Rescuers on duty',
    'residents' => 'Residents',
];
$overviewRows = [];
foreach ($overviewLabels as $key => $label) {
    $overviewRows[] = ['key' => $key, 'label' => $label, 'value' => $overview[$key]];
}

$trendSql = "SELECT DATE(completed_at) AS day, COUNT(*) AS completed
     FROM adoptions WHERE status = 'completed' AND completed_at IS NOT NULL";
$trendArgs = [];
if ($ranged) {
    $trendSql .= " AND DATE(completed_at) BETWEEN ? AND ?";
    $trendArgs = [$start, $end];
}
$trendSql .= $ranged
    ? " GROUP BY DATE(completed_at) ORDER BY day ASC LIMIT 400"
    : " GROUP BY DATE(completed_at) ORDER BY day DESC LIMIT 30";
$stmt = $pdo->prepare($trendSql);
$stmt->execute($trendArgs);
$trends = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$updateSql = "SELECT fs.id, fs.animal_id, fs.rescue_status, fs.health_status, fs.logged_at,
            a.name AS animal_name, a.species, a.breed_type,
            u.full_name AS logged_by_name
     FROM animal_field_status fs
     JOIN animals a ON a.id = fs.animal_id
     LEFT JOIN users u ON u.id = fs.logged_by";
$updateArgs = [];
if ($ranged) {
    $updateSql .= " WHERE DATE(fs.logged_at) BETWEEN ? AND ?";
    $updateArgs = [$start, $end];
}
$updateSql .= $ranged
    ? " ORDER BY fs.logged_at DESC LIMIT 500"
    : " ORDER BY fs.logged_at DESC LIMIT 50";
$stmt = $pdo->prepare($updateSql);
$stmt->execute($updateArgs);
$updates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$stmtUser = $pdo->prepare('SELECT id, full_name, email, role, profile_photo_url FROM users WHERE id = ?');
$stmtUser->execute([(string) $_SESSION['user']['id']]);
$currentUserData = $stmtUser->fetch(\PDO::FETCH_ASSOC) ?: [];

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
    'range' => ['start' => $start, 'end' => $end],
    'overview' => $overviewRows,
    'trends' => $trends,
    'updates' => $updates,
];

$adminUser = [
    'id' => (string) $_SESSION['user']['id'],
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($currentUserData['profile_photo_url'] ?? ''),
];
$activeNav = 'analytics';
$navBadges = [];

ob_start();
require views_path('admin/analytics/view.php');
$adminChildren = (string) ob_get_clean();

ob_start();
require views_path('layouts/admin.php');
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Analytics & Exports';
$pageDescription = 'FurEscue admin analytics — shelter-wide metrics with date-range filtering and CSV exports.';
$pageCss = [
    '/admin/css/admin.css',
    '/admin/analytics/css/analytics.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require views_path('components/site-head.php');
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/analytics/js/analytics.js"></script>
  </body>
</html>
