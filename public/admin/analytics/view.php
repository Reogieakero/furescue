<?php

declare(strict_types=1);

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__)) {
    header('Location: /admin/analytics/', true, 302);
    exit;
}

require_once dirname(__DIR__, 3) . '/views/path.php';
require views_path('admin/analytics/view.php');
