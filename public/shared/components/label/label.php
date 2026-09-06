<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('label_html')) {
    function label_html(string $htmlFor = '', string $children = '', string $className = '', bool $required = false): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $cls = trim('label' . ($className !== '' ? ' ' . $className : ''));
        $mark = $required ? ' <span class="label-req" aria-hidden="true">*</span>' : '';
        $inner = $required ? '<span class="label-text">' . $children . $mark . '</span>' : $children;
        return '<label for="' . $esc($htmlFor) . '" class="' . $esc($cls) . '">' . $inner . '</label>';
    }
}
