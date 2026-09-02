<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\JwtService;
use App\Database;
use App\Repositories\ReportRepository;

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

$requiredRole = ['resident', 'rescuer', 'admin'];
require_once dirname(__DIR__, 2) . '/views/path.php';
require views_path('components/guard.php');

$uid = (string) $_SESSION['user']['id'];
$pdo = Database::connect();
$userData = (new \App\Repositories\UserRepository($pdo))->find($uid);
$userData = $userData ? $userData->toArray() : [];

$reportRepo = new ReportRepository($pdo);
$listError = null;
try {
    $result = $reportRepo->paginate(1, 50, ['resident_id' => $uid]);
    $reports = array_map(static fn($r) => $r->toArray(), $result['items']);
} catch (Throwable $e) {
    $reports = [];
    $listError = 'Could not load your reports. Please try again.';
}

ob_start();
require views_path('reports/index.php');
$content = ob_get_clean();

$residentUser = [
    'id' => $uid,
    'full_name' => (string) ($userData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($userData['profile_photo_url'] ?? ''),
];
$activeNav = 'my reports';
$residentShellTitle = 'My Reports';
$jwt = new JwtService();
$pageState = [
    'accessToken' => $jwt->issueAccessToken(['id' => $uid, 'role' => $residentUser['role']]),
    'user' => $residentUser,
];
$pageModules = ['/reports/js/reports.js'];

$pageTitle = 'FurEscue — My Reports';
$pageDescription = 'Track the stray animal reports you submitted to FurEscue in Mati City.';

require views_path('layouts/resident.php');
