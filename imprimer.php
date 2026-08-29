<?php
require_once __DIR__ . '/includes/auth.php';
requireRole(['sage_femme', 'docteur']);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/sections.php';

$visiteId = intval($_GET['visite_id'] ?? 0);
$volet = $_GET['volet'] ?? 'maman';

$stmt = $pdo->prepare("
    SELECT v.*, p.nom, p.prenom, p.telephone, p.date_naissance, p.id AS patiente_id
    FROM visites v JOIN patientes p ON p.id = v.patiente_id
    WHERE v.id = ?
");
$stmt->execute([$visiteId]);
$selected = $stmt->fetch();

if (!$selected) {
    die("Dossier introuvable.");
}

$isPostnatal = $selected['type_visite'] === 'postnatal';
if (!$isPostnatal) $volet = 'prenatal';

$sections = !$isPostnatal ? $PRENATAL_SECTIONS : ($volet === 'enfant' ? $POSTNATAL_CHILD_SECTIONS : $POSTNATAL_MOTHER_SECTIONS);
$showVaccinTable = $isPostnatal && $volet === 'enfant';

// Dernière consultation enregistrée pour ce passage (et ce volet si postnatal)
$sql = "SELECT * FROM consultations WHERE visite_id = ?" . ($isPostnatal ? " AND volet = ?" : "") . " ORDER BY date_consultation DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$isPostnatal ? $stmt->execute([$visiteId, $volet]) : $stmt->execute([$visiteId]);
$derniereConsultation = $stmt->fetch();
$donnees = $derniereConsultation ? (json_decode($derniereConsultation['donnees'], true) ?: []) : [];

// Historique complet de la patiente
$stmt = $pdo->prepare("
    SELECT c.*, v.type_visite
    FROM consultations c JOIN visites v ON v.id = c.visite_id
    WHERE v.patiente_id = ?
    ORDER BY c.date_consultation DESC
    LIMIT 8
");
$stmt->execute([$selected['patiente_id']]);
$historique = $stmt->fetchAll();

$typeVisiteLabel = !$isPostnatal ? "Consultation prénatale" : ("Consultation postnatale — " . ($volet === 'enfant' ? "suivi de l'enfant" : "suivi de la maman"));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Fiche — <?php echo e($selected['prenom'] . ' ' . $selected['nom']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Inter', sans-serif; color: #111; max-width: 800px; margin: 24px auto; padding: 0 16px; }
  .font-display { font-family: 'Fraunces', serif; }
  .entete { display:flex; justify-content:space-between; align-items:baseline; border-bottom:2px solid #111; padding-bottom:8px; margin-bottom:16px; }
  .section-title { font-size:11px; font-weight:700; text-transform:uppercase; border-bottom:1px solid #ccc; margin-bottom:4px; padding-bottom:2px; }
  table { width:100%; font-size:12px; border-collapse:collapse; margin-bottom:10px; }
  td { padding:2px 4px; }
  td.label { color:#555; width:55%; }
  .btn-print { background:#2E5D57; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-size:14px; cursor:pointer; }
  @media print { .no-print { display:none !important; } }
</style>
</head>
<body>

<div class="no-print" style="display:flex; justify-content:flex-end; gap:12px; margin-bottom:16px;">
  <a href="logout.php" style="text-decoration:none; background:#7C3AED; color:#fff; padding:10px 14px; border-radius:10px; font-size:14px;">Déconnexion</a>
  <button class="btn-print" onclick="window.print()">🖨️ Imprimer / Enregistrer en PDF</button>
</div>

<div class="entete">
  <div>
    <div class="font-display" style="font-size:20px; font-weight:600;">NatalGest — Fiche de consultation</div>
    <div style="font-size:11px; color:#555;">Suivi prénatal & postnatal</div>
  </div>
  <div style="font-size:11px; color:#555;">Imprimé le <?php echo date('d/m/Y'); ?></div>
</div>

<div style="margin-bottom:14px; font-size:13px; line-height:1.6;">
  <strong><?php echo e($selected['prenom'] . ' ' . $selected['nom']); ?></strong>
  — <?php echo calculerAge($selected['date_naissance']); ?> ans — <?php echo e($selected['telephone'] ?: '—'); ?><br>
  Dossier #<?php echo $selected['id']; ?> · <?php echo e($typeVisiteLabel); ?><br>
  Motif : <?php echo e($selected['motif'] ?: 'non précisé'); ?>
</div>

<?php if (!$derniereConsultation): ?>
  <p style="font-size:12px; color:#900;">Aucune consultation enregistrée pour l'instant pour ce volet.</p>
<?php endif; ?>

<?php foreach ($sections as $sec): ?>
  <div>
    <div class="section-title"><?php echo e($sec['title']); ?></div>
    <table><tbody>
      <?php foreach ($sec['fields'] as $f):
        $val = $donnees[$f['key']] ?? '';
      ?>
        <tr><td class="label"><?php echo e($f['label']); ?></td><td style="font-weight:500;"><?php echo e($val !== '' ? $val : '—'); ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
<?php endforeach; ?>

<?php if ($showVaccinTable): ?>
  <div>
    <div class="section-title">Suivi vaccinal de l'enfant</div>
    <table><tbody>
      <?php foreach ($VACCINES_ENFANT as $v):
        $vd = $donnees['vaccinations'][$v['key']] ?? null;
        $texte = ($vd && !empty($vd['fait'])) ? ('Fait le ' . e($vd['date'] ?: '—')) : 'Non fait';
      ?>
        <tr><td class="label"><?php echo e($v['label'] . ' (' . $v['age'] . ')'); ?></td><td><?php echo $texte; ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
<?php endif; ?>

<div>
  <div class="section-title">Observations</div>
  <p style="font-size:12px;"><?php echo nl2br(e($derniereConsultation['observations'] ?? '—')); ?></p>
  <p style="font-size:12px;">Prochain rendez-vous : <?php echo $derniereConsultation && $derniereConsultation['prochain_rdv'] ? date('d/m/Y', strtotime($derniereConsultation['prochain_rdv'])) : '—'; ?></p>
</div>

<?php if (!empty($historique)): ?>
  <div>
    <div class="section-title">Historique des visites précédentes</div>
    <ul style="font-size:11px; padding-left:16px; margin:0;">
      <?php foreach ($historique as $h): ?>
        <li><?php echo date('d/m/Y', strtotime($h['date_consultation'])); ?> — <?php echo $h['type_visite'] === 'postnatal' ? 'Postnatale' : 'Prénatale'; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div style="margin-top:40px; display:flex; justify-content:space-between; font-size:12px;">
  <div>Signature sage-femme / médecin : ______________________</div>
  <div>Date : ______________</div>
</div>

</body>
</html>
