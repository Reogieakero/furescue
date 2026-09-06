    <div class="mx-auto w-full max-w-4xl">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 class="rpage-title">My Adoptions</h1>
          <p class="rpage-sub">Track the applications you have submitted.</p>
        </div>
        <a href="/animals/" class="rbtn rbtn--ghost"><i data-lucide="paw-print"></i><span>Browse animals</span></a>
      </div>

      <div class="rtabs mt-5" role="tablist" aria-label="Filter applications by status">
        <button type="button" class="rtab is-active" data-status="" role="tab" aria-selected="true">All</button>
        <button type="button" class="rtab" data-status="pending" role="tab" aria-selected="false">Pending</button>
        <button type="button" class="rtab" data-status="approved" role="tab" aria-selected="false">Approved</button>
        <button type="button" class="rtab" data-status="rejected" role="tab" aria-selected="false">Rejected</button>
        <button type="button" class="rtab" data-status="completed" role="tab" aria-selected="false">Completed</button>
        <button type="button" class="rtab" data-status="cancelled" role="tab" aria-selected="false">Cancelled</button>
      </div>

      <ul class="rlist mt-4" id="adoption-list"></ul>

      <div id="adoptions-empty" class="rempty mt-2" hidden>
        <i data-lucide="file-heart"></i>
        <p class="rempty-title">No applications here yet</p>
        <p class="rempty-text">When you apply to adopt an animal, it will show up in this list.</p>
        <a href="/animals/" class="rbtn rbtn--solid"><i data-lucide="search"></i><span>Find a friend</span></a>
        <p class="text-sm text-muted-foreground">Looking to rehome a rescued animal instead?
          <a href="/listings/" class="underline text-primary font-semibold">Post an adoption listing</a>.</p>
      </div>
    </div>
