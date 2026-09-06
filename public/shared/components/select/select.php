<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('select_control')) {
    function select_control(string $id = '', array $options = [], string $value = '', string $placeholder = 'Select', string $triggerClassName = '', string $contentClassName = '', string $className = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $chevron = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="m6 9 6 6 6-6"/></svg>';
        $check = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
        $label = $placeholder;
        foreach ($options as $o) {
            if (($o['value'] ?? '') === $value) {
                $label = (string) ($o['label'] ?? '');
                break;
            }
        }
        $items = '';
        foreach ($options as $o) {
            $selected = ($o['value'] ?? '') === $value;
            $itemCls = 'flex w-full cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm transition-colors'
                . ($selected ? ' bg-accent text-accent-foreground' : ' hover:bg-accent hover:text-accent-foreground');
            $items .= '
  <div role="option" aria-selected="' . ($selected ? 'true' : 'false') . '" data-select-item data-value="' . $esc((string) ($o['value'] ?? '')) . '" class="' . $esc($itemCls) . '">
    <span data-select-item-label>' . $esc((string) ($o['label'] ?? '')) . '</span>
    ' . ($selected ? '<span data-select-check class="shrink-0">' . $check . '</span>' : '') . '
  </div>';
        }
        $wrapCls = trim('relative inline-block' . ($className !== '' ? ' ' . $className : ''));
        $triggerCls = 'flex h-8 w-full items-center justify-between gap-2 whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-1 focus:ring-ring'
            . ($triggerClassName !== '' ? ' ' . $triggerClassName : '');
        $contentCls = 'absolute left-0 top-full z-50 mt-1 hidden min-w-full overflow-hidden rounded-md border border-input bg-card text-card-foreground shadow-md'
            . ($contentClassName !== '' ? ' ' . $contentClassName : '');
        return '
  <div id="' . $esc($id) . '" data-select class="' . $esc($wrapCls) . '">
    <button type="button" data-select-trigger aria-haspopup="listbox" aria-expanded="false" class="' . $esc($triggerCls) . '">
      <span data-select-value>' . $esc($label) . '</span>
      <span class="shrink-0 opacity-50">' . $chevron . '</span>
    </button>
    <div data-select-content role="listbox" class="' . $esc($contentCls) . '">' . $items . '
    </div>
  </div>';
    }
}
