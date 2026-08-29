<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin'); // Seul l'Admin gère les comptes du personnel (matrice RBAC, section 3)

$result = $mysqli->query(
    "SELECT id, nom, email, role, statut, date_creation FROM users ORDER BY date_creation DESC"
);

include __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <a href="../admin_dashboard.php" class="btn btn-sm btn-outline-secondary me-3">&larr; Retour</a>
            <h3 class="fw-bold text-primary-custom mb-0">Gestion des utilisateurs</h3>
        </div>
        <a href="creer_utilisateur.php" class="btn btn-primary-custom">+ Nouveau compte</a>
    </div>

    <?php if (isset($_GET['succes'])): ?>
        <div class="alert alert-success small"><?= htmlspecialchars($_GET['succes']) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($u = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['nom']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td class="text-capitalize"><?= htmlspecialchars($u['role']) ?></td>
                            <td>
                                <?php if ($u['statut'] === 'actif'): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Désactivé</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars(date('d/m/Y', strtotime($u['date_creation']))) ?></td>
                            <td class="text-end">
                                <a href="modifier_utilisateur.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <form action="activer_desactiver.php" method="POST" class="d-inline"
                                          onsubmit="return confirm('Confirmer le changement de statut de ce compte ?');">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <?php if ($u['statut'] === 'actif'): ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Désactiver</button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success">Réactiver</button>
                                        <?php endif; ?>
                                    </form>
                                    <form action="supprimer.php" method="POST" class="d-inline"
                                          onsubmit="return confirm('Supprimer définitivement cet utilisateur ?');">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucun utilisateur enregistré.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
