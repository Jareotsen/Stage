<?php
session_start();
require '../../config/database.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : "";
$catFiltre = isset($_GET['cat']) ? (int) $_GET['cat'] : null;

$conditions = [];
$parametres = [];

if ($search !== "") {
    $conditions[] = "(structure.nom_struc LIKE :q OR structure.sigle LIKE :q OR structure.adresse LIKE :q OR structure.directeur_general LIKE :q OR categories.nom_cat LIKE :q)";
    $parametres[':q'] = "%$search%";
}
if ($catFiltre) {
    $conditions[] = "categories.id_cat = :cat";
    $parametres[':cat'] = $catFiltre;
}

$sql = "SELECT structure.id_struc, structure.nom_struc, structure.adresse, 
               structure.directeur_general, categories.nom_cat, categories.id_cat, structure.sigle
        FROM structure
        JOIN categories ON structure.id_cat = categories.id_cat";
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $db->prepare($sql);
$stmt->execute($parametres);
$structures = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoriesStmt = $db->query("SELECT id_cat, nom_cat FROM categories ORDER BY nom_cat");
$toutesCategories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>

<section class="bg-light py-4 border-bottom">
  <div class="container text-center">
    <h1 class="h3 fw-bold">Catalogue des structures</h1>

    <form action="" method="get" class="mx-auto mb-3" style="max-width: 600px;">
      <?php if ($catFiltre): ?><input type="hidden" name="cat" value="<?= $catFiltre ?>"><?php endif; ?>
      <input type="text" name="q" class="form-control form-control-lg" placeholder="Rechercher une structure (nom, sigle...)" value="<?= htmlspecialchars($search) ?>">
    </form>

    <div class="d-flex flex-wrap justify-content-center gap-2">
      <a href="catalogue.php<?= $search ? '?q=' . urlencode($search) : '' ?>" class="btn btn-sm rounded-pill <?= $catFiltre === null ? 'btn-mrri' : 'btn-outline-success' ?>">Tous</a>
      <?php foreach ($toutesCategories as $cat): ?>
        <a href="catalogue.php?cat=<?= $cat['id_cat'] ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
           class="btn btn-sm rounded-pill <?= $catFiltre === (int) $cat['id_cat'] ? 'btn-mrri' : 'btn-outline-success' ?>">
          <?= htmlspecialchars($cat['nom_cat']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<main class="container my-5">
  <p class="text-secondary"><?= count($structures) ?> résultat(s) trouvé(s)</p>

  <?php if (empty($structures)): ?>
    <div class="alert alert-light border text-center">Aucune structure ne correspond à votre recherche.</div>
  <?php else: ?>
    <div class="row row-cols-1 row-cols-md-3 g-3">
      <?php foreach ($structures as $s): ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
<h5 class="card-title mb-0 structure-carte-titre"><?= htmlspecialchars($s['nom_struc']) ?></h5>
              <p class="text-secondary small mb-2"><?= htmlspecialchars($s['sigle'] ?? '') ?></p>
              <span class="badge bg-success-subtle text-success mb-2 align-self-start"><?= htmlspecialchars($s['nom_cat']) ?></span>
              <p class="card-text small text-secondary flex-grow-1"><?= htmlspecialchars($s['adresse'] ?? 'Adresse non renseignée') ?></p>
              <a href="fiche.php?id=<?= $s['id_struc'] ?>" class="btn btn-outline-success btn-sm mt-2">voir +</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php require '../../includes/footer.php'; ?>