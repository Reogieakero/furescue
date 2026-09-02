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
$kpiData = [
    ['icon' => 'folder-kanban', 'tone' => 'jungle', 'value' => $reportsTotal, 'label' => 'Total Reports', 'trend' => dash_trend_label((int) ($overview['reports_today'] ?? 0))],
    ['icon' => 'file-warning', 'tone' => 'coral', 'value' => $reportsPending['total'], 'label' => 'Pending Reports', 'trend' => dash_trend_label((int) ($overview['pending_today'] ?? 0))],
    ['icon' => 'refresh-cw', 'tone' => 'sky', 'value' => $inProgressCount, 'label' => 'In Progress', 'trend' => dash_trend_label((int) ($overview['in_progress_today'] ?? 0))],
    ['icon' => 'check-circle-2', 'tone' => 'amber', 'value' => $resolvedCount, 'label' => 'Resolved', 'trend' => dash_trend_label((int) ($overview['resolved_today'] ?? 0))],
];
$kpiTiles = '';
foreach ($kpiData as $k) {
    $trendTone = $k['trend']['tone'] ?? 'neutral';
    $aria = $k['label'] . ': ' . (string) $k['value'];
    $kpiTiles .= '
  <article class="kpi-card" aria-label="' . e($aria) . '">
    <div class="kpi-card__icon kpi-card__icon--' . e($k['tone']) . '" aria-hidden="true"><i data-lucide="' . e($k['icon']) . '"></i></div>
    <div class="kpi-card__body">
      <p class="kpi-card__label">' . e($k['label']) . '</p>
      <p class="kpi-card__value">' . e((string) $k['value']) . '</p>
      <p class="kpi-card__trend kpi-card__trend--' . e($trendTone) . '">' . e($k['trend']['text']) . '</p>
    </div>
  </article>';
}
$kpiGrid = "<div class=\"kpi-grid\" id=\"kpi-grid\">{$kpiTiles}</div>";

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
