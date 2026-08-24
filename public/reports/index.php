<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth\JwtService;
use App\Database;
use App\Repositories\ReportRepository;

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

$requiredRole = ['resident', 'rescuer', 'admin'];
require __DIR__ . '/../includes/guard.php';

$uid = (string) $_SESSION['user']['id'];
$pdo = Database::connect();
$userData = (new \App\Repositories\UserRepository($pdo))->find($uid);
$userData = $userData ? $userData->toArray() : [];

$reportRepo = new ReportRepository($pdo);
$listError = null;
try {
    $result = $reportRepo->paginate(1, 50, ['resident_id' => $uid]);
    $reports = array_map(static fn($r) => $r->toArray(), $result['items']);
} catch (Throwable $e) {
    $reports = [];
    $listError = 'Could not load your reports. Please try again.';
}

$timeAgo = static function (?string $value): string {
    if (!$value) {
        return '';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return $m === 1 ? '1 min ago' : "{$m} mins ago";
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return $h === 1 ? '1 hr ago' : "{$h} hrs ago";
    }
    $d = (int) floor($diff / 86400);
    if ($d === 1) {
        return 'Yesterday';
    }
    if ($d < 7) {
        return "{$d} days ago";
    }
    return date('M j, Y', $ts);
};

$pillBase = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wide';
$statusPillCls = [
    'pending_verification' => 'bg-teal/10 text-teal',
    'verified' => 'bg-jungle/10 text-jungle',
    'dismissed' => 'bg-muted text-muted-foreground',
];
$statusLabel = [
    'pending_verification' => 'Pending verification',
    'verified' => 'Verified',
    'dismissed' => 'Dismissed',
];

$esc = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$btnPrimary = 'inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 text-sm font-bold text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background';
$btnOutline = 'inline-flex h-8 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-input bg-background px-3 text-[13px] font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50';

$photoUrlsOf = static function (array $r): array {
    $raw = $r['photo_urls'] ?? null;
    if (is_array($raw)) {
        return array_values(array_filter($raw, static fn($u) => is_string($u) && trim($u) !== ''));
    }
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    return array_values(array_filter($decoded, static fn($u) => is_string($u) && trim($u) !== ''));
};

$renderPhotos = static function (array $urls) use ($esc): string {
    if (!$urls) {
        return '';
    }
    $thumbs = '';
    foreach (array_slice($urls, 0, 4) as $url) {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4', 'webm'], true)) {
            $thumbs .= '<span class="flex h-16 w-16 items-center justify-center rounded-lg border border-border bg-secondary text-muted-foreground"><i data-lucide="film" class="h-5 w-5"></i></span>';
        } else {
            $thumbs .= '<img src="' . $esc($url) . '" alt="Report photo" loading="lazy" class="h-16 w-16 rounded-lg border border-border object-cover">';
        }
    }
    return '<div class="mt-3 flex flex-wrap gap-2">' . $thumbs . '</div>';
};

$renderCard = static function (array $r) use ($esc, $timeAgo, $pillBase, $statusPillCls, $statusLabel, $photoUrlsOf, $renderPhotos): string {
    $status = (string) ($r['status'] ?? '');
    $pill = '<span class="' . $pillBase . ' ' . $esc($statusPillCls[$status] ?? 'bg-muted text-muted-foreground') . '">'
        . $esc($statusLabel[$status] ?? ucwords(str_replace('_', ' ', $status))) . '</span>';
    if (($r['validation_status'] ?? null) === 'flagged_duplicate' && $status !== 'dismissed') {
        $pill .= ' <span class="' . $pillBase . ' bg-destructive/10 text-destructive">Flagged duplicate</span>';
    }
    $address = trim((string) ($r['address_text'] ?? ''));
    $addressRow = $address !== ''
        ? '<div class="mt-3 flex items-start gap-1.5 text-xs text-muted-foreground"><i data-lucide="map-pin" class="mt-0.5 h-3.5 w-3.5 shrink-0"></i><span class="min-w-0">' . $esc($address) . '</span></div>'
        : '';
    $when = $timeAgo($r['created_at'] ?? null);

    return '
    <article class="flex flex-col rounded-xl border bg-card p-4 text-card-foreground shadow-sm sm:p-5" data-report-id="' . $esc($r['id'] ?? '') . '">
      <div class="flex flex-wrap items-center gap-2">
        ' . $pill . '
        ' . ($when !== '' ? '<time class="ml-auto text-xs text-muted-foreground" datetime="' . $esc($r['created_at'] ?? '') . '">' . $esc($when) . '</time>' : '') . '
      </div>
      <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">' . $esc($r['animal_description'] ?? '') . '</p>
      ' . $addressRow . '
      ' . $renderPhotos($photoUrlsOf($r)) . '
    </article>';
};

$cardsHtml = '';
foreach ($reports as $r) {
    $cardsHtml .= $renderCard($r);
}

$countText = count($reports) === 1 ? '1 report submitted' : count($reports) . ' reports submitted';

$emptyState = '
    <div id="reports-empty" class="md:col-span-2 rounded-xl border bg-card px-6 py-12 text-center">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-secondary">
        <i data-lucide="inbox" class="h-6 w-6 text-muted-foreground"></i>
      </div>
      <h2 class="mt-3 text-base font-extrabold">You haven\'t submitted any reports yet.</h2>
      <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">Spotted a stray animal in need? Pin its location and our rescue team will take it from there.</p>
      <a href="/report/" class="' . $btnPrimary . ' mt-4">Report an animal</a>
    </div>';

$errorState = '
    <div id="reports-error" class="md:col-span-2 rounded-xl border bg-card px-6 py-12 text-center">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-secondary">
        <i data-lucide="triangle-alert" class="h-6 w-6 text-destructive"></i>
      </div>
      <h2 class="mt-3 text-base font-extrabold">Could not load your reports.</h2>
      <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">' . $esc($listError ?? 'Please try again.') . '</p>
      <button type="button" id="reports-retry" class="' . $btnOutline . ' mt-4">
        <i data-lucide="refresh-cw" class="h-4 w-4"></i><span>Try again</span>
      </button>
    </div>';

$content = '
  <div class="mx-auto w-full max-w-4xl">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wider text-secondary-foreground">Community</span>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">My Reports</h1>
        <p id="reports-count" class="mt-1 text-sm text-muted-foreground">' . $esc($countText) . '</p>
      </div>
      <button type="button" id="refresh-reports" class="' . $btnOutline . '">
        <i data-lucide="refresh-cw" class="h-4 w-4"></i><span>Refresh</span>
      </button>
    </div>

    <div id="reports-list" class="grid grid-cols-1 gap-4 md:grid-cols-2">' .
        ($listError ? $errorState : ($cardsHtml !== '' ? $cardsHtml : $emptyState)) . '
    </div>
  </div>';

$residentUser = [
    'id' => $uid,
    'full_name' => (string) ($userData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => (string) ($userData['profile_photo_url'] ?? ''),
];
$activeNav = 'my reports';
$residentShellTitle = 'My Reports';
$jwt = new JwtService();
$pageState = [
    'accessToken' => $jwt->issueAccessToken(['id' => $uid, 'role' => $residentUser['role']]),
    'user' => $residentUser,
];
$pageModules = ['/reports/js/reports.js'];

$pageTitle = 'FurEscue — My Reports';
$pageDescription = 'Track the stray animal reports you submitted to FurEscue in Mati City.';

require __DIR__ . '/../includes/resident-shell.php';

