<?php

namespace App\Tests\Support;

use App\Database;
use App\Services\DedupService;
use PDO;

final class TestDedupService extends DedupService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->pdo = $pdo;
    }

    public function findDuplicate(string $contentHash, float $lat, float $lng): ?string
    {
        $radiusMeters = (int) Database::env('DEDUP_RADIUS_METERS', 50);
        $windowHours = (int) Database::env('DEDUP_TIME_WINDOW_HOURS', 24);
        $since = date('Y-m-d H:i:s', strtotime("-{$windowHours} hours"));

        $stmt = $this->pdo->prepare(
            "SELECT id, content_hash, latitude, longitude
             FROM reports
             WHERE created_at >= ?
               AND validation_status <> 'invalid'
               AND id NOT IN (
                   SELECT duplicate_of_report_id FROM reports
                   WHERE duplicate_of_report_id IS NOT NULL
               )
             ORDER BY created_at DESC"
        );
        $stmt->execute([$since]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['content_hash'] === $contentHash) {
                return (string) $row['id'];
            }
            $distanceMeters = 6371000 * acos(
                cos(deg2rad($lat)) * cos(deg2rad((float) $row['latitude']))
                * cos(deg2rad((float) $row['longitude']) - deg2rad($lng))
                + sin(deg2rad($lat)) * sin(deg2rad((float) $row['latitude']))
            );
            if ($distanceMeters <= $radiusMeters) {
                return (string) $row['id'];
            }
        }

        return null;
    }
}
