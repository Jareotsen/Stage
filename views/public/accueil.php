<?php
session_start();
require '../../config/database.php';
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
    LIMIT 8
");

$actualites = $actu->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   CATÉGORIES
   ========================================================= */

$categoriesStmt = $db->query("
    SELECT id_cat, nom_cat
    FROM categories
    ORDER BY nom_cat
");

$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   STRUCTURES
   ========================================================= */

$parPage = 9;

$page = isset($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;

$catFiltre = isset($_GET['cat'])
    ? (int) $_GET['cat']
    : null;

$conditions = [];
$parametres = [];

if ($catFiltre) {
    $conditions[] = "structure.id_cat = :cat";
    $parametres[':cat'] = $catFiltre;
}

$whereSQL = $conditions
    ? " WHERE " . implode(" AND ", $conditions)
    : "";


/* Nombre total */

$compteStmt = $db->prepare("
    SELECT COUNT(*)
    FROM structure
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
.quick-section { padding-bottom: 4.5rem; }
.section-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 1.75rem; }
.section-heading h2 { margin: 0; font-size: 1.7rem; }
.section-heading p { margin: 6px 0 0; color: var(--mrri-text-light); font-size: .92rem; }
.heading-line { width: 40px; height: 3px; background: var(--mrri-or); margin-top: 10px; }

.quick-card { height: 100%; background: #fff; border: 1px solid var(--mrri-border); border-radius: var(--radius-md); padding: 1.6rem; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.quick-card:hover { transform: translateY(-4px); border-color: rgba(15,138,75,.3); box-shadow: var(--shadow-md); }
.quick-icon { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin-bottom: 1.2rem; font-size: 1.15rem; }
.quick-icon.green { background: #E5F5EC; color: var(--mrri-vert); }
.quick-icon.blue { background: #E8EDFB; color: var(--mrri-bleu); }
.quick-icon.gold { background: var(--mrri-or-light); color: #936D0F; }
.quick-icon.dark { background: #E9EDF4; color: var(--mrri-bleu-nuit); }
.quick-card h3 { font-size: 1.05rem; margin-bottom: 8px; }
.quick-card p { color: var(--mrri-text-light); font-size: .85rem; margin-bottom: 1.1rem; }
.quick-link { color: var(--mrri-vert); font-size: .85rem; font-weight: 700; }
.quick-link:hover { color: var(--mrri-vert-dark); }

/* ---------- Catalogue (section aperçu) ---------- */
.structures-section { padding: 4.5rem 0; background: #fff; border-top: 1px solid var(--mrri-border); border-bottom: 1px solid var(--mrri-border); }
.category-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 2rem; }
.category-filter { display: inline-flex; align-items: center; padding: 8px 15px; border: 1px solid var(--mrri-border); border-radius: 50px; background: #fff; color: var(--mrri-text-light); font-size: .8rem; font-weight: 600; }
.category-filter:hover { border-color: var(--mrri-vert); color: var(--mrri-vert); }
.category-filter.active { background: var(--mrri-vert); border-color: var(--mrri-vert); color: #fff; }

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
</style>
<div class="mrri-top-band"></div>
<section class="mrri-hero">
  <div class="mrri-hero-grid">
    <div class="mrri-hero-texte">
      <div class="mrri-hero-accent"></div>
      <h1>Plateforme de consultation des structures sous tutelle du MRRI</h1>
      <p>Accédez facilement aux informations sur les structures sous tutelle du Ministère des Réformes et des Relations avec les Institutions.</p>
      <div class="mrri-hero-points">
        <div class="mrri-hero-point">
          <div class="mrri-hero-point-icone vert">🏛️</div>
          <span>Structures<br>sous tutelle</span>
        </div>
        <div class="mrri-hero-point">
          <div class="mrri-hero-point-icone bleu">📄</div>
          <span>Informations<br>fiables</span>
        </div>
        <div class="mrri-hero-point">
          <div class="mrri-hero-point-icone or">👥</div>
          <span>Au service<br>des citoyens</span>
        </div>
      </div>
    </div>
    <div class="mrri-hero-image-col">
      <img src="../../public/image/GA.jpeg" alt="Bâtiment institutionnel du Gabon">
      <div class="mrri-hero-tagline">Un État plus proche de ses citoyens</div>
    </div>
  </div>
</section>

<section class="search-section">
  <div class="container">
    <div class="search-box">
      <div class="search-title">Rechercher une structure</div>
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



<!-- =========================================================
     ACCÈS RAPIDES
     ========================================================= -->

<section class="quick-access">

    <div class="container">

        <div class="row g-4">

            <!-- CARTE 1 -->
            <div class="col-md-6 col-lg-3">

                <div class="quick-card">

                    <div class="quick-top">

                        <div class="quick-icon green">

                            <!-- Icône -->

                        </div>

                        <h3>
                            Consulter les structures
                        </h3>

                    </div>

                    <p>
                        Découvrez la liste des structures
                        sous tutelle du ministère.
                    </p>

                    <a href="catalogue.php"
                       class="quick-link green-link">

                        Accéder au catalogue

                        <span>→</span>

                    </a>

                </div>

            </div>


            <!-- CARTE 2 -->
            <div class="col-md-6 col-lg-3">

                <div class="quick-card">

                    <div class="quick-top">

                        <div class="quick-icon blue">

                            <!-- Icône -->

                        </div>

                        <h3>
                            Informations détaillées
                        </h3>

                    </div>

                    <p>
                        Accédez aux missions, services,
                        coordonnées et informations de chaque structure.
                    </p>

                    <a href="catalogue.php"
                       class="quick-link blue-link">

                        En savoir plus

                        <span>→</span>

                    </a>

                </div>

            </div>


            <!-- CARTE 3 -->
            <div class="col-md-6 col-lg-3">

                <div class="quick-card">

                    <div class="quick-top">

                        <div class="quick-icon gold">

                            <!-- Icône -->

                        </div>

                        <h3>
                            Actualités
                        </h3>

                    </div>

                    <p>
                        Restez informé des dernières actualités
                        du ministère et de ses structures.
                    </p>

                    <a href="actualite.php"
                       class="quick-link gold-link">

                        Voir toutes les actualités

                        <span>→</span>

                    </a>

                </div>

            </div>


            <!-- CARTE 4 -->
            <div class="col-md-6 col-lg-3">

                <div class="quick-card">

                    <div class="quick-top">

                        <div class="quick-icon dark">

                            <!-- Icône -->

                        </div>

                        <h3>
                            À propos du MRRI
                        </h3>

                    </div>

                    <p>
                        Le ministère au service de la réforme
                        des institutions et du développement du Gabon.
                    </p>

                    <a href="#institution"
                       class="quick-link dark-link">

                        Découvrir le ministère

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
                    Structures sous tutelle
                </h2>

                <div class="heading-line"></div>

                <p>
                    Explorez les structures enregistrées
                    dans la plateforme.
                </p>

            </div>

            <a
                href="catalogue.php"
                class="btn btn-outline-primary"
            >
                Voir le catalogue
            </a>

        </div>



        <!-- FILTRES -->

        <div class="category-filters">

            <a
                href="accueil.php"
                class="category-filter <?= $catFiltre === null ? 'active' : '' ?>"
            >
                Toutes les structures
            </a>


            <?php foreach ($categories as $cat): ?>

                <a
                    href="accueil.php?cat=<?= $cat['id_cat'] ?>"
                    class="category-filter <?= $catFiltre === (int) $cat['id_cat'] ? 'active' : '' ?>"
                >

                    <?= htmlspecialchars($cat['nom_cat']) ?>

                </a>

            <?php endforeach; ?>

        </div>



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

                                    🏛️

                                </div>


                                <span class="structure-category">

                                    <?= htmlspecialchars($s['nom_cat']) ?>

                                </span>

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

                                📍

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



            <!-- PAGINATION -->

            <?php if ($totalPages > 1): ?>

                <nav
                    class="mt-5"
                    aria-label="Pagination des structures"
                >

                    <ul class="pagination justify-content-center">


                        <li
                            class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"
                        >

                            <a
                                class="page-link"
                                href="?page=<?= $page - 1 ?><?= $catFiltre ? '&cat=' . $catFiltre : '' ?>"
                            >
                                Précédent
                            </a>

                        </li>



                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                            <li
                                class="page-item <?= $i === $page ? 'active' : '' ?>"
                            >

                                <a
                                    class="page-link"
                                    href="?page=<?= $i ?><?= $catFiltre ? '&cat=' . $catFiltre : '' ?>"
                                >

                                    <?= $i ?>

                                </a>

                            </li>

                        <?php endfor; ?>



                        <li
                            class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"
                        >

                            <a
                                class="page-link"
                                href="?page=<?= $page + 1 ?><?= $catFiltre ? '&cat=' . $catFiltre : '' ?>"
                            >

                                Suivant

                            </a>

                        </li>


                    </ul>

                </nav>

            <?php endif; ?>


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

                                        <?= htmlspecialchars(
                                            $actualite['nom_struc']
                                        ) ?>
                                    </span>
                                </div>
                                <div class="news-content">

                                    <div class="news-date">

                                        <?= htmlspecialchars(
                                            $actualite['date_publication']
                                        ) ?>

                                    </div>


                                    <h3>

                                        <?= htmlspecialchars(
                                            $actualite['titre']
                                        ) ?>

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