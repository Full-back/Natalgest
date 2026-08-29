<?php
/**
 * rapports.php
 * NatalGest — Rapports & Statistiques
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

requireLogin();

$role = $_SESSION['user_role'] ?? 'guest';
$nom  = $_SESSION['user_nom']  ?? 'Utilisateur';

$totalPatientes     = 0;
$totalConsultations = 0;
$totalVisites       = 0;
$totalUsers         = 0;
$nouvellesPatMois   = 0;
$consMois           = 0;
$parMois            = [];
$parRole            = [];
$topMotifs          = [];

try {
    $totalPatientes     = (int)$pdo->query("SELECT COUNT(*) FROM patientes WHERE COALESCE(archivee,0)=0")->fetchColumn();
    $totalUsers         = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $nouvellesPatMois   = (int)$pdo->query("SELECT COUNT(*) FROM patientes WHERE date_creation >= DATE_FORMAT(NOW(),'%Y-%m-01')")->fetchColumn();
    try {
        $totalConsultations = (int)$pdo->query("SELECT COUNT(*) FROM consultations")->fetchColumn();
        $consMois           = (int)$pdo->query("SELECT COUNT(*) FROM consultations WHERE date_consultation >= DATE_FORMAT(NOW(),'%Y-%m-01')")->fetchColumn();
    } catch(Exception $e){}
    try { $totalVisites = (int)$pdo->query("SELECT COUNT(*) FROM visites")->fetchColumn(); } catch(Exception $e){}
    try {
        $stmt = $pdo->query("SELECT DATE_FORMAT(date_consultation,'%Y-%m') AS mois, COUNT(*) AS nb FROM consultations WHERE date_consultation >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY mois ORDER BY mois ASC");
        foreach ($stmt->fetchAll() as $r) { $parMois[$r['mois']] = (int)$r['nb']; }
    } catch(Exception $e){}
    try {
        $stmt = $pdo->query("SELECT role, COUNT(*) as nb FROM users GROUP BY role");
        foreach ($stmt->fetchAll() as $r) { $parRole[$r['role']] = (int)$r['nb']; }
    } catch(Exception $e){}
    try {
        $stmt = $pdo->query("SELECT motif, COUNT(*) as nb FROM consultations WHERE motif IS NOT NULL AND motif != '' GROUP BY motif ORDER BY nb DESC LIMIT 5");
        $topMotifs = $stmt->fetchAll();
    } catch(Exception $e){}
} catch(Exception $e){}

$labels12 = []; $data12 = [];
for ($i = 11; $i >= 0; $i--) {
    $key      = date('Y-m', strtotime("-$i months"));
    $mNum     = (int)date('m', strtotime("-$i months"));
    $lblsFr   = ['Jan','Fev','Mar','Avr','Mai','Jun','Jul','Aou','Sep','Oct','Nov','Dec'];
    $labels12[] = $lblsFr[$mNum-1];
    $data12[]   = $parMois[$key] ?? 0;
}

$roleColors = ['admin'=>'#7C3AED','docteur'=>'#3B82F6','sage_femme'=>'#EC4899','receptionniste'=>'#F59E0B'];
$rLbls      = ['admin'=>'Administrateur','docteur'=>'Docteur','sage_femme'=>'Sage-femme','receptionniste'=>'Receptionniste'];
$moisFr     = ['Janvier','Fevrier','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Decembre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rapports & Statistiques — NatalGest</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<style>
:root{
  --v700:#6D28D9;--v600:#7C3AED;--v500:#8B5CF6;--v100:#EDE9FE;--v50:#F5F3FF;
  --rose:#EC4899;--rose100:#FCE7F3;
  --bleu:#3B82F6;--bleu100:#DBEAFE;
  --vert:#10B981;--vert100:#D1FAE5;
  --amb:#F59E0B;--amb100:#FEF3C7;
  --g900:#1F2937;--g600:#6B7280;--g300:#E5E7EB;--g100:#F3F4F6;
  --r:16px;--shadow:0 4px 20px rgba(109,40,217,.07);
}
*{box-sizing:border-box;margin:0;padding:0;}
body{
  font-family:'Inter',sans-serif;
  background:linear-gradient(135deg,rgba(15,23,42,.52) 0%,rgba(88,28,135,.42) 50%,rgba(15,23,42,.52) 100%),
             url('assets/img/login_slides/slide1.png') center/cover no-repeat fixed;
  color:var(--g900);display:flex;min-height:100vh;
}
h1,h2,h3{font-family:'Poppins',sans-serif;}
a{text-decoration:none;color:inherit;}

.sidebar{width:270px;background:rgba(255,255,255,.93);backdrop-filter:blur(16px);border-right:1px solid rgba(255,255,255,.6);box-shadow:4px 0 25px rgba(0,0,0,.15);padding:24px 18px;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;}
.brand{display:flex;align-items:center;gap:12px;padding:0 6px 22px;border-bottom:1px solid var(--g100);margin-bottom:18px;}
.brand-logo{width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,var(--v600),var(--rose));display:flex;align-items:center;justify-content:center;color:white;font-size:20px;flex-shrink:0;}
.brand-name{font-weight:700;font-size:17px;color:var(--v700);}
.brand-sub{font-size:11.5px;color:var(--g600);margin-top:2px;}
.nav-section-title{font-size:11px;font-weight:700;letter-spacing:.06em;color:var(--g600);text-transform:uppercase;margin:20px 8px 8px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;font-size:14px;font-weight:500;color:var(--g900);margin-bottom:2px;transition:background .15s,color .15s;}
.nav-item i{width:18px;text-align:center;color:var(--g600);font-size:15px;}
.nav-item:hover{background:var(--v50);}
.nav-item.active{background:linear-gradient(90deg,var(--v100),var(--rose100));color:var(--v700);font-weight:600;}
.nav-item.active i{color:var(--v700);}
.sidebar-footer{margin-top:auto;padding-top:14px;border-top:1px solid var(--g100);}
.sidebar-footer a{display:flex;align-items:center;gap:10px;font-size:13.5px;color:var(--g600);padding:8px 12px;}

.main{flex:1;display:flex;flex-direction:column;min-width:0;}
.topbar{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);border-bottom:1px solid rgba(229,231,235,.8);padding:16px 32px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:5;}
.topbar-title{font-family:'Poppins',sans-serif;font-weight:700;font-size:18px;color:var(--v700);}
.topbar-sub{font-size:13px;color:var(--g600);}
.topbar-right{margin-left:auto;}
.btn-back{display:inline-flex;align-items:center;gap:8px;background:var(--v50);color:var(--v700);padding:8px 16px;border-radius:10px;font-size:13.5px;font-weight:600;border:1px solid var(--v100);transition:background .15s;}
.btn-back:hover{background:var(--v100);}

.content{padding:28px 32px 48px;display:flex;flex-direction:column;gap:24px;}

.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;}
.kpi-card{background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-radius:var(--r);padding:22px;box-shadow:var(--shadow);border:1px solid rgba(255,255,255,.9);display:flex;align-items:center;gap:16px;transition:transform .2s,box-shadow .2s;}
.kpi-card:hover{transform:translateY(-3px);box-shadow:0 12px 30px rgba(109,40,217,.12);}
.kpi-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;color:white;flex-shrink:0;}
.kpi-icon.v{background:var(--v600);}.kpi-icon.r{background:var(--rose);}.kpi-icon.b{background:var(--bleu);}.kpi-icon.g{background:var(--vert);}
.kpi-label{font-size:12.5px;color:var(--g600);margin-bottom:4px;}
.kpi-value{font-family:'Poppins',sans-serif;font-size:28px;font-weight:700;line-height:1;}
.kpi-delta{font-size:12px;color:var(--vert);font-weight:600;margin-top:4px;}

.card{background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-radius:var(--r);padding:24px;box-shadow:var(--shadow);border:1px solid rgba(255,255,255,.9);}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.card-head h2{font-size:16px;color:var(--g900);}
.card-head span{font-size:12px;color:var(--g600);}
.grid2{display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start;}
.grid3{display:grid;grid-template-columns:1fr 2fr;gap:24px;}

.legend-list{display:flex;flex-direction:column;gap:12px;margin-top:16px;}
.legend-item{display:flex;align-items:center;justify-content:space-between;font-size:13.5px;}
.legend-dot{width:11px;height:11px;border-radius:50%;flex-shrink:0;}

.motif-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--g100);}
.motif-row:last-child{border-bottom:none;}
.motif-rank{width:24px;height:24px;border-radius:50%;background:var(--v100);color:var(--v700);font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.motif-bar-bg{height:6px;background:var(--g100);border-radius:4px;margin-top:4px;overflow:hidden;}
.motif-bar{height:6px;background:linear-gradient(90deg,var(--v600),var(--rose));border-radius:4px;}

table{width:100%;border-collapse:collapse;}
thead th{text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--g600);padding:10px 12px;border-bottom:1px solid var(--g100);}
tbody td{padding:13px 12px;font-size:13.5px;border-bottom:1px solid var(--g100);vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
.badge{display:inline-flex;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600;}

@media(max-width:1100px){.kpi-grid{grid-template-columns:repeat(2,1fr);}.grid2,.grid3{grid-template-columns:1fr;}}
@media(max-width:720px){.sidebar{display:none;}.kpi-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="brand">
    <div class="brand-logo"><i class="fa-solid fa-baby-carriage"></i></div>
    <div><div class="brand-name">NatalGest</div><div class="brand-sub">Suivi prenatal & postnatal</div></div>
  </div>
  <nav>
    <a href="<?= $role === 'admin' ? 'admin_dashboard.php' : 'tableau_bord_sf.php' ?>" class="nav-item"><i class="fa-solid fa-house"></i> Tableau de bord</a>
    <?php if ($role === 'admin'): ?>
    <div class="nav-section-title">Gestion utilisateurs</div>
    <a href="admin/gestion_utilisateurs.php" class="nav-item"><i class="fa-solid fa-users-gear"></i> Tous les utilisateurs</a>
    <a href="admin/creer_utilisateur.php"    class="nav-item"><i class="fa-solid fa-user-plus"></i> Creer un utilisateur</a>
    <?php endif; ?>
    <div class="nav-section-title">Suivi Medical</div>
    <a href="tableau_bord_sf.php" class="nav-item"><i class="fa-solid fa-calendar-days"></i> Rendez-vous & RDV</a>
    <a href="dossiers_sf.php"     class="nav-item"><i class="fa-solid fa-folder-open"></i> Dossiers patientes</a>
    <a href="sage_femme.php"      class="nav-item"><i class="fa-solid fa-stethoscope"></i> Consultation / File</a>
    <div class="nav-section-title">Analyses</div>
    <a href="rapports.php" class="nav-item active"><i class="fa-solid fa-chart-bar"></i> Rapports & Statistiques</a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php"><i class="fa-solid fa-right-to-bracket"></i> Deconnexion</a>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <div>
      <div class="topbar-title"><i class="fa-solid fa-chart-bar" style="color:var(--v600);margin-right:8px;"></i>Rapports & Statistiques</div>
      <div class="topbar-sub">Vue d'ensemble de l'activite — <?= date('d/m/Y') ?></div>
    </div>
    <div class="topbar-right">
      <a href="<?= $role === 'admin' ? 'admin_dashboard.php' : 'tableau_bord_sf.php' ?>" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Retour
      </a>
    </div>
  </header>

  <div class="content">

    <!-- KPIs -->
    <section class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon v"><i class="fa-solid fa-person-pregnant"></i></div>
        <div><div class="kpi-label">Total patientes</div><div class="kpi-value"><?= $totalPatientes ?></div><div class="kpi-delta">+<?= $nouvellesPatMois ?> ce mois</div></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon r"><i class="fa-solid fa-stethoscope"></i></div>
        <div><div class="kpi-label">Consultations totales</div><div class="kpi-value"><?= $totalConsultations ?></div><div class="kpi-delta">+<?= $consMois ?> ce mois</div></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon b"><i class="fa-solid fa-calendar-check"></i></div>
        <div><div class="kpi-label">Visites enregistrees</div><div class="kpi-value"><?= $totalVisites ?></div><div class="kpi-delta">Toutes periodes</div></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon g"><i class="fa-solid fa-users"></i></div>
        <div><div class="kpi-label">Personnel medical</div><div class="kpi-value"><?= $totalUsers ?></div><div class="kpi-delta">Comptes actifs</div></div>
      </div>
    </section>

    <!-- GRAPHIQUES -->
    <section class="grid2">
      <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-chart-line" style="color:var(--v600);margin-right:8px;"></i>Consultations sur 12 mois</h2><span><?= date('Y') ?></span></div>
        <canvas id="chartMois" height="110"></canvas>
      </div>
      <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-chart-pie" style="color:var(--rose);margin-right:8px;"></i>Repartition du personnel</h2></div>
        <canvas id="chartRoles" height="160"></canvas>
        <div class="legend-list">
          <?php $totalR = array_sum($parRole) ?: 1;
          foreach ($parRole as $r => $nb):
            $pct = round($nb / $totalR * 100);
            $col = $roleColors[$r] ?? '#94A3B8';
          ?>
          <div class="legend-item">
            <div style="display:flex;align-items:center;gap:8px;"><span class="legend-dot" style="background:<?= $col ?>;"></span><?= htmlspecialchars($rLbls[$r] ?? ucfirst($r)) ?></div>
            <span style="font-weight:700;color:<?= $col ?>;"><?= $nb ?> <small style="font-weight:400;color:var(--g600);">(<?= $pct ?>%)</small></span>
          </div>
          <?php endforeach; if (empty($parRole)): ?>
            <p style="color:var(--g600);font-size:13px;text-align:center;">Aucun utilisateur.</p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- TOP MOTIFS + RESUME MENSUEL -->
    <section class="grid3">
      <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-list-ol" style="color:var(--amb);margin-right:8px;"></i>Top motifs</h2><span>Consultations</span></div>
        <?php if (!empty($topMotifs)):
          $maxM = max(array_column($topMotifs,'nb')) ?: 1;
          foreach ($topMotifs as $i => $m): ?>
          <div class="motif-row">
            <div class="motif-rank"><?= $i+1 ?></div>
            <div style="flex:1;">
              <div style="display:flex;justify-content:space-between;">
                <span style="font-size:13.5px;"><?= htmlspecialchars($m['motif']) ?></span>
                <span style="font-size:13px;font-weight:700;color:var(--v600);"><?= $m['nb'] ?></span>
              </div>
              <div class="motif-bar-bg"><div class="motif-bar" style="width:<?= round($m['nb']/$maxM*100) ?>%;"></div></div>
            </div>
          </div>
          <?php endforeach; else: ?>
          <p style="color:var(--g600);font-size:13px;text-align:center;padding:20px 0;">Aucun motif enregistre.</p>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-table" style="color:var(--bleu);margin-right:8px;"></i>Resume mensuel (6 derniers mois)</h2></div>
        <table>
          <thead><tr><th>Mois</th><th>Consultations</th><th>Nv. patientes</th><th>Tendance</th></tr></thead>
          <tbody>
          <?php $prevVal = null;
          for ($i = 5; $i >= 0; $i--):
            $key  = date('Y-m', strtotime("-$i months"));
            $mNum = (int)date('m', strtotime("-$i months")) - 1;
            $annee = date('Y', strtotime("-$i months"));
            $nbC  = $parMois[$key] ?? 0;
            $nbP  = 0;
            try { $sP = $pdo->prepare("SELECT COUNT(*) FROM patientes WHERE DATE_FORMAT(date_creation,'%Y-%m')=?"); $sP->execute([$key]); $nbP = (int)$sP->fetchColumn(); } catch(Exception $e){}
            $tend = '—'; $tCol = 'var(--g600)';
            if ($prevVal !== null) {
              if ($nbC > $prevVal)     { $tend = '<i class="fa-solid fa-arrow-trend-up"></i> Hausse'; $tCol = 'var(--vert)'; }
              elseif ($nbC < $prevVal) { $tend = '<i class="fa-solid fa-arrow-trend-down"></i> Baisse'; $tCol = '#EF4444'; }
              else                     { $tend = '<i class="fa-solid fa-minus"></i> Stable'; }
            }
            $prevVal = $nbC;
          ?>
          <tr>
            <td><strong><?= $moisFr[$mNum] ?> <?= $annee ?></strong></td>
            <td><span class="badge" style="background:var(--v100);color:var(--v700);"><?= $nbC ?></span></td>
            <td><span class="badge" style="background:var(--rose100);color:#BE185D;"><?= $nbP ?></span></td>
            <td style="color:<?= $tCol ?>;font-weight:600;font-size:13px;"><?= $tend ?></td>
          </tr>
          <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</div>

<script>
const ctxMois = document.getElementById('chartMois').getContext('2d');
new Chart(ctxMois, {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels12) ?>,
    datasets: [{
      label: 'Consultations',
      data: <?= json_encode($data12) ?>,
      backgroundColor: 'rgba(124,58,237,0.18)',
      borderColor: '#7C3AED',
      borderWidth: 2,
      borderRadius: 8,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,.05)' } },
      x: { grid: { display: false } }
    }
  }
});

const rolesData   = <?= json_encode(array_values($parRole)) ?>;
const rolesLabels = <?= json_encode(array_map(fn($r) => $rLbls[$r] ?? ucfirst($r), array_keys($parRole))) ?>;
const rolesCols   = <?= json_encode(array_map(fn($r) => $roleColors[$r] ?? '#94A3B8', array_keys($parRole))) ?>;
if (rolesData.length) {
  new Chart(document.getElementById('chartRoles').getContext('2d'), {
    type: 'doughnut',
    data: { labels: rolesLabels, datasets: [{ data: rolesData, backgroundColor: rolesCols, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }] },
    options: { responsive: true, cutout: '70%', plugins: { legend: { display: false } } }
  });
}
</script>
</body>
</html>
