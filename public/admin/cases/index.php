<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\ReportRepository;
use App\Repositories\UserRepository;
use App\Services\GeoService;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../../includes/guard.php';

require __DIR__ . '/../includes/ui-helpers.php';

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

$pageHeadHtml = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Rescue Management</span>
      <h1 class="page-title">Cases</h1>
      <p class="page-sub">Track active rescues, assign rescuers, and follow each case to resolution.</p>
    </div>
    <div class="page-head-actions">
      ' . button_html('Export CSV', 'outline', icon: 'download') . '
    </div>
  </div>';

$kpiData = [
    ['icon' => 'clipboard-list', 'value' => $cAll, 'label' => 'Total cases', 'note' => null, 'dark' => false, 'desc' => 'Every case in the system, all statuses included.'],
    ['icon' => 'folder-open', 'value' => $cOpen, 'label' => 'Open', 'note' => $cOpen > 0 ? ['text' => 'Intake', 'cls' => 'kpi-note--coral'] : null, 'dark' => false, 'desc' => 'Newly reported cases not yet assigned to a rescuer.'],
    ['icon' => 'user-plus', 'value' => $cAssigned, 'label' => 'Assigned', 'note' => null, 'dark' => false, 'desc' => 'Assigned to a rescuer, awaiting their acceptance.'],
    ['icon' => 'activity', 'value' => $cInProgress, 'label' => 'In progress', 'note' => null, 'dark' => true, 'desc' => 'Rescues that are actively underway.'],
    ['icon' => 'check-circle-2', 'value' => $cResolved, 'label' => 'Resolved', 'note' => null, 'dark' => false, 'desc' => 'Cases successfully completed and closed.'],
];
$kpiTiles = '';
foreach ($kpiData as $k) {
    $note = '';
    if (!empty($k['note'])) {
        $note = '<span class="kpi-note ' . e($k['note']['cls']) . '">' . e($k['note']['text']) . '</span>';
    }
    $tileCls = 'kpi-tile' . (!empty($k['dark']) ? ' kpi-tile--dark' : '');
    $kpiTiles .= "
  <div class=\"{$tileCls}\">
    <div class=\"kpi-top\">
      <div class=\"kpi-icon\"><i data-lucide=\"{$k['icon']}\"></i></div>
      {$note}
    </div>
    <div class=\"kpi-value\">" . e($k['value']) . "</div>
    <div class=\"kpi-label\">" . e($k['label']) . '</div>
    <div class="kpi-desc">' . e($k['desc']) . '</div>
  </div>';
}

$legendRows = '';
foreach ([
    ['Open', $cOpen, 'status-seg--open'],
    ['Assigned', $cAssigned, 'status-seg--assigned'],
    ['In progress', $cInProgress, 'status-seg--live'],
    ['Resolved', $cResolved, 'status-seg--resolved'],
] as [$legendLabel, $legendVal, $legendCls]) {
    $legendRows .= '<span class="status-legend-item"><span class="status-dot ' . $legendCls . '"></span>' . e($legendLabel) . ' &middot; ' . e($legendVal) . '</span>';
}
$statusChartHtml = '
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="pie-chart"></i><h2 class="panel-title panel-title--sm">Case status breakdown</h2></div>
    <div class="donut-wrap">
      <div class="donut">
        <canvas id="status-donut"></canvas>
        <div class="donut-center"><span class="donut-total">' . e($cAll) . '</span><span class="donut-label">Cases</span></div>
      </div>
      <div class="status-legend">' . $legendRows . '</div>
    </div>
  </div>';

$kpiStripHtml = '<div class="kpi-grid">' . $kpiTiles . '</div><div class="kpi-donut" id="kpi-donut-card">' . $statusChartHtml . '</div>';

$tabButtons = '';
foreach ([
    ['all', 'All', $cAll],
    ['open', 'Open', $cOpen],
    ['assigned', 'Assigned', $cAssigned],
    ['in_progress', 'In Progress', $cInProgress],
    ['resolved', 'Resolved', $cResolved],
] as [$filterKey, $filterLabel, $filterCount]) {
    $active = $filterKey === 'in_progress' ? ' is-active' : '';
    $tabButtons .= '<button data-filter="' . e($filterKey) . '" class="q-btn' . $active . '">' . e($filterLabel) . ' &middot; ' . e($filterCount) . '</button>';
}

$toolbarHtml = '
  <div class="report-toolbar">
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="case-search" type="text" placeholder="Search case #, barangay, animal…" value="">
    </div>
    <div class="report-sort">
      ' . select_control('case-sort', [
          ['value' => '', 'label' => 'Sort'],
          ['value' => 'newest', 'label' => 'Newest'],
          ['value' => 'status', 'label' => 'Status'],
          ['value' => 'updated', 'label' => 'Updated'],
      ], '', 'Sort', '', '', 'report-sort-control') . '
    </div>
  </div>';

$rescuerChip = static function (?array $rescuer): string {
    if ($rescuer === null) {
        return '<span class="case-card-unassigned">Unassigned</span>';
    }
    $name = (string) ($rescuer['full_name'] ?? '');
    return '
    <span class="case-card-rescuer">
      <span class="table-avatar table-avatar--initial">' . e(initials_of($name)) . '</span>
      <span class="case-card-rescuer-name">' . e($name) . '</span>
    </span>';
};

$caseAction = static function (array $c): string {
    if ($c['rescuer'] === null && $c['statusRaw'] === 'open') {
        $attrs = 'data-action="assign" data-case="' . e($c['id']) . '" data-report="' . e(($c['report']['id'] ?? '') === null ? '' : (string) ($c['report']['id'] ?? '')) . '"';
        return button_html('Assign rescuer', 'default', 'sm', '', 'user-plus', $attrs);
    }
    if ($c['statusRaw'] === 'assigned') {
        return '<span class="action-text">' . e('Waiting for rescuer to accept the assigned rescue') . '</span>';
    }
    if ($c['statusRaw'] === 'in_progress') {
        return '<span class="action-text">' . e('In progress') . '</span>';
    }
    return '';
};

$caseCardHtml = static function (array $c) use ($rescuerChip, $caseAction): string {
    $live = $c['statusRaw'] === 'in_progress' ? ' case-card--live' : '';
    $time = $c['statusRaw'] === 'in_progress' ? 'Updated ' . e($c['updated']) : e($c['when']);
    return "
  <article class=\"case-card{$live}\" data-case-id=\"" . e($c['id']) . '">
    <div class="case-card-head">
      <span class="case-card-id">' . e($c['shortId']) . '</span>
      <span class="stamp stamp--sm ' . e($c['statusCls']) . '">' . e($c['status']) . '</span>
    </div>
    <div class="case-card-body">
      <div class="case-card-row"><i data-lucide="map-pin"></i><span>' . e($c['brgy']) . '</span></div>
      <div class="case-card-row"><i data-lucide="paw-print"></i><span>' . e($c['animal']) . '</span></div>
    </div>
    <div class="case-card-foot">
      ' . $rescuerChip($c['rescuer']) . '
      <span class="case-card-time">' . $time . '</span>
    </div>
    <div class="case-card-actions">' . $caseAction($c) . '</div>
  </article>';
};

$initialList = array_values(array_filter($enrichedCases, static fn(array $c) => $c['statusRaw'] === 'in_progress'));
usort($initialList, static fn(array $a, array $b) => strtotime($b['createdAt']) <=> strtotime($a['createdAt']));
$casePageSize = 6;
$casePageItems = array_slice($initialList, 0, $casePageSize);

if ($initialList === []) {
    $listInnerHtml = '<div class="queue-empty"><div class="empty-state"><i data-lucide="clipboard-list"></i><span>No cases match.</span></div></div>';
} else {
    $cards = '';
    foreach ($casePageItems as $c) {
        $cards .= $caseCardHtml($c);
    }
    $listInnerHtml = '<div class="case-grid">' . $cards . '</div>'
        . (count($initialList) > $casePageSize
            ? '<div class="queue-pagination">' . pagination_bar(count($initialList), $casePageSize, 1) . '</div>'
            : '');
}

$pinCount = count(array_filter($enrichedCases, static fn(array $c) => $c['lat'] !== null && $c['lng'] !== null));

$mapPanelHtml = '
  <div class="panel case-map-panel" id="case-map-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="map"></i>
        <h2 class="panel-title">Case map &middot; City of Mati</h2>
      </div>
      <div class="map-tools">
        <span class="map-label">Heat intensity</span>
        ' . select_control('case-map-intensity', [
            ['value' => 'low', 'label' => 'Low'],
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'high', 'label' => 'High'],
        ], 'medium', 'Heat intensity') . '
        <button type="button" id="case-map-expand" class="map-expand" aria-label="Expand map" title="Expand map"><i data-lucide="maximize"></i></button>
        <div class="map-toggle" id="case-map-toggle" role="group" aria-label="Map display mode">
          <button type="button" class="map-toggle-btn is-active" data-map-mode="pins"><i data-lucide="map-pin"></i> Pins</button>
          <button type="button" class="map-toggle-btn" data-map-mode="heatmap"><i data-lucide="flame"></i> Heatmap</button>
        </div>
      </div>
    </div>
    <div id="case-map" class="map-canvas map-canvas--leaflet case-map"></div>
    <div class="map-foot"><span id="case-map-count">' . e($pinCount) . '</span> <span id="case-map-foot-label">pinned cases &middot; Click a pin for details</span></div>
  </div>';

$children = $pageHeadHtml
    . '<div id="case-kpis" class="case-kpis">' . $kpiStripHtml . '</div>'
    . '<div class="cols case-split">'
    . '<div class="case-list-col">'
    . '
  <div class="panel case-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="clipboard-list"></i>
        <h2 class="panel-title">Cases</h2>
      </div>
      <div class="panel-head-tools">
        <div id="case-tabs-wrap"><div class="q-tabs" id="case-tabs">' . $tabButtons . '</div></div>
        <span class="stamp stamp--sm stamp--accent" id="case-total-badge">' . e($cAll) . '</span>
      </div>
    </div>
    <div id="case-controls">' . $toolbarHtml . '</div>
    <div id="case-list" class="panel-body">' . $listInnerHtml . '</div>
  </div>'
    . '</div>'
    . '<div class="case-map-col">' . $mapPanelHtml . '</div>'
    . '</div>';

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
$adminChildren = $children;

ob_start();
require __DIR__ . '/../../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Cases';
$pageDescription = 'FurEscue admin cases — track active rescues, assign rescuers and follow case activity for City of Mati.';
$pageCss = [
    '/admin/css/admin.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
$importMapExtras = ['chart.js' => 'https://esm.sh/chart.js@4.4.4/auto'];
require __DIR__ . '/../../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script type="module" src="/admin/cases/js/cases.js"></script>
  </body>
</html>
