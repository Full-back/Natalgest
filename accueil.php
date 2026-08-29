<?php
require_once __DIR__ . '/includes/auth.php';
requireRole('receptionniste');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers.php';

// ---- Traitement de l'enregistrement (formulaire POST) ---------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enregistrer') {
  $nom = trim($_POST['nom'] ?? '');
  $prenom = trim($_POST['prenom'] ?? '');
  $dateNaissance = $_POST['date_naissance'] ?: null;
  $telephone = trim($_POST['telephone'] ?? '');
  $typeVisite = ($_POST['type_visite'] === 'postnatal') ? 'postnatal' : 'prenatal';
  $motif = trim($_POST['motif'] ?? '');
  $patienteId = intval($_POST['patiente_id'] ?? 0);

  if ($nom !== '' && $prenom !== '') {

    // Photo upload patiente
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
      $tmpName = $_FILES['photo']['tmp_name'];
      $name = $_FILES['photo']['name'];
      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
      if (in_array($ext, $allowed, true)) {
        $filename = 'patiente_' . time() . '_' . uniqid() . '.' . $ext;
        $uploadDir = __DIR__ . '/uploads/patientes/';
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0755, true);
        }
        if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
          $photoPath = 'uploads/patientes/' . $filename;
        }
      }
    }

    if ($patienteId <= 0) {
      // Nouvelle patiente : on la crée
      $stmt = $pdo->prepare("INSERT INTO patientes (nom, prenom, date_naissance, telephone, photo) VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$nom, $prenom, $dateNaissance, $telephone, $photoPath]);
      $patienteId = $pdo->lastInsertId();
      enregistrerAudit($pdo, 'patientes', $patienteId, 'creation', "Nouvelle patiente enregistrée : $prenom $nom", $patienteId);
    } else {
      // Patiente existante : mettre à jour la photo si fournie
      if ($photoPath) {
        $stmt = $pdo->prepare("UPDATE patientes SET photo = ? WHERE id = ?");
        $stmt->execute([$photoPath, $patienteId]);
      }
    }
    // Nouveau passage envoyé directement à la page de la sage-femme
    $stmt = $pdo->prepare("INSERT INTO visites (patiente_id, type_visite, motif, statut) VALUES (?, ?, ?, 'attente')");
    $stmt->execute([$patienteId, $typeVisite, $motif]);

    header("Location: accueil.php?success=1");
    exit;
  }
}

// ---- Recherche d'une patiente déjà connue ---------------------------------
$q = trim($_GET['q'] ?? '');
$resultats = [];
if ($q !== '') {
  $stmt = $pdo->prepare("
        SELECT p.*, COUNT(v.id) AS nb_visites
        FROM patientes p
        LEFT JOIN visites v ON v.patiente_id = p.id
        WHERE p.nom LIKE ? OR p.prenom LIKE ? OR p.telephone LIKE ?
        GROUP BY p.id
        ORDER BY p.nom ASC
        LIMIT 10
    ");
  $like = "%$q%";
  $stmt->execute([$like, $like, $like]);
  $resultats = $stmt->fetchAll();
}

// ---- Passages du jour -------------------------------------------------------
$stmt = $pdo->query("
    SELECT v.*, p.nom, p.prenom, p.telephone, p.photo
    FROM visites v
    JOIN patientes p ON p.id = v.patiente_id
    WHERE DATE(v.heure_enregistrement) = CURDATE()
    ORDER BY v.heure_enregistrement DESC
");
$passagesDuJour = $stmt->fetchAll();

$pageTitle = "Accueil";
$active = "accueil";
require 'header.php';
?>

<div class="row g-4">
  <!-- Formulaire d'enregistrement -->
  <div class="col-lg-5">
    <div class="card-materna-hero p-4">
      <h5 class="font-display fs-4 mb-1">Enregistrer une patiente</h5>
      <p class="text-muted small mb-4">Le dossier est envoyé automatiquement à la page de passage de la sage-femme dès validation.</p>

      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success py-2 small">Dossier créé et transmis à la page de passage.</div>
      <?php endif; ?>

      <!-- Recherche d'une patiente déjà connue -->
      <div id="bloc-recherche" class="mb-4">
        <label class="form-label small text-muted">Rechercher une patiente déjà connue</label>
        <form method="get" class="d-flex gap-2">
          <input type="text" name="q" value="<?php echo e($q); ?>" class="form-control form-control-sm" placeholder="Nom, prénom ou téléphone...">
          <button class="btn btn-sm btn-outline-secondary">🔍</button>
        </form>

        <?php if ($q !== ''): ?>
          <div class="mt-2">
            <?php if (empty($resultats)): ?>
              <p class="small text-muted">Aucune patiente trouvée pour « <?php echo e($q); ?> ». Complète le formulaire ci-dessous pour créer un nouveau dossier.</p>
              <?php else: foreach ($resultats as $r): ?>
                <div class="patiente-item p-2 mb-1 d-flex justify-content-between align-items-center" style="cursor:pointer"
                  onclick="selectionnerPatiente(<?php echo (int)$r['id']; ?>, '<?php echo e(addslashes($r['nom'])); ?>', '<?php echo e(addslashes($r['prenom'])); ?>', '<?php echo e($r['date_naissance']); ?>', '<?php echo e(addslashes($r['telephone'])); ?>')">
                  <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($r['photo'])): ?>
                      <img src="<?php echo e($r['photo']); ?>" class="rounded-circle" style="width:32px; height:32px; object-fit:cover;">
                    <?php endif; ?>
                    <div>
                      <div class="small fw-medium"><?php echo e($r['prenom'] . ' ' . $r['nom']); ?></div>
                      <div class="small text-muted"><?php echo e($r['telephone'] ?: '—'); ?> · <?php echo (int)$r['nb_visites']; ?> passage(s) enregistré(s)</div>
                    </div>
                  </div>
                  <span class="small" style="color: var(--primary);">Sélectionner</span>
                </div>
            <?php endforeach;
            endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <div id="badge-existante" class="alert py-2 small d-none justify-content-between align-items-center" style="background-color: var(--sage-soft); color: var(--sage);">
        <span>✅ Patiente existante — identité pré-remplie.</span>
        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-underline" style="color: var(--sage);" onclick="nouvellePatiente()">Nouvelle patiente</button>
      </div>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="enregistrer">
        <input type="hidden" name="patiente_id" id="patiente_id" value="0">

        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label small text-muted">Nom</label>
            <input type="text" name="nom" id="nom" class="form-control form-control-sm" placeholder="Mabiala" required>
          </div>
          <div class="col-6 mb-3">
            <label class="form-label small text-muted">Prénom</label>
            <input type="text" name="prenom" id="prenom" class="form-control form-control-sm" placeholder="Christelle" required>
          </div>
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label small text-muted">Date de naissance</label>
            <input type="date" name="date_naissance" id="date_naissance" class="form-control form-control-sm">
          </div>
          <div class="col-6 mb-3">
            <label class="form-label small text-muted">Téléphone</label>
            <input type="text" name="telephone" id="telephone" class="form-control form-control-sm" placeholder="06 500 00 00">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small text-muted">Photo de la patiente (Optionnel)</label>
          <input type="file" name="photo" id="photoInput" class="form-control form-control-sm" accept="image/*" onchange="previewPhoto(this)">
          <div id="photoPreviewContainer" class="mt-2 d-none align-items-center gap-2">
            <img id="photoPreview" src="" class="rounded-circle shadow-sm" style="width:50px; height:50px; object-fit:cover; border:2px solid var(--primary);">
            <span class="small text-muted">Aperçu de la photo sélectionnée</span>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small text-muted">Type de visite</label>
          <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="type_visite" id="type_prenatal" value="prenatal" checked>
            <label class="btn btn-outline-secondary btn-sm" for="type_prenatal">Prénatale</label>
            <input type="radio" class="btn-check" name="type_visite" id="type_postnatal" value="postnatal">
            <label class="btn btn-outline-secondary btn-sm" for="type_postnatal">Postnatale</label>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small text-muted">Motif de la visite</label>
          <textarea name="motif" class="form-control form-control-sm" rows="2" placeholder="Ex: Consultation de suivi - 6ème mois"></textarea>
        </div>

        <button type="submit" class="btn btn-primary-materna btn-sm w-100">➜ Enregistrer et envoyer à la sage-femme</button>
      </form>
    </div>
  </div>

  <!-- Passages du jour -->
  <div class="col-lg-7">
    <div class="card-materna-hero p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="font-display fs-4 mb-0">Passages du jour</h5>
        <span class="badge rounded-pill text-bg-light"><?php echo count($passagesDuJour); ?> patiente(s)</span>
      </div>

      <?php if (empty($passagesDuJour)): ?>
        <p class="text-muted small">Aucune patiente enregistrée pour l'instant aujourd'hui.</p>
        <?php else: foreach ($passagesDuJour as $p):
          $b = badgeStatut($p['statut']);
          $initiales = mb_substr($p['prenom'], 0, 1) . mb_substr($p['nom'], 0, 1);
        ?>
          <div class="d-flex justify-content-between align-items-center p-3 mb-2" style="border:1px solid var(--border); border-radius:0.75rem; background: rgba(255,255,255,0.7);">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar-initiales"><?php echo e($initiales); ?></div>
              <div>
                <div class="small fw-medium"><?php echo e($p['prenom'] . ' ' . $p['nom']); ?></div>
                <div class="small text-muted">
                  #<?php echo (int)$p['id']; ?> · <?php echo date('H:i', strtotime($p['heure_enregistrement'])); ?> ·
                  <?php echo $p['type_visite'] === 'prenatal' ? 'Prénatale' : 'Postnatale'; ?>
                </div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-3">
              <?php echo afficherParcours($p['statut'], true); ?>
              <span class="badge rounded-pill <?php echo $b['class']; ?>"><?php echo $b['label']; ?></span>
            </div>
          </div>
      <?php endforeach;
      endif; ?>
    </div>
  </div>
</div>

<script>
  function selectionnerPatiente(id, nom, prenom, dob, tel) {
    document.getElementById('patiente_id').value = id;
    document.getElementById('nom').value = nom;
    document.getElementById('prenom').value = prenom;
    document.getElementById('date_naissance').value = dob;
    document.getElementById('telephone').value = tel;
    ['nom', 'prenom', 'date_naissance', 'telephone'].forEach(id => document.getElementById(id).readOnly = true);
    document.getElementById('bloc-recherche').classList.add('d-none');
    document.getElementById('badge-existante').classList.remove('d-none');
    document.getElementById('badge-existante').classList.add('d-flex');
  }

  function nouvellePatiente() {
    document.getElementById('patiente_id').value = 0;
    ['nom', 'prenom', 'date_naissance', 'telephone'].forEach(id => {
      document.getElementById(id).value = '';
      document.getElementById(id).readOnly = false;
    });
    document.getElementById('bloc-recherche').classList.remove('d-none');
    document.getElementById('badge-existante').classList.add('d-none');
    document.getElementById('badge-existante').classList.remove('d-flex');
    const container = document.getElementById('photoPreviewContainer');
    if (container) container.classList.add('d-none');
  }

  function previewPhoto(input) {
    const container = document.getElementById('photoPreviewContainer');
    const img = document.getElementById('photoPreview');
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        img.src = e.target.result;
        container.classList.remove('d-none');
        container.classList.add('d-flex');
      }
      reader.readAsDataURL(input.files[0]);
    } else {
      container.classList.add('d-none');
      container.classList.remove('d-flex');
    }
  }
</script>

<?php require 'footer.php'; ?>