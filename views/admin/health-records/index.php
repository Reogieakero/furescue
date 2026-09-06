<?php

declare(strict_types=1);

require views_path('admin/health-records/partials/index-kpis.php');
require views_path('admin/health-records/partials/index-charts.php');
require views_path('admin/health-records/partials/index-queue.php');
require views_path('admin/health-records/partials/index-table.php');
require views_path('admin/health-records/partials/index-head.php');

$adminChildren = '<div class="hr-list">'
    . $pageHead . "\n"
    . "<div id=\"hr-kpis\">{$hrKpisHtml}</div>\n"
    . $controlsPanel . "\n"
    . "<div class=\"cols cols--vax\"><div id=\"hr-vax-dog\">{$dogCard}</div><div id=\"hr-vax-cat\">{$catCard}</div><div id=\"hr-conditions\">{$conditionsPanel}</div></div>\n"
    . "<div id=\"hr-trend\">{$trendPanel}</div>\n"
    . "<div class=\"cols cols--two hr-split-row\"><div id=\"hr-stacked\">{$stackedPanel}</div><div id=\"hr-queue\">{$queuePanel}</div></div>\n"
    . "<div id=\"hr-records\">{$recordsPanel}</div>"
    . '</div>';
