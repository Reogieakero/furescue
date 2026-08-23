<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Database;

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

$googleClientId = trim((string) Database::env('GOOGLE_CLIENT_ID', ''));

$pageTitle = 'FurEscue — Create account';
$pageDescription = 'Create a FurEscue account to report strays, follow rescue cases, and help Puspin & Aspin find permanent families.';
$pageCss = ['/auth/css/auth.css'];
require __DIR__ . '/../includes/site-head.php';
?>
  <body>
    <div id="app">
      <main class="auth-page">
        <div class="auth-split">
          <div class="auth-panel">
            <div class="auth-panel-inner">
              <a href="/index.php" class="brand auth-brand">
                <span class="logo-mark"><i data-lucide="paw-print"></i></span>
                <span>Fur<span class="text-primary">escue</span></span>
              </a>
              <h1 class="auth-panel-title">Join the rescue effort.</h1>
              <p class="auth-panel-sub">
                Create an account to report animals in need, track rescue progress, and give Puspins &amp; Aspins a second chance.
              </p>
              <ul class="auth-points">
                <li><i data-lucide="map-pin"></i> Report strays with exact location</li>
                <li><i data-lucide="bell-ring"></i> Follow your case until resolution</li>
                <li><i data-lucide="heart"></i> Adopt or volunteer as a rescuer</li>
              </ul>
            </div>
          </div>
          <div class="auth-form-side">
            <div class="rounded-xl border bg-card text-card-foreground shadow auth-card">
              <div class="flex flex-col space-y-1.5 p-6 auth-card-head">
                <h3 class="font-semibold leading-none tracking-tight auth-card-title">Create your account</h3>
                <p class="text-sm text-muted-foreground">Sign up in seconds — rescuer accounts are reviewed by an admin.</p>
              </div>
              <div class="p-6 pt-0 auth-card-body">
                <form id="signup-form" class="auth-form" novalidate>
                  <div class="field">
                    <label for="full_name" class="label">Full name</label>
                    <input id="full_name" name="full_name" type="text" placeholder="Juan Dela Cruz" autocomplete="name" maxlength="150" class="input" />
                  </div>
                  <div class="field">
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" placeholder="you@example.com" autocomplete="email" class="input" />
                  </div>
                  <div class="field">
                    <label for="password" class="label">Password</label>
                    <div class="input-wrap">
                      <input id="password" name="password" type="password" placeholder="At least 8 characters" autocomplete="new-password" class="input" />
                      <button type="button" id="toggle-pw" class="input-affix" aria-label="Show password">
                        <i data-lucide="eye"></i>
                      </button>
                    </div>
                  </div>
                  <div class="auth-grid-2">
                    <div class="field">
                      <label for="phone_number" class="label">Phone <span class="auth-optional">(optional)</span></label>
                      <input id="phone_number" name="phone_number" type="tel" placeholder="09XX XXX XXXX" autocomplete="tel" maxlength="20" class="input" />
                    </div>
                    <div class="field">
                      <label for="role" class="label">I want to join as</label>
                      <select id="role" name="role" class="input">
                        <option value="resident" selected>Resident</option>
                        <option value="rescuer">Rescuer</option>
                      </select>
                    </div>
                  </div>
                  <div class="field">
                    <label for="address" class="label">Address <span class="auth-optional">(optional)</span></label>
                    <input id="address" name="address" type="text" placeholder="Barangay, City of Mati" maxlength="1000" class="input" />
                  </div>
                  <p id="rescuer-note" class="auth-note hidden">
                    <i data-lucide="shield-alert"></i>
                    <span>Your account will require admin approval before you can respond to rescues.</span>
                  </p>
                  <p id="form-error" class="auth-error hidden" role="alert"></p>
                  <button type="submit" id="signup-submit" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-6 text-sm auth-submit"><i data-lucide="user-plus" class="icon"></i><span>Create account</span></button>
                </form>
              </div>
              <div class="flex items-center p-6 pt-0 auth-card-foot">
                <div class="separator separator--label"><span>or</span></div>
                <a href="#google" data-google-signin class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-6 text-sm auth-submit">
                  <svg class="google-icon" viewBox="0 0 48 48" width="18" height="18" aria-hidden="true">
                    <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.4 29.3 35 24 35c-6.1 0-11-4.9-11-11s4.9-11 11-11c2.8 0 5.4 1.1 7.3 2.8l5.7-5.7C33.5 6.5 29 5 24 5 13.5 5 5 13.5 5 24s8.5 19 19 19 19-8.5 19-19c0-1.3-.1-2.3-.4-3.5z"/>
                    <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 13 24 13c2.8 0 5.4 1.1 7.3 2.8l5.7-5.7C33.5 6.5 29 5 24 5 16.3 5 9.7 9.3 6.3 14.7z"/>
                    <path fill="#4CAF50" d="M24 43c5 0 9.5-1.9 12.9-5.1l-6-5.2C29.2 34.3 26.7 35 24 35c-5.3 0-9.7-3.6-11.3-8.4l-6.5 5C9.6 39 16.2 43 24 43z"/>
                    <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4 5.6l6 5.2C41.4 35.8 44 30.4 44 24c0-1.3-.1-2.3-.4-3.5z"/>
                  </svg>
<span>Continue with Google</span>
                </a>
                <p class="auth-alt">Already have an account? <a href="/auth/login.php" class="auth-link">Sign in</a></p>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
    <script>window.FURESCUE_GOOGLE_CLIENT_ID = <?= json_encode($googleClientId) ?>;</script>
    <script type="module" src="/auth/js/auth.js"></script>
  </body>
</html>
