<?php

namespace App\Repositories;

use App\Database;
use App\Entity\RescueCase;
use PDO;

class CaseRepository
{
    private const COLUMNS = [
        'id', 'report_id', 'assigned_rescuer_id', 'assigned_by', 'status',
        'resolution_notes', 'resolution_photos', 'created_at', 'updated_at',
    ];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(string $id): ?RescueCase
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cases WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? RescueCase::fromRow($row) : null;
    }

    public function findByReportId(string $reportId): ?RescueCase
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cases WHERE report_id = ? LIMIT 1');
        $stmt->execute([$reportId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? RescueCase::fromRow($row) : null;
    }

    public function create(array $data): string
    {
        if (empty($data['id'])) {
            $data['id'] = Database::uuidV4();
        }
        foreach (array_keys($data) as $column) {
            $this->assertColumn($column);
        }
        $columns = array_keys($data);
        $colSql = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO cases ({$colSql}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return $data['id'];
    }

    public function update(string $id, array $data): bool
    {
        unset($data['id']);
        if (empty($data)) {
            return true;
        }
        foreach (array_keys($data) as $column) {
            $this->assertColumn($column);
        }
        $setSql = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE cases SET {$setSql} WHERE id = ?");
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    private function assertColumn(string $column): void
    {
        if (!in_array($column, self::COLUMNS, true)) {
            throw new \InvalidArgumentException("Column not allowed: {$column}");
        }
    }
}
