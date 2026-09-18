<?php
session_start();
require '../../config/database.php';

/* =========================================================
   ACTUALITÉS
   ========================================================= */

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

/* =========================================================
   IDENTITÉ VISUELLE MRRI
   ========================================================= */

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

    --shadow-sm:
        0 2px 8px rgba(11, 29, 77, 0.06);

    --shadow-md:
        0 10px 30px rgba(11, 29, 77, 0.09);

    --font-title:
        "Fraunces",
        Georgia,
        serif;

    --font-body:
        "Public Sans",
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Arial,
        sans-serif;
}


/* =========================================================
   BASE
   ========================================================= */

body {

    margin: 0;

    background: var(--mrri-bg);

    color: var(--mrri-text);

    font-family: var(--font-body);

    line-height: 1.6;
}

h1,
h2,
h3,
h4,
h5 {

    font-family: var(--font-title);

    color: var(--mrri-bleu-nuit);

    font-weight: 700;
}

a {

    text-decoration: none;

}


/* =========================================================
   BANDEAU NATIONAL
   ========================================================= */

.mrri-top-band {

    height: 6px;

    background:
        linear-gradient(
            90deg,
            var(--mrri-vert) 0 33.33%,
            var(--mrri-or) 33.33% 66.66%,
            var(--mrri-bleu) 66.66% 100%
        );
}


/* =========================================================
   HERO
   ========================================================= */

.mrri-hero {

    position: relative;

    min-height: 510px;

    overflow: hidden;

    background: var(--mrri-bleu-nuit);
}


/* Image */

.mrri-hero-image {

    position: absolute;

    inset: 0;

    width: 100%;
    height: 100%;

    object-fit: cover;

    object-position: center;

}


/* Voile */

.mrri-hero-overlay {

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            rgba(11, 29, 77, 0.96) 0%,
            rgba(11, 29, 77, 0.84) 38%,
            rgba(11, 29, 77, 0.40) 70%,
            rgba(11, 29, 77, 0.10) 100%
        );
}


/* Contenu */

.mrri-hero-content {

    position: relative;

    z-index: 2;

    min-height: 510px;

    display: flex;

    align-items: center;
}

.mrri-hero-text {

    max-width: 700px;

    color: white;
}

.mrri-hero-label {

    display: inline-flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 20px;

    color: white;

    font-size: 0.82rem;

    font-weight: 700;

    letter-spacing: 0.08em;

    text-transform: uppercase;
}

.mrri-hero-label::before {

    content: "";

    display: block;

    width: 38px;

    height: 3px;

    background: var(--mrri-or);
}


.mrri-hero h1 {

    color: white;

    font-size: clamp(2.4rem, 5vw, 4.2rem);

    line-height: 1.08;

    margin-bottom: 22px;

    letter-spacing: -0.025em;
}

.mrri-hero-description {

    max-width: 620px;

    color: rgba(255,255,255,0.86);

    font-size: 1.08rem;

    margin-bottom: 30px;
}


/* Bouton */

.btn-mrri-primary {

    display: inline-flex;

    align-items: center;

    gap: 10px;

    padding: 13px 22px;

    border-radius: 7px;

    background: var(--mrri-vert);

    border: 1px solid var(--mrri-vert);

    color: white;

    font-weight: 700;

    transition: .2s ease;
}

.btn-mrri-primary:hover {

    background: var(--mrri-vert-dark);

    border-color: var(--mrri-vert-dark);

    color: white;

    transform: translateY(-1px);
}


/* =========================================================
   RECHERCHE
   ========================================================= */

.search-section {

    position: relative;

    z-index: 10;

    margin-top: -55px;

    margin-bottom: 70px;
}

.search-box {

    background: white;

    border: 1px solid var(--mrri-border);

    border-radius: var(--radius-md);

    box-shadow: var(--shadow-md);

    padding: 25px;
}

.search-title {

    margin-bottom: 15px;

    font-family: var(--font-title);

    font-size: 1.35rem;

    font-weight: 700;

    color: var(--mrri-bleu-nuit);
}

.search-form {

    display: flex;

    gap: 10px;
}

.search-input-wrapper {

    position: relative;

    flex: 1;
}

.search-input-wrapper svg {

    position: absolute;

    left: 17px;

    top: 50%;

    transform: translateY(-50%);

    color: var(--mrri-text-light);
}

.search-input {

    width: 100%;

    height: 52px;

    padding: 0 18px 0 48px;

    border: 1px solid var(--mrri-border);

    border-radius: 7px;

    font-size: 0.95rem;

    outline: none;

    transition: .2s ease;
}

.search-input:focus {

    border-color: var(--mrri-vert);

    box-shadow:
        0 0 0 3px rgba(15,138,75,.10);
}

.search-button {

    height: 52px;

    padding: 0 25px;

    border: none;

    border-radius: 7px;

    background: var(--mrri-vert);

    color: white;

    font-weight: 700;

    transition: .2s ease;
}

.search-button:hover {

    background: var(--mrri-vert-dark);
}


/* =========================================================
   RÉSULTATS RECHERCHE
   ========================================================= */

#results {

    position: relative;

    z-index: 20;
}


/* =========================================================
   ACCÈS RAPIDES
   ========================================================= */

.quick-section {

    padding-bottom: 75px;
}

.section-heading {

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 30px;
}

.section-heading h2 {

    margin: 0;

    font-size: 2rem;
}

.section-heading p {

    margin: 6px 0 0;

    color: var(--mrri-text-light);

    font-size: .95rem;
}

.heading-line {

    width: 42px;

    height: 3px;

    background: var(--mrri-or);

    margin-top: 12px;
}


/* Cartes */

.quick-card {

    height: 100%;

    background: white;

    border: 1px solid var(--mrri-border);

    border-radius: var(--radius-md);

    padding: 28px;

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        border-color .2s ease;
}

.quick-card:hover {

    transform: translateY(-4px);

    border-color: rgba(15,138,75,.30);

    box-shadow: var(--shadow-md);
}

.quick-icon {

    width: 52px;

    height: 52px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    margin-bottom: 22px;

    font-size: 1.3rem;
}

.quick-icon.green {

    background: #E5F5EC;

    color: var(--mrri-vert);
}

.quick-icon.blue {

    background: #E8EDFB;

    color: var(--mrri-bleu);
}

.quick-icon.gold {

    background: var(--mrri-or-light);

    color: #936D0F;
}

.quick-icon.dark {

    background: #E9EDF4;

    color: var(--mrri-bleu-nuit);
}

.quick-card h3 {

    font-family: var(--font-title);

    font-size: 1.15rem;

    margin-bottom: 9px;
}

.quick-card p {

    color: var(--mrri-text-light);

    font-size: .88rem;

    margin-bottom: 20px;
}

.quick-link {

    color: var(--mrri-vert);

    font-size: .86rem;

    font-weight: 700;
}

.quick-link:hover {

    color: var(--mrri-vert-dark);
}


/* =========================================================
   CATALOGUE STRUCTURES
   ========================================================= */

.structures-section {

    padding: 80px 0;

    background: white;

    border-top: 1px solid var(--mrri-border);

    border-bottom: 1px solid var(--mrri-border);
}


/* Filtres */

.category-filters {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    margin-bottom: 35px;
}

.category-filter {

    display: inline-flex;

    align-items: center;

    padding: 8px 15px;

    border: 1px solid var(--mrri-border);

    border-radius: 50px;

    background: white;

    color: var(--mrri-text-light);

    font-size: .82rem;

    font-weight: 600;

    transition: .2s ease;
}

.category-filter:hover {

    border-color: var(--mrri-vert);

    color: var(--mrri-vert);
}

.category-filter.active {

    background: var(--mrri-vert);

    border-color: var(--mrri-vert);

    color: white;
}


/* Carte structure */

.structure-card {

    height: 100%;

    display: flex;

    flex-direction: column;

    background: white;

    border: 1px solid var(--mrri-border);

    border-radius: var(--radius-md);

    padding: 24px;

    transition: .2s ease;
}

.structure-card:hover {

    transform: translateY(-3px);

    border-color: rgba(27,63,174,.30);

    box-shadow: var(--shadow-sm);
}

.structure-card-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 15px;
}

.structure-icon {

    flex-shrink: 0;

    width: 45px;

    height: 45px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    background: #E8F5EE;

    color: var(--mrri-vert);

    font-size: 1.1rem;
}

.structure-category {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 5px;

    background: #E8F5EE;

    color: var(--mrri-vert-dark);

    font-size: .7rem;

    font-weight: 700;
}

.structure-card h3 {

    min-height: 50px;

    margin-bottom: 5px;

    font-size: 1.05rem;

    line-height: 1.35;
}

.structure-sigle {

    color: var(--mrri-bleu);

    font-size: .78rem;

    font-weight: 700;

    margin-bottom: 14px;
}

.structure-address {

    flex-grow: 1;

    color: var(--mrri-text-light);

    font-size: .82rem;

    margin-bottom: 20px;
}

.structure-link {

    display: inline-flex;

    align-items: center;

    justify-content: space-between;

    padding-top: 15px;

    border-top: 1px solid var(--mrri-border);

    color: var(--mrri-bleu);

    font-size: .82rem;

    font-weight: 700;
}

.structure-link:hover {

    color: var(--mrri-bleu-dark);
}


/* =========================================================
   ACTUALITÉS
   ========================================================= */

.news-section {

    padding: 80px 0;
}

.news-card {

    height: 100%;

    overflow: hidden;

    background: white;

    border: 1px solid var(--mrri-border);

    border-radius: var(--radius-md);

    transition: .2s ease;
}

.news-card:hover {

    transform: translateY(-3px);

    box-shadow: var(--shadow-sm);
}

.news-image {

    position: relative;

    height: 190px;

    overflow: hidden;

    background: var(--mrri-bleu-nuit);
}

.news-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    transition: transform .4s ease;
}

.news-card:hover .news-image img {

    transform: scale(1.04);
}

.news-image-placeholder {

    height: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            var(--mrri-bleu),
            var(--mrri-bleu-nuit)
        );

    color: rgba(255,255,255,.8);
}

.news-badge {

    position: absolute;

    left: 15px;

    bottom: 15px;

    padding: 5px 10px;

    border-radius: 5px;

    background: var(--mrri-vert);

    color: white;

    font-size: .7rem;

    font-weight: 700;
}

.news-content {

    padding: 20px;
}

.news-date {

    color: var(--mrri-text-light);

    font-size: .72rem;

    margin-bottom: 8px;
}

.news-content h3 {

    font-size: 1.05rem;

    line-height: 1.4;

    margin: 0;
}


/* =========================================================
   PAGINATION
   ========================================================= */

.pagination .page-link {

    border-color: var(--mrri-border);

    color: var(--mrri-bleu);

    font-weight: 600;
}

.pagination .page-item.active .page-link {

    background: var(--mrri-vert);

    border-color: var(--mrri-vert);

    color: white;
}


/* =========================================================
   BLOC INSTITUTIONNEL
   ========================================================= */

.institution-section {

    padding: 75px 0;

    background: var(--mrri-bleu-nuit);

    color: white;
}

.institution-section h2 {

    color: white;

    font-size: 2.2rem;
}

.institution-section p {

    color: rgba(255,255,255,.72);

    max-width: 650px;
}

.institution-accent {

    width: 45px;

    height: 3px;

    background: var(--mrri-or);

    margin: 15px 0 20px;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 991px) {

    .mrri-hero {

        min-height: 560px;
    }

    .mrri-hero-content {

        min-height: 560px;
    }

    .search-section {

        margin-top: -35px;
    }

}

@media (max-width: 767px) {

    .mrri-hero {

        min-height: 620px;
    }

    .mrri-hero-content {

        min-height: 620px;
    }

    .mrri-hero h1 {

        font-size: 2.5rem;
    }

    .search-form {

        flex-direction: column;
    }

    .search-button {

        width: 100%;
    }

    .section-heading {

        align-items: flex-start;

        flex-direction: column;
    }

    .search-section {

        margin-bottom: 50px;
    }

}

</style>
<div class="mrri-top-band"></div>
<section class="mrri-hero">
    <img
        src="../../publique/image/GA.jpeg"
        alt="Bâtiment institutionnel du Gabon"
        class="mrri-hero-image"
    >
    <div class="mrri-hero-overlay"></div>
    <div class="container mrri-hero-content">
        <div class="mrri-hero-text">
            <div class="mrri-hero-label">
                Portail institutionnel
            </div>
            <h1>
                Plateforme de consultation
                des structures sous tutelle
            </h1>
            <p class="mrri-hero-description">
                Accédez simplement et rapidement aux informations
                relatives aux structures placées sous la tutelle
                du Ministère de la Réforme et des Relations avec
                les Institutions.
            </p>
            <a href="catalogue.php" class="btn-mrri-primary">
                Consulter les structures
                <span>→</span>
            </a>
        </div>
    </div>
</section>
<section class="search-section">
    <div class="container">
        <div class="search-box">
            <div class="search-title">
                Rechercher une structure
            </div>
            <form action="catalogue.php" method="get" class="search-form" >

                <div class="search-input-wrapper">

                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle
                            cx="11"
                            cy="11"
                            r="7"
                        ></circle>

                        <path
                            d="m20 20-4-4"
                        ></path>
                    </svg>


                    <input
                        id="search"
                        type="text"
                        name="q"
                        class="search-input"
                        placeholder="Nom de la structure, sigle, domaine d'activité..."
                        autocomplete="off"
                    >

                </div>


                <button
                    type="submit"
                    class="search-button"
                >
                    Rechercher
                </button>

            </form>


            <div
                id="results"
                class="mt-3"
            ></div>

        </div>

    </div>

</section>



<!-- =========================================================
     ACCÈS RAPIDES
     ========================================================= -->

<section class="quick-section">

    <div class="container">

        <div class="section-heading">

            <div>

                <h2>
                    Accéder aux informations
                </h2>

                <div class="heading-line"></div>

                <p>
                    Les principaux services de la plateforme.
                </p>

            </div>

        </div>


        <div class="row g-4">


            <!-- STRUCTURES -->

            <div class="col-md-6 col-lg-3">

                <div class="quick-card">

                    <div class="quick-icon green">

                        🏛️

                    </div>

                    <h3>
                        Structures sous tutelle
                    </h3>

                    <p>
                        Consultez l'ensemble des structures
                        placées sous la tutelle du ministère.
                    </p>

                    <a
                        href="catalogue.php"
                        class="quick-link"
                    >
                        Accéder au catalogue →
                    </a>

                </div>

            </div>



            <!-- INFORMATIONS -->

            <div class="col-md-6 col-lg-3">

                <div class="quick-card">

                    <div class="quick-icon blue">

                       🗂️

                    </div>

                    <h3>
                        Informations détaillées
                    </h3>

                    <p>
                        Découvrez les missions, services,
                        coordonnées et informations de chaque structure.
                    </p>

                    <a
                        href="catalogue.php"
                        class="quick-link"
                    >
                        Consulter →
                    </a>

                </div>

            </div>



            <!-- ACTUALITÉS -->

            <div class="col-md-6 col-lg-3">

                <div class="quick-card">

                    <div class="quick-icon gold">

                         📰
                    </div>

                    <h3>
                        Actualités
                    </h3>

                    <p>
                        Retrouvez les dernières actualités
                        publiées par le ministère et ses structures.
                    </p>

                    <a
                        href="actualite.php"
                        class="quick-link"
                    >
                        Voir les actualités →
                    </a>

                </div>

            </div>



            <!-- MRRI -->

            <div class="col-md-6 col-lg-3">

                <div class="quick-card">

                    <div class="quick-icon dark">

                        ℹ

                    </div>

                    <h3>
                        À propos du MRRI
                    </h3>

                    <p>
                        Découvrez le rôle du ministère,
                        ses missions et son action institutionnelle.
                    </p>

                    <a
                        href="#institution"
                        class="quick-link"
                    >
                        En savoir plus →
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
                                    Consulter la fiche
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
                        $photosActuStmt->fetch(PDO::FETCH_ASSOC);

                    ?>


                    <div class="col-md-6 col-lg-4">

                        <a
                            href="actualite_detail.php?id=<?= $actualite['id_actu'] ?>"
                            class="text-decoration-none"
                        >

                            <article class="news-card">


                                <div class="news-image">


                                    <?php if ($premierePhoto): ?>

                                        <img
                                            src="../../uploads/photos/<?= htmlspecialchars($premierePhoto['Photos_actu_url']) ?>"
                                            alt="<?= htmlspecialchars($actualite['titre']) ?>"
                                        >

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

    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <h2>
                    Une plateforme au service
                    de l'information publique
                </h2>

                <div class="institution-accent"></div>

                <p>

                    Cette plateforme facilite l'accès aux informations
                    relatives aux structures sous tutelle du Ministère
                    de la Réforme et des Relations avec les Institutions.

                    Elle contribue à une meilleure organisation,
                    centralisation et diffusion de l'information
                    institutionnelle.

                </p>

            </div>


            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">

                <a
                    href="catalogue.php"
                    class="btn-mrri-primary"
                >

                    Consulter les structures →

                </a>

            </div>

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