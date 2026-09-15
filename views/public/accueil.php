<?php
session_start();
require '../../config/database.php';

$actu = $db->query("SELECT id_actu, titre, contenu, structure.nom_struc, date_publication FROM actualites
JOIN structure ON actualites.id_structure = structure.id_struc
ORDER BY date_publication DESC
LIMIT 8");
$actualites = $actu->fetchAll(PDO::FETCH_ASSOC);

$categoriesStmt = $db->query("SELECT id_cat, nom_cat FROM categories ORDER BY nom_cat");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// --- Nouveau bloc : liste paginée des structures ---
$parPage = 9;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$catFiltre = isset($_GET['cat']) ? (int) $_GET['cat'] : null;

$conditions = [];
$parametres = [];
if ($catFiltre) {
    $conditions[] = "structure.id_cat = :cat";
    $parametres[':cat'] = $catFiltre;
}
$whereSQL = $conditions ? " WHERE " . implode(" AND ", $conditions) : "";

$compteStmt = $db->prepare("SELECT COUNT(*) FROM structure" . $whereSQL);
$compteStmt->execute($parametres);
$totalStructures = (int) $compteStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalStructures / $parPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $parPage;

$structuresStmt = $db->prepare(
    "SELECT structure.id_struc, structure.nom_struc, structure.sigle, structure.adresse, categories.nom_cat
     FROM structure
     JOIN categories ON structure.id_cat = categories.id_cat"
    . $whereSQL .
    " ORDER BY structure.nom_struc ASC
     LIMIT $parPage OFFSET $offset"
);
$structuresStmt->execute($parametres);
$structuresListe = $structuresStmt->fetchAll(PDO::FETCH_ASSOC);
// --- Fin du nouveau bloc ---

require '../../includes/header.php';
?>

<section class="bg-light py-4 text-center border-bottom">
  <div class="container">
    <h1 class="fw-bold">Plateforme de consultation des structures sous tutelle du MRRI</h1>
    <p class="text-secondary mb-4">Centralisation, organisation et accès sécurisé aux informations administratives et opérationnelles des entités publiques.</p>
<?php if (!empty($actualites)): ?>
  <div id="carouselActus" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
      <?php foreach ($actualites as $index => $actualite): ?>
        <?php
        $photosActuStmt = $db->prepare("SELECT * FROM actualites_photos WHERE id_actualites = :id ORDER BY Position");
        $photosActuStmt->execute([':id' => $actualite['id_actu']]);
        $premierePhoto = $photosActuStmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
          <a href="actualite.php?id=<?= $actualite['id_actu'] ?>" class="actu-carte-lien">
            <?php if ($premierePhoto): ?>
              <img src="../../uploads/photos/<?= htmlspecialchars($premierePhoto['Photos_actu_url']) ?>"
                   alt="<?= htmlspecialchars($actualite['titre']) ?>">
            <?php else: ?>
              <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="background:#e9e9e6;">
                <span class="text-secondary">Pas de photo</span>
              </div>
            <?php endif; ?>

            <div class="actu-barre-titre">
              <span class="actu-pastille"></span>
              <p class="actu-titre-barre"><?= htmlspecialchars($actualite['titre']) ?></p>
              <div class="actu-puces">
                <?php foreach ($actualites as $i => $a): ?>
                  <button type="button" data-bs-target="#carouselActus" data-bs-slide-to="<?= $i ?>" class="<?= $i === $index ? 'active' : '' ?>" onclick="event.preventDefault(); event.stopPropagation();"></button>
                <?php endforeach; ?>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#carouselActus" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselActus" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>
<?php else: ?>
  <div class="alert alert-light border">Aucune actualité publiée pour le moment.</div>
<?php endif; ?>
  </div>
</section>

<section class="py-5">
  <div class="container text-center">
    <h2 class="mb-4">Trouver une structure</h2>

    <form action="catalogue.php" method="get" class="mx-auto mb-3" style="max-width: 600px;">
      <input id="search" type="text" name="q" class="form-control form-control-lg" placeholder="Rechercher une structure (nom, sigle)...">
    </form>

    <div id="results" class="mx-auto mb-3" style="max-width: 600px;"></div>

    <div class="d-flex flex-wrap justify-content-center gap-2 mb-5">
      <a href="accueil.php" class="btn btn-sm rounded-pill <?= $catFiltre === null ? 'btn-mrri' : 'btn-outline-success' ?>">Tous</a>
      <?php foreach ($categories as $cat): ?>
        <a href="accueil.php?cat=<?= $cat['id_cat'] ?>" class="btn btn-sm rounded-pill <?= $catFiltre === (int) $cat['id_cat'] ? 'btn-mrri' : 'btn-outline-success' ?>">
          <?= htmlspecialchars($cat['nom_cat']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($structuresListe)): ?>
      <div class="alert alert-light border">Aucune structure trouvée.</div>
    <?php else: ?>
      <div class="row row-cols-1 row-cols-md-3 g-3 text-start">
        <?php foreach ($structuresListe as $s): ?>
          <div class="col">
            <div class="card h-100 shadow-sm">
              <div class="card-body d-flex flex-column">
                <h5 class="card-title mb-0"><?= htmlspecialchars($s['nom_struc']) ?></h5>
                <p class="text-secondary small mb-2"><?= htmlspecialchars($s['sigle'] ?? '') ?></p>
                <span class="badge bg-success-subtle text-success mb-2 align-self-start"><?= htmlspecialchars($s['nom_cat']) ?></span>
                <p class="card-text small text-secondary flex-grow-1"><?= htmlspecialchars($s['adresse'] ?? 'Adresse non renseignée') ?></p>
                <a href="fiche.php?id=<?= $s['id_struc'] ?>" class="btn btn-outline-success btn-sm mt-2">Voir la fiche</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="mt-4" aria-label="Pagination des structures">
          <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page - 1 ?><?= $catFiltre ? '&cat=' . $catFiltre : '' ?>">Précédent</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?><?= $catFiltre ? '&cat=' . $catFiltre : '' ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page + 1 ?><?= $catFiltre ? '&cat=' . $catFiltre : '' ?>">Suivant</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require '../../includes/footer.php'; ?>
<script>
document.getElementById('search').addEventListener('keyup', function() {
    let q = this.value;

    if (q.length < 1) {
        document.getElementById('results').innerHTML = "";
        return;
    }

    fetch('search_structures.php?q=' + encodeURIComponent(q))
        .then(response => response.text())
        .then(data => {
            document.getElementById('results').innerHTML = data;
        });
});
</script>
