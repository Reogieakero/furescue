<?php

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
