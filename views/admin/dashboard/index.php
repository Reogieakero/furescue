<?php

declare(strict_types=1);

$greetingNameEsc = e($greetingName);
$btnExport = button_anchor_html('/admin/analytics/', 'Export Report', 'outline', icon: 'download');
$btnAnnouncement = button_html('New Announcement', 'default', icon: 'megaphone', attrs: 'id="announce-btn"');
$greeting = <<<HTML
  <div class="greeting">
    <div>
      <p class="dash-kicker">Command Center</p>
      <h1 class="greeting-title">Good morning, {$greetingNameEsc}</h1>
      <p class="greeting-sub" id="greeting-sub">{$decisionCount} items need a decision today across reports, rescuers, health records, and adoptions.</p>
    </div>
    <div class="greeting-actions">
      {$btnExport}
      {$btnAnnouncement}
    </div>
  </div>
HTML;

$inProgressCount = (int) ($overview['cases_in_progress'] ?? 0);
$resolvedCount = (int) ($overview['cases_resolved'] ?? $resolvedCases);
$onDutyCount = (int) ($overview['rescuers_on_duty'] ?? 0);
$activeRescuerCount = (int) ($overview['rescuers_active'] ?? 0);
$dutyTrend = [
    'text' => $activeRescuerCount > 0
        ? ($onDutyCount . ' of ' . $activeRescuerCount . ' active')
        : 'No active rescuers',
    'tone' => $onDutyCount > 0 ? 'up' : 'neutral',
];
$kpiData = [
    ['icon' => 'folder-kanban', 'tone' => 'jungle', 'value' => $reportsTotal, 'label' => 'Total Reports', 'trend' => dash_trend_label((int) ($overview['reports_today'] ?? 0))],
    ['icon' => 'file-warning', 'tone' => 'coral', 'value' => $reportsPending['total'], 'label' => 'Pending Reports', 'trend' => dash_trend_label((int) ($overview['pending_today'] ?? 0))],
    ['icon' => 'refresh-cw', 'tone' => 'sky', 'value' => $inProgressCount, 'label' => 'In Progress', 'trend' => dash_trend_label((int) ($overview['in_progress_today'] ?? 0))],
    ['icon' => 'check-circle-2', 'tone' => 'amber', 'value' => $resolvedCount, 'label' => 'Resolved', 'trend' => dash_trend_label((int) ($overview['resolved_today'] ?? 0))],
    ['icon' => 'siren', 'tone' => 'sky', 'value' => $onDutyCount, 'label' => 'Rescuers on duty', 'trend' => $dutyTrend, 'href' => '/admin/rescuers/'],
];
$kpiTiles = [];
foreach ($kpiData as $k) {
    $kpiTiles[] = [
        'icon' => $k['icon'],
        'tone' => $k['tone'],
        'value' => $k['value'],
        'label' => $k['label'],
        'trend' => (string) ($k['trend']['text'] ?? ''),
        'trendTone' => (string) ($k['trend']['tone'] ?? 'neutral'),
        'href' => (string) ($k['href'] ?? ''),
    ];
}
$kpiGrid = kpi_grid_html($kpiTiles, 'kpi-grid');

require views_path('admin/dashboard/partials/queues.php');
require views_path('admin/dashboard/partials/cards.php');
require views_path('admin/dashboard/partials/gis.php');
require views_path('admin/dashboard/partials/recent-reports.php');
require views_path('admin/dashboard/partials/health-overview.php');
require views_path('admin/dashboard/partials/activity.php');

$children = '<div class="dash">' . $greeting . "\n" . $kpiGrid . "\n" . $dashboardSections . '</div>';

$currentUserData = $currentUser ? $currentUser->toArray() : [];
$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($currentUserData['profile_photo_url'] ?? ''),
];
$activeNav = 'dashboard';
$navBadges = [
    'reports' => $reportsTotal,
    'health' => $healthUpdatesState['total'],
    'applications' => $adoptionsPending['total'],
];
$adminChildren = $children;
require views_path('layouts/admin.php');
