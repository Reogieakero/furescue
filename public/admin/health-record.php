<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\Repository;
use App\Repositories\UserRepository;
use App\Services\VaccinationEngine;

require __DIR__ . '/../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../includes/guard.php';

require __DIR__ . '/includes/ui-helpers.php';

$recordIdParam = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
if ($recordIdParam === '' || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $recordIdParam) !== 1) {
    header('Location: /admin/health-records.php');
    exit;
}

$pdo = Database::connect();
$uid = (string) $_SESSION['user']['id'];

$animalRepo = new Repository($pdo, 'animals');
$animal = $animalRepo->find($recordIdParam);
if ($animal === null) {
    header('Location: /admin/health-records.php');
    exit;
}

$fsStmt = $pdo->prepare(
    "SELECT health_status FROM animal_field_status
     WHERE animal_id = ? ORDER BY logged_at DESC LIMIT 1"
);
$fsStmt->execute([$recordIdParam]);
$fsRow = $fsStmt->fetch(\PDO::FETCH_ASSOC);
$healthStatus = $fsRow['health_status'] ?? 'healthy';

$medicalRepo = new Repository($pdo, 'animal_medical_records');
$medical = $medicalRepo->findBy('animal_id', $recordIdParam) ?: [];

$vitalsRepo = new Repository($pdo, 'vitals_log');
$vitalsRows = $vitalsRepo->all(['animal_id' => $recordIdParam], 'recorded_at', 'DESC');
$latestVital = $vitalsRows[0] ?? null;

$photoUrl = null;
if (!empty($animal['photo_urls'])) {
    $urls = $animal['photo_urls'];
    if (is_string($urls)) {
        $dec = json_decode($urls, true);
        $urls = is_array($dec) ? $dec : [];
    }
    if (is_array($urls) && count($urls) > 0) {
        $photoUrl = $urls[0];
    }
}

$vaccinationStatus = $medical['vaccination_status'] ?? 'none';
$vaccinationDetails = $medical['vaccination_details'] ?? null;
if (is_string($vaccinationDetails) && $vaccinationDetails !== '') {
    $dec = json_decode($vaccinationDetails, true);
    $vaccinationDetails = is_array($dec) ? $dec : [];
} elseif ($vaccinationDetails === null) {
    $vaccinationDetails = [];
}

$rawRecords = $medical['vaccination_records'] ?? [];
if (is_string($rawRecords) && $rawRecords !== '') {
    $rawRecords = json_decode($rawRecords, true) ?: [];
}
if (!is_array($rawRecords)) {
    $rawRecords = [];
}
if (empty($rawRecords) && !empty($vaccinationDetails)) {
    $rawRecords = array_map(function ($v) {
        return [
            'vaccine' => $v['vaccine'] ?? 'Vaccine',
            'administered_date' => $v['dateGiven'] ?? ($v['date'] ?? null),
            'next_due' => $v['nextDue'] ?? null,
            'status' => $v['status'] ?? null,
            'dose_number' => $v['doseNumber'] ?? null,
            'manufacturer' => $v['manufacturer'] ?? null,
            'product_name' => $v['productName'] ?? null,
            'batch_number' => $v['batchNumber'] ?? null,
            'route' => $v['route'] ?? null,
            'notes' => $v['notes'] ?? null,
        ];
    }, $vaccinationDetails);
}

$vaccinations = array_map(function ($r) {
    $status = $r['status'] ?? null;
    if (!in_array($status, ['none', 'partial', 'complete', 'Completed', 'Pending', 'Overdue'], true)) {
        $status = $status ?: 'Completed';
    }
    return [
        'vaccine' => $r['vaccine'] ?? 'Vaccine',
        'dateGiven' => $r['administered_date'] ?? $r['dateGiven'] ?? null,
        'nextDue' => $r['next_due'] ?? $r['nextDue'] ?? null,
        'dueWindow' => null,
        'status' => $status,
        'doseNumber' => $r['dose_number'] ?? null,
        'flags' => [],
        'seriesComplete' => null,
        'minimumAgeWeeks' => null,
        'manufacturer' => $r['manufacturer'] ?? null,
        'productName' => $r['product_name'] ?? null,
        'batchNumber' => $r['batch_number'] ?? null,
        'route' => $r['route'] ?? null,
        'notes' => $r['notes'] ?? null,
    ];
}, $rawRecords);

$today = new \DateTime();
$reminders = [];
$addReminder = function (string $title, $due, string $icon) use (&$reminders, $today): void {
    if (!$due) {
        return;
    }
    $d = \DateTime::createFromFormat('Y-m-d', substr((string) $due, 0, 10));
    if (!$d) {
        return;
    }
    $days = (int) ceil(($d->getTimestamp() - $today->getTimestamp()) / 86400);
    $tone = $days < 0 ? 'red' : ($days <= 30 ? 'yellow' : 'blue');
    $reminders[] = [
        'title' => $title,
        'dueDate' => $d->format('M d, Y'),
        'days' => $days,
        'tone' => $tone,
        'icon' => $icon,
    ];
};
$addReminder('Next checkup', $medical['next_checkup_due'] ?? null, 'stethoscope');
$addReminder('Vaccination expiry', $medical['vaccination_expiry'] ?? null, 'syringe');

foreach ($vaccinations as $v) {
    $due = $v['nextDue'] ?? null;
    if ($due) {
        $vaccine = $v['vaccine'] ?? 'Vaccine';
        $addReminder($vaccine . ' vaccination due', $due, 'syringe');
    }
}

$history = [];
if (!empty($medical['last_checkup_date'])) {
    $history[] = [
        'date' => $medical['last_checkup_date'],
        'doctor' => $medical['vet_name'] ?? 'Furescue Vet',
        'title' => 'Regular Check-up',
        'description' => 'General physical examination',
        'tone' => 'green',
    ];
}
foreach ($vaccinations as $v) {
    if (!empty($v['dateGiven'])) {
        $history[] = [
            'date' => $v['dateGiven'],
            'doctor' => $medical['vet_name'] ?? 'Furescue Vet',
            'title' => $v['vaccine'] . ' Vaccination',
            'description' => 'Vaccine administered',
            'tone' => 'blue',
        ];
    }
}
$condition = $medical['condition'] ?? null;
if ($condition === null) {
    $condition = $healthStatus === 'not_healthy' ? 'Unknown' : 'Healthy';
}
if ($healthStatus === 'not_healthy') {
    $history[] = [
        'date' => $medical['updated_at'] ?? $animal['updated_at'],
        'doctor' => $medical['vet_name'] ?? 'Furescue Vet',
        'title' => 'Treatment',
        'description' => 'Marked not healthy — ' . $condition,
        'tone' => 'red',
    ];
}
usort($history, static fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

$vitals = [];
if (isset($medical['weight_kg']) && $medical['weight_kg'] !== null) {
    $vitals[] = ['label' => 'Weight', 'value' => (string) $medical['weight_kg'], 'unit' => 'kg'];
}
if (isset($medical['temperature_c']) && $medical['temperature_c'] !== null) {
    $vitals[] = ['label' => 'Body Temperature', 'value' => (string) $medical['temperature_c'], 'unit' => '°C'];
}
if ($latestVital && isset($latestVital['heart_rate_bpm']) && $latestVital['heart_rate_bpm'] !== null) {
    $vitals[] = ['label' => 'Heart Rate', 'value' => (string) $latestVital['heart_rate_bpm'], 'unit' => 'bpm'];
}
$vitalMeta = $latestVital ? ('Recorded on ' . substr((string) $latestVital['recorded_at'], 0, 10)) : null;

$heartRateHistory = [];
foreach (array_reverse($vitalsRows) as $vr) {
    if (isset($vr['heart_rate_bpm']) && $vr['heart_rate_bpm'] !== null) {
        $heartRateHistory[] = [
            'date' => substr((string) ($vr['recorded_at'] ?? ''), 0, 10),
            'value' => (int) $vr['heart_rate_bpm'],
        ];
    }
}

$docRows = (new Repository($pdo, 'animal_documents'))->all(['animal_id' => $recordIdParam], 'created_at', 'DESC');
$documents = array_map(function ($d) {
    return [
        'id' => $d['id'],
        'name' => $d['name'],
        'type' => $d['doc_type'] ?? null,
        'fileUrl' => $d['file_url'] ?? null,
        'meta' => $d['meta'] ?? null,
    ];
}, $docRows);

$notesMeta = '';
if (!empty($medical['vet_name'])) {
    $notesMeta .= 'by ' . $medical['vet_name'];
}
if (!empty($medical['updated_at'])) {
    $notesMeta = trim(($notesMeta !== '' ? $notesMeta . ' · ' : '') . 'updated ' . substr((string) $medical['updated_at'], 0, 10));
}

$record = [
    'id' => $animal['id'],
    'hasMedicalRecord' => !empty($medical),
    'name' => $animal['name'] ?? 'Unnamed',
    'species' => $animal['species'] ?? null,
    'breedType' => $animal['breed_type'] ?? null,
    'sex' => $animal['sex'] ?? null,
    'ageEstimate' => $animal['age_estimate'] ?? null,
    'birthDate' => $animal['birth_date'] ?? null,
    'barangay' => $animal['barangay'] ?? null,
    'adoptionStatus' => $animal['adoption_status'] ?? null,
    'photoUrl' => $photoUrl,
    'overview' => [
        'healthStatus' => $healthStatus,
        'vaccinationStatus' => $vaccinationStatus,
        'deworming' => $medical['deworming_status'] ?? 'unknown',
        'neutered' => $medical['neutered'] ?? 'unknown',
        'notes' => $medical['medical_history_notes'] ?? null,
        'notesMeta' => $notesMeta,
    ],
    'history' => $history,
    'vaccinations' => $vaccinations,
    'reminders' => $reminders,
    'vitals' => $vitals,
    'vitalMeta' => $vitalMeta,
    'heartRateHistory' => $heartRateHistory,
    'documents' => $documents,
    'protocols' => VaccinationEngine::protocolsForSpecies($animal['species'] ?? ''),
    'ageWeeks' => null,
];

$cap = static function (mixed $v): string {
    $s = (string) ($v ?? '');
    return $s !== '' ? mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, null, 'UTF-8') : $s;
};

$toneFor = static function (string $field, mixed $value): string {
    return match ($field) {
        'healthStatus' => $value === 'not_healthy' ? 'red' : 'green',
        'vaccinationStatus' => $value === 'complete' ? 'blue' : ($value === 'partial' ? 'yellow' : 'red'),
        'deworming' => $value === 'up_to_date' ? 'green' : ($value === 'overdue' ? 'red' : 'yellow'),
        'neutered' => $value === 'yes' ? 'green' : ($value === 'no' ? 'orange' : 'yellow'),
        default => 'blue',
    };
};

$TONE = [
    'green' => 'tint-green text-green',
    'blue' => 'tint-blue text-blue',
    'purple' => 'tint-purple text-purple',
    'orange' => 'tint-orange text-orange',
    'red' => 'tint-red text-red',
    'yellow' => 'tint-yellow text-yellow',
];
$ICON = [
    'green' => 'heart',
    'blue' => 'shield',
    'purple' => 'link',
    'orange' => 'scissors',
    'red' => 'activity',
    'yellow' => 'clock',
];

$chip = static function (string $tone, string $text): string {
    return '<span class="pill pill--' . e($tone) . '">' . e($text) . '</span>';
};

$emptyState = static function (string $msg, string $icon = 'inbox'): string {
    return '<div class="empty-state"><i data-lucide="' . e($icon) . '"></i><span>' . e($msg) . '</span></div>';
};

$vaxStatusPill = static function (mixed $status): string {
    $s = (string) ($status ?? '');
    $tone = match ($s) {
        'complete', 'Completed' => 'green',
        'partial' => 'yellow',
        'none', 'Not vaccinated' => 'red',
        default => 'gray',
    };
    $cls = $tone === 'gray' ? 'pill' : 'pill pill--' . $tone;
    $label = $s !== '' ? ucfirst(strtolower($s)) : 'Unknown';
    return '<span class="' . e($cls) . '">' . e($label) . '</span>';
};

$actionsHtml = !empty($record['hasMedicalRecord'])
    ? button_html('Edit', 'outline') . button_html('Add Health Record', 'default', 'sm', '', '', 'data-action="add-record"')
    : button_html('Add Health Record', 'default', 'sm', '', '', 'data-action="add-record"');

$pageHeadHtml = '
  <div class="page-head">
    <div>
      <a href="/admin/health-records.php" class="cd-back"><i data-lucide="chevron-left"></i> Back to health records</a>
    </div>
    <div class="page-head-actions">
      ' . $actionsHtml . '
    </div>
  </div>';

$photoHtml = $record['photoUrl']
    ? '<img src="' . e($record['photoUrl']) . '" alt="' . e($record['name']) . '" class="hr-photo">'
    : '<span class="hr-photo hr-photo--ph">' . e(mb_strtoupper(mb_substr((string) ($record['name'] ?? '?'), 0, 1, 'UTF-8'), 'UTF-8')) . '</span>';

$profileRows = [
    ['paw-print', 'Species', $record['species']],
    ['dog', 'Breed', $record['breedType']],
    ['venus-mars', 'Sex', $record['sex']],
    ['calendar', 'Age', $record['ageEstimate']],
    ['calendar-check', 'Date of birth', $record['birthDate']],
    ['map-pin', 'Location', $record['barangay']],
    ['heart-handshake', 'Status', $record['adoptionStatus']],
];
$profileRowsHtml = '';
foreach ($profileRows as $row) {
    [$ic, $label, $val] = $row;
    if ($val === null || $val === '') {
        continue;
    }
    $profileRowsHtml .= '
    <li class="hr-detail-row">
      <i data-lucide="' . e($ic) . '" class="hr-detail-ic"></i>
      <div class="hr-detail-text">
        <span class="hr-detail-label">' . e($label) . '</span>
        <span class="hr-detail-value">' . e($cap($val)) . '</span>
      </div>
    </li>';
}

$profilePanelHtml = '
  <section class="panel hr-profile-panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="dog"></i><h3 class="panel-title">Animal Profile</h3></div>
    </div>
    <div class="panel-body hr-profile-body">
      <div class="hr-profile">
        ' . $photoHtml . '
        <div class="hr-profile-info">
          <h2 class="hr-profile-name">' . e($cap($record['name'])) . '</h2>
          <div class="hr-info-card">
            <ul class="hr-detail-list">
            ' . $profileRowsHtml . '
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>';

$overview = $record['overview'];
$subCard = static function (string $field, string $label, mixed $value, string $extra = '') use ($toneFor, $TONE, $ICON, $chip): string {
    $tone = $toneFor($field, (string) ($value ?? ''));
    $toneClass = $TONE[$tone] ?? $TONE['blue'];
    $icon = $ICON[$tone] ?? 'circle';
    $displayValue = $value ?? '—';
    return '
    <div class="hr-subcard">
      <span class="tint-circle ' . e(explode(' ', $toneClass)[0]) . '"><i data-lucide="' . e($icon) . '"></i></span>
      <div>
        <div class="hr-subcard-label">' . e($label) . '</div>
        <div class="hr-subcard-value">' . $chip($tone, (string) $displayValue) . '</div>
        ' . $extra . '
      </div>
    </div>';
};

$vaxList = $record['vaccinations'] ?? [];
$vaxSorted = array_values(array_filter($vaxList, static fn($v) => !empty($v['vaccine'])));
usort($vaxSorted, static fn($a, $b) => strcmp((string) ($b['dateGiven'] ?? ''), (string) ($a['dateGiven'] ?? '')));
$latestVax = $vaxSorted[0] ?? null;

$interpretation = '';
if ($overview) {
    $parts = [];
    if (!empty($overview['healthStatus'])) {
        $parts[] = $overview['healthStatus'] === 'not_healthy'
            ? 'This animal is currently flagged as not healthy and needs prompt veterinary attention.'
            : 'This animal is in good general health.';
    }
    if (!empty($overview['vaccinationStatus'])) {
        $parts[] = match ($overview['vaccinationStatus']) {
            'complete' => 'Vaccinations are complete and up to date.',
            'partial' => 'Vaccinations are only partially done; remaining doses should be scheduled.',
            default => 'Vaccinations are not up to date and should be prioritised.',
        };
    }
    if (!empty($overview['deworming'])) {
        $parts[] = match ($overview['deworming']) {
            'up_to_date' => 'Deworming is up to date.',
            'overdue' => 'Deworming is overdue and should be repeated soon.',
            default => 'Deworming status is pending.',
        };
    }
    if (!empty($overview['neutered'])) {
        $parts[] = match ($overview['neutered']) {
            'yes' => 'The animal is neutered.',
            'no' => 'The animal is not neutered; consider scheduling the procedure.',
            default => 'Neutering status is unknown.',
        };
    }
    $interpretation = implode(' ', $parts);
}

$notesHtml = $interpretation !== ''
    ? '<div class="hr-notes"><p class="hr-notes-text">' . e($interpretation) . '</p><p class="hr-notes-meta">' . e($overview['notesMeta'] ?? 'Interpretation of the health overview data above') . '</p></div>'
    : $emptyState('No health data recorded');

$overviewPanelHtml = '
  <section class="panel hr-overview-panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="activity"></i><h3 class="panel-title">Health Overview</h3></div>
    </div>
    <div class="panel-body hr-overview-body">
      <div class="hr-subcards">
        ' . $subCard('healthStatus', 'Health Status', $overview['healthStatus'] ?? '') . '
        ' . $subCard('vaccinationStatus', 'Vaccination', $latestVax ? $latestVax['vaccine'] : ($overview['vaccinationStatus'] ?? '')) . '
        ' . $subCard('deworming', 'Deworming', $overview['deworming'] ?? '') . '
        ' . $subCard('neutered', 'Neutered', $overview['neutered'] ?? '') . '
      </div>
      ' . $notesHtml . '
    </div>
  </section>';

$historyItems = '';
foreach ($history as $i => $h) {
    $tone = $TONE[$h['tone']] ?? $TONE['green'];
    $toneClass = explode(' ', $tone)[0];
    $icon = $ICON[$h['tone']] ?? 'circle';
    $historyItems .= '
    <li class="hr-tl-item">
      <span class="hr-tl-dot ' . e($toneClass) . '"><i data-lucide="' . e($icon) . '"></i></span>
      <div class="hr-tl-content">
        <div class="hr-tl-row"><span class="hr-tl-date">' . e($h['date']) . '</span><span class="hr-tl-doctor">' . e($h['doctor']) . '</span></div>
        <div class="hr-tl-title">' . e($h['title']) . '</div>
        <div class="hr-tl-desc">' . e($h['description']) . '</div>
      </div>
    </li>';
}

$historyPanelHtml = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="clipboard-list"></i><h3 class="panel-title">Medical History</h3></div>
    </div>
    <div class="panel-body">
      ' . ($historyItems !== '' ? '<ul class="hr-timeline">' . $historyItems . '</ul>' : $emptyState('No medical history recorded yet')) . '
    </div>
  </section>';

$vaxVisible = $record['vaccinations'] ?? [];
$vaxTableRows = '';
foreach ($vaxVisible as $i => $v) {
    $vaxTableRows .= '
    <tr>
      <td class="table-cell table-cell--strong">' . e($v['vaccine']) . '</td>
      <td class="table-cell table-cell--mono">' . e($v['dateGiven'] ?? '—') . '</td>
      <td class="table-cell table-cell--mono">Dose ' . e($v['doseNumber'] ?? '—') . '</td>
      <td class="table-cell table-cell--mono">' . e($v['nextDue'] ?? '—') . '</td>
      <td class="table-cell table-cell--right">' . $vaxStatusPill($v['status'] ?? null) . '</td>
    </tr>';
}

$vaxPanelHtml = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="syringe"></i><h3 class="panel-title">Vaccination Record</h3></div>
    </div>
    <div class="panel-body">
      ' . ($vaxTableRows !== '' ? '<div class="table-wrap"><table class="table"><thead class="table-head"><tr><th>Vaccine</th><th>Date Given</th><th>Dose</th><th>Next Due</th><th class="table-cell--right">Status</th></tr></thead><tbody>' . $vaxTableRows . '</tbody></table></div>' : $emptyState('No vaccinations recorded')) . '
    </div>
  </section>';

$reminderItems = '';
foreach ($record['reminders'] ?? [] as $r) {
    $tone = $TONE[$r['tone']] ?? $TONE['blue'];
    $toneClass = explode(' ', $tone)[0];
    $reminderItems .= '
    <li class="hr-reminder">
      <div class="hr-reminder-left">
        <span class="tint-circle ' . e($toneClass) . '"><i data-lucide="' . e($r['icon']) . '"></i></span>
        <div>
          <div class="hr-reminder-title">' . e($r['title']) . '</div>
          <div class="hr-reminder-due">Due ' . e($r['dueDate']) . '</div>
        </div>
      </div>
      <span class="pill pill--' . e($r['tone']) . '">' . e($r['days'] . ' days') . '</span>
    </li>';
}

$remindersPanelHtml = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="bell"></i><h3 class="panel-title">Upcoming Reminders</h3></div>
    </div>
    <div class="panel-body">
      ' . ($reminderItems !== '' ? '<ul class="hr-reminder-list">' . $reminderItems . '</ul>' : $emptyState('No upcoming reminders')) . '
    </div>
  </section>';

$vitalItems = '';
foreach ($record['vitals'] ?? [] as $v) {
    $vitalItems .= '
    <li class="hr-vital">
      <div class="hr-vital-left">
        <span class="hr-vital-label">' . e($v['label']) . '</span>
        <span class="hr-vital-value">' . e($v['value']) . '<small>' . e($v['unit']) . '</small></span>
      </div>
    </li>';
}

$vitalsPanelHtml = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="heart-pulse"></i><h3 class="panel-title">Vital Signs</h3></div>
    </div>
    <div class="panel-body">
      ' . ($vitalItems !== '' ? '<ul class="hr-vital-list">' . $vitalItems . '</ul><p class="hr-vital-meta">' . e($record['vitalMeta'] ?? '') . '</p>' : $emptyState('No vitals recorded')) . '
    </div>
  </section>';

$docRowsHtml = '';
foreach (array_slice($record['documents'] ?? [], 0, 3) as $i => $d) {
    $docRowsHtml .= '
    <tr class="hr-doc-row" data-action="view-document" data-idx="' . e((string) $i) . '" style="cursor:pointer">
      <td class="table-cell table-cell--strong"><i data-lucide="file-text" class="hr-doc-ic"></i>' . e($d['name']) . '</td>
      <td class="table-cell table-cell--muted">' . e($d['type'] ?? 'Document') . '</td>
      <td class="table-cell table-cell--mono">' . e($d['meta'] ?? '—') . '</td>
      <td class="table-cell table-cell--right">' . ($d['fileUrl'] ? '<span class="hr-doc-open">View</span>' : '<span class="table-cell--muted">—</span>') . '</td>
    </tr>';
}

$documentsPanelHtml = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="file-text"></i><h3 class="panel-title">Health Documents</h3></div>
    </div>
    <div class="panel-body">
      ' . ($docRowsHtml !== '' ? '<div class="table-wrap hr-doc-table-wrap"><table class="table"><thead class="table-head"><tr><th>Document</th><th>Type</th><th>Meta</th><th class="table-cell--right">File</th></tr></thead><tbody>' . $docRowsHtml . '</tbody></table></div>' : $emptyState('No documents uploaded')) . '
    </div>
  </section>';

$statItems = [
    ['Check-ups', count(array_filter($record['history'] ?? [], static fn($h) => preg_match('/check/i', $h['title'] ?? ''))), 'stethoscope', 'green'],
    ['Vaccinations', count($record['vaccinations'] ?? []), 'syringe', 'blue'],
    ['Reminders', count($record['reminders'] ?? []), 'bell', 'yellow'],
    ['Vitals logged', count($record['vitals'] ?? []), 'heart-pulse', 'purple'],
];
$statHtml = '';
foreach ($statItems as [$label, $num, $icon, $tone]) {
    $statHtml .= '
  <div class="hr-stat">
    <span class="tint-circle ' . e(explode(' ', $TONE[$tone] ?? $TONE['blue'])[0]) . '"><i data-lucide="' . e($icon) . '"></i></span>
    <div class="hr-stat-text">
      <div class="hr-stat-num">' . e((string) $num) . '</div>
      <div class="hr-stat-label">' . e($label) . '</div>
    </div>
  </div>';
}

$statsPanelHtml = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="bar-chart-3"></i><h3 class="panel-title">Health Statistics</h3></div>
    </div>
    <div class="panel-body">
      <div class="hr-stat-strip">
        ' . $statHtml . '
      </div>
    </div>
  </section>';

$children = $pageHeadHtml
    . '<div class="hr-grid">' . $profilePanelHtml . $overviewPanelHtml . '</div>'
    . '<div class="hr-trio">
        ' . $historyPanelHtml . '
        <div class="hr-trio-col">' . $vaxPanelHtml . $remindersPanelHtml . '</div>
        <div class="hr-trio-col">' . $vitalsPanelHtml . $documentsPanelHtml . '</div>
      </div>'
    . $statsPanelHtml;

$currentUser = (new UserRepository($pdo))->find($uid);
$currentUserData = $currentUser ? $currentUser->toArray() : [];
$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => '',
];
$activeNav = 'health records';
$navBadges = ['notifications' => 3];
$adminChildren = $children;

ob_start();
require __DIR__ . '/../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$state = [
    'record' => $record,
];

$pageTitle = 'FurEscue — Health Record';
$pageDescription = 'FurEscue admin — dedicated health record for a rescued animal.';
$pageCss = ['/admin/css/admin.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
$importMapExtras = [];
require __DIR__ . '/../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script type="module" src="js/health-record.js"></script>
  </body>
</html>
