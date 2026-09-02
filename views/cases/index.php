<?php

declare(strict_types=1);

$esc = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$dutyOn = ($dutyStatus ?? 'off_duty') === 'on_duty';
?>
    <div class="mx-auto w-full max-w-4xl min-w-0">
      <div class="rpage-head">
        <div class="min-w-0">
          <h1 class="rpage-title">My Cases</h1>
          <p class="rpage-sub">Accept assigned rescues, update in-progress work, and file proof when you finish.</p>
        </div>
        <div class="rpage-actions">
          <button type="button" id="duty-toggle" class="rbtn rbtn--sm <?= $dutyOn ? 'rbtn--solid' : 'rbtn--ghost' ?>" data-status="<?= $esc($dutyStatus) ?>" aria-pressed="<?= $dutyOn ? 'true' : 'false' ?>" aria-label="Toggle duty status">
            <i data-lucide="<?= $dutyOn ? 'siren' : 'circle-dot' ?>"></i>
            <span><?= $dutyOn ? 'On duty' : 'Off duty' ?></span>
          </button>
          <button type="button" id="refresh-cases" class="rbtn rbtn--ghost rbtn--sm">
            <i data-lucide="refresh-cw"></i><span>Refresh</span>
          </button>
        </div>
      </div>

      <div class="rtabs" role="tablist" aria-label="Filter cases by status">
        <button type="button" class="rtab is-active" data-status="" role="tab" aria-selected="true">All</button>
        <button type="button" class="rtab" data-status="assigned" role="tab" aria-selected="false">Assigned</button>
        <button type="button" class="rtab" data-status="in_progress" role="tab" aria-selected="false">In Progress</button>
        <button type="button" class="rtab" data-status="resolved" role="tab" aria-selected="false">Resolved</button>
      </div>

      <p id="cases-count" class="mt-3 text-sm text-muted-foreground" aria-live="polite"></p>
      <ul id="cases-list" class="rlist mt-2"></ul>

      <div id="cases-empty" class="rempty mt-2" hidden>
        <i data-lucide="clipboard-list"></i>
        <p class="rempty-title">No cases in this view</p>
        <p class="rempty-text">When the team assigns a rescue to you, it will show up here.</p>
      </div>
    </div>
