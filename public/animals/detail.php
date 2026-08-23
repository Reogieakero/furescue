<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\JwtService;

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

require __DIR__ . '/../includes/guard.php';

$uid = (string) $_SESSION['user']['id'];

$residentUser = [
    'id' => $uid,
    'full_name' => (string) ($_SESSION['user']['full_name'] ?? ''),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => '',
];

$content = '
    <div class="mx-auto w-full max-w-6xl">
      <p><a href="/animals/" class="inline-flex items-center gap-1.5 text-sm font-bold text-muted-foreground hover:text-primary"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to gallery</a></p>
      <div id="detail-root" aria-live="polite">
        <div class="rempty">
          <i data-lucide="loader-circle"></i>
          <p class="rempty-text">Loading profile…</p>
        </div>
      </div>
    </div>';

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

require __DIR__ . '/../includes/resident-shell.php';
