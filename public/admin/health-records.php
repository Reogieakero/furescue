<?php

declare(strict_types=1);

use App\Database;

require __DIR__ . '/../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../includes/guard.php';

require __DIR__ . '/includes/ui-helpers.php';

$pdo = Database::connect();

// ---------------------------------------------------------------------------
// Data builders — mirror src/Controllers/HealthController.php serialization 1:1
// ---------------------------------------------------------------------------

// HealthController::records()
$stmt = $pdo->prepare(
    "SELECT
        a.id,
        a.name,
        a.species,
        a.breed_type,
        a.sex,
        a.age_estimate,
        a.birth_date,
        a.barangay,
        a.updated_at AS animal_updated_at,
        am.vaccination_status,
        am.vaccination_details,
        am.last_checkup_date,
        am.next_checkup_due,
        am.vaccination_expiry,
        am.condition,
        am.treatment_stage,
        am.weight_kg,
        am.temperature_c,
        am.vet_name,
        am.medical_history_notes,
        am.updated_at AS medical_updated_at,
        CASE WHEN am.animal_id IS NOT NULL THEN 1 ELSE 0 END AS has_medical_record,
        fs.health_status,
        v.heart_rate_bpm AS last_heart_rate
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
    LEFT JOIN (
        SELECT v1.animal_id, v1.heart_rate_bpm
        FROM vitals_log v1
        INNER JOIN (
            SELECT animal_id, MAX(recorded_at) AS mx
            FROM vitals_log
            GROUP BY animal_id
        ) v2 ON v2.animal_id = v1.animal_id AND v2.mx = v1.recorded_at
    ) v ON v.animal_id = a.id
    ORDER BY a.created_at DESC"
);
$stmt->execute();
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$records = array_map(static function (array $r): array {
    $healthStatus = $r['health_status'] ?? 'healthy';
    $condition = $r['condition'];
    if ($condition === null) {
        $condition = $healthStatus === 'not_healthy' ? 'Unknown' : 'Healthy';
    }
    $treatmentStage = $r['treatment_stage'] ?? 'none';

    $vaccinationDetails = $r['vaccination_details'];
    if (is_string($vaccinationDetails) && $vaccinationDetails !== '') {
        $decoded = json_decode($vaccinationDetails, true);
        $vaccinationDetails = is_array($decoded) ? $decoded : [];
    } elseif ($vaccinationDetails === null) {
        $vaccinationDetails = [];
    }

    return [
        'id' => $r['id'],
        'animalId' => $r['id'],
        'animalName' => $r['name'] ?? 'Unnamed',
        'species' => $r['species'],
        'breedType' => $r['breed_type'],
        'sex' => $r['sex'],
        'ageEstimate' => $r['age_estimate'],
        'barangay' => $r['barangay'],
        'vaccinationStatus' => $r['vaccination_status'] ?? 'none',
        'vaccinationDetails' => $vaccinationDetails,
        'vaccinationExpiry' => $r['vaccination_expiry'],
        'lastCheckupDate' => $r['last_checkup_date'],
        'nextCheckupDue' => $r['next_checkup_due'],
        'healthStatus' => $healthStatus,
        'condition' => $condition,
        'treatmentStage' => $treatmentStage,
        'heartRateBpm' => $r['last_heart_rate'] !== null ? (int) $r['last_heart_rate'] : null,
        'weightKg' => $r['weight_kg'] !== null ? (float) $r['weight_kg'] : null,
        'temperatureC' => $r['temperature_c'] !== null ? (float) $r['temperature_c'] : null,
        'vetName' => $r['vet_name'],
        'notes' => $r['medical_history_notes'],
        'hasMedicalRecord' => !empty($r['has_medical_record']),
        'updatedAt' => $r['medical_updated_at'] ?? $r['animal_updated_at'],
    ];
}, $rows);

// HealthController::activity() — 400 daily buckets
$days = 400;
$map = [];
$todayTs = new \DateTime();
for ($i = $days - 1; $i >= 0; $i--) {
    $d = (clone $todayTs)->modify("-{$i} days")->format('Y-m-d');
    $map[$d] = ['date' => $d, 'checkups' => 0, 'treatments' => 0, 'vaccinations' => 0];
}
$hrFillActivity = static function (array &$map, string $sql, string $key) use ($pdo): void {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
        $day = $row['day'];
        if (isset($map[$day])) {
            $map[$day][$key] += (int) $row['c'];
        }
    }
};
$hrFillActivity($map,
    "SELECT DATE(last_checkup_date) AS day, COUNT(*) AS c
     FROM animal_medical_records WHERE last_checkup_date IS NOT NULL
     GROUP BY day",
    'checkups');
$hrFillActivity($map,
    "SELECT DATE(logged_at) AS day, COUNT(*) AS c
     FROM animal_field_status WHERE health_status = 'not_healthy'
     GROUP BY day",
    'treatments');
$hrFillActivity($map,
    "SELECT DATE(last_checkup_date) AS day, COUNT(*) AS c
     FROM animal_medical_records WHERE vaccination_status <> 'none' AND last_checkup_date IS NOT NULL
     GROUP BY day",
    'vaccinations');

$daily = array_values($map);

// ---------------------------------------------------------------------------
// JS-faithful derivations for the DEFAULT view:
// filter=all, query='', sort=newest, range=30d, species=all, page=1, queueExpanded=false
// (mirrors public/admin/js/pages/health-records/{state.js,components/*})
// ---------------------------------------------------------------------------

// components/util.js daysUntil(): new Date(null) === epoch (valid!), new Date('') is NaN -> 0.
$hrDaysUntil = static function (?string $value): int {
    if ($value === null) {
        $ts = 0; // new Date(null) -> epoch
    } elseif ($value === '') {
        return 0; // new Date('') -> NaN -> 0
    } else {
        $parsed = strtotime((string) $value);
        if ($parsed === false) {
            return 0;
        }
        $ts = mktime(0, 0, 0, (int) date('n', $parsed), (int) date('j', $parsed), (int) date('Y', $parsed));
    }
    $today = mktime(0, 0, 0);
    return (int) round(($ts - $today) / 86400);
};

// kpis.js overdue rule: new Date(r.nextCheckupDue).getTime() < Date.now().
// new Date(null) === epoch (valid!) -> always overdue; '' / unparsable -> NaN -> not overdue.
$hrOverdueNow = static function (?string $value): bool {
    if ($value === null) {
        return true;
    }
    if ($value === '') {
        return false;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
        $p = date_parse_from_format('Y-m-d', (string) $value);
        if ($p === false || $p['error_count'] > 0) {
            return false;
        }
        $ts = gmmktime(0, 0, 0, (int) $p['month'], (int) $p['day'], (int) $p['year']); // ISO dates parse as UTC in JS
    } else {
        $ts = strtotime((string) $value);
        if ($ts === false) {
            return false;
        }
    }
    return $ts * 1000 < (int) round(microtime(true) * 1000);
};

$hrCap = static fn(?string $s): string => ($s !== null && $s !== '')
    ? mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, null, 'UTF-8')
    : (string) $s;

const HR_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// components/util.js fmtDate(): "Mon D, YY" | MM/DD | em dash fallback.
$hrFmtDate = static function (?string $value, string $style = 'short'): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $ts = strtotime((string) $value);
    if ($ts === false) {
        return '—';
    }
    if ($style === 'short') {
        return HR_MONTHS[(int) date('n', $ts) - 1] . ' ' . date('j', $ts) . ', ' . substr((string) ((int) date('Y', $ts)), -2);
    }
    // mono
    return str_pad(date('n', $ts), 2, '0', STR_PAD_LEFT) . '/' . str_pad(date('j', $ts), 2, '0', STR_PAD_LEFT);
};

// ui/badge.js final tailwind-merge-resolved strings.
const HR_BADGE_BASE = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2';
$hrBadgeVariant = [
    'success' => 'border-transparent bg-primary/10 text-primary',
    'accent' => 'border-transparent bg-accent text-accent-foreground',
    'destructive' => 'border-transparent bg-destructive text-destructive-foreground',
];
$hrBadge = static function (string $text, string $variant) use ($hrBadgeVariant): string {
    return '<span class="' . e(HR_BADGE_BASE . ' ' . $hrBadgeVariant[$variant]) . '">' . e($text) . '</span>';
};

// dashboard/helpers.js shortId()
$hrShortId = static fn(mixed $id): string => !$id ? '—' : '#' . strtoupper(substr(str_replace('-', '', (string) $id), 0, 4));

// ---- visibleRecords() under the default view == all records ---------------
$visible = $records;

// ---- KPI strip (kpis.js buildKpis over default filters) --------------------
$totalCount = count($visible);
$completeCount = count(array_filter($visible, static fn($r) => $r['vaccinationStatus'] === 'complete'));
$partialCount = count(array_filter($visible, static fn($r) => $r['vaccinationStatus'] === 'partial'));
$kpiOverdue = count(array_filter($visible, static fn($r) => $hrOverdueNow($r['nextCheckupDue'])));
$underCount = count(array_filter($visible, static fn($r) => $r['healthStatus'] === 'not_healthy'));
$pct = $totalCount ? js_round(($completeCount / $totalCount) * 100) : 0;
$hrList = array_filter($visible, static fn($r) => is_int($r['heartRateBpm']) || is_float($r['heartRateBpm']));
$avgHeartRate = count($hrList)
    ? js_round(array_sum(array_map(static fn($r) => (float) $r['heartRateBpm'], $hrList)) / count($hrList))
    : 0;

$hrKpiData = [
    ['icon' => 'clipboard-list', 'value' => (string) $totalCount, 'label' => 'Records', 'desc' => 'Filtered animal health records in view.', 'note' => null, 'dark' => false],
    ['icon' => 'shield-check', 'value' => (string) $completeCount, 'label' => 'Fully vaccinated', 'desc' => 'Complete vaccination coverage within the current filter.', 'note' => ['text' => '+' . $pct . '%', 'cls' => 'kpi-note--accent'], 'dark' => false],
    ['icon' => 'syringe', 'value' => (string) $partialCount, 'label' => 'Partially vaccinated', 'desc' => 'Animals with an incomplete vaccination course.', 'note' => null, 'dark' => false],
    ['icon' => 'alert-triangle', 'value' => (string) $kpiOverdue, 'label' => 'Overdue checkups', 'desc' => 'Checkups whose due date has passed.', 'note' => $kpiOverdue ? ['text' => 'Action', 'cls' => 'kpi-note--coral'] : null, 'dark' => false],
    ['icon' => 'stethoscope', 'value' => (string) $underCount, 'label' => 'Under treatment', 'desc' => 'Animals flagged not healthy and being monitored.', 'note' => null, 'dark' => true],
    ['icon' => 'heart-pulse', 'value' => (string) $avgHeartRate, 'label' => 'Avg heart rate', 'desc' => 'Mean bpm across the filtered records.', 'note' => null, 'dark' => false],
];
$hrKpis = '';
foreach ($hrKpiData as $k) {
    $note = '';
    if (!empty($k['note'])) {
        $note = '<span class="kpi-note ' . e($k['note']['cls']) . '">' . e($k['note']['text']) . '</span>';
    }
    $tileCls = 'kpi-tile' . (!empty($k['dark']) ? ' kpi-tile--dark' : '');
    $hrKpis .= "
  <div class=\"{$tileCls}\">
    <div class=\"kpi-top\">
      <div class=\"kpi-icon\"><i data-lucide=\"{$k['icon']}\"></i></div>
      {$note}
    </div>
    <div class=\"kpi-value\">" . e($k['value']) . "</div>
    <div class=\"kpi-label\">" . e($k['label']) . "</div>
    <div class=\"kpi-desc\">" . e($k['desc']) . '</div>
  </div>';
}
$hrKpisHtml = "<div class=\"kpi-grid\">{$hrKpis}</div>";

// ---- recordCounts() over the FULL dataset (filter tab badges) --------------
$countsAll = count($records);
$countsComplete = count(array_filter($records, static fn($r) => $r['vaccinationStatus'] === 'complete'));
$countsPartial = count(array_filter($records, static fn($r) => $r['vaccinationStatus'] === 'partial'));
$countsNone = count(array_filter($records, static fn($r) => $r['vaccinationStatus'] === 'none'));
$countsOverdue = count(array_filter($records, static fn($r) => $hrDaysUntil($r['nextCheckupDue']) < 0));
$countsTreatment = count(array_filter($records, static fn($r) => $r['healthStatus'] === 'not_healthy'));

$hrFilters = [
    ['key' => 'all', 'label' => 'All', 'count' => $countsAll],
    ['key' => 'complete', 'label' => 'Complete', 'count' => $countsComplete],
    ['key' => 'partial', 'label' => 'Partial', 'count' => $countsPartial],
    ['key' => 'none', 'label' => 'Not vaccinated', 'count' => $countsNone],
    ['key' => 'overdue', 'label' => 'Overdue', 'count' => $countsOverdue],
    ['key' => 'under_treatment', 'label' => 'Under treatment', 'count' => $countsTreatment],
];
$hrTabs = '';
foreach ($hrFilters as $f) {
    $activeCls = $f['key'] === 'all' ? ' is-active' : '';
    $hrTabs .= '<button data-filter="' . e($f['key']) . '" class="q-btn' . $activeCls . '">' . e($f['label']) . ' &middot; ' . e((string) $f['count']) . '</button>';
}

// ---- species vaccination breakdowns (charts.js cards) ----------------------
$hrSpeciesBreakdown = static function (string $species) use ($records): array {
    $list = array_values(array_filter($records, static fn($r) => $r['species'] === $species));
    return [
        'total' => count($list),
        'complete' => count(array_filter($list, static fn($r) => $r['vaccinationStatus'] === 'complete')),
        'partial' => count(array_filter($list, static fn($r) => $r['vaccinationStatus'] === 'partial')),
        'none' => count(array_filter($list, static fn($r) => $r['vaccinationStatus'] === 'none')),
    ];
};
$dogB = $hrSpeciesBreakdown('dog');
$catB = $hrSpeciesBreakdown('cat');

$hrVaxLegend = static function (array $b): string {
    $segs = [
        ['label' => 'Complete', 'val' => $b['complete'], 'cls' => 'status-seg--complete'],
        ['label' => 'Partial', 'val' => $b['partial'], 'cls' => 'status-seg--partial'],
        ['label' => 'Not vaccinated', 'val' => $b['none'], 'cls' => 'status-seg--none'],
    ];
    $out = '';
    foreach ($segs as $s) {
        $out .= '<span class="status-legend-item"><span class="status-dot ' . e($s['cls']) . '"></span>' . e($s['label']) . ' &middot; ' . e((string) $s['val']) . '</span>';
    }
    return $out;
};
$hrVaxGroup = static function (string $label, array $items): string {
    $lis = '';
    foreach ($items as $i) {
        $lis .= '<li>' . e($i) . '</li>';
    }
    return '
    <div class="hr-vax-group">
      <span class="hr-vax-group-label">' . e($label) . '</span>
      <ul class="hr-vax-items">' . $lis . '</ul>
    </div>';
};
$hrVaxList = static function (array $core, array $nonCore) use ($hrVaxGroup): string {
    return '<div class="hr-vax-list">' . $hrVaxGroup('Core', $core) . $hrVaxGroup('Non-core', $nonCore) . '</div>';
};

$hrDonutCard = static function (string $species, string $title, string $icon, string $canvasId, array $b, string $listHtml) use ($hrVaxLegend): string {
    $donutLabel = $species === 'dog' ? 'Dogs' : 'Cats';
    return "
  <div class=\"panel panel--padded\">
    <div class=\"panel-title-wrap\"><i data-lucide=\"{$icon}\"></i><h2 class=\"panel-title panel-title--sm\">{$title}</h2></div>
    <div class=\"donut-wrap\">
      <div class=\"donut\">
        <canvas id=\"{$canvasId}\"></canvas>
        <div class=\"donut-center\"><span class=\"donut-total\">" . e((string) $b['total']) . "</span><span class=\"donut-label\">{$donutLabel}</span></div>
      </div>
      <div class=\"status-legend\">" . $hrVaxLegend($b) . '</div>
    </div>
    ' . $listHtml . '
  </div>';
};
$dogCard = $hrDonutCard('dog', 'Dog vaccinations', 'dog', 'hr-donut-dog', $dogB, $hrVaxList(['DHPP / DAPP', 'Rabies', 'Leptospirosis'], ['Bordetella', 'Canine Influenza', 'Lyme']));
$catCard = $hrDonutCard('cat', 'Cat vaccinations', 'cat', 'hr-donut-cat', $catB, $hrVaxList(['FVRCP', 'Rabies', 'FeLV (Feline Leukemia Virus)'], ['Chlamydia felis', 'Bordetella']));

// ---- topConditions() top 6 over the filtered set ---------------------------
$condCounts = [];
foreach ($visible as $r) {
    $c = (string) $r['condition'];
    $condCounts[$c] = ($condCounts[$c] ?? 0) + 1;
}
// Object.entries() equivalent: insertion-ordered [condition, count] pairs
$condEntries = array_map(null, array_keys($condCounts), array_values($condCounts));
usort($condEntries, static fn(array $a, array $b): int => $b[1] <=> $a[1]);
$condEntries = array_slice($condEntries, 0, 6);
$condMax = $condEntries ? $condEntries[0][1] : 1;

const HR_COND_COLORS = [
    'Healthy' => 'hsl(152, 64%, 42%)',
    'Mange' => 'hsl(28, 90%, 55%)',
    'Malnutrition' => 'hsl(40, 92%, 50%)',
    'Fracture' => 'hsl(0, 72%, 51%)',
    'Parvovirus' => 'hsl(280, 60%, 55%)',
    'Tick fever' => 'hsl(199, 74%, 53%)',
    'Respiratory infection' => 'hsl(211, 71%, 48%)',
    'Wound care' => 'hsl(14, 78%, 55%)',
];

if ($condEntries) {
    $condRows = '';
    foreach ($condEntries as [$label, $val]) {
        $pctBar = $condMax ? js_round(($val / $condMax) * 100) : 0;
        $color = HR_COND_COLORS[$label] ?? 'hsl(199, 74%, 53%)';
        $condRows .= '
        <div class="hr-cond-row">
          <span class="hr-cond-label">' . e($label) . '</span>
          <span class="hr-cond-val">' . e((string) $val) . '</span>
          <span class="hr-cond-track"><span class="hr-cond-bar" style="width:' . $pctBar . '%;background:' . e($color) . '"></span></span>
        </div>';
    }
} else {
    $condRows = '<div class="empty-state"><i data-lucide="check-circle-2"></i><span>No conditions to summarise.</span></div>';
}
$conditionsPanel = '
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="stethoscope"></i><h2 class="panel-title panel-title--sm">Top conditions</h2></div>
    <div class="hr-cond-list">' . $condRows . '</div>
  </div>';

// ---- Attention queue (queue.js attentionItems + panel, first 6) ------------
$attentionItems = [];
foreach ($visible as $r) {
    $base = [
        'id' => (string) $r['id'],
        'animalName' => (string) $r['animalName'],
        'barangay' => (string) $r['barangay'],
        'species' => (string) $r['species'],
        'condition' => (string) $r['condition'],
    ];
    $due = $hrDaysUntil($r['nextCheckupDue']);
    if ($due < 0) {
        $attentionItems[] = $base + [
            'kind' => 'checkup',
            'icon' => 'stethoscope',
            'text' => 'Overdue checkup',
            'date' => (string) $r['nextCheckupDue'],
            'days' => $due,
            'tier' => $due <= -8 ? 'critical' : 'warn',
        ];
    }
    if (!empty($r['vaccinationExpiry'])) {
        $exp = $hrDaysUntil((string) $r['vaccinationExpiry']);
        if ($exp >= 0 && $exp <= 30) {
            $attentionItems[] = $base + [
                'kind' => 'vaccine',
                'icon' => 'syringe',
                'text' => 'Vaccination expiring',
                'date' => (string) $r['vaccinationExpiry'],
                'days' => $exp,
                'tier' => $exp <= 7 ? 'warn' : 'soon',
            ];
        }
    }
}
usort($attentionItems, static fn(array $a, array $b): int => $a['days'] <=> $b['days']);

$QUEUE_LIMIT = 6;
$attTotal = count($attentionItems);
$attOverdue = count(array_filter($attentionItems, static fn($i) => $i['kind'] === 'checkup'));
$attExpiring = count(array_filter($attentionItems, static fn($i) => $i['kind'] === 'vaccine'));

const HR_QUEUE_TIER = [
    'critical' => 'hr-queue-card--critical',
    'warn' => 'hr-queue-card--warn',
    'soon' => 'hr-queue-card--soon',
];
$hrDaysLabel = static function (int $d): string {
    if ($d < 0) {
        return abs($d) . 'd overdue';
    }
    if ($d === 0) {
        return 'Due today';
    }
    return $d . 'd left';
};
$hrQueueCards = '';
foreach (array_slice($attentionItems, 0, $QUEUE_LIMIT) as $it) {
    $tier = (string) $it['tier'];
    $speciesCap = $hrCap((string) $it['species']);
    $title = e((string) $it['animalName']) . ' · ' . e((string) $it['barangay']) . ' · ' . e((string) $it['text']) . ' — open in records';
    $hrQueueCards .= '
  <button type="button" class="hr-queue-card ' . e(HR_QUEUE_TIER[$tier]) . '" data-queue-card data-animal="' . e((string) $it['animalName']) . '" title="' . $title . '">
    <span class="hr-qc-head">
      <span class="hr-qc-kind"><i data-lucide="' . e((string) $it['icon']) . '"></i></span>
      <span class="stamp stamp--sm hr-qc-days hr-qc-days--' . e($tier) . '">' . e($hrDaysLabel((int) $it['days'])) . '</span>
    </span>
    <span class="hr-qc-name">' . e((string) $it['animalName']) . '</span>
    <span class="hr-qc-meta">' . e($speciesCap) . ' · ' . e((string) $it['barangay']) . '</span>
    <span class="hr-qc-reason">' . e((string) $it['text']) . '<span class="hr-qc-id">' . e($hrShortId($it['id'])) . '</span></span>
    <span class="hr-qc-foot">
      <span class="hr-qc-date"><i data-lucide="calendar"></i>' . e($hrFmtDate((string) $it['date'], 'short')) . '</span>
      <span class="hr-qc-go"><i data-lucide="chevron-right"></i></span>
    </span>
  </button>';
}
$queueBody = $attTotal
    ? $hrQueueCards
    : '<div class="empty-state hr-queue-empty"><i data-lucide="check-circle-2"></i><span>Nothing needs urgent attention.</span></div>';
$tally = $attTotal
    ? '<span class="stamp stamp--sm stamp--coral">' . e((string) $attOverdue) . ' overdue</span>
       <span class="stamp stamp--sm stamp--muted">' . e((string) $attExpiring) . ' expiring</span>'
    : '<span class="stamp stamp--sm stamp--accent">All clear</span>';

// allAttentionCount() over ALL records (nav badge)
$attentionCount = 0;
foreach ($records as $r) {
    if ($hrDaysUntil($r['nextCheckupDue']) < 0) {
        $attentionCount += 1;
    }
    if (!empty($r['vaccinationExpiry'])) {
        $exp = $hrDaysUntil((string) $r['vaccinationExpiry']);
        if ($exp >= 0 && $exp <= 30) {
            $attentionCount += 1;
        }
    }
}

$queuePanel = "
  <div class=\"panel\">
    <div class=\"panel-head\">
      <div class=\"panel-title-wrap\">
        <i data-lucide=\"bell\"></i>
        <h2 class=\"panel-title\">Attention queue</h2>
      </div>
      <div class=\"hr-queue-tally\">{$tally}</div>
    </div>
    <div class=\"hr-queue-grid\">{$queueBody}</div>
    " . ($attTotal > $QUEUE_LIMIT
        ? '<button class="hr-queue-all" data-queue-all type="button">View all ' . e((string) $attTotal) . '</button>'
        : '') . '
  </div>';

// ---- Records table page 1 (sortedRecords() default sort = newest updated) --
$hrUpdatedTs = static fn(?string $v): int => $v ? (strtotime((string) $v) ?: 0) : 0; // new Date(null) -> epoch 0
$sortedRecords = $visible;
usort($sortedRecords, static fn(array $a, array $b): int => $hrUpdatedTs($b['updatedAt']) <=> $hrUpdatedTs($a['updatedAt']));
$PAGE_SIZE = 8;
$pageOne = array_slice($sortedRecords, 0, $PAGE_SIZE);
$totalInView = count($sortedRecords);

$HR_VACC_TONE = [
    'complete' => ['label' => 'Complete', 'variant' => 'success'],
    'partial' => ['label' => 'Partial', 'variant' => 'accent'],
    'none' => ['label' => 'Not vaccinated', 'variant' => 'destructive'],
];

$tableBody = '';
foreach ($pageOne as $r) {
    $tone = $HR_VACC_TONE[$r['vaccinationStatus']] ?? $HR_VACC_TONE['none'];
    $due = $hrDaysUntil($r['nextCheckupDue']);
    $dueStamp = $due < 0 ? 'stamp--coral' : ($due <= 14 ? 'stamp--muted' : 'stamp--accent');
    $initials = mb_strtoupper(mb_substr((string) $r['animalName'], 0, 2, 'UTF-8'), 'UTF-8');
    $condVariant = $r['condition'] === 'Healthy' ? 'success' : 'destructive';
    $tableBody .= '
    <tr>
      <td class="table-cell">
        <span class="hr-cell-animal">
          <span class="hr-avatar">' . e($initials) . '</span>
          <span>
            <span class="table-cell--strong">' . e((string) $r['animalName']) . '</span><br>
            <span class="hr-id">' . e((string) $r['id']) . '</span>
          </span>
        </span>
      </td>
      <td class="table-cell hr-species">' . e($hrCap((string) $r['species'])) . ' · ' . e($hrCap((string) $r['breedType'])) . '</td>
      <td class="table-cell table-cell--center">' . $hrBadge((string) $tone['label'], (string) $tone['variant']) . '</td>
      <td class="table-cell table-cell--mono table-cell--muted table-cell--center">' . e($hrFmtDate($r['lastCheckupDate'], 'mono')) . '</td>
      <td class="table-cell table-cell--mono table-cell--muted table-cell--center"><span class="stamp stamp--sm ' . e($dueStamp) . '">' . e($hrFmtDate($r['nextCheckupDue'], 'mono')) . '</span></td>
      <td class="table-cell table-cell--center">' . $hrBadge((string) $r['condition'], (string) $condVariant) . '</td>
      <td class="table-cell table-cell--mono table-cell--muted table-cell--center">' . e($hrFmtDate($r['updatedAt'], 'mono')) . '</td>
    </tr>';
}

if ($tableBody === '') {
    $recordsTableInner = '<div class="queue-empty"><div class="empty-state"><i data-lucide="clipboard-list"></i><span>No records match the current filters.</span></div></div>';
} else {
    $pagination = $totalInView > $PAGE_SIZE
        ? '<div class="queue-pagination" id="hr-pagination">' . pagination_bar($totalInView, $PAGE_SIZE, 1) . '</div>'
        : '';
    $recordsTableInner = '
    <div class="table-wrap">
      <table class="table hr-table">
        <thead>
          <tr class="table-head">
            <th>Animal</th><th>Species / Breed</th><th class="table-cell--center">Vaccination</th><th class="table-cell--center">Last checkup</th>
            <th class="table-cell--center">Next due</th><th class="table-cell--center">Condition</th><th class="table-cell--center">Updated</th>
          </tr>
        </thead>
        <tbody>' . $tableBody . '</tbody>
      </table>
    </div>
    ' . $pagination;
}

$recordsPanel = '
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="clipboard-list"></i>
        <h2 class="panel-title">Health records</h2>
      </div>
      <span class="stamp stamp--sm stamp--accent">' . e((string) $totalInView) . ' in view</span>
    </div>
    <div class="panel-body" id="hr-records-body">' . $recordsTableInner . '</div>
  </div>';

// ui/button.js final tailwind-merge-resolved string (cva base incl. disabled:* groups
// which shared BTN_BASE lacks — same local-emission pattern as reports/rescuers units).
const HR_BTN_BASE = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50';
$hrExportCsvButton = '<button type="button" class="' . e(trim(HR_BTN_BASE . ' border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-4')) . '" ><i data-lucide="download" class="icon"></i><span>Export CSV</span></button>';

// ---- Page head + controls ---------------------------------------------------
$pageHead = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Animal Management</span>
      <h1 class="page-title">Health Records</h1>
      <p class="page-sub">Track vaccinations, checkups, conditions, and vitals across the shelter population.</p>
    </div>
    <div class="page-head-actions">
      ' . $hrExportCsvButton . '
      <button type="button" class="btn-see-animals" data-animals-open><i data-lucide="paw-print"></i><span>See animals</span></button>
    </div>
  </div>';

$controlsPanel = '
  <div class="panel panel--padded">
    <div class="report-toolbar">
      <div class="q-tabs" id="hr-tabs">' . $hrTabs . '</div>
      <div class="report-search">
        <i data-lucide="search"></i>
        <input id="hr-search" type="text" placeholder="Search animal, barangay, condition, vet, id…" value="">
      </div>
      <div class="report-sort">
        <label for="hr-range" class="report-sort-label">Range</label>
        ' . select_control('hr-range', [
            ['value' => '30d', 'label' => 'Last 30 days'],
            ['value' => '90d', 'label' => 'Last 90 days'],
            ['value' => '12mo', 'label' => 'Last 12 months'],
        ], '30d', 'Range') . '
      </div>
    </div>
  </div>';

$trendPanel = '
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="activity"></i><h2 class="panel-title panel-title--sm">Checkups &amp; treatments</h2></div>
    <div class="hr-chart"><canvas id="hr-trend-canvas"></canvas></div>
  </div>';

$stackedToggle = ''
    . '<button class="hr-toggle-btn is-active" data-species="all">All</button>'
    . '<button class="hr-toggle-btn" data-species="dog">Dogs</button>'
    . '<button class="hr-toggle-btn" data-species="cat">Cats</button>';
$stackedPanel = '
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="bar-chart-3"></i><h2 class="panel-title panel-title--sm">Health by barangay</h2></div>
    <div class="report-sort" style="margin:8px 0 12px;"><span class="hr-toggle">' . $stackedToggle . '</span></div>
    <div class="hr-chart"><canvas id="hr-stacked-canvas"></canvas></div>
  </div>';

$adminChildren = $pageHead . "\n"
    . $controlsPanel . "\n"
    . "<div id=\"hr-kpis\">{$hrKpisHtml}</div>\n"
    . "<div class=\"cols cols--vax\"><div id=\"hr-vax-dog\">{$dogCard}</div><div id=\"hr-vax-cat\">{$catCard}</div><div id=\"hr-conditions\">{$conditionsPanel}</div></div>\n"
    . "<div id=\"hr-trend\">{$trendPanel}</div>\n"
    . "<div class=\"cols cols--two hr-split-row\"><div id=\"hr-stacked\">{$stackedPanel}</div><div id=\"hr-queue\">{$queuePanel}</div></div>\n"
    . "<div id=\"hr-records\">{$recordsPanel}</div>";

// ---------------------------------------------------------------------------
// Embedded state — keys mirror pages/health-records/state.js exactly
// ---------------------------------------------------------------------------
$state = [
    'filter' => 'all',
    'query' => '',
    'sort' => 'newest',
    'range' => '30d',
    'species' => 'all',
    'page' => 1,
    'queueExpanded' => false,
    'records' => $records,
    'activity' => $daily,
];

$stmtUser = $pdo->prepare('SELECT id, full_name, email, role, profile_photo_url FROM users WHERE id = ?');
$stmtUser->execute([(string) $_SESSION['user']['id']]);
$currentUserData = $stmtUser->fetch(\PDO::FETCH_ASSOC) ?: [];

$adminUser = [
    'id' => (string) $_SESSION['user']['id'],
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($currentUserData['profile_photo_url'] ?? ''),
];
$activeNav = 'health records';
$navBadges = [
    'health' => $attentionCount,
];

ob_start();
require __DIR__ . '/../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Health Records';
$pageDescription = 'FurEscue admin health records — vaccinations, checkups, conditions, and vitals across the shelter population.';
$pageCss = ['/admin/css/admin.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
$importMapExtras = ['chart.js' => 'https://esm.sh/chart.js@4.4.4/auto'];
require __DIR__ . '/../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="js/health-records.js"></script>
  </body>
</html>
