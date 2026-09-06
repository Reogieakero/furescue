<?php

declare(strict_types=1);

require views_path('admin/health-records/partials/record-head.php');
require views_path('admin/health-records/partials/record-profile.php');
require views_path('admin/health-records/partials/record-overview.php');
require views_path('admin/health-records/partials/record-history.php');
require views_path('admin/health-records/partials/record-vaccinations.php');
require views_path('admin/health-records/partials/record-reminders.php');
require views_path('admin/health-records/partials/record-vitals.php');
require views_path('admin/health-records/partials/record-documents.php');
require views_path('admin/health-records/partials/record-stats.php');

$adminChildren = $pageHeadHtml
    . '<div class="hr-grid">' . $profilePanelHtml . $overviewPanelHtml . '</div>'
    . '<div class="hr-trio">
        ' . $historyPanelHtml . '
        <div class="hr-trio-col">' . $vaxPanelHtml . $remindersPanelHtml . '</div>
        <div class="hr-trio-col">' . $vitalsPanelHtml . $documentsPanelHtml . '</div>
      </div>'
    . $statsPanelHtml;
