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
        $sql = "SELECT r.id, r.latitude, r.longitude, r.animal_description, r.status,
                       r.address_text, r.created_at, r.resident_id, r.validation_status,
                       u.full_name AS resident_name,
                       c.status AS case_status, c.id AS case_id
                FROM reports r
                LEFT JOIN users u ON u.id = r.resident_id
                LEFT JOIN cases c ON c.report_id = r.id
                WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL";
        $args = [];
        if ($status !== 'all') {
            $status = $status ?: 'verified';
            $sql .= " AND r.validation_status = 'validated' AND r.status = ?";
            $args[] = $status;
        }
        $sql .= " ORDER BY r.created_at DESC LIMIT 500";
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

                public function reverseGeocode(float $lat, float $lng): ?array
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18&lat="
            . rawurlencode((string) $lat) . "&lon=" . rawurlencode((string) $lng);
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_USERAGENT => "FurEscue/1.0 (admin geocoding)",
            CURLOPT_HTTPHEADER => ["Accept: application/json"],
        ];
        $ca = $this->caBundle();
        if ($ca !== null) {
            $opts[CURLOPT_CAINFO] = $ca;
        }
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false || $code !== 200) {
            return null;
        }
        $data = json_decode($resp, true);
        if (!is_array($data)) {
            return null;
        }

        $addr = $data["address"] ?? [];
        $priority = ["hamlet", "neighbourhood", "suburb", "quarter", "city_district", "village", "town", "city", "county"];
        $name = null;
        foreach ($priority as $key) {
            if (!empty($addr[$key])) {
                $name = $addr[$key];
                break;
            }
        }
        if ($name === null && !empty($data["name"])) {
            $name = $data["name"];
        }

        return [
            "name" => $name,
            "road" => $addr["road"] ?? null,
            "full" => $data["display_name"] ?? null,
        ];
    }

                private function caBundle(): ?string
    {
        $candidates = [
            Database::env("GEO_CA_BUNDLE", ""),
            "C:\\Program Files\\Git\\mingw64\\etc\\ssl\\certs\\ca-bundle.crt",
            "C:/Program Files/Git/mingw64/etc/ssl/certs/ca-bundle.crt",
            "/usr/ssl/certs/ca-bundle.crt",
            "/etc/ssl/certs/ca-certificates.crt",
        ];
        foreach ($candidates as $c) {
            if ($c !== "" && is_file($c)) {
                return $c;
            }
        }
        return null;
    }
}
