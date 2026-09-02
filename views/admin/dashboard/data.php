<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\ReportRepository;
use App\Repositories\Repository;
use App\Repositories\UserRepository;
use App\Services\GeoService;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require_once dirname(__DIR__, 3) . '/views/path.php';
require views_path('components/guard.php');

require views_path('components/admin-ui-helpers.php');
require __DIR__ . '/insights.php';

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
    'cases_in_progress' => $countWhere("cases", "status IN ('assigned','in_progress')"),
    'reports_pending' => $countWhere("reports", "status = 'pending_verification'"),
    'reports_today' => $countWhere("reports", "DATE(created_at) = CURDATE()"),
    'pending_today' => $countWhere("reports", "status = 'pending_verification' AND DATE(created_at) = CURDATE()"),
    'in_progress_today' => $countWhere("cases", "status IN ('assigned','in_progress') AND DATE(updated_at) = CURDATE()"),
    'resolved_today' => $countWhere("cases", "status = 'resolved' AND DATE(updated_at) = CURDATE()"),
];

$reportRepo = new ReportRepository($pdo);
$reportsPendingResult = $reportRepo->paginate(1, 100, ['status' => 'pending_verification']);
$allReportsResult = $reportRepo->paginate(1, 100, []);
$reportsPendingItems = array_map(static fn($r) => $r->toArray(), $reportsPendingResult['items']);
$stmt = $pdo->prepare(
    "SELECT r.id, r.resident_id, r.animal_description, r.latitude, r.longitude,
            r.address_text, r.status, r.created_at, r.validation_status,
            u.full_name AS resident_name,
            c.status AS case_status, c.id AS case_id
     FROM reports r
     LEFT JOIN users u ON u.id = r.resident_id
     LEFT JOIN cases c ON c.report_id = r.id
     ORDER BY r.created_at DESC
     LIMIT 100"
);
$stmt->execute();
$allReportItems = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$freshRescuersByStatus = static function (string $accountStatus) use ($pdo): array {
    $stmt = $pdo->prepare(
        "SELECT u.*, COALESCE(d.status, 'off_duty') AS duty_status
         FROM users u
         LEFT JOIN rescuer_duty_status d ON d.user_id = u.id
         WHERE u.account_status = ?
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
$rescuersPendingItems = $freshRescuersByStatus('pending');
$rescuersActive = $freshRescuersByStatus('active');

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM adoptions a WHERE a.status = ?");
$totalStmt->execute(['pending']);
$adoptionsTotal = (int) $totalStmt->fetchColumn();
$stmt = $pdo->prepare(
    "SELECT a.*, u.full_name AS applicant_name, an.name AS animal_name
     FROM adoptions a
     LEFT JOIN users u ON u.id = a.applicant_id
     LEFT JOIN animals an ON an.id = a.animal_id
     WHERE a.status = ?
     ORDER BY a.created_at DESC
     LIMIT 100 OFFSET 0"
);
$stmt->execute(['pending']);
$adoptionsPendingItems = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM cases c');
$totalStmt->execute();
$casesTotal = (int) $totalStmt->fetchColumn();
$stmt = $pdo->prepare(
    "SELECT c.*, r.animal_description, r.address_text, u.full_name AS assigned_rescuer_name
     FROM cases c
     LEFT JOIN reports r ON r.id = c.report_id
     LEFT JOIN users u ON u.id = c.assigned_rescuer_id
     ORDER BY c.updated_at DESC
     LIMIT 100 OFFSET 0"
);
$stmt->execute();
$caseList = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$notifRepo = new Repository($pdo, 'notifications', ['id', 'user_id', 'type', 'message', 'related_type', 'related_id', 'is_read', 'created_at']);
$notificationsResult = $notifRepo->paginate(1, 100, ['user_id' => $uid, 'is_read' => 0], 'created_at', 'DESC');

$moduleRepo = new Repository($pdo, 'elearning_modules', ['id', 'title', 'category', 'published_status', 'created_at']);
$publishedResult = $moduleRepo->paginate(1, 100, ['published_status' => 'published']);
$draftsResult = $moduleRepo->paginate(1, 100, ['published_status' => 'draft']);
$elearning = [
    'published' => $publishedResult['total'],
    'drafts' => $draftsResult['total'],
    'items' => $publishedResult['items'],
];

$stmt = $pdo->prepare(
    "SELECT DATE(completed_at) AS day, COUNT(*) AS completed
     FROM adoptions WHERE status = 'completed' AND completed_at IS NOT NULL
     GROUP BY DATE(completed_at) ORDER BY day DESC LIMIT 30"
);
$stmt->execute();
$trends = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$heatmap = (new GeoService())->heatmapPoints('all');

$stmt = $pdo->prepare(
    "SELECT a.id, a.name, a.species, a.photo_urls,
            am.vaccination_status, am.vaccination_expiry, am.next_checkup_due,
            am.last_checkup_date, am.treatment_stage,
            fs.health_status
     FROM animals a
     LEFT JOIN animal_medical_records am ON am.animal_id = a.id
     LEFT JOIN (
         SELECT fs1.animal_id, fs1.health_status
         FROM animal_field_status fs1
         INNER JOIN (
             SELECT animal_id, MAX(logged_at) AS mx
             FROM animal_field_status
             GROUP BY animal_id
         ) fs2 ON fs2.animal_id = fs1.animal_id AND fs2.mx = fs1.logged_at
     ) fs ON fs.animal_id = a.id
     WHERE a.deleted_at IS NULL
     ORDER BY a.created_at DESC
     LIMIT 200"
);
$stmt->execute();
$healthRecordRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
$healthRecords = array_map(static function (array $r): array {
    return [
        'id' => $r['id'],
        'animalId' => $r['id'],
        'animalName' => $r['name'] ?? 'Unnamed',
        'name' => $r['name'] ?? 'Unnamed',
        'species' => $r['species'],
        'photo_urls' => $r['photo_urls'],
        'vaccinationStatus' => $r['vaccination_status'] ?? 'none',
        'vaccinationExpiry' => $r['vaccination_expiry'],
        'lastCheckupDate' => $r['last_checkup_date'],
        'nextCheckupDue' => $r['next_checkup_due'],
        'healthStatus' => $r['health_status'] ?? 'healthy',
        'treatmentStage' => $r['treatment_stage'] ?? 'none',
    ];
}, $healthRecordRows);
$reportTrend = dash_report_trend($pdo);
$overview['reports_monthly'] = $reportTrend;

$stmt = $pdo->prepare(
    "SELECT fs.id, fs.animal_id, fs.rescue_status, fs.health_status, fs.logged_at,
            a.name AS animal_name, a.species, a.breed_type,
            u.full_name AS logged_by_name
     FROM animal_field_status fs
     JOIN animals a ON a.id = fs.animal_id
     LEFT JOIN users u ON u.id = fs.logged_by
     ORDER BY fs.logged_at DESC
     LIMIT 50"
);
$stmt->execute();
$updates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$trendMap = [];
foreach ($trends as $t) {
    $trendMap[(string) $t['day']] = (int) $t['completed'];
}
$nowParts = getdate();
$bars = [];
for ($i = 6; $i >= 0; $i--) {
    $ts = mktime(0, 0, 0, $nowParts['mon'], $nowParts['mday'] - $i, $nowParts['year']);
    $key = gmdate('Y-m-d', $ts);
    $bars[] = ['day' => date('D', $ts), 'count' => $trendMap[$key] ?? 0];
}
$max = 1;
foreach ($bars as $b) {
    $max = max($max, $b['count']);
}
$peakIndex = 0;
foreach ($bars as $idx => $b) {
    if ($b['count'] === $max) {
        $peakIndex = $idx;
        break;
    }
}
$chartBars = [];
foreach ($bars as $idx => $b) {
    $chartBars[] = [
        'day' => $b['day'],
        'count' => $b['count'],
        'h' => js_round(($b['count'] / $max) * 100),
        'coral' => $idx === $peakIndex,
    ];
}
$curSum = array_sum(array_column($chartBars, 'count'));
$prevSum = 0;
for ($i = 13; $i >= 7; $i--) {
    $ts = mktime(0, 0, 0, $nowParts['mon'], $nowParts['mday'] - $i, $nowParts['year']);
    $prevSum += $trendMap[gmdate('Y-m-d', $ts)] ?? 0;
}
$growth = null;
if ($prevSum > 0) {
    $growth = js_round((($curSum - $prevSum) / $prevSum) * 100);
}

$reportsTotal = $allReportsResult['total'] ?: count($allReportItems);
$reportsPending = ['items' => $reportsPendingItems, 'total' => $reportsPendingResult['total']];
$rescuersPending = ['items' => $rescuersPendingItems, 'total' => count($rescuersPendingItems)];
$adoptionsPending = ['items' => $adoptionsPendingItems, 'total' => $adoptionsTotal];
$notificationsState = ['items' => $notificationsResult['items'], 'total' => $notificationsResult['total']];
$healthUpdatesState = ['items' => $updates, 'total' => count($updates)];

$currentUser = (new UserRepository($pdo))->find($uid);
$greetingName = ($currentUser && !empty($currentUser->toArray()['full_name'])) ? (string) $currentUser->toArray()['full_name'] : 'Admin';

$onDutyRescuers = array_values(array_filter($rescuersActive, static fn($u) => ($u['duty_status'] ?? 'off_duty') === 'on_duty'));
$resolvedCases = count(array_filter($caseList, static fn($c) => ($c['status'] ?? '') === 'resolved'));
$decisionCount = $reportsPending['total'] + $rescuersPending['total'] + $healthUpdatesState['total'] + $adoptionsPending['total'];

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
    'reports' => $allReportItems,
    'reportsTotal' => $reportsTotal,
    'reportsPending' => $reportsPending,
    'rescuersPending' => $rescuersPending,
    'healthUpdates' => $healthUpdatesState,
    'adoptionsPending' => $adoptionsPending,
    'rescuers' => $rescuersActive,
    'activity' => $caseList,
    'chart' => $chartBars,
    'growth' => $growth,
    'elearning' => $elearning,
    'notifications' => $notificationsState,
    'heatmap' => $heatmap,
    'healthRecords' => $healthRecords,
    'reportTrend' => $reportTrend,
    'decisionCount' => $decisionCount,
    'activityPage' => 1,
];
