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

    // Reverse-geocodes a point into a specific, human-readable place name.
    // Prefers the most granular component (hamlet / neighbourhood / suburb / …)
    // over the broad barangay/city, so callers get the real spot, not just the area.
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

    // Finds a CA bundle so PHP's curl can verify TLS to the geocoder without
    // disabling peer verification. Override via GEO_CA_BUNDLE; otherwise probes
    // common dev locations (Git for Windows, Linux/macOS system stores).
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
