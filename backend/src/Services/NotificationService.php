<?php

namespace App\Services;

use App\Database;
use PDO;

class NotificationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function notify(
        string $userId,
        string $type,
        string $message,
        ?string $relatedType = null,
        ?string $relatedId = null
    ): string {
        $id = Database::uuidV4();
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications (id, user_id, type, message, related_type, related_id, is_read, created_at)
             VALUES (?, ?, ?, ?, ?, ?, FALSE, NOW())"
        );
        $stmt->execute([$id, $userId, $type, $message, $relatedType, $relatedId]);
        return $id;
    }

    public function unreadCount(string $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}
