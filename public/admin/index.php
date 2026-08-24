<?php

declare(strict_types=1);

require __DIR__ . '/includes/dashboard-data.php';

$greetingNameEsc = e($greetingName);
$btnExport = button_anchor_html('/admin/analytics/', 'Export Report', 'outline', icon: 'download');
$btnAnnouncement = button_html('New Announcement', 'default', icon: 'megaphone', attrs: 'id="announce-btn"');
$greeting = <<<HTML
  <div class="greeting">
    <div>
      <span class="stamp stamp--coral">Command Center</span>
      <h1 class="greeting-title">Good morning, {$greetingNameEsc}</h1>
      <p class="greeting-sub" id="greeting-sub">{$decisionCount} items need a decision today across reports, rescuers, health records, and adoptions.</p>
    </div>
    <div class="greeting-actions">
      {$btnExport}
      {$btnAnnouncement}
    </div>
  </div>
HTML;

$kpiTiles = '';
$kpiData = [
    ['icon' => 'map-pin', 'value' => $reportsTotal, 'label' => 'Total reports', 'note' => null],
    ['icon' => 'badge-check', 'value' => $reportsPending['total'], 'label' => 'Pending verify', 'note' => $reportsPending['total'] ? ['text' => 'Needs You', 'cls' => 'kpi-note--coral'] : null],
    ['icon' => 'siren', 'value' => count($onDutyRescuers), 'label' => 'Rescuers on duty', 'note' => null],
    ['icon' => 'heart-pulse', 'value' => $healthUpdatesState['total'], 'label' => 'Health updates', 'note' => $healthUpdatesState['total'] ? ['text' => 'Recent', 'cls' => 'kpi-note--muted'] : null],
    ['icon' => 'home', 'value' => $adoptionsPending['total'], 'label' => 'Pending adoptions', 'note' => null],
    ['icon' => 'check-circle-2', 'value' => $resolvedCases, 'label' => 'Resolved cases', 'dark' => true],
];
foreach ($kpiData as $k) {
    $note = '';
    if (!empty($k['note'])) {
        $note = '<span class="kpi-note ' . e($k['note']['cls']) . '">' . e($k['note']['text']) . '</span>';
    }
    $tileCls = 'kpi-tile' . (!empty($k['dark']) ? ' kpi-tile--dark' : '');
    $kpiTiles .= "
  <div class=\"{$tileCls}\">
    <div class=\"kpi-top\">
      <div class=\"kpi-icon\"><i data-lucide=\"{$k['icon']}\"></i></div>
      {$note}
    </div>
    <div class=\"kpi-value\">" . e($k['value']) . "</div>
    <div class=\"kpi-label\">" . e($k['label']) . '</div>
  </div>';
}
$kpiGrid = "<div class=\"kpi-grid\" id=\"kpi-grid\">{$kpiTiles}</div>";

require __DIR__ . '/partials/queues.php';
require __DIR__ . '/partials/cards.php';
require __DIR__ . '/partials/activity.php';

$children = $greeting . "\n" . $kpiGrid . "\n" . $attentionRow . "\n" . $dashboardSections;

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
ob_start();
require __DIR__ . '/../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Admin Command Center';
$pageDescription = 'FurEscue admin command center — reports, cases, rescuers, health records, and adoptions for City of Mati.';
$pageCss = [
    '/admin/css/admin.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
require __DIR__ . '/../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script type="module" src="js/dashboard.js"></script>
  </body>
</html>
