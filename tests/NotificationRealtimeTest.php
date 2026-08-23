<?php

namespace App\Tests;

use App\Services\NotificationService;
use PHPUnit\Framework\TestCase;

class NotificationRealtimeTest extends TestCase
{
    public function testDbNowReturnsDatabaseTimestamp(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn('2026-08-23 10:00:00');
        $pdo->method('query')->with('SELECT NOW()')->willReturn($stmt);

        $this->assertSame('2026-08-23 10:00:00', (new NotificationService($pdo))->dbNow());
    }

    public function testLatestSinceScopesToUserUnreadOnly(): void
    {
        $captured = [];
        $rows = [
            ['id' => 'n1', 'user_id' => 'u1', 'type' => 'admin_announcement', 'message' => 'Hi', 'created_at' => '2026-08-23 10:00:00'],
        ];
        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturnCallback(function ($sql) use (&$captured, $rows) {
            $stmt = $this->createMock(\PDOStatement::class);
            $stmt->method('execute')->willReturnCallback(function ($params) use (&$captured, $stmt) {
                $captured = $params;
                return true;
            });
            $stmt->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturnCallback(function () use ($sql, $rows) {
                // Guard the query shape the stream relies on.
                if (stripos($sql, 'is_read = FALSE') === false || stripos($sql, 'created_at >= ?') === false) {
                    return [];
                }
                if (!str_contains($sql, 'LIMIT 50')) {
                    return [];
                }
                return $rows;
            });
            return $stmt;
        });

        $result = (new NotificationService($pdo))->latestSince('u1', '2026-08-23 09:59:00');

        $this->assertSame(['u1', '2026-08-23 09:59:00'], $captured);
        $this->assertCount(1, $result);
        $this->assertSame('n1', $result[0]['id']);
    }

    public function testRecentBroadcastsCollapsesPerRecipientRows(): void
    {
        $rows = [
            ['message' => 'Adoption day!', 'type' => 'admin_announcement', 'recipients' => 12, 'created_at' => '2026-08-23 09:00:00'],
            ['message' => 'Vaccine drive', 'type' => 'admin_announcement', 'recipients' => 7, 'created_at' => '2026-08-22 09:00:00'],
        ];
        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturnCallback(function ($sql) use ($rows) {
            $stmt = $this->createMock(\PDOStatement::class);
            $stmt->method('execute')->willReturn(true);
            $stmt->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturnCallback(function () use ($sql, $rows) {
                return str_contains($sql, 'GROUP BY message, type') && str_contains($sql, "type = 'admin_announcement'")
                    ? $rows
                    : [];
            });
            return $stmt;
        });

        $result = (new NotificationService($pdo))->recentBroadcasts(20);

        $this->assertCount(2, $result);
        $this->assertSame(12, $result[0]['recipients']);
    }
}
