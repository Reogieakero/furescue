<?php

namespace App\Controllers;

use App\Http\Request;
use App\Repositories\Repository;
use PDO;

abstract class AbstractController
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function repo(string $table, array $columns = []): Repository
    {
        return new Repository($this->pdo, $table, $columns);
    }

    protected function page(Request $req): int
    {
        return max(1, (int) ($req->query['page'] ?? 1));
    }

    protected function perPage(Request $req): int
    {
        return min(100, max(1, (int) ($req->query['per_page'] ?? 20)));
    }

    protected function meta(int $page, int $perPage, int $total): array
    {
        return ['page' => $page, 'per_page' => $perPage, 'total' => $total];
    }

    protected function notifyRole(string $role, string $type, string $message, ?string $relatedType = null, ?string $relatedId = null): void
    {
        $notif = new \App\Services\NotificationService($this->pdo);
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE role = ?");
        $stmt->execute([$role]);
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $uid) {
            $notif->notify($uid, $type, $message, $relatedType, $relatedId);
        }
    }
}
