<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\ReportRepository;

require __DIR__ . '/../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../includes/guard.php';

require __DIR__ . '/includes/ui-helpers.php';

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

$state = [
    'overview' => $overview,
    'reports' => $reports,
    'cases' => $cases,
    'rescuers' => $rescuers,
];

$rptStampCls = static function (?string $status): string {
    if ($status === 'dismissed' || $status === 'rejected') {
        return 'stamp--muted';
    }
    if ($status === 'assigned' || $status === 'pending_verification' || $status === 'open') {
        return 'stamp--coral';
    }
    return 'stamp--accent';
};

$rptBtnBase = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50';
$rptBtnVariants = [
    'default' => 'bg-primary text-primary-foreground shadow hover:bg-primary/90',
    'outline' => 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
    'destructive' => 'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90',
];
$rptButton = static function (string $text, string $variant, string $icon, string $attrs) use ($rptBtnBase, $rptBtnVariants): string {
    $cls = trim($rptBtnBase . ' ' . $rptBtnVariants[$variant] . ' h-7 px-3');
    return '<button type="button" class="' . e($cls) . '" ' . $attrs . '><i data-lucide="' . e($icon) . '" class="icon"></i><span>' . e($text) . '</span></button>';
};

$counts = [
    'all' => count($reports),
    'pending' => count(array_filter($reports, static fn(array $r) => ($r['status'] ?? null) === 'pending_verification')),
    'verified' => count(array_filter($reports, static fn(array $r) => ($r['status'] ?? null) === 'verified')),
    'dismissed' => count(array_filter($reports, static fn(array $r) => ($r['status'] ?? null) === 'dismissed')),
    'activeCases' => count(array_filter($cases, static fn(array $c) => ($c['status'] ?? '') !== 'resolved')),
    'resolvedCases' => count(array_filter($cases, static fn(array $c) => ($c['status'] ?? '') === 'resolved')),
];

$kpiData = [
    ['icon' => 'map-pin', 'value' => $counts['all'], 'label' => 'Total reports', 'note' => null],
    ['icon' => 'badge-check', 'value' => $counts['pending'], 'label' => 'Pending verify', 'note' => $counts['pending'] ? ['text' => 'Needs You', 'cls' => 'kpi-note--coral'] : null],
    ['icon' => 'file-check', 'value' => $counts['verified'], 'label' => 'Verified', 'note' => null],
    ['icon' => 'file-x', 'value' => $counts['dismissed'], 'label' => 'Dismissed', 'note' => null],
    ['icon' => 'clipboard-list', 'value' => $counts['activeCases'], 'label' => 'Active cases', 'note' => null],
    ['icon' => 'check-circle-2', 'value' => $counts['resolvedCases'], 'label' => 'Resolved cases', 'dark' => true],
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
  </div>';
}

$caseByReport = [];
foreach ($cases as $c) {
    if (!empty($c['report_id'])) {
        $caseByReport[(string) $c['report_id']] = $c;
    }
}
$rescuerById = [];
foreach ($rescuers as $u) {
    $rescuerById[(string) ($u['id'] ?? '')] = $u;
}

$rptCaseStatusRank = static function (?array $c): int {
    if ($c === null) {
        return 0;
    }
    return match ((string) ($c['status'] ?? '')) {
        'open' => 1,
        'assigned' => 2,
        'in_progress' => 3,
        default => 4,
    };
};
usort($reports, static function (array $a, array $b) use ($caseByReport, $rptCaseStatusRank): int {
    $ca = $caseByReport[(string) ($a['id'] ?? '')] ?? null;
    $cb = $caseByReport[(string) ($b['id'] ?? '')] ?? null;
    $assignedA = ($ca !== null && !empty($ca['assigned_rescuer_id'])) ? 0 : 1;
    $assignedB = ($cb !== null && !empty($cb['assigned_rescuer_id'])) ? 0 : 1;
    if ($assignedA !== $assignedB) {
        return $assignedA <=> $assignedB;
    }
    $rankA = $rptCaseStatusRank($ca);
    $rankB = $rptCaseStatusRank($cb);
    if ($rankA !== $rankB) {
        return $rankA <=> $rankB;
    }
    $tsA = -((int) strtotime((string) ($a['created_at'] ?? '')));
    $tsB = -((int) strtotime((string) ($b['created_at'] ?? '')));
    return $tsA <=> $tsB;
});

$PAGE_SIZE = 15;

$actionLinksFor = static function (array $r) use ($caseByReport, $rptButton): string {
    $ridAttr = 'data-action="%s" data-id="' . e((string) ($r['id'] ?? '')) . '"';
    $status = (string) ($r['status'] ?? '');
    if ($status === 'pending_verification') {
        return $rptButton('Verify', 'default', 'badge-check', sprintf($ridAttr, 'verify'))
            . $rptButton('Dismiss', 'destructive', 'file-x', sprintf($ridAttr, 'dismiss'));
    }
    if ($status === 'verified') {
        $c = $caseByReport[(string) ($r['id'] ?? '')] ?? null;
        if ($c === null) {
            return '';
        }
        $cid = (string) ($c['id'] ?? '');
        $timeline = $rptButton('Timeline', 'outline', 'history', 'data-action="timeline" data-id="' . e((string) ($r['id'] ?? '')) . '" data-case="' . e($cid) . '"');
        $assignee = $c['assigned_rescuer_id'] ?? null;
        if (!$assignee) {
            return $rptButton('Assign rescuer', 'default', 'user-plus', 'data-action="assign" data-id="' . e((string) ($r['id'] ?? '')) . '" data-case="' . e($cid) . '"')
                . $timeline;
        }
        $cStatus = (string) ($c['status'] ?? '');
        if ($cStatus === 'assigned') {
            return $rptButton('Mark in progress', 'default', 'play', 'data-action="progress" data-id="' . e((string) ($r['id'] ?? '')) . '" data-case="' . e($cid) . '"')
                . $timeline;
        }
        if ($cStatus === 'in_progress') {
            return $rptButton('Resolve', 'default', 'check-circle-2', 'data-action="resolve" data-id="' . e((string) ($r['id'] ?? '')) . '" data-case="' . e($cid) . '"')
                . $timeline;
        }
        return $timeline;
    }
    return '';
};

$rowsHtml = '';
$pageRows = array_slice($reports, 0, $PAGE_SIZE);
foreach ($pageRows as $r) {
    $c = $caseByReport[(string) ($r['id'] ?? '')] ?? null;
    $resolved = ($c !== null && (string) ($c['status'] ?? '') === 'resolved');
    $rowCls = $resolved ? 'row--resolved' : '';
    $rescuerName = '—';
    if ($c !== null && !empty($c['assigned_rescuer_id'])) {
        $ru = $rescuerById[(string) $c['assigned_rescuer_id']] ?? null;
        $rescuerName = ($ru !== null && !empty($ru['full_name'])) ? (string) $ru['full_name'] : 'Assigned';
    }
    $rowsHtml .= '
    <tr data-id="' . e((string) ($r['id'] ?? '')) . '" class="' . $rowCls . '">
      <td class="table-cell table-cell--mono table-cell--strong">' . e(short_id($r['id'] ?? null)) . '</td>
      <td class="table-cell">' . e(($r['address_text'] ?? null) ?: '—') . '</td>
      <td class="table-cell table-cell--mono table-cell--muted">' . e(short_id($r['resident_id'] ?? null)) . '</td>
      <td class="table-cell"><span class="stamp stamp--sm ' . e($rptStampCls((string) ($r['status'] ?? null))) . '">' . e(title_case($r['status'] ?? null)) . '</span></td>
      <td class="table-cell">' . ($c !== null ? '<span class="stamp stamp--sm ' . e($rptStampCls((string) ($c['status'] ?? null))) . '">' . e(title_case($c['status'] ?? null)) . '</span>' : '—') . '</td>
      <td class="table-cell">' . e($rescuerName) . '</td>
      <td class="table-cell table-cell--mono table-cell--muted">' . e(time_ago($r['created_at'] ?? null)) . '</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">' . $actionLinksFor($r) . '</span>
      </td>
    </tr>';
}

if ($reports === []) {
    $tableInner = '<div class="queue-empty"><div class="empty-state"><i data-lucide="file-text"></i><span>No reports match.</span></div></div>';
} else {
    $pagination = count($reports) > $PAGE_SIZE ? '<div class="queue-pagination">' . pagination_bar(count($reports), $PAGE_SIZE, 1) . '</div>' : '';
    $tableInner = '
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Case</th><th>Barangay</th><th>Reporter</th><th>Status</th><th>Case status</th><th>Rescuer</th><th>Submitted</th><th>Action</th>
          </tr>
        </thead>
        <tbody>' . $rowsHtml . '</tbody>
      </table>
    </div>
    ' . $pagination;
}

$exportCsvButton = (static function () use ($rptBtnBase, $rptBtnVariants): string {
    $cls = trim($rptBtnBase . ' ' . $rptBtnVariants['outline'] . ' h-8 px-4');
    return '<button type="button" class="' . e($cls) . '" ><i data-lucide="download" class="icon"></i><span>Export CSV</span></button>';
})();

$filterButtons = static function (array $defs, string $activeKey, array $chipCounts): string {
    $out = '';
    foreach ($defs as $f) {
        $isActive = $activeKey === $f['key'];
        $out .= '<button data-filter="' . e($f['key']) . '" class="q-btn' . ($isActive ? ' is-active' : '') . '">' . e($f['label']) . ' &middot; ' . e($chipCounts[$f['key']]) . '</button>';
    }
    return $out;
};
$chipCounts = [
    'all' => $counts['all'],
    'pending_verification' => $counts['pending'],
    'verified' => $counts['verified'],
    'dismissed' => $counts['dismissed'],
];
$filterDefs = [
    ['key' => 'all', 'label' => 'All'],
    ['key' => 'pending_verification', 'label' => 'Pending verification'],
    ['key' => 'verified', 'label' => 'Verified'],
    ['key' => 'dismissed', 'label' => 'Dismissed'],
];

$sortSelect = select_control('report-sort', [
    ['value' => 'assigned', 'label' => 'Assigned'],
    ['value' => 'verified', 'label' => 'Verified'],
], 'assigned', 'Sort', '', '', 'report-sort-control');

$children = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Rescue Management</span>
      <h1 class="page-title">Reports</h1>
      <p class="page-sub">Verify reports, assign rescuers, and track the full case workflow.</p>
    </div>
    <div class="page-head-actions">
      ' . $exportCsvButton . '
    </div>
  </div>'
    . '<div id="report-kpis" class="kpi-grid">' . $kpiTiles . '</div>'
    . '
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="map-pin"></i>
        <h2 class="panel-title">All reports</h2>
      </div>
    </div>
    <div id="report-filters">'
        . '
  <div class="report-toolbar">
    <div class="q-tabs" id="report-tabs">
      ' . $filterButtons($filterDefs, 'all', $chipCounts) . '
    </div>
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="report-search" type="text" placeholder="Search case #, barangay, description…" value="">
    </div>
    <div class="report-sort">
      <label for="report-sort" class="report-sort-label">Sort</label>
      ' . $sortSelect . '
    </div>
  </div>'
        . '</div>
    <div id="report-table" class="panel-body">' . $tableInner . '</div>
  </div>';

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
$adminChildren = $children;

ob_start();
require __DIR__ . '/../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Reports';
$pageDescription = 'FurEscue admin reports — verify, dismiss, assign rescuers and track case workflow for City of Mati.';
$pageCss = [
    '/admin/css/admin.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require __DIR__ . '/../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script type="module" src="js/reports.js"></script>
  </body>
</html>
