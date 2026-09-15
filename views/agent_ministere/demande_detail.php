<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'agent_ministere') {
    redirection_vers_les_dashboards($_SESSION['role']);
}

if (!isset($_GET['id'])) {
    die("Aucune demande sélectionnée.");
}
$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveauStatut = $_POST['statut'] ?? '';
    $reponse = $_POST['reponse_agent'] ?? '';

    $majStmt = $db->prepare("UPDATE demandes SET statut = :statut, reponse_agent = :reponse, id_agent_traitant = :id_agent, date_reponse = NOW() WHERE id_demande = :id");
    $majStmt->execute([':statut' => $nouveauStatut, ':reponse' => $reponse, ':id_agent' => $_SESSION['user_id'], ':id' => $id]);

    definir_flash("Demande mise à jour avec succès.");
    header("Location: demande_detail.php?id=" . $id);
    exit();
}

$demandeStmt = $db->prepare("
    SELECT demandes.*, utilisateurs.username AS soumis_par
    FROM demandes
    JOIN utilisateurs ON utilisateurs.id = demandes.id_point_focal
    WHERE id_demande = :id
");
$demandeStmt->execute([':id' => $id]);
$demande = $demandeStmt->fetch(PDO::FETCH_ASSOC);

if (!$demande) {
    die("Demande introuvable.");
}

$structuresStmt = $db->prepare("
    SELECT structure.nom_struc
    FROM demandes_structures
    JOIN structure ON structure.id_struc = demandes_structures.id_structure
    WHERE id_demande = :id
");
$structuresStmt->execute([':id' => $id]);
$structuresConcernees = $structuresStmt->fetchAll(PDO::FETCH_ASSOC);

$libellesStatut = ['en_attente' => 'En attente', 'en_cours' => 'En cours', 'besoin_rdv' => "Besoin d'un rendez-vous", 'accepte' => 'Acceptée', 'refuse' => 'Refusée', 'traite' => 'Traitée'];
$couleursStatut = ['en_attente' => 'bg-secondary', 'en_cours' => 'bg-primary', 'besoin_rdv' => 'bg-warning text-dark', 'accepte' => 'bg-success', 'refuse' => 'bg-danger', 'traite' => 'bg-success'];

require '../../includes/header.php';
?>

<main class="container my-4 mb-5" style="max-width: 700px;">
    <?php afficher_flash(); ?>
    <p><a href="demandes.php" class="link-secondary">← Retour aux demandes</a></p>

    <span class="badge <?= $couleursStatut[$demande['statut']] ?> mb-2"><?= $libellesStatut[$demande['statut']] ?></span>
    <h1 class="h3"><?= htmlspecialchars($demande['titre_theme']) ?></h1>
    <p class="text-secondary small">Soumise par <?= htmlspecialchars($demande['soumis_par']) ?> — le <?= htmlspecialchars($demande['date_soumission']) ?></p>

    <div class="card shadow-sm mb-3"><div class="card-body">
        <h2 class="h6">Structures concernées</h2>
        <p class="mb-0"><?= implode(', ', array_map('htmlspecialchars', array_column($structuresConcernees, 'nom_struc'))) ?></p>
    </div></div>

    <div class="card shadow-sm mb-3"><div class="card-body">
        <h2 class="h6">Contexte</h2><p><?= nl2br(htmlspecialchars($demande['contexte'])) ?></p>
        <h2 class="h6">Problématique</h2><p class="mb-0"><?= nl2br(htmlspecialchars($demande['problematique'])) ?></p>
    </div></div>

    <?php if (!empty($demande['objectifs'])): ?>
        <div class="card shadow-sm mb-3"><div class="card-body"><h2 class="h6">Objectifs attendus</h2><p class="mb-0"><?= nl2br(htmlspecialchars($demande['objectifs'])) ?></p></div></div>
    <?php endif; ?>

    <?php if (!empty($demande['utilisateurs_concernes'])): ?>
        <div class="card shadow-sm mb-3"><div class="card-body"><h2 class="h6">Utilisateurs concernés</h2><p class="mb-0"><?= nl2br(htmlspecialchars($demande['utilisateurs_concernes'])) ?></p></div></div>
    <?php endif; ?>

    <?php if (!empty($demande['contraintes']) || !empty($demande['delai_souhaite'])): ?>
        <div class="card shadow-sm mb-3"><div class="card-body">
            <h2 class="h6">Contraintes et délai</h2>
            <?php if (!empty($demande['contraintes'])): ?><p><?= nl2br(htmlspecialchars($demande['contraintes'])) ?></p><?php endif; ?>
            <?php if (!empty($demande['delai_souhaite'])): ?><p class="mb-0"><strong>Délai souhaité :</strong> <?= htmlspecialchars($demande['delai_souhaite']) ?></p><?php endif; ?>
        </div></div>
    <?php endif; ?>

    <div class="card shadow-sm"><div class="card-body">
        <h2 class="h6">Traitement de la demande</h2>
        <form action="" method="post">
            <div class="mb-3">
                <label class="form-label">Statut</label>
                <select name="statut" class="form-select">
                    <?php foreach ($libellesStatut as $valeur => $libelle): ?>
                        <option value="<?= $valeur ?>" <?= $demande['statut'] === $valeur ? 'selected' : '' ?>><?= $libelle ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Réponse / commentaire</label>
                <textarea name="reponse_agent" class="form-control" rows="3" placeholder="Votre retour au point focal..."><?= htmlspecialchars($demande['reponse_agent'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-mrri">Enregistrer</button>
        </form>
    </div></div>
</main>

<?php require '../../includes/footer.php'; ?>