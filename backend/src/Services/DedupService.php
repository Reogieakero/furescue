<?php

namespace App\Services;

use App\Database;
use App\Repositories\Repository;
use PDO;

class DedupService
{
    private PDO $pdo;
    private int $radiusMeters;
    private int $windowHours;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->radiusMeters = (int) Database::env('DEDUP_RADIUS_METERS', 50);
        $this->windowHours = (int) Database::env('DEDUP_TIME_WINDOW_HOURS', 24);
    }

    public static function contentHash(string $description, float $lat, float $lng, ?string $day = null): string
    {
        $day = $day ?? date('Y-m-d');
        $normalized = trim(preg_replace('/\s+/', ' ', strtolower($description)));
        $roundedLat = round($lat, 3);
        $roundedLng = round($lng, 3);
        return hash('sha256', $normalized . '|' . $roundedLat . '|' . $roundedLng . '|' . $day);
    }

    public function findDuplicate(string $contentHash, float $lat, float $lng): ?string
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$this->windowHours} hours"));

        $stmt = $this->pdo->prepare(
            "SELECT id, content_hash, latitude, longitude, created_at,
                    (6371000 * acos(
                        cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    )) AS distance_m
             FROM reports
             WHERE created_at >= ?
               AND validation_status <> 'invalid'
               AND id NOT IN (
                   SELECT duplicate_of_report_id FROM reports
                   WHERE duplicate_of_report_id IS NOT NULL
               )
             HAVING content_hash = ? OR distance_m <= ?
             ORDER BY created_at DESC
             LIMIT 1"
        );
        $stmt->execute([$lat, $lng, $lat, $since, $contentHash, $this->radiusMeters]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string) $row['id'] : null;
    }
}
