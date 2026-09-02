<?php

namespace App\Tests;

require_once __DIR__ . '/Support/SqliteTestDatabase.php';
require_once __DIR__ . '/Support/InteractsWithHttp.php';

use App\Controllers\AdoptionListingController;
use App\Tests\Support\InteractsWithHttp;
use App\Tests\Support\SqliteTestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

class AdoptionListingControllerTest extends TestCase
{
    use InteractsWithHttp;

    private PDO $pdo;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite driver is not available.');
        }
        SqliteTestDatabase::env();
        $this->pdo = SqliteTestDatabase::create();
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS animal_medical_records (
                id TEXT PRIMARY KEY,
                animal_id TEXT NOT NULL UNIQUE,
                vaccination_records TEXT,
                vaccination_details TEXT,
                weight_kg TEXT,
                temperature_c TEXT
            )"
        );
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS vitals_log (
                id TEXT PRIMARY KEY,
                animal_id TEXT NOT NULL,
                heart_rate_bpm INTEGER,
                recorded_at TEXT NOT NULL DEFAULT (datetime('now'))
            )"
        );
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS adoption_listings (
                id TEXT PRIMARY KEY,
                animal_id TEXT NOT NULL,
                posted_by TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending_review',
                reviewed_by TEXT,
                review_notes TEXT,
                reviewed_at TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )"
        );
        $this->seedUser('admin-1', 'admin');
        $this->seedUser('poster-1', 'admin');
    }

    public function testCreateRejectsIneligibleAnimal(): void
    {
        $this->seedAnimal('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeee1');

        $response = $this->createListing('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeee1');

        $this->assertSame(409, $response['status']);
        $this->assertSame('NOT_HEALTH_READY', $response['body']['error']['code']);
    }

    public function testCreateRejectsDuplicateLiveListing(): void
    {
        $animalId = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeee2';
        $this->seedEligibleAnimal($animalId);
        $first = $this->createListing($animalId);
        $this->assertTrue($first['body']['success']);

        $second = $this->createListing($animalId);

        $this->assertSame(409, $second['status']);
        $this->assertSame('LISTING_EXISTS', $second['body']['error']['code']);
        $count = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM adoption_listings WHERE animal_id = '{$animalId}' AND status IN ('pending_review','approved')"
        )->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function testRejectedListingMayBeRelisted(): void
    {
        $animalId = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeee3';
        $this->seedEligibleAnimal($animalId);
        $this->pdo->prepare(
            "INSERT INTO adoption_listings (id, animal_id, posted_by, status) VALUES (?, ?, ?, ?)"
        )->execute(['listing-old', $animalId, 'poster-1', 'rejected']);

        $response = $this->createListing($animalId);

        $this->assertTrue($response['body']['success']);
        $count = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM adoption_listings WHERE animal_id = '{$animalId}' AND status = 'pending_review'"
        )->fetchColumn();
        $this->assertSame(1, $count);
    }

    private function createListing(string $animalId): array
    {
        $request = $this->makeRequest(
            'POST',
            '/api/v1/adoption-listings',
            ['animal_id' => $animalId],
            [],
            [],
            ['id' => 'poster-1', 'role' => 'admin']
        );
        $request->permissions = ['adoptions.listings.create'];
        return $this->observe(fn () => (new AdoptionListingController($this->pdo))->create($request));
    }

    private function seedUser(string $id, string $role): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (id, full_name, email, role, account_status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $id, $id . '@test.local', $role, 'active']);
    }

    private function seedAnimal(string $id): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO animals (id, name, species, breed_type, sex, adoption_status, source, created_by)
             VALUES (?, ?, 'dog', 'aspin', 'male', 'not_listed', 'rescued_case', ?)"
        );
        $stmt->execute([$id, 'Test animal', 'poster-1']);
    }

    private function seedEligibleAnimal(string $id): void
    {
        $this->seedAnimal($id);
        $this->pdo->prepare(
            "INSERT INTO animal_medical_records (id, animal_id, vaccination_details, weight_kg)
             VALUES (?, ?, ?, ?)"
        )->execute([$id . '-med', $id, json_encode([['name' => 'Rabies']]), '12.5']);
    }
}
