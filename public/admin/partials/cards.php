<?php

declare(strict_types=1);

$healthSlides = '';
$healthList = array_map($mapHealthUpdate, $healthUpdatesState['items']);
$buildHealthSlide = static function (array $h): string {
    return "
    <div class=\"carousel-slide\">
      <div class=\"hc-top\">
        <div class=\"hc-icon\"><i data-lucide=\"" . e($h['icon']) . "\"></i></div>
        <div class=\"hc-meta\">
          <div class=\"hc-animal\">" . e($h['animal']) . "</div>
          <div class=\"hc-when\">" . e($h['when']) . "</div>
        </div>
      </div>
      <div class=\"hc-cards\">
        <div class=\"hc-card\">
          <span class=\"hc-card-icon\"><i data-lucide=\"shield-check\"></i></span>
          <div class=\"hc-card-body\">
            <span class=\"hc-card-label\">Rescue status</span>
            <span class=\"hc-card-value hc-card--accent\">" . e($h['rescue']) . "</span>
          </div>
        </div>
        <div class=\"hc-card " . e($h['statusCls']) . "\">
          <span class=\"hc-card-icon\"><i data-lucide=\"activity\"></i></span>
          <div class=\"hc-card-body\">
            <span class=\"hc-card-label\">Health</span>
            <span class=\"hc-card-value\">" . e($h['status']) . "</span>
          </div>
        </div>
      </div>
    </div>";
};
if ($healthList === []) {
    $healthCarousel = '
  <div class="panel health-carousel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="heart-pulse"></i><h2 class="panel-title panel-title--sm">Recent health updates</h2></div>
      <span class="stamp stamp--sm stamp--accent">0 Updates</span>
    </div>
    <div class="carousel">
      <div class="carousel-empty">
        ' . empty_state('heart-pulse', 'No health updates yet.') . '
        <p class="carousel-empty-note">Recent field status logs will appear here.</p>
      </div>
    </div>
  </div>';
} else {
    foreach ($healthList as $h) {
        $healthSlides .= $buildHealthSlide($h);
    }
    $healthSlides .= $buildHealthSlide($healthList[0]);
    $dots = '';
    foreach ($healthList as $i => $_) {
        $dots .= '<button class="carousel-dot' . ($i === 0 ? ' is-active' : '') . '" data-i="' . $i . '" aria-label="Slide ' . ($i + 1) . '"></button>';
    }
    $healthCarousel = '
  <div class="panel health-carousel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="heart-pulse"></i><h2 class="panel-title panel-title--sm">Recent health updates</h2></div>
      <span class="stamp stamp--sm stamp--accent">' . e(count($healthList)) . ' Updates</span>
    </div>
    <div class="carousel">
      <div class="carousel-track">' . $healthSlides . '</div>
    </div>
    <div class="carousel-dots">' . $dots . '</div>
  </div>';
}

$rescuerCardRows = '';
foreach (array_slice($onDutyRescuers, 0, 4) as $u) {
    $rImg = (string) ($u['profile_photo_url'] ?? '');
    $rName = ($u['full_name'] ?? null) ?: 'Rescuer';
    $rOrg = ($u['phone_number'] ?? null) ?: 'Rescuer';
    $rescuerCardRows .= "
    <div class=\"rescuer\">
      <div class=\"rescuer-avatar-wrap\">
        " . rescuer_avatar($rImg, $rName) . "
        <span class=\"rescuer-status\"></span>
      </div>
      <div class=\"rescuer-body\">
        <div class=\"rescuer-name\">" . e($rName) . "</div>
        <div class=\"rescuer-org\">" . e($rOrg) . "</div>
      </div>
      <span class=\"rescuer-meta\">On duty</span>
    </div>";
}
$rescuersCard = '
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="siren"></i><h2 class="panel-title panel-title--sm">Rescuers on duty</h2></div>
      <div class="rescuer-head-tools">
        <span class="stamp stamp--sm stamp--accent">' . e($overview['rescuers_on_duty']) . ' On duty</span>
        <a href="/admin/rescuers/" class="btn-link">View all ' . chevron_right() . '</a>
      </div>
    </div>
    ' . ($rescuerCardRows !== '' ? "<div class=\"rescuer-list\">{$rescuerCardRows}</div>" : empty_state('siren', 'No rescuers on duty.')) . '
  </div>';

$attentionRow = "
  <div class=\"attention-row\">
    <div class=\"attention-main\">{$attentionQueue}</div>
    <div class=\"attention-side\">
      {$healthCarousel}
      {$rescuersCard}
    </div>
  </div>";

$selectHeat = select_control('heat-intensity', [
    ['value' => 'low', 'label' => 'Low'],
    ['value' => 'medium', 'label' => 'Medium'],
    ['value' => 'high', 'label' => 'High'],
], 'medium', 'Heat intensity');
$mapCard = '
  <div class="panel" id="case-density-panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="map"></i><h2 class="panel-title">Case density &middot; City of Mati</h2></div>
      <div class="map-tools">
        <span class="map-label">Heat intensity</span>
        ' . $selectHeat . '
        <button type="button" id="map-expand" class="map-expand" aria-label="Expand map" title="Expand map"><i data-lucide="maximize"></i></button>
        <a href="/admin/cases/" class="btn-link">Open full map ' . chevron_right() . '</a>
      </div>
    </div>
    <div id="case-density-map" class="map-canvas map-canvas--leaflet"></div>
    <div class="map-foot"><span id="heat-count">0</span> Active pins &middot; Live</div>
  </div>';

$chartCols = '';
foreach ($chartBars as $d) {
    $barCls = 'chart-bar' . ($d['coral'] ? ' chart-bar--coral' : '');
    $chartCols .= "
    <div class=\"chart-col\">
      <div class=\"chart-track\"><div class=\"{$barCls}\" style=\"height:{$d['h']}%\"></div></div>
      <span class=\"chart-day\">" . e($d['day']) . '</span>
    </div>';
}
$growthHtml = '';
if ($growth !== null) {
    $growthHtml = ' &middot; <span class="chart-foot-accent">' . ($growth > 0 ? '+' : '') . e($growth) . '% vs last week</span>';
}
$chartCard = '
    <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="bar-chart-3"></i><h2 class="panel-title panel-title--sm">Adoptions this week</h2></div>
    <div class="chart">' . $chartCols . '</div>
    <div class="chart-foot">
      <span class="chart-foot-muted">Total completed</span>
      <span class="chart-foot-total">' . e($overview['adoptions_completed']) . $growthHtml . '</span>
    </div>
  </div>';

$elearnItems = $elearning['items'] ?? [];
if ($elearnItems === []) {
    $elearningCard = '
  <div class="panel panel--padded elearn-card">
    <div class="panel-title-wrap"><i data-lucide="book-open"></i><h2 class="panel-title panel-title--sm">E-Learning library</h2></div>
    ' . empty_state('book-open', 'No records.') . '
  </div>';
} else {
    $buildElearnSlide = static function (array $m): string {
        return '
    <div class="carousel-slide carousel-slide--elearn">
      <span class="ec-category">' . e(($m['category'] ?? null) ?: 'Module') . '</span>
      <h3 class="ec-title">' . e(($m['title'] ?? null) ?: 'Untitled module') . '</h3>
      <p class="ec-meta">' . e(time_ago($m['created_at'] ?? null)) . ' &middot; Published</p>
      <a href="#" class="btn-link ec-link">Read module ' . chevron_right() . '</a>
    </div>';
    };
    $elearnSlides = '';
    foreach ($elearnItems as $m) {
        $elearnSlides .= $buildElearnSlide($m);
    }
    $elearnSlides .= $buildElearnSlide($elearnItems[0]);
    $elearnDots = '';
    foreach ($elearnItems as $i => $_) {
        $elearnDots .= '<button class="carousel-dot' . ($i === 0 ? ' is-active' : '') . '" data-i="' . $i . '" aria-label="Slide ' . ($i + 1) . '"></button>';
    }
    $elearningCard = '
  <div class="panel panel--padded elearn-card">
    <div class="panel-title-wrap"><i data-lucide="book-open"></i><h2 class="panel-title panel-title--sm">E-Learning library</h2></div>
    <div class="elearn-carousel">
      <div class="carousel">
        <div class="carousel-track">' . $elearnSlides . '</div>
      </div>
      <div class="carousel-dots">' . $elearnDots . '</div>
    </div>
    ' . button_html('Manage content', 'outline', className: 'w-full elearn-action') . '
  </div>';
}

$auditItems = array_slice($notificationsState['items'], 0, 4);
if ($auditItems !== []) {
    $auditRows = '';
    foreach ($auditItems as $n) {
        $auditRows .= '
    <li class="audit-item"><span class="audit-time">' . e(time_ago($n['created_at'] ?? null)) . '</span><span class="audit-text">' . e(($n['message'] ?? null) ?: '—') . '</span></li>';
    }
} else {
    $auditRows = '<li class="audit-item"><span class="audit-text">No recent notifications.</span></li>';
}
$auditLogCard = '
  <div class="audit">
    <div class="audit-head"><i data-lucide="bell"></i> Recent notifications</div>
    <ul class="audit-list">' . $auditRows . '</ul>
  </div>';
