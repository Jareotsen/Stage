<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
is_authenticated();

// Contrôle du rôle : toujours suivi d'un exit()
if ($_SESSION['role'] !== 'agent_ministere') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

// Liste blanche des statuts autorisés (la seule source de vérité)
$libellesStatut = [
    'en_attente' => 'En attente',
    'en_cours'   => 'En cours',
    'besoin_rdv' => "Besoin d'un rendez-vous",
    'accepte'    => 'Acceptée',
    'refuse'     => 'Refusée',
    'traite'     => 'Traitée',
];
$couleursStatut = [
    'en_attente' => 'bg-secondary',
    'en_cours'   => 'bg-primary',
    'besoin_rdv' => 'bg-warning text-dark',
    'accepte'    => 'bg-success',
    'refuse'     => 'bg-danger',
    'traite'     => 'bg-success',
];

/* =========================================================
   1. VALIDATION DE L'IDENTIFIANT (jamais utilisé tel quel)
   ========================================================= */
$idBrut = $_GET['id'] ?? '';
if (!ctype_digit((string) $idBrut) || (int) $idBrut <= 0) {
    definir_flash("Demande introuvable.", 'erreur');
    header('Location: demandes.php');
    exit();
}
$id = (int) $idBrut;

/* =========================================================
   2. CHARGEMENT DE LA DEMANDE
   LEFT JOIN : la demande reste consultable même si le compte
   du point focal a été supprimé.
   ========================================================= */
$demandeStmt = $db->prepare("
    SELECT demandes.*, utilisateurs.username AS soumis_par
    FROM demandes
    LEFT JOIN utilisateurs ON utilisateurs.id = demandes.id_point_focal
    WHERE demandes.id_demande = :id
");
$demandeStmt->execute([':id' => $id]);
$demande = $demandeStmt->fetch(PDO::FETCH_ASSOC);

if (!$demande) {
    definir_flash("Demande introuvable.", 'erreur');
    header('Location: demandes.php');
    exit();
}

/* =========================================================
   3. TRAITEMENT DU FORMULAIRE
   ========================================================= */
$erreur = null;
$statutSaisi = $demande['statut'];
$reponseSaisie = $demande['reponse_agent'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
        exit('Requête invalide.');
    }

    $statutSaisi = $_POST['statut'] ?? '';
    $reponseSaisie = trim($_POST['reponse_agent'] ?? '');

    // Le statut doit faire partie de la liste blanche
    if (!array_key_exists($statutSaisi, $libellesStatut)) {
        $erreur = "Statut invalide.";
        $statutSaisi = $demande['statut'];
    } elseif (mb_strlen($reponseSaisie) > 2000) {
        $erreur = "La réponse ne doit pas dépasser 2000 caractères.";
    } else {
        try {
            $majStmt = $db->prepare("
                UPDATE demandes
                SET statut = :statut, reponse_agent = :reponse,
                    id_agent_traitant = :id_agent, date_reponse = NOW()
                WHERE id_demande = :id
            ");
            $majStmt->execute([
                ':statut'   => $statutSaisi,
                ':reponse'  => $reponseSaisie,
                ':id_agent' => $_SESSION['user_id'],
                ':id'       => $id,
            ]);

            definir_flash("Demande mise à jour avec succès.");
            header('Location: demande_detail.php?id=' . $id);
            exit();
        } catch (PDOException $e) {
            // Détail technique uniquement dans les logs, jamais à l'écran
            error_log("Erreur mise à jour demande #$id : " . $e->getMessage());
            $erreur = "Impossible d'enregistrer la modification. Veuillez réessayer.";
        }
    }
}

/* =========================================================
   4. STRUCTURES CONCERNÉES
   ========================================================= */
$structuresStmt = $db->prepare("
    SELECT structure.nom_struc
    FROM demandes_structures
    JOIN structure ON structure.id_struc = demandes_structures.id_structure
    WHERE demandes_structures.id_demande = :id
");
$structuresStmt->execute([':id' => $id]);
$structuresConcernees = $structuresStmt->fetchAll(PDO::FETCH_ASSOC);

$statutActuel = $demande['statut'];
$libelleActuel = $libellesStatut[$statutActuel] ?? 'Statut inconnu';
$couleurActuelle = $couleursStatut[$statutActuel] ?? 'bg-secondary';

$token = generer_token_csrf();

require '../../includes/header_dashboard.php';
?>

<div class="mrri-dashboard dashboard-agent">

    <?php $pageActive = 'demandes'; require '../../includes/sidebar_agent.php'; ?>

    <main class="mrri-main">
        <header class="mrri-topbar">
            <div class="mrri-topbar-title">
                MRRI / <strong>Demandes reçues</strong>
            </div>
            <div class="mrri-user">
                <div class="mrri-user-avatar">AM</div>
                <div class="mrri-user-info">
                    <div class="mrri-user-name">Agent du ministère</div>
                    <div class="mrri-user-role">Accès lecture étendue</div>
                </div>
            </div>
        </header>

        <section class="mrri-content" style="max-width: 800px;">

            <?php afficher_flash(); ?>

            <p><a href="demandes.php" class="link-secondary">← Retour aux demandes</a></p>

            <span class="badge <?= htmlspecialchars($couleurActuelle) ?> mb-2"><?= htmlspecialchars($libelleActuel) ?></span>
            <h1 class="h3"><?= htmlspecialchars($demande['titre_theme']) ?></h1>
            <p class="text-secondary small">
                Soumise par <?= htmlspecialchars($demande['soumis_par'] ?? 'compte supprimé') ?>
                — le <?= htmlspecialchars($demande['date_soumission']) ?>
            </p>

            <div class="card shadow-sm mb-3"><div class="card-body">
                <h2 class="h6">Structures concernées</h2>
                <p class="mb-0">
                    <?= empty($structuresConcernees)
                        ? 'Aucune structure renseignée.'
                        : implode(', ', array_map('htmlspecialchars', array_column($structuresConcernees, 'nom_struc'))) ?>
                </p>
            </div></div>

            <div class="card shadow-sm mb-3"><div class="card-body">
                <h2 class="h6">Contexte</h2>
                <p><?= nl2br(htmlspecialchars($demande['contexte'])) ?></p>
                <h2 class="h6">Problématique</h2>
                <p class="mb-0"><?= nl2br(htmlspecialchars($demande['problematique'])) ?></p>
            </div></div>

            <?php if (!empty($demande['objectifs'])): ?>
                <div class="card shadow-sm mb-3"><div class="card-body">
                    <h2 class="h6">Objectifs attendus</h2>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($demande['objectifs'])) ?></p>
                </div></div>
            <?php endif; ?>

            <?php if (!empty($demande['utilisateurs_concernes'])): ?>
                <div class="card shadow-sm mb-3"><div class="card-body">
                    <h2 class="h6">Utilisateurs concernés</h2>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($demande['utilisateurs_concernes'])) ?></p>
                </div></div>
            <?php endif; ?>

            <?php if (!empty($demande['contraintes']) || !empty($demande['delai_souhaite'])): ?>
                <div class="card shadow-sm mb-3"><div class="card-body">
                    <h2 class="h6">Contraintes et délai</h2>
                    <?php if (!empty($demande['contraintes'])): ?>
                        <p><?= nl2br(htmlspecialchars($demande['contraintes'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($demande['delai_souhaite'])): ?>
                        <p class="mb-0"><strong>Délai souhaité :</strong> <?= htmlspecialchars($demande['delai_souhaite']) ?></p>
                    <?php endif; ?>
                </div></div>
            <?php endif; ?>

            <div class="card shadow-sm mb-5"><div class="card-body">
                <h2 class="h6">Traitement de la demande</h2>

                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="statut">Statut</label>
                        <select name="statut" id="statut" class="form-select">
                            <?php foreach ($libellesStatut as $valeur => $libelle): ?>
                                <option value="<?= htmlspecialchars($valeur) ?>" <?= $statutSaisi === $valeur ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($libelle) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="reponse_agent">Réponse / commentaire</label>
                        <textarea name="reponse_agent" id="reponse_agent" class="form-control" rows="3" maxlength="2000"
                                  placeholder="Votre retour au point focal..."><?= htmlspecialchars($reponseSaisie) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-mrri">Enregistrer</button>
                </form>
            </div></div>

        </section>
    </main>
</div>

<?php require '../../includes/footer_dashboard.php'; ?>