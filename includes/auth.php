<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Empêche la mise en cache par le navigateur (protection anti-retour après déconnexion)
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

// Durée d'inactivité avant déconnexion automatique (en secondes) 
define('SESSION_TIMEOUT', 15 * 60); 


function requireLogin(): void
{
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['user_id'])) {
        header("Location: " . appBaseUrl() . "/login.php");
        exit;
    }

    // Déconnexion automatique après inactivité 
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header("Location: " . appBaseUrl() . "/login.php?expired=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * @param string|array $rolesAutorises Un rôle ('admin') ou un tableau de rôles (['admin','docteur'])
 */
function requireRole($rolesAutorises): void
{
    requireLogin();

    $roles = is_array($rolesAutorises) ? $rolesAutorises : [$rolesAutorises];

    if (!in_array($_SESSION['user_role'], $roles, true)) {
        http_response_code(403);
        echo "<h2 style='font-family:sans-serif;color:#C00000;'>Accès refusé</h2>";
        echo "<p style='font-family:sans-serif;'>Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>";
        echo "<p style='font-family:sans-serif;'><a href='" . appBaseUrl() . "/accueil.php'>Retour à l'accueil</a></p>";
        exit;
    }
}


function appBaseUrl(): string
{
    if (defined('APP_BASE_URL')) {
        return rtrim(APP_BASE_URL, '/');
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = dirname($scriptName);
    $base = $base === '/' ? '' : rtrim($base, '/');
    define('APP_BASE_URL', $base);
    return $base;
}

function redirectToDashboard(string $role): void
{
    $base = appBaseUrl();

    switch ($role) {
        case 'admin':
        case 'administrateur':
            header("Location: {$base}/admin_dashboard.php");
            exit;
        case 'receptionniste':
            header("Location: {$base}/accueil.php");
            exit;
        case 'docteur':
            header("Location: {$base}/sage_femme.php");
            exit;
        case 'sage_femme':
        case 'sage-femme':
        case 'sagefemme':
            header("Location: {$base}/sage_femme.php");
            exit;
        default:
            header("Location: {$base}/accueil.php");
            exit;
    }
}


function clean(string $value): string
{    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}
