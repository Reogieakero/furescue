<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;

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
                a.barangay,
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
