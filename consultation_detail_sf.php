<?php
require_once __DIR__ . '/includes/auth.php';
requireRole(['sage_femme', 'docteur']);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/sections.php';

// ---- correction d'une consultation déjà enregistrée -----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_consultation') {
    $consultationId = intval($_POST['consultation_id']);
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

    $stmtAvant = $pdo->prepare("
        SELECT c.*, v.patiente_id FROM consultations c JOIN visites v ON v.id = c.visite_id WHERE c.id = ?
    ");
    $stmtAvant->execute([$consultationId]);
    $avant = $stmtAvant->fetch();

    $stmt = $pdo->prepare("UPDATE consultations SET donnees = ?, observations = ?, prochain_rdv = ? WHERE id = ?");
    $stmt->execute([json_encode($donnees, JSON_UNESCAPED_UNICODE), $observations, $prochainRdv, $consultationId]);

    if ($avant) {
        enregistrerAudit($pdo, 'consultations', $consultationId, 'modification', 'Consultation corrigée par la sage-femme', $avant['patiente_id']);
    }

    header("Location: consultation_detail_sf.php?consultation_id=$consultationId&maj=1");
    exit;
}

// ---- Chargement de la consultation ------
$consultationId = intval($_GET['consultation_id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT c.*, v.type_visite, v.motif, v.patiente_id, p.nom, p.prenom, p.date_naissance
    FROM consultations c
    JOIN visites v ON v.id = c.visite_id
    JOIN patientes p ON p.id = v.patiente_id
    WHERE c.id = ?
");
$stmt->execute([$consultationId]);
$consultation = $stmt->fetch();

if (!$consultation) {
    require 'header.php';
    echo '<div class="card-materna p-4 text-center text-muted">Consultation introuvable. <a href="dossiers_sf.php">Retour aux dossiers</a></div>';
    require 'footer.php';
    exit;
}

$donnees = json_decode($consultation['donnees'], true) ?: [];
$isPostnatal = $consultation['type_visite'] === 'postnatal';
$volet = $consultation['volet'];

$sections = [];
$showVaccinTable = false;
if (!$isPostnatal) {
    $sections = $PRENATAL_SECTIONS;
} elseif ($volet === 'enfant') {
    $sections = $POSTNATAL_CHILD_SECTIONS;
    $showVaccinTable = true;
} else {
    $sections = $POSTNATAL_MOTHER_SECTIONS;
}

$pageTitle = "Détail de la consultation";
$active = "sage_femme";
require 'header.php';
?>

<div class="card-materna p-4">
  <div class="d-flex justify-content-between align-items-start mb-3 no-print">
    <div>
      <h4 class="font-display"><?php echo e($consultation['prenom'] . ' ' . $consultation['nom']); ?></h4>
      <div class="small text-muted">
        Consultation du <?php echo date('d/m/Y H:i', strtotime($consultation['date_consultation'])); ?>
        &nbsp;·&nbsp; <?php echo $isPostnatal ? "Postnatale — " . ($volet === 'enfant' ? "suivi de l'enfant" : "suivi de la maman") : "Prénatale"; ?>
      </div>
    </div>
    <a href="dossiers_sf.php?patiente_id=<?php echo $consultation['patiente_id']; ?>" class="btn btn-sm btn-outline-secondary">← Retour au dossier</a>
  </div>

  <?php if (isset($_GET['maj'])): ?>
    <div class="alert py-2 small mb-3" style="background-color: var(--sage-soft); color: var(--sage);">✅ Consultation mise à jour.</div>
  <?php endif; ?>

  <div class="p-3 mb-3 rounded small text-muted no-print" style="background-color: var(--paper); color: #000000 !important;">
    Motif de la visite : <?php echo e($consultation['motif'] ?: 'non précisé'); ?>
  </div>

  <form method="post" class="no-print">
    <input type="hidden" name="action" value="update_consultation">
    <input type="hidden" name="consultation_id" value="<?php echo $consultation['id']; ?>">

    <?php foreach ($sections as $sec): ?>
      <div class="mb-3">
        <div class="section-title"><?php echo e($sec['title']); ?></div>
        <div class="row">
          <?php foreach ($sec['fields'] as $f) echo champHtml($f, $donnees); ?>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if ($showVaccinTable): ?>
      <div class="mb-3">
        <div class="section-title">💉 Suivi vaccinal de l'enfant</div>
        <div style="border:1px solid var(--border); border-radius:0.5rem; overflow:hidden;">
          <?php $vaccData = $donnees['vaccinations'] ?? []; ?>
          <?php foreach ($VACCINES_ENFANT as $i => $v):
            $vv = $vaccData[$v['key']] ?? ['fait' => false, 'date' => ''];
          ?>
            <div class="d-flex justify-content-between align-items-center px-3 py-2 small" style="background-color: rgba(255, 255, 255, 0.05); <?php echo $i>0 ? 'border-top:1px solid var(--border);' : ''; ?>">
              <div>
                <div><?php echo e($v['label']); ?></div>
                <div class="text-muted" style="font-size:0.72rem;">Prévu : <?php echo e($v['age']); ?></div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <input type="date" name="vaccinations[<?php echo e($v['key']); ?>][date]" value="<?php echo e($vv['date']); ?>" class="form-control form-control-sm" style="width:150px;">
                <label class="small d-flex align-items-center gap-1 mb-0">
                  <input type="checkbox" name="vaccinations[<?php echo e($v['key']); ?>][fait]" <?php echo !empty($vv['fait']) ? 'checked' : ''; ?>> Fait
                </label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="mb-3">
      <label class="form-label small text-muted">Observations</label>
      <textarea name="observations" class="form-control form-control-sm" rows="2"><?php echo e($consultation['observations']); ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label small text-muted">Prochain rendez-vous</label>
      <input type="date" name="prochain_rdv" class="form-control form-control-sm" value="<?php echo e($consultation['prochain_rdv']); ?>">
    </div>

    <button type="submit" class="btn btn-primary-materna btn-sm w-100">💾 Enregistrer les corrections</button>
  </form>
</div>

<?php require 'footer.php'; ?>
