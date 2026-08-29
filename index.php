<?php
// Redirige vers la page de connexion par défaut
header('Location: login.php');
exit;
?>

<div class="row justify-content-center mt-5">
  <div class="col-md-8 text-center mb-4">
    <h1 class="font-display">Bienvenue sur Materna</h1>
    <p class="text-muted">Choisis ton espace de travail. (L'authentification par compte sera ajoutée par l'administrateur.)</p>
  </div>
</div>

<div class="row justify-content-center g-4">
  <div class="col-md-4">
    <a href="accueil.php" class="text-decoration-none">
      <div class="card-materna p-4 text-center h-100">
        <div class="fs-1 mb-2">📋</div>
        <h5 class="font-display" style="color: var(--ink);">Accueil / Secrétaire</h5>
        <p class="text-muted small mb-0">Enregistrer une patiente et l'envoyer à la sage-femme.</p>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="sage_femme.php" class="text-decoration-none">
      <div class="card-materna p-4 text-center h-100">
        <div class="fs-1 mb-2">🩺</div>
        <h5 class="font-display" style="color: var(--ink);">Sage-femme / Médecin</h5>
        <p class="text-muted small mb-0">Consulter la file d'attente et remplir les dossiers.</p>
      </div>
    </a>
  </div>
</div>

<?php require 'footer.php'; ?>
