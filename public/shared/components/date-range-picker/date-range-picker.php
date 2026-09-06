<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('date_range_pretty')) {
    function date_range_pretty(string $iso): string
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $iso);
        return $dt instanceof \DateTime ? $dt->format('M j, Y') : $iso;
    }
}

if (!function_exists('date_range_label')) {
    function date_range_label(string $start, string $end, string $placeholder = 'Pick dates'): string
    {
        if ($start !== '' && $end !== '') {
            return $start === $end
                ? date_range_pretty($start)
                : date_range_pretty($start) . ' to ' . date_range_pretty($end);
        }
        if ($start !== '') {
            return date_range_pretty($start) . ' to …';
        }
        return $placeholder;
    }
}

if (!function_exists('date_range_picker')) {
    /**
     * @param array{
     *   id?: string,
     *   start_id?: string,
     *   end_id?: string,
     *   start_name?: string,
     *   end_name?: string,
     *   start?: string,
     *   end?: string,
     *   min?: string,
     *   max?: string,
     *   placeholder?: string,
     *   className?: string,
     *   presets?: bool
     * } $opts
     */
    function date_range_picker(array $opts = []): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $id = (string) ($opts['id'] ?? '');
        $startId = (string) ($opts['start_id'] ?? ($id !== '' ? $id . '-start' : ''));
        $endId = (string) ($opts['end_id'] ?? ($id !== '' ? $id . '-end' : ''));
        $startName = (string) ($opts['start_name'] ?? 'start');
        $endName = (string) ($opts['end_name'] ?? 'end');
        $start = (string) ($opts['start'] ?? '');
        $end = (string) ($opts['end'] ?? '');
        $min = (string) ($opts['min'] ?? '');
        $max = (string) ($opts['max'] ?? '');
        $placeholder = (string) ($opts['placeholder'] ?? 'Pick dates');
        $className = (string) ($opts['className'] ?? '');
        $presets = ($opts['presets'] ?? true) !== false;
        $label = date_range_label($start, $end, $placeholder);
        $labelCls = 'dp-trigger-label' . ($start === '' ? ' is-placeholder' : '');
        $presetItems = [
            ['today', 'Today'],
            ['7d', '7 days'],
            ['30d', '30 days'],
            ['month', 'This month'],
        ];
        $presetHtml = '';
        if ($presets) {
            $presetHtml = '<div class="dp-presets">';
            foreach ($presetItems as [$pid, $plabel]) {
                $presetHtml .= '<button type="button" class="dp-preset" data-range-preset="' . $esc($pid) . '">' . $esc($plabel) . '</button>';
            }
            $presetHtml .= '<button type="button" class="dp-preset dp-preset--clear" data-range-clear>Clear</button></div>';
        }
        $wrapCls = trim('dp-range' . ($className !== '' ? ' ' . $className : ''));
        return '
  <div id="' . $esc($id) . '" class="' . $esc($wrapCls) . '" data-date-range data-min="' . $esc($min) . '" data-max="' . $esc($max) . '" data-placeholder="' . $esc($placeholder) . '">
    <button type="button" class="dp-trigger" data-range-trigger aria-haspopup="dialog" aria-expanded="false" aria-label="Date range">
      <i data-lucide="calendar-range" class="dp-trigger-icon"></i>
      <span class="' . $esc($labelCls) . '" data-range-label>' . $esc($label) . '</span>
      <i data-lucide="chevron-down" class="dp-trigger-caret"></i>
    </button>
    <div class="dp-popover" data-range-popover hidden role="dialog" aria-label="Choose date range">
      ' . $presetHtml . '
      <div class="dp-cals-toolbar">
        <button type="button" class="dp-nav" data-range-prev aria-label="Previous month"><i data-lucide="chevron-left"></i></button>
        <span class="dp-cals-caption" data-range-caption></span>
        <button type="button" class="dp-nav" data-range-next aria-label="Next month"><i data-lucide="chevron-right"></i></button>
      </div>
      <div class="dp-cals" data-range-cals></div>
      <p class="dp-hint" data-range-hint>Choose a start date, then an end date.</p>
    </div>
    <input type="hidden" data-range-start id="' . $esc($startId) . '" name="' . $esc($startName) . '" value="' . $esc($start) . '">
    <input type="hidden" data-range-end id="' . $esc($endId) . '" name="' . $esc($endName) . '" value="' . $esc($end) . '">
  </div>';
    }
}
