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
    <div class="mx-auto w-full max-w-4xl min-w-0">
      <div class="rpage-head">
        <div class="min-w-0">
          <h1 class="rpage-title">My Cases</h1>
          <p class="rpage-sub">Accept assigned rescues, update in-progress work, and file proof when you finish.</p>
        </div>
        <div class="rpage-actions">
          <button type="button" id="refresh-cases" class="rbtn rbtn--ghost rbtn--sm">
            <i data-lucide="refresh-cw"></i><span>Refresh</span>
          </button>
        </div>
      </div>

      <div class="rtabs" role="tablist" aria-label="Filter cases by status">
        <button type="button" class="rtab is-active" data-status="" role="tab" aria-selected="true">All</button>
        <button type="button" class="rtab" data-status="assigned" role="tab" aria-selected="false">Assigned</button>
        <button type="button" class="rtab" data-status="in_progress" role="tab" aria-selected="false">In Progress</button>
        <button type="button" class="rtab" data-status="resolved" role="tab" aria-selected="false">Resolved</button>
      </div>

      <p id="cases-count" class="mt-3 text-sm text-muted-foreground" aria-live="polite"></p>
      <ul id="cases-list" class="rlist mt-2"></ul>

      <div id="cases-empty" class="rempty mt-2" hidden>
        <i data-lucide="clipboard-list"></i>
        <p class="rempty-title">No cases in this view</p>
        <p class="rempty-text">When the team assigns a rescue to you, it will show up here.</p>
      </div>
    </div>';

$jwt = new JwtService();
$pageState = [
    'accessToken' => $jwt->issueAccessToken(['id' => $uid, 'role' => $residentUser['role']]),
    'user' => $residentUser,
];
$pageModules = ['/cases/js/list.js'];
$pageTitle = 'FurEscue — My Cases';
$pageDescription = 'Review and act on rescue cases assigned to you in Mati City.';
$activeNav = 'my cases';
$residentShellTitle = 'My Cases';

require __DIR__ . '/../includes/resident-shell.php';
