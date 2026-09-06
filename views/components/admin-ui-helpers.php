<?php

declare(strict_types=1);

if (!function_exists('shared_path')) {
    require_once dirname(__DIR__) . '/path.php';
}

require_once shared_path('button/button.php');
require_once shared_path('select/select.php');
require_once shared_path('pagination/pagination.php');
require_once shared_path('table/table.php');
require_once shared_path('empty-state/empty-state.php');
require_once shared_path('kpi-card/kpi-card.php');
require_once shared_path('toolbar/toolbar.php');
require_once shared_path('filter-tabs/filter-tabs.php');
require_once shared_path('input/input.php');
require_once shared_path('checkbox/checkbox.php');
require_once shared_path('label/label.php');
require_once shared_path('badge/badge.php');
require_once shared_path('spinner/spinner.php');
require_once shared_path('separator/separator.php');
require_once shared_path('search/search.php');
require_once shared_path('date-range-picker/date-range-picker.php');

function e(mixed $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function js_round(float|int $x): int
{
    return (int) floor((float) $x + 0.5);
}

function short_id(mixed $id): string
{
    if (!$id) {
        return '—';
    }
    $s = str_replace('-', '', (string) $id);
    return '#' . strtoupper(substr($s, 0, 4));
}

function time_ago(mixed $value): string
{
    if (!$value) {
        return '—';
    }
    $ts = strtotime((string) $value);
    if ($ts === false) {
        return '—';
    }
    $today = strtotime('today');
    $day = mktime(0, 0, 0, (int) date('n', $ts), (int) date('j', $ts), (int) date('Y', $ts));
    $diff = js_round(($today - $day) / 86400);
    if ($diff === 0) {
        return date('h:i A', $ts);
    }
    if ($diff === 1) {
        return 'Yesterday';
    }
    if ($diff < 7) {
        return "{$diff} days ago";
    }
    return date('M j', $ts);
}

function title_case(mixed $value): string
{
    $s = str_replace('_', ' ', (string) ($value ?? ''));
    $words = preg_split('/\s+/', trim($s)) ?: [];
    $out = [];
    foreach ($words as $w) {
        if ($w === '') {
            continue;
        }
        $out[] = mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($w, 1, null, 'UTF-8');
    }
    return implode(' ', $out);
}

function truncate_text(mixed $text, int $n = 22): string
{
    if (!$text) {
        return '—';
    }
    $t = (string) $text;
    return mb_strlen($t, 'UTF-8') > $n ? mb_substr($t, 0, $n - 1, 'UTF-8') . '…' : $t;
}

function initials_of(mixed $name): string
{
    if (!$name) {
        return '?';
    }
    $words = preg_split('/\s+/u', trim((string) $name)) ?: [];
    $words = array_values(array_filter($words, fn($w) => $w !== ''));
    $first = array_slice($words, 0, 2);
    $out = '';
    foreach ($first as $w) {
        $out .= mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return $out;
}

function avatar_img(mixed $src, mixed $name): string
{
    if ($src) {
        return '<img class="table-avatar" src="' . e($src) . '" alt="">';
    }
    return '<span class="table-avatar table-avatar--initial">' . e(initials_of($name)) . '</span>';
}

function rescuer_avatar(mixed $src, mixed $name): string
{
    if ($src) {
        return '<img class="rescuer-avatar" src="' . e($src) . '" alt="">';
    }
    return '<span class="rescuer-avatar rescuer-avatar--initial">' . e(initials_of($name)) . '</span>';
}

function chevron_right(): string
{
    return '<i data-lucide="chevron-right" class="link-chevron"></i>';
}
