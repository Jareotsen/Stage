<?php 
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
}

$resultat = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
$resultat->execute([':id_responsable' => $_SESSION['user_id']]);
$structure = $resultat->fetch(PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>

<main class="container my-4">
    <?php afficher_flash(); ?>

    <h1 class="h3">Bienvenue, point focal de <?= htmlspecialchars($structure['nom_struc'] ?? '') ?></h1>
    <p class="text-secondary">Sigle : <?= htmlspecialchars($structure['sigle'] ?? '') ?> — Adresse : <?= htmlspecialchars($structure['adresse'] ?? 'Non renseignée') ?></p>

    <div class="row row-cols-1 row-cols-md-2 g-3 mt-2">
        <div class="col"><a href="modifier_structure.php" class="card text-decoration-none h-100 shadow-sm"><div class="card-body"><p class="small text-secondary mb-1">Gérer</p><h5 class="card-title">Modifier la structure</h5></div></a></div>
        <div class="col"><a href="ajouter_actualite.php" class="card text-decoration-none h-100 shadow-sm"><div class="card-body"><p class="small text-secondary mb-1">Publier</p><h5 class="card-title">Ajouter une actualité</h5></div></a></div>
        <div class="col"><a href="upload.php" class="card text-decoration-none h-100 shadow-sm"><div class="card-body"><p class="small text-secondary mb-1">Partager</p><h5 class="card-title">Uploader un document</h5></div></a></div>
        <div class="col"><a href="nouvelle_demande.php" class="card text-decoration-none h-100 shadow-sm"><div class="card-body"><p class="small text-secondary mb-1">Contacter le Ministère</p><h5 class="card-title">Soumettre une demande</h5></div></a></div>
    </div>
</main>

<?php require '../../includes/footer.php'; ?>