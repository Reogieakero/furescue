<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\CaseRepository;
use App\Repositories\ReportRepository;
use App\Repositories\Repository;
use App\Repositories\UserRepository;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require_once dirname(__DIR__, 3) . '/views/path.php';
require views_path('components/guard.php');

require views_path('components/admin-ui-helpers.php');

// Missing/malformed id or unknown case -> back to the cases list. The legacy
// JS alerted "No case specified." / rendered a not-found panel; a redirect is
// the server-side equivalent.
$caseIdParam = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
if ($caseIdParam === '' || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $caseIdParam) !== 1) {
    header('Location: /admin/cases/');
    exit;
}

$pdo = Database::connect();
$uid = (string) $_SESSION['user']['id'];

$caseEntity = (new CaseRepository($pdo))->find($caseIdParam);
if ($caseEntity === null) {
    header('Location: /admin/cases/');
    exit;
}
$caseRow = $caseEntity->toArray();

// Activity log — mirrors CaseController::activity() serialization.
$activityRows = (new Repository(
    $pdo,
    'case_activity_log',
    ['id', 'case_id', 'actor_id', 'actor_role', 'action', 'notes', 'created_at']
))->all(['case_id' => $caseIdParam], 'created_at', 'ASC');

$report = null;
if (!empty($caseRow['report_id'])) {
    $reportEntity = (new ReportRepository($pdo))->find((string) $caseRow['report_id']);
    if ($reportEntity !== null) {
        $report = $reportEntity->toArray();
    }
}

$rescuer = null;
if (!empty($caseRow['assigned_rescuer_id'])) {
    $rescuerEntity = (new UserRepository($pdo))->find((string) $caseRow['assigned_rescuer_id']);
    if ($rescuerEntity !== null) {
        $rescuer = $rescuerEntity->toArray();
    }
}

$rescuerName = (string) ($rescuer['full_name'] ?? '');
$rescuerPhoto = (string) ($rescuer['profile_photo_url'] ?? '');

// Port of pages/case-detail/components/util.js photos().
$photosOf = static function (mixed $value): array {
    if ($value === null || $value === '') {
        return [];
    }
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }
        $value = $decoded;
    }
    if (!is_array($value)) {
        return [];
    }
    $out = [];
    foreach ($value as $item) {
        if (is_string($item)) {
            $out[] = $item;
        } elseif (is_array($item)) {
            $url = $item['url'] ?? '';
            $out[] = is_string($url) ? $url : '';
        } else {
            $out[] = '';
        }
    }
    return array_values(array_filter($out, static fn($u) => is_string($u) && $u !== ''));
};

$attachments = $photosOf(is_array($report) ? ($report['photo_urls'] ?? null) : null);
$proofUrls = $photosOf($caseRow['resolution_photos'] ?? null);

require views_path('admin/cases/case-detail.php');

$authState = include views_path('components/admin-page-auth-state.php');

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
    'caseId' => $caseIdParam,
    'caseData' => $caseRow + [
        'report' => $report,
        'rescuer' => $rescuer,
        'rescuer_name' => $rescuerName,
        'rescuer_photo' => $rescuerPhoto,
    ],
    'report' => $report,
    'rescuer' => $rescuer,
    'activity' => $activityRows,
    'attachments' => $attachments,
    'proof' => $proofUrls,
    'error' => null,
];

$currentUser = (new UserRepository($pdo))->find($uid);
$currentUserData = $currentUser ? $currentUser->toArray() : [];
$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => '',
];
$activeNav = 'cases';
$navBadges = ['notifications' => 0, 'cases' => 0];

ob_start();
require views_path('layouts/admin.php');
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Case Detail';
$pageDescription = 'FurEscue admin case detail — transactions, workflow, and progress for a single rescue case.';
$pageCss = [
    '/admin/css/admin.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
$importMapExtras = ['chart.js' => 'https://esm.sh/chart.js@4.4.4/auto'];
require views_path('components/site-head.php');
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script type="module" src="/admin/cases/js/case-detail.js"></script>
  </body>
</html>

