<?php

$navLinks = [
    ['label' => 'Home', 'href' => '#home'],
    ['label' => 'Who It Helps', 'href' => '#audiences'],
    ['label' => 'Features', 'href' => '#features'],
    ['label' => 'How It Works', 'href' => '#how'],
];

$navLinkItems = static function (array $links): string {
    $out = '';
    foreach ($links as $link) {
        $out .= '<a href="' . htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') . '" class="nav-link">' . htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') . '</a>';
    }
    return $out;
};

$navLinkMarkup = $navLinkItems($navLinks);

$btnGhostSm = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-7 px-3';
$btnDefaultSm = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-7 px-3';
$btnOutlineSm = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-7 px-3';
?>
<header id="navbar" class="nav">
  <div class="container nav-inner">
    <a href="#home" class="brand">
      <span class="logo-mark" aria-hidden="true"><i data-lucide="paw-print"></i></span>
      <span>Fur<span class="text-primary">escue</span></span>
    </a>

    <nav class="nav-links">
      <?= $navLinkMarkup ?>
    </nav>

<div class="nav-actions">
      <a href="/auth/login.php" class="<?= $btnGhostSm ?>"><span>Log in</span></a>
      <a href="/auth/login.php" class="<?= $btnDefaultSm ?>"><span>Get Started</span></a>
    </div>

    <button id="menu-toggle" class="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
      <span class="bar"></span>
      <span class="bar"></span>
      <span class="bar"></span>
    </button>
  </div>

  <div id="mobile-menu" class="mobile-menu">
    <nav class="container mobile-menu-links">
      <?= $navLinkMarkup ?>
    </nav>
<div class="container mobile-menu-actions">
      <a href="/auth/login.php" class="<?= $btnOutlineSm ?>"><span>Log in</span></a>
      <a href="/auth/login.php" class="<?= $btnDefaultSm ?>"><span>Get Started</span></a>
    </div>
  </div>
</header>
