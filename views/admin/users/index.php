<?php

declare(strict_types=1);

require_once shared_path('search/search.php');

/** @var list<array<string, mixed>> $users */
/** @var string $uid */

const USER_PAGE_SIZE = 20;

const USER_ROLE_LABELS = [
    'admin' => 'Admin',
    'rescuer' => 'Rescuer',
    'resident' => 'Resident',
];

const USER_STATUS_LABELS = [
    'active' => 'Active',
    'pending' => 'Pending',
    'suspended' => 'Suspended',
    'rejected' => 'Rejected',
];

$userRoleStamp = static function (?string $role): string {
    return match ($role) {
        'admin' => 'stamp--coral',
        'rescuer' => 'stamp--accent',
        default => 'stamp--muted',
    };
};

$userStatusStamp = static function (?string $status): string {
    return match ($status) {
        'pending' => 'stamp--coral',
        'suspended', 'rejected' => 'stamp--muted',
        default => 'stamp--accent',
    };
};

$countRole = static function (string $role) use ($users): int {
    return count(array_filter($users, static fn(array $row) => ($row['role'] ?? '') === $role));
};
$countStatus = static function (string $status) use ($users): int {
    return count(array_filter($users, static fn(array $row) => ($row['account_status'] ?? '') === $status));
};

$counts = [
    'all' => count($users),
    'admin' => $countRole('admin'),
    'rescuer' => $countRole('rescuer'),
    'resident' => $countRole('resident'),
    'pending' => $countStatus('pending'),
    'suspended' => $countStatus('suspended'),
];

$kpiData = [
    ['icon' => 'users', 'value' => $counts['all'], 'label' => 'Total accounts', 'tone' => 'jungle'],
    ['icon' => 'shield', 'value' => $counts['admin'], 'label' => 'Admins', 'tone' => 'coral'],
    ['icon' => 'siren', 'value' => $counts['rescuer'], 'label' => 'Rescuers', 'tone' => 'sky'],
    ['icon' => 'heart-handshake', 'value' => $counts['resident'], 'label' => 'Residents', 'tone' => 'ink'],
];
$kpiTiles = '';
foreach ($kpiData as $k) {
    $kpiTiles .= kpi_card_html($k);
}

$filterDefs = [
    ['key' => 'all', 'label' => 'All', 'count' => $counts['all']],
    ['key' => 'admin', 'label' => 'Admin', 'count' => $counts['admin']],
    ['key' => 'rescuer', 'label' => 'Rescuer', 'count' => $counts['rescuer']],
    ['key' => 'resident', 'label' => 'Resident', 'count' => $counts['resident']],
    ['key' => 'pending', 'label' => 'Pending', 'count' => $counts['pending']],
    ['key' => 'suspended', 'label' => 'Suspended', 'count' => $counts['suspended']],
];
$tabButtons = '';
foreach ($filterDefs as $f) {
    $activeCls = $f['key'] === 'all' ? ' is-active' : '';
    $tabButtons .= '
        <button data-filter="' . e($f['key']) . '" class="q-btn' . $activeCls . '">' . e($f['label']) . ' &middot; ' . e((string) $f['count']) . '</button>';
}

$pageRows = array_slice($users, 0, USER_PAGE_SIZE);
$rowsHtml = '';
foreach ($pageRows as $row) {
    $id = (string) ($row['id'] ?? '');
    $isSelf = $id !== '' && $id === $uid;
    $name = trim((string) ($row['full_name'] ?? '')) !== '' ? (string) $row['full_name'] : 'Unnamed';
    $email = trim((string) ($row['email'] ?? '')) !== '' ? (string) $row['email'] : '—';
    $roleKey = (string) ($row['role'] ?? '');
    $statusKey = (string) ($row['account_status'] ?? '');
    $roleLabel = USER_ROLE_LABELS[$roleKey] ?? title_case($roleKey);
    $statusLabel = USER_STATUS_LABELS[$statusKey] ?? title_case($statusKey);
    $idAttr = 'data-id="' . e($id) . '"';
    $actions = button_html('Edit', 'outline', 'sm', icon: 'pencil', attrs: 'data-action="edit" ' . $idAttr);
    if (!$isSelf) {
        if ($statusKey === 'suspended' || $statusKey === 'rejected') {
            $actions .= button_html('Activate', 'outline', 'sm', icon: 'user-check', attrs: 'data-action="activate" ' . $idAttr);
        } elseif ($statusKey === 'pending') {
            $actions .= button_html('Activate', 'default', 'sm', icon: 'user-check', attrs: 'data-action="activate" ' . $idAttr);
        } else {
            $actions .= button_html('Suspend', 'outline', 'sm', icon: 'slash', attrs: 'data-action="suspend" ' . $idAttr);
        }
        $actions .= button_html('Delete', 'destructive', 'sm', icon: 'trash-2', attrs: 'data-action="delete" ' . $idAttr);
    }
    $you = $isSelf ? ' <span class="stamp stamp--sm stamp--accent">You</span>' : '';
    $rowsHtml .= '
    <tr data-id="' . e($id) . '">
      <td class="table-cell">
        <div class="table-avatar-name">
          ' . avatar_img($row['profile_photo_url'] ?? null, $name) . '
          <div>
            <div class="table-name table-cell--strong">' . e($name) . $you . '</div>
            <div class="table-cell--muted">' . e($email) . '</div>
          </div>
        </div>
      </td>
      <td class="table-cell"><span class="stamp stamp--sm ' . e($userRoleStamp($roleKey)) . '">' . e($roleLabel) . '</span></td>
      <td class="table-cell"><span class="stamp stamp--sm ' . e($userStatusStamp($statusKey)) . '">' . e($statusLabel) . '</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">' . e(time_ago($row['created_at'] ?? null)) . '</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">' . $actions . '</span>
      </td>
    </tr>';
}

if ($users === []) {
    $tableInner = '<div class="queue-empty">' . empty_state('users', 'No user accounts yet.') . '</div>';
} else {
    $pagination = '<div class="queue-pagination">' . pagination_bar(count($users), USER_PAGE_SIZE, 1) . '</div>';
    $tableInner = '
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Account</th><th>Role</th><th>Status</th><th>Joined</th><th class="table-cell--right">Action</th>
          </tr>
        </thead>
        <tbody>' . $rowsHtml . '</tbody>
      </table>
    </div>
    ' . $pagination;
}

$adminChildren = '
  <div class="page-head">
    <div>
      <span class="stamp stamp--accent">System</span>
      <h1 class="page-title">Users</h1>
      <p class="page-sub">Create accounts, change roles, and suspend or remove access.</p>
    </div>
    <div class="page-head-actions">
      ' . button_html('Export CSV', 'outline', icon: 'download', attrs: 'data-export="csv"') . '
      ' . button_html('Create user', 'default', icon: 'user-plus', attrs: 'data-action="create"') . '
    </div>
  </div>
  <div id="user-kpis" class="kpi-grid">' . $kpiTiles . '</div>
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="users"></i>
        <h2 class="panel-title" id="user-panel-title">All accounts</h2>
      </div>
    </div>
    <div id="user-filters">
      ' . toolbar_html(
          filter_tabs_html('user-tabs', $tabButtons)
          . search_control('user-search', 'Search name, email, phone…')
      ) . '
    </div>
    <div id="user-table" class="panel-body">' . $tableInner . '</div>
  </div>';
