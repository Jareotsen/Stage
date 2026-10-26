<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
require_once '../../includes/dates.php';
require_once '../../includes/consultation_helpers.php';
require_once '../../includes/consultations_gestion.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

// La structure du point focal est déterminée côté serveur, jamais depuis le formulaire
$resultat = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
$resultat->execute([':id_responsable' => $_SESSION['user_id']]);
$structure = $resultat->fetch(PDO::FETCH_ASSOC);

$erreur = null;
$saisie = ['titre' => '', 'contenu' => '', 'date_fermeture' => ''];

/* =========================================================
   TRAITEMENT : créer ou fermer (uniquement pour SA structure)
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
        exit('Requête invalide.');
    }

    if (!$structure) {
        definir_flash("Aucune structure ne vous est rattachée.", 'erreur');
        header('Location: mes_consultations.php');
        exit();
    }

    $idStructure = (int) $structure['id_struc'];
    $action = $_POST['action'] ?? '';

    if ($action === 'creer') {
        $saisie = [
            'titre' => trim($_POST['titre'] ?? ''),
            'contenu' => trim($_POST['contenu'] ?? ''),
            'date_fermeture' => trim($_POST['date_fermeture'] ?? ''),
        ];

        $erreur = creer_consultation(
            $db, $saisie['titre'], $saisie['contenu'], $saisie['date_fermeture'],
            $idStructure, (int) $_SESSION['user_id']
        );

        if ($erreur === null) {
            definir_flash("Consultation publiée avec succès.");
            header('Location: mes_consultations.php');
            exit();
        }
    } elseif ($action === 'fermer') {
        // $idStructure en 3e paramètre : la requête refuse de fermer la consultation d'une autre structure
        $ok = fermer_consultation($db, (int) ($_POST['id_consultation'] ?? 0), $idStructure);
        definir_flash(
            $ok ? "Consultation fermée." : "Action impossible : consultation introuvable, déjà fermée ou non autorisée.",
            $ok ? 'succes' : 'erreur'
        );
        header('Location: mes_consultations.php');
        exit();
    }
}

/* =========================================================
   LISTE : uniquement les consultations de la structure
   ========================================================= */
$consultations = [];
if ($structure) {
    $liste = $db->prepare(
        "SELECT co.id_consultation, co.titre, co.statut, co.date_ouverture, co.date_fermeture,
                co.id_structure,
                (SELECT COUNT(*) FROM contributions c WHERE c.id_consultation = co.id_consultation) AS nb_contributions
         FROM consultations co
         WHERE co.id_structure = :id_structure
         ORDER BY co.date_ouverture DESC"
    );
    $liste->execute([':id_structure' => $structure['id_struc']]);
    $consultations = $liste->fetchAll(PDO::FETCH_ASSOC);
}

$token = generer_token_csrf();
$titreFormulaire = 'Nouvelle consultation';
$afficherPortee = false;
$formulaireActif = (bool) $structure;

require '../../includes/header_dashboard.php';
?>

<div class="mrri-dashboard dashboard-point-focal">

 <?php $pageActive = 'consultations'; require '../../includes/sidebar_point_focal.php'; ?>

    <main class="mrri-main">
        <header class="mrri-topbar">
            <div class="mrri-topbar-title">
                MRRI / <strong>Consultations</strong>
            </div>
            <div class="mrri-user">
                <div class="mrri-user-avatar">PF</div>
                <div class="mrri-user-info">
                    <div class="mrri-user-name"><?= htmlspecialchars($structure['nom_struc'] ?? 'Point focal') ?></div>
                    <div class="mrri-user-role">Point focal</div>
                </div>
            </div>
        </header>

        <section class="mrri-content">

            <?php afficher_flash(); ?>

            <div class="mrri-page-header">
                <div>
                    <div class="mrri-breadcrumb">Espace professionnel / <span class="active">Consultations</span></div>
                    <h1 class="mrri-page-title">Mes consultations</h1>
                    <p class="mrri-page-description">
                        <?= $structure
                            ? "Consultations publiées au nom de " . htmlspecialchars($structure['nom_struc'])
                            : "Aucune structure ne vous est rattachée : contactez l'administrateur." ?>
                    </p>
                </div>
            </div>

            <?php require '../../includes/consultations_bloc.php'; ?>

        </section>
    </main>
</div>

<?php require '../../includes/footer_dashboard.php'; ?>
