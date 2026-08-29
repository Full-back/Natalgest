<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: gestion_utilisateurs.php");
    exit;
}

$id = (int) ($_POST['id'] ?? 0);


$stmt = $mysqli->prepare("SELECT role, statut, nom FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($u && $u['role'] !== 'admin') {
    $nouveauStatut = $u['statut'] === 'actif' ? 'desactive' : 'actif';

    $update = $mysqli->prepare("UPDATE users SET statut = ? WHERE id = ?");
    $update->bind_param("si", $nouveauStatut, $id);
    $update->execute();
    $update->close();

    $message = $nouveauStatut === 'actif'
        ? "Compte de {$u['nom']} réactivé."
        : "Compte de {$u['nom']} désactivé.";

    header("Location: gestion_utilisateurs.php?succes=" . urlencode($message));
    exit;
}

header("Location: gestion_utilisateurs.php");
exit;
