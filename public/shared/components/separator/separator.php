<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('separator_html')) {
    function separator_html(string $label = '', string $className = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        if ($label !== '') {
            $cls = trim('separator separator--label' . ($className !== '' ? ' ' . $className : ''));
            return '<div class="' . $esc($cls) . '"><span>' . $esc($label) . '</span></div>';
        }
        $cls = trim('separator' . ($className !== '' ? ' ' . $className : ''));
        return '<div class="' . $esc($cls) . '" role="separator"></div>';
    }
}
