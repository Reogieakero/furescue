<?php

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

if (!function_exists('table_head')) {
    function table_head(array $cols): string
    {
        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $th = '';
        foreach ($cols as $c) {
            $th .= '<th>' . $esc((string) $c) . '</th>';
        }
        return '
  <thead>
    <tr class="table-head">
      ' . $th . '
    </tr>
  </thead>';
    }
}
