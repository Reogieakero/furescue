<?php

declare(strict_types=1);

$health = dash_health_overview($healthRecords);
$summaryHtml = '';
foreach ($health['summary'] as $row) {
    $summaryHtml .= '
      <div class="dash-health-row dash-health-row--' . dash_esc($row['key']) . '">
        <span><i data-lucide="' . dash_esc($row['icon']) . '"></i>' . dash_esc($row['label']) . '</span>
        <strong>' . dash_esc((string) $row['count']) . '</strong>
      </div>';
}
$vaxLegend = '';
foreach ($health['vax'] as $item) {
    $vaxLegend .= '
      <div class="dash-cat-item">
        <span><span class="dash-legend-dot dash-legend-dot--' . dash_esc($item['key']) . '"></span>' . dash_esc($item['label']) . '</span>
        <strong>' . dash_esc((string) $item['pct']) . '%</strong>
      </div>';
}
if ($health['reminders'] === []) {
    $remindersHtml = empty_state('bell', 'No upcoming reminders.');
} else {
    $remindersHtml = '';
    foreach ($health['reminders'] as $item) {
        $remindersHtml .= '
      <div class="dash-reminder">
        <div>
          <div class="dash-reminder-label">' . dash_esc($item['label']) . '</div>
          <div class="dash-reminder-detail">' . dash_esc($item['detail']) . '</div>
        </div>
        <span class="dash-reminder-count">' . dash_esc((string) $item['count']) . '</span>
      </div>';
    }
}
if ($health['checkups'] === []) {
    $checkupsHtml = empty_state('stethoscope', 'No recent check-ups.');
} else {
    $checkupsHtml = '';
    foreach ($health['checkups'] as $c) {
        $photo = $c['photo']
            ? '<img class="dash-checkup-photo" src="' . dash_esc($c['photo']) . '" alt="">'
            : '<span class="dash-checkup-fallback">' . dash_esc(initials_of($c['name'])) . '</span>';
        $href = $c['animalId'] !== ''
            ? '/admin/health-records/health-record.php?id=' . rawurlencode($c['animalId'])
            : '/admin/health-records/';
        $checkupsHtml .= '
      <a class="dash-checkup" href="' . dash_esc($href) . '">
        ' . $photo . '
        <div class="dash-checkup-body">
          <div class="dash-checkup-name">' . dash_esc($c['name']) . '</div>
          <div class="dash-checkup-meta">' . dash_esc($c['meta']) . '</div>
        </div>
        ' . dash_health_pill($c['statusKey'], $c['status']) . '
      </a>';
    }
}

$healthOverviewCard = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="heart-pulse"></i><h2 class="panel-title">Health Monitoring Overview</h2></div>
      <a href="/admin/health-records/" class="dash-link">Open records</a>
    </div>
    <div class="dash-health-grid">
      <div class="dash-subcard">
        <h3>Health Summary</h3>
        ' . $summaryHtml . '
        <div class="dash-health-total">Total Animals: ' . dash_esc((string) $health['totalAnimals']) . '</div>
      </div>
      <div class="dash-subcard">
        <h3>Vaccination Status</h3>
        <div class="dash-cat-wrap">
          <div class="dash-donut"><canvas id="vax-status-donut"></canvas></div>
          <div class="dash-cat-legend">' . $vaxLegend . '</div>
        </div>
      </div>
      <div class="dash-subcard">
        <h3>Upcoming Reminders</h3>
        ' . $remindersHtml . '
      </div>
      <div class="dash-subcard">
        <h3>Recent Check-ups</h3>
        ' . $checkupsHtml . '
      </div>
    </div>
  </section>';

$reportsTrendCard = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="trending-up"></i><h2 class="panel-title">Reports Over Time</h2></div>
    </div>
    <div class="dash-trend-body">
      <canvas id="reports-trend-chart"></canvas>
    </div>
  </section>';

$healthTrendRow = '
  <div class="dash-bottom">
    ' . $healthOverviewCard . '
    ' . $reportsTrendCard . '
  </div>';
