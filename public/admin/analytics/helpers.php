<?php

declare(strict_types=1);

/**
 * Render helpers for /admin/analytics/ (page-local).
 * Row builders intentionally mirror what analytics.js renders client-side.
 */

function overview_value(array $rows, string $key): int
{
    foreach ($rows as $r) {
        if (($r['key'] ?? '') === $key) {
            return (int) ($r['value'] ?? 0);
        }
    }
    return 0;
}

function kpi_grid_html(array $tiles): string
{
    $out = '<div class="kpi-grid">';
    foreach ($tiles as $k) {
        $note = '';
        if (!empty($k['note'])) {
            $note = '<span class="kpi-note ' . e($k['note']['cls']) . '">' . e($k['note']['text']) . '</span>';
        }
        $tileCls = 'kpi-tile' . (!empty($k['dark']) ? ' kpi-tile--dark' : '');
        $out .= "
  <div class=\"{$tileCls}\">
    <div class=\"kpi-top\">
      <div class=\"kpi-icon\"><i data-lucide=\"" . e($k['icon']) . "\"></i></div>
      {$note}
    </div>
    <div class=\"kpi-value\">" . e($k['value']) . "</div>
    <div class=\"kpi-label\">" . e($k['label']) . '</div>
  </div>';
    }
    return $out . '</div>';
}

function overview_rows_html(array $rows): string
{
    $html = '';
    foreach ($rows as $r) {
        $html .= "
    <tr>
      <td class=\"table-cell\">" . e($r['label'] ?? '') . "</td>
      <td class=\"table-cell table-cell--mono table-cell--strong\">" . e($r['value'] ?? '') . '</td>
    </tr>';
    }
    return $html;
}

function trend_rows_html(array $trends): string
{
    $html = '';
    foreach ($trends as $t) {
        $html .= "
    <tr>
      <td class=\"table-cell table-cell--mono\">" . e($t['day'] ?? '') . "</td>
      <td class=\"table-cell table-cell--mono table-cell--strong\">" . e($t['completed'] ?? 0) . '</td>
    </tr>';
    }
    return $html;
}

function health_update_row(array $h): array
{
    $healthy = ($h['health_status'] ?? '') === 'healthy';
    $animalParts = array_filter(
        [(string) ($h['animal_name'] ?? ''), (string) ($h['breed_type'] ?? '')],
        static fn($p) => $p !== ''
    );
    return [
        'id' => short_id($h['id'] ?? null),
        'animal' => $animalParts !== [] ? implode(', ', $animalParts) : 'Unnamed animal',
        'by' => ($h['logged_by_name'] ?? null) ?: '—',
        'when' => time_ago($h['logged_at'] ?? null),
        'status' => $healthy ? 'Stable' : 'Needs Attention',
        'statusCls' => $healthy ? 'stamp--accent' : 'stamp--coral',
    ];
}

function health_rows_html(array $rows): string
{
    $html = '';
    foreach ($rows as $r) {
        $html .= "
    <tr>
      <td class=\"table-cell table-cell--mono table-cell--strong\">" . e($r['id']) . "</td>
      <td class=\"table-cell\">" . e($r['animal']) . "</td>
      <td class=\"table-cell\">" . e($r['by']) . "</td>
      <td class=\"table-cell\"><span class=\"stamp stamp--sm " . e($r['statusCls']) . "\">" . e($r['status']) . "</span></td>
      <td class=\"table-cell table-cell--mono table-cell--muted\">" . e($r['when']) . '</td>
    </tr>';
    }
    return $html;
}
