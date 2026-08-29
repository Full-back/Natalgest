<?php
require_once __DIR__ . '/includes/auth.php';
requireRole(['sage_femme', 'docteur']);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/sections.php';

// ---- Traitement de la consultation (formulaire POST) -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'consultation') {
    $visiteId = intval($_POST['visite_id']);
    $volet = $_POST['volet'];
    $donnees = $_POST['champs'] ?? [];

    if (isset($_POST['vaccinations'])) {
        $vacc = [];
        foreach ($_POST['vaccinations'] as $key => $v) {
            $vacc[$key] = [
                'fait' => isset($v['fait']) ? true : false,
                'date' => $v['date'] ?? '',
            ];
        }
        $donnees['vaccinations'] = $vacc;
    }

    $observations = trim($_POST['observations'] ?? '');
    $prochainRdv = $_POST['prochain_rdv'] ?: null;

    $stmt = $pdo->prepare("INSERT INTO consultations (visite_id, volet, donnees, observations, prochain_rdv) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$visiteId, $volet, json_encode($donnees, JSON_UNESCAPED_UNICODE), $observations, $prochainRdv]);
    $nouvelleConsultationId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("UPDATE visites SET statut = 'terminee' WHERE id = ?");
    $stmt->execute([$visiteId]);

    $stmtP = $pdo->prepare("SELECT patiente_id FROM visites WHERE id = ?");
    $stmtP->execute([$visiteId]);
    $vp = $stmtP->fetch();
    if ($vp) {
        enregistrerAudit($pdo, 'consultations', $nouvelleConsultationId, 'creation', "Consultation ($volet) enregistrée", $vp['patiente_id']);
    }

    header("Location: sage_femme.php?visite_id=$visiteId&volet=$volet");
    exit;
}

// ---- Sélection d'une patiente dans la file d'attente ------------------------
$visiteId = intval($_GET['visite_id'] ?? 0);
$volet = $_GET['volet'] ?? 'maman';
$selected = null;

if ($visiteId > 0) {
    $stmt = $pdo->prepare("
        SELECT v.*, p.nom, p.prenom, p.telephone, p.date_naissance, p.id AS patiente_id
        FROM visites v JOIN patientes p ON p.id = v.patiente_id
        WHERE v.id = ?
    ");
    $stmt->execute([$visiteId]);
    $selected = $stmt->fetch();

    if ($selected && $selected['statut'] === 'attente') {
        $pdo->prepare("UPDATE visites SET statut = 'en_consultation' WHERE id = ?")->execute([$visiteId]);
        $selected['statut'] = 'en_consultation';
    }
}

$isPostnatal = $selected && $selected['type_visite'] === 'postnatal';
if (!$isPostnatal) $volet = 'prenatal';

$sections = [];
$showVaccinTable = false;
if ($selected) {
    if (!$isPostnatal) {
        $sections = $PRENATAL_SECTIONS;
    } elseif ($volet === 'enfant') {
        $sections = $POSTNATAL_CHILD_SECTIONS;
        $showVaccinTable = true;
    } else {
        $sections = $POSTNATAL_MOTHER_SECTIONS;
    }
}

// ---- Historique complet de la patiente (toutes ses visites, pas seulement celle-ci) ----
$historique = [];
if ($selected) {
    $stmt = $pdo->prepare("
        SELECT c.*, v.type_visite
        FROM consultations c JOIN visites v ON v.id = c.visite_id
        WHERE v.patiente_id = ?
        ORDER BY c.date_consultation DESC
    ");
    $stmt->execute([$selected['patiente_id']]);
    $historique = $stmt->fetchAll();
}

// ---- File d'attente ----------------------------------------------------------
$recherche = trim($_GET['recherche'] ?? '');
$sql = "
    SELECT v.*, p.nom, p.prenom, p.telephone, p.date_naissance
    FROM visites v JOIN patientes p ON p.id = v.patiente_id
    WHERE v.statut != 'terminee' AND COALESCE(p.archivee,0) = 0
";
$params = [];
if ($recherche !== '') {
    $sql .= " AND (p.nom LIKE ? OR p.prenom LIKE ?)";
    $params = ["%$recherche%", "%$recherche%"];
}
$sql .= " ORDER BY v.heure_enregistrement ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$queue = $stmt->fetchAll();

$pageTitle = "Sage-femme";
$active = "sage_femme";
require 'header.php';
?>

<div class="row g-4">
  <!-- File d'attente -->
  <div class="col-lg-4 no-print">
    <div class="card-materna p-3 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="font-display fs-5 mb-0">👥 File d'attente</h5>
        <div class="d-flex gap-2">
          <a href="tableau_bord_sf.php" class="btn btn-sm btn-outline-secondary">📅 Rendez-vous</a>
          <a href="dossiers_sf.php" class="btn btn-sm btn-outline-secondary">📁 Tous les dossiers</a>
        </div>
      </div>
      <form method="get" class="mb-3">
        <input type="text" name="recherche" value="<?php echo e($recherche); ?>" class="form-control form-control-sm" placeholder="🔍 Rechercher une patiente...">
      </form>

      <?php if (empty($queue)): ?>
        <p class="text-muted small">Aucune patiente en attente.</p>
      <?php else: foreach ($queue as $p):
        $b = badgeStatut($p['statut']);
        $active_item = ($p['id'] == $visiteId) ? 'active' : '';
      ?>
        <a href="sage_femme.php?visite_id=<?php echo $p['id']; ?>" class="patiente-item d-block p-3 mb-2 text-decoration-none text-reset <?php echo $active_item; ?>">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="small fw-medium"><?php echo e($p['prenom'] . ' ' . $p['nom']); ?></div>
              <div class="small text-muted"><?php echo $p['type_visite'] === 'prenatal' ? 'Prénatale' : 'Postnatale'; ?> · <?php echo e($p['motif']); ?></div>
            </div>
            <span class="badge rounded-pill <?php echo $b['class']; ?>"><?php echo $b['label']; ?></span>
          </div>
          <div class="mt-2"><?php echo afficherParcours($p['statut']); ?></div>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Dossier / consultation -->
  <div class="col-lg-8">
    <div class="card-materna p-4">
      <?php if (!$selected): ?>
        <div class="text-center py-5 text-muted no-print">
          📋<br><br>Sélectionne une patiente dans la file d'attente pour ouvrir son dossier.
        </div>
      <?php else: ?>

        <div class="d-flex justify-content-between align-items-start mb-3 no-print">
          <div>
            <h4 class="font-display"><?php echo e($selected['prenom'] . ' ' . $selected['nom']); ?></h4>
            <div class="small text-muted">
              📅 <?php echo calculerAge($selected['date_naissance']); ?> ans &nbsp;
              📞 <?php echo e($selected['telephone'] ?: '—'); ?> &nbsp;
              #<?php echo $selected['id']; ?>
            </div>
          </div>
          <div class="d-flex gap-2">
            <span class="badge rounded-pill badge-app">💗 <?php echo !$isPostnatal ? 'Consultation prénatale' : ("Consultation postnatale — " . ($volet === 'enfant' ? "suivi de l'enfant" : "suivi de la maman")); ?></span>
            <a class="btn btn-sm btn-outline-secondary" href="imprimer.php?visite_id=<?php echo $selected['id']; ?>&volet=<?php echo e($volet); ?>" target="_blank">🖨️ Imprimer</a>
          </div>
        </div>

        <?php if ($isPostnatal): ?>
          <div class="volet-toggle btn-group mb-3 no-print" role="group">
            <a href="sage_femme.php?visite_id=<?php echo $visiteId; ?>&volet=maman" class="btn btn-sm <?php echo $volet==='maman' ? 'btn-primary-materna' : 'btn-outline-secondary'; ?>">Suivi de la maman</a>
            <a href="sage_femme.php?visite_id=<?php echo $visiteId; ?>&volet=enfant" class="btn btn-sm <?php echo $volet==='enfant' ? 'btn-primary-materna' : 'btn-outline-secondary'; ?>">Suivi de l'enfant</a>
          </div>
        <?php endif; ?>

        <div class="p-3 mb-3 rounded small text-muted no-print" style="background-color: var(--paper); color: #000000 !important;">
          Motif : <?php echo e($selected['motif'] ?: 'non précisé'); ?>
        </div>

        <?php if (!empty($historique)): ?>
          <div class="mb-4 no-print">
            <div class="small fw-medium text-muted mb-2">Historique des consultations (<?php echo count($historique); ?>)</div>
            <?php foreach ($historique as $h):
              $d = json_decode($h['donnees'], true) ?: [];
              $nbVac = isset($d['vaccinations']) ? comptageVaccinsFaits($d['vaccinations']) : null;
              $resume = [];
              foreach ($d as $k => $v) {
                if ($k !== 'vaccinations' && $v !== '' && !is_array($v)) $resume[] = "$k: $v";
              }
            ?>
              <div class="small p-2 mb-1" style="border:1px solid var(--border); border-radius:0.5rem;">
                <span style="color: var(--muted);"><?php echo date('d/m/Y H:i', strtotime($h['date_consultation'])); ?></span>
                <span class="badge rounded-pill badge-app mx-1"><?php echo $h['type_visite'] === 'postnatal' ? 'Postnatale' : 'Prénatale'; ?></span>
                <?php if ($nbVac !== null): ?><span class="mx-1" style="color: var(--sage);">· <?php echo $nbVac; ?> vaccin(s) réalisé(s)</span><?php endif; ?>
                — <?php echo e(implode(' · ', array_slice($resume, 0, 6))); ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($selected['statut'] === 'terminee'): ?>
          <div class="alert py-2 small" style="background-color: var(--sage-soft); color: var(--sage);">✅ Consultation terminée pour cette visite.</div>
        <?php else: ?>
          <form method="post" class="no-print">
            <input type="hidden" name="action" value="consultation">
            <input type="hidden" name="visite_id" value="<?php echo $visiteId; ?>">
            <input type="hidden" name="volet" value="<?php echo e($volet); ?>">

            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="fw-medium">📈 Fiche de consultation</span>
            </div>

            <?php foreach ($sections as $sec): ?>
              <div class="mb-3">
                <div class="section-title"><?php echo e($sec['title']); ?></div>
                <div class="row">
                  <?php foreach ($sec['fields'] as $f) echo champHtml($f, []); ?>
                </div>
              </div>
            <?php endforeach; ?>

            <?php if ($showVaccinTable): ?>
              <div class="mb-3">
                <div class="section-title">💉 Suivi vaccinal de l'enfant</div>
                <div style="border:1px solid var(--border); border-radius:0.5rem; overflow:hidden;">
                  <?php foreach ($VACCINES_ENFANT as $i => $v): ?>
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 small" style="background-color: rgba(255, 255, 255, 0.05); <?php echo $i>0 ? 'border-top:1px solid var(--border);' : ''; ?>">
                      <div>
                        <div><?php echo e($v['label']); ?></div>
                        <div class="text-muted" style="font-size:0.72rem;">Prévu : <?php echo e($v['age']); ?></div>
                      </div>
                      <div class="d-flex align-items-center gap-2">
                        <input type="date" name="vaccinations[<?php echo e($v['key']); ?>][date]" class="form-control form-control-sm" style="width:150px;">
                        <label class="small d-flex align-items-center gap-1 mb-0">
                          <input type="checkbox" name="vaccinations[<?php echo e($v['key']); ?>][fait]"> Fait
                        </label>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label small text-muted">Observations</label>
              <textarea name="observations" class="form-control form-control-sm" rows="2"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label small text-muted">Prochain rendez-vous</label>
              <input type="date" name="prochain_rdv" class="form-control form-control-sm">
            </div>

            <button type="submit" class="btn btn-primary-materna btn-sm w-100">✅ Terminer la consultation</button>
          </form>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>
</div>

<?php require 'footer.php'; ?>
