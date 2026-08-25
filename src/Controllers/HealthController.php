<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\VaccinationEngine;

class HealthController extends AbstractController
{
    public function records(Request $req): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                a.id,
                a.name,
                a.species,
                a.breed_type,
                a.sex,
                a.age_estimate,
                a.birth_date,
                a.barangay,
                a.photo_urls,
                a.updated_at AS animal_updated_at,
                am.vaccination_status,
                am.vaccination_details,
                am.last_checkup_date,
                am.next_checkup_due,
                am.vaccination_expiry,
                am.condition,
                am.treatment_stage,
                am.weight_kg,
                am.temperature_c,
                am.vet_name,
                am.medical_history_notes,
                am.updated_at AS medical_updated_at,
                CASE WHEN am.animal_id IS NOT NULL THEN 1 ELSE 0 END AS has_medical_record,
                fs.health_status,
                v.heart_rate_bpm AS last_heart_rate
            FROM animals a
            LEFT JOIN animal_medical_records am ON am.animal_id = a.id
            LEFT JOIN (
                SELECT fs1.animal_id, fs1.health_status
                FROM animal_field_status fs1
                INNER JOIN (
                    SELECT animal_id, MAX(logged_at) AS mx
                    FROM animal_field_status
                    GROUP BY animal_id
                ) fs2 ON fs2.animal_id = fs1.animal_id AND fs2.mx = fs1.logged_at
            ) fs ON fs.animal_id = a.id
            LEFT JOIN (
                SELECT v1.animal_id, v1.heart_rate_bpm
                FROM vitals_log v1
                INNER JOIN (
                    SELECT animal_id, MAX(recorded_at) AS mx
                    FROM vitals_log
                    GROUP BY animal_id
                ) v2 ON v2.animal_id = v1.animal_id AND v2.mx = v1.recorded_at
            ) v ON v.animal_id = a.id
            ORDER BY a.created_at DESC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = array_map(function ($r) {
            $healthStatus = $r['health_status'] ?? 'healthy';
            $condition = $r['condition'];
            if ($condition === null) {
                $condition = $healthStatus === 'not_healthy' ? 'Unknown' : 'Healthy';
            }
            $treatmentStage = $r['treatment_stage'] ?? 'none';

            $vaccinationDetails = $r['vaccination_details'];
            if (is_string($vaccinationDetails) && $vaccinationDetails !== '') {
                $decoded = json_decode($vaccinationDetails, true);
                $vaccinationDetails = is_array($decoded) ? $decoded : [];
            } elseif ($vaccinationDetails === null) {
                $vaccinationDetails = [];
            }

            return [
                'id' => $r['id'],
                'animalId' => $r['id'],
                'animalName' => $r['name'] ?? 'Unnamed',
                'species' => $r['species'],
                'breedType' => $r['breed_type'],
                'sex' => $r['sex'],
                'ageEstimate' => $r['age_estimate'],
                'barangay' => $r['barangay'],
                'photo_urls' => $r['photo_urls'],
                'vaccinationStatus' => $r['vaccination_status'] ?? 'none',
                'vaccinationDetails' => $vaccinationDetails,
                'vaccinationExpiry' => $r['vaccination_expiry'],
                'lastCheckupDate' => $r['last_checkup_date'],
                'nextCheckupDue' => $r['next_checkup_due'],
                'healthStatus' => $healthStatus,
                'condition' => $condition,
                'treatmentStage' => $treatmentStage,
                'heartRateBpm' => $r['last_heart_rate'] !== null ? (int) $r['last_heart_rate'] : null,
                'weightKg' => $r['weight_kg'] !== null ? (float) $r['weight_kg'] : null,
                'temperatureC' => $r['temperature_c'] !== null ? (float) $r['temperature_c'] : null,
                'vetName' => $r['vet_name'],
                'notes' => $r['medical_history_notes'],
                'hasMedicalRecord' => !empty($r['has_medical_record']),
                'updatedAt' => $r['medical_updated_at'] ?? $r['animal_updated_at'],
            ];
        }, $rows);

        Response::success(['records' => $records]);
    }

    public function record(Request $req): void
    {
        $id = $req->params['id'] ?? '';
        $animal = $this->repo('animals')->find($id);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }

        $fsStmt = $this->pdo->prepare(
            "SELECT health_status FROM animal_field_status
             WHERE animal_id = ? ORDER BY logged_at DESC LIMIT 1"
        );
        $fsStmt->execute([$id]);
        $fsRow = $fsStmt->fetch(\PDO::FETCH_ASSOC);
        $healthStatus = $fsRow['health_status'] ?? 'healthy';

        $medical = $this->repo('animal_medical_records')->findBy('animal_id', $id) ?: [];

        $vitalsRows = $this->repo('vitals_log')->all(['animal_id' => $id], 'recorded_at', 'DESC');
        $latestVital = $vitalsRows[0] ?? null;

        $photoUrl = null;
        if (!empty($animal['photo_urls'])) {
            $urls = $animal['photo_urls'];
            if (is_string($urls)) {
                $dec = json_decode($urls, true);
                $urls = is_array($dec) ? $dec : [];
            }
            if (is_array($urls) && count($urls) > 0) {
                $photoUrl = $urls[0];
            }
        }

        $condition = $medical['condition'] ?? null;
        if ($condition === null) {
            $condition = $healthStatus === 'not_healthy' ? 'Unknown' : 'Healthy';
        }

        $vaccinationStatus = $medical['vaccination_status'] ?? 'none';
        $vaccinationDetails = $medical['vaccination_details'] ?? null;
        if (is_string($vaccinationDetails) && $vaccinationDetails !== '') {
            $dec = json_decode($vaccinationDetails, true);
            $vaccinationDetails = is_array($dec) ? $dec : [];
        } elseif ($vaccinationDetails === null) {
            $vaccinationDetails = [];
        }

        // Prefer structured vaccination_records (admin-entered type/date/next-due/status);
        // fall back to legacy vaccination_details. No engine-based evaluation.
        $rawRecords = $medical['vaccination_records'] ?? [];
        if (is_string($rawRecords) && $rawRecords !== '') {
            $rawRecords = json_decode($rawRecords, true) ?: [];
        }
        if (!is_array($rawRecords)) {
            $rawRecords = [];
        }
        if (empty($rawRecords) && !empty($vaccinationDetails)) {
            $rawRecords = array_map(function ($v) {
                return [
                    'vaccine' => $v['vaccine'] ?? 'Vaccine',
                    'administered_date' => $v['dateGiven'] ?? ($v['date'] ?? null),
                    'next_due' => $v['nextDue'] ?? null,
                    'status' => $v['status'] ?? null,
                    'dose_number' => $v['doseNumber'] ?? null,
                    'manufacturer' => $v['manufacturer'] ?? null,
                    'product_name' => $v['productName'] ?? null,
                    'batch_number' => $v['batchNumber'] ?? null,
                    'route' => $v['route'] ?? null,
                    'notes' => $v['notes'] ?? null,
                ];
            }, $vaccinationDetails);
        }

        $vaccinations = array_map(function ($r) {
            $status = $r['status'] ?? null;
            if (!in_array($status, ['none', 'partial', 'complete', 'Completed', 'Pending', 'Overdue'], true)) {
                $status = $status ?: 'Completed';
            }
            return [
                'vaccine' => $r['vaccine'] ?? 'Vaccine',
                'dateGiven' => $r['administered_date'] ?? $r['dateGiven'] ?? null,
                'nextDue' => $r['next_due'] ?? $r['nextDue'] ?? null,
                'dueWindow' => null,
                'status' => $status,
                'doseNumber' => $r['dose_number'] ?? null,
                'flags' => [],
                'seriesComplete' => null,
                'minimumAgeWeeks' => null,
                'manufacturer' => $r['manufacturer'] ?? null,
                'productName' => $r['product_name'] ?? null,
                'batchNumber' => $r['batchNumber'] ?? null,
                'route' => $r['route'] ?? null,
                'notes' => $r['notes'] ?? null,
            ];
        }, $rawRecords);

        $today = new \DateTime();
        $reminders = [];
        $addReminder = function (string $title, $due, string $icon) use (&$reminders, $today) {
            if (!$due) {
                return;
            }
            $d = \DateTime::createFromFormat('Y-m-d', substr((string) $due, 0, 10));
            if (!$d) {
                return;
            }
            $days = (int) ceil(($d->getTimestamp() - $today->getTimestamp()) / 86400);
            $tone = $days < 0 ? 'red' : ($days <= 30 ? 'yellow' : 'blue');
            $reminders[] = [
                'title' => $title,
                'dueDate' => $d->format('M d, Y'),
                'days' => $days,
                'tone' => $tone,
                'icon' => $icon,
            ];
        };
        $addReminder('Next checkup', $medical['next_checkup_due'] ?? null, 'stethoscope');
        $addReminder('Vaccination expiry', $medical['vaccination_expiry'] ?? null, 'syringe');

        // Vaccination reminders come from the admin-set next-due date on each record.
        foreach ($vaccinations as $v) {
            $due = $v['nextDue'] ?? null;
            if ($due) {
                $vaccine = $v['vaccine'] ?? 'Vaccine';
                $addReminder("{$vaccine} vaccination due", $due, 'syringe');
            }
        }

        $history = [];
        if (!empty($medical['last_checkup_date'])) {
            $history[] = [
                'date' => $medical['last_checkup_date'],
                'doctor' => $medical['vet_name'] ?? 'Furescue Vet',
                'title' => 'Regular Check-up',
                'description' => 'General physical examination',
                'tone' => 'green',
            ];
        }
        foreach ($vaccinations as $v) {
            if (!empty($v['dateGiven'])) {
                $history[] = [
                    'date' => $v['dateGiven'],
                    'doctor' => $medical['vet_name'] ?? 'Furescue Vet',
                    'title' => $v['vaccine'] . ' Vaccination',
                    'description' => 'Vaccine administered',
                    'tone' => 'blue',
                ];
            }
        }
        if ($healthStatus === 'not_healthy') {
            $history[] = [
                'date' => $medical['updated_at'] ?? $animal['updated_at'],
                'doctor' => $medical['vet_name'] ?? 'Furescue Vet',
                'title' => 'Treatment',
                'description' => 'Marked not healthy — ' . ($condition ?? 'condition'),
                'tone' => 'red',
            ];
        }
        usort($history, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        $vitals = [];
        if (isset($medical['weight_kg']) && $medical['weight_kg'] !== null) {
            $vitals[] = ['label' => 'Weight', 'value' => (string) $medical['weight_kg'], 'unit' => 'kg'];
        }
        if (isset($medical['temperature_c']) && $medical['temperature_c'] !== null) {
            $vitals[] = ['label' => 'Body Temperature', 'value' => (string) $medical['temperature_c'], 'unit' => '°C'];
        }
        if ($latestVital && isset($latestVital['heart_rate_bpm']) && $latestVital['heart_rate_bpm'] !== null) {
            $vitals[] = ['label' => 'Heart Rate', 'value' => (string) $latestVital['heart_rate_bpm'], 'unit' => 'bpm'];
        }
        $vitalMeta = $latestVital ? ('Recorded on ' . substr((string) $latestVital['recorded_at'], 0, 10)) : null;

        $heartRateHistory = [];
        foreach (array_reverse($vitalsRows) as $vr) {
            if (isset($vr['heart_rate_bpm']) && $vr['heart_rate_bpm'] !== null) {
                $heartRateHistory[] = [
                    'date' => substr((string) ($vr['recorded_at'] ?? ''), 0, 10),
                    'value' => (int) $vr['heart_rate_bpm'],
                ];
            }
        }

        $docRows = $this->repo('animal_documents')->all(['animal_id' => $id], 'created_at', 'DESC');
        $documents = array_map(function ($d) {
            return [
                'id' => $d['id'],
                'name' => $d['name'],
                'type' => $d['doc_type'] ?? null,
                'fileUrl' => $d['file_url'] ?? null,
                'meta' => $d['meta'] ?? null,
            ];
        }, $docRows);

        $notesMeta = '';
        if (!empty($medical['vet_name'])) {
            $notesMeta .= 'by ' . $medical['vet_name'];
        }
        if (!empty($medical['updated_at'])) {
            $notesMeta = trim(($notesMeta !== '' ? $notesMeta . ' · ' : '') . 'updated ' . substr((string) $medical['updated_at'], 0, 10));
        }

        $record = [
            'id' => $animal['id'],
            'hasMedicalRecord' => !empty($medical),
            'name' => $animal['name'] ?? 'Unnamed',
            'species' => $animal['species'] ?? null,
            'breedType' => $animal['breed_type'] ?? null,
            'sex' => $animal['sex'] ?? null,
            'ageEstimate' => $animal['age_estimate'] ?? null,
            'birthDate' => $animal['birth_date'] ?? null,
            'barangay' => $animal['barangay'] ?? null,
            'adoptionStatus' => $animal['adoption_status'] ?? null,
            'photoUrl' => $photoUrl,
            'overview' => [
                'healthStatus' => $healthStatus,
                'vaccinationStatus' => $vaccinationStatus,
                'deworming' => $medical['deworming_status'] ?? 'unknown',
                'neutered' => $medical['neutered'] ?? 'unknown',
                'notes' => $medical['medical_history_notes'] ?? null,
                'notesMeta' => $notesMeta,
            ],
            'history' => $history,
            'vaccinations' => $vaccinations,
            'reminders' => $reminders,
            'vitals' => $vitals,
            'vitalMeta' => $vitalMeta,
            'heartRateHistory' => $heartRateHistory,
            'documents' => $documents,
            'protocols' => VaccinationEngine::protocolsForSpecies($animal['species'] ?? ''),
            'ageWeeks' => null,
        ];

        Response::success(['record' => $record]);
    }

    public function activity(Request $req): void
    {
        $days = 400;
        $map = [];
        $today = new \DateTime();
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = (clone $today)->modify("-{$i} days")->format('Y-m-d');
            $map[$d] = ['date' => $d, 'checkups' => 0, 'treatments' => 0, 'vaccinations' => 0];
        }

        $this->fill($map,
            "SELECT DATE(last_checkup_date) AS day, COUNT(*) AS c
             FROM animal_medical_records WHERE last_checkup_date IS NOT NULL
             GROUP BY day",
            'checkups');

        $this->fill($map,
            "SELECT DATE(logged_at) AS day, COUNT(*) AS c
             FROM animal_field_status WHERE health_status = 'not_healthy'
             GROUP BY day",
            'treatments');

        $this->fill($map,
            "SELECT DATE(last_checkup_date) AS day, COUNT(*) AS c
             FROM animal_medical_records WHERE vaccination_status <> 'none' AND last_checkup_date IS NOT NULL
             GROUP BY day",
            'vaccinations');

        Response::success(['daily' => array_values($map)]);
    }

    private function fill(array &$map, string $sql, string $key): void
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $day = $row['day'];
            if (isset($map[$day])) {
                $map[$day][$key] += (int) $row['c'];
            }
        }
    }
}
