<?php
require_once __DIR__ . '/config/database.php';

try {
    $stmt = $pdo->query("SELECT DISTINCT role FROM users ORDER BY role");
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    echo '<h3>Erreur lors de la lecture des rôles</h3>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Rôles présents</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <h3>Rôles uniques dans la table <code>users</code></h3>
    <?php if (empty($roles)): ?>
      <div class="alert alert-warning">Aucun rôle trouvé.</div>
    <?php else: ?>
      <ul class="list-group">
        <?php foreach ($roles as $r): ?>
          <li class="list-group-item"><?php echo htmlspecialchars($r); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <p class="mt-3 small text-muted">Supprimez ce fichier après vérification : <code>list_roles.php</code></p>
  </div>
</body>
</html>
