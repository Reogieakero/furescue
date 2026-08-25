<?php

declare(strict_types=1);

$activityRows = '';
$activityMapped = array_map(static function (array $c): array {
    $status = (string) ($c['status'] ?? 'assigned');
    $statusCls = $status === 'resolved' ? 'stamp--accent'
        : ($status === 'in_progress' ? 'stamp--accent' : 'stamp--coral');
    $whenSrc = ($c['updated_at'] ?? null) ?: ($c['created_at'] ?? null);
    return [
        'id' => short_id($c['id'] ?? null),
        'animal' => ($c['animal_description'] ?? null) ? truncate_text($c['animal_description'], 28) : '—',
        'brgy' => ($c['address_text'] ?? null) ?: '—',
        'rescuer' => ($c['assigned_rescuer_name'] ?? null) ?: '—',
        'status' => title_case($status),
        'statusCls' => $statusCls,
        'when' => time_ago($whenSrc),
    ];
}, $caseList);
if ($activityMapped === []) {
    $activityInner = '<div class="activity-empty">' . empty_state('list', 'No records.') . '</div>';
} else {
    foreach (array_slice($activityMapped, 0, 5) as $r) {
        $activityRows .= "
    <tr>
      <td class=\"table-cell table-cell--mono table-cell--strong\">" . e($r['id']) . "</td>
      <td class=\"table-cell\">" . e($r['animal']) . "</td>
      <td class=\"table-cell\">" . e($r['brgy']) . "</td>
      <td class=\"table-cell\">" . e($r['rescuer']) . "</td>
      <td class=\"table-cell\"><span class=\"stamp stamp--sm " . e($r['statusCls']) . "\">" . e($r['status']) . "</span></td>
      <td class=\"table-cell table-cell--mono table-cell--muted\">" . e($r['when']) . '</td>
    </tr>';
    }
    $pagination = count($activityMapped) > 5 ? '<div class="queue-pagination">' . pagination_bar(count($activityMapped), 5, 1) . '</div>' : '';
    $activityInner = '
    <div class="table-wrap">
      <table class="table">
        ' . table_head(['Case', 'Animal', 'Barangay', 'Rescuer', 'Status', 'Updated']) . '
        <tbody>' . $activityRows . '</tbody>
      </table>
    </div>
    ' . $pagination;
}

$activityTable = '
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="list"></i><h2 class="panel-title">Recent case activity</h2></div>
      <a href="/admin/cases/" class="btn-link">View all cases ' . chevron_right() . '</a>
    </div>
    <div id="activity-table" class="activity-table">' . $activityInner . '</div>
  </div>';

$dashboardSections = "
  {$gisRow}
  {$recentReportsCard}
  {$healthTrendRow}
  {$attentionRow}
  <div class=\"cols cols--two\">
    {$elearningCard}
    {$auditLogCard}
  </div>";
