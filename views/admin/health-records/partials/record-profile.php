<?php

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
