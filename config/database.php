<?php


$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";          
$DB_NAME = "natalgest";

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($mysqli->connect_errno) {
    
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}

$mysqli->set_charset("utf8mb4");

define('APP_BASE_URL', '/natalgest');

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}

try {
    $colsUsers = $pdo->query("SHOW COLUMNS FROM users LIKE 'photo'")->fetch();
    if (!$colsUsers) {
        $pdo->exec("ALTER TABLE users ADD COLUMN photo VARCHAR(255) NULL");
    }
    $colsPatientes = $pdo->query("SHOW COLUMNS FROM patientes LIKE 'photo'")->fetch();
    if (!$colsPatientes) {
        $pdo->exec("ALTER TABLE patientes ADD COLUMN photo VARCHAR(255) NULL");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        table_cible VARCHAR(100) NOT NULL,
        enregistrement_id INT NULL,
        patiente_id INT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT NULL,
        utilisateur VARCHAR(255) NOT NULL,
        date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit_date (date_action),
        INDEX idx_audit_patiente (patiente_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    
}

