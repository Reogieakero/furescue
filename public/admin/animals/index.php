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

// Mirrors AnimalController::index (GET /api/v1/animals?per_page=100 → page 1)
// serialization exactly: raw rows, created_at DESC, deleted excluded.
$stmt = $pdo->prepare(
    "SELECT id,name,species,breed_type,sex,age_estimate,birth_date,color_markings,photo_urls,model_3d_url,photo_360_set,adoption_status,source,created_at
     FROM animals WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 100 OFFSET 0"
);
$stmt->execute();
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

// Mirrors HealthController::records → fetchMedicalAnimalIds():
// set of animalIds having any row in animal_medical_records.
$medStmt = $pdo->prepare('SELECT DISTINCT animal_id FROM animal_medical_records');
$medStmt->execute();
$medSet = array_flip($medStmt->fetchAll(\PDO::FETCH_COLUMN));

const ANIMAL_STATUS_LABELS = [
    'not_listed' => 'Not listed',
    'available' => 'Available',
    'pending' => 'Pending',
    'adopted' => 'Adopted',
];

const ANIMAL_STATUS_TONES = [
    'Available' => 'stamp--accent',
    'Pending' => 'stamp--muted',
    'Adopted' => 'stamp--jungle',
    'Not listed' => 'stamp--muted',
];

$firstPhoto = static function (mixed $urls): ?string {
    if (is_array($urls)) {
        return $urls !== [] ? (string) $urls[0] : null;
    }
    if (is_string($urls) && $urls !== '') {
        $dec = json_decode($urls, true);
        if (is_array($dec) && $dec !== []) {
            return (string) $dec[0];
        }
        return null;
    }
    return null;
};

$normalize = static function (array $r) use ($firstPhoto, $medSet): array {
    // Mirrors state.js normalize() 1:1.
    $speciesLabel = strtolower(((string) ($r['species'] ?? '')) ?: 'dog') === 'cat' ? 'Cat' : 'Dog';
    $breedRaw = strtolower((string) ($r['breed_type'] ?? ''));
    $breedLabel = $breedRaw !== ''
        ? mb_strtoupper(mb_substr($breedRaw, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($breedRaw, 1, null, 'UTF-8')
        : '—';
    $sexLabel = ($r['sex'] ?? null) === 'female' ? 'F' : (($r['sex'] ?? null) === 'male' ? 'M' : '—');
    $statusLabel = ANIMAL_STATUS_LABELS[$r['adoption_status']] ?? 'Not listed';
    $location = !empty($r['color_markings'])
        ? (string) $r['color_markings']
        : (($r['source'] ?? null) === 'resident_listing'
            ? 'Resident listing'
            : (!empty($r['source']) ? 'Rescued case' : '—'));
    return [
        'id' => (string) $r['id'],
        'name' => ((string) ($r['name'] ?? '')) ?: 'Unnamed',
        'species' => $speciesLabel,
        'breed' => $breedLabel,
        'age' => ((string) ($r['age_estimate'] ?? '')) ?: '—',
        'sex' => $sexLabel,
        'status' => $statusLabel,
        'barangay' => $location,
        'intake' => substr((string) ($r['created_at'] ?? ''), 0, 10),
        'photo' => $firstPhoto($r['photo_urls'] ?? null),
        'hasMedical' => isset($medSet[(string) $r['id']]),
    ];
};

$animals = array_map($normalize, $rows);

$statusCount = static function (string $s) use ($animals): int {
    return count(array_filter($animals, static fn(array $a) => $a['status'] === $s));
};
$counts = [
    'all' => count($animals),
    'Available' => $statusCount('Available'),
    'Pending' => $statusCount('Pending'),
    'Adopted' => $statusCount('Adopted'),
    'Not listed' => $statusCount('Not listed'),
];

$noMedical = count(array_filter($animals, static fn(array $a) => empty($a['hasMedical'])));
$animalKpiData = [
    ['icon' => 'paw-print', 'value' => $counts['all'], 'label' => 'Total', 'tone' => 'jungle', 'filter' => 'all', 'trend' => null, 'desc' => 'Every animal currently in the shelter system.'],
    ['icon' => 'check-circle-2', 'value' => $counts['Available'], 'label' => 'Available', 'tone' => 'sky', 'filter' => 'Available', 'trend' => null, 'desc' => 'Animals listed and ready for adoption.'],
    ['icon' => 'hourglass', 'value' => $counts['Pending'], 'label' => 'Pending', 'tone' => 'amber', 'filter' => 'Pending', 'trend' => null, 'desc' => 'In-care animals on hold pending adoption or review.'],
    ['icon' => 'heart', 'value' => $counts['Adopted'], 'label' => 'Adopted', 'tone' => 'ink', 'filter' => 'Adopted', 'trend' => null, 'desc' => 'Animals that have already been adopted.'],
    ['icon' => 'alert-triangle', 'value' => $noMedical, 'label' => 'No medical records', 'tone' => 'coral', 'filter' => null, 'trend' => $noMedical ? ['text' => 'Needs records', 'tone' => 'down'] : null, 'desc' => 'Animals with no medical file on record.'],
];
$kpiTiles = '';
foreach ($animalKpiData as $k) {
    $label = (string) $k['label'];
    $value = (string) $k['value'];
    $aria = $label . ': ' . $value;
    if (!empty($k['desc'])) {
        $aria .= '. ' . (string) $k['desc'];
    }
    $title = !empty($k['desc']) ? ' title="' . e((string) $k['desc']) . '"' : '';
    $trend = '';
    if (!empty($k['trend']['text'])) {
        $trend = '<p class="kpi-card__trend kpi-card__trend--' . e((string) ($k['trend']['tone'] ?? 'neutral')) . '">' . e((string) $k['trend']['text']) . '</p>';
    }
    $filter = $k['filter'] ?? null;
    $tag = $filter ? 'button' : 'article';
    $extraClass = $filter ? ' kpi-card--interactive' : '';
    $typeAttr = $filter ? ' type="button"' : '';
    $filterAttr = $filter ? ' data-filter="' . e((string) $filter) . '"' : '';
    $kpiTiles .= '
  <' . $tag . ' class="kpi-card' . $extraClass . '"' . $typeAttr . $filterAttr . ' aria-label="' . e($aria) . '"' . $title . '>
    <div class="kpi-card__icon kpi-card__icon--' . e((string) $k['tone']) . '" aria-hidden="true"><i data-lucide="' . e((string) $k['icon']) . '"></i></div>
    <div class="kpi-card__body">
      <p class="kpi-card__label">' . e($label) . '</p>
      <p class="kpi-card__value">' . e($value) . '</p>
      ' . $trend . '
    </div>
  </' . $tag . '>';
}
$kpiGrid = '<div id="animal-kpis" class="kpi-grid">' . $kpiTiles . '</div>';

$animalFilters = [
    ['key' => 'all', 'label' => 'All'],
    ['key' => 'Available', 'label' => 'Available'],
    ['key' => 'Pending', 'label' => 'Pending'],
    ['key' => 'Adopted', 'label' => 'Adopted'],
    ['key' => 'Not listed', 'label' => 'Not listed'],
];
$filterTabsHtml = '';
foreach ($animalFilters as $f) {
    $activeCls = $f['key'] === 'all' ? ' is-active' : '';
    $filterTabsHtml .= '<button data-filter="' . e($f['key']) . '" class="q-btn' . $activeCls . '">' . e($f['label']) . ' &middot; ' . e($counts[$f['key']]) . '</button>';
}

$cardsHtml = '';
foreach ($animals as $a) {
    $tone = ANIMAL_STATUS_TONES[$a['status']] ?? 'stamp--muted';
    $initial = mb_strtoupper(mb_substr($a['name'] !== '' ? $a['name'] : '?', 0, 1, 'UTF-8'), 'UTF-8');
    $thumbInner = $a['photo'] !== null
        ? '<img src="' . e($a['photo']) . '" alt="' . e($a['name']) . '" class="animal-thumb-img">'
        : '<span class="animal-thumb-initial">' . e($initial) . '</span>'
            . '<i data-lucide="' . e(strtolower($a['species']) === 'cat' ? 'cat' : 'paw-print') . '" class="animal-thumb-icon"></i>';
    $ribbon = $a['hasMedical']
        ? '<span class="animal-card-ribbon animal-card-ribbon--green">Medical</span>'
        : '<span class="animal-card-ribbon animal-card-ribbon--red">No records</span>';
    $cardsHtml .= '
    <button type="button" class="animal-card" data-animal="' . e($a['id']) . '">
      <div class="animal-thumb animal-thumb--' . e(strtolower($a['species'])) . '">
        ' . $thumbInner . '
        ' . $ribbon . '
      </div>
      <div class="animal-card-body">
        <div class="animal-card-top">
          <span class="animal-card-name">' . e($a['name']) . '</span>
          <span class="stamp stamp--sm ' . e($tone) . '">' . e($a['status']) . '</span>
        </div>
      </div>
    </button>';
}
if ($cardsHtml === '') {
    $cardsHtml = '<div class="animal-empty empty-state"><i data-lucide="paw-print"></i><span>No animals match your filters.</span></div>';
}

$gridPanel = '
  <div class="panel animal-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="paw-print"></i>
        <h2 class="panel-title">Animals <span class="animal-count" id="animal-total-badge">' . e(count($animals)) . '</span></h2>
      </div>
    </div>
    <div class="report-toolbar animal-toolbar">
      <div id="animal-filter-tabs" class="q-tabs">' . $filterTabsHtml . '</div>
      <div class="report-search animal-search">
        <i data-lucide="search"></i>
        <input id="animal-search" type="text" placeholder="Search name, species, breed, ID…" value="">
      </div>
    </div>
    <div class="panel-body">
      <div id="animal-grid" class="animal-grid">' . $cardsHtml . '</div>
      <div id="animal-selected-store" hidden></div>
    </div>
  </div>';

$detailEmpty = '
    <div class="panel panel--padded animal-detail animal-detail--empty">
      <div class="rescuer-detail-empty">
        <i data-lucide="mouse-pointer-click"></i>
        <p>Select an animal card to view its full profile.</p>
      </div>
    </div>';

$pageHead = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--jungle">Animal Management</span>
      <h1 class="page-title">Animals</h1>
      <p class="page-sub">Browse every animal in the system, add new rescues, and review their profiles.</p>
    </div>
    <div class="page-head-actions">
      ' . button_html('Add animal', 'default', icon: 'plus', attrs: 'data-act="open-add"') . '
      ' . button_html('Export CSV', 'outline', icon: 'download', attrs: 'data-export="csv"') . '
    </div>
  </div>';

$children = '<div class="animals-list">'
    . $pageHead
    . $kpiGrid
    . '<div class="animal-split">'
    . '<div class="animal-grid-col">' . $gridPanel . '</div>'
    . '<div id="animal-side" class="animal-side-col">'
    . '<div class="animal-side"><div id="animal-detail">' . $detailEmpty . '</div></div>'
    . '</div>'
    . '</div>'
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
    'animals' => $animals,
    'query' => '',
    'filter' => 'all',
    'selectedId' => null,
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
$activeNav = 'animals';
$navBadges = [];
$adminChildren = $children;
ob_start();
require __DIR__ . '/../../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Animals';
$pageDescription = 'FurEscue admin animals — browse, add, and manage every animal in the City of Mati rescue system.';
$pageCss = ['/admin/css/admin.css', '/admin/animals/css/animals-list.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700;800&display=swap';
require __DIR__ . '/../../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="/admin/animals/js/animals.js"></script>
  </body>
</html>
