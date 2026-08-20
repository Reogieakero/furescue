<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

use App\Database;

function splitStatements(string $sql): array
{
    $statements = [];
    $current = '';
    $inDollar = false;
    $n = strlen($sql);
    $i = 0;

    while ($i < $n) {
        if ($sql[$i] === '$') {
            $j = $i + 1;
            while ($j < $n && $sql[$j] !== '$') {
                $j++;
            }
            if ($j < $n) {
                $tag = substr($sql, $i, $j - $i + 1);
                $current .= $tag;
                $inDollar = !$inDollar;
                $i = $j + 1;
                continue;
            }
        }

        $ch = $sql[$i];
        if (!$inDollar && $ch === ';') {
            $stmt = trim($current);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $current = '';
            $i++;
            continue;
        }

        $current .= $ch;
        $i++;
    }

    if (trim($current) !== '') {
        $statements[] = trim($current);
    }

    return $statements;
}

try {
    $pdo = Database::connect();
} catch (\PDOException $e) {
    fwrite(STDERR, "Cannot connect to the database. Check .env and ensure the database exists.\n");
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$driver = Database::env('DB_DRIVER', 'pgsql');

if ($driver === 'mysql') {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS migrations_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} else {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS migrations_log (
            id SERIAL PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );
}

$migDir = __DIR__ . '/../migrations';
$files = glob($migDir . '/*.sql');
sort($files);

$appliedRows = $pdo->query("SELECT migration FROM migrations_log")->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($appliedRows);

$count = 0;
foreach ($files as $file) {
    $name = basename($file);

    if (isset($applied[$name])) {
        echo "skip  $name\n";
        continue;
    }

    $sql = (string) file_get_contents($file);
    $statements = splitStatements($sql);

    try {
        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
        }
        $pdo->prepare("INSERT INTO migrations_log (migration) VALUES (?)")->execute([$name]);
        echo "apply $name (" . count($statements) . " statements)\n";
        $count++;
    } catch (\PDOException $e) {
        fwrite(STDERR, "ERROR applying $name: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Done. Applied $count migration file(s). " . count($files) . " total migration file(s) present.\n";
