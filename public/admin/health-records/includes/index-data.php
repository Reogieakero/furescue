<?php

declare(strict_types=1);

use App\Database;

require __DIR__ . '/../../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 4))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../../../includes/guard.php';

require __DIR__ . '/../../includes/ui-helpers.php';

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
    WHERE a.deleted_at IS NULL
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

// ---- recordCounts() over the FULL dataset (filter tab badges + KPIs) -------
$countsAll = count($records);
$countsComplete = count(array_filter($records, static fn($r) => $r['vaccinationStatus'] === 'complete'));
$countsPartial = count(array_filter($records, static fn($r) => $r['vaccinationStatus'] === 'partial'));
$countsNone = count(array_filter($records, static fn($r) => $r['vaccinationStatus'] === 'none'));
$countsOverdue = count(array_filter($records, static fn($r) => $hrDaysUntil($r['nextCheckupDue']) < 0));
$countsTreatment = count(array_filter($records, static fn($r) => $r['healthStatus'] === 'not_healthy'));
$countsDueSoon = count(array_filter($records, static function ($r) use ($hrDaysUntil): bool {
    $due = $r['nextCheckupDue'] ?? null;
    if ($due === null || $due === '') {
        return false;
    }
    $d = $hrDaysUntil((string) $due);
    return $d >= 0 && $d <= 14;
}));
$pctComplete = $countsAll ? js_round(($countsComplete / $countsAll) * 100) : 0;

$hrKpiData = [
    [
        'icon' => 'alert-triangle',
        'value' => (string) $countsOverdue,
        'label' => 'Overdue',
        'tone' => 'coral',
        'filter' => 'overdue',
        'trend' => $countsOverdue ? ['text' => 'Needs attention', 'tone' => 'down'] : null,
        'desc' => 'Checkups whose due date has already passed.',
    ],
    [
        'icon' => 'calendar-clock',
        'value' => (string) $countsDueSoon,
        'label' => 'Due soon',
        'tone' => 'amber',
        'filter' => null,
        'trend' => ['text' => 'Next 14 days', 'tone' => 'neutral'],
        'desc' => 'Checkups due today or within the next 14 days.',
    ],
    [
        'icon' => 'shield-check',
        'value' => (string) $countsComplete,
        'label' => 'Current',
        'tone' => 'jungle',
        'filter' => 'complete',
        'trend' => ['text' => $pctComplete . '% of records', 'tone' => 'neutral'],
        'desc' => 'Animals with complete vaccination coverage.',
    ],
    [
        'icon' => 'clipboard-x',
        'value' => (string) $countsNone,
        'label' => 'Missing vaccines',
        'tone' => 'ink',
        'filter' => 'none',
        'trend' => null,
        'desc' => 'Animals with no vaccination on file.',
    ],
    [
        'icon' => 'stethoscope',
        'value' => (string) $countsTreatment,
        'label' => 'In treatment',
        'tone' => 'sky',
        'filter' => 'under_treatment',
        'trend' => null,
        'desc' => 'Animals flagged not healthy and being monitored.',
    ],
];

$hrFilters = [
    ['key' => 'all', 'label' => 'All', 'count' => $countsAll],
    ['key' => 'complete', 'label' => 'Complete', 'count' => $countsComplete],
    ['key' => 'partial', 'label' => 'Partial', 'count' => $countsPartial],
    ['key' => 'none', 'label' => 'Not vaccinated', 'count' => $countsNone],
    ['key' => 'overdue', 'label' => 'Overdue', 'count' => $countsOverdue],
    ['key' => 'under_treatment', 'label' => 'Under treatment', 'count' => $countsTreatment],
];
