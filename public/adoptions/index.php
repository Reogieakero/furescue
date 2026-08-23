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
    <div class="mx-auto w-full max-w-4xl">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 class="rpage-title">My Adoptions</h1>
          <p class="rpage-sub">Track the applications you have submitted.</p>
        </div>
        <a href="/animals/" class="rbtn rbtn--ghost"><i data-lucide="paw-print"></i><span>Browse animals</span></a>
      </div>

      <div class="rtabs mt-5" role="tablist" aria-label="Filter applications by status">
        <button type="button" class="rtab is-active" data-status="" role="tab" aria-selected="true">All</button>
        <button type="button" class="rtab" data-status="pending" role="tab" aria-selected="false">Pending</button>
        <button type="button" class="rtab" data-status="approved" role="tab" aria-selected="false">Approved</button>
        <button type="button" class="rtab" data-status="rejected" role="tab" aria-selected="false">Rejected</button>
        <button type="button" class="rtab" data-status="completed" role="tab" aria-selected="false">Completed</button>
        <button type="button" class="rtab" data-status="cancelled" role="tab" aria-selected="false">Cancelled</button>
      </div>

      <ul class="rlist mt-4" id="adoption-list"></ul>

      <div id="adoptions-empty" class="rempty mt-2" hidden>
        <i data-lucide="file-heart"></i>
        <p class="rempty-title">No applications here yet</p>
        <p class="rempty-text">When you apply to adopt an animal, it will show up in this list.</p>
        <a href="/animals/" class="rbtn rbtn--solid"><i data-lucide="search"></i><span>Find a friend</span></a>
        <p class="text-sm text-muted-foreground">Looking to rehome a rescued animal instead?
          <a href="/listings/" class="underline text-primary font-semibold">Post an adoption listing</a>.</p>
      </div>
    </div>';

$jwt = new JwtService();

$pageState = [
    'accessToken' => $jwt->issueAccessToken(['id' => $uid, 'role' => $residentUser['role']]),
    'user' => $residentUser,
    'applyAnimalId' => (string) ($_GET['apply'] ?? ''),
];
$pageModules = ['/adoptions/js/adoptions.js'];
$pageTitle = 'FurEscue — My Adoptions';
$pageDescription = 'Track your pet adoption applications.';
$pageCss = [];
$activeNav = 'my adoptions';
$residentShellTitle = 'My Adoptions';

require __DIR__ . '/../includes/resident-shell.php';
