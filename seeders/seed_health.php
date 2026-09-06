<?php

/**
 * Medical records, vaccination history, vitals, and document stubs.
 *
 *   php seeders\seed_health.php
 */

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/catalog.php';

function seed_health(SeedContext $ctx): void
{
    $ctx->loadUsers();
    $ctx->loadAnimals(true);
    if ($ctx->animals === [] || !$ctx->adminId) {
        echo "Health skipped: seed animals first.\n";
        return;
    }

    $vets = seed_catalog_vets();
    $conditions = seed_catalog_conditions();
    $medicals = 0;
    $vitals = 0;
    $docs = 0;

    foreach ($ctx->animals as $i => $animal) {
        $species = $animal['species'];
        $unhealthy = $i % 5 === 0;
        $lastCheckup = $ctx->dateAgo(3, 80);
        $weight = $species === 'cat' ? $ctx->float(2.4, 6.2, 1) : $ctx->float(6.0, 28.0, 1);
        $temp = $unhealthy ? $ctx->float(39.4, 40.4, 1) : $ctx->float(38.0, 39.2, 1);
        $vax = seed_health_vaccines($species, $lastCheckup, $unhealthy);

        if (!$ctx->rowExists('animal_medical_records', 'animal_id = ?', [$animal['id']])) {
            $ctx->insert('animal_medical_records', [
                'animal_id' => $animal['id'],
                'medical_history_notes' => seed_health_notes($animal, $unhealthy),
                'vaccination_status' => $vax['status'],
                'vaccination_details' => json_encode($vax['details']),
                'vaccine_protocols' => json_encode($vax['protocols']),
                'vaccination_records' => json_encode($vax['records']),
                'last_checkup_date' => $lastCheckup,
                'next_checkup_due' => date('Y-m-d', strtotime($lastCheckup . ' +' . ($unhealthy ? '14' : '90') . ' days')),
                'vaccination_expiry' => $vax['status'] === 'none' ? null : date('Y-m-d', strtotime($lastCheckup . ' +365 days')),
                'condition' => $unhealthy ? $conditions[$i % count($conditions)] : 'Healthy',
                'treatment_stage' => $unhealthy ? $ctx->pick(['ongoing', 'ongoing', 'completed']) : 'none',
                'deworming_status' => $ctx->pick(['up_to_date', 'up_to_date', 'overdue', 'unknown']),
                'neutered' => $ctx->pick(['yes', 'no', 'unknown']),
                'weight_kg' => $weight,
                'temperature_c' => $temp,
                'vet_name' => $vets[$i % count($vets)],
                'updated_by' => $ctx->adminId,
            ]);
            $medicals++;
        }

        $existingVitals = $ctx->count('SELECT COUNT(*) FROM vitals_log WHERE animal_id = ?', [$animal['id']]);
        $needVitals = max(0, 4 - $existingVitals);
        $baseHr = $species === 'cat' ? 165 : 95;
        for ($k = 0; $k < $needVitals; $k++) {
            $ctx->insert('vitals_log', [
                'animal_id' => $animal['id'],
                'heart_rate_bpm' => $baseHr + $ctx->int(-12, 18),
                'respiratory_rate_bpm' => $species === 'cat' ? $ctx->int(18, 32) : $ctx->int(14, 26),
                'recorded_at' => $ctx->daysAgo(1, 90),
                'source' => $ctx->pick(['clinic', 'clinic', 'field', 'iot']),
            ]);
            $vitals++;
        }

        if ($i % 4 === 0 && !$ctx->rowExists('animal_documents', 'animal_id = ? AND name = ?', [$animal['id'], 'Intake exam notes'])) {
            $ctx->insert('animal_documents', [
                'animal_id' => $animal['id'],
                'name' => 'Intake exam notes',
                'doc_type' => 'exam',
                'file_url' => null,
                'meta' => 'Seeded clinic intake summary',
                'uploaded_by' => $ctx->adminId,
            ]);
            $docs++;
        }
    }

    echo "Health: {$medicals} medical records, {$vitals} vitals, {$docs} documents.\n";
}

function seed_health_vaccines(string $species, string $lastCheckup, bool $unhealthy): array
{
    if ($species === 'cat') {
        $protocols = [
            ['name' => 'FVRCP', 'interval_days' => 28],
            ['name' => 'Rabies', 'interval_days' => 365],
        ];
        $core = ['FVRCP', 'Rabies'];
    } else {
        $protocols = [
            ['name' => 'DHPP / DAPP', 'interval_days' => 28],
            ['name' => 'Rabies', 'interval_days' => 365],
            ['name' => 'Leptospirosis', 'interval_days' => 365],
        ];
        $core = ['DHPP / DAPP', 'Rabies'];
    }

    if ($unhealthy && random_int(0, 1) === 0) {
        return ['status' => 'none', 'details' => [], 'records' => [], 'protocols' => $protocols];
    }

    $records = [];
    foreach ($core as $offset => $name) {
        $given = date('Y-m-d', strtotime($lastCheckup . ' -' . ($offset * 21) . ' days'));
        $records[] = [
            'vaccine' => $name,
            'dateGiven' => $given,
            'administered_date' => $given,
            'status' => 'Completed',
            'nextDue' => date('Y-m-d', strtotime($given . ' +365 days')),
        ];
    }

    return [
        'status' => count($records) >= 2 ? 'complete' : 'partial',
        'details' => $records,
        'records' => $records,
        'protocols' => $protocols,
    ];
}

function seed_health_notes(array $animal, bool $unhealthy): string
{
    $name = $animal['name'] ?: 'This animal';
    if ($unhealthy) {
        return "{$name} arrived thin and dehydrated. Started on fluids, deworming, and daily wound care. Recheck scheduled with City Vet.";
    }
    return "{$name} is eating well, bright and alert on kennel rounds. Core vaccines recorded. Cleared for socialization walks pending listing review.";
}

if (seeders_is_entry(__FILE__)) {
    seed_health($ctx);
}
