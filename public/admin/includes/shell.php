<?php

declare(strict_types=1);

function admin_nav_groups(): array
{
    return [
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
}

function admin_nav_item(array $item, array $map, string $activeNav): string
{
    $override = isset($item['badgeKey']) && array_key_exists($item['badgeKey'], $map) ? $map[$item['badgeKey']] : null;
    $value = $override !== null ? $override : ($item['badge'] ?? null);
    $badge = $value ? '<span class="stamp stamp--sm sidebar-badge ' . e($item['badgeCls'] ?? '') . '">' . e($value) . '</span>' : '';
    $isActive = ($activeNav !== '' ? $activeNav : 'dashboard') === strtolower((string) ($item['label'] ?? ''));
    $tone = $isActive ? ' sidebar-link--active' : '';
    return '
    <a href="#" data-nav="' . e(strtolower((string) ($item['label'] ?? ''))) . '" class="sidebar-link' . $tone . '">
      <i data-lucide="' . e($item['icon'] ?? '') . '"></i> <span>' . e($item['label'] ?? '') . '</span>
      ' . $badge . '
    </a>';
}

function admin_sidebar(array $badges = [], int $notifications = 3, string $activeNav = ''): string
{
    $map = ['notifications' => $notifications];
    foreach ($badges as $k => $v) {
        if ($v !== null) {
            $map[$k] = $v;
        }
    }
    $groups = '';
    foreach (admin_nav_groups() as $g) {
        $items = '';
        foreach ($g['items'] as $i) {
            $items .= admin_nav_item($i, $map, $activeNav);
        }
        $groups .= '
    <div class="sidebar-group">
      <div class="sidebar-label">' . e($g['label']) . '</div>
      <div class="sidebar-links">' . $items . '</div>
    </div>';
    }

    return '
  <aside id="sidebar" class="sidebar">
    <div class="sidebar-head">
      <div class="sidebar-logo"><i data-lucide="paw-print"></i></div>
      <div>
        <div class="sidebar-brand">FurEscue</div>
        <div class="sidebar-tag">Admin Console</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      ' . $groups . '
    </nav>
  </aside>';
}

function admin_topbar(): string
{
    $itemBase = 'relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground';
    $dangerCls = ' text-destructive hover:bg-destructive/10 hover:text-destructive';
    $menuItem = fn(string $icon, string $label, bool $danger = false) =>
        '
  <a href="#" class="' . e($itemBase . ($danger ? $dangerCls : '')) . '">
    <i data-lucide="' . e($icon) . '" class="h-4 w-4"></i>
    <span>' . e($label) . '</span>
  </a>';
    $menuLabel = fn(string $text) => '<div class="px-2 py-1.5 text-xs font-semibold text-muted-foreground">' . e($text) . '</div>';
    $separator = '<div class="-mx-1 my-1 h-px bg-muted"></div>';

    $rows =
        $menuLabel('Insights') .
        $menuItem('bar-chart-3', 'Analytics') .
        $menuItem('file-down', 'Reports & Exports') .
        $separator .
        $menuLabel('System') .
        $menuItem('users', 'Users') .
        $menuItem('settings', 'Settings') .
        $separator .
        $menuItem('log-out', 'Log Out', true);

    $profileMenu = '
  <div id="profile-menu" data-dropdown class="relative">
    <button type="button" data-dropdown-trigger class="topbar-user" aria-haspopup="menu" aria-expanded="false" aria-label="Admin menu">
      <img src="https://i.pravatar.cc/64?img=33" alt="Admin avatar">
    </button>
    <div data-dropdown-content role="menu" class="absolute top-full z-50 mt-1 hidden min-w-56 overflow-hidden rounded-md border border-input bg-card p-1 text-card-foreground shadow-md right-0">' . $rows . '
    </div>
  </div>';

    return '
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
      ' . $profileMenu . '
    </div>
  </header>';
}

function admin_app_shell(string $children, array $badges = [], int $notifications = 3, string $activeNav = ''): string
{
    return '
  <div class="admin-shell">
    ' . admin_sidebar($badges, $notifications, $activeNav) . '
    <div id="overlay" class="admin-overlay"></div>
    <div class="admin-body">
      ' . admin_topbar() . '
      <main class="admin-main">
        ' . $children . '
      </main>
    </div>
  </div>';
}
