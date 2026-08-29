<?php
require_once __DIR__ . '/includes/auth.php';
requireRole(['sage_femme', 'docteur']);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers.php';

$sql = "
    SELECT c.prochain_rdv, c.date_consultation, p.id AS patiente_id, p.nom, p.prenom, p.telephone, p.photo, v.type_visite
    FROM consultations c
    JOIN visites v ON v.id = c.visite_id
    JOIN patientes p ON p.id = v.patiente_id
    WHERE COALESCE(p.archivee,0) = 0
      AND c.prochain_rdv IS NOT NULL
      AND c.id = (
          SELECT c2.id FROM consultations c2
          JOIN visites v2 ON v2.id = c2.visite_id
          WHERE v2.patiente_id = p.id AND c2.prochain_rdv IS NOT NULL
          ORDER BY c2.date_consultation DESC
          LIMIT 1
      )
    ORDER BY c.prochain_rdv ASC
";
$stmt = $pdo->query($sql);
$rdvList = $stmt->fetchAll();

$aujourdhui = date('Y-m-01');
$dansUneSemaine = date('Y-m-d', strtotime('+7 days'));

$enRetard = [];
$aVenirBientot = [];
$plusTard = [];

foreach ($rdvList as $r) {
    if ($r['prochain_rdv'] < date('Y-m-d')) {
        $enRetard[] = $r;
    } elseif ($r['prochain_rdv'] <= $dansUneSemaine) {
        $aVenirBientot[] = $r;
    } else {
        $plusTard[] = $r;
    }
}

function ligneRdv($r, $couleurDate = null) {
    $style = $couleurDate ? "color: $couleurDate; font-weight:600;" : "color: var(--muted);";
    echo '<a href="dossiers_sf.php?patiente_id=' . $r['patiente_id'] . '" class="patiente-item d-block p-3 mb-2 text-decoration-none text-reset">';
    echo '<div class="d-flex justify-content-between align-items-center">';
    echo '<div class="d-flex align-items-center gap-2">';
    if (!empty($r['photo'])) {
        echo '<img src="' . e($r['photo']) . '" class="rounded-circle" style="width:32px; height:32px; object-fit:cover;">';
    } else {
        echo '<div class="rounded-circle bg-light d-flex align-items-center justify-content-center fw-bold text-secondary" style="width:32px; height:32px; font-size:12px;">' . mb_strtoupper(mb_substr($r['prenom'],0,1) . mb_substr($r['nom'],0,1)) . '</div>';
    }
    echo '<div>';
    echo '<div class="small fw-medium">' . e($r['prenom'] . ' ' . $r['nom']) . '</div>';
    echo '<div class="small text-muted">' . ($r['type_visite'] === 'postnatal' ? 'Postnatale' : 'Prénatale') . ' · ' . e($r['telephone'] ?: '—') . '</div>';
    echo '</div></div>';
    echo '<span class="small" style="' . $style . '">' . date('d/m/Y', strtotime($r['prochain_rdv'])) . '</span>';
    echo '</div></a>';
}

$pageTitle = "Tableau de bord";
$active = "sage_femme";
require 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
  <h4 class="font-display mb-0">📅 Tableau de bord des rendez-vous</h4>
  <a href="dossiers_sf.php" class="btn btn-sm btn-outline-secondary">📁 Tous les dossiers</a>
</div>

<div class="row g-4">

  <div class="col-md-4">
    <div class="card-materna p-3 h-100">
      <h6 class="font-display mb-3" style="color:#c0392b;">⚠️ En retard (<?php echo count($enRetard); ?>)</h6>
      <?php if (empty($enRetard)): ?>
        <p class="text-muted small">Aucun rendez-vous en retard.</p>
      <?php else: foreach ($enRetard as $r) ligneRdv($r, '#c0392b'); endif; ?>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card-materna p-3 h-100">
      <h6 class="font-display mb-3" style="color: var(--sage);">🔔 Dans les 7 prochains jours (<?php echo count($aVenirBientot); ?>)</h6>
      <?php if (empty($aVenirBientot)): ?>
        <p class="text-muted small">Aucun rendez-vous prévu cette semaine.</p>
      <?php else: foreach ($aVenirBientot as $r) ligneRdv($r, 'var(--sage)'); endif; ?>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card-materna p-3 h-100">
      <h6 class="font-display mb-3">📆 Plus tard (<?php echo count($plusTard); ?>)</h6>
      <?php if (empty($plusTard)): ?>
        <p class="text-muted small">Aucun autre rendez-vous programmé.</p>
      <?php else: foreach ($plusTard as $r) ligneRdv($r); endif; ?>
    </div>
  </div>

</div>

<div class="mt-4 no-print small text-muted">
  💉 Le suivi des vaccins en retard sera ajouté ici prochainement.
</div>

<?php require 'footer.php'; ?>
