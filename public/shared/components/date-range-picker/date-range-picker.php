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
                : date_range_pretty($start) . ' – ' . date_range_pretty($end);
        }
        if ($start !== '') {
            return 'From ' . date_range_pretty($start);
        }
        if ($end !== '') {
            return 'Through ' . date_range_pretty($end);
        }
        return $placeholder;
    }
}

if (!function_exists('date_range_picker')) {
    /**
     * Compact one-month range: pick start then end, then Apply.
     *
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
        $label = date_range_label($start, $end, $placeholder);
        $labelCls = 'dp-trigger-label' . ($start === '' ? ' is-placeholder' : '');
        $errorId = $id !== '' ? $id . '-error' : '';
        $wrapCls = trim('dp-range' . ($className !== '' ? ' ' . $className : ''));
        $errorAttr = $errorId !== '' ? ' id="' . $esc($errorId) . '"' : '';

        return '
  <div id="' . $esc($id) . '" class="' . $esc($wrapCls) . '" data-date-range data-min="' . $esc($min) . '" data-max="' . $esc($max) . '" data-placeholder="' . $esc($placeholder) . '">
    <button type="button" class="dp-trigger" data-range-trigger aria-haspopup="dialog" aria-expanded="false" aria-label="Date range">
      <span class="' . $esc($labelCls) . '" data-range-label>' . $esc($label) . '</span>
      <i data-lucide="chevron-down" class="dp-trigger-caret"></i>
    </button>
    <div class="dp-popover" data-range-popover hidden role="dialog" aria-label="Choose date range">
      <div class="dp-cals" data-range-cals></div>
      <p class="dp-range-error" data-range-error' . $errorAttr . ' hidden>The start date must be on or before the end date.</p>
      <p class="dp-range-live" data-range-live aria-live="polite"></p>
      <div class="dp-range-actions">
        <button type="button" class="dp-range-today" data-range-today>Today</button>
        <button type="button" class="dp-range-clear" data-range-clear>Clear</button>
        <button type="button" class="dp-range-apply" data-range-apply>Apply</button>
      </div>
    </div>
    <input type="hidden" data-range-start id="' . $esc($startId) . '" name="' . $esc($startName) . '" value="' . $esc($start) . '">
    <input type="hidden" data-range-end id="' . $esc($endId) . '" name="' . $esc($endName) . '" value="' . $esc($end) . '">
  </div>';
    }
}
