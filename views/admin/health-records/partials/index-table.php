<?php

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
            <span class="table-cell--strong"><a href="/admin/health-records/health-record.php?id=' . e((string) $r['id']) . '">' . e((string) $r['animalName']) . '</a></span><br>
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
