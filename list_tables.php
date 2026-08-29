<?php
require_once __DIR__ . '/config/database.php';

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    echo '<h3>Erreur lors de la lecture des tables</h3>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Tables dans la base</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <h3>Tables existantes dans la base `<?php echo htmlspecialchars($DB_NAME ?? ''); ?>`</h3>
    <?php if (empty($tables)): ?>
      <div class="alert alert-warning">Aucune table trouvée.</div>
    <?php else: ?>
      <ul class="list-group">
        <?php foreach ($tables as $t): ?>
          <li class="list-group-item"><?php echo htmlspecialchars($t); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <p class="mt-3 small text-muted">Supprimez ce fichier après vérification : <code>list_tables.php</code></p>
  </div>
</body>
</html>
