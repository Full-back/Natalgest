<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$erreur = "";
$rolesValides = ['admin', 'receptionniste', 'docteur', 'sage_femme'];

// Récupération du compte à modifier
$stmt = $mysqli->prepare("SELECT id, nom, email, role, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$utilisateur = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$utilisateur) {
    header("Location: gestion_utilisateurs.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom   = clean($_POST['nom'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $role  = clean($_POST['role'] ?? '');
    $nouveauMotDePasse = $_POST['mot_de_passe'] ?? '';

    if ($nom === '' || $email === '' || $role === '') {
        $erreur = "Le nom, l'email et le rôle sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse email invalide.";
    } elseif (!in_array($role, $rolesValides, true)) {
        $erreur = "Rôle invalide.";
    } elseif ($nouveauMotDePasse !== '' && strlen($nouveauMotDePasse) < 8) {
        $erreur = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } else {
        // Vérifier que l'email n'est pas déjà utilisé par un AUTRE compte
        $check = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $erreur = "Cet email est déjà utilisé par un autre compte.";
        } else {
            
            $photoPath = $utilisateur['photo'];
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

            if ($nouveauMotDePasse !== '') {
                $hash = password_hash($nouveauMotDePasse, PASSWORD_BCRYPT);
                $update = $mysqli->prepare(
                    "UPDATE users SET nom = ?, email = ?, role = ?, mot_de_passe = ?, photo = ? WHERE id = ?"
                );
                $update->bind_param("sssssi", $nom, $email, $role, $hash, $photoPath, $id);
            } else {
                $update = $mysqli->prepare(
                    "UPDATE users SET nom = ?, email = ?, role = ?, photo = ? WHERE id = ?"
                );
                $update->bind_param("ssssi", $nom, $email, $role, $photoPath, $id);
            }

            if ($update->execute()) {
                
                if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
                    $_SESSION['user_nom'] = $nom;
                    if ($photoPath) {
                        $_SESSION['user_photo'] = $photoPath;
                    }
                }
                header("Location: gestion_utilisateurs.php?succes=" . urlencode("Compte de $nom mis à jour."));
                exit;
            } else {
                $erreur = "Erreur lors de la mise à jour. Veuillez réessayer.";
            }
            $update->close();
        }
        $check->close();
    }
    // On garde les valeurs saisies affichées en cas d'erreur
    $utilisateur['nom'] = $nom;
    $utilisateur['email'] = $email;
    $utilisateur['role'] = $role;
    $utilisateur['photo'] = $photoPath ?? $utilisateur['photo'];
}

include __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex align-items-center mb-3">
        <a href="gestion_utilisateurs.php" class="btn btn-sm btn-outline-secondary me-3">&larr; Retour</a>
        <h3 class="fw-bold text-primary-custom mb-0">Modifier le compte</h3>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 560px;">
        <div class="card-body p-4">
            <?php if ($erreur): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" action="modifier_utilisateur.php?id=<?= (int)$id ?>" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="id" value="<?= (int)$id ?>">

                <?php if (!empty($utilisateur['photo'])): ?>
                    <div class="mb-3 text-center">
                        <img src="../<?= htmlspecialchars($utilisateur['photo']) ?>" alt="Photo de profil" class="rounded-circle mb-2" style="width:80px; height:80px; object-fit:cover; border:2px solid #7C3AED;">
                        <div class="small text-muted">Photo actuelle</div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Nom complet</label>
                    <input type="text" name="nom" class="form-control" required
                           value="<?= htmlspecialchars($utilisateur['nom']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Adresse email</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= htmlspecialchars($utilisateur['email']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Changer / Ajouter la photo de profil</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <div class="form-text">Formats acceptés : JPG, PNG, WEBP.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rôle</label>
                    <select name="role" class="form-select" required>
                        <?php foreach ($rolesValides as $r): ?>
                            <option value="<?= $r ?>" <?= $utilisateur['role'] === $r ? 'selected' : '' ?>>
                                <?= ucfirst($r) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="mot_de_passe" class="form-control" minlength="8">
                    <div class="form-text">Laisser vide pour conserver le mot de passe actuel.</div>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100">Enregistrer les modifications</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

