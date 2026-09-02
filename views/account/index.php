<?php

declare(strict_types=1);

$esc = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fullName = $esc($userData['full_name'] ?? ($residentUser['full_name'] ?? ''));
$phone = $esc($userData['phone_number'] ?? '');
$address = $esc($userData['address'] ?? '');
?>
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
            <input id="full_name" name="full_name" type="text" class="input" maxlength="150" required autocomplete="name" value="<?= $fullName ?>">
          </div>
          <div class="rform-field">
            <label for="phone_number" class="rform-label">Phone number</label>
            <input id="phone_number" name="phone_number" type="tel" class="input" maxlength="20" autocomplete="tel" value="<?= $phone ?>">
          </div>
          <div class="rform-field">
            <label for="address" class="rform-label">Address</label>
            <textarea id="address" name="address" class="input input--area" rows="3" maxlength="2000" autocomplete="street-address"><?= $address ?></textarea>
          </div>
          <p class="rform-error" id="account-error" hidden><i data-lucide="alert-circle"></i><span></span></p>
        </div>
        <div class="rmodal-foot">
          <button type="submit" class="rbtn rbtn--solid" id="account-save">
            <i data-lucide="save"></i><span>Save changes</span>
          </button>
        </div>
      </form>
    </div>
