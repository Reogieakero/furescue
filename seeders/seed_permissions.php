<?php

/**
 * Permission slugs catalog. Idempotent.
 *
 *   php seeders\seed_permissions.php
 */

require_once __DIR__ . '/lib/bootstrap.php';

function seed_permissions(PDO $pdo): int
{
    $permissions = [
        ['animals.read', 'List and view animal records'],
        ['animals.write', 'Create and update animal records'],
        ['animals.medical.read', 'View animal medical records'],
        ['animals.medical.write', 'Create and update animal medical records'],
        ['animals.documents.upload', 'Upload animal documents'],
        ['animals.documents.delete', 'Delete animal documents'],
        ['animals.field_status.write', 'Update animal field status'],
        ['animals.vitals.read', 'View animal vitals'],
        ['cases.read', 'List and view cases'],
        ['cases.assign', 'Assign cases to rescuers'],
        ['cases.respond', 'Respond to assigned cases'],
        ['cases.status_change', 'Update case status'],
        ['cases.proof', 'Add resolution proof to cases'],
        ['reports.read', 'View all reports'],
        ['reports.read_own', 'View own reports'],
        ['reports.create', 'Create new reports'],
        ['reports.verify', 'Verify reports'],
        ['reports.dismiss', 'Dismiss reports'],
        ['users.read', 'View user profiles'],
        ['users.update_self', 'Update own profile'],
        ['users.create', 'Create user accounts'],
        ['users.write', 'Update other user accounts'],
        ['users.delete', 'Delete user accounts'],
        ['users.approve_rescuers', 'Approve rescuer applications'],
        ['users.reject_rescuers', 'Reject rescuer applications'],
        ['users.toggle_duty', 'Toggle rescuer duty status'],
        ['notifications.read', 'Read notifications'],
        ['notifications.broadcast', 'Broadcast notifications'],
        ['notifications.delete', 'Delete notifications'],
        ['analytics.read', 'View analytics dashboards'],
        ['analytics.export', 'Export analytics data'],
        ['elearning.read', 'View e-learning modules'],
        ['elearning.write', 'Create and update e-learning modules'],
        ['adoptions.read', 'View adoption records'],
        ['adoptions.apply', 'Submit adoption applications'],
        ['adoptions.approve', 'Approve adoption applications'],
        ['adoptions.reject', 'Reject adoption applications'],
        ['adoptions.complete', 'Complete adoptions'],
        ['adoptions.listings.create', 'Create adoption listings'],
        ['adoptions.listings.approve', 'Approve adoption listings'],
        ['adoptions.listings.reject', 'Reject adoption listings'],
        ['messages.send', 'Send messages'],
        ['messages.read', 'Read message threads'],
        ['messages.mark_read', 'Mark messages as read'],
        ['health.read', 'View health updates and records'],
        ['health.export', 'Export health data'],
        ['vitals.ingest', 'Ingest vitals from devices'],
    ];

    $exists = $pdo->prepare('SELECT 1 FROM permissions WHERE slug = ?');
    $insert = $pdo->prepare('INSERT INTO permissions (slug, description) VALUES (?, ?)');
    $added = 0;
    foreach ($permissions as [$slug, $description]) {
        $exists->execute([$slug]);
        if ($exists->fetchColumn()) {
            continue;
        }
        $insert->execute([$slug, $description]);
        $added++;
    }

    echo 'Permissions seeded: ' . count($permissions) . " slugs processed ({$added} new).\n";
    return count($permissions);
}

if (seeders_is_entry(__FILE__)) {
    seed_permissions($pdo);
}
