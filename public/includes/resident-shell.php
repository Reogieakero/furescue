<?php

declare(strict_types=1);

/**
 * Shared resident chrome (Sidebar + Topbar + main slot) for /report, /reports,
 * /learning, /messages, /notifications and future resident pages.
 *
 * Two inclusion modes:
 *
 * 1. Document mode (default) — used by pages that define page content in
 *    `$content` and simply `require` this shell at the end (see /report,
 *    /reports). The shell renders the full HTML document via site-head.php,
 *    emits `window.__PAGE_STATE__` from `$pageState`, and loads `$pageModules`.
 *
 * 2. Partial mode (`$residentShellMode = 'partial';` before including) —
 *    outputs ONLY the `.resident-shell` markup so the caller can capture it
 *    with ob_start() and compose the document itself (see /learning,
 *    /messages, /notifications which pre-render `$residentChildren`).
 *
 * Contract — define BEFORE including:
 *   $residentUser       array  id/full_name/email/role/profile_photo_url
 *   $activeNav          string lowercase nav key ('' falls back to 'home')
 *   $navBadges          array  assoc map badgeKey => value (may be empty)
 *   $content            string page HTML (document mode)
 *   $residentChildren   string page HTML (partial mode)
 *   $pageState          array  JSON payload for window.__PAGE_STATE__ (doc mode)
 *   $pageModules        array  absolute module URLs loaded at end of body
 *   $pageScripts        array  absolute classic script URLs (doc mode, e.g. Leaflet)
 *   $pageTitle / $pageDescription / $pageCss / $fontsHref  (doc mode)
 *   $residentShellTitle string topbar heading
 *
 * Styles live in public/css/input.css under the .resident-shell family;
 * client behaviors (hamburger, overlay, bell badge, profile dropdown) are
 * initialized by public/js/components/resident-shell.js.
 */

$residentUser = $residentUser ?? [];
$activeNav = $activeNav ?? '';
$navBadges = $navBadges ?? [];
$residentShellTitle = $residentShellTitle ?? 'FurEscue';
$navRole = strtolower((string) ($residentUser['role'] ?? ''));
$isRescuer = $navRole === 'rescuer';
$rsideTag = $isRescuer ? 'Rescue Portal' : 'Community Portal';

$esc = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$residentNavGroups = [
    [
        'label' => 'Overview',
        'items' => [
            ['icon' => 'house', 'label' => 'Home', 'href' => '/index.php', 'hideFor' => ['rescuer']],
            ['icon' => 'clipboard-list', 'label' => 'My Cases', 'href' => '/cases/', 'roles' => ['rescuer']],
        ],
    ],
    [
        'label' => 'Community',
        'residentOnly' => true,
        'items' => [
            ['icon' => 'map-pin-plus', 'label' => 'Report Animal', 'href' => '/report/'],
            ['icon' => 'clipboard-list', 'label' => 'My Reports', 'href' => '/reports/'],
        ],
    ],
    [
        'label' => 'Adoption',
        'residentOnly' => true,
        'items' => [
            ['icon' => 'heart', 'label' => 'Browse Animals', 'href' => '/animals/'],
            ['icon' => 'file-check', 'label' => 'My Adoptions', 'href' => '/adoptions/'],
        ],
    ],
    [
        'label' => 'Learn',
        'items' => [
            ['icon' => 'book-open', 'label' => 'Learning Hub', 'href' => '/learning/'],
        ],
    ],
    [
        'label' => 'Communication',
        'items' => [
            ['icon' => 'message-square', 'label' => 'Messages', 'href' => '/messages/', 'badgeKey' => 'messages'],
            ['icon' => 'bell', 'label' => 'Notifications', 'href' => '/notifications/', 'badgeKey' => 'notifications'],
        ],
    ],
];

$itemVisible = static function (array $item) use ($navRole): bool {
    $roles = $item['roles'] ?? null;
    if (is_array($roles)) {
        return in_array($navRole, $roles, true);
    }
    $hideFor = $item['hideFor'] ?? null;
    if (is_array($hideFor) && in_array($navRole, $hideFor, true)) {
        return false;
    }
    return true;
};

$visibleNavGroups = [];
foreach ($residentNavGroups as $group) {
    if (!empty($group['residentOnly']) && $isRescuer) {
        continue;
    }
    $items = array_values(array_filter($group['items'], $itemVisible));
    if ($items === []) {
        continue;
    }
    $group['items'] = $items;
    $visibleNavGroups[] = $group;
}
$residentNavGroups = $visibleNavGroups;

$residentNavItems = static function (array $items) use ($esc, $navBadges, $activeNav): string {
    $out = '';
    foreach ($items as $item) {
        $badgeKey = (string) ($item['badgeKey'] ?? '');
        $value = $badgeKey !== '' ? ($navBadges[$badgeKey] ?? null) : null;
        $showBadge = $value !== null && $value !== '' && $value !== 0;
        $badge = '<span class="rside-badge" data-nav-badge="' . $esc($badgeKey) . '"' . ($showBadge ? '' : ' hidden') . '>'
            . ($showBadge ? $esc((string) $value) : '')
            . '</span>';
        $isActive = ($activeNav !== '' ? $activeNav : 'home') === strtolower((string) ($item['label'] ?? ''));
        $tone = $isActive ? ' rside-link--active' : '';
        $out .= '
      <a href="' . $esc($item['href'] ?? '#') . '" class="rside-link' . $tone . '">
        <i data-lucide="' . $esc($item['icon'] ?? '') . '"></i> <span>' . $esc($item['label'] ?? '') . '</span>
        ' . $badge . '
      </a>';
    }
    return $out;
};

$residentGroupsHtml = '';
foreach ($residentNavGroups as $group) {
    $residentGroupsHtml .= '
    <div class="rside-group">
      <div class="rside-label">' . $esc($group['label']) . '</div>
      <div class="rside-links">' . $residentNavItems($group['items']) . '</div>
    </div>';
}

$residentAvatarSrc = trim((string) ($residentUser['profile_photo_url'] ?? '')) !== ''
    ? (string) $residentUser['profile_photo_url']
    : 'https://i.pravatar.cc/64?img=47';

$residentMenuBase = 'relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground';
$residentProfileName = trim((string) ($residentUser['full_name'] ?? '')) !== ''
    ? (string) $residentUser['full_name']
    : 'My Account';

$residentChrome = static function () use ($esc, $residentGroupsHtml, $residentShellTitle, $residentAvatarSrc, $residentMenuBase, $residentProfileName, $residentUser, $rsideTag): string {
    $profileMenu = '
      <div id="profile-menu" data-dropdown class="relative">
        <button type="button" data-dropdown-trigger class="rtop-user" aria-haspopup="menu" aria-expanded="false" aria-label="Account menu">
          <img src="' . $esc($residentAvatarSrc) . '" alt="Your avatar">
        </button>
        <div data-dropdown-content role="menu" class="absolute top-full z-50 mt-1 hidden min-w-56 overflow-hidden rounded-md border border-input bg-card p-1 text-card-foreground shadow-md right-0">
          <div class="px-2 py-1.5">
            <p class="text-sm font-semibold leading-none">' . $esc($residentProfileName) . '</p>
            <p class="text-xs text-muted-foreground mt-1">' . $esc((string) ($residentUser['email'] ?? '')) . '</p>
          </div>
          <div class="-mx-1 my-1 h-px bg-muted"></div>
          <a href="/notifications/" class="' . $residentMenuBase . '">
            <i data-lucide="bell" class="lucide h-4 w-4"></i><span>Notifications</span>
          </a>
          <a href="/auth/logout.php" data-action="logout" class="' . $residentMenuBase . ' text-destructive hover:bg-destructive/10 hover:text-destructive">
            <i data-lucide="log-out" class="lucide h-4 w-4"></i><span>Log Out</span>
          </a>
        </div>
      </div>';

    return '<div class="resident-shell">
  <aside id="rside" class="rside">
    <div class="rside-head">
      <div class="rside-logo"><i data-lucide="paw-print"></i></div>
      <div>
        <div class="rside-brand">FurEscue</div>
        <div class="rside-tag">' . $esc($rsideTag) . '</div>
      </div>
    </div>

    <nav class="rside-nav">' . $residentGroupsHtml . '
    </nav>
  </aside>
  <div id="roverlay" class="roverlay"></div>
  <div class="rmain-wrap">
    <header class="rtop">
      <button id="rmenu-toggle" class="rtop-menu" aria-label="Open menu" aria-expanded="false" aria-controls="rside">
        <i data-lucide="menu"></i>
      </button>

      <p class="rtop-title">' . $esc($residentShellTitle) . '</p>

      <div class="rtop-actions">
        <span class="rtop-meta"><i data-lucide="calendar"></i> <span data-resident-date></span> &middot; City of Mati</span>
        <a href="/notifications/" class="rtop-bell" aria-label="Notifications">
          <i data-lucide="bell"></i>
          <span class="rtop-bell-count" id="notif-badge" data-nav-badge="notifications" hidden></span>
        </a>
        <span class="rtop-divider"></span>' . $profileMenu . '
      </div>
    </header>
    <main class="resident-main">';
};

$residentChromeEnd = '</main>
  </div>
</div>';

if (($residentShellMode ?? 'document') === 'partial') {
    echo $residentChrome();
    echo $residentChildren ?? '';
    echo $residentChromeEnd;
    return;
}

// --- Document mode: render the full page ---------------------------------

$pageState = $pageState ?? [];
$pageModules = $pageModules ?? [];
$pageScripts = $pageScripts ?? [];

$residentChildren = $content ?? ($residentChildren ?? '');
ob_start();
echo $residentChrome();
echo $residentChildren;
echo $residentChromeEnd;
$residentPageHtml = (string) ob_get_clean();

$pageTitle = $pageTitle ?? 'FurEscue';
$pageDescription = $pageDescription ?? '';
$pageCss = $pageCss ?? [];
$fontsHref = $fontsHref
    ?? 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';

require __DIR__ . '/site-head.php';
?>
  <body>
    <div id="app"><?= $residentPageHtml ?></div>
    <script type="module" src="/js/components/resident-shell.js"></script>
<?php if ($pageState !== []): ?>
    <script>window.__PAGE_STATE__ = <?= json_encode($pageState, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<?php endif; ?>
<?php foreach ($pageScripts as $residentScriptSrc): ?>
    <script src="<?= $esc($residentScriptSrc) ?>"></script>
<?php endforeach; ?>
<?php foreach ($pageModules as $residentModuleSrc): ?>
    <script type="module" src="<?= $esc($residentModuleSrc) ?>"></script>
<?php endforeach; ?>
  </body>
</html>
