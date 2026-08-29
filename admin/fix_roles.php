<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Seuls les admins peuvent utiliser ce script
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $new_role = trim($_POST['new_role'] ?? '');
    
    $rolesValides = ['admin', 'receptionniste', 'docteur', 'sage_femme'];
    
    if ($user_id <= 0) {
        $erreur = "ID utilisateur invalide";
    } elseif ($new_role === '' || !in_array($new_role, $rolesValides, true)) {
        $erreur = "Rôle invalide. Rôles acceptés: " . implode(", ", $rolesValides);
    } else {
        // Utiliser PDO directement
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $succes = "Rôle mis à jour avec succès!";
                
                // Vérifier la mise à jour
                $check = $pdo->prepare("SELECT id, nom, email, role FROM users WHERE id = ?");
                $check->execute([$user_id]);
                $user = $check->fetch();
            } else {
                $erreur = "Aucun utilisateur trouvé avec cet ID";
            }
        } catch (Exception $e) {
            $erreur = "Erreur: " . $e->getMessage();
        }
    }
}

// Récupérer tous les utilisateurs
$users = $pdo->query("SELECT id, nom, email, role FROM users ORDER BY nom")->fetchAll();
$rolesValides = ['admin', 'receptionniste', 'docteur', 'sage_femme'];

require 'header.php';
?>
<div class="container py-4">
    <h2>Mise à jour directe des rôles</h2>
    
    <?php if (isset($succes)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($succes) ?></div>
        <h4>Utilisateur mis à jour:</h4>
        <pre><?php print_r($user); ?></pre>
    <?php endif; ?>
    
    <?php if (isset($erreur)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5>Sélectionner un utilisateur et changer son rôle</h5>
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Utilisateur</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">-- Sélectionner un utilisateur --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= htmlspecialchars($u['nom']) ?> (<?= htmlspecialchars($u['email']) ?>) - Rôle actuel: <?= htmlspecialchars($u['role']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nouveau rôle</label>
                    <select name="new_role" class="form-select" required>
                        <option value="">-- Sélectionner un rôle --</option>
                        <?php foreach ($rolesValides as $r): ?>
                            <option value="<?= $r ?>"><?= ucfirst(str_replace('_', '-', $r)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary-custom">Mettre à jour le rôle</button>
                </div>
            </form>
        </div>
    </div>
    
    <h5>Liste de tous les utilisateurs</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nom']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><strong><?= htmlspecialchars($u['role'] ?? 'NULL') ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require 'footer.php'; ?>
