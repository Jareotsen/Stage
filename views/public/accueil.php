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

require '../../includes/header_public.php';
?>

<section class="bg-light py-4 text-center border-bottom">
  <div class="container">
    <h1 class="fw-bold">Plateforme de consultation des structures sous tutelle du MRRI</h1>
    <p class="text-secondary mb-4">Centralisation, organisation et accès sécurisé aux informations administratives et opérationnelles des entités publiques.</p>

    <?php if (!empty($actualites)): ?>
      <div id="carouselActus" class="carousel slide shadow rounded overflow-hidden" data-bs-ride="carousel">
        <div class="carousel-indicators">
          <?php foreach ($actualites as $index => $a): ?>
            <button type="button" data-bs-target="#carouselActus" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>"></button>
          <?php endforeach; ?>
        </div>

        <div class="carousel-inner" style="height: 560px;">
          <?php foreach ($actualites as $index => $actualite): ?>
            <?php
            $photosActuStmt = $db->prepare("SELECT * FROM actualites_photos WHERE id_actualites = :id ORDER BY Position");
            $photosActuStmt->execute([':id' => $actualite['id_actu']]);
            $premierePhoto = $photosActuStmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>" style="height: 560px; background-color: #1B7A3D; <?= $premierePhoto ? 'background-image: url(\'../../uploads/photos/' . htmlspecialchars($premierePhoto['Photos_actu_url']) . '\'); background-size: cover; background-position: center;' : '' ?>">
              <div class="carousel-caption text-start bg-dark bg-opacity-50 rounded p-4" style="bottom: 30px; max-width: 700px; margin: 0 0 0 20px;">
                <p class="text-warning small fw-bold mb-1"><?= htmlspecialchars($actualite['nom_struc']) ?></p>
                <h3><?= htmlspecialchars($actualite['titre']) ?></h3>
                <p class="d-none d-md-block"><?= htmlspecialchars($actualite['contenu']) ?></p>
                <p class="small mb-0"><?= htmlspecialchars($actualite['date_publication']) ?></p>
              </div>
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
      <input type="text" name="q" class="form-control form-control-lg" placeholder="Rechercher une structure (nom, sigle)...">
    </form>

    <div class="d-flex flex-wrap justify-content-center gap-2">
      <?php foreach ($categories as $cat): ?>
        <a href="catalogue.php?cat=<?= $cat['id_cat'] ?>" class="btn btn-outline-success btn-sm rounded-pill">
          <?= htmlspecialchars($cat['nom_cat']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require '../../includes/footer.php'; ?>