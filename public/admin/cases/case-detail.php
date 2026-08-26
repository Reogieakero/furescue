<?php

declare(strict_types=1);

use App\Database;
use App\Repositories\CaseRepository;
use App\Repositories\ReportRepository;
use App\Repositories\Repository;
use App\Repositories\UserRepository;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require __DIR__ . '/../../includes/guard.php';

require __DIR__ . '/../includes/ui-helpers.php';

// Missing/malformed id or unknown case -> back to the cases list. The legacy
// JS alerted "No case specified." / rendered a not-found panel; a redirect is
// the server-side equivalent.
$caseIdParam = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
if ($caseIdParam === '' || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $caseIdParam) !== 1) {
    header('Location: /admin/cases/');
    exit;
}

$pdo = Database::connect();
$uid = (string) $_SESSION['user']['id'];

$caseEntity = (new CaseRepository($pdo))->find($caseIdParam);
if ($caseEntity === null) {
    header('Location: /admin/cases/');
    exit;
}
$caseRow = $caseEntity->toArray();

// Activity log — mirrors CaseController::activity() serialization.
$activityRows = (new Repository(
    $pdo,
    'case_activity_log',
    ['id', 'case_id', 'actor_id', 'actor_role', 'action', 'notes', 'created_at']
))->all(['case_id' => $caseIdParam], 'created_at', 'ASC');

$report = null;
if (!empty($caseRow['report_id'])) {
    $reportEntity = (new ReportRepository($pdo))->find((string) $caseRow['report_id']);
    if ($reportEntity !== null) {
        $report = $reportEntity->toArray();
    }
}

$rescuer = null;
if (!empty($caseRow['assigned_rescuer_id'])) {
    $rescuerEntity = (new UserRepository($pdo))->find((string) $caseRow['assigned_rescuer_id']);
    if ($rescuerEntity !== null) {
        $rescuer = $rescuerEntity->toArray();
    }
}

$rescuerName = (string) ($rescuer['full_name'] ?? '');
$rescuerPhoto = (string) ($rescuer['profile_photo_url'] ?? '');

// Port of pages/case-detail/components/util.js photos().
$photosOf = static function (mixed $value): array {
    if ($value === null || $value === '') {
        return [];
    }
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }
        $value = $decoded;
    }
    if (!is_array($value)) {
        return [];
    }
    $out = [];
    foreach ($value as $item) {
        if (is_string($item)) {
            $out[] = $item;
        } elseif (is_array($item)) {
            $url = $item['url'] ?? '';
            $out[] = is_string($url) ? $url : '';
        } else {
            $out[] = '';
        }
    }
    return array_values(array_filter($out, static fn($u) => is_string($u) && $u !== ''));
};

$attachments = $photosOf(is_array($report) ? ($report['photo_urls'] ?? null) : null);
$proofUrls = $photosOf($caseRow['resolution_photos'] ?? null);

// Port of workflow.js formatEventTime() — en-US equivalents of
// toLocaleDateString/toLocaleTimeString with the same options.
$eventTimeParts = static function (mixed $iso): array {
    if (!is_string($iso) || $iso === '') {
        return ['date' => '', 'time' => ''];
    }
    $ts = strtotime($iso);
    if ($ts === false) {
        return ['date' => '', 'time' => ''];
    }
    return [
        'date' => date('M j, Y', $ts),
        'time' => date('g:i A', $ts),
    ];
};

// Final tailwind-merge-resolved classes of ui/badge.js.
$badgeBaseCls = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2';
$badgeHtml = static function (string $text, string $variant = 'default', string $icon = '') use ($badgeBaseCls): string {
    $variantCls = $variant === 'secondary'
        ? ' border-transparent bg-secondary text-secondary-foreground'
        : ($variant === 'outline' ? ' text-foreground' : ' border-transparent bg-primary text-primary-foreground');
    $iconHtml = $icon !== '' ? '<i data-lucide="' . e($icon) . '" class="badge-icon"></i>' : '';
    return '<span class="' . e($badgeBaseCls . $variantCls) . '">' . $iconHtml . e($text) . '</span>';
};

$statusRaw = (string) ($caseRow['status'] ?? '');
$isResolved = $statusRaw === 'resolved';
$showResolve = $statusRaw === 'in_progress';
$canResolve = $showResolve && count($proofUrls) >= 1;
$stampCls = ($statusRaw === 'in_progress' || $statusRaw === 'resolved') ? 'stamp--accent' : 'stamp--coral';

// renderActions(): Button ignores unknown props, so the disabled "Resolved"
// button renders without a disabled attribute and without data-cd-action.
$locationBtn = button_html('See location', 'outline', 'sm', '', 'map-pin', 'data-cd-action="location"');
if ($isResolved) {
    $actionsHtml = '<div class="cd-actions">' . $locationBtn
        . button_html('Resolved', 'outline', 'sm', '', 'check-circle-2') . '</div>';
} else {
    $assignLabel = !empty($caseRow['assigned_rescuer_id']) ? 'Reassign' : 'Assign rescuer';
    $actionsHtml = '
      <div class="cd-actions">
        ' . $locationBtn . '
        ' . button_html($assignLabel, 'outline', 'sm', '', 'user-plus', 'data-cd-action="assign"') . '
        ' . ($showResolve ? button_html(
            'Resolve',
            'default',
            'sm',
            '',
            'check-circle-2',
            $canResolve
                ? 'data-cd-action="resolve"'
                : 'disabled aria-disabled="true" title="Rescue proof required before resolve"'
        ) : '') . '
      </div>';
}

$pageHeadHtml = '
  <div class="page-head">
    <div>
      <a href="/admin/cases/" class="cd-back"><i data-lucide="chevron-left"></i> Back to cases</a>
    </div>
    ' . $actionsHtml . '
  </div>';

// buildActivity() port — "Case opened" base event + activity rows.
$events = [];
$openTime = $eventTimeParts($caseRow['created_at'] ?? null);
$events[] = [
    'type' => 'open',
    'title' => 'Case opened',
    'note' => 'Case created from a verified report.',
    'actor' => '',
    'date' => $openTime['date'],
    'time' => $openTime['time'],
];

$workflowLabels = [
    'assigned' => 'Rescuer assigned',
    'status_change' => 'Status updated',
    'accepted' => 'Rescue accepted',
    'declined' => 'Rescue declined',
    'proof_added' => 'Rescue proof added',
];
$statusNotes = [
    'in_progress' => 'Rescuer accepted and started the rescue',
    'resolved' => 'Admin marked the case resolved',
    'assigned' => 'Rescuer re-assigned to the case',
];
$assignedCount = 0;
foreach ($activityRows as $ev) {
    $type = (string) (($ev['action'] ?? '') !== '' ? $ev['action'] : ($ev['type'] ?? ''));
    $rowTitle = $workflowLabels[$type] ?? title_case($type !== '' ? $type : 'event');
    $rowActor = '';
    $rowNote = '';
    if ($type === 'assigned') {
        $assignedCount++;
        // First assignment reads "assigned"; any later one is a re-assignment.
        $isReassign = $assignedCount > 1;
        $rowTitle = $isReassign ? 'Rescuer reassigned' : 'Rescuer assigned';
        $rowActor = $badgeHtml($rescuerName !== '' ? $rescuerName : 'Rescuer', 'secondary', 'user');
        // Still "assigned" means the rescuer has not accepted yet.
        $rowNote = $statusRaw === 'assigned'
            ? 'Waiting for rescuer to accept'
            : ($isReassign ? 'Rescuer reassigned to the case' : 'Rescuer assigned to the case');
    } elseif ($type === 'status_change') {
        $notesStr = (string) (($ev['notes'] ?? '') !== '' ? $ev['notes'] : ($ev['note'] ?? ''));
        if (preg_match('/^Status set to (.+)$/sD', $notesStr, $m) === 1) {
            $st = (string) $m[1];
            $rowNote = $statusNotes[$st] ?? ('Status changed to ' . title_case($st));
        }
        $role = strtolower((string) (($ev['actor_role'] ?? '') !== '' ? $ev['actor_role'] : 'admin'));
        $byRescuer = $role === 'rescuer';
        $rowActor = $byRescuer
            ? $badgeHtml($rescuerName !== '' ? $rescuerName : 'Rescuer', 'secondary', 'user')
            : $badgeHtml(title_case((string) (($ev['actor_role'] ?? '') !== '' ? $ev['actor_role'] : 'Admin')), 'outline', 'shield');
    } elseif (in_array($type, ['accepted', 'declined', 'proof_added'], true)) {
        $rowActor = $badgeHtml($rescuerName !== '' ? $rescuerName : 'Rescuer', 'secondary', 'user');
        $rowNote = match ($type) {
            'accepted' => 'Rescuer accepted the assignment',
            'declined' => 'Rescuer declined the assignment',
            'proof_added' => 'Rescuer uploaded rescue proof',
            default => '',
        };
    } else {
        $rowNote = (string) (($ev['notes'] ?? '') !== '' ? $ev['notes'] : ($ev['note'] ?? ''));
    }
    $t = $eventTimeParts($ev['created_at'] ?? null);
    $events[] = [
        'type' => $type,
        'title' => $rowTitle,
        'note' => $rowNote,
        'actor' => $rowActor,
        'date' => $t['date'],
        'time' => $t['time'],
    ];
}

$timelineItems = '';
foreach ($events as $i => $evItem) {
    $noteHtml = $evItem['note'] !== '' && $evItem['note'] !== null
        ? '<div class="cd-tl-notes">' . e((string) $evItem['note']) . '</div>' : '';
    $actorHtml = $evItem['actor'] !== ''
        ? '<span class="cd-tl-actor">' . $evItem['actor'] . '</span>' : '';
    $dateHtml = $evItem['date'] !== ''
        ? '<span class="cd-tl-date">' . e($evItem['date']) . '</span>' : '';
    $clockHtml = $evItem['time'] !== ''
        ? '<span class="cd-tl-clock">' . e($evItem['time']) . '</span>' : '';
    $timelineItems .= '
    <li class="cd-tl-item cd-tl--' . e((string) $evItem['type']) . '">
      <span class="cd-tl-dot">' . e((string) ($i + 1)) . '</span>
      <div class="cd-tl-body">
        <div class="cd-tl-title">' . e((string) $evItem['title']) . '</div>
        ' . $noteHtml . '
        <div class="cd-tl-meta">
          ' . $actorHtml . '
          <span class="cd-tl-time">
            ' . $dateHtml . '
            ' . $clockHtml . '
          </span>
        </div>
      </div>
    </li>';
}

$workflowPanelHtml = '
  <div class="panel case-detail-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="git-branch"></i>
        <h2 class="panel-title">Workflow &amp; transactions</h2>
      </div>
      <span class="stamp stamp--sm stamp--muted">' . e((string) count($events)) . ' events</span>
    </div>
    <div class="panel-body"><ul class="cd-timeline">' . $timelineItems . '</ul></div>
  </div>';

$infoRow = static function (string $label, string $value): string {
    return '
        <div class="dialog-info-row">
          <span class="dialog-info-label">' . e($label) . '</span>
          <span class="dialog-info-value">' . $value . '</span>
        </div>';
};

$rescuerCell = !empty($caseRow['assigned_rescuer_id'])
    ? $badgeHtml($rescuerName !== '' ? $rescuerName : 'Unassigned', 'secondary', 'user')
    : $badgeHtml('Unassigned', 'outline');

$infoPanelHtml = '
  <div class="panel case-detail-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="clipboard-list"></i>
        <h2 class="panel-title">Case details</h2>
      </div>
    </div>
    <div class="panel-body"><div class="dialog-info">'
    . $infoRow('Case', e(short_id($caseRow['id'] ?? null)))
    . $infoRow('Status', '<span class="stamp stamp--sm ' . e($stampCls) . '">' . e(title_case($statusRaw)) . '</span>')
    . $infoRow('Rescuer', $rescuerCell)
    . $infoRow('Source report', !empty($caseRow['report_id']) ? e(short_id($caseRow['report_id'])) : '—')
    . $infoRow('Created', !empty($caseRow['created_at']) ? e(time_ago($caseRow['created_at'])) : '—')
    . $infoRow('Updated', !empty($caseRow['updated_at']) ? e(time_ago($caseRow['updated_at'])) : '—')
    . '</div></div>
  </div>';

if ($report === null) {
    $sourcePanelHtml = '
  <div class="panel case-detail-panel">
    <div class="panel-head"><div class="panel-title-wrap"><i data-lucide="file-text"></i><h2 class="panel-title">Source report</h2></div></div>
    <div class="panel-body"><div class="empty-state"><i data-lucide="file-x"></i><span>No linked report.</span></div></div>
  </div>';
} else {
    $animalText = ($report['animal_description'] ?? '') ?: '—';
    $locValue = ($report['address_text'] ?? '') ?: '';
    $sourcePanelHtml = '
  <div class="panel case-detail-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="file-text"></i>
        <h2 class="panel-title">Source report &middot; ' . e(short_id($report['id'] ?? null)) . '</h2>
      </div>
    </div>
    <div class="panel-body">
      <div class="dialog-info">'
        . $infoRow('Animal', e((string) $animalText))
        . $infoRow('Location', $locValue !== '' ? e($locValue) : '—')
        . $infoRow('Validation', e((string) (($report['validation_status'] ?? '') ?: '—')))
        . $infoRow('Report status', e((string) (($report['status'] ?? '') ?: '—')))
      . '</div>
    </div>
  </div>';
}

$attachmentCount = count($attachments);
$filesGallery = $attachmentCount > 0
    ? '<div class="cd-files">' . implode('', array_map(
        static fn(string $f): string => '<a class="cd-file" href="' . e($f) . '" target="_blank" rel="noopener"><img src="' . e($f) . '" alt="Case attachment" loading="lazy"></a>',
        $attachments
    )) . '</div>'
    : '<div class="empty-state"><i data-lucide="image-off"></i><span>No attachments submitted.</span></div>';

$attachmentsPanelHtml = '
  <div class="panel case-detail-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="paperclip"></i>
        <h2 class="panel-title">Attached files</h2>
      </div>
      ' . ($attachmentCount > 0 ? '<span class="stamp stamp--sm stamp--muted">' . e((string) $attachmentCount) . '</span>' : '') . '
    </div>
    <div class="panel-body">' . $filesGallery . '</div>
  </div>';

$proofCount = count($proofUrls);
$proofGallery = $proofCount > 0
    ? '<div class="cd-files">' . implode('', array_map(
        static fn(string $p): string => '<a class="cd-file" href="' . e($p) . '" target="_blank" rel="noopener"><img src="' . e($p) . '" alt="Rescue proof" loading="lazy"></a>',
        $proofUrls
    )) . '</div>'
    : '<div class="empty-state"><i data-lucide="image-off"></i><span>No rescue proof uploaded.</span></div>';
$proofMeta = $proofCount > 0
    ? '<div class="cd-rescuer-meta">' . $badgeHtml($rescuerName !== '' ? $rescuerName : 'Rescuer', 'secondary', 'user') . '</div>'
    : '';

$proofPanelHtml = '
  <div class="panel case-detail-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="camera"></i>
        <h2 class="panel-title">Rescue proof</h2>
      </div>
      ' . $proofMeta . '
    </div>
    <div class="panel-body">
      ' . $proofGallery . '
    </div>
  </div>';

$children = $pageHeadHtml
    . '<div class="case-detail-grid">'
    . '<div class="cd-col-workflow">' . $workflowPanelHtml . '</div>'
    . '<div class="cd-col-info">' . $infoPanelHtml . $sourcePanelHtml . '</div>'
    . '<div class="cd-col-files">' . $attachmentsPanelHtml . '</div>'
    . '<div class="cd-col-rescuer">' . $proofPanelHtml . '</div>'
    . '</div>';

$authState = include __DIR__ . '/../../includes/admin-page-auth-state.php';

// Mirrors loadCaseDetail()'s state shape after a successful fetch.
$uid = (string) $_SESSION['user']['id'];
$role = (string) ($_SESSION['user']['role'] ?? '');
$state = [
    'accessToken' => (new \App\Auth\JwtService())->issueAccessToken(['id' => $uid, 'role' => $role]),
    'user' => [
        'id' => $uid,
        'full_name' => (string) ($_SESSION['user']['full_name'] ?? ''),
        'email' => (string) ($_SESSION['user']['email'] ?? ''),
        'role' => $role,
    ],
    'caseId' => $caseIdParam,
    'caseData' => $caseRow + [
        'report' => $report,
        'rescuer' => $rescuer,
        'rescuer_name' => $rescuerName,
        'rescuer_photo' => $rescuerPhoto,
    ],
    'report' => $report,
    'rescuer' => $rescuer,
    'activity' => $activityRows,
    'attachments' => $attachments,
    'proof' => $proofUrls,
    'error' => null,
];

$currentUser = (new UserRepository($pdo))->find($uid);
$currentUserData = $currentUser ? $currentUser->toArray() : [];
$adminUser = [
    'id' => $uid,
    'full_name' => (string) ($currentUserData['full_name'] ?? ($_SESSION['user']['full_name'] ?? '')),
    'email' => (string) ($_SESSION['user']['email'] ?? ''),
    'role' => (string) ($_SESSION['user']['role'] ?? ''),
    'profile_photo_url' => '',
];
$activeNav = 'cases';
$navBadges = ['notifications' => 0, 'cases' => 0];
$adminChildren = $children;

ob_start();
require __DIR__ . '/../../includes/admin-shell.php';
$pageHtml = (string) ob_get_clean();

$pageTitle = 'FurEscue — Case Detail';
$pageDescription = 'FurEscue admin case detail — transactions, workflow, and progress for a single rescue case.';
$pageCss = [
    '/admin/css/admin.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
];
$fontsHref = 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..900&family=Nunito:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap';
$importMapExtras = ['chart.js' => 'https://esm.sh/chart.js@4.4.4/auto'];
require __DIR__ . '/../../includes/site-head.php';
?>
  <body>
    <div id="app"><?= $pageHtml ?></div>
    <script>window.__PAGE_STATE__ = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script type="module" src="/admin/cases/js/case-detail.js"></script>
  </body>
</html>
