<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\SessionAuth;

SessionAuth::logout();
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Location: /auth/login.php', true, 302);
require_once dirname(__DIR__, 2) . '/views/path.php';
require views_path('auth/logout.php');
