<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\ReportRepository;
use App\Repositories\UserRepository;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../../includes/guard.php';

require __DIR__ . '/../includes/ui-helpers.php';

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

// State keys mirror public/admin/js/pages/rescuers/state.js exactly.
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

// rescuerCounts() replica
$activeRows = array_values(array_filter($rescuerRowsAll, static fn($r) => ($r['account_status'] ?? '') === 'active'));
$dutyOf = static fn(array $r): string => !empty($r['duty_status']) ? (string) $r['duty_status'] : 'off_duty';
$onDutyCount = count(array_filter($activeRows, static fn($r) => $dutyOf($r) === 'on_duty'));
$suspendedCount = count(array_filter($rescuerRowsAll, static fn($r) => ($r['account_status'] ?? '') === 'suspended'));
$pendingCount = count($pendingItems);
$totalCount = count($rescuerRowsAll) + $pendingCount;

// Button builder — same composition as ui-helpers button_html plus the
// destructive variant (final tailwind-merge-resolved strings; cva emits
// base → variant → size with no conflicting groups for these combos).
$rescuersButton = static function (
    string $text,
    string $variant = 'default',
    string $size = 'default',
    string $icon = '',
    string $attrs = ''
): string {
    $variantCls = match ($variant) {
        'outline' => BTN_VARIANT_OUTLINE,
        'destructive' => 'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90',
        default => BTN_VARIANT_DEFAULT,
    };
    $sizeCls = $size === 'sm' ? BTN_SIZE_SM : BTN_SIZE_DEFAULT;
    $cls = trim(BTN_BASE . ' ' . $variantCls . ' ' . $sizeCls);
    $inner = ($icon !== '' ? '<i data-lucide="' . e($icon) . '" class="icon"></i>' : '') . '<span>' . e($text) . '</span>';
    return '<button type="button" class="' . e($cls) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . $inner . '</button>';
};

// PageHead()
$pageHead = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Rescue Management</span>
      <h1 class="page-title">Rescuers</h1>
      <p class="page-sub">Manage rescuers, duty status, and applications.</p>
    </div>
    <div class="page-head-actions">
      ' . $rescuersButton('Export CSV', 'outline', 'default', 'download') . '
    </div>
  </div>';

// KpiTile() × buildKpis()
$kpiTiles = '';
$kpiData = [
    ['icon' => 'users', 'value' => $totalCount, 'label' => 'Total rescuers', 'note' => null],
    ['icon' => 'badge-check', 'value' => count($activeRows), 'label' => 'Active', 'note' => null],
    [
        'icon' => 'siren',
        'value' => $onDutyCount,
        'label' => 'On duty',
        'note' => $onDutyCount > 0 ? ['text' => 'On duty', 'cls' => 'kpi-note--accent'] : null,
    ],
    [
        'icon' => 'clock',
        'value' => $pendingCount,
        'label' => 'Pending',
        'note' => $pendingCount > 0 ? ['text' => 'Needs You', 'cls' => 'kpi-note--coral'] : null,
    ],
    ['icon' => 'slash', 'value' => $suspendedCount, 'label' => 'Suspended', 'note' => null],
];
foreach ($kpiData as $k) {
    $note = '';
    if (!empty($k['note'])) {
        $note = '<span class="kpi-note ' . e($k['note']['cls']) . '">' . e($k['note']['text']) . '</span>';
    }
    $kpiTiles .= '
  <div class="kpi-tile">
    <div class="kpi-top">
      <div class="kpi-icon"><i data-lucide="' . e($k['icon']) . '"></i></div>
      ' . $note . '
    </div>
    <div class="kpi-value">' . e($k['value']) . '</div>
    <div class="kpi-label">' . e($k['label']) . '</div>
  </div>';
}
$kpiGrid = '<div id="rescuer-kpis" class="kpi-grid">' . $kpiTiles . '</div>';

// FilterTabs() — initial state: filter=all, query=''
$filterDefs = [
    ['key' => 'all', 'label' => 'All', 'count' => $totalCount],
    ['key' => 'active', 'label' => 'Active', 'count' => count($activeRows)],
    ['key' => 'on_duty', 'label' => 'On duty', 'count' => $onDutyCount],
    ['key' => 'off_duty', 'label' => 'Off duty', 'count' => count($activeRows) - $onDutyCount],
    ['key' => 'pending', 'label' => 'Pending', 'count' => $pendingCount],
];
$tabButtons = '';
foreach ($filterDefs as $f) {
    $activeCls = $f['key'] === 'all' ? ' is-active' : '';
    $tabButtons .= '
        <button data-filter="' . e($f['key']) . '" class="q-btn' . $activeCls . '">' . e($f['label']) . ' &middot; ' . e($f['count']) . '</button>';
}
$filterTabs = '
  <div class="report-toolbar">
    <div class="q-tabs" id="rescuer-tabs">' . $tabButtons . '
    </div>
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="rescuer-search" type="text" placeholder="Search name, email, phone…" value="">
    </div>
  </div>';

// ActiveTable() — filter=all, query='', page=1 → unfiltered list, first 10 rows
const RESCUERS_PAGE_SIZE = 10;
$pagedRows = array_slice($rescuerRowsAll, 0, RESCUERS_PAGE_SIZE);

$rescuerRowHtml = static function (array $r) use ($rescuersButton, $dutyOf): string {
    $duty = $dutyOf($r);
    $isSuspended = ($r['account_status'] ?? '') === 'suspended';
    $id = e((string) ($r['id'] ?? ''));
    $dutyToggle = $isSuspended
        ? ''
        : $rescuersButton(
            $duty === 'on_duty' ? 'Set off duty' : 'Set on duty',
            'outline',
            'sm',
            'power',
            'data-action="duty" data-id="' . $id . '" data-status="' . ($duty === 'on_duty' ? 'off_duty' : 'on_duty') . '"'
        );
    $toggle = $isSuspended
        ? $rescuersButton('Activate', 'outline', 'sm', 'user-check', 'data-action="activate" data-id="' . $id . '"')
        : $rescuersButton('Suspend', 'destructive', 'sm', '', 'data-action="suspend" data-id="' . $id . '"');
    return '
    <tr data-id="' . $id . '">
      <td class="table-cell table-cell--strong">' . e(($r['full_name'] ?? null) ?: 'Unnamed') . '</td>
      <td class="table-cell">' . e(($r['email'] ?? null) ?: '—') . '</td>
      <td class="table-cell table-cell--mono">' . e(($r['phone_number'] ?? null) ?: '—') . '</td>
      <td class="table-cell"><span class="stamp stamp--sm ' . e($duty === 'on_duty' ? 'stamp--accent' : 'stamp--muted') . '">' . e($duty === 'on_duty' ? 'On duty' : 'Off duty') . '</span></td>
      <td class="table-cell">' . e(time_ago($r['created_at'] ?? null)) . '</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">
          ' . $dutyToggle . '
          ' . $toggle . '
        </span>
      </td>
    </tr>';
};

if ($pagedRows === []) {
    $tableInner = '<div class="queue-empty"><div class="empty-state"><i data-lucide="siren"></i><span>No rescuers match.</span></div></div>';
} else {
    $rowsHtml = '';
    foreach ($pagedRows as $r) {
        $rowsHtml .= $rescuerRowHtml($r);
    }
    $pagination = count($rescuerRowsAll) > RESCUERS_PAGE_SIZE
        ? '<div class="queue-pagination">' . pagination_bar(count($rescuerRowsAll), RESCUERS_PAGE_SIZE, 1) . '</div>'
        : '';
    $tableInner = '
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Rescuer</th><th>Email</th><th>Phone</th><th>Duty</th><th>Joined</th><th class="table-cell--right">Action</th>
          </tr>
        </thead>
        <tbody>' . $rowsHtml . '</tbody>
      </table>
    </div>
    ' . $pagination;
}

// RescuersPanel() — panel title is "Applications" only when filter=pending
$rescuersPanel = '
  <div class="panel rescuer-record-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="siren"></i>
        <h2 class="panel-title">Rescuers</h2>
      </div>
    </div>
    <div id="rescuer-filters">' . $filterTabs . '</div>
    <div id="rescuer-table" class="panel-body">' . $tableInner . '</div>
  </div>';

// RescuerDetail() — fresh load has no selection
$rescuerDetail = '<div id="rescuer-detail" class="panel rescuer-detail-panel"><div class="rescuer-detail-empty"><i data-lucide="user-round-search"></i><span>Select a rescuer to view their profile and past cases.</span></div></div>';

$adminChildren = $pageHead . "\n" . $kpiGrid . "\n" . '<div class="rescuer-split">' . "\n" . $rescuersPanel . "\n" . $rescuerDetail . "\n" . '</div>';

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
// JS page passes notifications:0 (suppresses the static badge unless swr cache
// has one — unknowable server-side, so paint fresh-load state) + pending badge.
$navBadges = [
    'notifications' => null,
    'rescuers' => $pendingCount,
];

ob_start();
require __DIR__ . '/../../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Rescuers';
$pageDescription = 'FurEscue admin rescuers — manage rescuers, duty status, and review applications for City of Mati.';
$pageCss = [
    '/admin/css/admin.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require __DIR__ . '/../../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/rescuers/js/rescuers.js"></script>
  </body>
</html>
