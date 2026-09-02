<?php

$pageTitle = 'FurEscue — Centralized Rescue Platform for Puspin & Aspin';
$pageDescription = 'FurEscue connects animal rescuers, city veterinarians, and the community on one map-driven platform for stray, injured, and abandoned Puspin and Aspin welfare.';
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
$pageCss = ['/landing/css/landing.css'];
require __DIR__ . '/../components/site-head.php';
require __DIR__ . '/../components/guest-header.php';
require __DIR__ . '/copy.php';
?>
  <body>
    <div id="app">
      <section id="home" class="hero">
        <div class="hero-glow" aria-hidden="true"></div>
        <div class="container hero-split">
          <div class="hero-copy">
            <span class="badge badge--soft hero-badge"><i data-lucide="paw-print" class="badge-icon"></i>For Puspin &amp; Aspin welfare</span>

            <h1 class="hero-title">
              The centralized rescue platform for every <span class="text-primary">stray, injured &amp; abandoned</span> animal
            </h1>

            <p class="hero-subtitle">
              Fur<strong>escue</strong> connects rescuers, city veterinarians, and the
              community on one map-driven platform &mdash; so urgent cases get found faster and more animals find safe homes.
            </p>

            <div class="hero-actions">
              <a href="/animals/" class="btn btn--solid"><i data-lucide="heart" class="icon"></i><span>Adopt a Friend</span></a>
              <a href="/report/" class="btn btn--ghost"><i data-lucide="megaphone" class="icon"></i><span>Report an Activity</span></a>
            </div>

            <div class="hero-meta">
              <div class="hero-meta-item"><i data-lucide="map-pin"></i><span>Live map of cases</span></div>
              <div class="hero-meta-item"><i data-lucide="heart-handshake"></i><span>Community-driven</span></div>
            </div>
          </div>

          <div class="hero-visual">
            <div class="hero-visual-card">
<?php require __DIR__ . '/hero-art.php'; ?>

              <div class="float-card float-card--tl">
                <span class="float-icon"><i data-lucide="map-pin"></i></span>
                <div>
                  <strong>128</strong>
                  <small>active cases</small>
                </div>
              </div>

              <div class="float-card float-card--br">
                <span class="float-icon"><i data-lucide="heart"></i></span>
                <div>
                  <strong>64</strong>
                  <small>adopted</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="what-we-do" class="section">
        <div class="container">
          <div class="section-head">
            <span class="section-eyebrow"><i data-lucide="heart-handshake"></i> What we do</span>
            <h2 class="section-title">From first report to forever home</h2>
            <p class="section-subtitle">
              Four simple steps that turn a sighting on the street into a safe, happy ending.
            </p>
          </div>
          <div class="wwd-grid">
<?php foreach ($whatWeDo as $step): ?>
            <div class="card wwd-card">
              <div class="wwd-icon"><i data-lucide="<?= htmlspecialchars($step['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
              <h3 class="wwd-title"><?= htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8') ?></h3>
              <p class="wwd-desc"><?= htmlspecialchars($step['desc'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
<?php endforeach; ?>
          </div>
        </div>
      </section>

      <section id="audiences" class="section">
        <div class="container">
          <div class="section-head">
            <span class="section-eyebrow"><i data-lucide="users"></i> Who it helps</span>
            <h2 class="section-title">Built for everyone in the rescue chain</h2>
            <p class="section-subtitle">
              From the neighbor who spots a stray to the city vet planning resources &mdash;
              Fur<strong>escue</strong> gives each role the tools they need.
            </p>
          </div>
          <div class="laptop">
            <div class="laptop-screen">
              <div class="laptop-chrome" aria-hidden="true">
                <span></span><span></span><span></span>
              </div>
              <div class="laptop-screen-inner">
                <div class="audiences-grid">
<?php foreach ($audiences as $audience): ?>
                  <div class="card audience-card" id="<?= htmlspecialchars($audience['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="audience-header">
                      <div class="audience-top">
                        <div class="audience-icon"><i data-lucide="<?= htmlspecialchars($audience['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                        <span class="<?= htmlspecialchars($badgeClassMap[$audience['badgeVariant']], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($audience['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                      <h3 class="audience-title"><?= htmlspecialchars($audience['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                      <p class="audience-desc"><?= htmlspecialchars($audience['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="audience-body">
                      <ul class="audience-list">
<?php foreach ($audience['points'] as $point): ?>
                        <li class="audience-point"><i class="audience-check" data-lucide="check"></i><span><?= htmlspecialchars($point, ENT_QUOTES, 'UTF-8') ?></span></li>
<?php endforeach; ?>
                      </ul>
                    </div>
                    <div class="audience-footer"><a class="audience-link" href="#<?= htmlspecialchars($audience['id'], ENT_QUOTES, 'UTF-8') ?>">Learn more <i data-lucide="arrow-right"></i></a></div>
                  </div>
<?php endforeach; ?>
                </div>
              </div>
            </div>
            <div class="laptop-base" aria-hidden="true"></div>
            <div class="laptop-keyboard" aria-hidden="true"></div>
          </div>
        </div>
      </section>

      <section id="features" class="section section-muted">
        <div class="container">
          <div class="section-head">
            <span class="section-eyebrow"><i data-lucide="sparkles"></i> Features</span>
            <h2 class="section-title">Everything a modern rescue operation needs</h2>
            <p class="section-subtitle">
              Practical tools that turn scattered reports into coordinated, measurable action.
            </p>
          </div>
          <div class="features-grid">
<?php foreach ($features as $feature): ?>
            <div class="card feature-card">
              <div class="feature-header">
                <div class="feature-icon"><i data-lucide="<?= htmlspecialchars($feature['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                <h3 class="feature-title"><?= htmlspecialchars($feature['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="feature-desc"><?= htmlspecialchars($feature['desc'], ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            </div>
<?php endforeach; ?>
          </div>
        </div>
      </section>

      <section id="how" class="section">
        <div class="container">
          <div class="section-head">
            <span class="section-eyebrow"><i data-lucide="route"></i> How it works</span>
            <h2 class="section-title">From sighting to safe home in three steps</h2>
            <p class="section-subtitle">
              A simple loop that keeps the whole community moving in the same direction.
            </p>
          </div>
          <ol class="stepper">
<?php foreach ($steps as $index => $step): ?>
            <li class="stepper-step">
              <div class="stepper-marker-wrap">
                <div class="stepper-marker"><span><?= htmlspecialchars($step['n'], ENT_QUOTES, 'UTF-8') ?></span></div>
<?php if ($index < count($steps) - 1): ?>
                <span class="stepper-connector" aria-hidden="true"></span>
<?php endif; ?>
              </div>
              <div class="stepper-content">
                <h3 class="stepper-title"><?= htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="stepper-desc"><?= htmlspecialchars($step['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="stepper-status"><i data-lucide="loader-circle" style="width:14px;height:14px"></i><span><?= htmlspecialchars($step['status'], ENT_QUOTES, 'UTF-8') ?></span></div>
              </div>
            </li>
<?php endforeach; ?>
          </ol>
        </div>
      </section>

      <section class="section section-band">
        <div class="container">
          <div class="stats-grid">
<?php foreach ($stats as $stat): ?>
            <div class="stat">
              <div class="stat-value"><?= htmlspecialchars($stat['value'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="stat-label"><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="stat-sub"><?= htmlspecialchars($stat['sub'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
<?php endforeach; ?>
          </div>
        </div>
      </section>

      <section id="signup" class="section">
        <div class="container">
          <div class="cta-card on-dark">
            <div class="cta-glow" aria-hidden="true"></div>
            <div class="cta-inner">
              <h2 class="cta-title">Ready to make rescues faster &amp; smarter?</h2>
              <p class="cta-subtitle">
                Join rescuers, city veterinarians, and community members already working
                together for Puspin and Aspin welfare.
              </p>
              <div class="cta-actions">
                <a href="/animals/" class="btn btn--solid"><i data-lucide="heart" class="icon"></i><span>Adopt a Friend</span></a>
                <a href="/report/" class="btn btn--ghost"><i data-lucide="megaphone" class="icon"></i><span>Report an Activity</span></a>
              </div>
            </div>
          </div>
        </div>
      </section>

<?php require __DIR__ . '/../components/guest-footer.php'; ?>
    </div>

    <script type="module" src="/landing/js/landing.js"></script>
  </body>
</html>
