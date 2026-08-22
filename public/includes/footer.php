<?php

$footerCols = [
    ['title' => 'Platform', 'links' => ['Report a stray', 'Browse adoption', 'Find rescuers', 'Map view']],
    ['title' => 'For', 'links' => ['Rescuers', 'City Veterinarian', 'Community', 'Volunteers']],
    ['title' => 'Resources', 'links' => ['How it works', 'Safety guide', 'Contact', 'FAQ']],
];

$footerColMarkup = static function (array $cols): string {
    $out = '';
    foreach ($cols as $col) {
        $out .= '
      <div class="footer-col">
        <h4 class="footer-col-title">' . htmlspecialchars($col['title'], ENT_QUOTES, 'UTF-8') . '</h4>
        <ul class="footer-col-links">';
        foreach ($col['links'] as $linkLabel) {
            $out .= '<li><a href="#" class="footer-link">' . htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8') . '</a></li>';
        }
        $out .= '
        </ul>
      </div>';
    }
    return $out;
};
?>
<footer class="footer">
  <div class="container">
    <div class="footer-top">
      <div class="footer-brand">
        <a href="#home" class="brand">
          <span class="logo-mark" aria-hidden="true"><i data-lucide="paw-print"></i></span>
          <span>Fur<span class="text-primary">escue</span></span>
        </a>
        <p class="footer-tagline">
          A centralized rescue platform for Puspin &amp; Aspin welfare &mdash;
          built for rescuers, city vets, and the community.
        </p>
      </div>
      <div class="footer-cols">
        <?= $footerColMarkup($footerCols) ?>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= (int) date('Y') ?> Fur<span class="font-semibold">escue</span>. All rights reserved.</p>
      <p class="footer-muted">Made with care for every stray that needs a home.</p>
    </div>
  </div>
</footer>
