<?php

/**
 * Rescue cases and activity logs for verified reports.
 *
 *   php seeders\seed_cases.php
 */

require_once __DIR__ . '/lib/bootstrap.php';

function seed_cases(SeedContext $ctx): void
{
    $ctx->loadUsers();
    $ctx->loadReports();
    if ($ctx->verifiedReports === [] || !$ctx->adminId) {
        echo "Cases skipped: seed verified reports first.\n";
        return;
    }

    $onDuty = $ctx->ids(
        "SELECT u.id FROM users u
         JOIN rescuer_duty_status d ON d.user_id = u.id
         WHERE u.role = 'rescuer' AND u.account_status = 'active' AND d.status = 'on_duty'"
    );
    $rescuers = $onDuty !== [] ? $onDuty : $ctx->rescuerIds;
    if ($rescuers === []) {
        echo "Cases skipped: no active rescuers.\n";
        return;
    }

    $created = 0;
    foreach ($ctx->verifiedReports as $i => $report) {
        if ($ctx->rowExists('cases', 'report_id = ?', [$report['id']])) {
            continue;
        }

        $bucket = $i % 10;
        if ($bucket === 0) {
            $status = 'open';
            $rescuer = null;
        } elseif ($bucket <= 2) {
            $status = 'assigned';
            $rescuer = $rescuers[$i % count($rescuers)];
        } elseif ($bucket <= 5) {
            $status = 'in_progress';
            $rescuer = $rescuers[$i % count($rescuers)];
        } else {
            $status = 'resolved';
            $rescuer = $rescuers[$i % count($rescuers)];
        }

        $createdAt = $ctx->daysAgo(1, 240);
        $notes = null;
        if ($status === 'resolved') {
            $place = $report['address_text'] ?: 'the reported site';
            $notes = $ctx->pick([
                "Animal safely contained at {$place} and brought to the City Vet holding kennel.",
                'Rescued after dark with barangay tanod assistance. Now on fluids and observation.',
                'Owner later identified; case closed after on-site first aid and owner counseling.',
                'Transferred to temporary foster in Central while awaiting a kennel slot.',
            ]);
        }

        $caseId = $ctx->insert('cases', [
            'report_id' => $report['id'],
            'assigned_rescuer_id' => $rescuer,
            'assigned_by' => $rescuer ? $ctx->adminId : null,
            'status' => $status,
            'resolution_notes' => $notes,
            'created_at' => $createdAt,
        ]);
        seed_cases_activity($ctx, $caseId, $status, $rescuer, $createdAt);
        $created++;
    }

    $ctx->loadCases();
    echo "Cases: {$created} new, " . count($ctx->caseIds) . " total.\n";
}

function seed_cases_activity(SeedContext $ctx, string $caseId, string $status, ?string $rescuer, string $createdAt): void
{
    $admin = $ctx->adminId;
    if (!$admin) {
        return;
    }

    $stamp = strtotime($createdAt);
    $log = static function (string $action, string $notes, string $actor, string $role, int $offset) use ($ctx, $caseId, $stamp): void {
        if ($ctx->rowExists('case_activity_log', 'case_id = ? AND action = ? AND notes = ?', [$caseId, $action, $notes])) {
            return;
        }
        $ctx->insert('case_activity_log', [
            'case_id' => $caseId,
            'actor_id' => $actor,
            'actor_role' => $role,
            'action' => $action,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s', $stamp + $offset),
        ]);
    };

    $log('opened', 'Case opened after the report was verified.', $admin, 'admin', 60);
    if ($rescuer) {
        $log('assigned', 'Rescuer assigned from the on-duty pool.', $admin, 'admin', 900);
    }
    if (in_array($status, ['in_progress', 'resolved'], true) && $rescuer) {
        $log('accepted', 'Rescuer accepted the assignment and is en route.', $rescuer, 'rescuer', 2400);
        $log('status_changed', 'Field response started at the reported barangay.', $rescuer, 'rescuer', 5400);
    }
    if ($status === 'resolved' && $rescuer) {
        $log('resolved', 'Rescue completed. Notes posted on the case.', $rescuer, 'rescuer', 12000);
    }
}

if (seeders_is_entry(__FILE__)) {
    seed_cases($ctx);
}
