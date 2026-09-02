<?php

$footerCols = [
    ['title' => 'Platform', 'links' => [
        ['label' => 'Report a stray', 'href' => '/report/'],
        ['label' => 'Browse adoption', 'href' => '/animals/'],
        ['label' => 'Find rescuers', 'href' => '#'],
        ['label' => 'Map view', 'href' => '#'],
    ]],
    ['title' => 'For', 'links' => [
        ['label' => 'Rescuers', 'href' => '#'],
        ['label' => 'City Veterinarian', 'href' => '#'],
        ['label' => 'Community', 'href' => '#'],
        ['label' => 'Volunteers', 'href' => '#'],
    ]],
    ['title' => 'Resources', 'links' => [
        ['label' => 'How it works', 'href' => '#how'],
        ['label' => 'Safety guide', 'href' => '#'],
        ['label' => 'Contact', 'href' => '#'],
        ['label' => 'FAQ', 'href' => '#'],
    ]],
];

$footerColMarkup = static function (array $cols): string {
    $out = '';
    foreach ($cols as $col) {
        $out .= '
      <div class="footer-col">
        <h4 class="footer-col-title">' . htmlspecialchars($col['title'], ENT_QUOTES, 'UTF-8') . '</h4>
        <ul class="footer-col-links">';
        foreach ($col['links'] as $link) {
            $href = htmlspecialchars((string) ($link['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string) ($link['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $out .= '<li><a href="' . $href . '" class="footer-link">' . $label . '</a></li>';
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
      <p>&copy; <?= (int) date('Y') ?> Fur<strong>escue</strong>. All rights reserved.</p>
      <p class="footer-muted">Made with care for every stray that needs a home.</p>
    </div>
  </div>
</footer>
