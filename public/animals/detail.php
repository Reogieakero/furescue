<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\JwtService;

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

require_once dirname(__DIR__, 2) . '/views/path.php';
require views_path('components/guard.php');

$uid = (string) $_SESSION['user']['id'];

$residentUser = [
    'id' => $uid,
    'full_name' => (string) ($_SESSION['user']['full_name'] ?? ''),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => '',
];

ob_start();
require views_path('animals/detail.php');
$content = ob_get_clean();

$jwt = new JwtService();

$pageState = [
    'accessToken' => $jwt->issueAccessToken(['id' => $uid, 'role' => $residentUser['role']]),
    'user' => $residentUser,
    'animalId' => (string) ($_GET['id'] ?? ''),
];
$pageModules = ['/animals/js/animal-detail.js'];
$pageTitle = 'FurEscue — Animal profile';
$pageDescription = 'View full adoption profile, medical summary and status history.';
$pageCss = [];
$importMapExtras = [
    'three' => 'https://unpkg.com/three@0.160.0/build/three.module.js',
    'three/addons/' => 'https://unpkg.com/three@0.160.0/examples/jsm/',
];
$activeNav = 'browse animals';
$residentShellTitle = 'Animal Profile';

require views_path('layouts/resident.php');
