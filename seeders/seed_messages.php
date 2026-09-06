<?php

/**
 * In-app messages tied to real reports, cases, and adoptions.
 *
 *   php seeders\seed_messages.php
 */

require_once __DIR__ . '/lib/bootstrap.php';

function seed_messages(SeedContext $ctx): void
{
    $ctx->loadUsers();
    $ctx->loadReports();
    $ctx->loadCases();
    $ctx->loadAdoptions();
    if ($ctx->adminId === null || $ctx->residentIds === []) {
        echo "Messages skipped: seed accounts first.\n";
        return;
    }

    $needed = max(0, SeedContext::TARGET_MESSAGES - $ctx->count('SELECT COUNT(*) FROM messages'));
    if ($needed === 0) {
        echo 'Messages: ' . $ctx->count('SELECT COUNT(*) FROM messages') . " already present.\n";
        return;
    }

    $created = 0;
    $created += seed_messages_cases($ctx, (int) ceil($needed * 0.45));
    $created += seed_messages_adoptions($ctx, (int) ceil($needed * 0.35));
    $created += seed_messages_reports($ctx, $needed - $created);

    echo "Messages: {$created} new, " . $ctx->count('SELECT COUNT(*) FROM messages') . " total.\n";
}

function seed_messages_cases(SeedContext $ctx, int $limit): int
{
    if ($ctx->cases === [] || $limit <= 0) {
        return 0;
    }
    $lines = [
        ['resident', 'Thank you for taking this case. The animal is still near the waiting shed.'],
        ['rescuer', 'On site now. I can see the dog under the store awning.'],
        ['admin', 'Please upload proof photos once the animal is in the crate.'],
        ['rescuer', 'Contained and heading to the holding kennel. Will update status shortly.'],
        ['resident', 'Salamat. The barangay tanod is waiting if you need a second pair of hands.'],
    ];
    return seed_messages_write($ctx, $ctx->cases, 'case', $lines, $limit);
}

function seed_messages_adoptions(SeedContext $ctx, int $limit): int
{
    if ($ctx->adoptions === [] || $limit <= 0) {
        return 0;
    }
    $lines = [
        ['resident', 'Can we schedule a meet-and-greet this Saturday morning?'],
        ['admin', 'Yes — please bring a valid ID and photos of the sleeping area.'],
        ['resident', 'We finished the responsible pet ownership module last week.'],
        ['admin', 'Noted. We will confirm after the home-check notes are encoded.'],
    ];
    $rows = array_map(static fn($a) => [
        'id' => $a['id'],
        'rescuer_id' => null,
        'report_id' => null,
        'applicant_id' => $a['applicant_id'],
    ], $ctx->adoptions);
    return seed_messages_write($ctx, $rows, 'adoption', $lines, $limit);
}

function seed_messages_reports(SeedContext $ctx, int $limit): int
{
    if ($ctx->verifiedReports === [] || $limit <= 0) {
        return 0;
    }
    $lines = [
        ['resident', 'The animal moved closer to the chapel. Sharing an updated location.'],
        ['admin', 'Report received. A case will be opened after verification.'],
        ['resident', 'I left a bowl of water. Still here as of 5 PM.'],
    ];
    $rows = array_map(static fn($r) => [
        'id' => $r['id'],
        'rescuer_id' => null,
        'resident_id' => $r['resident_id'],
    ], $ctx->verifiedReports);
    return seed_messages_write($ctx, $rows, 'report', $lines, $limit);
}

function seed_messages_write(SeedContext $ctx, array $entities, string $type, array $lines, int $limit): int
{
    $created = 0;
    $n = count($entities);
    for ($i = 0; $i < $limit; $i++) {
        $entity = $entities[$i % $n];
        [$who, $text] = $lines[$i % count($lines)];
        $pair = seed_messages_pair($ctx, $entity, $type, $who);
        if ($pair === null) {
            continue;
        }
        [$sender, $receiver] = $pair;
        if ($sender === $receiver) {
            continue;
        }
        $marker = $text . ' · ' . substr($entity['id'], 0, 8) . ' · ' . $i;
        if ($ctx->rowExists('messages', 'related_id = ? AND message_text = ?', [$entity['id'], $marker])) {
            continue;
        }
        $ctx->insert('messages', [
            'sender_id' => $sender,
            'receiver_id' => $receiver,
            'related_type' => $type,
            'related_id' => $entity['id'],
            'message_text' => $marker,
            'sent_at' => $ctx->daysAgo(0, 50),
            'read_at' => $i % 3 === 0 ? null : $ctx->daysAgo(0, 20),
        ]);
        $created++;
    }
    return $created;
}

function seed_messages_pair(SeedContext $ctx, array $entity, string $type, string $who): ?array
{
    $admin = $ctx->adminId;
    $rescuer = $entity['rescuer_id'] ?? ($ctx->rescuerIds[0] ?? $admin);
    $resident = $entity['resident_id'] ?? $entity['applicant_id'] ?? ($ctx->residentIds[0] ?? null);
    if (!$admin || !$resident) {
        return null;
    }
    return match ($who) {
        'rescuer' => [$rescuer ?: $admin, $admin],
        'resident' => [$resident, $type === 'case' ? ($rescuer ?: $admin) : $admin],
        default => [$admin, $resident],
    };
}

if (seeders_is_entry(__FILE__)) {
    seed_messages($ctx);
}
