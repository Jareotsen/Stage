<?php
session_start();
require '../../config/database.php';

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        switch ($user['role']) {
            case 'super_admin':
                header('Location: ../super_admin/dashboard.php');
                exit;

            case 'point_focal':
                header('Location: ../point_focal/dashboard.php');
                exit;

            case 'agent_ministere':
                header('Location: ../agent_ministere/dashboard.php');
                exit;

            default:
                $erreur = "Rôle utilisateur inconnu.";
        }
    } else {
        $erreur = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>

<style>

/* =========================================================
   PALETTE MRRI
========================================================= */

:root {
    --bleu-roi: #1B3FAE;
    --bleu-fonce: #102A73;

    --vert-irlandais: #009A44;
    --vert-fonce: #006B30;

    --jaune-or: #D4AF37;
    --jaune-clair: #F4E7B5;

    --blanc: #FFFFFF;
    --blanc-casse: #F7F9FC;

    --texte: #172033;
    --gris: #758096;

    --font: "Public Sans", Arial, sans-serif;
}


/* =========================================================
   RESET
========================================================= */

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
}

body {
    font-family: var(--font);
}


/* =========================================================
   PAGE
========================================================= */

.login-page {
    min-height: 100vh;

    display: flex;

    position: relative;
    overflow: hidden;

    background: var(--vert-irlandais);
}


/* =========================================================
   PARTIE GAUCHE
========================================================= */

.login-visual {
    width: 62%;
    min-height: 100vh;

    position: relative;

    display: flex;
    align-items: center;

    padding: 55px 8% 55px 7%;

    background: var(--blanc);

    overflow: hidden;

    /*
       Forme organique principale
    */
    clip-path: ellipse(72% 85% at 28% 50%);

    z-index: 2;
}


/* =========================================================
   FORMES DÉCORATIVES GAUCHE
========================================================= */

.login-visual::before {
    content: "";

    position: absolute;

    width: 420px;
    height: 420px;

    left: -170px;
    top: 80px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(0, 154, 68, 0.13),
            rgba(0, 154, 68, 0.04) 55%,
            transparent 70%
        );
}


.login-visual::after {
    content: "";

    position: absolute;

    width: 180px;
    height: 180px;

    right: 20%;
    bottom: 12%;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(27, 63, 174, 0.08),
            transparent 70%
        );
}


/* =========================================================
   PETITS CERCLES DÉCORATIFS
========================================================= */

.decor-circle {
    position: absolute;

    border-radius: 50%;

    pointer-events: none;
}

.decor-circle.one {
    width: 55px;
    height: 55px;

    top: 17%;
    left: 27%;

    background: rgba(0, 154, 68, 0.07);
}

.decor-circle.two {
    width: 32px;
    height: 32px;

    top: 26%;
    left: 12%;

    background: rgba(27, 63, 174, 0.08);
}

.decor-circle.three {
    width: 70px;
    height: 70px;

    bottom: 18%;
    left: 31%;

    background: rgba(212, 175, 55, 0.10);
}

.decor-circle.four {
    width: 25px;
    height: 25px;

    bottom: 28%;
    right: 23%;

    background: rgba(0, 154, 68, 0.10);
}


/* =========================================================
   CONTENU GAUCHE
========================================================= */

.visual-content {
    position: relative;

    z-index: 5;

    width: 100%;
    max-width: 470px;
}


/* =========================================================
   LOGO
========================================================= */

.brand-logo {
    width: 92px;
    height: 92px;

    display: flex;
    align-items: center;
    justify-content: center;

    margin-bottom: 30px;

    background: white;

    border-radius: 20px;

    box-shadow:
        0 12px 35px rgba(27, 63, 174, 0.12);

    border: 1px solid #edf0f5;
}

.brand-logo img {
    width: 78px;
    height: 78px;

    object-fit: contain;
}


/* =========================================================
   IDENTITÉ
========================================================= */

.brand-kicker {
    display: flex;
    align-items: center;
    gap: 9px;

    margin-bottom: 15px;

    color: var(--bleu-roi);

    font-size: 12px;
    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 1.5px;
}

.brand-kicker::before {
    content: "";

    width: 28px;
    height: 3px;

    border-radius: 5px;

    background:
        linear-gradient(
            90deg,
            var(--vert-irlandais) 0 33%,
            var(--jaune-or) 33% 66%,
            var(--bleu-roi) 66% 100%
        );
}


.brand-title {
    margin: 0;

    max-width: 460px;

    color: var(--bleu-fonce);

    font-size: clamp(32px, 3.4vw, 46px);

    line-height: 1.12;

    font-weight: 700;

    letter-spacing: -1.3px;
}


.brand-description {
    max-width: 440px;

    margin: 20px 0 0;

    color: #657086;

    font-size: 15px;

    line-height: 1.75;
}


/* =========================================================
   BANDE TRICOLORE
========================================================= */

.brand-line {
    width: 90px;
    height: 5px;

    margin-top: 30px;

    border-radius: 5px;

    background:
        linear-gradient(
            90deg,
            var(--vert-irlandais) 0 33%,
            var(--jaune-or) 33% 66%,
            var(--bleu-roi) 66% 100%
        );
}


/* =========================================================
   INFORMATIONS
========================================================= */

.brand-info {
    display: flex;
    flex-direction: column;

    gap: 13px;

    margin-top: 30px;
}

.brand-info-item {
    display: flex;
    align-items: center;

    gap: 12px;

    color: #5d687c;

    font-size: 13px;
}

.brand-info-item i {
    width: 32px;
    height: 32px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: #f0f5ff;

    color: var(--bleu-roi);

    font-size: 14px;
}


/* =========================================================
   PARTIE DROITE — CONNEXION
========================================================= */

.login-panel {
    width: 38%;
    min-height: 100vh;

    position: relative;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 40px;

    background:
        linear-gradient(
            145deg,
            var(--vert-fonce),
            var(--vert-irlandais)
        );

    color: white;
}


/* Décoration */

.login-panel::before {
    content: "";

    position: absolute;

    width: 350px;
    height: 350px;

    right: -180px;
    top: -150px;

    border-radius: 50%;

    border: 1px solid rgba(255,255,255,0.10);

    box-shadow:
        0 0 0 60px rgba(255,255,255,0.025),
        0 0 0 120px rgba(255,255,255,0.018);
}


.login-panel::after {
    content: "";

    position: absolute;

    width: 230px;
    height: 230px;

    left: -130px;
    bottom: -100px;

    border-radius: 50%;

    background: rgba(212, 175, 55, 0.08);
}


/* =========================================================
   FORMULAIRE
========================================================= */

.login-box {
    width: 100%;
    max-width: 340px;

    position: relative;
    z-index: 5;
}


/* Petit logo au-dessus */

.login-symbol {
    width: 55px;
    height: 55px;

    display: flex;
    align-items: center;
    justify-content: center;

    margin-bottom: 25px;

    border-radius: 50%;

    background: rgba(255,255,255,0.12);

    border: 1px solid rgba(255,255,255,0.15);

    color: var(--jaune-or);

    font-size: 21px;
}


/* Titre */

.login-title {
    margin: 0 0 8px;

    color: white;

    font-size: 34px;

    font-weight: 700;

    letter-spacing: -0.7px;
}


.login-subtitle {
    margin: 0 0 30px;

    color: rgba(255,255,255,0.72);

    font-size: 13px;

    line-height: 1.6;
}


/* =========================================================
   CHAMPS
========================================================= */

.login-field {
    margin-bottom: 19px;
}


.login-field label {
    display: block;

    margin-bottom: 7px;

    color: rgba(255,255,255,0.88);

    font-size: 12px;

    font-weight: 500;
}


.login-input-wrapper {
    position: relative;
}


.login-input-wrapper i {
    position: absolute;

    left: 15px;
    top: 50%;

    transform: translateY(-50%);

    color: rgba(255,255,255,0.55);

    font-size: 14px;

    pointer-events: none;
}


.login-input {
    width: 100%;
    height: 46px;

    padding: 0 15px 0 42px;

    border: 1px solid rgba(255,255,255,0.08);

    border-radius: 23px;

    outline: none;

    background: rgba(0,0,0,0.18);

    color: white;

    font-family: var(--font);

    font-size: 13px;

    transition: 0.2s ease;
}


.login-input::placeholder {
    color: rgba(255,255,255,0.45);
}


.login-input:hover {
    background: rgba(0,0,0,0.23);
}


.login-input:focus {
    border-color: var(--jaune-or);

    background: rgba(0,0,0,0.24);

    box-shadow:
        0 0 0 3px rgba(212,175,55,0.12);
}


/* =========================================================
   ERREUR
========================================================= */

.login-error {
    display: flex;
    align-items: center;

    gap: 9px;

    margin-bottom: 18px;

    padding: 11px 13px;

    border-radius: 9px;

    background: rgba(255,255,255,0.10);

    border: 1px solid rgba(255,120,120,0.35);

    color: #ffd4d4;

    font-size: 12px;

    line-height: 1.4;
}


/* =========================================================
   BOUTON
========================================================= */

.login-button {
    width: 100%;
    height: 47px;

    margin-top: 6px;

    border: none;

    border-radius: 24px;

    background: var(--jaune-or);

    color: #17300f;

    font-family: var(--font);

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

    transition:
        transform 0.2s ease,
        background 0.2s ease,
        box-shadow 0.2s ease;
}


.login-button:hover {
    background: #e2c04e;

    transform: translateY(-2px);

    box-shadow:
        0 9px 22px rgba(0,0,0,0.20);
}


.login-button:active {
    transform: translateY(0);
}


/* =========================================================
   FOOTER FORMULAIRE
========================================================= */

.login-footer {
    margin-top: 25px;

    padding-top: 18px;

    border-top: 1px solid rgba(255,255,255,0.10);

    text-align: center;

    color: rgba(255,255,255,0.52);

    font-size: 11px;

    line-height: 1.6;
}


.login-footer i {
    color: var(--jaune-or);
}


/* =========================================================
   PETIT BANDEAU COULEURS
========================================================= */

.color-strip {
    position: absolute;

    left: 0;
    bottom: 0;

    width: 100%;
    height: 4px;

    background:
        linear-gradient(
            90deg,
            var(--vert-irlandais) 0 33.33%,
            var(--jaune-or) 33.33% 66.66%,
            var(--bleu-roi) 66.66% 100%
        );
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 900px) {

    .login-visual {
        width: 55%;

        padding: 45px 5%;

        clip-path: ellipse(80% 85% at 25% 50%);
    }

    .login-panel {
        width: 45%;

        padding: 30px;
    }

    .brand-title {
        font-size: 32px;
    }

    .brand-description {
        font-size: 13px;
    }

    .brand-info {
        display: none;
    }
}


@media (max-width: 700px) {

    .login-page {
        display: block;

        overflow-y: auto;
    }

    .login-visual {
        width: 100%;
        min-height: auto;

        padding: 45px 30px 65px;

        clip-path: none;
    }

    .visual-content {
        max-width: 550px;
        margin: auto;
    }

    .brand-title {
        font-size: 31px;
    }

    .brand-description {
        font-size: 14px;
    }

    .login-panel {
        width: 100%;
        min-height: auto;

        padding: 55px 30px 60px;
    }

    .login-box {
        max-width: 430px;
    }
}


@media (max-width: 450px) {

    .login-visual {
        padding: 35px 23px 50px;
    }

    .brand-logo {
        width: 72px;
        height: 72px;
    }

    .brand-logo img {
        width: 62px;
        height: 62px;
    }

    .brand-title {
        font-size: 27px;
    }

    .login-panel {
        padding: 45px 23px 50px;
    }
}

</style>


<!-- =========================================================
     PAGE DE CONNEXION
========================================================= -->

<main class="login-page">


    <!-- =====================================================
         PARTIE GAUCHE
    ====================================================== -->

    <section class="login-visual">


        <!-- Cercles décoratifs -->

        <span class="decor-circle one"></span>
        <span class="decor-circle two"></span>
        <span class="decor-circle three"></span>
        <span class="decor-circle four"></span>


        <div class="visual-content">


            <!-- Logo -->

            <div class="brand-logo">

                <img
                    src="../../publique/image/mrri.png"
                    alt="Logo MRRI"
                >

            </div>


            <!-- Identité -->

            <div class="brand-kicker">
                République Gabonaise
            </div>


            <h1 class="brand-title">
                Plateforme de consultation
                des structures sous tutelle
            </h1>


            <p class="brand-description">
                Un espace professionnel dédié à la consultation,
                au suivi et à la centralisation des informations
                relatives aux structures placées sous la tutelle
                du Ministère.
            </p>


            <!-- Ligne tricolore -->

            <div class="brand-line"></div>


            <!-- Points -->

            <div class="brand-info">

                <div class="brand-info-item">

                    <i class="bi bi-shield-lock"></i>

                    <span>
                        Accès réservé aux utilisateurs autorisés
                    </span>

                </div>


                <div class="brand-info-item">

                    <i class="bi bi-diagram-3"></i>

                    <span>
                        Informations centralisées
                    </span>

                </div>


                <div class="brand-info-item">

                    <i class="bi bi-people"></i>

                    <span>
                        Agents et points focaux
                    </span>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         PARTIE DROITE
    ====================================================== -->

    <section class="login-panel">


        <div class="login-box">


            <!-- Symbole -->

            <div class="login-symbol">

                <i class="bi bi-shield-lock-fill"></i>

            </div>


            <!-- Titre -->

            <h2 class="login-title">
                Connexion
            </h2>


            <p class="login-subtitle">
                Accédez à votre espace professionnel
            </p>


            <!-- Erreur -->

            <?php if ($erreur): ?>

                <div class="login-error">

                    <i class="bi bi-exclamation-circle-fill"></i>

                    <span>
                        <?= htmlspecialchars($erreur) ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- Formulaire -->

            <form action="connexion.php" method="post">


                <!-- Username -->

                <div class="login-field">

                    <label for="username">
                        Nom d'utilisateur
                    </label>

                    <div class="login-input-wrapper">

                        <i class="bi bi-person"></i>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="login-input"
                            placeholder="Entrez votre nom d'utilisateur"
                            autocomplete="username"
                            required
                        >

                    </div>

                </div>


                <!-- Password -->

                <div class="login-field">

                    <label for="password">
                        Mot de passe
                    </label>

                    <div class="login-input-wrapper">

                        <i class="bi bi-lock"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="login-input"
                            placeholder="Entrez votre mot de passe"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                </div>


                <!-- Bouton -->

                <button
                    type="submit"
                    class="login-button"
                >

                    <i class="bi bi-box-arrow-in-right me-2"></i>

                    Se connecter

                </button>


            </form>


            <!-- Footer -->

            <div class="login-footer">

                <i class="bi bi-shield-check me-1"></i>

                Accès sécurisé — Espace professionnel

            </div>


        </div>


        <!-- Bande couleurs -->

        <div class="color-strip"></div>


    </section>


</main>