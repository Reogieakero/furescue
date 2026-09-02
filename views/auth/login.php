<?php

declare(strict_types=1);

require views_path('components/site-head.php');
?>
  <body>
    <div id="app">
      <main class="auth-page">
        <div class="auth-shell">
          <aside class="auth-photo">
            <img src="/auth/images/login-retriever.png" alt="Golden retriever" width="720" height="960" />
            <div class="auth-photo-overlay">
              <i data-lucide="heart" class="auth-photo-accent" aria-hidden="true"></i>
              <p class="auth-photo-quote">Be their voice&hellip;</p>
            </div>
          </aside>
          <div class="auth-form-side">
            <a href="/index.php" class="brand auth-brand">
              <span class="logo-mark"><i data-lucide="paw-print"></i></span>
              <span>Fur<span class="text-primary">escue</span></span>
            </a>
            <p class="auth-tagline">Rescue. Protect. Love.</p>
            <h1 class="auth-title">Welcome Back!</h1>
            <p class="auth-lead">Sign in to coordinate rescues and help Puspin &amp; Aspin find homes.</p>
            <form id="login-form" class="auth-form" novalidate method="post" action="/auth/login.php">
              <div class="field">
                <label for="email" class="field-label">Email</label>
                <input id="email" name="email" type="email" placeholder="you@example.com" value="<?= htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8') ?>" class="input" />
              </div>
              <div class="field">
                <label for="password" class="field-label">Password</label>
                <div class="input-wrap">
                  <input id="password" name="password" type="password" placeholder="••••••••" value="" class="input" />
                  <button type="button" id="toggle-pw" class="input-affix" aria-label="Show password">
                    <i data-lucide="eye"></i>
                  </button>
                </div>
              </div>
              <div class="auth-row">
                <label class="auth-remember">
                  <input type="checkbox" id="remember" name="remember" checked />
                  <span>Remember me</span>
                </label>
              </div>
<?php if ($error !== ''): ?>
              <div class="toast-viewport" aria-live="polite">
                <div class="toast toast--error is-visible" role="status">
                  <i data-lucide="alert-circle" class="toast-icon"></i>
                  <p class="toast-message"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                  <button class="toast-close" aria-label="Dismiss"><i data-lucide="x"></i></button>
                </div>
              </div>
<?php endif; ?>
              <button type="submit" class="auth-submit"><i data-lucide="lock" class="icon"></i><span>Log In</span></button>
            </form>
            <div class="separator separator--label auth-divider"><span>or</span></div>
<?php require views_path('auth/google-button.php'); ?>
            <p class="auth-alt">Don't have an account? <a href="/auth/signup.php" class="auth-link">Sign up</a></p>
          </div>
        </div>
      </main>
    </div>
    <script>window.FURESCUE_GOOGLE_CLIENT_ID = <?= json_encode($googleClientId) ?>;</script>
    <script type="module" src="/auth/js/auth.js"></script>
  </body>
</html>
