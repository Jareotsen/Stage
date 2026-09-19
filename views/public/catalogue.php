<?php
session_start();
require '../../config/database.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : "";
$catFiltre = isset($_GET['cat']) ? (int) $_GET['cat'] : null;
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'nom_asc';
$parPage = 8;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

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
$whereSQL = $conditions ? " WHERE " . implode(" AND ", $conditions) : "";

$ordreSQL = $tri === 'nom_desc' ? "structure.nom_struc DESC" : "structure.nom_struc ASC";

$compteStmt = $db->prepare("SELECT COUNT(*) FROM structure JOIN categories ON structure.id_cat = categories.id_cat" . $whereSQL);
$compteStmt->execute($parametres);
$totalStructures = (int) $compteStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalStructures / $parPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $parPage;

$sql = "SELECT structure.id_struc, structure.nom_struc, structure.sigle, structure.adresse,
               categories.id_cat, categories.nom_cat
        FROM structure
        JOIN categories ON structure.id_cat = categories.id_cat"
        . $whereSQL .
        " ORDER BY $ordreSQL LIMIT $parPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($parametres);
$structures = $stmt->fetchAll(PDO::FETCH_ASSOC);

$toutesCategories = $db->query("SELECT id_cat, nom_cat FROM categories ORDER BY nom_cat")->fetchAll(PDO::FETCH_ASSOC);

// Palette de couleurs de badge, assignée par id de catégorie (peu importe le nom)
function classeBadge($idCat) {
    return $idCat % 2 === 0 ? 'badge-bleu' : 'badge-vert';
}
require '../../includes/header.php';
?>

<style>
.catalogue-hero { background: linear-gradient(180deg, #3260bc 0%, var(--mrri-bg) 100%); padding: 2.5rem 0 2rem; border-bottom: 1px solid var(--mrri-border); }
.catalogue-hero-inner { display: flex; align-items: flex-start; gap: 1.1rem; }
.catalogue-hero-icone { width: 58px; height: 58px; border-radius: 50%; background: var(--mrri-vert); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.catalogue-hero-texte { border-left: 2px solid var(--mrri-border); padding-left: 1.1rem; }
.catalogue-hero-texte h1 { font-size: 1.9rem; margin-bottom: 0.3rem; }
.catalogue-hero-texte p { color: var(--mrri-text-light); margin: 0; max-width: 60ch; }

.catalogue-barre {
  background: #fff; border-radius: var(--radius-lg); box-shadow: var(--shadow-md);
  padding: 1rem; margin-top: -1.5rem; position: relative; z-index: 5;
  display: flex; gap: 0.75rem; flex-wrap: wrap;
}
.catalogue-barre .search-input-wrapper { position: relative; flex: 2; min-width: 240px; }
.catalogue-barre .search-input-wrapper svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--mrri-text-light); }
.catalogue-barre input[type="text"] { width: 100%; height: 50px; padding: 0 14px 0 42px; border: 1px solid var(--mrri-border); border-radius: 8px; font-size: 0.92rem; outline: none; }
.catalogue-barre input[type="text"]:focus { border-color: var(--mrri-vert); box-shadow: 0 0 0 3px rgba(15,138,75,.10); }
.catalogue-barre select { flex: 1; min-width: 200px; height: 50px; border: 1px solid var(--mrri-border); border-radius: 8px; padding: 0 14px; font-size: 0.9rem; background: #fff; }
.catalogue-barre button { height: 50px; padding: 0 1.6rem; border: none; border-radius: 8px; background: var(--mrri-bleu); color: #fff; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; }
.catalogue-barre button:hover { background: var(--mrri-bleu-dark); }

.filtres-rapides { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin: 1.5rem 0 1rem; }
.filtres-rapides .label { font-weight: 600; font-size: 0.88rem; color: var(--mrri-text); margin-right: 4px; }
.pastille-cat {
  padding: 8px 16px; border-radius: 999px; border: 1px solid var(--mrri-border);
  background: #fff; color: var(--mrri-text-light); font-size: 0.82rem; font-weight: 600;
}
.pastille-cat:hover { border-color: var(--mrri-vert); color: var(--mrri-vert); }
.pastille-cat.actif { background: var(--mrri-vert); border-color: var(--mrri-vert); color: #fff; }

.resultats-ligne { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem; }
.resultats-compte { font-weight: 700; color: var(--mrri-text); }
.tri-select { border: 1px solid var(--mrri-border); border-radius: 8px; padding: 0.4rem 0.8rem; font-size: 0.85rem; background: #fff; }

.structure-carte-2 {
  height: 100%; background: #fff; border: 1px solid var(--mrri-border); border-radius: var(--radius-md);
  padding: 1.4rem; transition: transform .15s ease, box-shadow .15s ease;
}
.structure-carte-2:hover { transform: translateY(-3px); box-shadow: var(--shadow-sm); }
.structure-carte-2-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 0.9rem; }
.structure-logo-rond {
  width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
  background: var(--mrri-bg); border: 1px solid var(--mrri-border);
  display: flex; align-items: center; justify-content: center; color: var(--mrri-bleu); font-size: 1.1rem;
}
.structure-carte-2 h3 { font-size: 1rem; line-height: 1.35; margin-bottom: 3px; min-height: 2.7rem; }
.structure-carte-2 .sigle { color: var(--mrri-text-light); font-size: 0.78rem; font-weight: 600; margin-bottom: 0.7rem; }
.structure-carte-2 .desc { color: var(--mrri-text-light); font-size: 0.82rem; margin-bottom: 0.8rem; }
.structure-carte-2 .adresse-ligne { display: flex; align-items: center; gap: 6px; color: var(--mrri-text-light); font-size: 0.8rem; margin-bottom: 0.9rem; }
.structure-carte-2 .voir-fiche { font-weight: 700; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; }

.badge-vert { background: #E5F5EC; color: var(--mrri-vert-dark); }
.badge-bleu { background: #E8EDFB; color: var(--mrri-bleu); }
.badge-cat { font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 999px; }

.pagination-ronde { display: flex; justify-content: center; gap: 8px; margin-top: 2.5rem; }
.pagination-ronde a, .pagination-ronde span {
  width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  border: 1px solid var(--mrri-border); color: var(--mrri-text); font-weight: 600; font-size: 0.88rem;
}
.pagination-ronde a:hover { border-color: var(--mrri-bleu); color: var(--mrri-bleu); }
.pagination-ronde .actif { background: var(--mrri-bleu); border-color: var(--mrri-bleu); color: #fff; }
.pagination-ronde .desactive { opacity: 0.4; pointer-events: none; }
</style>

<section class="catalogue-hero">
  <div class="container">
    <div class="catalogue-hero-inner">
      <div class="catalogue-hero-icone">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="21" x2="21" y2="21"/><line x1="5" y1="21" x2="5" y2="10"/><line x1="19" y1="21" x2="19" y2="10"/>
          <line x1="9" y1="21" x2="9" y2="10"/><line x1="15" y1="21" x2="15" y2="10"/><polygon points="12 3 21 9 3 9"/>
        </svg>
      </div>
      <div class="catalogue-hero-texte">
        <h1>Catalogue des structures</h1>
        <p>Découvrez l'ensemble des structures placées sous la tutelle du Ministère des Réformes et des Relations avec les Institutions.</p>
      </div>
    </div>

    <form action="" method="get" class="catalogue-barre">
      <div class="search-input-wrapper">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <input type="text" name="q" placeholder="Rechercher une structure, un sigle, une catégorie..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <select name="cat" onchange="this.form.submit()">
        <option value="">Toutes les catégories</option>
        <?php foreach ($toutesCategories as $cat): ?>
          <option value="<?= $cat['id_cat'] ?>" <?= $catFiltre === (int) $cat['id_cat'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nom_cat']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        Rechercher
      </button>
    </form>
  </div>
</section>

<main class="container my-4">
  <div class="filtres-rapides">
    <span class="label">Filtrer par catégorie :</span>
    <a href="?<?= $search ? 'q=' . urlencode($search) . '&' : '' ?>" class="pastille-cat <?= $catFiltre === null ? 'actif' : '' ?>">Toutes</a>
    <?php foreach ($toutesCategories as $cat): ?>
      <a href="?cat=<?= $cat['id_cat'] ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="pastille-cat <?= $catFiltre === (int) $cat['id_cat'] ? 'actif' : '' ?>">
        <?= htmlspecialchars($cat['nom_cat']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="resultats-ligne">
    <span class="resultats-compte"><?= $totalStructures ?> structure(s) trouvée(s)</span>
    <form action="" method="get">
      <?php if ($search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
      <?php if ($catFiltre): ?><input type="hidden" name="cat" value="<?= $catFiltre ?>"><?php endif; ?>
      <select name="tri" class="tri-select" onchange="this.form.submit()">
        <option value="nom_asc" <?= $tri === 'nom_asc' ? 'selected' : '' ?>>Trier par : Nom (A → Z)</option>
        <option value="nom_desc" <?= $tri === 'nom_desc' ? 'selected' : '' ?>>Trier par : Nom (Z → A)</option>
      </select>
    </form>
  </div>

  <?php if (empty($structures)): ?>
    <div class="alert alert-light border text-center">Aucune structure ne correspond à votre recherche.</div>
  <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
      <?php foreach ($structures as $s): ?>
        <div class="col">
          <div class="structure-carte-2">
            <div class="structure-carte-2-top">
              <div class="structure-logo-rond">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="21" x2="21" y2="21"/><line x1="5" y1="21" x2="5" y2="10"/><line x1="19" y1="21" x2="19" y2="10"/><line x1="9" y1="21" x2="9" y2="10"/><line x1="15" y1="21" x2="15" y2="10"/><polygon points="12 3 21 9 3 9"/></svg>
              </div>
              <span class="badge-cat <?= classeBadge($s['id_cat']) ?>"><?= htmlspecialchars($s['nom_cat']) ?></span>
            </div>
            <h3><?= htmlspecialchars($s['nom_struc']) ?></h3>
            <p class="sigle"><?= htmlspecialchars($s['sigle'] ?? '') ?></p>
            <p class="adresse-ligne">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <?= htmlspecialchars($s['adresse'] ?? 'Adresse non renseignée') ?>
            </p>
            <a href="fiche.php?id=<?= $s['id_struc'] ?>" class="voir-fiche" style="color: var(--mrri-bleu);">Voir + <span>→</span></a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="pagination-ronde">
        <a href="?page=<?= $page - 1 ?><?= $catFiltre ? '&cat=' . $catFiltre : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>&tri=<?= $tri ?>" class="<?= $page <= 1 ? 'desactive' : '' ?>">‹</a>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?= $i ?><?= $catFiltre ? '&cat=' . $catFiltre : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>&tri=<?= $tri ?>" class="<?= $i === $page ? 'actif' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <a href="?page=<?= $page + 1 ?><?= $catFiltre ? '&cat=' . $catFiltre : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>&tri=<?= $tri ?>" class="<?= $page >= $totalPages ? 'desactive' : '' ?>">›</a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<?php require '../../includes/footer.php'; ?>