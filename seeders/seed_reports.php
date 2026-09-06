<?php

/**
 * Community stray-animal reports across Mati barangays.
 *
 *   php seeders\seed_reports.php
 */

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/catalog.php';

function seed_reports(SeedContext $ctx): void
{
    $ctx->loadUsers();
    if ($ctx->residentIds === [] || !$ctx->adminId) {
        echo "Reports skipped: seed accounts first.\n";
        return;
    }

    $needed = max(0, SeedContext::TARGET_REPORTS - $ctx->count('SELECT COUNT(*) FROM reports'));
    if ($needed === 0) {
        $ctx->loadReports();
        echo 'Reports: ' . count($ctx->reportIds) . " already present.\n";
        return;
    }

    $barangays = seed_catalog_barangays();
    $landmarks = seed_catalog_landmarks();
    $stories = seed_catalog_report_stories();
    $injuries = seed_catalog_injuries();
    $symptoms = seed_catalog_symptoms();

    $created = 0;
    $verifiedIds = [];
    $nextRef = $ctx->count('SELECT COUNT(*) FROM reports') + 1;
    for ($i = 0; $i < $needed; $i++) {
        $brgy = $barangays[$i % count($barangays)];
        $resident = $ctx->residentIds[$i % count($ctx->residentIds)];
        $animal = $ctx->pick(['dog', 'dog', 'dog', 'cat', 'cat']);
        $desc = str_replace(
            ['{animal}', '{injury}', '{symptom}', '{brgy}', '{landmark}'],
            [$animal, $ctx->pick($injuries), $ctx->pick($symptoms), $brgy[0], $ctx->pick($landmarks)],
            $stories[$i % count($stories)]
        );
        $desc .= ' Ref #' . str_pad((string) ($nextRef + $i), 4, '0', STR_PAD_LEFT) . '.';

        [$lat, $lng] = $ctx->jitter((float) $brgy[1], (float) $brgy[2]);
        $createdAt = $ctx->daysAgo(1, 280);
        $day = substr($createdAt, 0, 10);
        $hash = \App\Services\DedupService::contentHash($desc, $lat, $lng, $day);
        if ($ctx->rowExists('reports', 'content_hash = ?', [$hash])) {
            continue;
        }

        $bucket = $i % 20;
        if ($bucket === 0 && $verifiedIds !== []) {
            $original = $verifiedIds[array_rand($verifiedIds)];
            $ctx->insert('reports', [
                'resident_id' => $resident,
                'animal_description' => $desc . ' Possible duplicate of an earlier sighting.',
                'photo_urls' => null,
                'latitude' => $lat,
                'longitude' => $lng,
                'address_text' => $brgy[0] . ', City of Mati, Davao Oriental',
                'content_hash' => $hash,
                'duplicate_of_report_id' => $original,
                'validation_status' => 'flagged_duplicate',
                'status' => 'dismissed',
                'dismiss_reason' => 'Same animal already reported nearby within the last day.',
                'verified_by' => $ctx->adminId,
                'verified_at' => $ctx->daysAgo(1, 20),
                'created_at' => $createdAt,
            ]);
            $created++;
            continue;
        }

        if ($bucket <= 2) {
            $ctx->insert('reports', [
                'resident_id' => $resident,
                'animal_description' => $desc,
                'latitude' => $lat,
                'longitude' => $lng,
                'address_text' => $brgy[0] . ', City of Mati, Davao Oriental',
                'content_hash' => $hash,
                'validation_status' => 'pending',
                'status' => 'pending_verification',
                'created_at' => $createdAt,
            ]);
            $created++;
            continue;
        }

        if ($bucket === 3) {
            $ctx->insert('reports', [
                'resident_id' => $resident,
                'animal_description' => $desc,
                'latitude' => $lat,
                'longitude' => $lng,
                'address_text' => $brgy[0] . ', City of Mati, Davao Oriental',
                'content_hash' => $hash,
                'validation_status' => 'invalid',
                'status' => 'dismissed',
                'dismiss_reason' => $ctx->pick([
                    'Could not locate the animal after two site visits.',
                    'Resident confirmed the owner already retrieved the pet.',
                    'Outside City Vet coverage — referred to the neighboring LGU.',
                ]),
                'verified_by' => $ctx->adminId,
                'verified_at' => $ctx->daysAgo(1, 60),
                'created_at' => $createdAt,
            ]);
            $created++;
            continue;
        }

        $id = $ctx->insert('reports', [
            'resident_id' => $resident,
            'animal_description' => $desc,
            'latitude' => $lat,
            'longitude' => $lng,
            'address_text' => $brgy[0] . ', City of Mati, Davao Oriental',
            'content_hash' => $hash,
            'validation_status' => 'validated',
            'status' => 'verified',
            'verified_by' => $ctx->adminId,
            'verified_at' => date('Y-m-d H:i:s', strtotime($createdAt) + $ctx->int(1800, 86400 * 3)),
            'created_at' => $createdAt,
        ]);
        $verifiedIds[] = $id;
        $created++;
    }

    $ctx->loadReports();
    echo "Reports: {$created} new, " . count($ctx->reportIds) . ' total ('
        . count($ctx->verifiedReportIds) . " verified).\n";
}

if (seeders_is_entry(__FILE__)) {
    seed_reports($ctx);
}
