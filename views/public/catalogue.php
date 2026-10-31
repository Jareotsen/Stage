<?php
session_start();
require '../../config/database.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : "";
$catFiltre = isset($_GET['cat']) ? (int) $_GET['cat'] : null;
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'nom_asc';
$parPage = 9;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

$conditions = [];
$parametres = [];

if ($search !== "") {
    $conditions[] = "(structure.nom_struc LIKE :q OR structure.sigle LIKE :q OR structure.adresse LIKE :q OR structure.directeur_general LIKE :q OR categories.nom_cat LIKE :q)";
    $parametres[':q'] = "%$search%";
}
if ($catFiltre) {
    // Une catégorie parente ramène aussi les structures de ses sous-catégories
    $conditions[] = "(categories.id_cat = :cat OR categories.parent_id = :cat)";
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

/* =========================================================
   ARBRE DES CATÉGORIES (colonne de filtres) avec le nombre
   de structures ; un parent cumule celles de ses enfants.
   ========================================================= */
$toutesCategories = $db->query("SELECT id_cat, nom_cat, parent_id FROM categories ORDER BY nom_cat")->fetchAll(PDO::FETCH_ASSOC);
$nbParCategorie = $db->query("SELECT id_cat, COUNT(*) FROM structure GROUP BY id_cat")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalGeneral = array_sum($nbParCategorie);

$categoriesRacines = [];
$sousCategories = [];
foreach ($toutesCategories as $cat) {
    $cat['nb'] = (int) ($nbParCategorie[$cat['id_cat']] ?? 0);
    if ($cat['parent_id'] === null) {
        $categoriesRacines[] = $cat;
    } else {
        $sousCategories[(int) $cat['parent_id']][] = $cat;
    }
}
foreach ($categoriesRacines as &$racine) {
    foreach ($sousCategories[(int) $racine['id_cat']] ?? [] as $enfant) {
        $racine['nb'] += $enfant['nb'];
    }
}
unset($racine);

$nomCategorieActive = null;
foreach ($toutesCategories as $cat) {
    if ($catFiltre === (int) $cat['id_cat']) {
        $nomCategorieActive = $cat['nom_cat'];
    }
}

function h(?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES, 'UTF-8');
}

// Les noms de catégories sont stockés en majuscules : on les rend plus lisibles.
function libelleCategorie(string $nom): string {
    return mb_convert_case(mb_strtolower($nom, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

// Conserve les filtres lors de la navigation entre les pages.
function lienCatalogue(array $modifications = []): string {
    $params = [
        'q' => isset($_GET['q']) ? trim((string) $_GET['q']) : '',
        'cat' => isset($_GET['cat']) ? (int) $_GET['cat'] : '',
        'tri' => isset($_GET['tri']) ? (string) $_GET['tri'] : 'nom_asc',
        'page' => isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1,
    ];
    foreach ($modifications as $cle => $valeur) {
        $params[$cle] = $valeur;
    }
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null && $v !== 0);
    return '?' . http_build_query($params);
}

require '../../includes/header.php';
?>

<style>
/* Styles propres au catalogue : les composants communs sont dans publique/css/portail.css */
.cat-mise-en-page { display: grid; grid-template-columns: 290px minmax(0, 1fr); gap: clamp(2rem, 4vw, 3.5rem); align-items: start; }
.cat-filtres { position: sticky; top: 100px; display: grid; gap: 2rem; }
.cat-filtres h2 { margin: 0 0 .9rem; padding-bottom: .6rem; border-bottom: 2px solid var(--acc-encre); color: var(--acc-encre); font-size: .8rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.cat-filtres .acc-champ input { height: 48px; font-size: .9rem; }
.cat-filtres .acc-champ button { height: 48px; padding: 0 1rem; }
.cat-liste, .cat-liste ul { margin: 0; padding: 0; list-style: none; }
.cat-liste ul { margin: .1rem 0 .3rem .9rem; padding-left: .7rem; border-left: 1px solid var(--acc-ligne); }
.cat-lien { display: flex; align-items: flex-start; justify-content: space-between; gap: .8rem; padding: .6rem .75rem; border-left: 3px solid transparent; color: var(--acc-encre); font-size: .88rem; font-weight: 600; line-height: 1.35; transition: background .2s ease, border-color .2s ease; }
.cat-liste ul .cat-lien { font-size: .84rem; font-weight: 500; }
.cat-lien:hover { background: var(--acc-fond); color: var(--acc-encre); }
.cat-lien.actif { border-left-color: var(--acc-vert); background: #E9F6EF; color: var(--acc-vert-fonce); }
.cat-lien .nb { flex-shrink: 0; min-width: 26px; padding: .1rem .45rem; border-radius: 999px; background: var(--acc-fond); color: var(--acc-gris); font-size: .74rem; font-weight: 700; text-align: center; }
.cat-lien.actif .nb { background: var(--acc-vert); color: #fff; }
.cat-aide { padding: 1.2rem; border-radius: 4px; background: var(--acc-nuit); color: rgba(255,255,255,.8); font-size: .86rem; line-height: 1.55; }
.cat-aide strong { display: block; margin-bottom: .35rem; color: #fff; font-family: var(--font-title); font-size: 1.05rem; font-weight: 600; }
.cat-aide a { display: inline-flex; gap: .4rem; margin-top: .7rem; color: var(--acc-jaune); font-weight: 700; }
.cat-resultats .acc-structures-grille { grid-template-columns: repeat(3, 1fr); }
@media (max-width: 1199.98px) { .cat-resultats .acc-structures-grille { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 991.98px) {
  .cat-mise-en-page { grid-template-columns: 1fr; }
  .cat-filtres { position: static; }
  .cat-aide { display: none; }
}
@media (max-width: 575.98px) { .cat-resultats .acc-structures-grille { grid-template-columns: 1fr; } }
</style>

<header class="acc-bandeau">
  <img class="acc-bandeau-image" src="../../publique/image/GA.jpeg" alt="">
  <div class="container">
    <ol class="acc-fil" aria-label="Fil d'Ariane">
      <li><a href="accueil.php">Accueil</a></li>
      <li aria-current="page">Structures</li>
    </ol>
    <span class="acc-surtitre">Répertoire institutionnel</span>
    <h1>Catalogue des structures</h1>
    <p>Retrouvez les informations essentielles sur les Institutions constitutionnelles et les Autorités administratives indépendantes.</p>
  </div>
</header>

<main class="acc-section">
  <div class="container cat-mise-en-page">

    <aside class="cat-filtres" aria-label="Filtres du catalogue">
      <div>
        <h2>Rechercher</h2>
        <form action="" method="get" class="acc-champ" role="search">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
          <label for="q" class="visually-hidden">Rechercher une structure</label>
          <input type="text" id="q" name="q" placeholder="Nom, sigle, adresse…" value="<?= h($search) ?>">
          <?php if ($catFiltre): ?><input type="hidden" name="cat" value="<?= (int) $catFiltre ?>"><?php endif; ?>
          <?php if ($tri !== 'nom_asc'): ?><input type="hidden" name="tri" value="<?= h($tri) ?>"><?php endif; ?>
          <button type="submit">OK</button>
        </form>
      </div>

      <nav aria-label="Catégories">
        <h2>Catégories</h2>
        <ul class="cat-liste">
          <li>
            <a href="<?= h(lienCatalogue(['cat' => '', 'page' => 1])) ?>" class="cat-lien <?= !$catFiltre ? 'actif' : '' ?>" <?= !$catFiltre ? 'aria-current="true"' : '' ?>>
              Toutes les structures <span class="nb"><?= $totalGeneral ?></span>
            </a>
          </li>
          <?php foreach ($categoriesRacines as $racine): ?>
            <?php $actif = $catFiltre === (int) $racine['id_cat']; ?>
            <li>
              <a href="<?= h(lienCatalogue(['cat' => (int) $racine['id_cat'], 'page' => 1])) ?>" class="cat-lien <?= $actif ? 'actif' : '' ?>" <?= $actif ? 'aria-current="true"' : '' ?>>
                <?= h(libelleCategorie($racine['nom_cat'])) ?> <span class="nb"><?= $racine['nb'] ?></span>
              </a>
              <?php if (!empty($sousCategories[(int) $racine['id_cat']])): ?>
                <ul>
                  <?php foreach ($sousCategories[(int) $racine['id_cat']] as $enfant): ?>
                    <?php $actif = $catFiltre === (int) $enfant['id_cat']; ?>
                    <li>
                      <a href="<?= h(lienCatalogue(['cat' => (int) $enfant['id_cat'], 'page' => 1])) ?>" class="cat-lien <?= $actif ? 'actif' : '' ?>" <?= $actif ? 'aria-current="true"' : '' ?>>
                        <?= h(libelleCategorie($enfant['nom_cat'])) ?> <span class="nb"><?= $enfant['nb'] ?></span>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <div class="cat-aide">
        <strong>Où se trouvent-elles ?</strong>
        Visualisez l'emplacement des institutions sur la carte de Libreville.
        <a href="localisation.php">Voir la carte</a>
      </div>
    </aside>

    <div class="cat-resultats">
      <div class="acc-barre">
        <div>
          <strong><?= $totalStructures ?></strong> structure<?= $totalStructures > 1 ? 's' : '' ?>
          <?php if ($nomCategorieActive): ?>
            <span class="acc-filtre-actif"><?= h(libelleCategorie($nomCategorieActive)) ?>
              <a href="<?= h(lienCatalogue(['cat' => '', 'page' => 1])) ?>" aria-label="Retirer le filtre de catégorie">×</a></span>
          <?php endif; ?>
          <?php if ($search !== ''): ?>
            <span class="acc-filtre-actif">« <?= h($search) ?> »
              <a href="<?= h(lienCatalogue(['q' => '', 'page' => 1])) ?>" aria-label="Retirer la recherche">×</a></span>
          <?php endif; ?>
        </div>
        <form action="" method="get">
          <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= h($search) ?>"><?php endif; ?>
          <?php if ($catFiltre): ?><input type="hidden" name="cat" value="<?= (int) $catFiltre ?>"><?php endif; ?>
          <label>Trier par
            <select name="tri" onchange="this.form.submit()">
              <option value="nom_asc" <?= $tri === 'nom_asc' ? 'selected' : '' ?>>Nom (A à Z)</option>
              <option value="nom_desc" <?= $tri === 'nom_desc' ? 'selected' : '' ?>>Nom (Z à A)</option>
            </select>
          </label>
        </form>
      </div>

      <?php if (empty($structures)): ?>
        <div class="acc-vide">
          <h2>Aucune structure trouvée</h2>
          <p>Essayez avec un autre mot-clé ou choisissez une autre catégorie.</p>
        </div>
      <?php else: ?>
        <div class="acc-structures-grille">
          <?php foreach ($structures as $s): ?>
            <a href="fiche.php?id=<?= (int) $s['id_struc'] ?>" class="acc-structure">
              <div class="acc-structure-tete">
                <span class="acc-structure-icone" aria-hidden="true">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.5v3"/><path d="M12 2.5h3l-.75 1 .75 1h-3"/><path d="M3 9.5 12 5.5l9 4z"/><path d="M4 9.5h16"/><path d="M6 12v6M10 12v6M14 12v6M18 12v6"/><path d="M4.5 12h15M4 18h16M3 21h18"/></svg>
                </span>
                <div>
                  <?php if (!empty($s['sigle'])): ?><div class="acc-structure-sigle"><?= h($s['sigle']) ?></div><?php endif; ?>
                  <div class="acc-structure-cat"><?= h(libelleCategorie($s['nom_cat'])) ?></div>
                </div>
              </div>
              <h3><?= h($s['nom_struc']) ?></h3>
              <p class="acc-structure-adresse">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span><?= h($s['adresse'] ?: 'Adresse non renseignée') ?></span>
              </p>
              <span class="acc-fleche">Consulter la fiche</span>
            </a>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav class="acc-pagination" aria-label="Pagination du catalogue">
            <a href="<?= h(lienCatalogue(['page' => max(1, $page - 1)])) ?>" class="<?= $page <= 1 ? 'desactive' : '' ?>" aria-label="Page précédente">‹</a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <?php if ($i === $page): ?>
                <span class="actif" aria-current="page"><?= $i ?></span>
              <?php else: ?>
                <a href="<?= h(lienCatalogue(['page' => $i])) ?>" aria-label="Page <?= $i ?>"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
            <a href="<?= h(lienCatalogue(['page' => min($totalPages, $page + 1)])) ?>" class="<?= $page >= $totalPages ? 'desactive' : '' ?>" aria-label="Page suivante">›</a>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php require '../../includes/footer.php'; ?>
