<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!defined('BTN_BASE')) {
    define('BTN_BASE', 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50');
}
if (!defined('BTN_VARIANT_DEFAULT')) {
    define('BTN_VARIANT_DEFAULT', 'bg-primary text-primary-foreground shadow hover:bg-primary/90');
}
if (!defined('BTN_VARIANT_SECONDARY')) {
    define('BTN_VARIANT_SECONDARY', 'bg-secondary text-secondary-foreground hover:bg-secondary/80');
}
if (!defined('BTN_VARIANT_OUTLINE')) {
    define('BTN_VARIANT_OUTLINE', 'border border-input bg-background hover:bg-accent hover:text-accent-foreground');
}
if (!defined('BTN_VARIANT_GHOST')) {
    define('BTN_VARIANT_GHOST', 'hover:bg-accent hover:text-accent-foreground');
}
if (!defined('BTN_VARIANT_DESTRUCTIVE')) {
    define('BTN_VARIANT_DESTRUCTIVE', 'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90');
}
if (!defined('BTN_SIZE_DEFAULT')) {
    define('BTN_SIZE_DEFAULT', 'h-8 px-4');
}
if (!defined('BTN_SIZE_SM')) {
    define('BTN_SIZE_SM', 'h-7 px-3');
}
if (!defined('BTN_SIZE_LG')) {
    define('BTN_SIZE_LG', 'h-10 px-6 text-sm');
}

if (!function_exists('button_classes')) {
    function button_classes(string $variant = 'default', string $size = 'default', string $className = ''): string
    {
        $variantCls = match ($variant) {
            'outline' => BTN_VARIANT_OUTLINE,
            'ghost' => BTN_VARIANT_GHOST,
            'secondary' => BTN_VARIANT_SECONDARY,
            'destructive' => BTN_VARIANT_DESTRUCTIVE,
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
        return trim($base . ' ' . $variantCls . ' ' . $sizeCls . ($className !== '' ? ' ' . $className : ''));
    }
}

if (!function_exists('button_html')) {
    function button_html(string $text = '', string $variant = 'default', string $size = 'default', string $className = '', string $icon = '', string $attrs = '', string $type = 'button'): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $inner = ($icon !== '' ? '<i data-lucide="' . $esc($icon) . '" class="icon"></i>' : '') . '<span>' . $esc($text) . '</span>';
        return '<button type="' . $esc($type) . '" class="' . $esc(button_classes($variant, $size, $className)) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . $inner . '</button>';
    }
}

if (!function_exists('button_anchor_html')) {
    function button_anchor_html(string $href, string $text = '', string $variant = 'default', string $size = 'default', string $className = '', string $icon = '', string $attrs = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $inner = ($icon !== '' ? '<i data-lucide="' . $esc($icon) . '" class="icon"></i>' : '') . '<span>' . $esc($text) . '</span>';
        return '<a href="' . $esc($href) . '" class="' . $esc(button_classes($variant, $size, $className)) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . $inner . '</a>';
    }
}
