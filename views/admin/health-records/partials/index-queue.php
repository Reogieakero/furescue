<?php

// ---- Attention queue (queue.js attentionItems + panel, first 6) ------------
$attentionItems = [];
foreach ($visible as $r) {
    $base = [
        'id' => (string) $r['id'],
        'animalName' => (string) $r['animalName'],
        'barangay' => (string) $r['barangay'],
        'species' => (string) $r['species'],
        'condition' => (string) $r['condition'],
    ];
    $due = $hrDaysUntil($r['nextCheckupDue']);
    if ($due < 0) {
        $attentionItems[] = $base + [
            'kind' => 'checkup',
            'icon' => 'stethoscope',
            'text' => 'Overdue checkup',
            'date' => (string) $r['nextCheckupDue'],
            'days' => $due,
            'tier' => $due <= -8 ? 'critical' : 'warn',
        ];
    }
    if (!empty($r['vaccinationExpiry'])) {
        $exp = $hrDaysUntil((string) $r['vaccinationExpiry']);
        if ($exp >= 0 && $exp <= 30) {
            $attentionItems[] = $base + [
                'kind' => 'vaccine',
                'icon' => 'syringe',
                'text' => 'Vaccination expiring',
                'date' => (string) $r['vaccinationExpiry'],
                'days' => $exp,
                'tier' => $exp <= 7 ? 'warn' : 'soon',
            ];
        }
    }
}
usort($attentionItems, static fn(array $a, array $b): int => $a['days'] <=> $b['days']);

$QUEUE_LIMIT = 6;
$attTotal = count($attentionItems);
$attOverdue = count(array_filter($attentionItems, static fn($i) => $i['kind'] === 'checkup'));
$attExpiring = count(array_filter($attentionItems, static fn($i) => $i['kind'] === 'vaccine'));

const HR_QUEUE_TIER = [
    'critical' => 'hr-queue-card--critical',
    'warn' => 'hr-queue-card--warn',
    'soon' => 'hr-queue-card--soon',
];
$hrDaysLabel = static function (int $d): string {
    if ($d < 0) {
        return abs($d) . 'd overdue';
    }
    if ($d === 0) {
        return 'Due today';
    }
    return $d . 'd left';
};
$hrQueueCards = '';
foreach (array_slice($attentionItems, 0, $QUEUE_LIMIT) as $it) {
    $tier = (string) $it['tier'];
    $speciesCap = $hrCap((string) $it['species']);
    $title = e((string) $it['animalName']) . ' · ' . e((string) $it['barangay']) . ' · ' . e((string) $it['text']) . ' — open in records';
    $hrQueueCards .= '
  <button type="button" class="hr-queue-card ' . e(HR_QUEUE_TIER[$tier]) . '" data-queue-card data-animal="' . e((string) $it['animalName']) . '" title="' . $title . '">
    <span class="hr-qc-head">
      <span class="hr-qc-kind"><i data-lucide="' . e((string) $it['icon']) . '"></i></span>
      <span class="stamp stamp--sm hr-qc-days hr-qc-days--' . e($tier) . '">' . e($hrDaysLabel((int) $it['days'])) . '</span>
    </span>
    <span class="hr-qc-name">' . e((string) $it['animalName']) . '</span>
    <span class="hr-qc-meta">' . e($speciesCap) . ' · ' . e((string) $it['barangay']) . '</span>
    <span class="hr-qc-reason">' . e((string) $it['text']) . '<span class="hr-qc-id">' . e($hrShortId($it['id'])) . '</span></span>
    <span class="hr-qc-foot">
      <span class="hr-qc-date"><i data-lucide="calendar"></i>' . e($hrFmtDate((string) $it['date'], 'short')) . '</span>
      <span class="hr-qc-go"><i data-lucide="chevron-right"></i></span>
    </span>
  </button>';
}
$queueBody = $attTotal
    ? $hrQueueCards
    : '<div class="empty-state hr-queue-empty"><i data-lucide="check-circle-2"></i><span>Nothing needs urgent attention.</span></div>';
$tally = $attTotal
    ? '<span class="stamp stamp--sm stamp--coral">' . e((string) $attOverdue) . ' overdue</span>
       <span class="stamp stamp--sm stamp--muted">' . e((string) $attExpiring) . ' expiring</span>'
    : '<span class="stamp stamp--sm stamp--accent">All clear</span>';

// allAttentionCount() over ALL records (nav badge)
$attentionCount = 0;
foreach ($records as $r) {
    if ($hrDaysUntil($r['nextCheckupDue']) < 0) {
        $attentionCount += 1;
    }
    if (!empty($r['vaccinationExpiry'])) {
        $exp = $hrDaysUntil((string) $r['vaccinationExpiry']);
        if ($exp >= 0 && $exp <= 30) {
            $attentionCount += 1;
        }
    }
}

$queuePanel = "
  <div class=\"panel\">
    <div class=\"panel-head\">
      <div class=\"panel-title-wrap\">
        <i data-lucide=\"bell\"></i>
        <h2 class=\"panel-title\">Attention queue</h2>
      </div>
      <div class=\"hr-queue-tally\">{$tally}</div>
    </div>
    <div class=\"hr-queue-grid\">{$queueBody}</div>
    " . ($attTotal > $QUEUE_LIMIT
        ? '<button class="hr-queue-all" data-queue-all type="button">View all ' . e((string) $attTotal) . '</button>'
        : '') . '
  </div>';
