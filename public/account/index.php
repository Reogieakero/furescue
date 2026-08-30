<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\JwtService;
use App\Database;

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

$requiredRole = ['resident', 'rescuer', 'admin'];
require __DIR__ . '/../includes/guard.php';

$uid = (string) $_SESSION['user']['id'];
$pdo = Database::connect();
$userData = (new \App\Repositories\UserRepository($pdo))->find($uid);
$userData = $userData ? $userData->toArray() : [];

$esc = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$residentUser = [
    'id' => $uid,
    'full_name' => (string) ($userData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($userData['profile_photo_url'] ?? ''),
];

$fullName = $esc($userData['full_name'] ?? $residentUser['full_name']);
$phone = $esc($userData['phone_number'] ?? '');
$address = $esc($userData['address'] ?? '');

$content = '
    <div class="mx-auto w-full max-w-4xl min-w-0">
      <div class="rpage-head">
        <div class="min-w-0">
          <h1 class="rpage-title">Account</h1>
          <p class="rpage-sub">Update the name, phone, and address on your FurEscue profile.</p>
        </div>
      </div>

      <form id="account-form" class="rcard" novalidate>
        <div class="rmodal-body">
          <div class="rform-field">
            <label for="full_name" class="rform-label">Full name</label>
            <input id="full_name" name="full_name" type="text" class="input" maxlength="150" required autocomplete="name" value="' . $fullName . '">
          </div>
          <div class="rform-field">
            <label for="phone_number" class="rform-label">Phone number</label>
            <input id="phone_number" name="phone_number" type="tel" class="input" maxlength="20" autocomplete="tel" value="' . $phone . '">
          </div>
          <div class="rform-field">
            <label for="address" class="rform-label">Address</label>
            <textarea id="address" name="address" class="input input--area" rows="3" maxlength="2000" autocomplete="street-address">' . $address . '</textarea>
          </div>
          <p class="rform-error" id="account-error" hidden><i data-lucide="alert-circle"></i><span></span></p>
        </div>
        <div class="rmodal-foot">
          <button type="submit" class="rbtn rbtn--solid" id="account-save">
            <i data-lucide="save"></i><span>Save changes</span>
          </button>
        </div>
      </form>
    </div>';

$jwt = new JwtService();
$pageState = [
    'accessToken' => $jwt->issueAccessToken(['id' => $uid, 'role' => $residentUser['role']]),
    'user' => $residentUser,
];
$pageModules = ['/account/js/account.js'];
$pageTitle = 'FurEscue — Account';
$pageDescription = 'Update your FurEscue name, phone number, and address.';
$activeNav = 'account';
$residentShellTitle = 'Account';

require __DIR__ . '/../includes/resident-shell.php';
