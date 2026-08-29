<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? e($pageTitle) . ' — Materna' : 'Materna'; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
</head>
<body<?php echo isset($active) && $active === 'accueil' ? ' class="page-accueil-body"' : (isset($active) && $active === 'sage_femme' ? ' class="sage-femme-body"' : ''); ?>>

<?php if (isset($active) && $active === 'accueil'): ?>
<!-- Background pour Accueil -->
<div class="accueil-bg-slider">
  <div class="accueil-bg-slide active" style="background-image: url('assets/img/accueil_slides/slide1.svg');"></div>
  <div class="accueil-bg-slide" style="background-image: url('assets/img/accueil_slides/slide2.svg');"></div>
  <div class="accueil-bg-slide" style="background-image: url('assets/img/accueil_slides/slide3.svg');"></div>
</div>
<div class="accueil-bg-overlay"></div>
<div class="page-accueil-wrapper">
<?php endif; ?>

<nav class="navbar navbar-materna no-print px-4 py-3">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
      <span class="font-display fs-4 fw-semibold">NatalGest</span>
      <span class="badge rounded-pill badge-app">suivi prénatal & postnatal</span>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <?php 
        // Afficher les boutons selon les permissions
        $role = $_SESSION['user_role'] ?? '';
        if ($role === 'receptionniste' || $role === 'admin'):
      ?>
        <a href="accueil.php" class="btn btn-sm <?php echo ($active ?? '') === 'accueil' ? 'btn-primary-materna' : 'btn-outline-secondary'; ?>">Accueil</a>
      <?php endif; ?>
      <?php if ($role === 'sage_femme' || $role === 'docteur' || $role === 'admin'): ?>
        <a href="sage_femme.php" class="btn btn-sm <?php echo ($active ?? '') === 'sage_femme' ? 'btn-primary-materna' : 'btn-outline-secondary'; ?>">Consultations</a>
      <?php endif; ?>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="logout.php" class="btn btn-sm btn-logout" title="Se déconnecter">🚪 Déconnexion</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-4">
