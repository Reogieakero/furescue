<?php

declare(strict_types=1);

require views_path('components/site-head.php');
?>
  <body>
    <div id="app">
      <main class="auth-page">
        <div class="auth-shell">
          <aside class="auth-photo">
            <img src="/auth/images/signup-kitten.png" alt="Ginger kitten" width="720" height="960" />
            <div class="auth-photo-overlay">
              <i data-lucide="paw-print" class="auth-photo-accent" aria-hidden="true"></i>
              <p class="auth-photo-quote">Small steps create big changes.</p>
            </div>
          </aside>
          <div class="auth-form-side">
            <a href="/index.php" class="brand auth-brand">
              <span class="logo-mark"><i data-lucide="paw-print"></i></span>
              <span>Fur<span class="text-primary">escue</span></span>
            </a>
            <p class="auth-tagline">Rescue. Protect. Love.</p>
            <h1 class="auth-title">Create an Account</h1>
            <p class="auth-lead">Sign up in seconds — rescuer accounts are reviewed by an admin.</p>
            <form id="signup-form" class="auth-form" novalidate>
              <div class="field">
                <label for="full_name" class="field-label">Full name</label>
                <input id="full_name" name="full_name" type="text" placeholder="Juan Dela Cruz" autocomplete="name" maxlength="150" class="input" />
              </div>
              <div class="field">
                <label for="email" class="field-label">Email</label>
                <input id="email" name="email" type="email" placeholder="you@example.com" autocomplete="email" class="input" />
              </div>
              <div class="field">
                <label for="password" class="field-label">Password</label>
                <div class="input-wrap">
                  <input id="password" name="password" type="password" placeholder="At least 8 characters" autocomplete="new-password" class="input" />
                  <button type="button" id="toggle-pw" class="input-affix" aria-label="Show password">
                    <i data-lucide="eye"></i>
                  </button>
                </div>
              </div>
              <div class="auth-grid-2">
                <div class="field">
                  <label for="phone_number" class="field-label">Phone <span class="auth-optional">(optional)</span></label>
                  <input id="phone_number" name="phone_number" type="tel" placeholder="09XX XXX XXXX" autocomplete="tel" maxlength="20" class="input" />
                </div>
                <div class="field">
                  <label for="role" class="field-label">I want to join as</label>
                  <select id="role" name="role" class="input">
                    <option value="resident" selected>Resident</option>
                    <option value="rescuer">Rescuer</option>
                  </select>
                </div>
              </div>
              <div class="field">
                <label for="address" class="field-label">Address <span class="auth-optional">(optional)</span></label>
                <input id="address" name="address" type="text" placeholder="Barangay, City of Mati" maxlength="1000" class="input" />
              </div>
              <p id="rescuer-note" class="auth-note hidden">
                <i data-lucide="shield-alert"></i>
                <span>Your account will require admin approval before you can respond to rescues.</span>
              </p>
              <p id="form-error" class="auth-error hidden" role="alert"></p>
              <button type="submit" id="signup-submit" class="auth-submit"><i data-lucide="user-plus" class="icon"></i><span>Sign Up</span></button>
            </form>
            <div class="separator separator--label auth-divider"><span>or</span></div>
<?php require views_path('auth/google-button.php'); ?>
            <p class="auth-alt">Already have an account? <a href="/auth/login.php" class="auth-link">Log in</a></p>
          </div>
        </div>
      </main>
    </div>
    <script>window.FURESCUE_GOOGLE_CLIENT_ID = <?= json_encode($googleClientId) ?>;</script>
    <script type="module" src="/auth/js/auth.js"></script>
  </body>
</html>
