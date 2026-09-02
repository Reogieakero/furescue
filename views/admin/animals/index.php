<?php

declare(strict_types=1);

$kpiTiles = '';
foreach ($animalKpiData as $k) {
    $label = (string) $k['label'];
    $value = (string) $k['value'];
    $aria = $label . ': ' . $value;
    if (!empty($k['desc'])) {
        $aria .= '. ' . (string) $k['desc'];
    }
    $title = !empty($k['desc']) ? ' title="' . e((string) $k['desc']) . '"' : '';
    $trend = '';
    if (!empty($k['trend']['text'])) {
        $trend = '<p class="kpi-card__trend kpi-card__trend--' . e((string) ($k['trend']['tone'] ?? 'neutral')) . '">' . e((string) $k['trend']['text']) . '</p>';
    }
    $filter = $k['filter'] ?? null;
    $tag = $filter ? 'button' : 'article';
    $extraClass = $filter ? ' kpi-card--interactive' : '';
    $typeAttr = $filter ? ' type="button"' : '';
    $filterAttr = $filter ? ' data-filter="' . e((string) $filter) . '"' : '';
    $kpiTiles .= '
  <' . $tag . ' class="kpi-card' . $extraClass . '"' . $typeAttr . $filterAttr . ' aria-label="' . e($aria) . '"' . $title . '>
    <div class="kpi-card__icon kpi-card__icon--' . e((string) $k['tone']) . '" aria-hidden="true"><i data-lucide="' . e((string) $k['icon']) . '"></i></div>
    <div class="kpi-card__body">
      <p class="kpi-card__label">' . e($label) . '</p>
      <p class="kpi-card__value">' . e($value) . '</p>
      ' . $trend . '
    </div>
  </' . $tag . '>';
}
$kpiGrid = '<div id="animal-kpis" class="kpi-grid">' . $kpiTiles . '</div>';

$filterTabsHtml = '';
foreach ($animalFilters as $f) {
    $activeCls = $f['key'] === 'all' ? ' is-active' : '';
    $filterTabsHtml .= '<button data-filter="' . e($f['key']) . '" class="q-btn' . $activeCls . '">' . e($f['label']) . ' &middot; ' . e($counts[$f['key']]) . '</button>';
}

$cardsHtml = '';
foreach ($animals as $a) {
    $tone = ANIMAL_STATUS_TONES[$a['status']] ?? 'stamp--muted';
    $initial = mb_strtoupper(mb_substr($a['name'] !== '' ? $a['name'] : '?', 0, 1, 'UTF-8'), 'UTF-8');
    $thumbInner = $a['photo'] !== null
        ? '<img src="' . e($a['photo']) . '" alt="' . e($a['name']) . '" class="animal-thumb-img">'
        : '<span class="animal-thumb-initial">' . e($initial) . '</span>'
            . '<i data-lucide="' . e(strtolower($a['species']) === 'cat' ? 'cat' : 'paw-print') . '" class="animal-thumb-icon"></i>';
    $ribbon = $a['hasMedical']
        ? '<span class="animal-card-ribbon animal-card-ribbon--green">Medical</span>'
        : '<span class="animal-card-ribbon animal-card-ribbon--red">No records</span>';
    $cardsHtml .= '
    <button type="button" class="animal-card" data-animal="' . e($a['id']) . '">
      <div class="animal-thumb animal-thumb--' . e(strtolower($a['species'])) . '">
        ' . $thumbInner . '
        ' . $ribbon . '
      </div>
      <div class="animal-card-body">
        <div class="animal-card-top">
          <span class="animal-card-name">' . e($a['name']) . '</span>
          <span class="stamp stamp--sm ' . e($tone) . '">' . e($a['status']) . '</span>
        </div>
      </div>
    </button>';
}
if ($cardsHtml === '') {
    $cardsHtml = '<div class="animal-empty empty-state"><i data-lucide="paw-print"></i><span>No animals match your filters.</span></div>';
}

$gridPanel = '
  <div class="panel animal-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="paw-print"></i>
        <h2 class="panel-title">Animals <span class="animal-count" id="animal-total-badge">' . e(count($animals)) . '</span></h2>
      </div>
    </div>
    <div class="report-toolbar animal-toolbar">
      <div id="animal-filter-tabs" class="q-tabs">' . $filterTabsHtml . '</div>
      <div class="report-search animal-search">
        <i data-lucide="search"></i>
        <input id="animal-search" type="text" placeholder="Search name, species, breed, ID…" value="">
      </div>
    </div>
    <div class="panel-body">
      <div id="animal-grid" class="animal-grid">' . $cardsHtml . '</div>
      <div id="animal-selected-store" hidden></div>
    </div>
  </div>';

$detailEmpty = '
    <div class="panel panel--padded animal-detail animal-detail--empty">
      <div class="rescuer-detail-empty">
        <i data-lucide="mouse-pointer-click"></i>
        <p>Select an animal card to view its full profile.</p>
      </div>
    </div>';

$pageHead = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--jungle">Animal Management</span>
      <h1 class="page-title">Animals</h1>
      <p class="page-sub">Browse every animal in the system, add new rescues, and review their profiles.</p>
    </div>
    <div class="page-head-actions">
      ' . button_html('Add animal', 'default', icon: 'plus', attrs: 'data-act="open-add"') . '
      ' . button_html('Export CSV', 'outline', icon: 'download', attrs: 'data-export="csv"') . '
    </div>
  </div>';

$adminChildren = '<div class="animals-list">'
    . $pageHead
    . $kpiGrid
    . '<div class="animal-split">'
    . '<div class="animal-grid-col">' . $gridPanel . '</div>'
    . '<div id="animal-side" class="animal-side-col">'
    . '<div class="animal-side"><div id="animal-detail">' . $detailEmpty . '</div></div>'
    . '</div>'
    . '</div>'
    . '</div>';
