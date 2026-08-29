<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

echo "<h3>Informations de session :</h3>";
echo "<pre>";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User Nom: " . $_SESSION['user_nom'] . "\n";
echo "User Role: " . $_SESSION['user_role'] . "\n";
echo "Role type: " . gettype($_SESSION['user_role']) . "\n";
echo "</pre>";

// Vérifier en base de données
$stmt = $pdo->prepare("SELECT id, nom, email, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

echo "<h3>Données en base de données :</h3>";
echo "<pre>";
echo "ID: " . $user['id'] . "\n";
echo "Nom: " . $user['nom'] . "\n";
echo "Email: " . $user['email'] . "\n";
echo "Role: " . $user['role'] . "\n";
echo "Role type: " . gettype($user['role']) . "\n";
echo "</pre>";

echo "<h3>Test requireRole :</h3>";
echo "<p>En test, on accepte ['sage_femme', 'docteur']</p>";
$roles = ['sage_femme', 'docteur'];
echo "<p>Rôles acceptés : " . implode(", ", $roles) . "</p>";
echo "<p>Ton rôle : " . $_SESSION['user_role'] . "</p>";
echo "<p>Vérification : " . (in_array($_SESSION['user_role'], $roles, true) ? "✓ OK" : "✗ ERREUR") . "</p>";

echo "<p><a href='logout.php'>Déconnexion</a></p>";
?>
