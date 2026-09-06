<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('empty_state')) {
    function empty_state(string $icon = 'inbox', string $text = 'No records.', string $className = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $cls = trim('empty-state' . ($className !== '' ? ' ' . $className : ''));
        return '<div class="' . $esc($cls) . '"><i data-lucide="' . $esc($icon) . '"></i><span>' . $esc($text) . '</span></div>';
    }
}
