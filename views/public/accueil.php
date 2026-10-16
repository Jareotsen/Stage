<?php
session_start();
require '../../config/database.php';

// ID de la catégorie "Autorité Administrative Indépendante" en base.
// (id_cat = 5 dans la table categories,)
define('AAI_ID', 5);

$actu = $db->query("
    SELECT 
        id_actu,
        titre,
        contenu,
        structure.nom_struc,
        date_publication
    FROM actualites
    JOIN structure 
        ON actualites.id_structure = structure.id_struc
    ORDER BY date_publication DESC
    LIMIT 3
");
$actualites = $actu->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   CATÉGORIES
   ========================================================= */

// Catégories "feuilles" affichées comme boutons simples,
// en excluant les secteurs de l'AAI (traités à part en dropdown)
// et les catégories intermédiaires sans structure directe (ex: id 2).
$categoriesStmt = $db->prepare("
    SELECT id_cat, nom_cat
    FROM categories
    WHERE id_cat NOT IN (
        SELECT DISTINCT parent_id FROM categories WHERE parent_id IS NOT NULL
    )
    AND (parent_id IS NULL OR parent_id != :aai)
    ORDER BY nom_cat
");
$categoriesStmt->execute([':aai' => AAI_ID]);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Catégorie AAI elle-même (pour le libellé du menu déroulant)
$aaiStmt = $db->prepare("SELECT id_cat, nom_cat FROM categories WHERE id_cat = :aai");
$aaiStmt->execute([':aai' => AAI_ID]);
$aai = $aaiStmt->fetch(PDO::FETCH_ASSOC);

// Les secteurs de l'AAI (contenu du menu déroulant)
$secteursStmt = $db->prepare("
    SELECT id_cat, nom_cat
    FROM categories
    WHERE parent_id = :aai
    ORDER BY nom_cat
");
$secteursStmt->execute([':aai' => AAI_ID]);
$secteursAAI = $secteursStmt->fetchAll(PDO::FETCH_ASSOC);
$secteursAAIIds = array_map('intval', array_column($secteursAAI, 'id_cat'));

/* =========================================================
   STRUCTURES
   ========================================================= */
$parPage = 3;
$page = isset($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;
$catFiltre = isset($_GET['cat'])
    ? (int) $_GET['cat']
    : null;

$conditions = [];
$parametres = [];
if ($catFiltre) {
    // Deux cas possibles :
    // - la structure est rattachée directement à la catégorie cliquée
    //   (ex: "Juridictionnelles")
    // - la catégorie de la structure a pour parent la catégorie cliquée
    //   (ex: clic sur "AAI" -> ramène les structures des 4 secteurs)
    $conditions[] = "(structure.id_cat = :cat OR categories.parent_id = :cat)";
    $parametres[':cat'] = $catFiltre;
}
$whereSQL = $conditions
    ? " WHERE " . implode(" AND ", $conditions)
    : "";

/* Nombre total (le JOIN est nécessaire ici aussi,
   puisque le WHERE référence categories.parent_id) */
$compteStmt = $db->prepare("
    SELECT COUNT(*)
    FROM structure
    JOIN categories
        ON structure.id_cat = categories.id_cat
    $whereSQL
");
$compteStmt->execute($parametres);
$totalStructures = (int) $compteStmt->fetchColumn();
$totalPages = max(
    1,
    (int) ceil($totalStructures / $parPage)
);
$page = min($page, $totalPages);
$offset = ($page - 1) * $parPage;

/* Liste */
$structuresStmt = $db->prepare("
    SELECT
        structure.id_struc,
        structure.nom_struc,
        structure.sigle,
        structure.adresse,
        categories.nom_cat
    FROM structure
    JOIN categories
        ON structure.id_cat = categories.id_cat
    $whereSQL
    ORDER BY structure.nom_struc ASC
    LIMIT $parPage
    OFFSET $offset
");

$structuresStmt->execute($parametres);

$structuresListe = $structuresStmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   HEADER
   ========================================================= */

require '../../includes/header.php';
?>
<style>
/* ---------- Hero (accueil uniquement) ---------- */
.mrri-hero { position: relative; min-height: 480px; overflow: hidden; background: var(--mrri-bleu-nuit); }
.mrri-hero-grid { display: grid; grid-template-columns: 1fr 1fr; min-height: 480px; }
.mrri-hero-texte { padding: 4rem 3rem; display: flex; flex-direction: column; justify-content: center; position: relative; z-index: 2; }
.mrri-hero-image-col { position: relative; overflow: hidden; }
.mrri-hero-image-col img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; }
.mrri-hero-image-col::before { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, var(--mrri-bleu-nuit) 0%, rgba(11,29,77,0) 25%); z-index: 1; }
.mrri-hero-tagline { position: absolute; right: 2rem; bottom: 1.5rem; z-index: 2; color: #fff; font-weight: 600; font-size: 0.95rem; background: rgba(11,29,77,0.55); padding: 0.5rem 1rem; border-radius: 6px; }

.mrri-hero-accent { width: 40px; height: 3px; background: var(--mrri-or); margin-bottom: 1.1rem; }
.mrri-hero-texte h1 { color: #fff; font-size: clamp(2rem, 4vw, 2.75rem); line-height: 1.15; margin-bottom: 1rem; }
.mrri-hero-texte p { color: rgba(255,255,255,0.85); font-size: 1rem; max-width: 46ch; margin-bottom: 1.75rem; }

.mrri-hero-points { display: flex; gap: 1.5rem; flex-wrap: wrap; }
.mrri-hero-point { display: flex; align-items: center; gap: 10px; }
.mrri-hero-point-icone { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.05rem; }
.mrri-hero-point-icone.vert { background: var(--mrri-vert); color: #fff; }
.mrri-hero-point-icone.bleu { background: var(--mrri-bleu); color: #fff; }
.mrri-hero-point-icone.or { background: var(--mrri-or); color: #fff; }
.mrri-hero-point span { color: #fff; font-size: 0.85rem; font-weight: 600; line-height: 1.25; }

/* ---------- Recherche flottante ---------- */
.search-section { position: relative; z-index: 10; margin-top: -48px; margin-bottom: 4.5rem; }
.search-box { background: #fff; border: 1px solid var(--mrri-border); border-radius: var(--radius-md); box-shadow: var(--shadow-md); padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; }
.search-title { font-family: var(--font-title); font-size: 1.15rem; font-weight: 700; color: var(--mrri-bleu-nuit); white-space: nowrap; }
.search-form { display: flex; gap: 10px; flex: 1; min-width: 280px; }
.search-input-wrapper { position: relative; flex: 1; }
.search-input-wrapper svg { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--mrri-text-light); }
.search-input { width: 100%; height: 50px; padding: 0 16px 0 46px; border: 1px solid var(--mrri-border); border-radius: 7px; font-size: 0.92rem; outline: none; }
.search-input:focus { border-color: var(--mrri-vert); box-shadow: 0 0 0 3px rgba(15,138,75,.10); }
.search-button { height: 50px; padding: 0 22px; border: none; border-radius: 7px; background: var(--mrri-vert); color: #fff; font-weight: 700; white-space: nowrap; }
.search-button:hover { background: var(--mrri-vert-dark); }
.search-filtres-btn { height: 50px; padding: 0 18px; border: 1px solid var(--mrri-border); border-radius: 7px; background: #fff; color: var(--mrri-text); font-weight: 600; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
.search-filtres-btn:hover { border-color: var(--mrri-bleu); color: var(--mrri-bleu); }
#results { position: relative; z-index: 20; }

/* ---------- Accès rapides ---------- */
.quick-section {
  padding: 2.5rem 0 4.5rem;
  display: flex;
  justify-content: center; /* Centre le bloc de cartes horizontalement */
  align-items: center;     /* Centre verticalement si nécessaire */
  gap: 1.5rem;            /* Espacement entre les cartes (ajustez selon vos besoins) */
  flex-wrap: wrap;        /* Permet un bon comportement sur écran mobile */
}
.quick-card {
  height: 89%;
  background: #fff;
  border: none;
  border-radius: 16px;
  padding: 1.6rem 1.75rem;
  box-shadow: 0 4px 18px rgba(11,29,77,0.06);
  transition: transform .2s ease, box-shadow .2s ease;
}
.quick-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(11,29,77,0.10); }

.quick-top {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.1rem;
}

.quick-icon {
  width: 56px;
  height: 56px;
  flex-shrink: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  color: #fff;
}
.quick-icon.green { background: var(--mrri-vert); }
.quick-icon.blue { background: var(--mrri-bleu); }
.quick-icon.gold { background: var(--mrri-or); }
.quick-icon.dark { background: var(--mrri-bleu-nuit); }

.quick-card h3 {
  font-size: 1.05rem;
  font-weight: 700;
  line-height: 1.3;
  margin: 0;
}

.quick-card p {
  color: var(--mrri-text-light);
  font-size: 0.88rem;
  line-height: 1.55;
  margin-bottom: 1.4rem;
}

.quick-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
  font-size: 0.92rem;
}
.quick-link span { transition: transform 0.15s ease; }
.quick-link:hover span { transform: translateX(3px); }

.green-link { color: var(--mrri-vert); }
.green-link:hover { color: var(--mrri-vert-dark); }
.blue-link { color: var(--mrri-bleu); }
.blue-link:hover { color: var(--mrri-bleu-dark); }
.gold-link { color: var(--mrri-or); }
.gold-link:hover { color: #8A6511; }
.dark-link { color: var(--mrri-bleu-nuit); }
.dark-link:hover { color: #000; }

/* ---------- Catalogue (section aperçu) ---------- */
.structures-section { padding: 4.5rem 0; background: #fff; border-top: 1px solid var(--mrri-border); border-bottom: 1px solid var(--mrri-border); }
.category-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 2rem; }
.category-filter { display: inline-flex; align-items: center; padding: 8px 15px; border: 1px solid var(--mrri-border); border-radius: 50px; background: #fff; color: var(--mrri-text-light); font-size: .8rem; font-weight: 600; }
.category-filter:hover { border-color: var(--mrri-vert); color: var(--mrri-vert); }
.category-filter.active { background: var(--mrri-vert); border-color: var(--mrri-vert); color: #fff; }
.category-filter.dropdown-toggle::after { margin-left: 8px; }

.structure-card { height: 100%; display: flex; flex-direction: column; background: #fff; border: 1px solid var(--mrri-border); border-radius: var(--radius-md); padding: 1.4rem; }
.structure-card:hover { transform: translateY(-3px); border-color: rgba(27,63,174,.3); box-shadow: var(--shadow-sm); }
.structure-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 14px; }
.structure-icon { flex-shrink: 0; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 9px; background: #E5F5EC; color: var(--mrri-vert); font-size: 1.05rem; }
.structure-category { display: inline-block; padding: 5px 9px; border-radius: 5px; background: #E5F5EC; color: var(--mrri-vert-dark); font-size: .68rem; font-weight: 700; }
.structure-card h3 { min-height: 50px; margin-bottom: 5px; font-size: 1.02rem; line-height: 1.35; }
.structure-sigle { color: var(--mrri-bleu); font-size: .76rem; font-weight: 700; margin-bottom: 12px; }
.structure-address { flex-grow: 1; color: var(--mrri-text-light); font-size: .8rem; margin-bottom: 1.1rem; }
.structure-link { display: inline-flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid var(--mrri-border); color: var(--mrri-bleu); font-size: .8rem; font-weight: 700; }
.structure-link:hover { color: var(--mrri-bleu-dark); }

/* ---------- Actualités ---------- */
.news-section { padding: 4.5rem 0; }
.news-card { height: 100%; overflow: hidden; background: #fff; border: 1px solid var(--mrri-border); border-radius: var(--radius-md); }
.news-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-sm); }
.news-image { position: relative; height: 180px; overflow: hidden; background: var(--mrri-bleu-nuit); }
.news-image img { width: 100%; height: 100%; object-fit: cover; }
.news-image-placeholder { height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--mrri-bleu), var(--mrri-bleu-nuit)); color: rgba(255,255,255,.8); }
.news-badge { position: absolute; left: 14px; bottom: 14px; padding: 5px 10px; border-radius: 5px; background: var(--mrri-vert); color: #fff; font-size: .68rem; font-weight: 700; }
.news-content { padding: 1.1rem; }
.news-date { color: var(--mrri-text-light); font-size: .7rem; margin-bottom: 6px; }
.news-content h3 { font-size: 1rem; line-height: 1.4; margin: 0; }

@media (max-width: 900px) {
  .mrri-hero-grid { grid-template-columns: 1fr; }
  .mrri-hero-image-col { display: none; }
}


/* =========================================================
   REFONTE VISUELLE — ACCUEIL MRRI
   Styles de présentation uniquement : logique PHP conservée.
   ========================================================= */
:root { scroll-behavior: smooth; }
.mrri-top-band { height: 5px; background: linear-gradient(90deg, var(--mrri-vert) 0 33.33%, var(--mrri-or) 33.33% 66.66%, var(--mrri-bleu) 66.66% 100%); }
.search-section { margin-top: -38px; margin-bottom: 3.5rem; }
.search-box { gap: 1rem; padding: clamp(1rem, 2vw, 1.5rem); border-radius: 16px; box-shadow: 0 16px 40px rgba(11,29,77,.12); }
.search-title { white-space: normal; }
.search-input, .search-button, .search-filtres-btn { min-height: 48px; border-radius: 9px; }
.search-button { transition: background .2s ease, transform .2s ease; }
.search-button:hover { transform: translateY(-1px); }
.citation-section { position: relative; overflow: hidden; padding: 5rem 0; background: var(--mrri-bleu-nuit); }
.citation-fond { position: absolute; inset: 0; background-position: center; background-size: cover; opacity: .34; }
.citation-voile { position: absolute; inset: 0; background: linear-gradient(110deg, rgba(11,29,77,.94), rgba(11,29,77,.64)); }
.citation-section .container { position: relative; z-index: 1; }
.citation-carte { max-width: 850px; margin-inline: auto; padding: clamp(1.5rem, 4vw, 3rem); text-align: center; color: #fff; border: 1px solid rgba(255,255,255,.22); border-radius: 20px; background: rgba(255,255,255,.08); backdrop-filter: blur(4px); }
.citation-portrait { width: 88px; height: 88px; object-fit: cover; border-radius: 50%; border: 3px solid rgba(255,255,255,.8); }
.citation-guillemet { display: block; margin-top: .5rem; color: var(--mrri-or); font-size: 3.5rem; line-height: .9; font-family: Georgia, serif; }
.citation-texte { margin: .5rem auto 1.25rem; max-width: 26ch; color: #fff; font-family: var(--font-title); font-size: clamp(1.5rem, 3.4vw, 2.4rem); line-height: 1.25; }
.citation-trait { width: 54px; height: 3px; margin: 0 auto 1.1rem; background: var(--mrri-or); }
.citation-auteur { max-width: 65ch; margin: 0 auto .5rem; color: rgba(255,255,255,.9); font-size: .9rem; font-weight: 700; }
.citation-date { margin: 0; color: rgba(255,255,255,.7); font-size: .8rem; }
.quick-access { padding: 4rem 0; background: #f5f8fc; }
.quick-access .row { align-items: stretch; }
.quick-access [class*="col-"] { display: flex; }
.quick-card { width: 100%; height: 100%; padding: 1.6rem; border: 1px solid #e6ebf2; border-radius: 16px; box-shadow: 0 5px 22px rgba(11,29,77,.045); }
.quick-card:hover { border-color: rgba(27,63,174,.2); }
.quick-top { align-items: center; }
.quick-icon { width: 52px; height: 52px; border-radius: 14px; }
.quick-card h3 { color: var(--mrri-bleu-nuit); }
.quick-card p { flex: 1; }
.structures-section { padding: clamp(3rem, 6vw, 5rem) 0; background: #EEF3F7; }
.section-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; margin-bottom: 1.75rem; }
.section-heading h2 { margin-bottom: .6rem; color: var(--mrri-bleu-nuit); font-family: var(--font-title); font-size: clamp(1.6rem, 3vw, 2.15rem); font-weight: 700; }
.section-heading p { margin: .8rem 0 0; color: var(--mrri-text-light); }
.heading-line { width: 58px; height: 3px; border-radius: 3px; background: var(--mrri-vert); }
.section-heading .btn { flex-shrink: 0; border-radius: 8px; padding: .7rem 1rem; font-weight: 700; }
.category-filters { gap: .55rem; margin-bottom: 1.75rem; }
.category-filter { padding: .6rem .95rem; color: #4f5c70; transition: all .2s ease; text-decoration: none; }
.category-filter.active { box-shadow: 0 4px 12px rgba(15,138,75,.16); }
.structure-card { padding: 1.35rem; border-radius: 15px; box-shadow: 0 4px 16px rgba(11,29,77,.035); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.structure-card:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(11,29,77,.09); }
.structure-card-top { align-items: center; }
.structure-icon { width: 50px; height: 50px; border-radius: 50%; }
.structure-category { max-width: 65%; text-align: right; line-height: 1.35; }
.structure-card h3 { margin-top: .2rem; color: var(--mrri-bleu-nuit); font-family: var(--font-title); font-size: 1.08rem; }
.structure-sigle { letter-spacing: .04em; }
.structure-address { display: flex; align-items: flex-start; gap: .45rem; line-height: 1.5; }
.structure-address svg { flex: 0 0 16px; margin-top: 2px; }
.structure-link { margin-top: auto; text-decoration: none; }
.news-section { padding: clamp(3rem, 6vw, 5rem) 0; background: #f5f8fc; }
.news-card { border-radius: 15px; border-color: #e5eaf1; box-shadow: 0 4px 16px rgba(11,29,77,.04); transition: transform .2s ease, box-shadow .2s ease; }
.news-card:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(11,29,77,.1); }
.news-image { height: 195px; }
.news-image img { transition: transform .35s ease; }
.news-card:hover .news-image img { transform: scale(1.035); }
.news-badge { max-width: calc(100% - 28px); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.news-content { padding: 1.2rem; }
.news-date { margin-bottom: .55rem; }
.news-content h3 { color: var(--mrri-bleu-nuit); font-size: 1.03rem; }
.pagination { gap: .35rem; }
.pagination .page-link { border-radius: 8px; border-color: #e1e7ef; color: var(--mrri-bleu); }
.pagination .page-item.active .page-link { background: var(--mrri-bleu); border-color: var(--mrri-bleu); }
.alert-light { border-radius: 12px; padding: 1.25rem; }
@media (max-width: 900px) {
  .mrri-hero-grid { grid-template-columns: 1fr; }
  .mrri-hero-image-col { display: none; }
  .search-section { margin-top: -24px; }
  .search-box { align-items: stretch; }
  .search-title { width: 100%; }
  .search-form { min-width: 0; }
}
@media (max-width: 575.98px) {
  .search-section { margin-bottom: 2.5rem; }
  .search-form { flex-direction: column; width: 100%; }
  .search-button, .search-filtres-btn { justify-content: center; width: 100%; }
  .section-heading { align-items: flex-start; flex-direction: column; }
  .citation-section { padding: 3rem 0; }
  .quick-access { padding: 2.75rem 0; }
  .structure-card { padding: 1.1rem; }
  .news-image { height: 185px; }
}


/* Accueil allégée : moins de texte, plus d'invitation à cliquer */
.quick-access { padding: 2.25rem 0 3rem; background: #f7f9fc; }
.quick-access .row { max-width: 1040px; margin-inline: auto; }
.quick-card { min-height: 132px; padding: 1.25rem 1.35rem; display: flex; flex-direction: column; justify-content: center; gap: .8rem; border: 1px solid #e5eaf1; border-radius: 15px; box-shadow: 0 5px 18px rgba(11,29,77,.045); }
.quick-top { margin: 0; gap: .85rem; }
.quick-icon { width: 46px; height: 46px; border-radius: 13px; }
.quick-card h3 { font-size: 1rem; }
.quick-card p { display: none; }
.quick-link { align-self: flex-start; font-size: .84rem; margin-left: 3.8rem; margin-top: -.35rem; text-decoration: none; }
.structures-section { padding: 3.25rem 0; }
.structures-section .section-heading { margin-bottom: 1.25rem; }
.structures-section .section-heading p, .news-section .section-heading p { max-width: 38ch; font-size: .9rem; }
.structure-card { padding: 1.15rem; }
.structure-card-top { margin-bottom: .85rem; }
.structure-icon { width: 44px; height: 44px; }
.structure-card h3 { min-height: 0; margin-bottom: .35rem; font-size: 1rem; }
.structure-sigle { margin-bottom: .6rem; }
.structure-address { font-size: .78rem; margin-bottom: .75rem; }
.structure-link { padding-top: .7rem; }
.news-section { padding: 3.25rem 0; }
.news-section .row { --bs-gutter-y: 1.15rem; }
.news-image { height: 165px; }
.news-content { padding: 1rem; }
.news-content h3 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: .98rem; }
.news-badge { font-size: .65rem; }
@media (max-width: 767px) {
  .quick-access { padding: 1.5rem 0 2rem; }
  .quick-card { min-height: 0; padding: 1rem; }
  .quick-link { margin-left: 0; }
  .structures-section, .news-section { padding: 2.5rem 0; }
  .section-heading { margin-bottom: 1.25rem; }
}

</style>
<div class="mrri-top-band"></div>
<?php require '../../includes/hero_actualites.php'; ?>
<section class="search-section">
  <div class="container">
    <div class="search-box">
      <div class="search-title">Rechercher une Information</div>
      <form action="catalogue.php" method="get" class="search-form">
        <div class="search-input-wrapper">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
          <input id="search" type="text" name="q" class="search-input" placeholder="Nom de la structure, sigle, mot-clé..." autocomplete="off">
        </div>
        <button type="submit" class="search-button">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px; margin-right:6px;"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
          Rechercher
        </button>
      </form>
      <a href="catalogue.php" class="search-filtres-btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
        Filtres avancés
      </a>
    </div>
    <div id="results" class="mt-3"></div>
  </div>
</section>
<section class="citation-section">
  <div class="citation-fond" style="background-image: url('../../public/image/GA.jpeg');"></div>
  <div class="citation-voile"></div>
  <div class="container">
    <div class="citation-carte">
      <img src="../../publique/image/GA2.png" alt="[Nom de la personne citée]" class="citation-portrait">
      <span class="citation-guillemet">"</span>
      <p class="citation-texte">Les réformes sont un pro­ces­sus continu, pas une fin en soi</p>
      <div class="citation-trait"></div>
      <p class="citation-auteur">FRANÇOIS NDONG OBIANG, Ministre de la Réforme et des Relations avec les Institutions</p>
      <p class="citation-date">09 mai 2025</p>
    </div>
  </div>
</section>
<!-- =========================================================
     ACCÈS RAPIDES
     ========================================================= -->
<section class="quick-access">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <!-- CARTE 1 -->
            <div class="col-md-6 col-lg-4">
                <div class="quick-card">
                    <div class="quick-top">
                        <div class="quick-icon green">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="21" x2="21" y2="21"/>
                    <line x1="5" y1="21" x2="5" y2="10"/>
                    <line x1="19" y1="21" x2="19" y2="10"/>
                    <line x1="9" y1="21" x2="9" y2="10"/>
                    <line x1="15" y1="21" x2="15" y2="10"/>
                    <polygon points="12 3 21 9 3 9"/>
                    </svg>
                        </div>
                        <h3>
                            I.C &  A.A.I
                        </h3>
                    </div>
                    <a href="catalogue.php"
                       class="quick-link green-link">
                        Explorer les institutions
                        <span>→</span>
                    </a>
                </div>
            </div>
            <!-- CARTE 3 -->
            <div class="col-md-6 col-lg-4">

                <div class="quick-card">
                    <div class="quick-top">
                        <div class="quick-icon gold">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h13a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4z"/>
                        <line x1="8" y1="8" x2="15" y2="8"/>
                        <line x1="8" y1="12" x2="15" y2="12"/>
                        <line x1="8" y1="16" x2="12" y2="16"/>
                    </svg>
                        </div>
                        <h3>
                            Actualités
                        </h3>
                    </div>
                    <a href="actualite.php"
                       class="quick-link gold-link">
                        Découvrir les actualités
                        <span>→</span>
                    </a>
                </div>
            </div>
            <!-- CARTE 4 -->
            <div class="col-md-6 col-lg-4">
                <div class="quick-card">
                    <div class="quick-top">
                        <div class="quick-icon dark">
                             <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/>
                            <line x1="12" y1="11" x2="12" y2="16.5"/>
                            <circle cx="12" cy="7.5" r="0.9" fill="#fff" stroke="none"/>
                        </svg>
                                                </div>
                        <h3>
                            À propos du MRRI
                        </h3>
                    </div>
                    <a href="https://relations-institutions.gouv.ga/"
                       class="quick-link dark-link">
                        En savoir plus
                        <span>→</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- =========================================================
     STRUCTURES
     ========================================================= -->
<section class="structures-section">
    <div class="container">
        <div class="section-heading">
            <div>
                <h2>
                 Institutions constitutionnelles et AAI
                </h2>
                <div class="heading-line"></div>
                <p>
                    Explorez les structures enregistrées
                    dans la plateforme.
                </p>
            </div>
            <a
                href="catalogue.php"
                class="btn btn-outline-primary">
                Voir le catalogue
            </a>
        </div>

        <!-- Aperçu volontairement épuré : les filtres complets sont disponibles dans le catalogue. -->

        <?php if (empty($structuresListe)): ?>

            <div class="alert alert-light border">

                Aucune structure trouvée.

            </div>

        <?php else: ?>


            <div class="row g-4">


                <?php foreach ($structuresListe as $s): ?>

                    <div class="col-md-6 col-lg-4">

                        <div class="structure-card">


                            <div class="structure-card-top">

                                <div class="structure-icon">
 <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#00865a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="21" x2="21" y2="21"/>
                    <line x1="5" y1="21" x2="5" y2="10"/>
                    <line x1="19" y1="21" x2="19" y2="10"/>
                    <line x1="9" y1="21" x2="9" y2="10"/>
                    <line x1="15" y1="21" x2="15" y2="10"/>
                    <polygon points="12 3 21 9 3 9"/>
                    </svg>

                                </div>



                            </div>



                            <h3>

                                <?= htmlspecialchars($s['nom_struc']) ?>

                            </h3>



                            <?php if (!empty($s['sigle'])): ?>

                                <div class="structure-sigle">

                                    <?= htmlspecialchars($s['sigle']) ?>

                                </div>

                            <?php endif; ?>



                            <p class="structure-address">
                            <svg xmlns="http://www.w3.org/2000/svg"
                            width="16" height="16" fill="currentColor"
                            viewBox="0 0 16 16" class="bi bi-geo-alt">
                            <path d="M12.166 8.94 8 15.5l-4.166-6.56A4.5 4.5 0 1 1 12.166 8.94z"/>
                            <path d="M8 9.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                           </svg>
                                <?= htmlspecialchars(
                                    $s['adresse'] ?? 'Adresse non renseignée'
                                ) ?>
                            </p>
                            <a
                                href="fiche.php?id=<?= $s['id_struc'] ?>"
                                class="structure-link"
                            >
                                <span>
                                    voir +
                                </span>
                                <span>
                                    →
                                </span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>


        <?php endif; ?>

    </div>

</section>



<!-- =========================================================
     ACTUALITÉS
     ========================================================= -->

<section class="news-section">

    <div class="container">


        <div class="section-heading">

            <div>

                <h2>
                    Actualités récentes
                </h2>

                <div class="heading-line"></div>

                <p>
                    Les dernières informations publiées
                    sur la plateforme.
                </p>

            </div>


            <a
                href="actualite.php"
                class="btn btn-outline-primary"
            >
                Toutes les actualités →
            </a>

        </div>



        <?php if (!empty($actualites)): ?>

            <div class="row g-4">


                <?php foreach ($actualites as $actualite): ?>

                    <?php

                    $photosActuStmt = $db->prepare("
                        SELECT *
                        FROM actualites_photos
                        WHERE id_actualites = :id
                        ORDER BY Position
                    ");

                    $photosActuStmt->execute([
                        ':id' => $actualite['id_actu']
                    ]);

                    $premierePhoto =
                        $photosActuStmt->fetch(PDO::FETCH_ASSOC);?>
                    <div class="col-md-6 col-lg-4">
                        <a href="actualite_detail.php?id=<?= $actualite['id_actu'] ?>" class="text-decoration-none">
                            <article class="news-card">
                                <div class="news-image">
                                    <?php if ($premierePhoto): ?>
                                        <img src="../../uploads/photos/<?= htmlspecialchars($premierePhoto['Photos_actu_url']) ?>" alt="<?= htmlspecialchars($actualite['titre']) ?>">
                                    <?php else: ?>
                                        <div class="news-image-placeholder">
                                            <span style="font-size: 2rem;">
                                                📰
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <span class="news-badge">
                                        <?= htmlspecialchars(  $actualite['nom_struc'] ) ?>
                                    </span>
                                </div>
                                <div class="news-content">
                                    <div class="news-date">
                                        <?= htmlspecialchars($actualite['date_publication']) ?>
                                    </div>
                                    <h3>
                                        <?= htmlspecialchars( $actualite['titre']) ?>
                                    </h3>
                                </div>
                            </article>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-light border">
                Aucune actualité publiée pour le moment.
            </div>
        <?php endif; ?>
    </div>
</section>
<!-- =========================================================
     BLOC INSTITUTIONNEL
     ========================================================= -->

<section
    class="institution-section"
    id="institution"
>

        </div>
    </div>
</section>



<?php require '../../includes/footer.php'; ?>


<!-- =========================================================
     RECHERCHE DYNAMIQUE
     ========================================================= -->

<script>

const searchInput =
    document.getElementById('search');

const results =
    document.getElementById('results');


if (searchInput) {

    searchInput.addEventListener(
        'keyup',
        function () {

            let q = this.value.trim();


            if (q.length < 1) {

                results.innerHTML = "";

                return;

            }


            fetch(
                'search_structures.php?q='
                + encodeURIComponent(q)
            )

            .then(response => response.text())

            .then(data => {

                results.innerHTML = data;

            })

            .catch(error => {

                console.error(
                    'Erreur de recherche :',
                    error
                );

            });

        }
    );

}

</script>