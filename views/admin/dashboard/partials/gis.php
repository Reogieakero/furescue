<?php

declare(strict_types=1);

$gisUpdatedTs = 0;
foreach ($heatmap as $p) {
    $t = strtotime((string) ($p['created_at'] ?? ''));
    if ($t && $t > $gisUpdatedTs) {
        $gisUpdatedTs = $t;
    }
}
$gisUpdated = $gisUpdatedTs ? date('M j, Y g:i A', $gisUpdatedTs) : '—';
$density = dash_density_summary($heatmap);
$categories = dash_category_breakdown($allReportItems);
$typeOptions = [
    ['value' => '', 'label' => 'All Report Types'],
    ['value' => 'stray', 'label' => 'Stray Animal'],
    ['value' => 'injured', 'label' => 'Injured Animal'],
    ['value' => 'abuse', 'label' => 'Abuse/Neglect'],
    ['value' => 'other', 'label' => 'Others'],
];
$statusOptions = [
    ['value' => '', 'label' => 'All Status'],
    ['value' => 'pending', 'label' => 'Pending'],
    ['value' => 'verified', 'label' => 'Verified'],
    ['value' => 'in_progress', 'label' => 'In Progress'],
    ['value' => 'resolved', 'label' => 'Resolved'],
];
$catLegend = '';
foreach ($categories as $item) {
    $catLegend .= '
      <div class="dash-cat-item">
        <span><span class="dash-legend-dot dash-legend-dot--' . dash_esc($item['key']) . '"></span>' . dash_esc($item['label']) . '</span>
        <strong>' . dash_esc((string) $item['pct']) . '%</strong>
      </div>';
}
$densityRows = '
    <div class="dash-density-row"><span><span class="dash-legend-dot dash-legend-dot--high"></span>High density</span><strong>' . dash_esc((string) $density['high']) . '</strong></div>
    <div class="dash-density-row"><span><span class="dash-legend-dot dash-legend-dot--mid"></span>Moderate density</span><strong>' . dash_esc((string) $density['moderate']) . '</strong></div>
    <div class="dash-density-row"><span><span class="dash-legend-dot dash-legend-dot--low"></span>Low density</span><strong>' . dash_esc((string) $density['low']) . '</strong></div>';
$pendingBadge = $reportsPending['total'] ? '<span class="dash-action-badge">' . dash_esc((string) $reportsPending['total']) . '</span>' : '';

$mapCard = '
  <section class="panel" id="case-density-panel">
    <div class="dash-gis-head">
      <div>
        <p class="dash-gis-kicker">GIS Heatmap View</p>
        <p class="dash-gis-sub">Geographic distribution of animal welfare reports across Mati City.</p>
      </div>
      <div class="dash-gis-tools">
        <div class="dash-seg" role="group" aria-label="Map display">
          <button type="button" data-map-mode="markers">Markers</button>
          <button type="button" data-map-mode="heatmap" class="is-active">Heatmap</button>
        </div>
        <button type="button" class="dash-filter-btn" id="gis-filters-toggle">
          Filters <i data-lucide="chevron-down"></i>
        </button>
      </div>
    </div>
    <div class="dash-filters" id="gis-filters">
      <input class="dash-date" id="gis-date-start" type="date" aria-label="Start date">
      <input class="dash-date" id="gis-date-end" type="date" aria-label="End date">
      ' . select_control('gis-type', $typeOptions, '', 'All Report Types', '', '', 'dash-select') . '
      ' . select_control('gis-status', $statusOptions, '', 'All Status', '', '', 'dash-select') . '
      ' . button_html('Apply Filters', 'default', icon: 'filter', className: 'dash-apply', attrs: 'id="gis-apply"') . '
      <button type="button" class="dash-reset" id="gis-reset"><i data-lucide="rotate-ccw"></i> Reset</button>
    </div>
    <div class="dash-map-wrap">
      <div id="case-density-map" class="map-canvas map-canvas--leaflet"></div>
      <aside class="dash-legend">
        <p class="dash-legend-title">What does this mean?</p>
        <div class="dash-legend-item"><span class="dash-legend-dot dash-legend-dot--high"></span> High density</div>
        <div class="dash-legend-item"><span class="dash-legend-dot dash-legend-dot--mid"></span> Moderate density</div>
        <div class="dash-legend-item"><span class="dash-legend-dot dash-legend-dot--low"></span> Low density</div>
        <p class="dash-legend-note">Red areas indicate locations with a higher number of reports. Last updated: <span id="gis-updated">' . dash_esc($gisUpdated) . '</span></p>
      </aside>
    </div>
  </section>';

$gisRow = '
  <div class="dash-gis">
    <div class="dash-gis-main">' . $mapCard . '</div>
    <div class="dash-gis-side">
      <section class="panel dash-side-card">
        <h3 class="dash-side-title">Heatmap Summary</h3>
        <div id="gis-density">' . $densityRows . '</div>
      </section>
      <section class="panel dash-side-card">
        <h3 class="dash-side-title">Reports by Category</h3>
        <div class="dash-cat-wrap">
          <div class="dash-donut"><canvas id="reports-category-donut"></canvas></div>
          <div class="dash-cat-legend" id="gis-cat-legend">' . $catLegend . '</div>
        </div>
      </section>
      <section class="panel dash-side-card">
        <h3 class="dash-side-title">Quick Actions</h3>
        <div class="dash-actions">
          <a class="dash-action" href="/admin/reports/"><i data-lucide="badge-check"></i> Validate Pending Reports ' . $pendingBadge . '</a>
          <a class="dash-action" href="/admin/reports/"><i data-lucide="files"></i> View All Reports</a>
          <a class="dash-action" href="/admin/analytics/"><i data-lucide="bar-chart-3"></i> Generate Report</a>
          <button type="button" class="dash-action" id="gis-export"><i data-lucide="download"></i> Export Heatmap Data</button>
        </div>
      </section>
    </div>
  </div>';
