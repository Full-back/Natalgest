<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$dernierAuditId = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM audit_log")->fetchColumn();
$_SESSION['dernier_audit_lu'] = $dernierAuditId;

http_response_code(204);