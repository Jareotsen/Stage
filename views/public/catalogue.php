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

function classeBadge($idCat) {
    return ((int) $idCat % 2 === 0) ? 'badge-bleu' : 'badge-vert';
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
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($params);
}

require '../../includes/header.php';
?>

<style>
/* Styles propres au catalogue : les composants partagés restent dans header.php. */
.catalogue-page { padding-bottom: 4rem; }
.catalogue-hero {
  position: relative; overflow: hidden; padding: clamp(2.5rem, 5vw, 4.4rem) 0 3.3rem;
  color: #fff; background: linear-gradient(118deg, #0B1D4D 0%, #173B91 68%, #176B58 100%);
}
.catalogue-hero::before, .catalogue-hero::after { content: ""; position: absolute; border: 1px solid rgba(255,255,255,.13); border-radius: 50%; pointer-events: none; }
.catalogue-hero::before { width: 360px; height: 360px; right: -90px; top: -190px; }
.catalogue-hero::after { width: 240px; height: 240px; right: 70px; bottom: -175px; }
.catalogue-hero .container { position: relative; z-index: 1; }
.catalogue-kicker { display: inline-flex; align-items: center; gap: .55rem; margin-bottom: .9rem; color: #F7EACB; font-size: .75rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
.catalogue-kicker::before { content: ""; width: 28px; height: 3px; border-radius: 5px; background: #C9972C; }
.catalogue-hero h1 { max-width: 760px; color: #fff; font-family: var(--font-title); font-size: clamp(2rem, 4vw, 3.15rem); line-height: 1.12; margin: 0 0 1rem; }
.catalogue-hero p { max-width: 680px; color: rgba(255,255,255,.82); font-size: 1rem; line-height: 1.8; margin: 0; }
.catalogue-stats { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: 1.5rem; }
.catalogue-stat { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem .85rem; border: 1px solid rgba(255,255,255,.22); border-radius: 999px; background: rgba(255,255,255,.08); color: #fff; font-size: .82rem; }
.catalogue-stat strong { color: #fff; font-size: .95rem; }
.catalogue-tools { position: relative; z-index: 3; margin-top: -1.45rem; }
.catalogue-search-panel { padding: 1rem; border: 1px solid var(--mrri-border); border-radius: 16px; background: #fff; box-shadow: 0 14px 38px rgba(11,29,77,.12); }
.catalogue-search-form { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(190px, .45fr) auto; gap: .75rem; }
.catalogue-field { position: relative; min-width: 0; }
.catalogue-field .field-icon { position: absolute; top: 50%; left: 1rem; transform: translateY(-50%); color: #667085; pointer-events: none; }
.catalogue-field input, .catalogue-field select { width: 100%; min-height: 50px; border: 1px solid #D7DDE7; border-radius: 10px; background: #fff; color: var(--mrri-text); font: inherit; font-size: .9rem; }
.catalogue-field input { padding: .7rem 1rem .7rem 2.8rem; }
.catalogue-field select { padding: .7rem 2.3rem .7rem .9rem; }
.catalogue-field input:focus, .catalogue-field select:focus, .catalogue-sort select:focus { outline: 0; border-color: var(--mrri-vert); box-shadow: 0 0 0 3px rgba(15,138,75,.12); }
.catalogue-submit { display: inline-flex; align-items: center; justify-content: center; gap: .55rem; min-height: 50px; padding: .7rem 1.3rem; border: 1px solid var(--mrri-bleu); border-radius: 10px; background: var(--mrri-bleu); color: #fff; font: inherit; font-weight: 700; transition: background .18s, transform .18s; }
.catalogue-submit:hover { background: var(--mrri-bleu-dark); transform: translateY(-1px); }
.catalogue-content { padding-top: 2.1rem; }
.catalogue-section-title { margin: 0 0 .35rem; font-size: 1.35rem; }
.catalogue-section-intro { margin: 0; color: var(--mrri-text-light); font-size: .9rem; }
.catalogue-filters { display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; margin: 1.3rem 0 1.7rem; }
.catalogue-filter-label { margin-right: .25rem; color: #475467; font-size: .83rem; font-weight: 700; }
.catalogue-chip { display: inline-flex; align-items: center; min-height: 35px; padding: .45rem .85rem; border: 1px solid #DDE3EB; border-radius: 999px; background: #fff; color: #475467; font-size: .8rem; font-weight: 650; transition: all .18s; }
.catalogue-chip:hover { border-color: var(--mrri-vert); color: var(--mrri-vert-dark); }
.catalogue-chip.active { border-color: var(--mrri-vert); background: var(--mrri-vert); color: #fff; }
.catalogue-results-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
.catalogue-result-count { display: flex; align-items: center; gap: .65rem; color: var(--mrri-text-light); font-size: .88rem; }
.catalogue-result-count strong { color: var(--mrri-bleu-nuit); font-size: 1rem; }
.catalogue-result-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--mrri-vert); box-shadow: 0 0 0 4px #E5F5EC; }
.catalogue-sort { display: flex; align-items: center; gap: .55rem; color: var(--mrri-text-light); font-size: .82rem; }
.catalogue-sort select { min-height: 40px; max-width: 235px; padding: .45rem 2rem .45rem .7rem; border: 1px solid #D7DDE7; border-radius: 9px; background: #fff; color: var(--mrri-text); font: inherit; }
/* L'espace du logo qui dépasse est réservé sur la colonne : la carte reste dans sa case et ne déborde plus sur la rangée suivante */
.catalogue-grille > .col { display: flex; flex-direction: column; padding-top: 2.5rem; }
.structure-carte-2 { position: relative; display: flex; flex: 1 1 auto; flex-direction: column; align-items: stretch; min-height: 300px; padding: 0 1.25rem 1.25rem; overflow: visible; border: 1px solid #E1E6EF; border-radius: 18px; background: #fff; box-shadow: 0 5px 18px rgba(11,29,77,.045); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.structure-carte-2::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, var(--mrri-vert), var(--mrri-or)); opacity: 0; transition: opacity .2s ease; }
.structure-carte-2:hover { transform: translateY(-4px); border-color: #C9D4E8; box-shadow: 0 13px 28px rgba(11,29,77,.09); }
.structure-carte-2:hover::before { opacity: 1; }
.structure-carte-2-top { display: flex; align-items: center; justify-content: center; margin: -2.5rem 0 1.1rem; }
.structure-logo-rond { display: flex; flex: 0 0 92px; align-items: center; justify-content: center; width: 92px; height: 92px; border: 7px solid #D7E8FB; border-radius: 50%; background: #F9FBFF; color: var(--mrri-bleu); box-shadow: 0 7px 16px rgba(11,29,77,.13); }
.structure-logo-rond svg { width: 36px; height: 36px; }
.structure-carte-2 h3 { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; overflow-wrap: anywhere; min-height: 2.8rem; margin: 0 0 .35rem; color: var(--mrri-bleu-nuit); font-family: var(--font-title); font-size: 1.15rem; line-height: 1.35; text-align: center; }
.structure-carte-2 .sigle { margin: 0 0 1rem; color: var(--mrri-text-light); font-size: .82rem; font-weight: 650; letter-spacing: .01em; text-align: center; }
.structure-carte-2 .adresse-ligne { display: flex; align-items: flex-start; gap: .5rem; margin: 0 0 1.1rem; color: var(--mrri-text-light); font-size: .8rem; line-height: 1.5; overflow-wrap: anywhere; }
.structure-carte-2 .adresse-ligne svg { flex: 0 0 14px; margin-top: 3px; color: var(--mrri-vert); }
.structure-carte-2 .voir-fiche { display: inline-flex; align-items: center; gap: .45rem; margin-top: auto; padding-top: .8rem; border-top: 1px solid #EEF1F5; color: var(--mrri-bleu); font-size: .84rem; font-weight: 800; }
.structure-carte-2 .voir-fiche span { transition: transform .18s ease; }
.structure-carte-2 .voir-fiche:hover { color: var(--mrri-vert-dark); }
.structure-carte-2 .voir-fiche:hover span { transform: translateX(4px); }
.catalogue-empty { padding: 3rem 1.25rem; border: 1px dashed #CDD5E1; border-radius: 16px; background: #fff; text-align: center; }
.catalogue-empty-icon { display: flex; align-items: center; justify-content: center; width: 58px; height: 58px; margin: 0 auto 1rem; border-radius: 16px; background: #F0F4FB; color: var(--mrri-bleu); }
.catalogue-empty h2 { margin-bottom: .4rem; font-size: 1.2rem; }
.catalogue-empty p { margin: 0; color: var(--mrri-text-light); font-size: .9rem; }
.pagination-ronde { position: relative; z-index: 1; display: flex; justify-content: center; flex-wrap: wrap; gap: .45rem; margin-top: 3rem; }
.pagination-ronde a, .pagination-ronde span { display: inline-flex; align-items: center; justify-content: center; min-width: 39px; height: 39px; padding: 0 .65rem; border: 1px solid #DDE3EB; border-radius: 10px; background: #fff; color: var(--mrri-text); font-size: .85rem; font-weight: 750; }
.pagination-ronde a:hover { border-color: var(--mrri-vert); color: var(--mrri-vert-dark); }
.pagination-ronde .actif { border-color: var(--mrri-bleu); background: var(--mrri-bleu); color: #fff; }
.pagination-ronde .desactive { opacity: .4; pointer-events: none; }
@media (max-width: 767.98px) {
  .catalogue-search-form { grid-template-columns: 1fr; }
  .catalogue-submit { width: 100%; }
  .catalogue-results-head { align-items: flex-start; flex-direction: column; }
  .catalogue-sort { width: 100%; justify-content: space-between; }
  .catalogue-sort select { max-width: 65%; }
  .catalogue-hero { padding-bottom: 2.8rem; }
  .catalogue-content { padding-top: 1.7rem; }
}
@media (prefers-reduced-motion: reduce) { .structure-carte-2, .catalogue-chip, .catalogue-submit { transition: none; } }
</style>

<div class="catalogue-page">
  <section class="catalogue-hero">
    <div class="container">
      <div class="catalogue-kicker">Portail institutionnel du Gabon</div>
      <h1>Catalogue des structures</h1>
      <p>Retrouvez les informations essentielles sur les Institutions constitutionnelles et les A.A.I</p>
      <div class="catalogue-stats" aria-label="Résumé du catalogue">
        <span class="catalogue-stat"><strong><?= $totalStructures ?></strong> structure(s) affichée(s) selon vos critères</span>
        <span class="catalogue-stat">Répertoire institutionnel officiel</span>
      </div>
    </div>
  </section>

  <div class="container catalogue-tools">
    <div class="catalogue-search-panel">
      <form action="" method="get" class="catalogue-search-form" role="search" aria-label="Rechercher dans le catalogue">
        <div class="catalogue-field">
          <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
          <input type="text" name="q" aria-label="Rechercher une structure" placeholder="Nom, sigle, adresse ou catégorie…" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="catalogue-field">
          <select name="cat" aria-label="Filtrer par catégorie">
            <option value="">Toutes les catégories</option>
            <?php foreach ($toutesCategories as $cat): ?>
              <option value="<?= (int) $cat['id_cat'] ?>" <?= $catFiltre === (int) $cat['id_cat'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nom_cat'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="catalogue-submit">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
          Rechercher
        </button>
        <?php if ($search !== '' || $catFiltre): ?>
          <a href="catalogue.php" class="small text-decoration-underline" style="grid-column:1/-1; color:var(--mrri-text-light);">Effacer les filtres</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <main class="container catalogue-content">
    <div>
      <h2 class="catalogue-section-title">Explorez les structures</h2>
      <p class="catalogue-section-intro">Utilisez les catégories ou la recherche pour trouver rapidement une structure.</p>
    </div>

    <nav class="catalogue-filters" aria-label="Filtres rapides par catégorie">
      <span class="catalogue-filter-label">Catégories :</span>
      <a href="<?= htmlspecialchars(lienCatalogue(['cat' => '', 'page' => 1]), ENT_QUOTES, 'UTF-8') ?>" class="catalogue-chip <?= !$catFiltre ? 'active' : '' ?>">Toutes</a>
      <?php foreach ($toutesCategories as $cat): ?>
        <a href="<?= htmlspecialchars(lienCatalogue(['cat' => (int) $cat['id_cat'], 'page' => 1]), ENT_QUOTES, 'UTF-8') ?>" class="catalogue-chip <?= $catFiltre === (int) $cat['id_cat'] ? 'active' : '' ?>">
          <?= htmlspecialchars($cat['nom_cat'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="catalogue-results-head">
      <div class="catalogue-result-count"><span class="catalogue-result-dot" aria-hidden="true"></span><span><strong><?= $totalStructures ?></strong> résultat(s) trouvé(s)</span></div>
      <form action="" method="get" class="catalogue-sort">
        <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
        <?php if ($catFiltre): ?><input type="hidden" name="cat" value="<?= (int) $catFiltre ?>"><?php endif; ?>
        <label for="tri">Trier par</label>
        <select name="tri" id="tri" onchange="this.form.submit()">
          <option value="nom_asc" <?= $tri === 'nom_asc' ? 'selected' : '' ?>>Nom (A à Z)</option>
          <option value="nom_desc" <?= $tri === 'nom_desc' ? 'selected' : '' ?>>Nom (Z à A)</option>
        </select>
      </form>
    </div>

    <?php if (empty($structures)): ?>
      <div class="catalogue-empty">
        <div class="catalogue-empty-icon" aria-hidden="true"><svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></div>
        <h2>Aucune structure trouvée</h2>
        <p>Essayez avec un autre mot-clé ou choisissez une autre catégorie.</p>
      </div>
    <?php else: ?>
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-3 gy-4 catalogue-grille">
        <?php foreach ($structures as $s): ?>
          <div class="col">
            <article class="structure-carte-2">
              <div class="structure-carte-2-top">
                <div class="structure-logo-rond" aria-hidden="true">
                  <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6M9 10h.01M15 10h.01"/></svg>
                </div>
              </div>
              <h3><?= htmlspecialchars($s['nom_struc'], ENT_QUOTES, 'UTF-8') ?></h3>
              <p class="sigle"><?= htmlspecialchars($s['sigle'] ?? 'Sigle non renseigné', ENT_QUOTES, 'UTF-8') ?></p>
              <p class="adresse-ligne">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span><?= htmlspecialchars($s['adresse'] ?? 'Adresse non renseignée', ENT_QUOTES, 'UTF-8') ?></span>
              </p>
              <a href="fiche.php?id=<?= (int) $s['id_struc'] ?>" class="voir-fiche">Consulter la fiche <span aria-hidden="true">→</span></a>
            </article>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pagination-ronde" aria-label="Pagination du catalogue">
          <a href="<?= htmlspecialchars(lienCatalogue(['page' => max(1, $page - 1)]), ENT_QUOTES, 'UTF-8') ?>" class="<?= $page <= 1 ? 'desactive' : '' ?>" aria-label="Page précédente">‹</a>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
              <span class="actif" aria-current="page"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= htmlspecialchars(lienCatalogue(['page' => $i]), ENT_QUOTES, 'UTF-8') ?>" aria-label="Page <?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <a href="<?= htmlspecialchars(lienCatalogue(['page' => min($totalPages, $page + 1)]), ENT_QUOTES, 'UTF-8') ?>" class="<?= $page >= $totalPages ? 'desactive' : '' ?>" aria-label="Page suivante">›</a>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</div>

<?php require '../../includes/footer.php'; ?>
