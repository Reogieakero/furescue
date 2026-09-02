<?php

declare(strict_types=1);

if (!function_exists('views_path')) {
    require_once dirname(__DIR__, 4) . '/views/path.php';
}
require views_path('admin/applications/partials/content.php');
