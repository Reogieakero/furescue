<?php

namespace App\Services;

use App\Database;
use PDO;


class GeoService
{
    private float $latMin;
    private float $latMax;
    private float $lngMin;
    private float $lngMax;

    public function __construct()
    {
        $this->latMin = (float) Database::env('MATI_LAT_MIN', 6.89);
        $this->latMax = (float) Database::env('MATI_LAT_MAX', 7.01);
        $this->lngMin = (float) Database::env('MATI_LNG_MIN', 126.13);
        $this->lngMax = (float) Database::env('MATI_LNG_MAX', 126.27);
    }

    public function inMatiBounds(float $lat, float $lng): bool
    {
        return $lat >= $this->latMin && $lat <= $this->latMax
            && $lng >= $this->lngMin && $lng <= $this->lngMax;
    }

    
    public function heatmapPoints(?string $status = null): array
    {
        $where = "WHERE validation_status = 'validated' AND status = 'verified'";
        $params = [];
        if ($status !== null) {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        $stmt = Database::connect()->prepare(
            "SELECT id, latitude, longitude, animal_description, status, created_at
             FROM reports {$where}
             ORDER BY created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
