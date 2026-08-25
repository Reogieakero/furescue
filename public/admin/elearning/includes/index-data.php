<?php

declare(strict_types=1);

use App\Database;

require __DIR__ . '/../../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 4))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../../../includes/guard.php';

require __DIR__ . '/../../includes/ui-helpers.php';

$pdo = Database::connect();

const ELEARN_PAGE_SIZE = 10;

const ELEARN_CATEGORIES = [
    ['key' => 'dog_behavior', 'label' => 'Dog Behavior'],
    ['key' => 'cat_behavior', 'label' => 'Cat Behavior'],
    ['key' => 'basic_training', 'label' => 'Basic Training'],
    ['key' => 'general_care', 'label' => 'General Care'],
];

function elearn_category_label(?string $key): string
{
    foreach (ELEARN_CATEGORIES as $c) {
        if ($c['key'] === $key) {
            return $c['label'];
        }
    }
    return title_case($key);
}

function elearn_status_stamp(?string $status): string
{
    return $status === 'published' ? 'stamp--accent' : 'stamp--muted';
}

function elearn_status_label(?string $status): string
{
    return $status === 'published' ? 'Published' : 'Draft';
}

function elearn_button(
    string $text,
    string $variant = 'default',
    string $size = 'sm',
    string $icon = '',
    string $attrs = '',
    string $type = 'button'
): string {
    $variantCls = match ($variant) {
        'outline' => BTN_VARIANT_OUTLINE,
        'destructive' => 'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90',
        default => BTN_VARIANT_DEFAULT,
    };
    $sizeCls = $size === 'sm' ? BTN_SIZE_SM : BTN_SIZE_DEFAULT;
    $cls = trim(BTN_BASE . ' ' . $variantCls . ' ' . $sizeCls);
    $inner = ($icon !== '' ? '<i data-lucide="' . e($icon) . '" class="icon"></i>' : '') . '<span>' . e($text) . '</span>';
    return '<button type="' . e($type) . '" class="' . e($cls) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . $inner . '</button>';
}

function elearn_row_actions(array $m): string
{
    $id = e((string) ($m['id'] ?? ''));
    $published = ($m['published_status'] ?? '') === 'published';
    $toggle = $published
        ? elearn_button('Unpublish', 'outline', 'sm', 'eye-off', 'data-action="unpublish" data-id="' . $id . '"')
        : elearn_button('Publish', 'default', 'sm', 'upload', 'data-action="publish" data-id="' . $id . '"');
    return '
        <span class="table-actions">
          ' . elearn_button('Edit', 'outline', 'sm', 'pencil', 'data-action="edit" data-id="' . $id . '"') . '
          ' . $toggle . '
        </span>';
}

$stmt = $pdo->prepare(
    'SELECT id, title, category, published_status, created_at, created_by
     FROM elearning_modules
     ORDER BY created_at DESC
     LIMIT 100 OFFSET 0'
);
$stmt->execute();
$modules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$elearnPublished = count(array_filter($modules, static fn(array $m) => ($m['published_status'] ?? '') === 'published'));
$elearnDrafts = count(array_filter($modules, static fn(array $m) => ($m['published_status'] ?? '') === 'draft'));
$elearnCategoryKeys = [];
foreach ($modules as $m) {
    if (!empty($m['category'])) {
        $elearnCategoryKeys[$m['category']] = true;
    }
}
$elearnCounts = [
    'total' => count($modules),
    'published' => $elearnPublished,
    'drafts' => $elearnDrafts,
    'categories' => count($elearnCategoryKeys),
];
$elearnCatCounts = ['all' => count($modules)];
foreach (ELEARN_CATEGORIES as $c) {
    $elearnCatCounts[$c['key']] = count(array_filter($modules, static fn(array $m) => ($m['category'] ?? '') === $c['key']));
};
