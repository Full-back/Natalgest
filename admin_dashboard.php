<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers.php';

// Bloque la page si l'utilisateur n'est pas connecté ou n'est pas admin
requireRole('admin');

$adminPhoto = $_SESSION['user_photo'] ?? '';
if (empty($adminPhoto) && isset($_SESSION['user_id'])) {
    try {
        $stmtP = $pdo->prepare("SELECT photo FROM users WHERE id = ?");
        $stmtP->execute([(int)$_SESSION['user_id']]);
        $adminPhoto = $stmtP->fetchColumn() ?: '';
        if ($adminPhoto) {
            $_SESSION['user_photo'] = $adminPhoto;
        }
    } catch (Exception $e) {}
}

$admin = [
    'nom'   => $_SESSION['user_nom'] ?? 'Admin',
    'role'  => 'Administrateur',
    'photo' => $adminPhoto,
];

// --- 1. Statistiques globales dynamiques ---
$stats = [
    'docteurs'       => ['valeur' => 0, 'delta' => '+0 ce mois'],
    'receptionnistes'=> ['valeur' => 0, 'delta' => '+0 ce mois'],
    'patientes'      => ['valeur' => 0, 'delta' => '+0 ce mois'],
    'consultations'  => ['valeur' => 0, 'delta' => '+0 ce mois'],
];

$debutMois = date('Y-m-01 00:00:00');

try {
    // Docteurs & Sages-Femmes
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('docteur', 'sage_femme') AND statut = 'actif'");
    $stats['docteurs']['valeur'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role IN ('docteur', 'sage_femme') AND date_creation >= ?");
    $stmt->execute([$debutMois]);
    $deltaDoc = (int)$stmt->fetchColumn();
    $stats['docteurs']['delta'] = ($deltaDoc >= 0 ? "+$deltaDoc" : "$deltaDoc") . " ce mois";

    // Réceptionnistes
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'receptionniste' AND statut = 'actif'");
    $stats['receptionnistes']['valeur'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'receptionniste' AND date_creation >= ?");
    $stmt->execute([$debutMois]);
    $deltaRec = (int)$stmt->fetchColumn();
    $stats['receptionnistes']['delta'] = ($deltaRec >= 0 ? "+$deltaRec" : "$deltaRec") . " ce mois";

    // Patientes
    $stmt = $pdo->query("SELECT COUNT(*) FROM patientes WHERE COALESCE(archivee, 0) = 0");
    $stats['patientes']['valeur'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM patientes WHERE COALESCE(archivee, 0) = 0 AND date_creation >= ?");
    $stmt->execute([$debutMois]);
    $deltaPat = (int)$stmt->fetchColumn();
    $stats['patientes']['delta'] = ($deltaPat >= 0 ? "+$deltaPat" : "$deltaPat") . " ce mois";

    // Consultations
    $stmt = $pdo->query("SELECT COUNT(*) FROM consultations");
    $stats['consultations']['valeur'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM consultations WHERE date_consultation >= ?");
    $stmt->execute([$debutMois]);
    $deltaCons = (int)$stmt->fetchColumn();
    $stats['consultations']['delta'] = ($deltaCons >= 0 ? "+$deltaCons" : "$deltaCons") . " ce mois";

} catch (Exception $e) {
    // Garde valeurs par défaut en cas d'erreur de requête BDD
}

// --- 2. Répartition dynamique des rôles et comptes ---
$repartitionRoles = [];
try {
    $stmt = $pdo->query("SELECT role, COUNT(*) as total FROM users GROUP BY role");
    $countsRoles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmtPat = $pdo->query("SELECT COUNT(*) FROM patientes WHERE COALESCE(archivee,0) = 0");
    $nbPatientes = (int)$stmtPat->fetchColumn();

    $nbDocteurs = ($countsRoles['docteur'] ?? 0) + ($countsRoles['sage_femme'] ?? 0);
    $nbReceptionnistes = $countsRoles['receptionniste'] ?? 0;
    $nbAdmins = $countsRoles['admin'] ?? 0;

    $grandTotal = $nbDocteurs + $nbReceptionnistes + $nbPatientes + $nbAdmins;
    if ($grandTotal === 0) $grandTotal = 1;

    $repartitionRoles = [
        ['label' => 'Docteurs / SF',    'valeur' => $nbDocteurs,        'pourcentage' => round(($nbDocteurs / $grandTotal) * 100),       'couleur' => '#7C3AED'],
        ['label' => 'Réceptionnistes',  'valeur' => $nbReceptionnistes, 'pourcentage' => round(($nbReceptionnistes / $grandTotal) * 100),'couleur' => '#EC4899'],
        ['label' => 'Patientes',        'valeur' => $nbPatientes,       'pourcentage' => round(($nbPatientes / $grandTotal) * 100),      'couleur' => '#60A5FA'],
        ['label' => 'Administrateurs',  'valeur' => $nbAdmins,          'pourcentage' => round(($nbAdmins / $grandTotal) * 100),         'couleur' => '#F59E0B'],
    ];
} catch (Exception $e) {
    $repartitionRoles = [
        ['label' => 'Docteurs / SF',    'valeur' => 0, 'pourcentage' => 0, 'couleur' => '#7C3AED'],
        ['label' => 'Réceptionnistes',  'valeur' => 0, 'pourcentage' => 0, 'couleur' => '#EC4899'],
        ['label' => 'Patientes',        'valeur' => 0, 'pourcentage' => 0, 'couleur' => '#60A5FA'],
    ];
}

// --- 3. Liste dynamique des utilisateurs récents ---
$utilisateurs = [];
try {
    $stmt = $pdo->query("SELECT id, nom, email, role, statut, photo, date_creation FROM users ORDER BY date_creation DESC");
    $dbUsers = $stmt->fetchAll();
    foreach ($dbUsers as $u) {
        $utilisateurs[] = [
            'raw_id'  => $u['id'],
            'id'      => 'USR-' . str_pad($u['id'], 3, '0', STR_PAD_LEFT),
            'nom'     => $u['nom'],
            'email'   => $u['email'],
            'role'    => ucfirst($u['role']),
            'service' => $u['email'],
            'statut'  => ucfirst($u['statut']),
            'photo'   => $u['photo'] ?? '',
        ];
    }
} catch (Exception $e) {
    $utilisateurs = [];
}

// --- 4. Alertes et Journal d'activité système ---
$alertes = [];
$nombreNotifications = 0;
$dernierAuditLu = (int)($_SESSION['dernier_audit_lu'] ?? 0);
try {
  $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE id > ?");
  $stmtCount->execute([$dernierAuditLu]);
  $nombreNotifications = (int)$stmtCount->fetchColumn();

  $stmt = $pdo->prepare("SELECT * FROM audit_log WHERE id > ? ORDER BY date_action DESC LIMIT 4");
  $stmt->execute([$dernierAuditLu]);
  $logs = $stmt->fetchAll();
    foreach ($logs as $l) {
        $alertes[] = [
            'titre'    => 'Action: ' . ucfirst($l['action']) . ' (' . htmlspecialchars($l['table_cible']) . ')',
            'detail'   => htmlspecialchars($l['details']) . ' — par ' . htmlspecialchars($l['utilisateur']),
            'tag'      => date('H:i', strtotime($l['date_action'])),
            'tagClass' => 'tag-info',
        ];
    }
} catch (Exception $e) {
    
}

if (empty($alertes)) {
    $alertes = [
        ['titre'=>'Système opérationnel','detail'=>'Aucune alerte récente enregistrée.','tag'=>'Info','tagClass'=>'tag-info'],
    ];
}

function initiales(string $nom): string {
    $mots = preg_split('/\s+/', trim($nom));
    $mots = array_filter($mots, fn($m) => !in_array(mb_strtolower($m), ['dr.', 'dr']));
    $mots = array_values($mots);
    $init = '';
    foreach (array_slice($mots, 0, 2) as $m) {
        $init .= mb_strtoupper(mb_substr($m, 0, 1));
    }
    return $init ?: 'U';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NatalGest — Tableau de bord Administrateur</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  :root{
    --violet-700:#6D28D9;
    --violet-600:#7C3AED;
    --violet-500:#8B5CF6;
    --violet-100:#EDE9FE;
    --violet-50:#F5F3FF;
    --rose-600:#EC4899;
    --rose-100:#FCE7F3;
    --bleu-500:#3B82F6;
    --bleu-100:#DBEAFE;
    --vert-600:#10B981;
    --vert-100:#D1FAE5;
    --ambre-600:#F59E0B;
    --ambre-100:#FEF3C7;
    --gris-900:#1F2937;
    --gris-600:#6B7280;
    --gris-300:#E5E7EB;
    --gris-100:#F3F4F6;
    --blanc:#FFFFFF;
    --rayon:16px;
    --ombre: 0 4px 20px rgba(109,40,217,0.07);
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  body{
    font-family:'Inter', sans-serif;
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.52) 0%, rgba(88, 28, 135, 0.42) 50%, rgba(15, 23, 42, 0.52) 100%), 
                url('assets/img/login_slides/slide1.png') center/cover no-repeat fixed;
    color:var(--gris-900);
    display:flex;
    min-height:100vh;
  }
  h1,h2,h3,.brand-name{font-family:'Poppins',sans-serif;}
  a{text-decoration:none; color:inherit;}
  ul{list-style:none;}

  
  .sidebar{
    width:270px;
    background:rgba(255, 255, 255, 0.93);
    backdrop-filter:blur(16px);
    -webkit-backdrop-filter:blur(16px);
    border-right:1px solid rgba(255, 255, 255, 0.6);
    box-shadow: 4px 0 25px rgba(0,0,0,0.15);
    padding:24px 18px;
    display:flex;
    flex-direction:column;
    position:sticky;
    top:0;
    height:100vh;
    overflow-y:auto;
  }
  .brand{display:flex; align-items:center; gap:12px; padding:0 6px 22px; border-bottom:1px solid var(--gris-100); margin-bottom:18px;}
  .brand-logo{
    width:44px; height:44px; border-radius:14px;
    background:linear-gradient(135deg,var(--violet-600),var(--rose-600));
    display:flex; align-items:center; justify-content:center; color:white; font-size:20px; flex-shrink:0;
  }
  .brand-name{font-weight:700; font-size:17px; color:var(--violet-700); line-height:1.1;}
  .brand-sub{font-size:11.5px; color:var(--gris-600); margin-top:2px;}

  .nav-section-title{
    font-size:11px; font-weight:700; letter-spacing:.06em; color:var(--gris-600);
    text-transform:uppercase; margin:20px 8px 8px;
  }
  .nav-item{
    display:flex; align-items:center; gap:12px;
    padding:10px 12px; border-radius:10px; font-size:14px; font-weight:500;
    color:var(--gris-900); margin-bottom:2px; transition:background .15s, color .15s;
  }
  .nav-item i{width:18px; text-align:center; color:var(--gris-600); font-size:15px;}
  .nav-item:hover{background:var(--violet-50);}
  .nav-item.active{background:linear-gradient(90deg,var(--violet-100),var(--rose-100)); color:var(--violet-700); font-weight:600;}
  .nav-item.active i{color:var(--violet-700);}

  .sidebar-footer{margin-top:auto; padding-top:14px; border-top:1px solid var(--gris-100);}
  .sidebar-collapse{display:flex; align-items:center; gap:10px; font-size:13.5px; color:var(--gris-600); padding:8px 12px;}
  .sidebar-collapse-text{font-size:0.9rem;}

  
  .main{flex:1; display:flex; flex-direction:column; min-width:0;}
  .topbar{
    background:rgba(255, 255, 255, 0.88);
    backdrop-filter:blur(14px);
    -webkit-backdrop-filter:blur(14px);
    border-bottom:1px solid rgba(229, 231, 235, 0.8);
    padding:16px 32px; display:flex; align-items:center; gap:20px; position:sticky; top:0; z-index:5;
  }
  .menu-toggle{color:var(--gris-600); font-size:18px; cursor:pointer;}
  .search-box{
    flex:1; max-width:420px; display:flex; align-items:center; gap:10px;
    background:rgba(243, 244, 246, 0.9); border-radius:10px; padding:8px 14px; color:var(--gris-600); font-size:13.5px;
    border:1px solid transparent; transition:border-color .2s, background .2s, box-shadow .2s;
  }
  .search-box:focus-within{
    background:var(--blanc);
    border-color:var(--violet-600);
    box-shadow:0 0 0 3px rgba(124,58,237,0.15);
  }
  .search-box input{
    border:none; background:transparent; outline:none; width:100%;
    font-size:13.5px; font-family:inherit; color:var(--gris-900);
  }
  .topbar-right{margin-left:auto; display:flex; align-items:center; gap:22px;}
  .notif-bell{position:relative; font-size:18px; color:var(--gris-600); cursor:pointer; padding:6px; transition:color .15s;}
  .notif-bell:hover{color:var(--violet-600);}
  .notif-badge{
    position:absolute; top:-2px; right:-4px; background:var(--rose-600); color:white;
    font-size:10px; font-weight:700; border-radius:50%; width:17px; height:17px;
    display:flex; align-items:center; justify-content:center;
  }
  .profile-dropdown-wrapper, .notif-dropdown-wrapper{ position:relative; }
  .profile{display:flex; align-items:center; gap:10px; cursor:pointer; padding:4px 8px; border-radius:10px; transition:background .15s;}
  .profile:hover{background:var(--violet-50);}
  .profile img{width:40px; height:40px; border-radius:50%; object-fit:cover;}
  .profile-name{font-size:13.5px; font-weight:600;}
  .profile-role{font-size:11.5px; color:var(--gris-600);}

  .dropdown-menu-custom{
    position:absolute; top:calc(100% + 12px); right:0; width:220px;
    background:rgba(255, 255, 255, 0.95); backdrop-filter:blur(16px);
    border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.12);
    border:1px solid var(--gris-300); padding:8px 0; z-index:100;
    display:none; flex-direction:column;
    animation: fadeInDropdown .15s ease-out;
  }
  .dropdown-menu-custom.show{ display:flex; }
  .dropdown-menu-custom a, .dropdown-menu-custom div.item{
    padding:10px 16px; font-size:13.5px; color:var(--gris-900);
    display:flex; align-items:center; gap:10px; transition:background .15s, color .15s;
    cursor:pointer; text-decoration:none;
  }
  .dropdown-menu-custom a:hover, .dropdown-menu-custom div.item:hover{
    background:var(--violet-50); color:var(--violet-700);
  }
  .dropdown-menu-custom .divider{
    height:1px; background:var(--gris-100); margin:4px 0;
  }
  @keyframes fadeInDropdown {
    from { opacity:0; transform:translateY(-8px); }
    to { opacity:1; transform:translateY(0); }
  }

  .content{padding:28px 32px 48px; display:flex; flex-direction:column; gap:24px;}

  
  .hero{
    background:linear-gradient(120deg, rgba(237,233,254,0.92) 0%, rgba(252,231,243,0.92) 100%);
    backdrop-filter:blur(10px);
    border-radius:var(--rayon);
    padding:30px 34px;
    display:flex; align-items:center; justify-content:space-between; gap:24px;
    overflow:hidden; position:relative;
    border:1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 8px 30px rgba(109, 40, 217, 0.08);
  }
  .hero-text h1{font-size:24px; color:var(--violet-700); margin-bottom:6px;}
  .hero-text p{font-size:14px; color:var(--gris-600); max-width:460px;}
  .hero-icon{
    width:120px; height:120px; border-radius:50%;
    background:linear-gradient(135deg,var(--violet-600),var(--rose-600));
    display:flex; align-items:center; justify-content:center; color:white; font-size:52px; flex-shrink:0;
    box-shadow: 0 10px 25px rgba(124, 58, 237, 0.25);
  }

  
  .stats-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:20px;}
  .stat-card{
    background:rgba(255, 255, 255, 0.9);
    backdrop-filter:blur(12px);
    border-radius:var(--rayon); padding:20px; box-shadow:var(--ombre);
    border:1px solid rgba(255, 255, 255, 0.9);
    display:flex; flex-direction:column; gap:10px;
    transition:transform .2s, box-shadow .2s;
  }
  .stat-card:hover{
    transform:translateY(-3px);
    box-shadow:0 12px 30px rgba(109,40,217,0.12);
  }
  .stat-icon{
    width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; color:white;
  }
  .stat-icon.violet{background:var(--violet-600);}
  .stat-icon.rose{background:var(--rose-600);}
  .stat-icon.bleu{background:var(--bleu-500);}
  .stat-icon.vert{background:var(--vert-600);}
  .stat-label{font-size:13px; color:var(--gris-600);}
  .stat-value{font-size:26px; font-weight:700;}
  .stat-delta{font-size:12px; color:var(--vert-600); font-weight:600;}

  /* ---------- QUICK ACTIONS ---------- */
  .section-card{
    background:rgba(255, 255, 255, 0.9);
    backdrop-filter:blur(12px);
    border-radius:var(--rayon); padding:24px; box-shadow:var(--ombre);
    border:1px solid rgba(255, 255, 255, 0.9);
  }
  .section-head{display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;}
  .section-head h2{font-size:16.5px;}
  .section-head a{font-size:13px; color:var(--violet-600); font-weight:600;}

  .actions-grid{display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:16px;}
  .action-btn{
    display:flex; flex-direction:column; align-items:center; gap:10px; text-align:center;
    padding:20px 12px; border-radius:14px; border:1px solid var(--gris-100); background:var(--gris-100);
    cursor:pointer; transition:transform .15s, box-shadow .15s, background .15s;
  }
  .action-btn:hover{transform:translateY(-3px); box-shadow:0 8px 18px rgba(124,58,237,.12); background:var(--violet-50);}
  .action-icon{
    width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:19px; color:white;
  }
  .action-label{font-size:13px; font-weight:600; line-height:1.3;}

  
  .split{display:grid; grid-template-columns:2fr 1fr; gap:24px; align-items:start;}

  table{width:100%; border-collapse:collapse;}
  thead th{
    text-align:left; font-size:12px; text-transform:uppercase; letter-spacing:.04em;
    color:var(--gris-600); padding:10px 12px; border-bottom:1px solid var(--gris-100);
  }
  tbody td{padding:14px 12px; font-size:13.5px; border-bottom:1px solid var(--gris-100); vertical-align:middle;}
  tbody tr:last-child td{border-bottom:none;}
  .user-cell{display:flex; align-items:center; gap:10px;}
  .avatar{width:36px; height:36px; border-radius:50%; object-fit:cover;}
  .avatar-fallback{
    width:36px; height:36px; border-radius:50%; background:var(--violet-100); color:var(--violet-700);
    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px;
  }
  .user-name{font-weight:600; font-size:13.5px;}
  .user-id{font-size:11.5px; color:var(--gris-600);}
  .role-pill{
    display:inline-flex; padding:4px 10px; border-radius:20px; font-size:11.5px; font-weight:600;
  }
  .role-docteur{background:var(--violet-100); color:var(--violet-700);}
  .role-receptionniste{background:var(--rose-100); color:#BE185D;}
  .tag{display:inline-flex; padding:4px 10px; border-radius:20px; font-size:11.5px; font-weight:600;}
  .tag-actif{background:var(--vert-100); color:#047857;}
  .tag-suspendu{background:#FEE2E2; color:#B91C1C;}
  .tag-warning{background:var(--ambre-100); color:#B45309;}
  .tag-info{background:var(--bleu-100); color:#1D4ED8;}
  .row-actions{display:flex; gap:10px; font-size:14px; color:var(--gris-600);}
  .row-actions i{cursor:pointer; transition:color .15s;}
  .row-actions i:hover{color:var(--violet-600);}
  .row-actions i.danger:hover{color:#DC2626;}

  /* Alerts panel */
  .alert-item{display:flex; gap:12px; padding:12px 0; border-bottom:1px solid var(--gris-100);}
  .alert-item:last-child{border-bottom:none;}
  .alert-icon{
    width:38px; height:38px; border-radius:10px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:15px;
    background:var(--violet-100); color:var(--violet-700);
  }
  .alert-title{font-size:13.5px; font-weight:600;}
  .alert-detail{font-size:12px; color:var(--gris-600); margin-top:2px;}
  .alert-tag-wrap{margin-top:6px;}

  .repartition-item{display:flex; align-items:center; justify-content:space-between; padding:10px 0;}
  .repartition-left{display:flex; align-items:center; gap:10px; font-size:13.5px;}
  .dot{width:10px; height:10px; border-radius:50%;}
  .bar-bg{background:var(--gris-100); border-radius:8px; height:8px; width:100%; margin-top:14px; overflow:hidden; display:flex;}

  .btn-primary{
    background:linear-gradient(90deg,var(--violet-600),var(--rose-600)); color:white; border:none;
    padding:10px 18px; border-radius:10px; font-size:13.5px; font-weight:600; cursor:pointer;
  }

  @media (max-width:1100px){
    .stats-grid{grid-template-columns:repeat(2,1fr);}
    .actions-grid{grid-template-columns:repeat(3,1fr);}
    .split{grid-template-columns:1fr;}
  }
  @media (max-width:720px){
    .sidebar{display:none;}
    .actions-grid{grid-template-columns:repeat(2,1fr);}
    .stats-grid{grid-template-columns:1fr;}
  }
</style>
</head>
<body>


<aside class="sidebar">
  <div class="brand">
    <div class="brand-logo"><i class="fa-solid fa-baby-carriage"></i></div>
    <div>
      <div class="brand-name">NatalGest</div>
      <div class="brand-sub">Suivi prénatal &amp; postnatal</div>
    </div>
  </div>

  <nav>
    <a href="admin_dashboard.php" class="nav-item active"><i class="fa-solid fa-house"></i> Tableau de bord</a>

    <div class="nav-section-title">Gestion des utilisateurs</div>
    <a href="admin/gestion_utilisateurs.php" class="nav-item"><i class="fa-solid fa-users-gear"></i> Tous les utilisateurs</a>
    <a href="admin/creer_utilisateur.php" class="nav-item"><i class="fa-solid fa-user-plus"></i> Créer un utilisateur</a>

    <div class="nav-section-title">Suivi Médical</div>
    <a href="tableau_bord_sf.php" class="nav-item"><i class="fa-solid fa-calendar-days"></i> Rendez-vous & RDV</a>
    <a href="dossiers_sf.php" class="nav-item"><i class="fa-solid fa-folder-open"></i> Dossiers patientes</a>
    <a href="sage_femme.php" class="nav-item"><i class="fa-solid fa-stethoscope"></i> Consultation / File</a>

    <div class="nav-section-title">Système</div>
    <a href="accueil.php" class="nav-item"><i class="fa-solid fa-circle-info"></i> Vue d'accueil</a>

    <div class="nav-section-title">Analyses</div>
    <a href="rapports.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> Rapports &amp; Statistiques</a>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php" class="sidebar-collapse">
      <i class="fa-solid fa-right-to-bracket"></i>
      <span class="sidebar-collapse-text">Déconnexion</span>
    </a>
  </div>
</aside>


<div class="main">

  
  <header class="topbar">
    <i class="fa-solid fa-bars menu-toggle" id="btnToggleSidebar" title="Afficher / Masquer le menu"></i>
    <div class="search-box">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="searchInput" placeholder="Rechercher un utilisateur, un rôle, un email..." autocomplete="off">
    </div>
    <div class="topbar-right">
      
      <!-- Notifications -->
      <div class="notif-dropdown-wrapper">
        <div class="notif-bell" id="btnNotifToggle" title="Alertes et notifications">
          <i class="fa-regular fa-bell"></i>
          <?php if ($nombreNotifications > 0): ?>
            <span class="notif-badge" id="notifBadge"><?= $nombreNotifications ?></span>
          <?php endif; ?>
        </div>
        <div class="dropdown-menu-custom" id="notifDropdown" style="width:290px;">
          <div style="padding:10px 16px; font-weight:700; font-size:13px; border-bottom:1px solid var(--gris-100); color:var(--violet-700);">
            <i class="fa-solid fa-bell me-1"></i> Notifications récentes
          </div>
          <?php foreach ($alertes as $a): ?>
            <div class="item" style="flex-direction:column; align-items:flex-start; gap:2px; font-size:12px;">
              <span style="font-weight:600; color:var(--gris-900);"><?= htmlspecialchars($a['titre']) ?></span>
              <span style="color:var(--gris-600);"><?= htmlspecialchars($a['detail']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Profile -->
      <div class="profile-dropdown-wrapper">
        <div class="profile" id="btnProfileToggle" title="Menu utilisateur">
          <?php if (!empty($admin['photo'])): ?>
            <img src="<?= htmlspecialchars($admin['photo']) ?>" alt="Photo de profil">
          <?php else: ?>
            <div class="avatar-fallback" style="width:40px; height:40px; font-size:15px; background:var(--violet-100); color:var(--violet-700); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700;">
              <?= initiales($admin['nom']) ?>
            </div>
          <?php endif; ?>
          <div>
            <div class="profile-name"><?= htmlspecialchars($admin['nom']) ?></div>
            <div class="profile-role"><?= htmlspecialchars($admin['role']) ?></div>
          </div>
          <i class="fa-solid fa-chevron-down" style="color:var(--gris-600); font-size:12px; margin-left:4px;"></i>
        </div>
        <div class="dropdown-menu-custom" id="profileDropdown">
          <a href="admin/gestion_utilisateurs.php"><i class="fa-solid fa-users-gear"></i> Tous les utilisateurs</a>
          <a href="admin/creer_utilisateur.php"><i class="fa-solid fa-user-plus"></i> Créer un utilisateur</a>
          <div class="divider"></div>
          <a href="tableau_bord_sf.php"><i class="fa-solid fa-calendar-days"></i> Rendez-vous & RDV</a>
          <a href="dossiers_sf.php"><i class="fa-solid fa-folder-open"></i> Dossiers patientes</a>
          <div class="divider"></div>
          <a href="logout.php" style="color:#DC2626;"><i class="fa-solid fa-right-to-bracket"></i> Déconnexion</a>
        </div>
      </div>

    </div>
  </header>

  <div class="content">

    
    <section class="hero">
      <div class="hero-text">
        <h1>Bonjour, <?= htmlspecialchars(explode(' ', $admin['nom'])[0] ?? 'Admin') ?></h1>
        <p>Vous gérez ici l'ensemble des comptes, des patientes et des paramètres de la plateforme NatalGest.</p>
      </div>
      <div class="hero-icon"><i class="fa-solid fa-shield-halved"></i></div>
    </section>

    
    <section class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon violet"><i class="fa-solid fa-user-doctor"></i></div>
        <div class="stat-label">Docteurs</div>
        <div class="stat-value"><?= $stats['docteurs']['valeur'] ?></div>
        <div class="stat-delta"><?= htmlspecialchars($stats['docteurs']['delta']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon rose"><i class="fa-solid fa-user-tie"></i></div>
        <div class="stat-label">Réceptionnistes</div>
        <div class="stat-value"><?= $stats['receptionnistes']['valeur'] ?></div>
        <div class="stat-delta"><?= htmlspecialchars($stats['receptionnistes']['delta']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon bleu"><i class="fa-solid fa-person-pregnant"></i></div>
        <div class="stat-label">Total patientes</div>
        <div class="stat-value"><?= $stats['patientes']['valeur'] ?></div>
        <div class="stat-delta"><?= htmlspecialchars($stats['patientes']['delta']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon vert"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="stat-label">Consultations (total)</div>
        <div class="stat-value"><?= $stats['consultations']['valeur'] ?></div>
        <div class="stat-delta"><?= htmlspecialchars($stats['consultations']['delta']) ?></div>
      </div>
    </section>

    <!-- QUICK ACTIONS -->
    <section class="section-card">
      <div class="section-head">
        <h2>Actions rapides</h2>
      </div>
      <div class="actions-grid">
        <a href="admin/gestion_utilisateurs.php" class="action-btn">
          <div class="action-icon" style="background:var(--violet-600);"><i class="fa-solid fa-users-gear"></i></div>
          <div class="action-label">Gérer tous les utilisateurs</div>
        </a>
        <a href="admin/creer_utilisateur.php" class="action-btn">
          <div class="action-icon" style="background:var(--rose-600);"><i class="fa-solid fa-user-doctor"></i></div>
          <div class="action-label">Créer utilisateur</div>
        </a>
        <a href="admin/modifier_utilisateur.php" class="action-btn">
          <div class="action-icon" style="background:var(--bleu-500);"><i class="fa-solid fa-user-tie"></i></div>
          <div class="action-label">Modifier utilisateur</div>
        </a>
        <a href="admin/activer_desactiver.php" class="action-btn">
          <div class="action-icon" style="background:var(--vert-600);"><i class="fa-solid fa-person-pregnant"></i></div>
          <div class="action-label">Activer/Désactiver</div>
        </a>
        <a href="admin/supprimer.php" class="action-btn">
          <div class="action-icon" style="background:var(--vert-600);"><i class="fa-solid fa-person-pregnant"></i></div>
          <div class="action-label">Supprimer</div>
        </a>
        <a href="rapports.php" class="action-btn">
          <div class="action-icon" style="background:var(--ambre-600);"><i class="fa-solid fa-chart-bar"></i></div>
          <div class="action-label">Rapports & Stats</div>
        </a>
        <a href="#" class="action-btn">
          <div class="action-icon" style="background:#0F766E;"><i class="fa-solid fa-hospital"></i></div>
          <div class="action-label">Paramètres</div>
        </a>
      </div>
    </section>

    <section class="split">

      <!-- Users table -->
      <div class="section-card">
        <div class="section-head">
          <h2>Comptes utilisateurs (docteurs &amp; réceptionnistes)</h2>
          <a href="admin/gestion_utilisateurs.php">Voir tous</a>
        </div>
        <table>
          <thead>
            <tr>
              <th>Utilisateur</th>
              <th>Rôle</th>
              <th>Service</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($utilisateurs)): ?>
              <tr>
                <td colspan="5" style="text-align:center; color:var(--gris-600); padding:20px;">
                  Aucun utilisateur trouvé. <a href="admin/creer_utilisateur.php" style="color:var(--violet-600); font-weight:600;">Créer un compte</a>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($utilisateurs as $u): ?>
              <tr>
                <td>
                  <div class="user-cell">
                    <?php if (!empty($u['photo'])): ?>
                      <img src="<?= htmlspecialchars($u['photo']) ?>" class="avatar" alt="">
                    <?php else: ?>
                      <div class="avatar-fallback"><?= initiales($u['nom']) ?></div>
                    <?php endif; ?>
                    <div>
                      <div class="user-name"><?= htmlspecialchars($u['nom']) ?></div>
                      <div class="user-id">ID: <?= htmlspecialchars($u['id']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="role-pill <?= in_array(strtolower($u['role']), ['docteur', 'sage_femme']) ? 'role-docteur' : 'role-receptionniste' ?>">
                    <?= htmlspecialchars($u['role']) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($u['service']) ?></td>
                <td>
                  <span class="tag <?= strtolower($u['statut']) === 'actif' ? 'tag-actif' : 'tag-suspendu' ?>">
                    <?= htmlspecialchars($u['statut']) ?>
                  </span>
                </td>
                <td>
                  <div class="row-actions">
                    <a href="admin/modifier_utilisateur.php?id=<?= (int)$u['raw_id'] ?>" title="Modifier">
                      <i class="fa-solid fa-pen"></i>
                    </a>
                    <?php if (strtolower($u['role']) !== 'admin' && strtolower($u['role']) !== 'administrateur'): ?>
                      <form action="admin/activer_desactiver.php" method="POST" style="display:inline;" onsubmit="return confirm('Confirmer l\'action sur cet utilisateur ?');">
                        <input type="hidden" name="id" value="<?= (int)$u['raw_id'] ?>">
                        <button type="submit" style="background:none; border:none; padding:0; cursor:pointer;" title="Activer / Désactiver">
                          <i class="fa-solid fa-power-off danger"></i>
                        </button>
                      </form>
                      <form action="admin/supprimer.php" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?');">
                        <input type="hidden" name="id" value="<?= (int)$u['raw_id'] ?>">
                        <button type="submit" style="background:none; border:none; padding:0; cursor:pointer;" title="Supprimer">
                          <i class="fa-solid fa-trash danger"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!--repartition + alerts -->
      <div style="display:flex; flex-direction:column; gap:24px;">

        <div class="section-card">
          <div class="section-head"><h2>Répartition des comptes</h2></div>
          <?php foreach ($repartitionRoles as $r): ?>
            <div class="repartition-item">
              <div class="repartition-left">
                <span class="dot" style="background:<?= $r['couleur'] ?>;"></span>
                <?= htmlspecialchars($r['label']) ?>
              </div>
              <strong><?= $r['valeur'] ?></strong>
            </div>
          <?php endforeach; ?>
          <div class="bar-bg">
            <?php foreach ($repartitionRoles as $r): ?>
              <div style="width:<?= $r['pourcentage'] ?>%; background:<?= $r['couleur'] ?>;"></div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="section-card">
          <div class="section-head">
            <h2>Alertes système</h2>
            <a href="#">Voir tout</a>
          </div>
          <?php foreach ($alertes as $a): ?>
            <div class="alert-item">
              <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
              <div>
                <div class="alert-title"><?= htmlspecialchars($a['titre']) ?></div>
                <div class="alert-detail"><?= htmlspecialchars($a['detail']) ?></div>
                <div class="alert-tag-wrap"><span class="tag <?= $a['tagClass'] ?>"><?= htmlspecialchars($a['tag']) ?></span></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    </section>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1.Profil Administrateur
    const btnProfile = document.getElementById('btnProfileToggle');
    const profileDropdown = document.getElementById('profileDropdown');
    const notifDropdown = document.getElementById('notifDropdown');
    
    if (btnProfile && profileDropdown) {
        btnProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            if (notifDropdown) notifDropdown.classList.remove('show');
            profileDropdown.classList.toggle('show');
        });
    }

    // 2.Notifications & Alertes
    const btnNotif = document.getElementById('btnNotifToggle');
    const notifBadge = document.getElementById('notifBadge');
    if (btnNotif && notifDropdown) {
        btnNotif.addEventListener('click', function(e) {
            e.stopPropagation();
            if (profileDropdown) profileDropdown.classList.remove('show');
            notifDropdown.classList.toggle('show');
        if (notifBadge) {
          fetch('admin/marquer_notifications_lues.php', {method: 'POST', credentials: 'same-origin'});
          notifBadge.remove();
        }
        });
    }

   
    document.addEventListener('click', function() {
        if (profileDropdown) profileDropdown.classList.remove('show');
        if (notifDropdown) notifDropdown.classList.remove('show');
    });

    // 3. Barre de recherche dynamique en temps réel
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('table tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    
    const btnToggleSidebar = document.getElementById('btnToggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    if (btnToggleSidebar && sidebar) {
        btnToggleSidebar.addEventListener('click', function() {
            if (window.getComputedStyle(sidebar).display === 'none') {
                sidebar.style.display = 'flex';
            } else {
                sidebar.style.display = 'none';
            }
        });
    }
});

// Empêche l'accès via le bouton retour du navigateur après déconnexion
window.addEventListener('pageshow', function (event) {
    if (event.persisted || (performance.navigation && performance.navigation.type === 2)) {
        window.location.reload();
    }
});
</script>

</body>
</html>
