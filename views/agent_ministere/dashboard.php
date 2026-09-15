<?php 
require_once '../../includes/auth.php';
require_once '../../config/database.php';
is_authenticated();

if ($_SESSION['role'] !== 'agent_ministere') {
    redirection_vers_les_dashboards($_SESSION['role']);
}

$resultat = $db->query("SELECT structure.id_struc, structure.nom_struc, structure.adresse, structure.directeur_general, categories.nom_cat, structure.sigle
FROM structure
JOIN categories ON structure.id_cat = categories.id_cat");
$structures = $resultat->fetchAll(PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>

<main class="container my-4">
    <h1 class="h3">Tableau de bord</h1>
    <p class="text-secondary">Vue d'ensemble des structures sous tutelle — accès agent du Ministère</p>
    <a href="demandes.php" class="btn btn-mrri mb-4">Voir les demandes reçues</a>

    <div class="row row-cols-1 row-cols-md-3 g-3">
        <?php foreach ($structures as $s): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-0"><?= htmlspecialchars($s['nom_struc']) ?></h5>
                        <p class="text-secondary small mb-2"><?= htmlspecialchars($s['sigle'] ?? '') ?></p>
                        <span class="badge bg-success-subtle text-success mb-2 align-self-start"><?= htmlspecialchars($s['nom_cat']) ?></span>
                        <p class="card-text small text-secondary flex-grow-1"><?= htmlspecialchars($s['adresse'] ?? 'Adresse non renseignée') ?></p>
                        <a href="../public/fiche.php?id=<?= $s['id_struc'] ?>" class="btn btn-outline-success btn-sm mt-2">Voir la fiche</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require '../../includes/footer.php'; ?>