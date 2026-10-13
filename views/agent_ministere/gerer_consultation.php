<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
require_once '../../includes/dates.php';
require_once '../../includes/consultation_helpers.php';
require_once '../../includes/consultations_gestion.php';
is_authenticated();

if ($_SESSION['role'] !== 'agent_ministere') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

$erreur = null;
$saisie = ['titre' => '', 'contenu' => '', 'date_fermeture' => ''];

/* =========================================================
   TRAITEMENT : créer (consultation générale) ou fermer
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
        exit('Requête invalide.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'creer') {
        $saisie = [
            'titre' => trim($_POST['titre'] ?? ''),
            'contenu' => trim($_POST['contenu'] ?? ''),
            'date_fermeture' => trim($_POST['date_fermeture'] ?? ''),
        ];

        // id_structure = null : consultation GÉNÉRALE, créée par le ministère
        $erreur = creer_consultation(
            $db, $saisie['titre'], $saisie['contenu'], $saisie['date_fermeture'],
            null, (int) $_SESSION['user_id']
        );

        if ($erreur === null) {
            definir_flash("Consultation publiée avec succès.");
            header('Location: gerer_consultations.php');
            exit();
        }
    } elseif ($action === 'fermer') {
        $ok = fermer_consultation($db, (int) ($_POST['id_consultation'] ?? 0), null);
        definir_flash(
            $ok ? "Consultation fermée." : "Cette consultation est introuvable ou déjà fermée.",
            $ok ? 'succes' : 'erreur'
        );
        header('Location: gerer_consultations.php');
        exit();
    }
}

/* =========================================================
   LISTE : l'agent voit toutes les consultations
   ========================================================= */
$consultations = $db->query(
    "SELECT co.id_consultation, co.titre, co.statut, co.date_ouverture, co.date_fermeture,
            co.id_structure, s.nom_struc,
            (SELECT COUNT(*) FROM contributions c WHERE c.id_consultation = co.id_consultation) AS nb_contributions
     FROM consultations co
     LEFT JOIN structure s ON s.id_struc = co.id_structure
     ORDER BY co.date_ouverture DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$token = generer_token_csrf();
$titreFormulaire = 'Nouvelle consultation générale';
$afficherPortee = true;
$formulaireActif = true;

require '../../includes/header_dashboard.php';
?>

<div class="mrri-dashboard dashboard-agent">

    <?php $pageActive = 'consultations'; require '../../includes/sidebar_agent.php'; ?>

    <main class="mrri-main">
        <header class="mrri-topbar">
            <div class="mrri-topbar-title">
                MRRI / <strong>Consultations</strong>
            </div>
            <div class="mrri-user">
                <div class="mrri-user-avatar">AM</div>
                <div class="mrri-user-info">
                    <div class="mrri-user-name">Agent du ministère</div>
                    <div class="mrri-user-role">Accès lecture étendue</div>
                </div>
            </div>
        </header>

        <section class="mrri-content">

            <?php afficher_flash(); ?>

            <div class="mrri-page-header">
                <div>
                    <div class="mrri-breadcrumb">Espace professionnel / <span class="active">Consultations</span></div>
                    <h1 class="mrri-page-title">Gestion des consultations</h1>
                    <p class="mrri-page-description">Publiez des consultations générales et clôturez celles qui sont terminées.</p>
                </div>
            </div>

            <?php require '../../includes/consultations_bloc.php'; ?>

        </section>
    </main>
</div>

<?php require '../../includes/footer_dashboard.php'; ?>