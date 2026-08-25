<?php

namespace App\Tests;

require_once __DIR__ . '/Support/SqliteTestDatabase.php';
require_once __DIR__ . '/Support/InteractsWithHttp.php';

use App\Controllers\ElearningController;
use App\Tests\Support\InteractsWithHttp;
use App\Tests\Support\SqliteTestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

class ElearningControllerTest extends TestCase
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
    }

    public function testProgressListsRowsOrderedByCompletedAt(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO elearning_progress (id, resident_id, module_id, status, completed_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute(['prog-open', 'resident-1', 'mod-a', 'in_progress', null]);
        $stmt->execute(['prog-done', 'resident-1', 'mod-b', 'completed', '2026-08-01 10:00:00']);
        $stmt->execute(['prog-other', 'resident-2', 'mod-a', 'completed', '2026-08-02 10:00:00']);

        $controller = new ElearningController($this->pdo);
        $req = $this->makeRequest(
            'GET',
            '/api/v1/elearning/progress',
            [],
            [],
            [],
            ['id' => 'resident-1', 'role' => 'resident']
        );
        $response = $this->observe(fn () => $controller->progress($req));

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['body']['success']);
        $rows = $response['body']['data']['progress'];
        $this->assertCount(2, $rows);
        $this->assertSame('prog-done', $rows[0]['id']);
        $this->assertSame('prog-open', $rows[1]['id']);
    }
}
