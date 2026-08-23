<?php

namespace App\Tests;

require_once __DIR__ . '/Support/SqliteTestDatabase.php';
require_once __DIR__ . '/Support/TestDedupService.php';
require_once __DIR__ . '/Support/InteractsWithHttp.php';

use App\Controllers\ReportController;
use App\Repositories\ReportRepository;
use App\Services\DedupService;
use App\Services\GeoService;
use App\Tests\Support\InteractsWithHttp;
use App\Tests\Support\SqliteTestDatabase;
use App\Tests\Support\TestDedupService;
use PDO;
use PHPUnit\Framework\TestCase;

class ReportControllerTest extends TestCase
{
    use InteractsWithHttp;

    private const MATI_LAT = 6.9554;
    private const MATI_LNG = 126.2131;

    private PDO $pdo;
    private ReportRepository $reports;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite driver is not available.');
        }
        SqliteTestDatabase::env();
        $this->pdo = SqliteTestDatabase::create();
        $this->reports = new ReportRepository($this->pdo);
    }

    public function testCreateRejectsCoordinatesOutsideMatiCity(): void
    {
        $response = $this->createReport('Injured dog', 14.5995, 120.9842);

        $this->assertFalse($response['body']['success']);
        $this->assertSame('OUT_OF_BOUNDS', $response['body']['error']['code']);
        $this->assertSame(0, $this->reportCount());
    }

    public function testCreatePersistsValidatedReportInsideMatiCity(): void
    {
        $response = $this->createReport('Injured dog near boulevard', self::MATI_LAT, self::MATI_LNG);

        $this->assertTrue($response['body']['success']);
        $report = $response['body']['data']['report'];
        $this->assertSame('validated', $report['validation_status']);
        $this->assertSame('pending_verification', $report['status']);
        $this->assertSame('resident-A', $report['resident_id']);
        $this->assertNull($report['duplicate_of_report_id']);
        $this->assertEqualsWithDelta(self::MATI_LAT, (float) $report['latitude'], 0.000001);
    }

    public function testCreateRejectsMissingDescription(): void
    {
        $request = $this->makeRequest('POST', '/api/v1/reports', [
            'latitude' => (string) self::MATI_LAT,
            'longitude' => (string) self::MATI_LNG,
        ], [], [], $this->resident('resident-A'));

        $response = $this->observe(fn () => $this->controller()->create($request));

        $this->assertFalse($response['body']['success']);
        $this->assertSame('VALIDATION_ERROR', $response['body']['error']['code']);
    }

    public function testCreateFlagsExactDuplicateByContentHash(): void
    {
        $this->seedReport('report-original', 'resident-B', 'Injured dog near boulevard', self::MATI_LAT, self::MATI_LNG);

        $response = $this->createReport('Injured dog near boulevard', self::MATI_LAT, self::MATI_LNG);

        $this->assertTrue($response['body']['success']);
        $report = $response['body']['data']['report'];
        $this->assertSame('flagged_duplicate', $report['validation_status']);
        $this->assertSame('report-original', $report['duplicate_of_report_id']);
    }

    public function testCreateFlagsDuplicateByProximityWithinRadius(): void
    {
        $this->seedReport('report-nearby', 'resident-B', 'Wounded cat', self::MATI_LAT, self::MATI_LNG);
        $nearbyLat = self::MATI_LAT + 0.00025;

        $response = $this->createReport('Different description entirely', $nearbyLat, self::MATI_LNG);

        $this->assertTrue($response['body']['success']);
        $report = $response['body']['data']['report'];
        $this->assertSame('flagged_duplicate', $report['validation_status']);
        $this->assertSame('report-nearby', $report['duplicate_of_report_id']);
    }

    public function testMineScopesReportsToAuthenticatedResident(): void
    {
        $this->seedReport('r1', 'resident-A', 'Dog', self::MATI_LAT, self::MATI_LNG);
        $this->seedReport('r2', 'resident-A', 'Cat', self::MATI_LAT + 0.001, self::MATI_LNG);
        $this->seedReport('r3', 'resident-B', 'Bird', self::MATI_LAT + 0.002, self::MATI_LNG);

        $response = $this->observe(fn () => $this->controller()->mine(
            $this->makeRequest('GET', '/api/v1/reports/me', [], [], [], $this->resident('resident-A'))
        ));

        $this->assertTrue($response['body']['success']);
        $this->assertSame(2, $response['body']['meta']['total']);
        foreach ($response['body']['data'] as $report) {
            $this->assertSame('resident-A', $report['resident_id']);
        }
    }

    public function testIndexScopesResidentsToTheirOwnReportsButNotAdmins(): void
    {
        $this->seedReport('r1', 'resident-A', 'Dog', self::MATI_LAT, self::MATI_LNG);
        $this->seedReport('r2', 'resident-B', 'Cat', self::MATI_LAT + 0.001, self::MATI_LNG);

        $asResident = $this->observe(fn () => $this->controller()->index(
            $this->makeRequest('GET', '/api/v1/reports', [], [], [], $this->resident('resident-A'))
        ));
        $asAdmin = $this->observe(fn () => $this->controller()->index(
            $this->makeRequest('GET', '/api/v1/reports', [], [], [], ['id' => 'admin-1', 'role' => 'admin'])
        ));

        $this->assertSame(1, $asResident['body']['meta']['total']);
        $this->assertSame('resident-A', $asResident['body']['data'][0]['resident_id']);
        $this->assertSame(2, $asAdmin['body']['meta']['total']);
    }

    public function testShowForbidsOtherResidentsButAllowsOwnerAndAdmin(): void
    {
        $this->seedReport('report-owned-by-b', 'resident-B', 'Dog', self::MATI_LAT, self::MATI_LNG);
        $show = fn (array $user) => $this->observe(fn () => $this->controller()->show(
            $this->makeRequest('GET', '/api/v1/reports/report-owned-by-b', [], [], [], $user)
        ));

        $otherResident = $show($this->resident('resident-A'));
        $owner = $show($this->resident('resident-B'));
        $admin = $show(['id' => 'admin-1', 'role' => 'admin']);

        $this->assertFalse($otherResident['body']['success']);
        $this->assertSame('FORBIDDEN', $otherResident['body']['error']['code']);
        $this->assertTrue($owner['body']['success']);
        $this->assertTrue($admin['body']['success']);
    }

    private function controller(): ReportController
    {
        return new ReportController($this->pdo, new TestDedupService($this->pdo), new GeoService());
    }

    private function resident(string $id): array
    {
        return ['id' => $id, 'role' => 'resident'];
    }

    private function createReport(string $description, float $lat, float $lng): array
    {
        $request = $this->makeRequest('POST', '/api/v1/reports', [
            'animal_description' => $description,
            'latitude' => (string) $lat,
            'longitude' => (string) $lng,
        ], [], [], $this->resident('resident-A'));

        return $this->observe(fn () => $this->controller()->create($request));
    }

    private function seedReport(string $id, string $residentId, string $description, float $lat, float $lng): void
    {
        $this->reports->create([
            'id' => $id,
            'resident_id' => $residentId,
            'animal_description' => $description,
            'latitude' => $lat,
            'longitude' => $lng,
            'content_hash' => DedupService::contentHash($description, $lat, $lng),
            'validation_status' => 'validated',
            'status' => 'pending_verification',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function reportCount(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM reports')->fetchColumn();
    }
}
