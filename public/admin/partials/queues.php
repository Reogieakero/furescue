<?php

declare(strict_types=1);

$mapReport = static function (array $r): array {
    return [
        'id' => short_id($r['id'] ?? null),
        'rid' => (string) ($r['id'] ?? ''),
        'brgy' => ($r['address_text'] ?? null) ?: '—',
        'reporter' => short_id($r['resident_id'] ?? null),
        'when' => time_ago($r['created_at'] ?? null),
    ];
};
$mapRescuerApplicant = static function (array $u): array {
    return [
        'name' => ($u['full_name'] ?? null) ?: 'Unnamed applicant',
        'rid' => (string) ($u['id'] ?? ''),
        'img' => (string) ($u['profile_photo_url'] ?? ''),
        'org' => ($u['phone_number'] ?? null) ?: '—',
        'file' => '—',
        'when' => time_ago($u['created_at'] ?? null),
    ];
};
$mapHealthUpdate = static function (array $h): array {
    $healthy = ($h['health_status'] ?? '') === 'healthy';
    $animalParts = array_filter([(string) ($h['animal_name'] ?? ''), (string) ($h['breed_type'] ?? '')], static fn($p) => $p !== '');
    return [
        'id' => short_id($h['id'] ?? null),
        'rid' => (string) ($h['id'] ?? ''),
        'animal' => $animalParts ? implode(', ', $animalParts) : 'Unnamed animal',
        'animal_id' => (string) ($h['animal_id'] ?? ''),
        'by' => ($h['logged_by_name'] ?? null) ?: '—',
        'when' => time_ago($h['logged_at'] ?? null),
        'icon' => ($h['species'] ?? '') === 'cat' ? 'cat' : 'paw-print',
        'ok' => $healthy,
        'rescue' => title_case($h['rescue_status'] ?? '') ?: 'Rescued',
        'status' => $healthy ? 'Stable' : 'Needs Attention',
        'statusCls' => $healthy ? 'hc-card--accent' : 'hc-card--coral',
    ];
};
$mapAdoption = static function (array $a): array {
    return [
        'name' => ($a['applicant_name'] ?? null) ?: short_id($a['applicant_id'] ?? null),
        'rid' => (string) ($a['id'] ?? ''),
        'animal' => ($a['animal_name'] ?? null) ?: short_id($a['animal_id'] ?? null),
        'visit' => '—',
        'visitCls' => 'status-text--muted',
        'when' => time_ago($a['created_at'] ?? null),
    ];
};

$reportsRows = '';
foreach (array_slice(array_map($mapReport, $reportsPending['items']), 0, 7) as $r) {
    $reportsRows .= "
    <tr>
      <td class=\"table-cell table-cell--mono table-cell--strong\">" . e($r['id']) . "</td>
      <td class=\"table-cell\">" . e($r['brgy']) . "</td>
      <td class=\"table-cell\">" . e($r['reporter']) . "</td>
      <td class=\"table-cell table-cell--mono table-cell--muted\">" . e($r['when']) . "</td>
      <td class=\"table-cell table-cell--right table-cell--nowrap\">
        <span class=\"table-actions\">
          <a href=\"#\" class=\"action-link\" data-action=\"details\" data-id=\"" . e($r['rid']) . "\">Details</a>
          <a href=\"#\" class=\"action-link\" data-action=\"verify\" data-id=\"" . e($r['rid']) . "\">Verify</a>
          <a href=\"#\" class=\"action-link action-link--danger\" data-action=\"dismiss\" data-id=\"" . e($r['rid']) . '">Dismiss</a>
        </span>
      </td>
    </tr>';
}
if ($reportsRows === '') {
    $reportsQueueInner = '<div class="queue-empty">' . empty_state('file-text', 'No reports pending verification.') . '</div>';
} else {
    $pagination = count($reportsPending['items']) > 7 ? '<div class="queue-pagination">' . pagination_bar(count($reportsPending['items']), 7, 1) . '</div>' : '';
    $reportsQueueInner = '
    <div class="table-wrap">
      <table class="table">
        ' . table_head(['Case', 'Barangay', 'Reporter', 'Submitted', 'Action']) . '
        <tbody>' . $reportsRows . '</tbody>
      </table>
    </div>
    <div class="panel-foot"><a href="/admin/reports/" class="btn-link">View all ' . e($reportsPending['total']) . ' reports ' . chevron_right() . '</a></div>
    ' . $pagination;
}

$rescuerRows = '';
foreach (array_slice(array_map($mapRescuerApplicant, $rescuersPending['items']), 0, 7) as $r) {
    $rescuerRows .= "
    <tr>
      <td class=\"table-cell table-cell--strong\"><span class=\"table-avatar-name\">" . avatar_img($r['img'], $r['name']) . e($r['name']) . "</span></td>
      <td class=\"table-cell\">" . e($r['org']) . "</td>
      <td class=\"table-cell\"><span class=\"file-link\"><i data-lucide=\"file-check\"></i> " . e($r['file']) . "</span></td>
      <td class=\"table-cell table-cell--mono table-cell--muted\">" . e($r['when']) . "</td>
      <td class=\"table-cell table-cell--right table-cell--nowrap\">
        <span class=\"table-actions\">
          <a href=\"#\" class=\"action-link\" data-action=\"details\" data-id=\"" . e($r['rid']) . "\">Details</a>
          <a href=\"#\" class=\"action-link\" data-action=\"approve-rescuer\" data-id=\"" . e($r['rid']) . "\">Approve</a>
          <a href=\"#\" class=\"action-link action-link--danger\" data-action=\"reject-rescuer\" data-id=\"" . e($r['rid']) . '">Reject</a>
        </span>
      </td>
    </tr>';
}
if ($rescuerRows === '') {
    $rescuersQueueInner = '<div class="queue-empty">' . empty_state('user-check', 'No rescuer applications awaiting review.') . '</div>';
} else {
    $pagination = count($rescuersPending['items']) > 7 ? '<div class="queue-pagination">' . pagination_bar(count($rescuersPending['items']), 7, 1) . '</div>' : '';
    $rescuersQueueInner = '
    <div class="table-wrap">
      <table class="table">
        ' . table_head(['Applicant', 'Affiliation', 'Proof of ID', 'Submitted', 'Action']) . '
        <tbody>' . $rescuerRows . '</tbody>
      </table>
    </div>
    ' . $pagination;
}

$healthRows = '';
foreach (array_slice(array_map($mapHealthUpdate, $healthUpdatesState['items']), 0, 7) as $r) {
    $healthRows .= "
    <tr>
      <td class=\"table-cell table-cell--mono table-cell--strong\">" . e($r['id']) . "</td>
      <td class=\"table-cell\">" . e($r['animal']) . "</td>
      <td class=\"table-cell\">" . e($r['by']) . "</td>
      <td class=\"table-cell table-cell--muted\">" . e($r['status']) . "</td>
      <td class=\"table-cell table-cell--mono table-cell--muted\">" . e($r['when']) . "</td>
      <td class=\"table-cell table-cell--right table-cell--nowrap\">
        <span class=\"table-actions\">
          <a href=\"#\" class=\"action-link\" data-action=\"details\" data-id=\"" . e($r['rid']) . "\">Details</a>
          <a href=\"/admin/health-records/health-record.php?id=" . e($r['animal_id']) . "\" class=\"action-link\">View record</a>
        </span>
      </td>
    </tr>";
}
if ($healthRows === '') {
    $healthQueueInner = '<div class="queue-empty">' . empty_state('heart-pulse', 'No recent health updates.') . '</div>';
} else {
    $pagination = count($healthUpdatesState['items']) > 7 ? '<div class="queue-pagination">' . pagination_bar(count($healthUpdatesState['items']), 7, 1) . '</div>' : '';
    $healthQueueInner = '
    <div class="table-wrap">
      <table class="table">
        ' . table_head(['Update', 'Animal', 'Logged by', 'Status', 'When', 'Action']) . '
        <tbody>' . $healthRows . '</tbody>
      </table>
    </div>
    ' . $pagination;
}

$adoptRows = '';
foreach (array_slice(array_map($mapAdoption, $adoptionsPending['items']), 0, 7) as $r) {
    $adoptRows .= "
    <tr>
      <td class=\"table-cell table-cell--strong\">" . e($r['name']) . "</td>
      <td class=\"table-cell\">" . e($r['animal']) . "</td>
      <td class=\"table-cell\"><span class=\"status-text " . e($r['visitCls']) . "\">" . e($r['visit']) . "</span></td>
      <td class=\"table-cell table-cell--mono table-cell--muted\">" . e($r['when']) . "</td>
      <td class=\"table-cell table-cell--right table-cell--nowrap\">
        <span class=\"table-actions\">
          <a href=\"#\" class=\"action-link\" data-action=\"details\" data-id=\"" . e($r['rid']) . "\">Details</a>
          <a href=\"#\" class=\"action-link\" data-action=\"approve-adoption\" data-id=\"" . e($r['rid']) . "\">Approve</a>
          <a href=\"#\" class=\"action-link action-link--danger\" data-action=\"decline-adoption\" data-id=\"" . e($r['rid']) . '">Decline</a>
        </span>
      </td>
    </tr>';
}
if ($adoptRows === '') {
    $adoptionQueueInner = '<div class="queue-empty">' . empty_state('home', 'No adoption applications awaiting review.') . '</div>';
} else {
    $pagination = count($adoptionsPending['items']) > 7 ? '<div class="queue-pagination">' . pagination_bar(count($adoptionsPending['items']), 7, 1) . '</div>' : '';
    $adoptionQueueInner = '
    <div class="table-wrap">
      <table class="table">
        ' . table_head(['Applicant', 'Animal', 'Home visit', 'Submitted', 'Action']) . '
        <tbody>' . $adoptRows . '</tbody>
      </table>
    </div>
    <div class="panel-foot"><a href="/admin/applications/" class="btn-link">View all ' . e($adoptionsPending['total']) . ' applications ' . chevron_right() . '</a></div>
    ' . $pagination;
}

$attentionQueue = "
  <div class=\"panel\">
    <div class=\"panel-head\">
      <div class=\"panel-title-wrap\">
        <i data-lucide=\"inbox\"></i>
        <h2 class=\"panel-title\">Needs your attention</h2>
      </div>
      <div class=\"q-tabs\" id=\"queueTabs\">
        <button data-q=\"reports\" class=\"q-btn is-active\">Reports &middot; " . e($reportsPending['total']) . "</button>
        <button data-q=\"rescuers\" class=\"q-btn\">Rescuers &middot; " . e($rescuersPending['total']) . "</button>
        <button data-q=\"health\" class=\"q-btn\">Health &middot; " . e($healthUpdatesState['total']) . "</button>
        <button data-q=\"adopt\" class=\"q-btn\">Adoptions &middot; " . e($adoptionsPending['total']) . "</button>
      </div>
    </div>
    <div id=\"queue-reports\" class=\"queue-panel\">{$reportsQueueInner}</div>
    <div id=\"queue-rescuers\" class=\"queue-panel is-hidden\">{$rescuersQueueInner}</div>
    <div id=\"queue-health\" class=\"queue-panel is-hidden\">{$healthQueueInner}</div>
    <div id=\"queue-adopt\" class=\"queue-panel is-hidden\">{$adoptionQueueInner}</div>
  </div>";
