<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'agent_ministere') {
    redirection_vers_les_dashboards($_SESSION['role']);
}

$demandesStmt = $db->query("
    SELECT demandes.id_demande, demandes.titre_theme, demandes.statut, demandes.date_soumission, utilisateurs.username AS soumis_par
    FROM demandes
    JOIN utilisateurs ON utilisateurs.id = demandes.id_point_focal
    ORDER BY demandes.date_soumission DESC
");
$demandes = $demandesStmt->fetchAll(PDO::FETCH_ASSOC);

$libellesStatut = ['en_attente' => 'En attente', 'en_cours' => 'En cours', 'besoin_rdv' => "Besoin d'un rendez-vous", 'accepte' => 'Acceptée', 'refuse' => 'Refusée', 'traite' => 'Traitée'];
$couleursStatut = ['en_attente' => 'bg-secondary', 'en_cours' => 'bg-primary', 'besoin_rdv' => 'bg-warning text-dark', 'accepte' => 'bg-success', 'refuse' => 'bg-danger', 'traite' => 'bg-success'];

require '../../includes/header.php';
?>

<main class="container my-4" style="max-width: 780px;">
    <?php afficher_flash(); ?>
    <h1 class="h3">Demandes reçues</h1>
    <p><a href="dashboard.php" class="link-secondary">← Retour au tableau de bord</a></p>

    <?php if (empty($demandes)): ?><div class="alert alert-light border">Aucune demande pour le moment.</div><?php endif; ?>

    <?php foreach ($demandes as $d): ?>
        <div class="card shadow-sm mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong><?= htmlspecialchars($d['titre_theme']) ?></strong>
                    <span class="badge <?= $couleursStatut[$d['statut']] ?>"><?= $libellesStatut[$d['statut']] ?></span>
                </div>
                <p class="small text-secondary mb-2">Soumise par <?= htmlspecialchars($d['soumis_par']) ?> — le <?= htmlspecialchars($d['date_soumission']) ?></p>
                <a href="demande_detail.php?id=<?= $d['id_demande'] ?>" class="btn btn-outline-success btn-sm">Consulter</a>
            </div>
        </div>
    <?php endforeach; ?>
</main>

<?php require '../../includes/footer.php'; ?>