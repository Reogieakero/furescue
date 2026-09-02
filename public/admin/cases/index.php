<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\ReportRepository;
use App\Repositories\UserRepository;
use App\Services\GeoService;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require_once dirname(__DIR__, 3) . '/views/path.php';
require views_path('components/guard.php');

require views_path('components/admin-ui-helpers.php');

$pdo = Database::connect();
$uid = (string) $_SESSION['user']['id'];

$casesStmt = $pdo->prepare(
    "SELECT c.*, r.animal_description, r.address_text, u.full_name AS assigned_rescuer_name
     FROM cases c
     LEFT JOIN reports r ON r.id = c.report_id
     LEFT JOIN users u ON u.id = c.assigned_rescuer_id
     ORDER BY c.updated_at DESC
     LIMIT 100 OFFSET 0"
);
$casesStmt->execute();
$caseList = $casesStmt->fetchAll(\PDO::FETCH_ASSOC);

$rescuersStmt = $pdo->prepare(
    "SELECT u.*, COALESCE(d.status, 'off_duty') AS duty_status
     FROM users u
     LEFT JOIN rescuer_duty_status d ON d.user_id = u.id
     WHERE u.account_status = ?
     ORDER BY u.created_at DESC
     LIMIT 100 OFFSET 0"
);
$rescuersStmt->execute(['active']);
$rescuersActive = array_map(static function (array $u) {
    unset($u['password_hash']);
    return $u;
}, $rescuersStmt->fetchAll(\PDO::FETCH_ASSOC));

$reportRepo = new ReportRepository($pdo);
$allReportsResult = $reportRepo->paginate(1, 100, []);
$allReportItems = array_map(static fn($r) => $r->toArray(), $allReportsResult['items']);

$heatmap = (new GeoService())->heatmapPoints(null);

$reportsById = [];
foreach ($allReportItems as $r) {
    if (!empty($r['id'])) {
        $reportsById[(string) $r['id']] = $r;
    }
}
$rescuersById = [];
foreach ($rescuersActive as $u) {
    if (!empty($u['id'])) {
        $rescuersById[(string) $u['id']] = $u;
    }
}

$finitize = static function (mixed $v): ?float {
    if ($v === null || $v === '' || !is_numeric((string) $v)) {
        return null;
    }
    $n = (float) $v;
    return is_finite($n) ? $n : null;
};

$enrichCase = static function (array $c) use ($reportsById, $rescuersById, $finitize): array {
    $report = !empty($c['report_id']) ? ($reportsById[(string) $c['report_id']] ?? null) : null;
    $rescuer = !empty($c['assigned_rescuer_id']) ? ($rescuersById[(string) $c['assigned_rescuer_id']] ?? null) : null;
    $statusRaw = (($c['status'] ?? '') !== '') ? (string) $c['status'] : 'open';
    $lat = array_key_exists('latitude', $c) && $c['latitude'] !== null
        ? $finitize($c['latitude'])
        : $finitize($report['latitude'] ?? null);
    $lng = array_key_exists('longitude', $c) && $c['longitude'] !== null
        ? $finitize($c['longitude'])
        : $finitize($report['longitude'] ?? null);
    $updatedAt = ($c['updated_at'] ?? null) ?: ($c['created_at'] ?? null);
    return [
        'id' => (string) ($c['id'] ?? ''),
        'shortId' => short_id($c['id'] ?? null),
        'status' => title_case($statusRaw),
        'statusCls' => ($statusRaw === 'in_progress' || $statusRaw === 'resolved') ? 'stamp--accent' : 'stamp--coral',
        'statusRaw' => $statusRaw,
        'report' => $report,
        'rescuer' => $rescuer,
        'brgy' => $report !== null ? (($report['address_text'] ?? null) ?: '—') : '—',
        'animal' => $report !== null ? (($report['animal_description'] ?? null) ?: '—') : '—',
        'lat' => $lat,
        'lng' => $lng,
        'when' => time_ago($c['created_at'] ?? null),
        'updated' => time_ago($updatedAt),
        'createdAt' => (string) ($c['created_at'] ?? ''),
        'updatedAt' => (string) ($updatedAt ?? ''),
    ];
};
$enrichedCases = array_map($enrichCase, $caseList);

$statusCountOf = static function (string $raw) use ($enrichedCases): int {
    return count(array_filter($enrichedCases, static fn(array $c) => $c['statusRaw'] === $raw));
};
$cAll = count($enrichedCases);
$cOpen = $statusCountOf('open');
$cAssigned = $statusCountOf('assigned');
$cInProgress = $statusCountOf('in_progress');
$cResolved = $statusCountOf('resolved');

require views_path('admin/cases/index.php');

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
    'cases' => $caseList,
    'reports' => $allReportItems,
    'rescuers' => $rescuersActive,
    'heatmap' => $heatmap,
    'filter' => 'in_progress',
    'query' => '',
    'sort' => '',
    'page' => 1,
];

$currentUser = (new UserRepository($pdo))->find($uid);
$currentUserData = $currentUser ? $currentUser->toArray() : [];
$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($currentUserData['profile_photo_url'] ?? ''),
];
$activeNav = 'cases';
$navBadges = ['notifications' => 0, 'cases' => $cAll];

ob_start();
require views_path('layouts/admin.php');
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Cases';
$pageDescription = 'FurEscue admin cases — track active rescues, assign rescuers and follow case activity for City of Mati.';
$pageCss = [
    '/admin/css/admin.css',
    '/admin/cases/css/kpis.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
$importMapExtras = ['chart.js' => 'https://esm.sh/chart.js@4.4.4/auto'];
require views_path('components/site-head.php');
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script type="module" src="/admin/cases/js/cases.js"></script>
  </body>
</html>

