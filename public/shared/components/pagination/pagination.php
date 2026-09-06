<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('pagination_page_sizes')) {
    function pagination_page_sizes(): array
    {
        return [10, 20, 50, 100];
    }
}

if (!function_exists('pagination_default_page_size')) {
    function pagination_default_page_size(): int
    {
        return 20;
    }
}

if (!function_exists('pagination_page_items')) {
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
}

if (!function_exists('pagination_bar')) {
    function pagination_bar(int $total = 0, int $perPage = 10, int $page = 1, string $className = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $perPage = max(1, $perPage);
        $pageTotal = max(1, (int) ceil($total / $perPage));
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
            $items[] = '<li class=""><button data-page="' . $p . '" class="' . $esc($cls) . '"' . ($active ? ' aria-current="page"' : '') . '>' . $p . '</button></li>';
        }

        $prevDisabled = $cur <= 1;
        $nextDisabled = $cur >= $pageTotal;
        $prevBtn = '<button data-page="' . max(1, $cur - 1) . '" class="' . $esc($arrowBase . ($prevDisabled ? ' pointer-events-none opacity-50' : '')) . '"' . ($prevDisabled ? ' aria-disabled="true"' : '') . '><i data-lucide="chevron-left" class="h-4 w-4"></i>Previous</button>';
        $nextBtn = '<button data-page="' . min($pageTotal, $cur + 1) . '" class="' . $esc($arrowBase . ($nextDisabled ? ' pointer-events-none opacity-50' : '')) . '"' . ($nextDisabled ? ' aria-disabled="true"' : '') . '>Next<i data-lucide="chevron-right" class="h-4 w-4"></i></button>';

        $sizes = pagination_page_sizes();
        if (!in_array($perPage, $sizes, true)) {
            $sizes[] = $perPage;
            sort($sizes);
        }
        $opts = '';
        foreach ($sizes as $n) {
            $opts .= '<option value="' . $n . '"' . ($n === $perPage ? ' selected' : '') . '>' . $n . '</option>';
        }
        $from = $total === 0 ? 0 : (($cur - 1) * $perPage) + 1;
        $to = min($total, $cur * $perPage);
        $meta = '<div class="pagination-meta"><label class="pagination-page-size"><span class="pagination-page-size-label">Rows per page</span><select class="pagination-page-size-select" data-per-page aria-label="Rows per page">' . $opts . '</select></label><span class="pagination-range">' . $from . '–' . $to . ' of ' . $total . '</span></div>';

        $barClass = trim('pagination-bar' . ($className !== '' ? ' ' . $className : ''));
        return '<div class="' . $esc($barClass) . '">' . $meta . '<nav class="flex w-auto justify-end" aria-label="Pagination"><ul class="flex flex-wrap items-center gap-1"><li class="">' . $prevBtn . '</li>' . implode('', $items) . '<li class="">' . $nextBtn . '</li></ul></nav></div>';
    }
}
