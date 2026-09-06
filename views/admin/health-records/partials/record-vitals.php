<?php

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
