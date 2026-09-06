<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('toolbar_html')) {
    function toolbar_html(string $inner = '', string $className = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $cls = trim('report-toolbar' . ($className !== '' ? ' ' . $className : ''));
        return '<div class="' . $esc($cls) . '">' . $inner . '</div>';
    }
}
