<?php

/**
 * User notifications and a few admin broadcasts.
 *
 *   php seeders\seed_notifications.php
 */

require_once __DIR__ . '/lib/bootstrap.php';

function seed_notifications(SeedContext $ctx): void
{
    $ctx->loadUsers();
    $ctx->loadReports();
    $ctx->loadCases();
    $ctx->loadAdoptions();
    if ($ctx->adminId === null) {
        echo "Notifications skipped: seed accounts first.\n";
        return;
    }

    $have = $ctx->count('SELECT COUNT(*) FROM notifications');
    $needed = max(0, SeedContext::TARGET_NOTIFICATIONS - $have);
    $created = 0;
    if ($needed > 0) {
        $created += seed_notifications_events($ctx, (int) ceil($needed * 0.75));
        $created += seed_notifications_broadcasts($ctx);
    }

    echo "Notifications: {$created} new, " . $ctx->count('SELECT COUNT(*) FROM notifications') . " total.\n";
}

function seed_notifications_events(SeedContext $ctx, int $limit): int
{
    $templates = [];
    foreach (array_slice($ctx->verifiedReports, 0, 80) as $report) {
        $templates[] = [
            'user_id' => $report['resident_id'],
            'type' => 'report_update',
            'message' => 'Your stray-animal report was verified. A rescue case is now open.',
            'related_type' => 'report',
            'related_id' => $report['id'],
        ];
    }
    foreach ($ctx->cases as $case) {
        if (empty($case['rescuer_id'])) {
            continue;
        }
        $templates[] = [
            'user_id' => $case['rescuer_id'],
            'type' => 'case_update',
            'message' => 'You were assigned a rescue case. Please accept it from the field queue.',
            'related_type' => 'case',
            'related_id' => $case['id'],
        ];
        if ($case['status'] === 'resolved') {
            $templates[] = [
                'user_id' => $ctx->adminId,
                'type' => 'case_update',
                'message' => 'A rescuer marked a case as resolved. Review the notes when you can.',
                'related_type' => 'case',
                'related_id' => $case['id'],
            ];
        }
    }
    foreach ($ctx->adoptions as $adoption) {
        $templates[] = [
            'user_id' => $adoption['applicant_id'],
            'type' => 'adoption_update',
            'message' => seed_notifications_adoption_copy((string) $adoption['status']),
            'related_type' => 'adoption',
            'related_id' => $adoption['id'],
        ];
    }

    if ($templates === []) {
        return 0;
    }

    $created = 0;
    $n = count($templates);
    for ($i = 0; $i < min($limit, $n); $i++) {
        $row = $templates[$i];
        if ($ctx->rowExists('notifications', 'user_id = ? AND related_id = ? AND type = ?', [
            $row['user_id'], $row['related_id'], $row['type'],
        ])) {
            continue;
        }
        $ctx->insert('notifications', [
            'user_id' => $row['user_id'],
            'type' => $row['type'],
            'message' => $row['message'],
            'related_type' => $row['related_type'],
            'related_id' => $row['related_id'],
            'is_read' => $i % 3 === 0 ? 1 : 0,
            'created_at' => $ctx->daysAgo(0, 40),
        ]);
        $created++;
    }
    return $created;
}

function seed_notifications_broadcasts(SeedContext $ctx): int
{
    $messages = [
        'City Vet: rabies vaccination caravan this Friday at Barangay Dahican covered court, 8 AM to 12 NN.',
        'Please keep newly adopted animals indoors for the first two weeks while they settle.',
        'Rescuers on duty: bring extra leashes. Several coastal reports came in after last night’s rain.',
    ];
    $created = 0;
    $targets = array_values(array_unique(array_merge($ctx->adminIds, $ctx->rescuerIds, array_slice($ctx->residentIds, 0, 40))));
    foreach ($messages as $message) {
        foreach ($targets as $userId) {
            if ($ctx->rowExists('notifications', 'user_id = ? AND message = ? AND type = ?', [$userId, $message, 'admin_announcement'])) {
                continue;
            }
            $ctx->insert('notifications', [
                'user_id' => $userId,
                'type' => 'admin_announcement',
                'message' => $message,
                'related_type' => null,
                'related_id' => null,
                'is_read' => 0,
                'created_at' => $ctx->daysAgo(1, 15),
            ]);
            $created++;
        }
    }
    return $created;
}

function seed_notifications_adoption_copy(string $status): string
{
    return match ($status) {
        'approved' => 'Your adoption application was approved. Please coordinate the pickup schedule.',
        'rejected' => 'Your adoption application was not approved. You may apply for another animal.',
        'completed' => 'Adoption completed. Welcome your new companion — keep the City Vet number handy.',
        'cancelled' => 'Your adoption application was cancelled as requested.',
        default => 'City Vet received your adoption application and will review it shortly.',
    };
}

if (seeders_is_entry(__FILE__)) {
    seed_notifications($ctx);
}
