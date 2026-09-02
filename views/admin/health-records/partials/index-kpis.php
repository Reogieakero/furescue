<?php

$hrKpis = '';
foreach ($hrKpiData as $k) {
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
    $hrKpis .= '
  <' . $tag . ' class="kpi-card' . $extraClass . '"' . $typeAttr . $filterAttr . ' aria-label="' . e($aria) . '"' . $title . '>
    <div class="kpi-card__icon kpi-card__icon--' . e((string) $k['tone']) . '" aria-hidden="true"><i data-lucide="' . e((string) $k['icon']) . '"></i></div>
    <div class="kpi-card__body">
      <p class="kpi-card__label">' . e($label) . '</p>
      <p class="kpi-card__value">' . e($value) . '</p>
      ' . $trend . '
    </div>
  </' . $tag . '>';
}
$hrKpisHtml = "<div class=\"kpi-grid\">{$hrKpis}</div>";
