<?php

namespace App\Repositories;

use App\Database;
use PDO;


class Repository
{
    protected PDO $pdo;
    protected string $table;

    
    protected array $columns = [];

    public function __construct(PDO $pdo, string $table, array $columns = [])
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->columns = $columns;
    }

    public function find(string $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findBy(string $column, $value): ?array
    {
        $this->assertColumn($column);
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    
    public function findByComposite(array $columns, array $values): ?array
    {
        $clauses = [];
        foreach ($columns as $c) {
            $this->assertColumn($c);
            $clauses[] = "{$c} = ?";
        }
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $clauses) . " LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    
    public function all(array $filters = [], string $orderBy = 'created_at', string $direction = 'DESC'): array
    {
        [$where, $params] = $this->buildWhere($filters);
        $this->assertColumn($orderBy);
        $dir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} {$where} ORDER BY {$orderBy} {$dir}");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function paginate(int $page, int $perPage, array $filters = [], string $orderBy = 'created_at', string $direction = 'DESC'): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        [$where, $params] = $this->buildWhere($filters);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $this->assertColumn($orderBy);
        $dir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} {$where} ORDER BY {$orderBy} {$dir} LIMIT ? OFFSET ?");
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    
    public function create(array $data): string
    {
        if (empty($data['id'])) {
            $data['id'] = Database::uuidV4();
        }
        $cols = array_keys($data);
        foreach ($cols as $c) {
            $this->assertColumn($c);
        }
        $colSql = implode(', ', $cols);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} ({$colSql}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return $data['id'];
    }

    
    public function update(string $id, array $data): bool
    {
        unset($data['id']);
        if (empty($data)) {
            return true;
        }
        $cols = array_keys($data);
        foreach ($cols as $c) {
            $this->assertColumn($c);
        }
        $setSql = implode(', ', array_map(fn($c) => "{$c} = ?", $cols));
        $params = array_values($data);
        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET {$setSql} WHERE id = ?");
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function delete(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function count(array $filters = []): int
    {
        [$where, $params] = $this->buildWhere($filters);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    
    protected function buildWhere(array $filters): array
    {
        $clauses = [];
        $params = [];
        foreach ($filters as $col => $val) {
            $this->assertColumn($col);
            if (is_array($val)) {
                $ph = implode(',', array_fill(0, count($val), '?'));
                $clauses[] = "{$col} IN ({$ph})";
                foreach ($val as $v) {
                    $params[] = $v;
                }
            } else {
                $clauses[] = "{$col} = ?";
                $params[] = $val;
            }
        }
        $where = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
        return [$where, $params];
    }

    protected function assertColumn(string $column): void
    {
        if ($this->columns !== [] && !in_array($column, $this->columns, true)) {
            throw new \InvalidArgumentException("Column not allowed: {$column}");
        }
    }
}
