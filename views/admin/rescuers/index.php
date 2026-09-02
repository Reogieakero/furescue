<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $rescuerRowsAll */
/** @var list<array<string, mixed>> $pendingItems */

$activeRows = array_values(array_filter($rescuerRowsAll, static fn($r) => ($r['account_status'] ?? '') === 'active'));
$dutyOf = static fn(array $r): string => !empty($r['duty_status']) ? (string) $r['duty_status'] : 'off_duty';
$onDutyCount = count(array_filter($activeRows, static fn($r) => $dutyOf($r) === 'on_duty'));
$suspendedCount = count(array_filter($rescuerRowsAll, static fn($r) => ($r['account_status'] ?? '') === 'suspended'));
$pendingCount = count($pendingItems);
$totalCount = count($rescuerRowsAll) + $pendingCount;

$rescuersButton = static function (
    string $text,
    string $variant = 'default',
    string $size = 'default',
    string $icon = '',
    string $attrs = ''
): string {
    $variantCls = match ($variant) {
        'outline' => BTN_VARIANT_OUTLINE,
        'destructive' => 'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90',
        default => BTN_VARIANT_DEFAULT,
    };
    $sizeCls = $size === 'sm' ? BTN_SIZE_SM : BTN_SIZE_DEFAULT;
    $cls = trim(BTN_BASE . ' ' . $variantCls . ' ' . $sizeCls);
    $inner = ($icon !== '' ? '<i data-lucide="' . e($icon) . '" class="icon"></i>' : '') . '<span>' . e($text) . '</span>';
    return '<button type="button" class="' . e($cls) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . $inner . '</button>';
};

$pageHead = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Rescue Management</span>
      <h1 class="page-title">Rescuers</h1>
      <p class="page-sub">Review duty status, suspend or activate rescuers, and decide applications.</p>
    </div>
    <div class="page-head-actions">
      ' . $rescuersButton('Export CSV', 'outline', 'default', 'download', 'data-export="csv"') . '
    </div>
  </div>';

$kpiTiles = '';
$kpiData = [
    ['icon' => 'users', 'value' => $totalCount, 'label' => 'Total rescuers', 'tone' => 'jungle', 'trend' => '', 'trendTone' => 'neutral'],
    ['icon' => 'badge-check', 'value' => count($activeRows), 'label' => 'Active', 'tone' => 'jungle', 'trend' => '', 'trendTone' => 'neutral'],
    [
        'icon' => 'siren',
        'value' => $onDutyCount,
        'label' => 'On duty',
        'tone' => 'sky',
        'trend' => $onDutyCount > 0 ? 'On duty' : '',
        'trendTone' => 'up',
    ],
    [
        'icon' => 'clock',
        'value' => $pendingCount,
        'label' => 'Pending',
        'tone' => 'coral',
        'trend' => $pendingCount > 0 ? 'Needs You' : '',
        'trendTone' => 'down',
    ],
    ['icon' => 'slash', 'value' => $suspendedCount, 'label' => 'Suspended', 'tone' => 'amber', 'trend' => '', 'trendTone' => 'neutral'],
];
foreach ($kpiData as $k) {
    $label = (string) $k['label'];
    $value = (string) $k['value'];
    $aria = $label . ': ' . $value;
    $trend = (string) ($k['trend'] ?? '');
    $trendHtml = $trend !== ''
        ? '<p class="kpi-card__trend kpi-card__trend--' . e((string) ($k['trendTone'] ?? 'neutral')) . '">' . e($trend) . '</p>'
        : '';
    $kpiTiles .= '
  <article class="kpi-card" aria-label="' . e($aria) . '">
    <div class="kpi-card__icon kpi-card__icon--' . e((string) $k['tone']) . '"><i data-lucide="' . e((string) $k['icon']) . '"></i></div>
    <div class="kpi-card__body">
      <p class="kpi-card__label">' . e($label) . '</p>
      <p class="kpi-card__value">' . e($value) . '</p>
      ' . $trendHtml . '
    </div>
  </article>';
}
$kpiGrid = '<div id="rescuer-kpis" class="kpi-grid">' . $kpiTiles . '</div>';

$filterDefs = [
    ['key' => 'all', 'label' => 'All', 'count' => $totalCount],
    ['key' => 'active', 'label' => 'Active', 'count' => count($activeRows)],
    ['key' => 'on_duty', 'label' => 'On duty', 'count' => $onDutyCount],
    ['key' => 'off_duty', 'label' => 'Off duty', 'count' => count($activeRows) - $onDutyCount],
    ['key' => 'pending', 'label' => 'Pending', 'count' => $pendingCount],
];
$tabButtons = '';
foreach ($filterDefs as $f) {
    $activeCls = $f['key'] === 'all' ? ' is-active' : '';
    $tabButtons .= '
        <button data-filter="' . e($f['key']) . '" class="q-btn' . $activeCls . '">' . e($f['label']) . ' &middot; ' . e($f['count']) . '</button>';
}
$filterTabs = '
  <div class="report-toolbar">
    <div class="q-tabs" id="rescuer-tabs">' . $tabButtons . '
    </div>
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="rescuer-search" type="text" placeholder="Search name, email, phone…" value="">
    </div>
  </div>';

const RESCUERS_PAGE_SIZE = 10;
$pagedRows = array_slice($rescuerRowsAll, 0, RESCUERS_PAGE_SIZE);

$rescuerRowHtml = static function (array $r) use ($rescuersButton, $dutyOf): string {
    $duty = $dutyOf($r);
    $isSuspended = ($r['account_status'] ?? '') === 'suspended';
    $id = e((string) ($r['id'] ?? ''));
    $toggle = $isSuspended
        ? $rescuersButton('Activate', 'outline', 'sm', 'user-check', 'data-action="activate" data-id="' . $id . '"')
        : $rescuersButton('Suspend', 'destructive', 'sm', '', 'data-action="suspend" data-id="' . $id . '"');
    return '
    <tr data-id="' . $id . '">
      <td class="table-cell table-cell--strong">' . e(($r['full_name'] ?? null) ?: 'Unnamed') . '</td>
      <td class="table-cell">' . e(($r['email'] ?? null) ?: '—') . '</td>
      <td class="table-cell table-cell--mono">' . e(($r['phone_number'] ?? null) ?: '—') . '</td>
      <td class="table-cell"><span class="stamp stamp--sm ' . e($duty === 'on_duty' ? 'stamp--accent' : 'stamp--muted') . '">' . e($duty === 'on_duty' ? 'On duty' : 'Off duty') . '</span></td>
      <td class="table-cell">' . e(time_ago($r['created_at'] ?? null)) . '</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">' . $toggle . '</span>
      </td>
    </tr>';
};

if ($pagedRows === []) {
    $tableInner = '<div class="queue-empty"><div class="empty-state"><i data-lucide="siren"></i><span>No rescuers match.</span></div></div>';
} else {
    $rowsHtml = '';
    foreach ($pagedRows as $r) {
        $rowsHtml .= $rescuerRowHtml($r);
    }
    $pagination = count($rescuerRowsAll) > RESCUERS_PAGE_SIZE
        ? '<div class="queue-pagination">' . pagination_bar(count($rescuerRowsAll), RESCUERS_PAGE_SIZE, 1) . '</div>'
        : '';
    $tableInner = '
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Rescuer</th><th>Email</th><th>Phone</th><th>Duty</th><th>Joined</th><th class="table-cell--right">Action</th>
          </tr>
        </thead>
        <tbody>' . $rowsHtml . '</tbody>
      </table>
    </div>
    ' . $pagination;
}

$rescuersPanel = '
  <div class="panel rescuer-record-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="siren"></i>
        <h2 class="panel-title">Rescuers</h2>
      </div>
    </div>
    <div id="rescuer-filters">' . $filterTabs . '</div>
    <div id="rescuer-table" class="panel-body">' . $tableInner . '</div>
  </div>';

$rescuerDetail = '<div id="rescuer-detail" class="panel rescuer-detail-panel"><div class="rescuer-detail-empty"><i data-lucide="user-round-search"></i><span>Select a rescuer to view their profile and past cases.</span></div></div>';

$adminChildren = $pageHead . "\n" . $kpiGrid . "\n" . '<div class="rescuer-split">' . "\n" . $rescuersPanel . "\n" . $rescuerDetail . "\n" . '</div>';
