<?php

namespace App\Repositories;

use App\Database;
use App\Entity\User;
use PDO;

class UserRepository
{
    private const COLUMNS = [
        'id', 'full_name', 'email', 'password_hash', 'auth_provider', 'google_id',
        'phone_number', 'address', 'role', 'account_status', 'profile_photo_url',
        'created_at', 'updated_at',
    ];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(string $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromRow($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findByColumn('email', $email);
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return $this->findByColumn('google_id', $googleId);
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
        $stmt = $this->pdo->prepare("INSERT INTO users ({$colSql}) VALUES ({$placeholders})");
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
        $stmt = $this->pdo->prepare("UPDATE users SET {$setSql} WHERE id = ?");
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function paginate(int $page, int $perPage, array $filters = [], string $orderBy = 'created_at', string $direction = 'DESC'): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        [$where, $params] = $this->buildWhere($filters);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $this->assertColumn($orderBy);
        $dir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare("SELECT * FROM users {$where} ORDER BY {$orderBy} {$dir} LIMIT " . (int) $perPage . " OFFSET " . (int) $offset);
        $stmt->execute($params);

        return [
            'items' => array_map(fn(array $row) => User::fromRow($row), $stmt->fetchAll(PDO::FETCH_ASSOC)),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    private function findByColumn(string $column, string $value): ?User
    {
        $this->assertColumn($column);
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromRow($row) : null;
    }

    private function buildWhere(array $filters): array
    {
        $clauses = [];
        $params = [];
        foreach ($filters as $column => $value) {
            $this->assertColumn($column);
            if (is_array($value)) {
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $clauses[] = "{$column} IN ({$placeholders})";
                foreach ($value as $v) {
                    $params[] = $v;
                }
            } else {
                $clauses[] = "{$column} = ?";
                $params[] = $value;
            }
        }
        $where = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
        return [$where, $params];
    }

    private function assertColumn(string $column): void
    {
        if (!in_array($column, self::COLUMNS, true)) {
            throw new \InvalidArgumentException("Column not allowed: {$column}");
        }
    }
}
