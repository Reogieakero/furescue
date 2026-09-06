<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\JwtService;

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

require_once dirname(__DIR__, 2) . '/views/path.php';
require views_path('components/guard.php');

$sessionUser = $_SESSION['user'] ?? [];
$residentUser = [
    'id' => (string) ($sessionUser['id'] ?? ''),
    'full_name' => (string) ($sessionUser['full_name'] ?? ''),
    'email' => (string) ($sessionUser['email'] ?? ''),
    'role' => (string) ($sessionUser['role'] ?? ''),
    'profile_photo_url' => (string) ($sessionUser['profile_photo_url'] ?? ''),
];
$pageState = [
    'accessToken' => (new JwtService())->issueAccessToken([
        'id' => $residentUser['id'],
        'role' => $residentUser['role'],
    ]),
    'user' => $residentUser,
];

$activeNav = 'messages';
$navBadges = [];
$residentShellTitle = 'Messages';

ob_start();
require views_path('messages/index.php');
$content = ob_get_clean();

$pageModules = ['/messages/js/messages.js'];
$pageTitle = 'FurEscue — Messages';
$pageDescription = 'Message rescuers and administrators about your animal reports, rescue cases, and adoption applications.';
$pageCss = ['/messages/css/messages.css'];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';

require views_path('layouts/resident.php');
