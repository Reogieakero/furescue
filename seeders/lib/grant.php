<?php

/**
 * Grant each user the default permission slugs for their role.
 */

if (defined('SEED_GRANT_LOADED')) {
    return;
}
define('SEED_GRANT_LOADED', true);

function seed_grant_role_permissions(SeedContext $ctx, ?array $userIds = null): int
{
    $permBySlug = [];
    foreach ($ctx->pdo->query('SELECT id, slug FROM permissions') as $row) {
        $permBySlug[$row['slug']] = $row['id'];
    }

    if ($userIds === null) {
        $users = $ctx->pdo->query('SELECT id, role FROM users')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        $users = [];
        $stmt = $ctx->pdo->prepare('SELECT id, role FROM users WHERE id = ?');
        foreach ($userIds as $id) {
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $users[] = $row;
            }
        }
    }

    $grant = $ctx->pdo->prepare(
        'INSERT INTO user_permissions (id, user_id, permission_id) VALUES (?, ?, ?)'
    );
    $hasGrant = $ctx->pdo->prepare(
        'SELECT 1 FROM user_permissions WHERE user_id = ? AND permission_id = ? LIMIT 1'
    );

    $written = 0;
    foreach ($users as $user) {
        foreach (\App\Auth\Permissions::resolve((string) $user['role']) as $slug) {
            $permissionId = $permBySlug[$slug] ?? null;
            if (!$permissionId) {
                continue;
            }
            $hasGrant->execute([$user['id'], $permissionId]);
            if ($hasGrant->fetch()) {
                continue;
            }
            $grant->execute([\App\Database::uuidV4(), $user['id'], $permissionId]);
            $written++;
        }
    }

    return $written;
}
