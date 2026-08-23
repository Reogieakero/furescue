<?php

declare(strict_types=1);

const BTN_BASE = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background';
const BTN_VARIANT_DEFAULT = 'bg-primary text-primary-foreground shadow hover:bg-primary/90';
const BTN_VARIANT_OUTLINE = 'border border-input bg-background hover:bg-accent hover:text-accent-foreground';
const BTN_VARIANT_GHOST = 'hover:bg-accent hover:text-accent-foreground';
const BTN_SIZE_DEFAULT = 'h-8 px-4';
const BTN_SIZE_SM = 'h-7 px-3';
const BTN_SIZE_LG = 'h-10 px-6 text-sm';

function e(mixed $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function js_round(float|int $x): int
{
    return (int) floor((float) $x + 0.5);
}

function short_id(mixed $id): string
{
    if (!$id) {
        return '—';
    }
    $s = str_replace('-', '', (string) $id);
    return '#' . strtoupper(substr($s, 0, 4));
}

function time_ago(mixed $value): string
{
    if (!$value) {
        return '—';
    }
    $ts = strtotime((string) $value);
    if ($ts === false) {
        return '—';
    }
    $today = strtotime('today');
    $day = mktime(0, 0, 0, (int) date('n', $ts), (int) date('j', $ts), (int) date('Y', $ts));
    $diff = js_round(($today - $day) / 86400);
    if ($diff === 0) {
        return date('h:i A', $ts);
    }
    if ($diff === 1) {
        return 'Yesterday';
    }
    if ($diff < 7) {
        return "{$diff} days ago";
    }
    return date('M j', $ts);
}

function title_case(mixed $value): string
{
    $s = str_replace('_', ' ', (string) ($value ?? ''));
    $words = preg_split('/\s+/', trim($s)) ?: [];
    $out = [];
    foreach ($words as $w) {
        if ($w === '') {
            continue;
        }
        $out[] = mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($w, 1, null, 'UTF-8');
    }
    return implode(' ', $out);
}

function truncate_text(mixed $text, int $n = 22): string
{
    if (!$text) {
        return '—';
    }
    $t = (string) $text;
    return mb_strlen($t, 'UTF-8') > $n ? mb_substr($t, 0, $n - 1, 'UTF-8') . '…' : $t;
}

function initials_of(mixed $name): string
{
    if (!$name) {
        return '?';
    }
    $words = preg_split('/\s+/u', trim((string) $name)) ?: [];
    $words = array_values(array_filter($words, fn($w) => $w !== ''));
    $first = array_slice($words, 0, 2);
    $out = '';
    foreach ($first as $w) {
        $out .= mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return $out;
}

function avatar_img(mixed $src, mixed $name): string
{
    if ($src) {
        return '<img class="table-avatar" src="' . e($src) . '" alt="">';
    }
    return '<span class="table-avatar table-avatar--initial">' . e(initials_of($name)) . '</span>';
}

function rescuer_avatar(mixed $src, mixed $name): string
{
    if ($src) {
        return '<img class="rescuer-avatar" src="' . e($src) . '" alt="">';
    }
    return '<span class="rescuer-avatar rescuer-avatar--initial">' . e(initials_of($name)) . '</span>';
}

function table_head(array $cols): string
{
    $th = '';
    foreach ($cols as $c) {
        $th .= '<th>' . e($c) . '</th>';
    }
    return '
  <thead>
    <tr class="table-head">
      ' . $th . '
    </tr>
  </thead>';
}

function empty_state(string $icon = 'inbox', string $text = 'No records.'): string
{
    return '<div class="empty-state"><i data-lucide="' . e($icon) . '"></i><span>' . e($text) . '</span></div>';
}

function chevron_right(): string
{
    return '<i data-lucide="chevron-right" class="link-chevron"></i>';
}

function button_classes(string $variant = 'default', string $size = 'default', string $className = ''): string
{
    $variantCls = match ($variant) {
        'outline' => BTN_VARIANT_OUTLINE,
        'ghost' => BTN_VARIANT_GHOST,
        default => BTN_VARIANT_DEFAULT,
    };
    $sizeCls = match ($size) {
        'sm' => BTN_SIZE_SM,
        'lg' => BTN_SIZE_LG,
        'icon' => 'h-8 w-8',
        default => BTN_SIZE_DEFAULT,
    };
    $base = BTN_BASE;
    if ($size === 'lg') {
        $base = str_replace('text-[13px]', 'text-sm', $base);
    }
    $cls = trim($base . ' ' . $variantCls . ' ' . $sizeCls . ($className !== '' ? ' ' . $className : ''));
    return $cls;
}

function button_html(string $text = '', string $variant = 'default', string $size = 'default', string $className = '', string $icon = '', string $attrs = '', string $type = 'button'): string
{
    $inner = ($icon !== '' ? '<i data-lucide="' . e($icon) . '" class="icon"></i>' : '') . '<span>' . e($text) . '</span>';
    return '<button type="' . e($type) . '" class="' . e(button_classes($variant, $size, $className)) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . $inner . '</button>';
}

function button_anchor_html(string $href, string $text = '', string $variant = 'default', string $size = 'default', string $className = '', string $icon = '', string $attrs = ''): string
{
    $inner = ($icon !== '' ? '<i data-lucide="' . e($icon) . '" class="icon"></i>' : '') . '<span>' . e($text) . '</span>';
    return '<a href="' . e($href) . '" class="' . e(button_classes($variant, $size, $className)) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . $inner . '</a>';
}

function pagination_page_items(int $current, int $totalPages): array
{
    $set = array_unique(array_filter([1, $totalPages, $current - 1, $current, $current + 1], fn($p) => $p >= 1 && $p <= $totalPages));
    sort($set);
    $out = [];
    $prev = 0;
    foreach ($set as $p) {
        if ($p - $prev > 1) {
            $out[] = 'ellipsis';
        }
        $out[] = $p;
        $prev = $p;
    }
    return $out;
}

function pagination_bar(int $total = 0, int $perPage = 10, int $page = 1, string $className = ''): string
{
    $pageTotal = max(1, (int) ceil($total / max(1, $perPage)));
    $cur = min(max(1, $page), $pageTotal);
    $linkBase = 'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';
    $arrowBase = 'inline-flex h-8 items-center gap-1 rounded-md border border-input bg-background px-2.5 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground';

    $items = [];
    foreach (pagination_page_items($cur, $pageTotal) as $p) {
        if ($p === 'ellipsis') {
            $items[] = '<li class=""><span class="flex h-8 w-8 items-center justify-center"><i data-lucide="ellipsis" class="h-4 w-4"></i></span></li>';
            continue;
        }
        $active = $p === $cur;
        $cls = $active
            ? $linkBase . ' border-primary bg-primary text-primary-foreground'
            : $linkBase . ' border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground';
        $items[] = '<li class=""><button data-page="' . $p . '" class="' . e($cls) . '"' . ($active ? ' aria-current="page"' : '') . '>' . $p . '</button></li>';
    }

    $prevDisabled = $cur <= 1;
    $nextDisabled = $cur >= $pageTotal;
    $prevBtn = '<button data-page="' . max(1, $cur - 1) . '" class="' . e($arrowBase . ($prevDisabled ? ' pointer-events-none opacity-50' : '')) . '"' . ($prevDisabled ? ' aria-disabled="true"' : '') . '><i data-lucide="chevron-left" class="h-4 w-4"></i>Previous</button>';
    $nextBtn = '<button data-page="' . min($pageTotal, $cur + 1) . '" class="' . e($arrowBase . ($nextDisabled ? ' pointer-events-none opacity-50' : '')) . '"' . ($nextDisabled ? ' aria-disabled="true"' : '') . '>Next<i data-lucide="chevron-right" class="h-4 w-4"></i></button>';

    return '<nav class="' . e(trim('mx-auto flex w-full justify-center' . ($className !== '' ? ' ' . $className : ''))) . '" aria-label="Pagination"><ul class="flex items-center gap-1"><li class="">' . $prevBtn . '</li>' . implode('', $items) . '<li class="">' . $nextBtn . '</li></ul></nav>';
}

function select_control(string $id = '', array $options = [], string $value = '', string $placeholder = 'Select', string $triggerClassName = '', string $contentClassName = '', string $className = ''): string
{
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
  <div role="option" aria-selected="' . ($selected ? 'true' : 'false') . '" data-select-item data-value="' . e($o['value'] ?? '') . '" class="' . e($itemCls) . '">
    <span data-select-item-label>' . e($o['label'] ?? '') . '</span>
    ' . ($selected ? '<span data-select-check class="shrink-0">' . $check . '</span>' : '') . '
  </div>';
    }
    $wrapCls = trim('relative inline-block' . ($className !== '' ? ' ' . $className : ''));
    $triggerCls = 'flex h-8 w-full items-center justify-between gap-2 whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-1 focus:ring-ring'
        . ($triggerClassName !== '' ? ' ' . $triggerClassName : '');
    $contentCls = 'absolute left-0 top-full z-50 mt-1 hidden min-w-full overflow-hidden rounded-md border border-input bg-card text-card-foreground shadow-md'
        . ($contentClassName !== '' ? ' ' . $contentClassName : '');
    return '
  <div id="' . e($id) . '" data-select class="' . e($wrapCls) . '">
    <button type="button" data-select-trigger aria-haspopup="listbox" aria-expanded="false" class="' . e($triggerCls) . '">
      <span data-select-value>' . e($label) . '</span>
      <span class="shrink-0 opacity-50">' . $chevron . '</span>
    </button>
    <div data-select-content role="listbox" class="' . e($contentCls) . '">' . $items . '
    </div>
  </div>';
}
