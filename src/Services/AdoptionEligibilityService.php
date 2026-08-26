<?php

namespace App\Services;

use PDO;

/**
 * Health-ready gate for listing an animal for adoption.
 *
 * Eligible only when BOTH are true:
 *  - ≥1 vaccination row in vaccination_records (or legacy vaccination_details)
 *  - ≥1 vital: weight_kg or temperature_c on the medical row, or a vitals_log heart rate
 *
 * A medical-row ribbon (vaccination_status / notes / checkup dates) is not enough.
 */
class AdoptionEligibilityService
{
    public const ERROR_CODE = 'NOT_HEALTH_READY';
    public const ERROR_MESSAGE = 'Animal must have a vaccination record and vitals before it can be listed for adoption.';

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function isEligible(string $animalId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT vaccination_records, vaccination_details, weight_kg, temperature_c
             FROM animal_medical_records
             WHERE animal_id = ?
             LIMIT 1'
        );
        $stmt->execute([$animalId]);
        $medical = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return $this->hasVaccination($medical) && $this->hasVital($animalId, $medical);
    }

    private function hasVaccination(?array $medical): bool
    {
        if ($medical === null) {
            return false;
        }

        return $this->jsonRowCount($medical['vaccination_records'] ?? null) > 0
            || $this->jsonRowCount($medical['vaccination_details'] ?? null) > 0;
    }

    private function hasVital(string $animalId, ?array $medical): bool
    {
        if ($medical !== null) {
            if ($this->isFilled($medical['weight_kg'] ?? null) || $this->isFilled($medical['temperature_c'] ?? null)) {
                return true;
            }
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM vitals_log
             WHERE animal_id = ? AND heart_rate_bpm IS NOT NULL
             LIMIT 1'
        );
        $stmt->execute([$animalId]);

        return (bool) $stmt->fetchColumn();
    }

    private function jsonRowCount(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : null;
        }
        if (!is_array($value)) {
            return 0;
        }

        return count($value);
    }

    private function isFilled(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
