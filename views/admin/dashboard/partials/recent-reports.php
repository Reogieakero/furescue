<?php

declare(strict_types=1);

$recentRows = '';
foreach (array_slice($allReportItems, 0, 8) as $r) {
    $statusKey = dash_display_status($r);
    $href = !empty($r['case_id'])
        ? '/admin/cases/case-detail.php?id=' . rawurlencode((string) $r['case_id'])
        : '/admin/reports/';
    $recentRows .= '
    <tr>
      <td class="table-cell table-cell--mono table-cell--strong">' . dash_esc(dash_format_report_id($r['id'] ?? '', $r['created_at'] ?? null)) . '</td>
      <td class="table-cell">' . dash_esc(dash_report_type_label($r['animal_description'] ?? '')) . '</td>
      <td class="table-cell">' . dash_esc(($r['address_text'] ?? null) ?: '—') . '</td>
      <td class="table-cell">' . dash_esc(($r['resident_name'] ?? null) ?: 'Resident') . '</td>
      <td class="table-cell table-cell--nowrap">' . dash_esc(dash_format_datetime($r['created_at'] ?? null)) . '</td>
      <td class="table-cell">' . dash_status_pill($statusKey) . '</td>
      <td class="table-cell table-cell--right">
        <a class="dash-icon-btn" href="' . dash_esc($href) . '" aria-label="View report"><i data-lucide="eye"></i></a>
      </td>
    </tr>';
}

if ($recentRows === '') {
    $recentInner = '<div class="queue-empty">' . empty_state('file-text', 'No reports yet.') . '</div>';
} else {
    $recentInner = '
    <div class="table-wrap">
      <table class="table">
        ' . table_head(['Report ID', 'Type', 'Location', 'Submitted By', 'Date & Time', 'Status', 'Action']) . '
        <tbody>' . $recentRows . '</tbody>
      </table>
    </div>
    <div class="panel-foot"><a href="/admin/reports/" class="dash-link">View All Reports ' . chevron_right() . '</a></div>';
}

$recentReportsCard = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="clipboard-list"></i><h2 class="panel-title">Recent Reports</h2></div>
    </div>
    <div id="recent-reports">' . $recentInner . '</div>
  </section>';
