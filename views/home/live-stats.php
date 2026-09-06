<?php

declare(strict_types=1);

use App\Database;

if (!class_exists(Database::class)) {
    require dirname(__DIR__, 2) . '/vendor/autoload.php';
    Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
}

$liveStats = [
    'animals' => 0,
    'animals_adopted' => 0,
    'reports' => 0,
    'rescuers_active' => 0,
    'cases_active' => 0,
];

try {
    $pdo = Database::connect();
    $count = static function (string $sql) use ($pdo): int {
        return (int) $pdo->query($sql)->fetchColumn();
    };
    $liveStats['animals'] = $count("SELECT COUNT(*) FROM animals WHERE deleted_at IS NULL");
    $liveStats['animals_adopted'] = $count("SELECT COUNT(*) FROM animals WHERE deleted_at IS NULL AND adoption_status = 'adopted'");
    $liveStats['reports'] = $count("SELECT COUNT(*) FROM reports");
    $liveStats['rescuers_active'] = $count("SELECT COUNT(*) FROM users WHERE role = 'rescuer' AND account_status = 'active'");
    $liveStats['cases_active'] = $count("SELECT COUNT(*) FROM cases WHERE status IN ('open','assigned','in_progress')");
} catch (Throwable $e) {
    // Landing still renders if the database is unavailable.
}
