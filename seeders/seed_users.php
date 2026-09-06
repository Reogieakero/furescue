<?php

/**
 * Auth-only seeder: demo users, roles, and permissions.
 * Does not create reports, animals, medical records, cases, or other demo data.
 *
 *   php seeders\seed_users.php
 */

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/grant.php';
require_once __DIR__ . '/seed_permissions.php';
require_once __DIR__ . '/seed_accounts.php';

seed_permissions($pdo);
seed_accounts($ctx, true);
$grantCount = seed_grant_role_permissions($ctx);

$pw = SeedContext::DEV_PASSWORD;
echo "Users / roles / permissions seeded.\n";
echo "  admin:    admin@furescue.local / {$pw}\n";
echo "  rescuer:  rescuer@furescue.local … rescuer5@ (active), rescuer6–7 (pending) / {$pw}\n";
echo "  resident: juan@, maria@, ana@, pedro@, rosa@, miguel@furescue.local / {$pw}\n";
echo '  users: ' . $ctx->count('SELECT COUNT(*) FROM users') . "\n";
echo '  permissions: ' . $ctx->count('SELECT COUNT(*) FROM permissions') . "\n";
echo "  user_permissions granted this run: {$grantCount}\n";
