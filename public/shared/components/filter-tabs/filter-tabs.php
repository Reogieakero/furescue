<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('filter_tabs_html')) {
    function filter_tabs_html(string $id = '', string $inner = '', string $className = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $cls = trim('q-tabs' . ($className !== '' ? ' ' . $className : ''));
        $idAttr = $id !== '' ? ' id="' . $esc($id) . '"' : '';
        return '<div' . $idAttr . ' class="' . $esc($cls) . '">' . $inner . '</div>';
    }
}

if (!function_exists('filter_tab_button_html')) {
    function filter_tab_button_html(string $key = '', string $label = '', bool $active = false, string $attrs = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $cls = 'q-btn' . ($active ? ' is-active' : '');
        $data = $key !== '' ? ' data-filter="' . $esc($key) . '"' : '';
        return '<button' . $data . ' class="' . $esc($cls) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . $label . '</button>';
    }
}
