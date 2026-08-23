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
          <h1 class="rpage-title">Community Listings</h1>
          <p class="rpage-sub">Rehome a rescued animal — listings go live after a quick review by the City Veterinarian\'s Office.</p>
        </div>
        <button type="button" id="btn-new-listing" class="rbtn rbtn--solid"><i data-lucide="megaphone"></i><span>Post for adoption</span></button>
      </div>

      <ul class="rlist mt-5" id="listing-list"></ul>

      <div id="listings-empty" class="rempty mt-2" hidden>
        <i data-lucide="megaphone"></i>
        <p class="rempty-title">No listings yet</p>
        <p class="rempty-text">Rescued an animal that needs a new home? Post it here and we\'ll review it.</p>
        <a href="/animals/" class="rbtn rbtn--ghost"><i data-lucide="search"></i><span>Browse adoptable animals</span></a>
      </div>
    </div>';

$jwt = new JwtService();

$pageState = [
    'accessToken' => $jwt->issueAccessToken(['id' => $uid, 'role' => $residentUser['role']]),
    'user' => $residentUser,
];
$pageModules = ['/listings/js/listings.js'];
$pageTitle = 'FurEscue — Community Listings';
$pageDescription = 'Post and manage community adoption listings.';
$pageCss = [];
$residentShellTitle = 'Community Listings';

require __DIR__ . '/../includes/resident-shell.php';
