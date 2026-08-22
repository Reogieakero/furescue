<?php

declare(strict_types=1);

/**
 * Shared admin chrome (AppShell = Sidebar + Topbar + main slot).
 *
 * Contract — define BEFORE including:
 *   $adminUser     array  id/full_name/email/role/profile_photo_url
 *   $activeNav     string lowercase nav key ('' falls back to 'dashboard')
 *   $navBadges     array  assoc map badgeKey => value (may be empty)
 *   $adminChildren string pre-rendered HTML for <main class="admin-main">
 *
 * Badge merge mirrors Sidebar()'s `{ notifications, ...getNavBadges(), ...badges }`
 * minus localStorage: server-side that collapses to ['notifications' => 3] + $navBadges.
 * An explicit null value in $navBadges suppresses that item's static badge,
 * matching JS `override !== undefined ? override : item.badge`.
 */

$adminUser = $adminUser ?? [];
$activeNav = $activeNav ?? '';
$navBadges = $navBadges ?? [];
$adminChildren = $adminChildren ?? '';

$esc = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$adminNavGroups = [
    [
        'label' => 'Overview',
        'items' => [['icon' => 'layout-dashboard', 'label' => 'Dashboard', 'active' => true]],
    ],
    [
        'label' => 'Rescue Management',
        'items' => [
            ['icon' => 'map-pin', 'label' => 'Reports', 'badgeKey' => 'reports', 'badge' => '14', 'badgeCls' => 'stamp--accent'],
            ['icon' => 'clipboard-list', 'label' => 'Cases', 'badgeKey' => 'cases'],
            ['icon' => 'siren', 'label' => 'Rescuers', 'badgeKey' => 'rescuers'],
        ],
    ],
    [
        'label' => 'Animal Management',
        'items' => [
            ['icon' => 'paw-print', 'label' => 'Animals'],
            ['icon' => 'heart-pulse', 'label' => 'Health Records', 'badgeKey' => 'health', 'badge' => '6', 'badgeCls' => 'stamp--muted'],
        ],
    ],
    [
        'label' => 'Adoption',
        'items' => [
            ['icon' => 'home', 'label' => 'Listings'],
            ['icon' => 'file-check', 'label' => 'Applications', 'badgeKey' => 'applications', 'badge' => '9', 'badgeCls' => 'stamp--accent'],
        ],
    ],
    [
        'label' => 'Content',
        'items' => [['icon' => 'book-open', 'label' => 'E-Learning']],
    ],
    [
        'label' => 'Communication',
        'items' => [
            ['icon' => 'message-square', 'label' => 'Messages'],
            ['icon' => 'bell', 'label' => 'Notifications', 'badgeKey' => 'notifications', 'badge' => '3', 'badgeCls' => 'stamp--coral'],
        ],
    ],
];

$adminBadgeMap = ['notifications' => 3];
foreach ($navBadges as $k => $v) {
    $adminBadgeMap[$k] = $v;
}

$adminNavItems = static function (array $items) use ($esc, $adminBadgeMap, $activeNav): string {
    $out = '';
    foreach ($items as $item) {
        $hasOverride = isset($item['badgeKey']) && array_key_exists($item['badgeKey'], $adminBadgeMap);
        $value = $hasOverride ? $adminBadgeMap[$item['badgeKey']] : ($item['badge'] ?? null);
        $showBadge = $value !== null && $value !== '' && $value !== 0;
        $badge = $showBadge
            ? '<span class="stamp stamp--sm sidebar-badge ' . $esc($item['badgeCls'] ?? '') . '">' . $esc($value) . '</span>'
            : '';
        $isActive = ($activeNav !== '' ? $activeNav : 'dashboard') === strtolower((string) ($item['label'] ?? ''));
        $tone = $isActive ? ' sidebar-link--active' : '';
        $out .= '
    <a href="#" data-nav="' . $esc(strtolower((string) ($item['label'] ?? ''))) . '" class="sidebar-link' . $tone . '">
      <i data-lucide="' . $esc($item['icon'] ?? '') . '"></i> <span>' . $esc($item['label'] ?? '') . '</span>
      ' . $badge . '
    </a>';
    }
    return $out;
};

$adminGroupsHtml = '';
foreach ($adminNavGroups as $group) {
    $adminGroupsHtml .= '
    <div class="sidebar-group">
      <div class="sidebar-label">' . $esc($group['label']) . '</div>
      <div class="sidebar-links">' . $adminNavItems($group['items']) . '</div>
    </div>';
}

$adminMenuItemBase = 'relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground';
$adminMenuDanger = 'text-destructive hover:bg-destructive/10 hover:text-destructive';
$adminMenuLabel = static fn(string $text): string =>
    '<div class="px-2 py-1.5 text-xs font-semibold text-muted-foreground">' . $esc($text) . '</div>';
$adminMenuSeparator = '<div class="-mx-1 my-1 h-px bg-muted"></div>';
$adminMenuItem = static function (string $icon, string $label, bool $danger = false) use ($esc, $adminMenuItemBase, $adminMenuDanger): string {
    $cls = $danger ? $adminMenuItemBase . ' ' . $adminMenuDanger : $adminMenuItemBase;
    return '
  <a href="#" class="' . $cls . '">
    <i data-lucide="' . $esc($icon) . '" class="h-4 w-4"></i>
    <span>' . $esc($label) . '</span>
  </a>';
};

$adminAvatarSrc = trim((string) ($adminUser['profile_photo_url'] ?? '')) !== ''
    ? (string) $adminUser['profile_photo_url']
    : 'https://i.pravatar.cc/64?img=33';

$adminProfileMenu = '
  <div id="profile-menu" data-dropdown class="relative">
    <button type="button" data-dropdown-trigger class="topbar-user" aria-haspopup="menu" aria-expanded="false" aria-label="Admin menu">
      <img src="' . $esc($adminAvatarSrc) . '" alt="Admin avatar">
    </button>
    <div data-dropdown-content role="menu" class="absolute top-full z-50 mt-1 hidden min-w-56 overflow-hidden rounded-md border border-input bg-card p-1 text-card-foreground shadow-md right-0">'
        . $adminMenuLabel('Insights')
        . $adminMenuItem('bar-chart-3', 'Analytics')
        . $adminMenuItem('file-down', 'Reports & Exports')
        . $adminMenuSeparator
        . $adminMenuLabel('System')
        . $adminMenuItem('users', 'Users')
        . $adminMenuItem('settings', 'Settings')
        . $adminMenuSeparator
        . $adminMenuItem('log-out', 'Log Out', true) . '
    </div>
  </div>';
?>

<div class="admin-shell">
  <aside id="sidebar" class="sidebar">
    <div class="sidebar-head">
      <div class="sidebar-logo"><i data-lucide="paw-print"></i></div>
      <div>
        <div class="sidebar-brand">FurEscue</div>
        <div class="sidebar-tag">Admin Console</div>
      </div>
    </div>

    <nav class="sidebar-nav">
<?= $adminGroupsHtml ?>
    </nav>
  </aside>
  <div id="overlay" class="admin-overlay"></div>
  <div class="admin-body">
    <header class="topbar">
      <button id="menu-toggle" class="topbar-menu" aria-label="Open menu">
        <i data-lucide="menu"></i>
      </button>

      <div class="topbar-search">
        <i data-lucide="search"></i>
        <input type="text" placeholder="Search case #, name, barangay…">
      </div>

      <div class="topbar-actions">
        <span class="topbar-meta"><i data-lucide="calendar"></i> <span id="admin-date"></span> &middot; City of Mati</span>
        <button class="topbar-bell" aria-label="Notifications"><i data-lucide="bell"></i></button>
        <span class="topbar-divider"></span>
        <?= $adminProfileMenu ?>
      </div>
    </header>
    <main class="admin-main">
<?= $adminChildren ?>
    </main>
  </div>
</div>
