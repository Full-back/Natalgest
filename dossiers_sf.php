<?php
require_once __DIR__ . '/includes/auth.php';
requireRole(['sage_femme', 'docteur']);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers.php';

// ---- modification des infos d'une patiente ---------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_patiente') {
    $id = intval($_POST['patiente_id']);
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $dateNaissance = $_POST['date_naissance'] ?: null;
    $telephone = trim($_POST['telephone'] ?? '');

    if ($nom !== '' && $prenom !== '' && $id > 0) {
        $stmtAvant = $pdo->prepare("SELECT * FROM patientes WHERE id = ?");
        $stmtAvant->execute([$id]);
        $avant = $stmtAvant->fetch();

        // Photo
        $photoPath = $avant['photo'] ?? null;
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

        $stmt = $pdo->prepare("UPDATE patientes SET nom = ?, prenom = ?, date_naissance = ?, telephone = ?, photo = ? WHERE id = ?");
        $stmt->execute([$nom, $prenom, $dateNaissance, $telephone, $photoPath, $id]);

        if ($avant) {
            $changements = [];
            $nouveau = ['nom' => $nom, 'prenom' => $prenom, 'date_naissance' => $dateNaissance, 'telephone' => $telephone];
            foreach ($nouveau as $champ => $val) {
                if ((string)($avant[$champ] ?? '') !== (string)$val) {
                    $changements[] = "$champ : \"" . ($avant[$champ] ?? '') . "\" → \"" . $val . "\"";
                }
            }
            if (!empty($changements)) {
                enregistrerAudit($pdo, 'patientes', $id, 'modification', implode(' · ', $changements), $id);
            }
        }
    }

    header("Location: dossiers_sf.php?patiente_id=$id&maj=1");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive_patiente') {
    $id = intval($_POST['patiente_id']);

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE patientes SET archivee = 1, date_archivage = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        enregistrerAudit($pdo, 'patientes', $id, 'archivage', 'Dossier archivé par la sage-femme', $id);
    }

    header("Location: dossiers_sf.php?archive=1");
    exit;
}

// ---- désarchive d'une patiente ------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'desarchive_patiente') {
    $id = intval($_POST['patiente_id']);

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE patientes SET archivee = 0, date_archivage = NULL WHERE id = ?");
        $stmt->execute([$id]);
        enregistrerAudit($pdo, 'patientes', $id, 'desarchivage', 'Dossier réactivé par la sage-femme', $id);
    }

    header("Location: dossiers_sf.php?patiente_id=$id&desarchive=1");
    exit;
}

// ---- Nouvelle consultation pour une patiente -------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nouvelle_visite') {
    $id = intval($_POST['patiente_id']);
    $type = ($_POST['type_visite'] ?? '') === 'postnatal' ? 'postnatal' : 'prenatal';
    $motif = trim($_POST['motif'] ?? '');

    if ($id > 0) {
        $stmt = $pdo->prepare("INSERT INTO visites (patiente_id, type_visite, motif, statut) VALUES (?, ?, ?, 'attente')");
        $stmt->execute([$id, $type, $motif]);
        $nouvelleVisiteId = $pdo->lastInsertId();
        enregistrerAudit($pdo, 'visites', $nouvelleVisiteId, 'creation', "Nouvelle visite $type créée" . ($motif ? " (motif : $motif)" : ''), $id);
        header("Location: sage_femme.php?visite_id=$nouvelleVisiteId");
        exit;
    }
}

// ---- Liste de toutes les patientes --------------
$recherche = trim($_GET['recherche'] ?? '');
$voirArchivees = isset($_GET['voir_archivees']);
$sql = "
    SELECT p.*, COUNT(v.id) AS nb_visites
    FROM patientes p
    LEFT JOIN visites v ON v.patiente_id = p.id
";
$conditions = [];
$params = [];
if (!$voirArchivees) {
    $conditions[] = "COALESCE(p.archivee,0) = 0";
}
if ($recherche !== '') {
    $conditions[] = "(p.nom LIKE ? OR p.prenom LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}
$sql .= " GROUP BY p.id ORDER BY p.nom ASC, p.prenom ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patientes = $stmt->fetchAll();

// ---- Dossier d'une patiente sélectionnée -----------
$patienteId = intval($_GET['patiente_id'] ?? 0);
$patiente = null;
$historique = [];

if ($patienteId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM patientes WHERE id = ?");
    $stmt->execute([$patienteId]);
    $patiente = $stmt->fetch();

    if ($patiente) {
        $stmt = $pdo->prepare("
            SELECT c.*, v.type_visite, v.motif, v.statut, v.id AS visite_id
            FROM consultations c JOIN visites v ON v.id = c.visite_id
            WHERE v.patiente_id = ?
            ORDER BY c.date_consultation DESC
        ");
        $stmt->execute([$patienteId]);
        $historique = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM audit_log WHERE patiente_id = ? ORDER BY date_action DESC");
        $stmt->execute([$patienteId]);
        $auditPatiente = $stmt->fetchAll();
    }
}

$pageTitle = "Dossiers patientes";
$active = "sage_femme";
require 'header.php';
?>

<div class="row g-4">

  <!-- Liste de toutes les patientes -->
  <div class="col-lg-4 no-print">
    <div class="card-materna p-3 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="font-display fs-5 mb-0">📁 Toutes les patientes</h5>
        <a href="tableau_bord_sf.php" class="btn btn-sm btn-outline-secondary">📅 RDV</a>
      </div>
      <form method="get" class="mb-3">
        <input type="text" name="recherche" value="<?php echo e($recherche); ?>" class="form-control form-control-sm mb-2" placeholder="🔍 Rechercher une patiente...">
        <label class="small d-flex align-items-center gap-1 mb-0">
          <input type="checkbox" name="voir_archivees" value="1" <?php echo $voirArchivees ? 'checked' : ''; ?> onchange="this.form.submit()">
          Afficher aussi les dossiers archivés
        </label>
      </form>

      <?php if (isset($_GET['archive'])): ?>
        <div class="alert py-2 small" style="background-color: var(--sage-soft); color: var(--sage);">🗄️ Dossier archivé.</div>
      <?php endif; ?>

      <?php if (empty($patientes)): ?>
        <p class="text-muted small">Aucune patiente trouvée.</p>
      <?php else: foreach ($patientes as $p):
        $active_item = ($p['id'] == $patienteId) ? 'active' : '';
      ?>
        <a href="dossiers_sf.php?patiente_id=<?php echo $p['id']; ?>" class="patiente-item d-block p-3 mb-2 text-decoration-none text-reset <?php echo $active_item; ?>">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <?php if (!empty($p['photo'])): ?>
                <img src="<?php echo e($p['photo']); ?>" class="rounded-circle" style="width:36px; height:36px; object-fit:cover; border:1px solid var(--border);">
              <?php else: ?>
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center fw-bold text-secondary" style="width:36px; height:36px; font-size:14px;">
                  <?php echo mb_strtoupper(mb_substr($p['prenom'],0,1) . mb_substr($p['nom'],0,1)); ?>
                </div>
              <?php endif; ?>
              <div>
                <div class="small fw-medium"><?php echo e($p['prenom'] . ' ' . $p['nom']); ?></div>
                <div class="small text-muted">
                  <?php echo calculerAge($p['date_naissance']); ?> ans · <?php echo e($p['telephone'] ?: '—'); ?>
                </div>
              </div>
            </div>
            <span class="badge rounded-pill <?php echo $p['archivee'] ? 'badge-terminee' : 'badge-app'; ?>">
              <?php echo $p['archivee'] ? '🗄️ Archivé' : intval($p['nb_visites']) . ' visite(s)'; ?>
            </span>
          </div>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Dossier détaillé -->
  <div class="col-lg-8">
    <div class="card-materna p-4">
      <?php if (!$patiente): ?>
        <div class="text-center py-5 text-muted no-print">
          📁<br><br>Sélectionne une patiente dans la liste pour voir son dossier complet.
        </div>
      <?php else: ?>

        <div class="d-flex justify-content-between align-items-start mb-3 no-print">
          <div class="d-flex align-items-center gap-3">
            <?php if (!empty($patiente['photo'])): ?>
              <img src="<?php echo e($patiente['photo']); ?>" class="rounded-circle shadow-sm" style="width:60px; height:60px; object-fit:cover; border:2px solid var(--primary);">
            <?php else: ?>
              <div class="rounded-circle bg-light d-flex align-items-center justify-content-center fw-bold text-primary" style="width:60px; height:60px; font-size:22px; border:2px solid var(--border);">
                <?php echo mb_strtoupper(mb_substr($patiente['prenom'],0,1) . mb_substr($patiente['nom'],0,1)); ?>
              </div>
            <?php endif; ?>
            <div>
              <h4 class="font-display mb-0"><?php echo e($patiente['prenom'] . ' ' . $patiente['nom']); ?></h4>
              <div class="small text-muted">#<?php echo $patiente['id']; ?> · Fiche créée le <?php echo date('d/m/Y', strtotime($patiente['created_at'])); ?></div>
            </div>
          </div>
          <span class="badge rounded-pill badge-app"><?php echo count($historique); ?> consultation(s)</span>
        </div>

        <details class="no-print mb-4" style="border:1px solid var(--border); border-radius:0.5rem; padding:0.75rem;">
          <summary class="fw-medium" style="cursor:pointer;">➕ Nouvelle consultation</summary>
          <form method="post" class="mt-3">
            <input type="hidden" name="action" value="nouvelle_visite">
            <input type="hidden" name="patiente_id" value="<?php echo $patiente['id']; ?>">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label small text-muted">Type de consultation</label>
                <select name="type_visite" class="form-select form-select-sm">
                  <option value="prenatal">Prénatale</option>
                  <option value="postnatal">Postnatale</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label small text-muted">Motif</label>
                <input type="text" name="motif" class="form-control form-control-sm" placeholder="Motif de la visite">
              </div>
            </div>
            <button type="submit" class="btn btn-primary-materna btn-sm">Créer et ouvrir la consultation</button>
          </form>
        </details>

        <?php if ($patiente['archivee']): ?>
          <div class="alert py-2 small mb-3" style="background-color: var(--paper); color: var(--muted); border:1px solid var(--border);">
            🗄️ Ce dossier est archivé depuis le <?php echo date('d/m/Y', strtotime($patiente['date_archivage'])); ?>.
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['maj'])): ?>
          <div class="alert py-2 small mb-3" style="background-color: var(--sage-soft); color: var(--sage);">✅ Informations mises à jour.</div>
        <?php endif; ?>
        <?php if (isset($_GET['desarchive'])): ?>
          <div class="alert py-2 small mb-3" style="background-color: var(--sage-soft); color: var(--sage);">✅ Dossier réactivé.</div>
        <?php endif; ?>

        <!-- Formulaire de modification des informations -->
        <form method="post" enctype="multipart/form-data" class="no-print mb-4">
          <input type="hidden" name="action" value="update_patiente">
          <input type="hidden" name="patiente_id" value="<?php echo $patiente['id']; ?>">
          <div class="section-title">✏️ Informations de la patiente</div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label small text-muted">Nom</label>
              <input type="text" name="nom" value="<?php echo e($patiente['nom']); ?>" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label small text-muted">Prénom</label>
              <input type="text" name="prenom" value="<?php echo e($patiente['prenom']); ?>" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label small text-muted">Date de naissance</label>
              <input type="date" name="date_naissance" value="<?php echo e($patiente['date_naissance']); ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label small text-muted">Téléphone</label>
              <input type="text" name="telephone" value="<?php echo e($patiente['telephone']); ?>" class="form-control form-control-sm">
            <div class="col-md-12 mb-3">
              <label class="form-label small text-muted">Changer / Ajouter la photo</label>
              <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-materna btn-sm">💾 Enregistrer les modifications</button>
          </div>
        </form>

        <!-- Archivage / désarchivage du dossier -->
        <?php if (!$patiente['archivee']): ?>
          <form method="post" class="no-print mb-4" onsubmit="return confirm('Archiver le dossier de <?php echo e(addslashes($patiente['prenom'] . ' ' . $patiente['nom'])); ?> ? Il ne sera plus affiché dans la liste par défaut, mais rien ne sera supprimé et tu pourras le réactiver à tout moment.');">
            <input type="hidden" name="action" value="archive_patiente">
            <input type="hidden" name="patiente_id" value="<?php echo $patiente['id']; ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">🗄️ Archiver ce dossier</button>
          </form>
        <?php else: ?>
          <form method="post" class="no-print mb-4">
            <input type="hidden" name="action" value="desarchive_patiente">
            <input type="hidden" name="patiente_id" value="<?php echo $patiente['id']; ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary">↩️ Réactiver ce dossier</button>
          </form>
        <?php endif; ?>

        <!-- Historique complet -->
        <div class="section-title">📜 Historique des consultations</div>
        <?php if (empty($historique)): ?>
          <p class="text-muted small">Aucune consultation enregistrée pour cette patiente.</p>
        <?php else: foreach ($historique as $h):
          $d = json_decode($h['donnees'], true) ?: [];
          $nbVac = isset($d['vaccinations']) ? comptageVaccinsFaits($d['vaccinations']) : null;
          $resume = [];
          foreach ($d as $k => $v) {
              if ($k !== 'vaccinations' && $v !== '' && !is_array($v)) $resume[] = "$k: $v";
          }
        ?>
          <div class="small p-2 mb-2" style="border:1px solid var(--border); border-radius:0.5rem;">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div>
                <span style="color: var(--muted);"><?php echo date('d/m/Y H:i', strtotime($h['date_consultation'])); ?></span>
                <span class="badge rounded-pill badge-app mx-1"><?php echo $h['type_visite'] === 'postnatal' ? 'Postnatale' : 'Prénatale'; ?></span>
                <span class="badge rounded-pill badge-app mx-1"><?php echo e(ucfirst($h['volet'])); ?></span>
              </div>
              <a href="consultation_detail_sf.php?consultation_id=<?php echo $h['id']; ?>" class="btn btn-sm btn-outline-secondary">Voir / Modifier</a>
            </div>
            <?php if ($nbVac !== null): ?><div style="color: var(--sage);">💉 <?php echo $nbVac; ?> vaccin(s) réalisé(s)</div><?php endif; ?>
            <?php if (!empty($resume)): ?><div class="text-muted"><?php echo e(implode(' · ', $resume)); ?></div><?php endif; ?>
            <?php if (!empty($h['observations'])): ?><div class="mt-1 fst-italic">"<?php echo e($h['observations']); ?>"</div><?php endif; ?>
          </div>
        <?php endforeach; endif; ?>

        <!-- Journal du dossier -->
        <div class="section-title mt-4">🕒 Historique des modifications</div>
        <?php if (empty($auditPatiente)): ?>
          <p class="text-muted small">Aucune modification enregistrée pour ce dossier.</p>
        <?php else: foreach ($auditPatiente as $a): $ba = badgeAction($a['action']); ?>
          <div class="small p-2 mb-1" style="border:1px solid var(--border); border-radius:0.5rem;">
            <span style="color: var(--muted);"><?php echo date('d/m/Y H:i', strtotime($a['date_action'])); ?></span>
            <span class="badge rounded-pill <?php echo $ba['class']; ?> mx-1"><?php echo $ba['label']; ?></span>
            <span class="text-muted">par <?php echo e($a['utilisateur']); ?></span>
            <?php if (!empty($a['details'])): ?><div class="mt-1"><?php echo e($a['details']); ?></div><?php endif; ?>
          </div>
        <?php endforeach; endif; ?>

      <?php endif; ?>
    </div>
  </div>
</div>

<?php require 'footer.php'; ?>
