<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('input_control')) {
    function input_control(string $id = '', string $name = '', string $type = 'text', string $placeholder = '', string $value = '', string $className = '', string $attrs = ''): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $cls = trim('input' . ($className !== '' ? ' ' . $className : ''));
        return '<input
    id="' . $esc($id) . '"
    name="' . $esc($name) . '"
    type="' . $esc($type) . '"
    placeholder="' . $esc($placeholder) . '"
    value="' . $esc($value) . '"
    class="' . $esc($cls) . '"
    ' . $attrs . '
  />';
    }
}
