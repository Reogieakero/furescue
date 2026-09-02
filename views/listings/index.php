    <div class="mx-auto w-full max-w-4xl">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 class="rpage-title">Community Listings</h1>
          <p class="rpage-sub">Rehome a rescued animal — listings go live after a quick review by the City Veterinarian's Office.</p>
        </div>
        <button type="button" id="btn-new-listing" class="rbtn rbtn--solid"><i data-lucide="megaphone"></i><span>Post for adoption</span></button>
      </div>

      <ul class="rlist mt-5" id="listing-list"></ul>

      <div id="listings-empty" class="rempty mt-2" hidden>
        <i data-lucide="megaphone"></i>
        <p class="rempty-title">No listings yet</p>
        <p class="rempty-text">Rescued an animal that needs a new home? Post it here and we'll review it.</p>
        <a href="/animals/" class="rbtn rbtn--ghost"><i data-lucide="search"></i><span>Browse adoptable animals</span></a>
      </div>
    </div>
