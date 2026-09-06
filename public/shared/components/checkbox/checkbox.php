<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('checkbox_control')) {
    function checkbox_control(string $id = '', string $name = '', bool $checked = false, string $className = '', string $attrs = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $cls = trim('checkbox' . ($className !== '' ? ' ' . $className : ''));
        return '<input
    type="checkbox"
    id="' . $esc($id) . '"
    name="' . $esc($name) . '"
    ' . ($checked ? 'checked' : '') . '
    class="' . $esc($cls) . '"
    ' . $attrs . '
  />';
    }
}
