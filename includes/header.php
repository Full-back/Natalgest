<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>NatalGest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= defined('APP_BASE_URL') ? APP_BASE_URL : '' ?>/assets/css/style.css" rel="stylesheet">
    <script>
    window.addEventListener('pageshow', function (event) {
        if (event.persisted || (performance.navigation && performance.navigation.type === 2)) {
            window.location.reload();
        }
    });
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container">
        <?php $brandTarget = (($_SESSION['user_role'] ?? '') === 'admin') ? '/admin_dashboard.php' : '/accueil.php'; ?>
        <a class="navbar-brand fw-bold" href="<?= (defined('APP_BASE_URL') ? APP_BASE_URL : '') . $brandTarget ?>">NatalGest</a>
        <div class="d-flex align-items-center gap-3">
            <?php $userRole = $_SESSION['user_role'] ?? '';
                  $userNom = $_SESSION['user_nom'] ?? '';
                  $baseUrl = defined('APP_BASE_URL') ? APP_BASE_URL : '';
            ?>
            <?php if ($userRole === 'receptionniste'): ?>
                <a href="<?= $baseUrl ?>/accueil.php" class="btn btn-sm <?= ($active ?? '') === 'accueil' ? 'btn-primary-materna' : 'btn-outline-secondary'; ?>">Accueil</a>
            <?php endif; ?>
            <?php if (in_array($userRole, ['sage_femme', 'docteur'], true)): ?>
                <a href="<?= $baseUrl ?>/sage_femme.php" class="btn btn-sm <?= ($active ?? '') === 'sage_femme' ? 'btn-primary-materna' : 'btn-outline-secondary'; ?>">Sage-femme</a>
            <?php endif; ?>
            <div class="d-flex align-items-center">
                <span class="badge bg-light text-dark me-3 text-capitalize">
                    <?= htmlspecialchars($userNom ?: 'Utilisateur') ?>
                    <?php if ($userRole): ?> — <?= htmlspecialchars($userRole) ?><?php endif; ?>
                </span>
                <a href="<?= $baseUrl ?>/logout.php" class="btn btn-sm btn-outline-light">Déconnexion</a>
            </div>
        </div>
    </div>
</nav>
<main class="main">
    <div class="content container">
