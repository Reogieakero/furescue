<?php

declare(strict_types=1);

$pageRows = array_slice($modules, 0, ELEARN_PAGE_SIZE);

$kpiTiles = kpi_card_html(['icon' => 'book-open', 'value' => $elearnCounts['total'], 'label' => 'Total modules', 'tone' => 'jungle'])
    . kpi_card_html(['icon' => 'badge-check', 'value' => $elearnCounts['published'], 'label' => 'Published', 'tone' => 'ink'])
    . kpi_card_html([
        'icon' => 'file-text',
        'value' => $elearnCounts['drafts'],
        'label' => 'Drafts',
        'tone' => 'coral',
        'trend' => $elearnCounts['drafts'] ? 'Needs You' : '',
        'trendTone' => 'down',
    ])
    . kpi_card_html(['icon' => 'library', 'value' => $elearnCounts['categories'], 'label' => 'Categories in use', 'tone' => 'amber']);

$statusTabs = '
        <button type="button" data-filter="all" class="q-btn is-active">All &middot; ' . e((string) $elearnCounts['total']) . '</button>
        <button type="button" data-filter="draft" class="q-btn">Draft &middot; ' . e((string) $elearnCounts['drafts']) . '</button>
        <button type="button" data-filter="published" class="q-btn">Published &middot; ' . e((string) $elearnCounts['published']) . '</button>';

$categoryTabs = '
      <button type="button" data-category="all" class="q-btn is-active">All categories &middot; ' . e((string) $elearnCatCounts['all']) . '</button>';
foreach (ELEARN_CATEGORIES as $c) {
    $categoryTabs .= '
      <button type="button" data-category="' . e($c['key']) . '" class="q-btn">' . e($c['label']) . ' &middot; ' . e((string) $elearnCatCounts[$c['key']]) . '</button>';
}

$filterTabs = '
  <div class="report-toolbar">
    <div class="q-tabs" id="elearn-status-tabs">
      ' . $statusTabs . '
    </div>
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="elearn-search" type="text" placeholder="Search title…" value="">
    </div>
  </div>
  <div class="report-toolbar elearn-cat-toolbar">
    <div class="q-tabs" id="elearn-category-tabs">' . $categoryTabs . '
    </div>
  </div>';

if ($modules === []) {
    $emptyBlock = '<div class="queue-empty">' . empty_state('book-open', 'No modules yet. Create your first lesson.') . '</div>';
    $libraryBody = '<div class="elearn-cards">' . $emptyBlock . '</div><div class="elearn-table">' . $emptyBlock . '</div>';
} else {
    $cardsHtml = '';
    $rowsHtml = '';
    foreach ($pageRows as $m) {
        $id = e((string) ($m['id'] ?? ''));
        $title = e(($m['title'] ?? '') !== '' ? (string) $m['title'] : 'Untitled');
        $catLabel = e(elearn_category_label($m['category'] ?? null));
        $statusCls = e(elearn_status_stamp($m['published_status'] ?? null));
        $statusText = e(elearn_status_label($m['published_status'] ?? null));
        $when = e(time_ago($m['created_at'] ?? null));
        $actions = elearn_row_actions($m);
        $cardsHtml .= '
  <article class="panel panel--padded elearn-mod-card" data-id="' . $id . '">
    <div class="elearn-mod-card-top">
      <span class="stamp stamp--sm stamp--coral">' . $catLabel . '</span>
      <span class="stamp stamp--sm ' . $statusCls . '">' . $statusText . '</span>
    </div>
    <h3 class="panel-title">' . $title . '</h3>
    <p class="page-sub">' . $when . '</p>
    ' . $actions . '
  </article>';
        $rowsHtml .= '
    <tr data-id="' . $id . '">
      <td class="table-cell table-cell--strong">' . $title . '</td>
      <td class="table-cell"><span class="stamp stamp--sm stamp--coral">' . $catLabel . '</span></td>
      <td class="table-cell"><span class="stamp stamp--sm ' . $statusCls . '">' . $statusText . '</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">' . $when . '</td>
      <td class="table-cell table-cell--right table-cell--nowrap">' . $actions . '</td>
    </tr>';
    }
    $pagination = count($modules) > ELEARN_PAGE_SIZE
        ? '<div class="queue-pagination">' . pagination_bar(count($modules), ELEARN_PAGE_SIZE, 1) . '</div>'
        : '';
    $libraryBody = '
    <div class="elearn-cards">' . $cardsHtml . '</div>
    <div class="elearn-table table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Title</th><th>Category</th><th>Status</th><th>Created</th><th class="table-cell--right">Action</th>
          </tr>
        </thead>
        <tbody>' . $rowsHtml . '</tbody>
      </table>
    </div>
    ' . $pagination;
}

$pageHead = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Content</span>
      <h1 class="page-title">E-Learning</h1>
      <p class="page-sub">Author lessons for the resident Learning Hub. Drafts stay private until you publish.</p>
    </div>
    <div class="page-head-actions">
      ' . elearn_button('New module', 'default', 'default', 'plus', 'data-action="new"') . '
    </div>
  </div>';

$adminChildren = '
    <div class="elearn-page">
      <div id="elearn-library">' . $pageHead . '
      <div id="elearn-kpis" class="kpi-grid">' . $kpiTiles . '</div>
      <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="book-open"></i>
        <h2 class="panel-title">Module library</h2>
      </div>
    </div>
    <div id="elearn-filters">' . $filterTabs . '</div>
    <div id="elearn-table" class="panel-body">' . $libraryBody . '</div>
  </div>
      </div>
      <div id="elearn-editor" class="is-hidden"></div>
    </div>';
