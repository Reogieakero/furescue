<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\JwtService;
use App\Database;

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

$requiredRole = 'rescuer';
require __DIR__ . '/../includes/guard.php';

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

$content = '
    <div class="mx-auto w-full max-w-5xl min-w-0">
      <p>
        <a href="/cases/" class="inline-flex items-center gap-1.5 text-sm font-bold text-muted-foreground hover:text-primary">
          <i data-lucide="arrow-left" class="h-4 w-4"></i>Back to My Cases
        </a>
      </p>
      <div id="case-detail-root" class="mt-3" aria-live="polite">
        <div class="rempty">
          <i data-lucide="loader-circle"></i>
          <p class="rempty-text">Loading case…</p>
        </div>
      </div>
    </div>';

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

require __DIR__ . '/../includes/resident-shell.php';
