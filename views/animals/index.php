<?php

declare(strict_types=1);

require_once shared_path('search/search.php');
require_once shared_path('select/select.php');

$speciesOptions = [
    ['value' => '', 'label' => 'All species'],
    ['value' => 'dog', 'label' => 'Dogs'],
    ['value' => 'cat', 'label' => 'Cats'],
];
$sexOptions = [
    ['value' => '', 'label' => 'Any sex'],
    ['value' => 'male', 'label' => 'Male'],
    ['value' => 'female', 'label' => 'Female'],
];
$breedOptions = [
    ['value' => '', 'label' => 'Any breed'],
    ['value' => 'aspin', 'label' => 'Aspin'],
    ['value' => 'puspin', 'label' => 'Puspin'],
];

?>
    <div class="mx-auto w-full max-w-6xl">
      <h1 class="rpage-title">Adoption Gallery</h1>
      <p class="rpage-sub">Meet the rescued animals currently looking for a forever home in Mati City.</p>

      <div class="rfilterbar mt-5" role="search">
        <?= search_control('filter-q', 'Search by name…', '', 'rfilter-search', 'aria-label="Search animals by name"') ?>
        <?= select_control('filter-species', $speciesOptions, '', 'All species', '', '', 'w-full') ?>
        <?= select_control('filter-sex', $sexOptions, '', 'Any sex', '', '', 'w-full') ?>
        <?= select_control('filter-breed', $breedOptions, '', 'Any breed', '', '', 'w-full') ?>
      </div>

      <p id="gallery-count" class="mt-3 text-sm text-muted-foreground" aria-live="polite"></p>

      <div id="gallery-grid" class="rgrid-cards mt-4"></div>

      <div id="gallery-empty" class="rempty mt-8" hidden>
        <i data-lucide="cat"></i>
        <p class="rempty-title">No animals match your filters</p>
        <p class="rempty-text">Try clearing a filter — or check back soon, new rescues arrive regularly.</p>
      </div>

      <div class="flex justify-center mt-6 mb-2">
        <button type="button" id="load-more" class="rbtn rbtn--ghost" hidden>
          <span>Load more animals</span><i data-lucide="chevron-down"></i>
        </button>
      </div>
    </div>
