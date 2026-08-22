<?php

$badgeBase = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2';
$badgeVariants = [
    'default' => ' border-transparent bg-primary text-primary-foreground',
    'secondary' => ' border-transparent bg-secondary text-secondary-foreground',
    'accent' => ' border-transparent bg-accent text-accent-foreground',
];
$btnLgBase = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50';
$btnDefaultLg = $btnLgBase . ' bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-6 text-sm';
$btnOutlineLg = $btnLgBase . ' border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-6 text-sm';

$cardBase = 'rounded-xl border bg-card text-card-foreground shadow';
$headerBase = 'flex flex-col space-y-1.5 p-6 ';
$titleBase = 'font-semibold leading-none tracking-tight ';
$descClass = 'text-sm text-muted-foreground';
$contentBase = 'p-6 pt-0 ';
$cardFooterBase = 'flex items-center p-6 pt-0 ';

$pageTitle = 'FurEscue — Centralized Rescue Platform for Puspin & Aspin';
$pageDescription = 'FurEscue connects animal rescuers, city veterinarians, and the community on one map-driven platform for stray, injured, and abandoned Puspin and Aspin welfare.';
$pageCss = ['/landing/css/landing.css'];
require __DIR__ . '/site-head.php';
require __DIR__ . '/header.php';

$audiences = [
    [
        'id' => 'rescuers',
        'icon' => 'siren',
        'title' => 'Animal Rescuers & Volunteers',
        'badge' => 'Faster response',
        'badgeVariant' => 'default',
        'desc' => 'Manage animal-related reports from one place. Locate cases on a map, prioritize the most urgent situations, and respond quicker — reducing delays across every rescue operation.',
        'points' => [
            'Live map of reported cases near you',
            'Urgent-first queue & status tracking',
            'Coordinate teams & volunteer shifts',
        ],
    ],
    [
        'id' => 'vets',
        'icon' => 'stethoscope',
        'title' => 'City Veterinarian',
        'badge' => 'Data-driven',
        'badgeVariant' => 'secondary',
        'desc' => 'Organize and monitor animal welfare data with clear visibility into high-incident areas. Plan actions and allocate resources where they matter most, backed by real reporting.',
        'points' => [
            'Heatmaps of frequent incident zones',
            'Centralized welfare dashboards',
            'Resource & clinic allocation planning',
        ],
    ],
    [
        'id' => 'community',
        'icon' => 'users',
        'title' => 'Community Members',
        'badge' => 'Get involved',
        'badgeVariant' => 'accent',
        'desc' => 'A simple, accessible way to report stray, injured, or abandoned animals and to browse pets available for adoption. Strengthen the public–rescuer collaboration and help animals find permanent homes.',
        'points' => [
            'Report a stray in under a minute',
            'Browse Puspin & Aspin for adoption',
            'Track the impact of your reports',
        ],
    ],
];

$features = [
    ['icon' => 'map', 'title' => 'Map-based case locating', 'desc' => 'Every report is pinned to a map so rescuers and vets can see exactly where help is needed and route efficiently.'],
    ['icon' => 'bell-ring', 'title' => 'Urgency prioritization', 'desc' => 'Injured and at-risk animals are surfaced first, helping teams act on the most critical cases without delay.'],
    ['icon' => 'zap', 'title' => 'Faster response times', 'desc' => 'A centralized inbox of reports removes the back-and-forth and shortens the path from sighting to rescue.'],
    ['icon' => 'bar-chart-3', 'title' => 'Welfare analytics', 'desc' => 'City vets get visibility into incident hotspots and trends to plan data-driven, resource-smart action.'],
    ['icon' => 'home', 'title' => 'Adoption marketplace', 'desc' => 'Community members browse Puspin and Aspin available for adoption, making the process simple and efficient.'],
    ['icon' => 'users', 'title' => 'Community collaboration', 'desc' => 'Public reports, rescuer coordination, and vet oversight in one platform that strengthens the whole network.'],
];

$steps = [
    ['n' => '01', 'title' => 'Report', 'status' => 'Reporting sighting…', 'desc' => 'Community members spot a stray, injured, or abandoned Puspin or Aspin and file a quick report with a photo and location.'],
    ['n' => '02', 'title' => 'Locate & Prioritize', 'status' => 'Locating & prioritizing…', 'desc' => 'Reports appear on the shared map. Rescuers and the city vet see urgent cases first and plan the fastest route.'],
    ['n' => '03', 'title' => 'Rescue & Rehome', 'status' => 'Updating status…', 'desc' => 'Teams respond, vets monitor welfare data, and recovered animals move into the adoption marketplace for a permanent home.'],
];

$stats = [
    ['value' => '1', 'label' => 'Centralized platform', 'sub' => 'for reports, maps & adoption'],
    ['value' => '24/7', 'label' => 'Community reporting', 'sub' => 'anytime, from anywhere'],
    ['value' => '3', 'label' => 'Connected roles', 'sub' => 'rescuers · vets · community'],
    ['value' => '100%', 'label' => 'Puspin & Aspin focus', 'sub' => 'native cats & dogs first'],
];
?>
  <body>
    <div id="app">
      <section id="home" class="hero">
        <div class="hero-glow" aria-hidden="true"></div>
        <div class="container hero-split">
          <div class="hero-copy">
            <span class="<?= htmlspecialchars($badgeBase . $badgeVariants['secondary'] . ' hero-badge', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="paw-print" class="badge-icon"></i>For Puspin &amp; Aspin welfare</span>

            <h1 class="hero-title">
              The centralized rescue platform for every <span class="text-primary">stray, injured &amp; abandoned</span> animal
            </h1>

            <p class="hero-subtitle">
              Fur<span class="font-semibold text-foreground">escue</span> connects rescuers, city veterinarians, and the
              community on one map-driven platform &mdash; so urgent cases get found faster and more animals find safe homes.
            </p>

            <div class="hero-actions">
              <a href="#report" class="<?= htmlspecialchars($btnDefaultLg, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="map-pin" class="icon"></i><span>Report an Animal</span></a>
              <a href="#adopt" class="<?= htmlspecialchars($btnOutlineLg, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="home" class="icon"></i><span>Browse for Adoption</span></a>
            </div>

            <div class="hero-meta">
              <div class="hero-meta-item"><i data-lucide="map-pin"></i><span>Live map of cases</span></div>
              <div class="hero-meta-item"><i data-lucide="heart-handshake"></i><span>Community-driven</span></div>
            </div>
          </div>

          <div class="hero-visual">
            <div class="hero-visual-card">
              <svg viewBox="0 0 440 380" class="hero-art" role="img" aria-label="Map locating rescued Puspin and Aspin">
                <defs>
                  <linearGradient id="pinGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="hsl(221 83% 53%)" />
                    <stop offset="1" stop-color="hsl(217 91% 60%)" />
                  </linearGradient>
                  <pattern id="dots" width="28" height="28" patternUnits="userSpaceOnUse">
                    <circle cx="3" cy="3" r="2.2" fill="hsl(214 50% 82%)" />
                  </pattern>
                </defs>

                <rect x="0" y="0" width="440" height="380" fill="url(#dots)" opacity="0.55" />

                <path d="M80 300 C 150 230 190 250 225 160 S 340 130 380 90"
                      fill="none" stroke="hsl(221 83% 53% / 0.45)" stroke-width="3"
                      stroke-dasharray="5 9" stroke-linecap="round" />

                <circle cx="80" cy="300" r="10" fill="hsl(217 91% 60%)" stroke="#fff" stroke-width="3" />
                <circle cx="380" cy="90" r="10" fill="hsl(217 91% 60%)" stroke="#fff" stroke-width="3" />

                <circle cx="120" cy="110" r="40" fill="hsl(217 91% 60% / 0.10)" />
                <circle cx="330" cy="270" r="30" fill="hsl(221 83% 53% / 0.10)" />

                <g transform="translate(225 175)">
                  <ellipse cx="0" cy="22" rx="26" ry="8" fill="hsl(221 83% 30% / 0.18)" />
                  <path d="M0 14 C -34 -36 -34 -82 0 -82 C 34 -82 34 -36 0 14 Z" fill="url(#pinGrad)" />
                  <g fill="#fff" transform="translate(0 -52)">
                    <ellipse cx="0" cy="8" rx="14" ry="11" />
                    <circle cx="-14" cy="-6" r="5.5" />
                    <circle cx="14" cy="-6" r="5.5" />
                    <circle cx="-6" cy="-14" r="5" />
                    <circle cx="6" cy="-14" r="5" />
                  </g>
                </g>

                <g fill="hsl(217 91% 60% / 0.30)">
                  <path transform="translate(300 150) scale(1.1)" d="M0 6 C -7 -3 -18 3 0 16 C 18 3 7 -3 0 6 Z" />
                  <path transform="translate(150 250) scale(0.9)" d="M0 6 C -7 -3 -18 3 0 16 C 18 3 7 -3 0 6 Z" />
                </g>
              </svg>

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

      <section id="audiences" class="section">
        <div class="container">
          <div class="section-head">
            <span class="section-eyebrow"><i data-lucide="users"></i> Who it helps</span>
            <h2 class="section-title">Built for everyone in the rescue chain</h2>
            <p class="section-subtitle">
              From the neighbor who spots a stray to the city vet planning resources &mdash;
              Fur<span class="font-semibold">escue</span> gives each role the tools they need.
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
                  <div class="<?= htmlspecialchars($cardBase . ' audience-card', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="<?= htmlspecialchars($headerBase . 'audience-header', ENT_QUOTES, 'UTF-8') ?>">
                      <div class="audience-top">
                        <div class="audience-icon"><i data-lucide="<?= htmlspecialchars($audience['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                        <span class="<?= htmlspecialchars($badgeBase . $badgeVariants[$audience['badgeVariant']], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($audience['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                      <h3 class="<?= htmlspecialchars($titleBase . 'audience-title', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($audience['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                      <p class="<?= htmlspecialchars($descClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($audience['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="<?= htmlspecialchars($contentBase . 'audience-body', ENT_QUOTES, 'UTF-8') ?>">
                      <ul class="audience-list">
<?php foreach ($audience['points'] as $point): ?>
                        <li class="audience-point"><i class="audience-check" data-lucide="check"></i><span><?= htmlspecialchars($point, ENT_QUOTES, 'UTF-8') ?></span></li>
<?php endforeach; ?>
                      </ul>
                    </div>
                    <div class="<?= htmlspecialchars($cardFooterBase . 'audience-footer', ENT_QUOTES, 'UTF-8') ?>"><a class="audience-link" href="#<?= htmlspecialchars($audience['id'], ENT_QUOTES, 'UTF-8') ?>">Learn more <i data-lucide="arrow-right"></i></a></div>
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
            <div class="<?= htmlspecialchars($cardBase . ' feature-card', ENT_QUOTES, 'UTF-8') ?>">
              <div class="<?= htmlspecialchars($headerBase . 'feature-header', ENT_QUOTES, 'UTF-8') ?>">
                <div class="feature-icon"><i data-lucide="<?= htmlspecialchars($feature['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                <h3 class="<?= htmlspecialchars($titleBase . 'feature-title', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($feature['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="<?= htmlspecialchars($descClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($feature['desc'], ENT_QUOTES, 'UTF-8') ?></p>
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
          <ol class="stepper ">
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
                <div class="stepper-status"><i data-lucide="loader-circle" class="animate-spin" style="width:14px;height:14px"></i><span><?= htmlspecialchars($step['status'], ENT_QUOTES, 'UTF-8') ?></span></div>
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
          <div class="cta-card">
            <div class="cta-glow" aria-hidden="true"></div>
            <div class="cta-inner">
              <h2 class="cta-title">Ready to make rescues faster &amp; smarter?</h2>
              <p class="cta-subtitle">
                Join rescuers, city veterinarians, and community members already working
                together for Puspin and Aspin welfare.
              </p>
              <div class="cta-actions">
                <a href="#report" class="<?= htmlspecialchars($btnDefaultLg, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="paw-print" class="icon"></i><span>Get Started</span></a>
                <a href="#report" class="<?= htmlspecialchars($btnOutlineLg, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="map-pin" class="icon"></i><span>Report an Animal</span></a>
              </div>
            </div>
          </div>
        </div>
      </section>

<?php require __DIR__ . '/footer.php'; ?>
    </div>

    <script type="module" src="/landing/js/landing.js"></script>
  </body>
</html>
