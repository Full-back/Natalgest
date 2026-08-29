<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Si déjà connecté, on redirige directement vers son tableau de bord
if (isset($_SESSION['user_id'])) {
    redirectToDashboard($_SESSION['user_role']);
}

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = clean($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    if ($email === '' || $motDePasse === '') {
        $erreur = "Veuillez renseigner l'email et le mot de passe.";
    } else {
        
        $stmt = $mysqli->prepare(
            "SELECT id, nom, email, mot_de_passe, role, statut FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $erreur = "Email ou mot de passe incorrect.";
        } elseif ($user['statut'] === 'desactive') {
            $erreur = "Ce compte a été désactivé. Contactez l'administrateur.";
        } elseif (!password_verify($motDePasse, $user['mot_de_passe'])) {
            $erreur = "Email ou mot de passe incorrect.";
        } else {
            // Authentification réussie
            session_regenerate_id(true);
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['user_nom']       = $user['nom'];
            $_SESSION['user_role']      = $user['role'];
            $_SESSION['last_activity']  = time();

            redirectToDashboard($user['role']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion — NatalGest (CPN & CPoN)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Fonts et Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --violet-primary: #7C3AED;
            --violet-hover: #6D28D9;
            --rose-accent: #EC4899;
            --dark-overlay: rgba(15, 23, 42, 0.65);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body.login-page-v2 {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            background: #0f172a;
        }

        
        .bg-slider-container {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .bg-slide {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transform: scale(1);
            transition: opacity 1.8s ease-in-out, transform 8s ease-out;
        }

        .bg-slide.active {
            opacity: 1;
            transform: scale(1.05);
        }


        .bg-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 2;
            background: linear-gradient(135deg, 
                rgba(15, 23, 42, 0.45) 0%, 
                rgba(88, 28, 135, 0.32) 50%, 
                rgba(190, 24, 93, 0.28) 100%);
            backdrop-filter: blur(1px);
        }

        
        .login-wrapper {
            position: relative;
            z-index: 3;
            width: 100%;
            max-width: 1100px;
            padding: 24px;
        }

        .login-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            background: transparent;
            border: none;
            border-radius: 28px;
            overflow: hidden;
            gap: 0;
        }

        
        .brand-banner {
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: transparent;
            border-right: none;
            color: #ffffff;
        }

        .brand-logo-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 52px; height: 52px;
            border-radius: 16px;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: white;
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4);
        }

        .brand-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            font-size: 26px;
            letter-spacing: -0.02em;
            background: linear-gradient(90deg, #FFFFFF, #FCE7F3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-sub {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
        }

        .hero-captions {
            margin: 40px 0;
        }

        .hero-caption-item {
            display: none;
            animation: fadeInCaption 0.8s ease forwards;
        }

        .hero-caption-item.active {
            display: block;
        }

        @keyframes fadeInCaption {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            font-size: 12px;
            font-weight: 600;
            color: #F3E8FF;
            margin-bottom: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero-caption-item h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 12px;
            color: #ffffff;
        }

        .hero-caption-item p {
            font-size: 14.5px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }

    
        .slider-dots {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.4s ease;
        }

        .dot.active {
            width: 32px;
            border-radius: 12px;
            background: linear-gradient(90deg, #A855F7, #EC4899);
        }

        /* ---------- RIGHT FORM CARD ---------- */
        .form-panel {
            padding: 56px 44px;
            background: transparent;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Textes du formulaire adaptés au fond transparent */
        .form-header h3 {
            color: #ffffff !important;
            text-shadow: 0 2px 10px rgba(0,0,0,0.9);
        }
        .form-header p {
            color: rgba(255,255,255,0.90) !important;
            text-shadow: 0 1px 6px rgba(0,0,0,0.8);
        }
        .input-group-custom label {
            color: rgba(255,255,255,0.95) !important;
            text-shadow: 0 1px 4px rgba(0,0,0,0.7);
        }
        .form-footer-text {
            color: rgba(255,255,255,0.90) !important;
            text-shadow: 0 1px 5px rgba(0,0,0,0.8);
        }

        .form-header h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 24px;
            color: #1E1B4B;
            margin-bottom: 6px;
        }

        .form-header p {
            font-size: 13.5px;
            color: #64748B;
            margin-bottom: 28px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group-custom label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i.field-icon {
            position: absolute;
            left: 14px;
            color: #94A3B8;
            font-size: 15px;
            transition: color 0.2s;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            border-radius: 12px;
            border: 1.5px solid #E2E8F0;
            background: #F8FAFC;
            font-size: 14px;
            color: #0F172A;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        .input-wrapper input:focus {
            border-color: var(--violet-primary);
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.12);
        }

        .input-wrapper input:focus + i.field-icon {
            color: var(--violet-primary);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            cursor: pointer;
            color: #94A3B8;
            font-size: 14px;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: var(--violet-primary);
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(90deg, #7C3AED, #EC4899);
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(124, 58, 237, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .form-footer-text {
            font-size: 12.5px;
            color: #64748B;
            text-align: center;
            margin-top: 24px;
        }

        @media (max-width: 920px) {
            .login-grid { grid-template-columns: 1fr; }
            .brand-banner { display: none; }
            .form-panel { padding: 40px 28px; }
        }
    </style>
</head>
<body class="login-page-v2">

    
    <div class="bg-slider-container">
        <div class="bg-slide active" style="background-image: url('assets/img/login_slides/slide1.png');"></div>
        <div class="bg-slide" style="background-image: url('assets/img/login_slides/slide2.png');"></div>
        <div class="bg-slide" style="background-image: url('assets/img/login_slides/slide3.png');"></div>
    </div>
    <div class="bg-overlay"></div>

    
    <div class="login-wrapper">
        <div class="login-grid">

            
            <div class="brand-banner">
                <div class="brand-logo-wrap">
                    <div class="brand-icon">
                        <i class="fa-solid fa-baby-carriage"></i>
                    </div>
                    <div>
                        <div class="brand-title">NatalGest</div>
                        <div class="brand-sub">Suivi Prénatal &amp; Postnatal (CPN &amp; CPoN)</div>
                    </div>
                </div>

                
                <div class="hero-captions">
                    <div class="hero-caption-item active" data-slide="0">
                        <div class="hero-tag"><i class="fa-solid fa-user-doctor"></i> Équipe Médicale Spécialisée</div>
                        <h2>Un suivi gynécologique et obstétrique d'excellence</h2>
                        <p>Plateforme dédiée au corps médical, aux docteurs et aux sages-femmes pour la prise en charge personnalisée de chaque patiente.</p>
                    </div>

                    <div class="hero-caption-item" data-slide="1">
                        <div class="hero-tag"><i class="fa-solid fa-person-pregnant"></i> Consultations Prénatales (CPN)</div>
                        <h2>Accompagnement attentif de la grossesse</h2>
                        <p>Planification des rendez-vous, examens ciblés et suivi de l'évolution de la maman en toute sécurité.</p>
                    </div>

                    <div class="hero-caption-item" data-slide="2">
                        <div class="hero-tag"><i class="fa-solid fa-baby"></i> Suivi Postnatal (CPoN) &amp; Nouveau-né</div>
                        <h2>Protection du nouveau-né &amp; de la jeune maman</h2>
                        <p>Suivi des vaccins, contrôles pédiatriques et tableau de bord complet des étapes de croissance de l'enfant.</p>
                    </div>
                </div>

                
                <div class="slider-dots">
                    <div class="dot active" onclick="goToSlide(0)"></div>
                    <div class="dot" onclick="goToSlide(1)"></div>
                    <div class="dot" onclick="goToSlide(2)"></div>
                </div>
            </div>

        
            <div class="form-panel">
                <div class="form-header">
                    <h3>Espace de connexion</h3>
                    <p>Veuillez saisir vos identifiants pour accéder à votre espace de travail.</p>
                </div>

                <?php if (isset($_GET['expired'])): ?>
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> Session expirée par inactivité. Veuillez vous reconnecter.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['logged_out'])): ?>
                    <div class="alert alert-success py-2 small mb-3">
                        <i class="fa-solid fa-circle-check me-1"></i> Vous êtes bien déconnecté.
                    </div>
                <?php endif; ?>

                <?php if ($erreur): ?>
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($erreur) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <div class="input-group-custom">
                        <label for="email">Adresse email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" placeholder="ex: admin@natalgest.com" required autofocus>
                            <i class="fa-regular fa-envelope field-icon"></i>
                        </div>
                    </div>

                    <div class="input-group-custom">
                        <label for="mot_de_passe">Mot de passe</label>
                        <div class="input-wrapper">
                            <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="••••••••" required>
                            <i class="fa-solid fa-lock field-icon"></i>
                            <i class="fa-regular fa-eye toggle-password" id="togglePasswordBtn"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        Se connecter <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </form>

                <p class="form-footer-text">
                    <i class="fa-solid fa-shield-halved me-1" style="color:var(--violet-primary);"></i>
                    Accès sécurisé réservé au personnel habilité.
                </p>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script>
        
        const slides = document.querySelectorAll('.bg-slide');
        const captions = document.querySelectorAll('.hero-caption-item');
        const dots = document.querySelectorAll('.dot');
        let currentSlide = 0;
        const totalSlides = slides.length;
        let slideInterval;

        function showSlide(index) {
            slides.forEach(s => s.classList.remove('active'));
            captions.forEach(c => c.classList.remove('active'));
            dots.forEach(d => d.classList.remove('active'));

            slides[index].classList.add('active');
            captions[index].classList.add('active');
            dots[index].classList.add('active');
            currentSlide = index;
        }

        function nextSlide() {
            let nextIndex = (currentSlide + 1) % totalSlides;
            showSlide(nextIndex);
        }

        function goToSlide(index) {
            clearInterval(slideInterval);
            showSlide(index);
            slideInterval = setInterval(nextSlide, 5000);
        }

        // Lancement automatique toutes les 5 secondes
        slideInterval = setInterval(nextSlide, 5000);

        // Toggle Affichage / Masquage du mot de passe
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('mot_de_passe');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function() {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Verrouillage Anti-retour du navigateur après déconnexion
        if (window.history && window.history.pushState) {
            window.history.pushState(null, null, window.location.href);
            window.onpopstate = function () {
                window.history.pushState(null, null, window.location.href);
            };
        }
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (performance.navigation && performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>

</body>
</html>
