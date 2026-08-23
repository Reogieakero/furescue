<?php

namespace App\Tests;

use App\Services\NotificationService;
use App\Controllers\NotificationController;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

class NotificationBroadcastTest extends TestCase
{
    /**
     * Fake PDO mirroring NotificationService's real queries:
     * role-scoped selects filter on the bound role param, the "all" select
     * filters active accounts, and notification inserts are counted.
     */
    private function makePdo(array $users = [], ?int &$insertCount = null): \PDO
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use ($users, &$insertCount) {
            $stmt = $this->createMock(\PDOStatement::class);

            if (stripos($sql, 'SELECT id FROM users WHERE role') !== false) {
                $rows = [];
                $stmt->method('execute')->willReturnCallback(function (?array $params) use (&$rows, $users) {
                    $role = $params[0] ?? null;
                    $active = array_values(array_filter(
                        $users,
                        fn(array $u): bool => $u['role'] === $role && $u['account_status'] === 'active'
                    ));
                    $rows = array_column($active, 'id');
                    return true;
                });
                $stmt->method('fetchAll')
                    ->with(\PDO::FETCH_COLUMN)
                    ->willReturnCallback(function () use (&$rows): array {
                        return $rows;
                    });

                return $stmt;
            }

            if (stripos($sql, 'SELECT id FROM users WHERE account_status') !== false) {
                $active = array_values(array_filter($users, fn(array $u): bool => $u['account_status'] === 'active'));
                $stmt->method('execute')->willReturn(true);
                $stmt->method('fetchAll')
                    ->with(\PDO::FETCH_COLUMN)
                    ->willReturn(array_column($active, 'id'));

                return $stmt;
            }

            if (stripos($sql, 'INSERT INTO notifications') !== false) {
                $stmt->method('execute')->willReturnCallback(function () use (&$insertCount) {
                    if ($insertCount !== null) {
                        $insertCount++;
                    }
                    return true;
                });

                return $stmt;
            }

            if (stripos($sql, 'SELECT COUNT') !== false) {
                $stmt->method('execute')->willReturn(true);
                $stmt->method('fetchColumn')->willReturn('0');

                return $stmt;
            }

            $stmt->method('execute')->willReturn(true);

            return $stmt;
        });

        return $pdo;
    }

    public function testBroadcastToRoleWritesOnePerActiveUser(): void
    {
        $users = [
            ['id' => '11111111-1111-1111-1111-111111111111', 'role' => 'admin', 'account_status' => 'active'],
            ['id' => '22222222-2222-2222-2222-222222222222', 'role' => 'admin', 'account_status' => 'active'],
            ['id' => '33333333-3333-3333-3333-333333333333', 'role' => 'admin', 'account_status' => 'suspended'],
        ];
        $insertCount = 0;
        $svc = new NotificationService($this->makePdo($users, $insertCount));

        $sent = $svc->broadcast('Hello admins', 'admin_announcement', ['role:admin']);

        $this->assertSame(2, $sent);
        $this->assertSame(2, $insertCount);
    }

    public function testBroadcastExcludesSender(): void
    {
        $users = [
            ['id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'role' => 'admin', 'account_status' => 'active'],
        ];
        $insertCount = 0;
        $svc = new NotificationService($this->makePdo($users, $insertCount));

        $sent = $svc->broadcast('Test', 'admin_announcement', ['role:admin'], 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');

        $this->assertSame(0, $sent);
        $this->assertSame(0, $insertCount);
    }

    public function testBroadcastReturnsZeroWhenNoActiveUsers(): void
    {
        $users = [
            ['id' => '77777777-7777-7777-7777-777777777777', 'role' => 'admin', 'account_status' => 'suspended'],
        ];
        $svc = new NotificationService($this->makePdo($users));

        $sent = $svc->broadcast('Nobody', 'admin_announcement', ['role:admin']);

        $this->assertSame(0, $sent);
    }

    public function testControllerBroadcastValidatesTargets(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $req = new Request();
        $req->body = ['message' => 'Test', 'targets' => ['bad-target!']];
        $req->user = ['id' => '11111111-1111-1111-1111-111111111111', 'role' => 'admin'];

        ob_start();
        (new NotificationController($pdo))->broadcast($req);
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        $this->assertFalse($payload['success']);
        $this->assertSame('VALIDATION_ERROR', $payload['error']['code']);
    }

    public function testControllerBroadcastDefaults(): void
    {
        $users = [
            ['id' => '99999999-9999-9999-9999-999999999999', 'role' => 'admin', 'account_status' => 'active'],
        ];
        $pdo = $this->makePdo($users);

        $req = new Request();
        $req->body = ['message' => 'Default target test'];
        $req->user = ['id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'role' => 'admin'];

        ob_start();
        (new NotificationController($pdo))->broadcast($req);
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        $this->assertTrue($payload['success']);
        $this->assertSame(1, $payload['data']['sent']);
        $this->assertSame('admin_announcement', $payload['data']['type']);
        $this->assertSame(['role:admin'], $payload['data']['targets']);
    }
}
