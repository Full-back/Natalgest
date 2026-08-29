<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers.php';
requireRole('admin');

$erreur = "";
$rolesValides = ['admin', 'receptionniste', 'docteur', 'sage_femme'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom          = trim(clean($_POST['nom'] ?? ''));
    $email        = trim(clean($_POST['email'] ?? ''));
    $role         = trim(clean($_POST['role'] ?? ''));
    $motDePasse   = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    // Debug : afficher ce qui est reçu
    error_log("DEBUG creer_utilisateur - Rôle reçu: |$role| (longueur: " . strlen($role) . ")");

    if ($nom === '' || $email === '' || $role === '' || $motDePasse === '') {
        $erreur = "Tous les champs sont obligatoires. (Rôle reçu: '$role')";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse email invalide.";
    } elseif (!in_array($role, $rolesValides, true)) {
        $erreur = "Rôle invalide. Les rôles acceptés sont: " . implode(", ", $rolesValides) . ". Rôle reçu: '$role'";
    } elseif (strlen($motDePasse) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($motDePasse !== $confirmation) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier l'unicité de l'email
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $erreur = "Un compte existe déjà avec cet email.";
        } else {
            $hash = password_hash($motDePasse, PASSWORD_BCRYPT);

            // Traitement de l'upload de photo
            $photoPath = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['photo']['tmp_name'];
                $name = $_FILES['photo']['name'];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $allowed, true)) {
                    $filename = 'user_' . time() . '_' . uniqid() . '.' . $ext;
                    $uploadDir = __DIR__ . '/../uploads/users/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                        $photoPath = 'uploads/users/' . $filename;
                    }
                }
            }

            $insert = $mysqli->prepare(
                "INSERT INTO users (nom, email, mot_de_passe, role, statut, photo) VALUES (?, ?, ?, ?, 'actif', ?)"
            );
            $insert->bind_param("sssss", $nom, $email, $hash, $role, $photoPath);

            if ($insert->execute()) {
                $nouvelUtilisateurId = $mysqli->insert_id;
                enregistrerAudit($pdo, 'users', $nouvelUtilisateurId, 'creation', "Compte utilisateur créé pour $nom");
                header("Location: gestion_utilisateurs.php?succes=" . urlencode("Compte créé avec succès pour $nom."));
                exit;
            } else {
                $erreur = "Erreur lors de la création du compte. Veuillez réessayer.";
            }
            $insert->close();
        }
        $stmt->close();
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex align-items-center mb-3">
        <a href="gestion_utilisateurs.php" class="btn btn-sm btn-outline-secondary me-3">&larr; Retour</a>
        <h3 class="fw-bold text-primary-custom mb-0">Créer un compte utilisateur</h3>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 560px;">
        <div class="card-body p-4">
            <?php if ($erreur): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" action="creer_utilisateur.php" enctype="multipart/form-data" novalidate>
                <div class="mb-3">
                    <label class="form-label">Nom complet</label>
                    <input type="text" name="nom" class="form-control" required
                           value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Adresse email</label>
                    <input type="email" name="email" class="form-control" pattern="[a-zA-Z0-9._%+-]+@gmail\.com" required
                    title="veuillez entrer une adresse Gmail valide,par exemple  example@gmail.com" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Photo de profil (Optionnel)</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <div class="form-text">Formats acceptés : JPG, PNG, WEBP.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rôle</label>
                    <select name="role" class="form-select" required>
                        <option value="receptionniste" selected>Réceptionniste</option>
                        <option value="sage_femme">Sage-femme</option>
                        <option value="docteur">Docteur</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="mot_de_passe" class="form-control" required minlength="8">
                    <div class="form-text">8 caractères minimum.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="confirmation" class="form-control" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary-custom w-100">Créer le compte</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
