<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\SessionAuth;

SessionAuth::logout();
require_once dirname(__DIR__, 2) . '/views/path.php';
require views_path('auth/logout.php');
