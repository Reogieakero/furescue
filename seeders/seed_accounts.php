<?php

/**
 * Users, rescuer approvals, and duty status.
 *
 *   php seeders\seed_accounts.php
 */

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/catalog.php';

function seed_accounts(SeedContext $ctx, bool $demoOnly = false): void
{
    $barangays = seed_catalog_barangays();
    $firstNames = seed_catalog_first_names();
    $lastNames = seed_catalog_last_names();

    $demoAccounts = [
        [
            'full_name' => 'City Vet Admin',
            'email' => 'admin@furescue.local',
            'role' => 'admin',
            'account_status' => 'active',
        ],
        [
            'full_name' => 'Rescuer One',
            'email' => 'rescuer@furescue.local',
            'role' => 'rescuer',
            'account_status' => 'active',
            'phone_number' => '09171234560',
            'duty' => 'on_duty',
        ],
        [
            'full_name' => 'Liza Ramos',
            'email' => 'rescuer2@furescue.local',
            'role' => 'rescuer',
            'account_status' => 'active',
            'phone_number' => '09171234563',
            'duty' => 'on_duty',
        ],
        [
            'full_name' => 'Carlos Mendoza',
            'email' => 'rescuer3@furescue.local',
            'role' => 'rescuer',
            'account_status' => 'active',
            'phone_number' => '09171234564',
            'duty' => 'on_duty',
        ],
        [
            'full_name' => 'Elena Castillo',
            'email' => 'rescuer4@furescue.local',
            'role' => 'rescuer',
            'account_status' => 'active',
            'phone_number' => '09171234565',
            'duty' => 'on_duty',
        ],
        [
            'full_name' => 'Diego Villanueva',
            'email' => 'rescuer5@furescue.local',
            'role' => 'rescuer',
            'account_status' => 'active',
            'phone_number' => '09171234566',
            'duty' => 'on_duty',
        ],
        [
            'full_name' => 'Marco Aquino',
            'email' => 'rescuer6@furescue.local',
            'role' => 'rescuer',
            'account_status' => 'pending',
            'phone_number' => '09171234567',
        ],
        [
            'full_name' => 'Sofia Rivera',
            'email' => 'rescuer7@furescue.local',
            'role' => 'rescuer',
            'account_status' => 'pending',
            'phone_number' => '09171234568',
        ],
        [
            'full_name' => 'Resident Juan Dela Cruz',
            'email' => 'juan@furescue.local',
            'role' => 'resident',
            'account_status' => 'active',
            'phone_number' => '09171234561',
            'address' => 'Purok 2, Barangay Central, City of Mati, Davao Oriental',
        ],
        [
            'full_name' => 'Resident Maria Santos',
            'email' => 'maria@furescue.local',
            'role' => 'resident',
            'account_status' => 'active',
            'phone_number' => '09171234562',
            'address' => 'Purok 4, Barangay Dahican, City of Mati, Davao Oriental',
        ],
        [
            'full_name' => 'Ana Garcia',
            'email' => 'ana@furescue.local',
            'role' => 'resident',
            'account_status' => 'active',
            'phone_number' => '09171234569',
            'address' => 'Purok 1, Barangay Mayo, City of Mati, Davao Oriental',
        ],
        [
            'full_name' => 'Pedro Torres',
            'email' => 'pedro@furescue.local',
            'role' => 'resident',
            'account_status' => 'active',
            'phone_number' => '09171234570',
            'address' => 'Purok 3, Barangay Matiao, City of Mati, Davao Oriental',
        ],
        [
            'full_name' => 'Rosa Bautista',
            'email' => 'rosa@furescue.local',
            'role' => 'resident',
            'account_status' => 'active',
            'phone_number' => '09171234571',
            'address' => 'Purok 5, Barangay Bobon, City of Mati, Davao Oriental',
        ],
        [
            'full_name' => 'Miguel Lopez',
            'email' => 'miguel@furescue.local',
            'role' => 'resident',
            'account_status' => 'active',
            'phone_number' => '09171234572',
            'address' => 'Purok 6, Barangay Taguibo, City of Mati, Davao Oriental',
        ],
    ];

    foreach ($demoAccounts as $account) {
        $duty = $account['duty'] ?? null;
        unset($account['duty']);
        $id = $ctx->ensureUser(array_merge($account, [
            'password_hash' => $ctx->hash,
            'auth_provider' => 'native',
        ]));
        seed_accounts_rescuer_extras($ctx, $id, $account, $duty);
    }

    if (!$demoOnly) {
        seed_accounts_fill($ctx, $barangays, $firstNames, $lastNames);
    }

    $ctx->loadUsers();
    echo 'Accounts: ' . $ctx->count('SELECT COUNT(*) FROM users')
        . ' users (' . count($ctx->residentIds) . ' residents, '
        . count($ctx->rescuerIds) . ' active rescuers, '
        . count($ctx->pendingRescuerIds) . " pending).\n";
}

function seed_accounts_rescuer_extras(SeedContext $ctx, string $userId, array $account, ?string $duty): void
{
    if (($account['role'] ?? '') !== 'rescuer') {
        return;
    }
    if ($account['account_status'] === 'active' && !$ctx->rowExists('rescuer_approvals', 'user_id = ?', [$userId])) {
        $ctx->insert('rescuer_approvals', [
            'user_id' => $userId,
            'reviewed_by' => $ctx->userId('admin@furescue.local'),
            'decision' => 'approved',
            'remarks' => 'Approved after barangay clearance and orientation.',
            'reviewed_at' => $ctx->daysAgo(20, 180),
        ]);
    }
    if ($account['account_status'] === 'rejected' && !$ctx->rowExists('rescuer_approvals', 'user_id = ?', [$userId])) {
        $ctx->insert('rescuer_approvals', [
            'user_id' => $userId,
            'reviewed_by' => $ctx->userId('admin@furescue.local'),
            'decision' => 'rejected',
            'remarks' => 'Incomplete training certificate. May reapply after the next City Vet orientation.',
            'reviewed_at' => $ctx->daysAgo(5, 40),
        ]);
    }
    if (in_array($account['account_status'], ['active', 'suspended'], true)
        && !$ctx->rowExists('rescuer_duty_status', 'user_id = ?', [$userId])) {
        $ctx->insert('rescuer_duty_status', [
            'user_id' => $userId,
            'status' => $duty ?? ($account['account_status'] === 'active' ? $ctx->pick(['on_duty', 'on_duty', 'off_duty']) : 'off_duty'),
        ]);
    }
}

function seed_accounts_fill(SeedContext $ctx, array $barangays, array $firstNames, array $lastNames): void
{
    $residentCount = $ctx->count("SELECT COUNT(*) FROM users WHERE role = 'resident'");
    $neededResidents = max(0, SeedContext::TARGET_RESIDENTS - $residentCount);
    for ($i = 0; $i < $neededResidents; $i++) {
        $fn = $firstNames[($residentCount + $i) % count($firstNames)];
        $ln = $lastNames[($residentCount + $i * 3) % count($lastNames)];
        $full = $fn . ' ' . $ln;
        $email = seed_email_slug($fn . $ln) . ($residentCount + $i + 1) . '@furescue.local';
        $brgy = $barangays[($residentCount + $i) % count($barangays)][0];
        $status = ($i % 35 === 0) ? 'suspended' : 'active';
        $ctx->ensureUser([
            'full_name' => $full,
            'email' => $email,
            'password_hash' => $ctx->hash,
            'auth_provider' => 'native',
            'role' => 'resident',
            'account_status' => $status,
            'phone_number' => '09' . str_pad((string) (171200000 + $residentCount + $i), 9, '0', STR_PAD_LEFT),
            'address' => 'Purok ' . (($i % 7) + 1) . ", Barangay {$brgy}, City of Mati, Davao Oriental",
        ]);
    }

    $activeRescuers = $ctx->count("SELECT COUNT(*) FROM users WHERE role = 'rescuer' AND account_status = 'active'");
    $neededRescuers = max(0, SeedContext::TARGET_RESCUERS - $activeRescuers);
    for ($i = 0; $i < $neededRescuers; $i++) {
        $fn = $firstNames[($i + 11) % count($firstNames)];
        $ln = $lastNames[($i + 17) % count($lastNames)];
        $email = seed_email_slug($fn . $ln) . 'rescuer' . ($activeRescuers + $i + 1) . '@furescue.local';
        $id = $ctx->ensureUser([
            'full_name' => $fn . ' ' . $ln,
            'email' => $email,
            'password_hash' => $ctx->hash,
            'auth_provider' => 'native',
            'role' => 'rescuer',
            'account_status' => 'active',
            'phone_number' => '09' . str_pad((string) (181200000 + $i), 9, '0', STR_PAD_LEFT),
            'address' => 'City Veterinarian volunteer pool, City of Mati',
        ]);
        seed_accounts_rescuer_extras($ctx, $id, ['role' => 'rescuer', 'account_status' => 'active'], null);
    }

    $pending = $ctx->count("SELECT COUNT(*) FROM users WHERE role = 'rescuer' AND account_status = 'pending'");
    $neededPending = max(0, SeedContext::TARGET_APPLICANTS - $pending);
    for ($i = 0; $i < $neededPending; $i++) {
        $fn = $firstNames[($i + 29) % count($firstNames)];
        $ln = $lastNames[($i + 5) % count($lastNames)];
        $ctx->ensureUser([
            'full_name' => $fn . ' ' . $ln,
            'email' => seed_email_slug($fn . $ln) . 'app' . ($pending + $i + 1) . '@furescue.local',
            'password_hash' => $ctx->hash,
            'auth_provider' => 'native',
            'role' => 'rescuer',
            'account_status' => 'pending',
            'phone_number' => '09' . str_pad((string) (191200000 + $i), 9, '0', STR_PAD_LEFT),
        ]);
    }

    $rejected = $ctx->count("SELECT COUNT(*) FROM users WHERE role = 'rescuer' AND account_status = 'rejected'");
    $neededRejected = max(0, SeedContext::TARGET_REJECTED - $rejected);
    for ($i = 0; $i < $neededRejected; $i++) {
        $fn = $firstNames[($i + 41) % count($firstNames)];
        $ln = $lastNames[($i + 9) % count($lastNames)];
        $id = $ctx->ensureUser([
            'full_name' => $fn . ' ' . $ln,
            'email' => seed_email_slug($fn . $ln) . 'rej' . ($rejected + $i + 1) . '@furescue.local',
            'password_hash' => $ctx->hash,
            'auth_provider' => 'native',
            'role' => 'rescuer',
            'account_status' => 'rejected',
            'phone_number' => '09' . str_pad((string) (201200000 + $i), 9, '0', STR_PAD_LEFT),
        ]);
        seed_accounts_rescuer_extras($ctx, $id, ['role' => 'rescuer', 'account_status' => 'rejected'], 'off_duty');
    }
}

if (seeders_is_entry(__FILE__)) {
    require_once __DIR__ . '/seed_permissions.php';
    seed_permissions($pdo);
    seed_accounts($ctx, false);
    require_once __DIR__ . '/lib/grant.php';
    echo 'Permissions granted this run: ' . seed_grant_role_permissions($ctx) . "\n";
}
