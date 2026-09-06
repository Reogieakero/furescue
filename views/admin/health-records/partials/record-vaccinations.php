<?php

$vaxVisible = $record['vaccinations'] ?? [];
$vaxTableRows = '';
foreach ($vaxVisible as $i => $v) {
    $vaxTableRows .= '
    <tr>
      <td class="table-cell table-cell--strong">' . e($v['vaccine']) . '</td>
      <td class="table-cell table-cell--mono">' . e($v['dateGiven'] ?? '—') . '</td>
      <td class="table-cell table-cell--mono">Dose ' . e($v['doseNumber'] ?? '—') . '</td>
      <td class="table-cell table-cell--mono">' . e($v['nextDue'] ?? '—') . '</td>
      <td class="table-cell table-cell--right">' . $vaxStatusPill($v['status'] ?? null) . '</td>
    </tr>';
}

$vaxPanelHtml = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="syringe"></i><h3 class="panel-title">Vaccination Record</h3></div>
    </div>
    <div class="panel-body">
      ' . ($vaxTableRows !== '' ? '<div class="table-wrap"><table class="table"><thead class="table-head"><tr><th>Vaccine</th><th>Date Given</th><th>Dose</th><th>Next Due</th><th class="table-cell--right">Status</th></tr></thead><tbody>' . $vaxTableRows . '</tbody></table></div>' : $emptyState('No vaccinations recorded')) . '
    </div>
  </section>';
