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
  --bleu-roi: #1B3FAE;
  --bleu-roi-fonce: #142F86;
  --bleu-nuit: #0B1D4D;
  --vert-irl: #0F8A4B;
  --vert-irl-fonce: #0B6B3A;
  --or: #C9972C;
  --encre: #101826;
  --gris-texte: #5B6472;
  --fond: #F4F6FA;
  --bordure: #E1E5EE;

  --police-titres: "Fraunces", Georgia, serif;
  --police-texte: "Public Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
}

body { font-family: var(--police-texte); color: var(--encre); background: #F0F4F8; }
h1, h2, h3, h4, h5, h6 { font-family: var(--police-titres); font-weight: 600; color: var(--bleu-nuit); }
a { color: var(--bleu-roi); }
a:hover { color: var(--bleu-roi-fonce); }

.bandeau-drapeau { height: 15px; background: linear-gradient(90deg, var(--vert-irl) 0 33%,var(--or) 33% 66%, var(--bleu-roi) 66% 100%);  box-shadow: 0 2px 4px rgba(0,0,0,0.08);}
.bandeau-drapeau-bas {
  height: 4px;
  background: linear-gradient(
    90deg,
    var(--vert-irl) 0% 33%,
    var(--or) 33% 66%,
    var(--bleu-roi) 66% 100%
  );
}
/* ---------- Boutons ---------- */
.btn-mrri {
  background: var(--bleu-roi);
  color: #fff;
  border: 1px solid var(--bleu-roi);
  font-weight: 600;
}
.btn-mrri:hover { background: var(--bleu-roi-fonce); border-color: var(--bleu-roi-fonce); color: #fff; }

.btn-accent {
  background: var(--vert-irl);
  color: #fff;
  border: 1px solid var(--vert-irl);
  font-weight: 600;
}
.btn-accent:hover { background: var(--vert-irl-fonce); border-color: var(--vert-irl-fonce); color: #fff; }

.btn-outline-success { --bs-btn-color: var(--vert-irl); --bs-btn-border-color: var(--vert-irl); --bs-btn-hover-bg: var(--vert-irl); --bs-btn-hover-border-color: var(--vert-irl); }
.btn-outline-secondary { --bs-btn-color: var(--bleu-roi); --bs-btn-border-color: var(--bleu-roi); --bs-btn-hover-bg: var(--bleu-roi); --bs-btn-hover-border-color: var(--bleu-roi); }
.btn-outline-danger, .btn-outline-danger:hover { --bs-btn-hover-color: #fff; }

/* ---------- Navigation ---------- */
.navbar { background: #fff; border-bottom: 1px solid var(--bordure); box-shadow: 0 2px 4px rgba(0,0,0,0.05);}
.navbar-brand { font-family: var(--police-titres); font-weight: 700; color: var(--bleu-nuit) !important; font-size: 1.1rem; }
.nav-link { font-weight: 500; color: var(--encre) !important; border-bottom: 2px solid transparent; padding-bottom: 4px !important; }
.nav-link:hover, .nav-link.active { color: var(--bleu-roi) !important; border-bottom-color: var(--bleu-roi); }

/* ---------- Cartes de structures ---------- */
.card { border: 1px solid var(--bordure); border-radius: 4px; box-shadow: none; transition: border-color .15s ease, transform .15s ease; }
.card:hover { border-color: var(--bleu-roi); transform: translateY(-2px); }
.card-title { font-family: var(--police-titres); color: var(--bleu-nuit); }

.structure-carte-titre {
  min-height: 3rem;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  overflow: hidden;
  overflow-wrap: break-word;
  word-break: break-word;
}

.badge.bg-success-subtle { background: #E4F5EC !important; color: var(--vert-irl-fonce) !important; }
.badge.bg-secondary-subtle { background: #EEF1F8 !important; color: var(--bleu-roi) !important; }
.badge.bg-warning-subtle { background: #FBF1DF !important; color: #8A6511 !important; }

/* ---------- Sections d'accueil ---------- */
.bg-light { background: var(--fond) !important; }

.filtre-actif, .btn-sm.rounded-pill.btn-mrri { background: var(--bleu-roi); border-color: var(--bleu-roi); }
.btn-sm.rounded-pill.btn-outline-success { color: var(--bleu-roi); border-color: var(--bordure); }
.btn-sm.rounded-pill.btn-outline-success:hover { background: var(--fond); border-color: var(--bleu-roi); color: var(--bleu-roi); }

/* ---------- Carrousel actualités ---------- */
.actu-carousel .carousel-item { height: 480px; }
.actu-slide {
  position: relative; display: block; width: 100%; height: 100%;
  text-decoration: none; color: inherit;
  background-size: cover; background-position: center;
  background-color: var(--bleu-nuit);
}
.actu-slide:hover { color: inherit; text-decoration: none; }
.actu-slide-vide { display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.75); background: linear-gradient(135deg, var(--bleu-roi) 0%, var(--bleu-nuit) 100%); }
.actu-degrade { position: absolute; inset: 0; background: linear-gradient(0deg, rgba(11,29,77,0.92) 0%, rgba(11,29,77,0.4) 45%, rgba(11,29,77,0) 75%); }
.actu-legende { position: absolute; left: 0; right: 0; bottom: 3rem; padding: 0 2.5rem; max-width: 900px; }
.actu-badges { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.9rem; }
.actu-badge-structure { background: var(--vert-irl); color: #fff; font-weight: 700; font-size: 0.82rem; padding: 0.4rem 0.9rem; border-radius: 4px; }
.actu-badge-date { background: rgba(255,255,255,0.15); color: #fff; font-size: 0.82rem; font-weight: 600; padding: 0.4rem 0.9rem; border-radius: 4px; }
.actu-trait { width: 46px; height: 3px; background: var(--or); margin-bottom: 0.7rem; }
.actu-legende h3 { color: #fff; font-weight: 700; font-size: 1.9rem; line-height: 1.25; margin: 0; }

.actu-carousel .carousel-control-prev-icon, .actu-carousel .carousel-control-next-icon { width: 42px; height: 42px; background-color: rgba(255,255,255,0.15); border-radius: 50%; background-size: 50%; }
.actu-carousel .carousel-indicators [data-bs-target] { width: 12px; height: 12px; border-radius: 50%; border: none; background-color: rgba(255,255,255,0.35); opacity: 1; margin: 0 5px; }
.actu-carousel .carousel-indicators .active { background-color: var(--vert-irl); }

/* ---------- Formulaires ---------- */
.form-control:focus, .form-select:focus { border-color: var(--bleu-roi); box-shadow: 0 0 0 3px rgba(27,63,174,0.15); }
.form-check-input:checked { background-color: var(--vert-irl); border-color: var(--vert-irl); }

/* ---------- Alertes / statuts ---------- */
.alert-success { background: #E4F5EC; color: var(--vert-irl-fonce); border-color: #BFE4CF; }
.alert-danger { background: #FBEAEA; color: #A5372B; border-color: #F0C4C0; }
.liseré-drapeau{ height:4px; background:linear-gradient(90deg, var(--vert-mrri) 33%, var(--jaune-drapeau) 33% 66%, var(--bleu-drapeau) 66%); }
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
<div class="bandeau-drapeau"></div>
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="../public/accueil.php">
      <img src="../../public/image/sceau.png" alt="Armoiries de la République Gabonaise" width="42" height="42">
      <span>Ministère de la Réforme et des Relations avec les Institutions</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMrri">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMrri">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active" href="../public/accueil.php">Accueil</a></li>
        <li class="nav-item"><a class="nav-link" href="../public/catalogue.php">Structures</a></li>
        <li class="nav-item"><a class="nav-link" href="../public/actualite.php">Actulités</a></li>
        <?php if ($estAgentMinistere): ?>
          <li class="nav-item"><a class="nav-link" href="../agent_ministere/demandes.php">Demandes</a></li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="<?= $lienDashboard ?>" class="btn btn-mrri btn-sm">Tableau de bord</a>
          <a href="../../deconnexion.php" class="btn btn-outline-secondary btn-sm">Déconnexion</a>
        <?php else: ?>
          <a href="../public/connexion.php" class="btn btn-mrri btn-sm">Se connecter</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>