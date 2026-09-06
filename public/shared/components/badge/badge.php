<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('badge_html')) {
    function badge_html(string $text = '', string $variant = 'default', string $className = '', string $icon = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $base = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2';
        $variantCls = match ($variant) {
            'secondary' => 'border-transparent bg-secondary text-secondary-foreground',
            'outline' => 'text-foreground',
            'destructive' => 'border-transparent bg-destructive text-destructive-foreground',
            'success' => 'border-transparent bg-primary/10 text-primary',
            'accent' => 'border-transparent bg-accent text-accent-foreground',
            default => 'border-transparent bg-primary text-primary-foreground',
        };
        $cls = trim($base . ' ' . $variantCls . ($className !== '' ? ' ' . $className : ''));
        $iconHtml = $icon !== '' ? '<i data-lucide="' . $esc($icon) . '" class="badge-icon"></i>' : '';
        return '<span class="' . $esc($cls) . '">' . $iconHtml . $esc($text) . '</span>';
    }
}
