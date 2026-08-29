<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: gestion_utilisateurs.php');
	exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$adminId = (int)($_SESSION['user_id'] ?? 0);

if (!$id || $id === $adminId) {
	header('Location: gestion_utilisateurs.php?succes=' . urlencode('Suppression refusée pour ce compte.'));
	exit;
}

$stmt = $mysqli->prepare("SELECT nom, role FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$utilisateur = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$utilisateur || $utilisateur['role'] === 'admin') {
	header('Location: gestion_utilisateurs.php?succes=' . urlencode('Ce compte ne peut pas être supprimé.'));
	exit;
}

$delete = $mysqli->prepare('DELETE FROM users WHERE id = ?');
$delete->bind_param('i', $id);
$supprime = $delete->execute();
$delete->close();

$message = $supprime
	? 'Le compte de ' . $utilisateur['nom'] . ' a été supprimé.'
	: 'La suppression du compte a échoué.';
header('Location: gestion_utilisateurs.php?succes=' . urlencode($message));
exit;
