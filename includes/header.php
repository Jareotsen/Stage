<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Structures sous tutelle — Ministère des Réformes et des Relations avec les Institutions</title>
<link href="../../publique/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../../publique/vendor/fonts/fonts.css" rel="stylesheet">
<!-- Ordre important : le thème partagé d'abord, puis les styles propres au site public -->
<link href="../../publique/css/theme.css" rel="stylesheet">
<link href="../../publique/css/public.css" rel="stylesheet">
<?php if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'localisation.php'): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<?php endif; ?>
</head>
<body>
<?php
// ---------- Lien du tableau de bord selon le rôle ----------
$lienDashboard = '#';
$estAgentMinistere = isset($_SESSION['role']) && $_SESSION['role'] === 'agent_ministere';
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'super_admin': $lienDashboard = '../super_admin/dashboard.php'; break;
        case 'point_focal': $lienDashboard = '../point_focal/dashboard.php'; break;
        case 'agent_ministere': $lienDashboard = '../agent_ministere/dashboard.php'; break;
    }
}

// ---------- Page courante : sert à marquer le lien actif du menu ----------
$pageCourante = basename($_SERVER['SCRIPT_NAME'] ?? '');
$estPage = static function (array $fichiers) use ($pageCourante): bool {
    return in_array($pageCourante, $fichiers, true);
};
$attrActif = static function (array $fichiers) use ($estPage): string {
    return $estPage($fichiers) ? ' aria-current="page"' : '';
};

// ---------- Catégories du menu « Structures » ----------
// Disponible seulement si la page a déjà ouvert la connexion ($db) ; sinon le menu reste un simple lien.
$racinesMenu = [];
$enfantsMenu = [];
if (isset($db) && $db instanceof PDO) {
    try {
        $categoriesMenu = $db->query("SELECT id_cat, nom_cat, parent_id FROM categories ORDER BY nom_cat")
                             ->fetchAll(PDO::FETCH_ASSOC);
        foreach ($categoriesMenu as $categorie) {
            if (empty($categorie['parent_id'])) {
                $racinesMenu[] = $categorie;
            } else {
                $enfantsMenu[(int) $categorie['parent_id']][] = $categorie;
            }
        }
    } catch (PDOException $e) {
        error_log('Menu catégories : ' . $e->getMessage());
    }
}
$menuStructuresDisponible = !empty($racinesMenu);
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
      <a href="../public/accueil.php"<?= $attrActif(['accueil.php']) ?>>Accueil</a>

      <?php if ($menuStructuresDisponible): ?>
        <div class="mrri-dropdown">
          <button type="button"
                  class="mrri-dropdown-toggle<?= $estPage(['catalogue.php', 'fiche.php']) ? ' est-actif' : '' ?>"
                  id="mrriStructuresBtn"
                  aria-haspopup="true"
                  aria-expanded="false"
                  aria-controls="mrriStructuresMenu">
            Structures
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
          </button>

          <ul class="mrri-dropdown-menu" id="mrriStructuresMenu" hidden>
            <li class="mrri-dropdown-tous">
              <a href="../public/catalogue.php">Toutes les structures</a>
            </li>

            <?php foreach ($racinesMenu as $racine): ?>
              <?php $enfants = $enfantsMenu[(int) $racine['id_cat']] ?? []; ?>
              <li class="<?= $enfants ? 'mrri-dropdown-parent' : '' ?>">
                <a href="../public/catalogue.php?cat=<?= (int) $racine['id_cat'] ?>">
                  <?= htmlspecialchars($racine['nom_cat'], ENT_QUOTES, 'UTF-8') ?>
                </a>
              </li>
              <?php foreach ($enfants as $enfant): ?>
                <li class="mrri-dropdown-enfant">
                  <a href="../public/catalogue.php?cat=<?= (int) $enfant['id_cat'] ?>">
                    <?= htmlspecialchars($enfant['nom_cat'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php else: ?>
        <a href="../public/catalogue.php"<?= $attrActif(['catalogue.php', 'fiche.php']) ?>>Structures</a>
      <?php endif; ?>

      <a href="../public/localisation.php"<?= $attrActif(['localisation.php']) ?>>Localisation</a>
      <a href="../public/actualite.php"<?= $attrActif(['actualite.php', 'actualite_detail.php']) ?>>Actualités</a>
      <a href="../public/consultations.php"<?= $attrActif(['consultations.php', 'consultation_detail.php']) ?>>Consultations</a>

      <?php if ($estAgentMinistere): ?>
        <a href="../agent_ministere/gerer_consultation.php">Gérer les consultations</a>
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

    /* ---------- Menu mobile ---------- */
    const menuToggle = document.getElementById('mrriMenuToggle');
    const nav = document.querySelector('.mrri-nav');

    if (menuToggle && nav) {
        menuToggle.addEventListener('click', function () {
            nav.classList.toggle('ouvert');
            menuToggle.setAttribute('aria-expanded', nav.classList.contains('ouvert') ? 'true' : 'false');
        });

        // Fermer le menu lorsqu'on clique sur un lien
        nav.querySelectorAll('a').forEach(function (lien) {
            lien.addEventListener('click', function () {
                nav.classList.remove('ouvert');
                menuToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    /* ---------- Liste déroulante « Structures » ---------- */
    const boutonStructures = document.getElementById('mrriStructuresBtn');
    const menuStructures = document.getElementById('mrriStructuresMenu');

    if (boutonStructures && menuStructures) {
        function fermerStructures() {
            menuStructures.hidden = true;
            boutonStructures.setAttribute('aria-expanded', 'false');
        }

        boutonStructures.addEventListener('click', function (e) {
            e.stopPropagation();
            const souhaiteOuvrir = menuStructures.hidden;
            menuStructures.hidden = !souhaiteOuvrir;
            boutonStructures.setAttribute('aria-expanded', souhaiteOuvrir ? 'true' : 'false');
        });

        // Clic en dehors : fermer
        document.addEventListener('click', function (e) {
            if (!menuStructures.contains(e.target)) {
                fermerStructures();
            }
        });

        // Touche Échap : fermer et rendre le focus au bouton
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !menuStructures.hidden) {
                fermerStructures();
                boutonStructures.focus();
            }
        });
    }
});
</script>