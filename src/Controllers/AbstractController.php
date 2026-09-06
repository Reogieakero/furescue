<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
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

    protected function rejectBadQuery(Request $req, array $enums = []): bool
    {
        if (array_key_exists('page', $req->query)) {
            $page = $req->query['page'];
            if (!is_numeric($page) || (string) (int) $page !== trim((string) $page) || (int) $page < 1) {
                Response::error('VALIDATION_ERROR', 'page must be a positive integer', 400);
                return true;
            }
        }
        if (array_key_exists('per_page', $req->query)) {
            $perPage = $req->query['per_page'];
            if (!is_numeric($perPage) || (string) (int) $perPage !== trim((string) $perPage) || (int) $perPage < 1 || (int) $perPage > 100) {
                Response::error('VALIDATION_ERROR', 'per_page must be between 1 and 100', 400);
                return true;
            }
        }
        foreach ($enums as $key => $allowed) {
            if (!isset($req->query[$key]) || $req->query[$key] === '') {
                continue;
            }
            if (!in_array($req->query[$key], $allowed, true)) {
                Response::error('VALIDATION_ERROR', $key . ' must be one of: ' . implode(', ', $allowed), 400);
                return true;
            }
        }
        return false;
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
