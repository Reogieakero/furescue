<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/views/path.php';
require views_path('admin/dashboard/data.php');

ob_start();
require views_path('admin/dashboard/index.php');
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Admin Command Center';
$pageDescription = 'FurEscue admin command center — reports, cases, rescuers, health records, and adoptions for City of Mati.';
$pageCss = [
    '/admin/css/admin.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Nunito:wght@400;500;600;700;800&display=swap';
$importMapExtras = ['chart.js' => 'https://esm.sh/chart.js@4.4.4/auto'];
require views_path('components/site-head.php');
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script type="module" src="js/dashboard.js"></script>
  </body>
</html>
