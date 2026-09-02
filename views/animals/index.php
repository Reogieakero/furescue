    <div class="mx-auto w-full max-w-6xl">
      <h1 class="rpage-title">Adoption Gallery</h1>
      <p class="rpage-sub">Meet the rescued animals currently looking for a forever home in Mati City.</p>

      <div class="rfilterbar mt-5" role="search">
        <div class="rfilter-field rfilter-field--grow">
          <i data-lucide="search"></i>
          <input id="filter-q" type="search" placeholder="Search by name…" aria-label="Search animals by name">
        </div>
        <div class="rfilter-field">
          <i data-lucide="paw-print"></i>
          <select id="filter-species" aria-label="Filter by species">
            <option value="">All species</option>
            <option value="dog">Dogs</option>
            <option value="cat">Cats</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="rfilter-field">
          <i data-lucide="venus-and-mars"></i>
          <select id="filter-sex" aria-label="Filter by sex">
            <option value="">Any sex</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
        </div>
        <div class="rfilter-field">
          <i data-lucide="tag"></i>
          <input id="filter-breed" type="text" list="breed-list" placeholder="Breed type" aria-label="Filter by breed type">
          <datalist id="breed-list">
            <option value="aspin"></option>
            <option value="puspin"></option>
            <option value="labrador"></option>
            <option value="shih tzu"></option>
          </datalist>
        </div>
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
