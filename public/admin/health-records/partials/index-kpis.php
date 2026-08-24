<?php

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
