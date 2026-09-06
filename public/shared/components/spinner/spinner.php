<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('spinner_html')) {
    function spinner_html(string $className = '', int $size = 24): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $cls = trim('animate-spin' . ($className !== '' ? ' ' . $className : ''));
        return '<i
    data-lucide="loader-circle"
    class="' . $esc($cls) . '"
    style="width:' . $size . 'px;height:' . $size . 'px"
  ></i>';
    }
}
