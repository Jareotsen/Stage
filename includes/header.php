<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Structures sous tutelle — Ministère des Réformes et des Relations avec les Institutions</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

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


/* Responsive */

@media (max-width: 575px) {

    .quick-card {
        min-height: 115px;
    }

}</style>
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
<div class="mrri-top-band"></div>
<header class="mrri-header">
  <div class="container mrri-header-inner">
    <a href="../public/accueil.php" class="mrri-brand">
      <img src="../../publique/image/sceau.png" alt="Armoiries de la République Gabonaise">
      <div>
        <p class="mrri-brand-titre">Ministère de la Réforme et des<br>Relations avec les Institutions</p>
        <div class="mrri-brand-sous"><span class="liseret"></span> République Gabonaise</div>
      </div>
    </a>

    <button type="button" class="mrri-menu-toggle" id="mrriMenuToggle" aria-label="Menu" aria-expanded="false">
  <span></span><span></span><span></span>
</button>

    <nav class="mrri-nav">
      <a href="../public/accueil.php" aria-current="page">Accueil</a>
      <a href="../public/catalogue.php">Structures</a>
      <a href="../public/actualite.php">Actualités</a>
      <?php if ($estAgentMinistere): ?>
        <a href="../agent_ministere/demandes.php">Demandes</a>
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