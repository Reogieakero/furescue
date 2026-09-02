<?php

// ---- species vaccination breakdowns (charts.js cards) ----------------------
$hrSpeciesBreakdown = static function (string $species) use ($records): array {
    $list = array_values(array_filter($records, static fn($r) => $r['species'] === $species));
    return [
        'total' => count($list),
        'complete' => count(array_filter($list, static fn($r) => $r['vaccinationStatus'] === 'complete')),
        'partial' => count(array_filter($list, static fn($r) => $r['vaccinationStatus'] === 'partial')),
        'none' => count(array_filter($list, static fn($r) => $r['vaccinationStatus'] === 'none')),
    ];
};
$dogB = $hrSpeciesBreakdown('dog');
$catB = $hrSpeciesBreakdown('cat');

$hrVaxLegend = static function (array $b): string {
    $segs = [
        ['label' => 'Complete', 'val' => $b['complete'], 'cls' => 'status-seg--complete'],
        ['label' => 'Partial', 'val' => $b['partial'], 'cls' => 'status-seg--partial'],
        ['label' => 'Not vaccinated', 'val' => $b['none'], 'cls' => 'status-seg--none'],
    ];
    $out = '';
    foreach ($segs as $s) {
        $out .= '<span class="status-legend-item"><span class="status-dot ' . e($s['cls']) . '"></span>' . e($s['label']) . ' &middot; ' . e((string) $s['val']) . '</span>';
    }
    return $out;
};
$hrVaxGroup = static function (string $label, array $items): string {
    $lis = '';
    foreach ($items as $i) {
        $lis .= '<li>' . e($i) . '</li>';
    }
    return '
    <div class="hr-vax-group">
      <span class="hr-vax-group-label">' . e($label) . '</span>
      <ul class="hr-vax-items">' . $lis . '</ul>
    </div>';
};
$hrVaxList = static function (array $core, array $nonCore) use ($hrVaxGroup): string {
    return '<div class="hr-vax-list">' . $hrVaxGroup('Core', $core) . $hrVaxGroup('Non-core', $nonCore) . '</div>';
};

$hrDonutCard = static function (string $species, string $title, string $icon, string $canvasId, array $b, string $listHtml) use ($hrVaxLegend): string {
    $donutLabel = $species === 'dog' ? 'Dogs' : 'Cats';
    return "
  <div class=\"panel panel--padded\">
    <div class=\"panel-title-wrap\"><i data-lucide=\"{$icon}\"></i><h2 class=\"panel-title panel-title--sm\">{$title}</h2></div>
    <div class=\"donut-wrap\">
      <div class=\"donut\">
        <canvas id=\"{$canvasId}\"></canvas>
        <div class=\"donut-center\"><span class=\"donut-total\">" . e((string) $b['total']) . "</span><span class=\"donut-label\">{$donutLabel}</span></div>
      </div>
      <div class=\"status-legend\">" . $hrVaxLegend($b) . '</div>
    </div>
    ' . $listHtml . '
  </div>';
};
$dogCard = $hrDonutCard('dog', 'Dog vaccinations', 'dog', 'hr-donut-dog', $dogB, $hrVaxList(['DHPP / DAPP', 'Rabies', 'Leptospirosis'], ['Bordetella', 'Canine Influenza', 'Lyme']));
$catCard = $hrDonutCard('cat', 'Cat vaccinations', 'cat', 'hr-donut-cat', $catB, $hrVaxList(['FVRCP', 'Rabies', 'FeLV (Feline Leukemia Virus)'], ['Chlamydia felis', 'Bordetella']));

// ---- topConditions() top 6 over the filtered set ---------------------------
$condCounts = [];
foreach ($visible as $r) {
    $c = (string) $r['condition'];
    $condCounts[$c] = ($condCounts[$c] ?? 0) + 1;
}
// Object.entries() equivalent: insertion-ordered [condition, count] pairs
$condEntries = array_map(null, array_keys($condCounts), array_values($condCounts));
usort($condEntries, static fn(array $a, array $b): int => $b[1] <=> $a[1]);
$condEntries = array_slice($condEntries, 0, 6);
$condMax = $condEntries ? $condEntries[0][1] : 1;

const HR_COND_COLORS = [
    'Healthy' => 'hsl(152, 64%, 42%)',
    'Mange' => 'hsl(28, 90%, 55%)',
    'Malnutrition' => 'hsl(40, 92%, 50%)',
    'Fracture' => 'hsl(0, 72%, 51%)',
    'Parvovirus' => 'hsl(280, 60%, 55%)',
    'Tick fever' => 'hsl(199, 74%, 53%)',
    'Respiratory infection' => 'hsl(211, 71%, 48%)',
    'Wound care' => 'hsl(14, 78%, 55%)',
];

if ($condEntries) {
    $condRows = '';
    foreach ($condEntries as [$label, $val]) {
        $pctBar = $condMax ? js_round(($val / $condMax) * 100) : 0;
        $color = HR_COND_COLORS[$label] ?? 'hsl(199, 74%, 53%)';
        $condRows .= '
        <div class="hr-cond-row">
          <span class="hr-cond-label">' . e($label) . '</span>
          <span class="hr-cond-val">' . e((string) $val) . '</span>
          <span class="hr-cond-track"><span class="hr-cond-bar" style="width:' . $pctBar . '%;background:' . e($color) . '"></span></span>
        </div>';
    }
} else {
    $condRows = '<div class="empty-state"><i data-lucide="check-circle-2"></i><span>No conditions to summarise.</span></div>';
}
$conditionsPanel = '
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="stethoscope"></i><h2 class="panel-title panel-title--sm">Top conditions</h2></div>
    <div class="hr-cond-list">' . $condRows . '</div>
  </div>';
