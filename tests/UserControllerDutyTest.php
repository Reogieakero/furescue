<?php

namespace App\Tests;

require_once __DIR__ . '/Support/SqliteTestDatabase.php';
require_once __DIR__ . '/Support/InteractsWithHttp.php';

use App\Controllers\UserController;
use App\Tests\Support\InteractsWithHttp;
use App\Tests\Support\SqliteTestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

class UserControllerDutyTest extends TestCase
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
        $this->seedUser('admin-1', 'admin');
        $this->seedUser('rescuer-1', 'rescuer');
        $this->seedUser('rescuer-2', 'rescuer');
    }

    public function testAdminCannotToggleRescuerDuty(): void
    {
        $response = $this->toggleDuty('rescuer-1', 'on_duty', [
            'id' => 'admin-1',
            'role' => 'admin',
        ], ['users.toggle_duty']);

        $this->assertSame(403, $response['status']);
        $this->assertFalse($response['body']['success']);
        $this->assertSame('FORBIDDEN', $response['body']['error']['code']);
    }

    public function testRescuerCannotToggleAnotherRescuerDuty(): void
    {
        $response = $this->toggleDuty('rescuer-2', 'on_duty', [
            'id' => 'rescuer-1',
            'role' => 'rescuer',
        ]);

        $this->assertSame(403, $response['status']);
        $this->assertSame('FORBIDDEN', $response['body']['error']['code']);
    }

    private function toggleDuty(string $targetId, string $status, array $user, array $permissions = []): array
    {
        $request = $this->makeRequest(
            'PATCH',
            "/api/v1/rescuers/{$targetId}/duty",
            ['status' => $status],
            [],
            [],
            $user
        );
        $request->params = ['id' => $targetId];
        $request->permissions = $permissions;
        return $this->observe(fn () => (new UserController($this->pdo))->toggleDuty($request));
    }

    private function seedUser(string $id, string $role): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (id, full_name, email, role, account_status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, ucfirst($role) . ' ' . $id, $id . '@test.local', $role, 'active']);
    }
}
