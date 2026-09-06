<?php

/**
 * Auth-only seeder: users, roles, and permissions.
 * Does not create reports, animals, medical records, cases, or other demo data.
 *
 *   php seeders\seed_users.php
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

use App\Auth\Permissions;
use App\Database;

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$devPassword = 'Password123!';
$hash = password_hash($devPassword, PASSWORD_ARGON2ID);

function users_insert(\PDO $pdo, string $table, array $data): string
{
    $id = $data['id'] ?? Database::uuidV4();
    $data['id'] = $id;
    $cols = array_keys($data);
    $colSql = implode(', ', array_map(static fn($c) => "`$c`", $cols));
    $placeholders = array_map(static fn($c) => ":$c", $cols);
    $sql = "INSERT INTO {$table} (" . $colSql . ") VALUES (" . implode(', ', $placeholders) . ")";
    $pdo->prepare($sql)->execute($data);
    return $id;
}

function users_id(\PDO $pdo, string $email): ?string
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ? $row['id'] : null;
}

function users_ensure(\PDO $pdo, array $data): string
{
    $existing = users_id($pdo, $data['email']);
    if ($existing) {
        return $existing;
    }
    return users_insert($pdo, 'users', $data);
}

function users_row_exists(\PDO $pdo, string $table, string $where, array $params = []): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE {$where} LIMIT 1");
    $stmt->execute($params);
    return (bool) $stmt->fetch();
}

$accounts = [
    [
        'full_name'      => 'City Vet Admin',
        'email'          => 'admin@furescue.local',
        'role'           => 'admin',
        'account_status' => 'active',
    ],
    [
        'full_name'      => 'Rescuer One',
        'email'          => 'rescuer@furescue.local',
        'role'           => 'rescuer',
        'account_status' => 'active',
        'phone_number'   => '09171234560',
    ],
    [
        'full_name'      => 'Resident Juan',
        'email'          => 'juan@furescue.local',
        'role'           => 'resident',
        'account_status' => 'active',
        'phone_number'   => '09171234561',
        'address'        => 'Barangay Poblacion, City of Mati',
    ],
    [
        'full_name'      => 'Resident Maria',
        'email'          => 'maria@furescue.local',
        'role'           => 'resident',
        'account_status' => 'active',
        'phone_number'   => '09171234562',
        'address'        => 'Barangay Dahican, City of Mati',
    ],
];

$userIdsByRole = ['admin' => [], 'rescuer' => [], 'resident' => []];

foreach ($accounts as $account) {
    $id = users_ensure($pdo, array_merge($account, [
        'password_hash' => $hash,
        'auth_provider' => 'native',
    ]));
    $userIdsByRole[$account['role']][] = $id;
}

$adminId = $userIdsByRole['admin'][0] ?? null;
foreach ($userIdsByRole['rescuer'] as $rescuerId) {
    if (!users_row_exists($pdo, 'rescuer_approvals', 'user_id = ?', [$rescuerId])) {
        users_insert($pdo, 'rescuer_approvals', [
            'user_id' => $rescuerId,
            'reviewed_by' => $adminId,
            'decision' => 'approved',
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
    }
    if (!users_row_exists($pdo, 'rescuer_duty_status', 'user_id = ?', [$rescuerId])) {
        users_insert($pdo, 'rescuer_duty_status', [
            'user_id' => $rescuerId,
            'status' => 'on_duty',
        ]);
    }
}

require __DIR__ . '/seed_permissions.php';

$permBySlug = [];
foreach ($pdo->query('SELECT id, slug FROM permissions') as $row) {
    $permBySlug[$row['slug']] = $row['id'];
}

$grant = $pdo->prepare(
    'INSERT INTO user_permissions (id, user_id, permission_id) VALUES (?, ?, ?)'
);
$hasGrant = $pdo->prepare(
    'SELECT 1 FROM user_permissions WHERE user_id = ? AND permission_id = ? LIMIT 1'
);

$grantCount = 0;
foreach ($accounts as $account) {
    $userId = users_id($pdo, $account['email']);
    if (!$userId) {
        continue;
    }
    foreach (Permissions::resolve($account['role']) as $slug) {
        $permissionId = $permBySlug[$slug] ?? null;
        if (!$permissionId) {
            fwrite(STDERR, "Skip unknown permission slug: {$slug}\n");
            continue;
        }
        $hasGrant->execute([$userId, $permissionId]);
        if ($hasGrant->fetch()) {
            continue;
        }
        $grant->execute([Database::uuidV4(), $userId, $permissionId]);
        $grantCount++;
    }
}

$count = static fn(string $sql): int => (int) $pdo->query($sql)->fetchColumn();

echo "Users / roles / permissions seeded.\n";
echo "  admin:    admin@furescue.local / {$devPassword}\n";
echo "  rescuer:  rescuer@furescue.local / {$devPassword}\n";
echo "  resident: juan@furescue.local, maria@furescue.local / {$devPassword}\n";
echo "  users: " . $count('SELECT COUNT(*) FROM users') . "\n";
echo "  permissions: " . $count('SELECT COUNT(*) FROM permissions') . "\n";
echo "  user_permissions granted this run: {$grantCount}\n";
