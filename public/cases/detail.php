<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\JwtService;
use App\Database;

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

$requiredRole = 'rescuer';
require_once dirname(__DIR__, 2) . '/views/path.php';
require views_path('components/guard.php');

$uid = (string) $_SESSION['user']['id'];
$pdo = Database::connect();
$userData = (new \App\Repositories\UserRepository($pdo))->find($uid);
$userData = $userData ? $userData->toArray() : [];

$residentUser = [
    'id' => $uid,
    'full_name' => (string) ($userData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($userData['profile_photo_url'] ?? ''),
];

ob_start();
require views_path('cases/detail.php');
$content = ob_get_clean();

$jwt = new JwtService();
$pageState = [
    'accessToken' => $jwt->issueAccessToken(['id' => $uid, 'role' => $residentUser['role']]),
    'user' => $residentUser,
    'caseId' => (string) ($_GET['id'] ?? ''),
];
$pageModules = ['/cases/js/detail.js'];
$pageTitle = 'FurEscue — Case detail';
$pageDescription = 'Accept, decline, or file rescue proof for an assigned case.';
$activeNav = 'my cases';
$residentShellTitle = 'Case detail';

require views_path('layouts/resident.php');
