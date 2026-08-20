<?php
require __DIR__ . '/../vendor/autoload.php';
$dot = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dot->safeLoad();
$pdo = \App\Database::connect();

$table = $_GET['table'] ?? null;
$sql = trim($_GET['sql'] ?? '');
$message = '';

function tableList(PDO $pdo): array {
    return $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
}

$rows = null; $columns = null; $struct = null;
if ($sql !== '') {
    $upper = strtoupper(ltrim($sql));
    if (!preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)/', $upper)) {
        $message = 'Only SELECT/SHOW/DESCRIBE/EXPLAIN are allowed in this viewer.';
    } else {
        try {
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columns = $rows ? array_keys($rows[0]) : [];
            $message = count($rows) . ' row(s) returned.';
        } catch (Throwable $e) {
            $message = 'Error: ' . $e->getMessage();
        }
    }
} elseif ($table !== null) {
    try {
        $struct = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->query("SELECT * FROM `$table` ORDER BY 1 DESC LIMIT 100");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columns = $rows ? array_keys($rows[0]) : [];
    } catch (Throwable $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>FURescue DB Viewer</title>
<style>
  body { font-family: system-ui, sans-serif; margin: 0; background:   header { background:   header h1 { margin: 0; font-size: 18px; }
  .wrap { display: flex; min-height: calc(100vh - 48px); }
  .side { width: 260px; background:   .side a { display: block; padding: 6px 8px; color:   .side a:hover, .side a.active { background:   .main { flex: 1; padding: 16px; overflow: auto; }
  .msg { background:   .err { background:   table { border-collapse: collapse; width: 100%; background:   th, td { border: 1px solid   th { background:   tr:nth-child(even) td { background:   form { margin-bottom: 14px; }
  textarea { width: 100%; height: 60px; font-family: monospace; padding: 8px; box-sizing: border-box; }
  button { background:   h2 { margin-top: 0; }
</style>
</head>
<body>
<header><h1>FURescue — MySQL Viewer (local dev)</h1></header>
<div class="wrap">
  <nav class="side">
    <strong>Tables</strong>
    <?php foreach (tableList($pdo) as $t): ?>
      <a href="?table=<?= urlencode($t) ?>" class="<?= $t === $table ? 'active' : '' ?>"><?= htmlspecialchars($t) ?></a>
    <?php endforeach; ?>
  </nav>
  <main>
    <form method="get">
      <strong>Run a read-only query:</strong><br>
      <textarea name="sql" placeholder="SELECT * FROM reports LIMIT 10"><?= htmlspecialchars($sql) ?></textarea><br>
      <button type="submit">Execute</button>
    </form>
    <?php if ($message): ?><div class="msg <?= str_starts_with($message, 'Error') ? 'err' : '' ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($struct): ?>
      <h2>Structure: <?= htmlspecialchars($table) ?></h2>
      <table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>
      <?php foreach ($struct as $c): ?><tr><td><?= htmlspecialchars($c['Field']) ?></td><td><?= htmlspecialchars($c['Type']) ?></td><td><?= $c['Null'] ?></td><td><?= $c['Key'] ?></td><td><?= htmlspecialchars((string)$c['Default']) ?></td><td><?= htmlspecialchars($c['Extra']) ?></td></tr><?php endforeach; ?>
      </table><br>
    <?php endif; ?>
    <?php if ($rows !== null): ?>
      <h2><?= $table ? htmlspecialchars($table) : 'Query' ?> — <?= count($rows) ?> row(s)</h2>
      <?php if ($columns): ?>
        <table><tr><?php foreach ($columns as $c): ?><th><?= htmlspecialchars($c) ?></th><?php endforeach; ?></tr>
        <?php foreach ($rows as $r): ?><tr><?php foreach ($columns as $c): ?><td><?= htmlspecialchars((string)($r[$c] ?? '')) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        </table>
      <?php else: ?><p>No columns / empty result.</p><?php endif; ?>
    <?php elseif (!$sql): ?>
      <p>Select a table on the left, or run a query above.</p>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
