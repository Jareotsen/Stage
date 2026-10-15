<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Plateforme de consultation publique</title>
<link rel="icon" type="image/jpeg" href="/maquette/publique/image/logo1.jpeg">
<link href="../../publique/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../../publique/vendor/fonts/fonts.css" rel="stylesheet">
<style>
:root {
  --mrri-vert: #0F8A4B;
  --mrri-vert-dark: #0B6B3A;
  --mrri-bleu: #1B3FAE;
  --mrri-bleu-dark: #142F86;
  --mrri-bleu-nuit: #0B1D4D;
  --mrri-or: #C9972C;
  --mrri-or-light: #F7EACB;
  --mrri-text: #172033;
  --mrri-text-light: #667085;
  --mrri-bg: #F5F7FA;
  --mrri-border: #E4E7EC;
  --mrri-white: #FFFFFF;
  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 18px;
  --shadow-sm: 0 2px 8px rgba(11,29,77,.06);
  --shadow-md: 0 10px 30px rgba(11,29,77,.09);
  --font-title: "Fraunces", Georgia, serif;
  --font-body: "Public Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
}

body { margin: 0; background: var(--mrri-bg); color: var(--mrri-text); font-family: var(--font-body); line-height: 1.6; }
h1, h2, h3, h4, h5 { font-family: var(--font-title); color: var(--mrri-bleu-nuit); font-weight: 700; }
a { text-decoration: none; }

/* ---------- Bandeau national ---------- */
.mrri-top-band { height: 5px; background: linear-gradient(90deg, var(--mrri-vert) 0 33.33%, var(--mrri-or) 33.33% 66.66%, var(--mrri-bleu) 66.66% 100%); }

/* ---------- En-tête ---------- */
.mrri-header { background: #fff; border-bottom: 1px solid var(--mrri-border); }
.mrri-header-inner { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; padding: 0.9rem 0; }
.mrri-brand { display: flex; align-items: center; gap: 0.85rem; }
.mrri-brand img { width: 50px; height: 50px; object-fit: contain; }
.mrri-brand-titre { font-family: var(--font-title); font-weight: 700; font-size: 1.05rem; color: var(--mrri-bleu-nuit); line-height: 1.25; margin: 0; }
.mrri-brand-sous {
  display: flex; align-items: center; gap: 8px;
  font-size: 0.68rem; letter-spacing: 0.08em; color: var(--mrri-text-light);
  text-transform: uppercase; font-weight: 600; margin-top: 3px;
}
.mrri-brand-sous .liseret { width: 28px; height: 3px; background: linear-gradient(90deg, var(--mrri-vert) 0 33%, var(--mrri-or) 33% 66%, var(--mrri-bleu) 66% 100%); display: inline-block; }

.mrri-nav { display: flex; align-items: center; gap: 2rem; }
.mrri-nav a { color: var(--mrri-text); font-weight: 600; font-size: 0.92rem; padding-bottom: 4px; border-bottom: 2px solid transparent; }
.mrri-nav a:hover, .mrri-nav a[aria-current="page"] { color: var(--mrri-vert); border-bottom-color: var(--mrri-vert); }

.mrri-header-actions { display: flex; align-items: center; gap: 0.9rem; }
.mrri-search-btn { background: none; border: none; color: var(--mrri-bleu-nuit); display: flex; align-items: center; cursor: pointer; }

.mrri-pill-btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 0.6rem 1.15rem; border-radius: 999px;
  border: 1px solid var(--mrri-bleu); color: var(--mrri-bleu);
  font-weight: 700; font-size: 0.86rem; background: #fff;
}
.mrri-pill-btn:hover { background: var(--mrri-bleu); color: #fff; }
.mrri-pill-btn.plein { background: var(--mrri-bleu); color: #fff; }
.mrri-pill-btn.plein:hover { background: var(--mrri-bleu-dark); }

/* ---------- Boutons génériques réutilisés dans les autres pages ---------- */
.btn-mrri { background: var(--mrri-bleu); color: #fff; border: 1px solid var(--mrri-bleu); font-weight: 600; }
.btn-mrri:hover { background: var(--mrri-bleu-dark); border-color: var(--mrri-bleu-dark); color: #fff; }
.btn-outline-success { --bs-btn-color: var(--mrri-vert); --bs-btn-border-color: var(--mrri-vert); --bs-btn-hover-bg: var(--mrri-vert); --bs-btn-hover-border-color: var(--mrri-vert); }
.btn-outline-secondary { --bs-btn-color: var(--mrri-bleu); --bs-btn-border-color: var(--mrri-bleu); --bs-btn-hover-bg: var(--mrri-bleu); --bs-btn-hover-border-color: var(--mrri-bleu); }

.card { border: 1px solid var(--mrri-border); border-radius: var(--radius-md); box-shadow: none; transition: border-color .15s ease, transform .15s ease; }
.card:hover { border-color: var(--mrri-bleu); transform: translateY(-2px); }
.card-title { font-family: var(--font-title); color: var(--mrri-bleu-nuit); }

.structure-carte-titre {
  min-height: 3rem; display: -webkit-box; -webkit-box-orient: vertical;
  -webkit-line-clamp: 2; line-clamp: 2; overflow: hidden;
  overflow-wrap: break-word; word-break: break-word;
}

.badge.bg-success-subtle { background: #E5F5EC !important; color: var(--mrri-vert-dark) !important; }
.badge.bg-secondary-subtle { background: #E8EDFB !important; color: var(--mrri-bleu) !important; }
.badge.bg-warning-subtle { background: var(--mrri-or-light) !important; color: #8A6511 !important; }

.form-control:focus, .form-select:focus { border-color: var(--mrri-bleu); box-shadow: 0 0 0 3px rgba(27,63,174,.14); }
.form-check-input:checked { background-color: var(--mrri-vert); border-color: var(--mrri-vert); }
.alert-success { background: #E5F5EC; color: var(--mrri-vert-dark); border-color: #BFE4CF; }
.alert-danger { background: #FBEAEA; color: #A5372B; border-color: #F0C4C0; }

.pagination .page-link { border-color: var(--mrri-border); color: var(--mrri-bleu); font-weight: 600; }
.pagination .page-item.active .page-link { background: var(--mrri-vert); border-color: var(--mrri-vert); color: #fff; }

.mrri-menu-toggle { display: none; background: none; border: none; cursor: pointer; padding: 8px; }
.mrri-menu-toggle span { display: block; width: 22px; height: 2px; background: var(--mrri-bleu-nuit); margin: 4px 0; }
.citation-section { position: relative; padding: 3.5rem 0; overflow: hidden; }
.citation-fond { position: absolute; inset: 0; background-size: cover; background-position: center; }
.citation-voile { position: absolute; inset: 0; background: linear-gradient(90deg, rgba(11,29,77,0.75) 0%, rgba(11,29,77,0.55) 60%, rgba(11,29,77,0.35) 100%); }

.citation-carte {
  position: relative; z-index: 2;
  background: #fff; border-radius: 14px;
  box-shadow: var(--shadow-md);
  padding: 1.75rem 2rem 1.75rem 5.5rem;
  margin-left: 3.5rem;
  display: flex; flex-direction: column; gap: 0.6rem;
}

.citation-portrait {
  position: absolute; left: -3.5rem; top: 50%; transform: translateY(-50%);
  width: 96px; height: 96px; border-radius: 50%;
  border: 4px solid #fff; object-fit: cover;
  box-shadow: var(--shadow-sm);
  z-index: 3;
}

.citation-guillemet { font-family: var(--font-title); font-size: 2.2rem; color: var(--mrri-vert); line-height: 0.5; margin-bottom: 0.3rem; }
.citation-texte { font-size: 1.05rem; font-weight: 600; color: var(--mrri-bleu-nuit); line-height: 1.5; margin: 0; }
.citation-trait { width: 36px; height: 3px; background: var(--mrri-or); margin: 0.4rem 0; }
.citation-auteur { font-weight: 700; font-size: 0.9rem; color: var(--mrri-text); margin: 0; }
.citation-date { font-size: 0.8rem; color: var(--mrri-text-light); margin: 0; }

@media (max-width: 700px) {
  .citation-carte { margin-left: 1rem; margin-right: 1rem; padding-left: 4.5rem; }
  .citation-portrait { width: 72px; height: 72px; left: -2.2rem; }
}
@media (max-width: 900px) {
  .mrri-menu-toggle { display: block; }
  .mrri-nav {
    display: none;
    position: absolute;
    top: 100%; left: 0; right: 0;
    background: #fff;
    border-bottom: 1px solid var(--mrri-border);
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
    padding: 0.5rem 0;
    box-shadow: var(--shadow-sm);
    z-index: 1000;
  }
  .mrri-nav.ouvert { display: flex; }
  .mrri-nav a { width: 100%; padding: 0.75rem 1.5rem; border-bottom: none; }
  .mrri-nav a:hover, .mrri-nav a[aria-current="page"] { background: var(--mrri-bg); border-bottom: none; }
  .mrri-header { position: relative; }
}


/* =====================================================
   CARTES D'ACCÈS RAPIDE
   ===================================================== */
.quick-card {
    background: #fff;
    border-radius: 7px;
    padding: 14px 15px 13px;
    min-height: 102px;

    border: 1px solid rgba(30, 60, 100, 0.04);

    box-shadow: 0 2px 10px rgba(20, 45, 80, 0.06);

    display: flex;
    flex-direction: column;

    transition: all 0.2s ease;
}
.quick-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 18px rgba(20, 45, 80, 0.10);
}
/* Partie icône + titre */
.quick-top {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 8px;
}
/* Icônes circulaires */
.quick-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;
}
/* SVG */
.quick-icon svg {
    width: 22px;
    height: 22px;

    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;

    stroke-linecap: round;
    stroke-linejoin: round;
}
/* Couleurs */
.quick-icon.green {
    background: #00865a;
    color: white;
}
.quick-icon.blue {
    background: #1558bd;
    color: white;
}
.quick-icon.gold {
    background: #c99409;
    color: white;
}
.quick-icon.dark {
    background: #61738e;
    color: white;
}
/* Titres */
.quick-card h3 {
    margin: 0;

    color: #102f5c;

    font-size: 11px;
    font-weight: 700;

    line-height: 1.25;
}
/* Descriptions */
.quick-card p {
    margin: 0 0 8px 54px;

    color: #64748b;

    font-size: 8.5px;
    line-height: 1.45;

    flex: 1;
}
/* Liens */
.quick-link {
    margin-left: 54px;

    display: inline-flex;
    align-items: center;
    gap: 8px;

    width: fit-content;

    text-decoration: none;

    font-size: 8.5px;
    font-weight: 700;

    transition: gap 0.2s ease;
}
.quick-link span {
    font-size: 13px;
    line-height: 1;
}
.quick-link:hover {
    gap: 11px;
}
/* Couleur des liens */
.green-link {
    color: #00865a;
}
.blue-link {
    color: #1558bd;
}
.gold-link {
    color: #c99409;
}
.dark-link {
    color: #243c61;
}
/* Fond de la section */
.quick-access {
    background: #f2f6fb;
    padding: 28px 0;
}
.slider {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.slider img {
  position: absolute;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0;
  transition: opacity 1s ease-in-out;
}

.slider img.active {
  opacity: 1;
}
/* Responsive */
@media (max-width: 575px) {

    .quick-card {
        min-height: 115px;
    }

}
@view-transition {
  navigation: auto;
}

::view-transition-old(root) {
  animation: fondu-sortie .25s ease both;
}

::view-transition-new(root) {
  animation: fondu-entree .3s ease both;
}

@keyframes fondu-sortie {
  from { opacity: 1; transform: translateY(0); }
  to   { opacity: 0; transform: translateY(-8px); }
}

@keyframes fondu-entree {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
  ::view-transition-old(root),
  ::view-transition-new(root) {
    animation: none;
  }
}
/* =====================================================
   MENU DÉROULANT "STRUCTURES"
   ===================================================== */
.mrri-dropdown {
  position: relative;
}

.mrri-dropdown-toggle {
  display: inline-flex;
  align-items: center;
  gap: 7px;

  background: none;
  border: none;
  padding: 0 0 4px;
  cursor: pointer;

  font: inherit;
  font-weight: 600;
  font-size: 0.92rem;
  color: var(--mrri-text);

  border-bottom: 2px solid transparent;
  transition: color .2s ease, border-color .2s ease;
}

.mrri-dropdown-toggle:hover,
.mrri-dropdown.ouvert .mrri-dropdown-toggle {
  color: var(--mrri-vert);
  border-bottom-color: var(--mrri-vert);
}

.mrri-dropdown-toggle svg {
  transition: transform .25s ease;
}

.mrri-dropdown.ouvert .mrri-dropdown-toggle svg {
  transform: rotate(180deg);
}

/* Menu principal */
.mrri-dropdown-menu {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;

  min-width: 330px;
  max-height: 70vh;
  overflow-y: auto;

  background: #fff;
  border: 1px solid var(--mrri-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);

  padding: 0.45rem 0;

  z-index: 1100;

  /* État fermé : animable */
  opacity: 0;
  visibility: hidden;
  transform: translateY(-8px) scale(.98);
  transform-origin: top left;

  transition:
    opacity .22s ease,
    transform .22s ease,
    visibility .22s ease;
}

.mrri-dropdown.ouvert .mrri-dropdown-menu {
  opacity: 1;
  visibility: visible;
  transform: translateY(0) scale(1);
}

/* Lien "Toutes les structures" */
.mrri-dropdown-menu > a {
  display: flex;
  align-items: center;
  gap: 10px;

  position: relative;

  padding: 0.6rem 1.15rem;
  padding-left: calc(1.15rem + var(--niveau, 0) * 1.05rem);

  font-weight: 500;
  font-size: 0.88rem;
  color: var(--mrri-text);

  border-left: 3px solid transparent;

  transition:
    background-color .18s ease,
    color .18s ease,
    border-color .18s ease,
    padding-left .18s ease;
}

.mrri-dropdown-menu > a:hover {
  background: var(--mrri-bg);
  color: var(--mrri-vert);
  border-left-color: var(--mrri-vert);
  padding-left: calc(1.35rem + var(--niveau, 0) * 1.05rem);
}

/* Catégories parentes */
.mrri-dropdown-menu > a.est-parent {
  font-weight: 700;
  color: var(--mrri-bleu-nuit);
}

.mrri-dropdown-menu > a.est-parent::before {
  content: "";
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--mrri-or);
  flex-shrink: 0;
  opacity: .85;
}

.mrri-dropdown-menu > a:not(.est-parent)::before {
  content: "";
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--mrri-border);
  flex-shrink: 0;
  transition: background-color .18s ease;
}

.mrri-dropdown-menu > a:hover:not(.est-parent)::before {
  background: var(--mrri-vert);
}

/* Séparateur */
.mrri-dropdown-menu .separateur {
  height: 1px;
  margin: 0.35rem 0;
  background: linear-gradient(
    90deg,
    var(--mrri-vert) 0 33%,
    var(--mrri-or) 33% 66%,
    var(--mrri-bleu) 66% 100%
  );
  opacity: .35;
}

/* Apparition progressive des éléments */
.mrri-dropdown.ouvert .mrri-dropdown-menu > a {
  animation: mrri-apparition .25s ease both;
}

.mrri-dropdown.ouvert .mrri-dropdown-menu > a:nth-child(1) { animation-delay: .02s; }
.mrri-dropdown.ouvert .mrri-dropdown-menu > a:nth-child(2) { animation-delay: .04s; }
.mrri-dropdown.ouvert .mrri-dropdown-menu > a:nth-child(3) { animation-delay: .06s; }
.mrri-dropdown.ouvert .mrri-dropdown-menu > a:nth-child(4) { animation-delay: .08s; }
.mrri-dropdown.ouvert .mrri-dropdown-menu > a:nth-child(5) { animation-delay: .10s; }
.mrri-dropdown.ouvert .mrri-dropdown-menu > a:nth-child(6) { animation-delay: .12s; }
.mrri-dropdown.ouvert .mrri-dropdown-menu > a:nth-child(7) { animation-delay: .14s; }
.mrri-dropdown.ouvert .mrri-dropdown-menu > a:nth-child(8) { animation-delay: .16s; }

@keyframes mrri-apparition {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Accessibilité : réduit les mouvements si l'utilisateur le demande */
@media (prefers-reduced-motion: reduce) {
  .mrri-dropdown-menu,
  .mrri-dropdown-menu > a,
  .mrri-dropdown-toggle svg {
    transition: opacity .15s ease, visibility .15s ease;
    transform: none !important;
    animation: none !important;
  }
}

/* Mobile */
@media (max-width: 900px) {
  .mrri-dropdown {
    width: 100%;
  }

  .mrri-dropdown-toggle {
    width: 100%;
    justify-content: space-between;
    padding: 0.75rem 1.5rem;
    border-bottom: none;
  }

  .mrri-dropdown-menu {
    position: static;
    min-width: 0;
    max-height: none;
    overflow: visible;

    border: none;
    border-radius: 0;
    box-shadow: none;
    background: var(--mrri-bg);
    padding: 0.25rem 0;

    transform: none;
  }

  .mrri-dropdown-menu > a {
    padding-left: calc(2rem + var(--niveau, 0) * 1rem);
  }

  .mrri-dropdown-menu > a:hover {
    padding-left: calc(2.2rem + var(--niveau, 0) * 1rem);
  }
}
@media (max-width: 900px) {
  .mrri-dropdown { width: 100%; }
  .mrri-dropdown-toggle { width: 100%; justify-content: space-between; padding: 0.75rem 1.5rem; border-bottom: none; }
  .mrri-dropdown-menu { position: static; min-width: 0; max-height: none; border: none; border-radius: 0; box-shadow: none; background: var(--mrri-bg); padding: 0.25rem 0; }
  .mrri-nav .mrri-dropdown-menu a { padding-left: calc(2rem + var(--niveau, 0) * 1rem); }
}
</style>
</head>
<body>
<?php
$lienDashboard = '#';
$estAgentMinistere = isset($_SESSION['role']) && $_SESSION['role'] === 'agent_ministere';
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'super_admin': $lienDashboard = '../super_admin/dashboard.php'; break;
        case 'point_focal': $lienDashboard = '../point_focal/dashboard.php'; break;
        case 'agent_ministere': $lienDashboard = '../agent_ministere/dashboard.php'; break;
    }
}
?>
<?php
// Catégories pour le menu déroulant "Structures".
// $db est fourni par la page qui inclut ce header ; sans lui, on affiche un simple lien.
$menuCategories = [];
if (isset($db) && $db instanceof PDO) {
    try {
        $lignesCat = $db->query("SELECT id_cat, nom_cat, parent_id FROM categories ORDER BY nom_cat")
                        ->fetchAll(PDO::FETCH_ASSOC);

        // Regroupe les catégories par parent (0 = catégorie de premier niveau)
        $enfantsCat = [];
        foreach ($lignesCat as $c) {
            $enfantsCat[(int) ($c['parent_id'] ?? 0)][] = $c;
        }

        // Aplatit l'arbre en liste ordonnée, avec la profondeur pour l'indentation
        $aplatirCat = function (int $parent, int $profondeur) use (&$aplatirCat, &$menuCategories, $enfantsCat) {
            if ($profondeur > 3) {
                return;
            }
            foreach ($enfantsCat[$parent] ?? [] as $c) {
                $idCat = (int) $c['id_cat'];
                $menuCategories[] = [
                    'id'         => $idCat,
                    'nom'        => $c['nom_cat'],
                    'profondeur' => $profondeur,
                    'parent'     => !empty($enfantsCat[$idCat]),
                ];
                $aplatirCat($idCat, $profondeur + 1);
            }
        };
        $aplatirCat(0, 0);
    } catch (PDOException $e) {
        error_log("Menu catégories : " . $e->getMessage());
    }
}
?>
<div class="mrri-top-band"></div>
<header class="mrri-header">
  <div class="container mrri-header-inner">
    <a href="../public/accueil.php" class="mrri-brand">
      <img src="../../publique/image/sceau.png" alt="Armoiries de la République Gabonaise">
      <div>
        <p class="mrri-brand-titre">MINISTERE DE LA REFORME ET DES <br>RELATIONS AVEC LES INSTITUTIONS</p>
        
      </div>
    </a>

    <button type="button" class="mrri-menu-toggle" id="mrriMenuToggle" aria-label="Menu" aria-expanded="false">
  <span></span><span></span><span></span>
</button>

    <nav class="mrri-nav">
<?php
$pageActuelle = basename($_SERVER['PHP_SELF']);
$estAccueil = ($pageActuelle === 'accueil.php');
?>

<a href="../public/accueil.php"
   <?= $estAccueil ? 'aria-current="page"' : '' ?>>
   Accueil
</a>      <?php if (!empty($menuCategories)): ?>
      <div class="mrri-dropdown">
        <button type="button" class="mrri-dropdown-toggle" aria-expanded="false" aria-controls="mrriStructuresMenu">
          Structures
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="mrri-dropdown-menu" id="mrriStructuresMenu">
          <a href="../public/catalogue.php" class="est-parent">Toutes les structures</a>
          <div class="separateur"></div>
          <?php foreach ($menuCategories as $cat): ?>
            <a href="../public/catalogue.php?cat=<?= (int) $cat['id'] ?>"
               class="<?= $cat['parent'] ? 'est-parent' : '' ?>"
               style="--niveau: <?= (int) $cat['profondeur'] ?>;"><?= htmlspecialchars($cat['nom']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php else: ?>
        <a href="../public/catalogue.php">Structures</a>
      <?php endif; ?>
      <a href="../public/actualite.php">Actualités</a>
      <a href="../public/consultations.php">Consultations</a>
      <?php if ($estAgentMinistere): ?>
        <a href="../agent_ministere/demandes.php">Demandes</a>
        <a href="../agent_ministere/gerer_consultations.php">Gérer les consultations</a>
      <?php endif; ?>
    </nav>

    <div class="mrri-header-actions">
      <button type="button" class="mrri-search-btn" onclick="window.location.href='../public/catalogue.php'" aria-label="Rechercher">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
      </button>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?= $lienDashboard ?>" class="mrri-pill-btn plein">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Tableau de bord
        </a>
        <a href="../../deconnexion.php" class="mrri-pill-btn">Déconnexion</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const menuToggle = document.getElementById('mrriMenuToggle');
    const nav = document.querySelector('.mrri-nav');

    if (!menuToggle || !nav) {
        return;
    }

    menuToggle.addEventListener('click', function () {

        nav.classList.toggle('ouvert');

        const ouvert = nav.classList.contains('ouvert');

        menuToggle.setAttribute('aria-expanded', ouvert ? 'true' : 'false');

    });

    // Fermer le menu lorsqu'on clique sur un lien
    nav.querySelectorAll('a').forEach(function (lien) {

        lien.addEventListener('click', function () {
            nav.classList.remove('ouvert');
            menuToggle.setAttribute('aria-expanded', 'false');
        });

    });

});
</script>

<script>
// Menu déroulant "Structures" : ouverture au clic, fermeture au clic ailleurs ou avec Échap
document.addEventListener('DOMContentLoaded', function () {
    const menu = document.querySelector('.mrri-dropdown');
    if (!menu) {
        return;
    }
    const bouton = menu.querySelector('.mrri-dropdown-toggle');

    function fermer() {
        menu.classList.remove('ouvert');
        bouton.setAttribute('aria-expanded', 'false');
    }

    bouton.addEventListener('click', function (e) {
        e.stopPropagation();
        const ouvert = menu.classList.toggle('ouvert');
        bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
    });

    document.addEventListener('click', function (e) {
        if (!menu.contains(e.target)) {
            fermer();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            fermer();
            bouton.focus();
        }
    });
});
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.mrri-sous-toggle').forEach(function (bouton) {
        const parent = bouton.closest('.mrri-sous-dropdown');

        bouton.addEventListener('click', function (e) {
            e.stopPropagation();

            // Ferme les sous-menus frères au même niveau
            const freres = parent.parentElement.querySelectorAll(':scope > .mrri-sous-dropdown');
            freres.forEach(function (frere) {
                if (frere !== parent) {
                    frere.classList.remove('ouvert');
                    const b = frere.querySelector(':scope > .mrri-sous-toggle');
                    if (b) b.setAttribute('aria-expanded', 'false');
                }
            });

            const ouvert = parent.classList.toggle('ouvert');
            bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        });
    });

    // Ferme tous les sous-menus si clic en dehors du menu principal
    document.addEventListener('click', function (e) {
        const menuPrincipal = document.getElementById('mrriStructuresMenu');
        if (menuPrincipal && !menuPrincipal.contains(e.target)) {
            document.querySelectorAll('.mrri-sous-dropdown.ouvert').forEach(function (item) {
                item.classList.remove('ouvert');
                const b = item.querySelector(':scope > .mrri-sous-toggle');
                if (b) b.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // Ferme aussi avec Échap
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.mrri-sous-dropdown.ouvert').forEach(function (item) {
                item.classList.remove('ouvert');
                const b = item.querySelector(':scope > .mrri-sous-toggle');
                if (b) b.setAttribute('aria-expanded', 'false');
            });
        }
    });
});
</script>