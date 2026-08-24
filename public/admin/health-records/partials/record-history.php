<?php

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
