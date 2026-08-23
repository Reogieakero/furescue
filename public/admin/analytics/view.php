<?php

declare(strict_types=1);

/**
 * Page-local partial for /admin/analytics/ — renders $adminChildren.
 * Consumes (from index.php scope): $start, $end, $ranged, $overviewRows,
 * $trends, $updates, plus shared ui-helpers.
 */

$today = date('Y-m-d');

$btnOverview = button_html('Export Overview CSV', 'outline', icon: 'download', attrs: 'id="export-overview" data-export="overview"');
$btnTrends = button_html('Export Adoption Trends CSV', 'outline', icon: 'download', attrs: 'id="export-trends" data-export="adoption-trends"');
$btnHealth = button_html('Export Health CSV', 'outline', icon: 'download', attrs: 'id="export-health" data-export="health-updates"');

$rangeLabel = $ranged ? "{$start} to {$end}" : 'Last 30 adoption days · 50 latest health updates';
?>
  <div class="page-head">
    <div>
      <span class="stamp stamp--jungle">Analytics</span>
      <h1 class="page-title">Analytics &amp; exports</h1>
      <p class="page-sub">Shelter-wide metrics for City of Mati &middot; Filter: <span id="range-label"><?= e($rangeLabel) ?></span></p>
    </div>
    <div class="page-head-actions analytics-actions">
      <?= $btnOverview ?>
      <?= $btnTrends ?>
      <?= $btnHealth ?>
    </div>
  </div>
<?= kpi_grid_html([
    ['icon' => 'map-pin', 'value' => overview_value($overviewRows, 'reports'), 'label' => 'Total reports'],
    ['icon' => 'badge-check', 'value' => overview_value($overviewRows, 'reports_verified'), 'label' => 'Reports verified'],
    ['icon' => 'check-circle-2', 'value' => overview_value($overviewRows, 'cases_resolved'), 'label' => 'Cases resolved', 'dark' => true],
    ['icon' => 'home', 'value' => overview_value($overviewRows, 'animals_adopted'), 'label' => 'Animals adopted'],
    ['icon' => 'paw-print', 'value' => overview_value($overviewRows, 'adoptions_pending'), 'label' => 'Adoptions pending', 'note' => overview_value($overviewRows, 'adoptions_pending') ? ['text' => 'Needs Review', 'cls' => 'kpi-note--coral'] : null],
    ['icon' => 'siren', 'value' => overview_value($overviewRows, 'rescuers_on_duty'), 'label' => 'Rescuers on duty'],
]) ?>

  <div class="panel panel--padded analytics-range">
    <div class="panel-title-wrap"><i data-lucide="calendar-range"></i><h2 class="panel-title panel-title--sm">Date range</h2></div>
    <div class="range-controls">
      <label class="dialog-label">From
        <input type="date" id="range-start" class="dialog-input" value="<?= e($start) ?>" max="<?= e($today) ?>" autocomplete="off">
      </label>
      <label class="dialog-label">To
        <input type="date" id="range-end" class="dialog-input" value="<?= e($end) ?>" max="<?= e($today) ?>" autocomplete="off">
      </label>
      <div class="range-actions">
        <?= button_html('Apply range', 'default', icon: 'check', attrs: 'id="range-apply"') ?>
        <?= button_html('Reset', 'ghost', attrs: 'id="range-reset"') ?>
      </div>
    </div>
    <p class="range-note">Range filters adoption completions and health updates. Overview metrics are shelter-wide totals.</p>
  </div>

  <div class="cols cols--two">
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title-wrap"><i data-lucide="bar-chart-3"></i><h2 class="panel-title panel-title--sm">Overview metrics</h2></div>
        <a href="#" class="btn-link" data-export="overview">Download CSV <?= chevron_right() ?></a>
      </div>
<?php if ($overviewRows === []): ?>
      <div id="table-overview" class="queue-empty"><?= empty_state('inbox', 'No records.') ?></div>
<?php else: ?>
      <div id="table-overview" class="table-wrap">
        <table class="table">
          <?= table_head(['Metric', 'Value']) ?>
          <tbody id="tbody-overview"><?= overview_rows_html($overviewRows) ?></tbody>
        </table>
      </div>
<?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div class="panel-title-wrap"><i data-lucide="trending-up"></i><h2 class="panel-title panel-title--sm">Adoption trends</h2></div>
        <a href="#" class="btn-link" data-export="adoption-trends">Download CSV <?= chevron_right() ?></a>
      </div>
<?php if ($trends === []): ?>
      <div id="table-trends" class="queue-empty"><?= empty_state('bar-chart-3', 'No completed adoptions in this range.') ?></div>
<?php else: ?>
      <div id="table-trends" class="table-wrap">
        <table class="table">
          <?= table_head(['Day', 'Completed adoptions']) ?>
          <tbody id="tbody-trends"><?= trend_rows_html($trends) ?></tbody>
        </table>
      </div>
<?php endif; ?>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="heart-pulse"></i><h2 class="panel-title panel-title--sm">Health updates</h2></div>
      <a href="#" class="btn-link" data-export="health-updates">Download CSV <?= chevron_right() ?></a>
    </div>
<?php if ($updates === []): ?>
    <div id="table-health" class="queue-empty"><?= empty_state('heart-pulse', 'No health updates in this range.') ?></div>
<?php else: ?>
    <div id="table-health" class="table-wrap">
      <table class="table">
        <?= table_head(['Update', 'Animal', 'Logged by', 'Status', 'When']) ?>
        <tbody id="tbody-health"><?= health_rows_html(array_map('health_update_row', $updates)) ?></tbody>
      </table>
    </div>
<?php endif; ?>
  </div>
