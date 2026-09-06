<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('search_control')) {
    function search_control(string $id = '', string $placeholder = '', string $value = '', string $className = '', string $attrs = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $cls = trim('search-field report-search' . ($className !== '' ? ' ' . $className : ''));
        $extra = $attrs !== '' ? ' ' . $attrs : '';
        return '<div class="' . $esc($cls) . '"><i data-lucide="search"></i><input id="' . $esc($id) . '" type="text" placeholder="' . $esc($placeholder) . '" value="' . $esc($value) . '"' . $extra . '></div>';
    }
}
