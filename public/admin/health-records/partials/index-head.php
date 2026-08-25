<?php

$hrTabs = '';
foreach ($hrFilters as $f) {
    $activeCls = $f['key'] === 'all' ? ' is-active' : '';
    $hrTabs .= '<button data-filter="' . e($f['key']) . '" class="q-btn' . $activeCls . '">' . e($f['label']) . ' &middot; ' . e((string) $f['count']) . '</button>';
}
// ui/button.js final tailwind-merge-resolved string (cva base incl. disabled:* groups
// which shared BTN_BASE lacks — same local-emission pattern as reports/rescuers units).
const HR_BTN_BASE = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50';
$hrExportCsvButton = '<button type="button" class="' . e(trim(HR_BTN_BASE . ' border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-4')) . '" data-export="csv"><i data-lucide="download" class="icon"></i><span>Export CSV</span></button>';

// ---- Page head + controls ---------------------------------------------------
$pageHead = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Animal Management</span>
      <h1 class="page-title">Health Records</h1>
      <p class="page-sub">Track vaccinations, checkups, conditions, and vitals across the shelter population.</p>
    </div>
    <div class="page-head-actions">
      ' . $hrExportCsvButton . '
      <button type="button" class="btn-see-animals" data-animals-open><i data-lucide="paw-print"></i><span>See animals</span></button>
    </div>
  </div>';

$controlsPanel = '
  <div class="panel hr-toolbar-panel">
    <div class="report-toolbar">
      <div class="q-tabs" id="hr-tabs">' . $hrTabs . '</div>
      <div class="report-search">
        <i data-lucide="search"></i>
        <input id="hr-search" type="text" placeholder="Search animal, barangay, condition, vet, id…" value="">
      </div>
      <div class="report-sort">
        <label for="hr-range" class="report-sort-label">Range</label>
        ' . select_control('hr-range', [
            ['value' => '30d', 'label' => 'Last 30 days'],
            ['value' => '90d', 'label' => 'Last 90 days'],
            ['value' => '12mo', 'label' => 'Last 12 months'],
        ], '30d', 'Range') . '
      </div>
    </div>
  </div>';

$trendPanel = '
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="activity"></i><h2 class="panel-title panel-title--sm">Checkups &amp; treatments</h2></div>
    <div class="hr-chart"><canvas id="hr-trend-canvas"></canvas></div>
  </div>';

$stackedToggle = ''
    . '<button class="hr-toggle-btn is-active" data-species="all">All</button>'
    . '<button class="hr-toggle-btn" data-species="dog">Dogs</button>'
    . '<button class="hr-toggle-btn" data-species="cat">Cats</button>';
$stackedPanel = '
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="bar-chart-3"></i><h2 class="panel-title panel-title--sm">Health by barangay</h2></div>
    <div class="report-sort" style="margin:8px 0 12px;"><span class="hr-toggle">' . $stackedToggle . '</span></div>
    <div class="hr-chart"><canvas id="hr-stacked-canvas"></canvas></div>
  </div>';
