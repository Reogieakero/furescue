<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\JwtService;
use App\Database;

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

$requiredRole = ['resident', 'rescuer', 'admin'];
require_once dirname(__DIR__, 2) . '/views/path.php';
require views_path('components/guard.php');

$uid = (string) $_SESSION['user']['id'];
$pdo = Database::connect();
$userData = (new \App\Repositories\UserRepository($pdo))->find($uid);
$userData = $userData ? $userData->toArray() : [];

$bounds = [
    'latMin' => (float) Database::env('MATI_LAT_MIN', 6.89),
    'latMax' => (float) Database::env('MATI_LAT_MAX', 7.01),
    'lngMin' => (float) Database::env('MATI_LNG_MIN', 126.13),
    'lngMax' => (float) Database::env('MATI_LNG_MAX', 126.27),
];

ob_start();
require views_path('report/index.php');
$content = ob_get_clean();

$residentUser = [
    'id' => $uid,
    'full_name' => (string) ($userData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($userData['profile_photo_url'] ?? ''),
];
$activeNav = 'report animal';
$residentShellTitle = 'Report an animal';
$jwt = new JwtService();
$pageState = [
    'accessToken' => $jwt->issueAccessToken(['id' => $uid, 'role' => $residentUser['role']]),
    'user' => $residentUser,
    'bounds' => $bounds,
];
$pageScripts = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'];
$pageModules = ['/report/js/report.js'];

$pageTitle = 'FurEscue — Report an animal';
$pageDescription = 'Report a stray animal in Mati City — pin the location, describe the situation and attach photos.';
$pageCss = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'];

require views_path('layouts/resident.php');
