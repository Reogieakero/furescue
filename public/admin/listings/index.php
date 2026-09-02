<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\UserRepository;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../../includes/guard.php';

require __DIR__ . '/../includes/ui-helpers.php';

$pdo = Database::connect();
$uid = (string) $_SESSION['user']['id'];

$stmt = $pdo->prepare(
    "SELECT l.*, a.name AS animal_name, u.full_name AS poster_name
     FROM adoption_listings l
     LEFT JOIN animals a ON a.id = l.animal_id
     LEFT JOIN users u ON u.id = l.posted_by
     ORDER BY l.created_at DESC
     LIMIT 100 OFFSET 0"
);
$stmt->execute();
$listings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$uniqueByAnimal = static function (array $rows): array {
    $rank = ['pending_review' => 0, 'approved' => 1, 'rejected' => 2];
    $best = [];
    foreach ($rows as $row) {
        $key = (string) ($row['animal_id'] ?? '');
        if ($key === '') {
            continue;
        }
        if (!isset($best[$key])) {
            $best[$key] = $row;
            continue;
        }
        $prev = $best[$key];
        $rNew = $rank[$row['status'] ?? ''] ?? 9;
        $rOld = $rank[$prev['status'] ?? ''] ?? 9;
        if ($rNew < $rOld) {
            $best[$key] = $row;
        } elseif ($rNew === $rOld) {
            $tNew = strtotime((string) ($row['created_at'] ?? '')) ?: 0;
            $tOld = strtotime((string) ($prev['created_at'] ?? '')) ?: 0;
            if ($tNew > $tOld) {
                $best[$key] = $row;
            }
        }
    }
    return array_values($best);
};
$listings = $uniqueByAnimal($listings);

usort($listings, static function (array $a, array $b): int {
    $rankA = (($a['status'] ?? '') === 'pending_review') ? 0 : 1;
    $rankB = (($b['status'] ?? '') === 'pending_review') ? 0 : 1;
    if ($rankA !== $rankB) {
        return $rankA <=> $rankB;
    }
    return -((int) strtotime((string) ($a['created_at'] ?? ''))) <=> -((int) strtotime((string) ($b['created_at'] ?? '')));
});

$role = (string) ($_SESSION['user']['role'] ?? '');
$state = [
    'accessToken' => (new \App\Auth\JwtService())->issueAccessToken(['id' => $uid, 'role' => $role]),
    'user' => [
        'id' => $uid,
        'full_name' => (string) ($_SESSION['user']['full_name'] ?? ''),
        'email' => (string) ($_SESSION['user']['email'] ?? ''),
        'role' => $role,
    ],
    'listings' => $listings,
    'filter' => 'all',
    'query' => '',
    'page' => 1,
    'error' => null,
];

const LISTING_STATUS_LABELS = [
    'pending_review' => 'In review',
    'approved' => 'Live',
    'rejected' => 'Rejected',
];
const LISTING_PAGE_SIZE = 15;

$listingStamp = static function (?string $status): string {
    if ($status === 'pending_review') {
        return 'stamp--coral';
    }
    if ($status === 'rejected') {
        return 'stamp--muted';
    }
    return 'stamp--accent';
};

$listingButton = static function (string $text, string $variant, string $icon, string $attrs): string {
    if ($variant === 'destructive') {
        $cls = trim(BTN_BASE . ' bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90 ' . BTN_SIZE_SM);
        $inner = '<i data-lucide="' . e($icon) . '" class="icon"></i><span>' . e($text) . '</span>';
        return '<button type="button" class="' . e($cls) . '" ' . $attrs . '>' . $inner . '</button>';
    }
    return button_html($text, $variant, 'sm', icon: $icon, attrs: $attrs);
};

$countStatus = static function (string $status) use ($listings): int {
    return count(array_filter($listings, static fn(array $row) => ($row['status'] ?? '') === $status));
};
$counts = [
    'all' => count($listings),
    'pending' => $countStatus('pending_review'),
    'live' => $countStatus('approved'),
    'rejected' => $countStatus('rejected'),
];

$kpiData = [
    ['icon' => 'home', 'value' => $counts['all'], 'label' => 'Total listings', 'tone' => 'jungle'],
    [
        'icon' => 'clock',
        'value' => $counts['pending'],
        'label' => 'In review',
        'tone' => 'coral',
        'trend' => $counts['pending'] ? 'Needs You' : '',
        'trendTone' => 'down',
    ],
    ['icon' => 'badge-check', 'value' => $counts['live'], 'label' => 'Live', 'tone' => 'sky'],
    ['icon' => 'file-x', 'value' => $counts['rejected'], 'label' => 'Rejected', 'tone' => 'ink'],
];
$kpiTiles = '';
foreach ($kpiData as $k) {
    $kpiTiles .= kpi_card_html($k);
}

$filterDefs = [
    ['key' => 'all', 'label' => 'All', 'count' => $counts['all']],
    ['key' => 'pending_review', 'label' => 'In review', 'count' => $counts['pending']],
    ['key' => 'approved', 'label' => 'Live', 'count' => $counts['live']],
    ['key' => 'rejected', 'label' => 'Rejected', 'count' => $counts['rejected']],
];
$tabButtons = '';
foreach ($filterDefs as $f) {
    $activeCls = $f['key'] === 'all' ? ' is-active' : '';
    $tabButtons .= '
        <button data-filter="' . e($f['key']) . '" class="q-btn' . $activeCls . '">' . e($f['label']) . ' &middot; ' . e($f['count']) . '</button>';
}

$pageRows = array_slice($listings, 0, LISTING_PAGE_SIZE);
$rowsHtml = '';
foreach ($pageRows as $row) {
    $status = (string) ($row['status'] ?? '');
    $name = trim((string) ($row['animal_name'] ?? '')) !== '' ? (string) $row['animal_name'] : 'Unnamed';
    $poster = trim((string) ($row['poster_name'] ?? '')) !== '' ? (string) $row['poster_name'] : 'Unknown poster';
    $label = LISTING_STATUS_LABELS[$status] ?? title_case($status);
    $actions = '';
    if ($status === 'pending_review') {
        $idAttr = 'data-id="' . e((string) ($row['id'] ?? '')) . '"';
        $actions = $listingButton('Approve', 'default', 'badge-check', 'data-action="approve" ' . $idAttr)
            . $listingButton('Reject', 'destructive', 'file-x', 'data-action="reject" ' . $idAttr);
    }
    $rowsHtml .= '
    <tr data-id="' . e((string) ($row['id'] ?? '')) . '">
      <td class="table-cell table-cell--strong">' . e($name) . '</td>
      <td class="table-cell">' . e($poster) . '</td>
      <td class="table-cell"><span class="stamp stamp--sm ' . e($listingStamp($status)) . '">' . e($label) . '</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">' . e(time_ago($row['created_at'] ?? null)) . '</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">' . $actions . '</span>
      </td>
    </tr>';
}

if ($listings === []) {
    $tableInner = '<div class="queue-empty">' . empty_state('home', 'No adoption listings yet.') . '</div>';
} else {
    $pagination = count($listings) > LISTING_PAGE_SIZE
        ? '<div class="queue-pagination">' . pagination_bar(count($listings), LISTING_PAGE_SIZE, 1) . '</div>'
        : '';
    $tableInner = '
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Animal</th><th>Poster</th><th>Status</th><th>Posted</th><th class="table-cell--right">Action</th>
          </tr>
        </thead>
        <tbody>' . $rowsHtml . '</tbody>
      </table>
    </div>
    ' . $pagination;
}

$adminChildren = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--accent">Adoption</span>
      <h1 class="page-title">Listings</h1>
      <p class="page-sub">Review community adoption posts. Approving a listing sets the animal as available.</p>
    </div>
    <div class="page-head-actions">
      ' . button_html('Export CSV', 'outline', icon: 'download', attrs: 'data-export="csv"') . '
    </div>
  </div>
  <div id="listing-kpis" class="kpi-grid">' . $kpiTiles . '</div>
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="home"></i>
        <h2 class="panel-title" id="listing-panel-title">All listings</h2>
      </div>
    </div>
    <div id="listing-filters">
      <div class="report-toolbar">
        <div class="q-tabs" id="listing-tabs">' . $tabButtons . '
        </div>
        <div class="report-search">
          <i data-lucide="search"></i>
          <input id="listing-search" type="text" placeholder="Search animal, poster…" value="">
        </div>
      </div>
    </div>
    <div id="listing-table" class="panel-body">' . $tableInner . '</div>
  </div>';

$currentUser = (new UserRepository($pdo))->find($uid);
$currentUserData = $currentUser ? $currentUser->toArray() : [];
$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($currentUserData['profile_photo_url'] ?? ''),
];
$activeNav = 'listings';
$navBadges = ['notifications' => null];

ob_start();
require __DIR__ . '/../../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Listings';
$pageDescription = 'FurEscue admin listings — review community adoption posts for City of Mati.';
$pageCss = ['/admin/css/admin.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require __DIR__ . '/../../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/listings/js/listings.js"></script>
  </body>
</html>
