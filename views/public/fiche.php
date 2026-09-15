<?php 
session_start();
require '../../config/database.php';

if (!isset($_GET['id'])) {
    die("Aucune structure sélectionnée.");
}
$id = $_GET['id'];

$estAgent = isset($_SESSION['role']) && $_SESSION['role'] === 'agent_ministere';
$sql = $estAgent
    ? "SELECT * FROM documents WHERE id_structure = :id"
    : "SELECT * FROM documents WHERE id_structure = :id AND est_public = 1";
$doc = $db->prepare($sql);
$doc->execute([':id' => $id]);
$documents = $doc->fetchAll(PDO::FETCH_ASSOC);

$resultat = $db->prepare("SELECT * FROM structure JOIN categories ON structure.id_cat = categories.id_cat WHERE id_struc = :id");
$resultat->execute([':id' => $id]);
$structure = $resultat->fetch(PDO::FETCH_ASSOC);

if ($structure) {
    $photosStmt = $db->prepare("SELECT * FROM structure_photos WHERE id_structure = :id ORDER BY Position");
    $photosStmt->execute([':id' => $id]);
    $photosStructure = $photosStmt->fetchAll(PDO::FETCH_ASSOC);

    $actualitesStmt = $db->prepare("SELECT * FROM actualites WHERE id_structure = :id ORDER BY date_publication DESC");
    $actualitesStmt->execute([':id' => $id]);
    $actualites = $actualitesStmt->fetchAll(PDO::FETCH_ASSOC);
}

require '../../includes/header_public.php';
?>

<?php if ($structure): ?>
<main class="container my-4">
    <p class="small"><a href="catalogue.php" class="link-secondary">Structures</a> / <?= htmlspecialchars($structure['nom_struc']) ?></p>

    <div class="d-flex align-items-start gap-3 border-bottom pb-3 mb-4">
        <?php if (!empty($photosStructure)): ?>
            <img src="../../uploads/photos/<?= htmlspecialchars($photosStructure[0]['Photos_url']) ?>" alt="Photo de <?= htmlspecialchars($structure['nom_struc']) ?>" class="rounded border" style="width: 84px; height: 84px; object-fit: cover;">
        <?php endif; ?>
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($structure['nom_struc'] ?? 'Aucun nom défini.') ?></h1>
            <span class="badge bg-secondary-subtle text-secondary me-1"><?= htmlspecialchars($structure['sigle'] ?? 'Sigle non renseigné') ?></span>
            <span class="badge bg-warning-subtle text-warning-emphasis"><?= htmlspecialchars($structure['nom_cat'] ?? 'Catégorie non définie') ?></span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                  <h2 class="h5">Informations générales</h2>
<div class="row row-cols-2 g-3">

    <div class="col">
        <p class="small text-secondary mb-0">Directeur général</p>
        <p class="fw-semibold"><?= htmlspecialchars($structure['directeur_general'] ?? 'Non renseigné') ?></p>
    </div>

    <div class="col">
        <p class="small text-secondary mb-0">Adresse</p>
        <p class="fw-semibold"><?= htmlspecialchars($structure['adresse'] ?? 'Non renseignée') ?></p>
    </div>

    <div class="col">
        <p class="small text-secondary mb-0">Email</p>
        <p class="fw-semibold"><?= htmlspecialchars($structure['email'] ?? 'Non renseigné') ?></p>
    </div>

    <div class="col">
        <p class="small text-secondary mb-0">Téléphone</p>
        <p class="fw-semibold"><?= htmlspecialchars($structure['telephone'] ?? 'Non renseigné') ?></p>
    </div>

    <div class="col-12">
        <p class="small text-secondary mb-0">Site web</p>
        <p class="fw-semibold">
            <?php if (!empty($structure['site_web'])): ?>
                <a href="<?= htmlspecialchars($structure['site_web']) ?>" target="_blank">
                    <?= htmlspecialchars($structure['site_web']) ?>
                </a>
            <?php else: ?>
                Non renseigné
            <?php endif; ?>
        </p>
    </div>

    <div class="col">
        <p class="small text-secondary mb-0">Missions</p>
        <p class="fw-semibold mt-3 mb-0"><?= htmlspecialchars($structure['mission'] ?? 'Non renseigné') ?></p>
    </div>

</div>

                    
                </div>
            </div>

            <?php if (count($photosStructure) > 1): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h5">Photos</h2>
                        <div class="row row-cols-3 g-2">
                            <?php foreach ($photosStructure as $photo): ?>
                                <div class="col"><img src="../../uploads/photos/<?= htmlspecialchars($photo['Photos_url']) ?>" class="img-fluid rounded border" style="aspect-ratio: 4/3; object-fit: cover; width: 100%;" alt="Photo de <?= htmlspecialchars($structure['nom_struc']) ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h5">Actualités</h2>
                    <?php if (empty($actualites)): ?>
                        <p class="text-secondary">Aucune actualité publiée pour le moment.</p>
                    <?php endif; ?>
                    <?php foreach ($actualites as $actualite): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <h3 class="h6"><?= htmlspecialchars($actualite['titre']) ?></h3>
                            <p><?= htmlspecialchars($actualite['contenu']) ?></p>
                            <p class="small text-secondary"><?= htmlspecialchars($actualite['date_publication']) ?></p>
                            <?php
                            $photosActuStmt = $db->prepare("SELECT * FROM actualites_photos WHERE id_actualites = :id ORDER BY Position");
                            $photosActuStmt->execute([':id' => $actualite['id_actu']]);
                            $photosActualite = $photosActuStmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php if ($photosActualite): ?>
                                <div class="row row-cols-3 g-2">
                                    <?php foreach ($photosActualite as $photo): ?>
                                        <div class="col"><img src="../../uploads/photos/<?= htmlspecialchars($photo['Photos_actu_url']) ?>" class="img-fluid rounded border" style="aspect-ratio: 4/3; object-fit: cover; width: 100%;" alt="Photo de l'actualité"></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Documents<?= $estAgent ? '' : ' publics' ?></h2>
                    <?php if ($documents): ?>
                        <ul class="list-unstyled">
                            <?php foreach ($documents as $document): ?>
                                <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <a href="../../uploads/documents/<?= htmlspecialchars($document['doc_url']) ?>" target="_blank"><?= htmlspecialchars($document['nom_affichage']) ?></a>
                                    <?php if ($estAgent): ?>
                                        <span class="badge <?= $document['est_public'] ? 'bg-success' : 'bg-danger' ?>"><?= $document['est_public'] ? 'Public' : 'Confidentiel' ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-secondary">Aucun document public disponible.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php else: ?>
    <div class="container my-5"><div class="alert alert-light border text-center">Structure introuvable.</div></div>
<?php endif; ?>

<?php require '../../includes/footer.php'; ?>