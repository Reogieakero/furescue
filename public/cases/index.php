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

$dutyStatus = 'off_duty';
try {
    $dutyStmt = $pdo->prepare('SELECT status FROM rescuer_duty_status WHERE user_id = ? LIMIT 1');
    $dutyStmt->execute([$uid]);
    $dutyRow = $dutyStmt->fetchColumn();
    if ($dutyRow === 'on_duty' || $dutyRow === 'off_duty') {
        $dutyStatus = $dutyRow;
    }
} catch (Throwable $e) {
    $dutyStatus = 'off_duty';
}

$residentUser = [
    'id' => $uid,
    'full_name' => (string) ($userData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($userData['profile_photo_url'] ?? ''),
];

ob_start();
require views_path('cases/index.php');
$content = ob_get_clean();

$jwt = new JwtService();
$pageState = [
    'accessToken' => $jwt->issueAccessToken(['id' => $uid, 'role' => $residentUser['role']]),
    'user' => $residentUser,
    'dutyStatus' => $dutyStatus,
];
$pageModules = ['/cases/js/list.js'];
$pageTitle = 'FurEscue — My Cases';
$pageDescription = 'Review and act on rescue cases assigned to you in Mati City.';
$activeNav = 'my cases';
$residentShellTitle = 'My Cases';

require views_path('layouts/resident.php');
