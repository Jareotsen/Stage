<?php
session_start();
require '../../config/database.php';

/**
 * Recherche facultative dans les actualités.
 * Les noms de tables et de colonnes restent ceux du code existant.
 */
$recherche = trim((string) ($_GET['q'] ?? ''));

// Pagination : 9 actualités par page (3 rangées de 3).
$parPage = 9;
$page = max(1, (int) ($_GET['page'] ?? 1));

$baseSql = " FROM actualites
             JOIN structure ON actualites.id_structure = structure.id_struc";

$params = [];
if ($recherche !== '') {
    $baseSql .= " WHERE actualites.titre LIKE :q
                  OR actualites.contenu LIKE :q
                  OR structure.nom_struc LIKE :q";
    $params[':q'] = '%' . $recherche . '%';
}

// Nombre total de résultats pour calculer la pagination.
$countStmt = $db->prepare("SELECT COUNT(*)" . $baseSql);
$countStmt->execute($params);
$totalActualites = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalActualites / $parPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $parPage;

$sql = "SELECT actualites.id_actu,
               actualites.titre,
               actualites.contenu,
               actualites.date_publication,
               structure.nom_struc" . $baseSql . "
        ORDER BY actualites.date_publication DESC
        LIMIT :limite OFFSET :debut";

$actuStmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $actuStmt->bindValue($key, $value, PDO::PARAM_STR);
}
$actuStmt->bindValue(':limite', $parPage, PDO::PARAM_INT);
$actuStmt->bindValue(':debut', $offset, PDO::PARAM_INT);
$actuStmt->execute();
$actualites = $actuStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupère la première photo de chaque actualité.
$photoStmt = $db->prepare(
    "SELECT Photos_actu_url
     FROM actualites_photos
     WHERE id_actualites = :id
     ORDER BY Position
     LIMIT 1"
);
foreach ($actualites as &$actualite) {
    $photoStmt->execute([':id' => $actualite['id_actu']]);
    $actualite['image'] = $photoStmt->fetchColumn();
}
unset($actualite);

// Formate une date SQL en français, par exemple : « 12 juin 2024 ».
function formaterDateFr(string $dateSql): string
{
    $mois = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
    ];

    $timestamp = strtotime($dateSql);
    if ($timestamp === false) {
        return $dateSql;
    }

    return date('d', $timestamp) . ' ' . $mois[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

require '../../includes/header.php';
?>

<style>
.actualite-page {
    max-width: 1180px;
    margin: 0 auto;
    padding: 42px 26px 76px;
    color: #24364b;
}
.actualite-hero {
    margin-bottom: 28px;
    padding-bottom: 22px;
    border-bottom: 1px solid #dce4ec;
}
.actualite-hero h1 {
    margin: 0;
    color: #145ca8;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 600;
    letter-spacing: -.035em;
    line-height: 1.15;
}
.actualite-hero p {
    margin: 12px 0 0;
    color: #5e7185;
    font-size: 1rem;
    line-height: 1.65;
}
.actualite-search {
    display: flex;
    align-items: end;
    gap: 12px;
    margin: 0 0 30px;
    padding: 20px;
    background: #f5f8fb;
    border: 1px solid #e0e7ee;
}
.actualite-search-field {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 8px;
}
.actualite-search label {
    color: #334c65;
    font-size: .88rem;
    font-weight: 700;
}
.actualite-search input {
    width: 100%;
    min-height: 46px;
    padding: 11px 13px;
    border: 1px solid #cbd7e2;
    border-radius: 3px;
    background: #fff;
    color: #24364b;
    font: inherit;
}
.actualite-search input:focus {
    outline: 3px solid rgba(18, 98, 173, .16);
    border-color: #1262ad;
}
.actualite-search button,
.actualite-reset {
    display: inline-flex;
    min-height: 46px;
    align-items: center;
    justify-content: center;
    padding: 11px 18px;
    border: 1px solid #1262ad;
    border-radius: 3px;
    background: #1262ad;
    color: #fff;
    font: inherit;
    font-size: .9rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}
.actualite-search button:hover {
    background: #0e4e8a;
}
.actualite-reset {
    border-color: #cbd7e2;
    background: #fff;
    color: #35536f;
}
.actualite-reset:hover {
    background: #edf3f8;
}
.actualite-results-heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 16px;
    margin: 0 0 8px;
}
.actualite-results-heading h2 {
    margin: 0;
    color: #244c72;
    font-size: 1.25rem;
    font-weight: 700;
}
.actualite-count {
    color: #6a7e91;
    font-size: .88rem;
}
.actualite-list {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 26px;
    margin-top: 18px;
}
.actualite-item {
    min-width: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 5px 22px rgba(25, 53, 78, .10);
    border: 1px solid #edf1f5;
}
.actualite-media {
    width: 100%;
    height: 230px;
    overflow: hidden;
    background: #e5edf4;
}
.actualite-media img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.actualite-no-image {
    display: flex;
    width: 100%;
    height: 100%;
    align-items: center;
    justify-content: center;
    color: #52728e;
    background: linear-gradient(135deg, #e6eef5, #d2e2ef);
    font-size: .9rem;
}
.actualite-item-body {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: flex-start;
    padding: 22px 20px 24px;
}
.actualite-date {
    padding-top: 0;
    margin-bottom: 18px;
    color: #637b91;
    font-size: .88rem;
    line-height: 1.5;
}
.actualite-date time {
    display: block;
    color: #41617f;
    font-weight: 700;
}
.actualite-source {
    display: block;
    margin-top: 7px;
    color: #74879a;
    font-size: .8rem;
}
.actualite-item-content {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: flex-start;
    padding: 0 20px 22px;
}
.actualite-item-content h3 {
    margin: 0 0 10px;
    color: #145ca8;
    font-size: 1.22rem;
    font-weight: 700;
    line-height: 1.4;
}
.actualite-item-content p {
    margin: 0 0 13px;
    color: #52677b;
    font-size: .95rem;
    line-height: 1.7;
}
.actualite-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: auto;
    padding-top: 8px;
    color: #1262ad;
    font-size: .9rem;
    font-weight: 750;
    text-decoration: none;
}
.actualite-link span {
    transition: transform .2s ease;
}
.actualite-link:hover {
    color: #0b7a49;
    text-decoration: underline;
}
.actualite-link:hover span {
    transform: translateX(4px);
}
.actualite-empty {
    padding: 38px 22px;
    border: 1px solid #dce4ec;
    background: #f7f9fb;
    text-align: center;
}
.actualite-empty h2 {
    margin: 0 0 8px;
    color: #244c72;
    font-size: 1.25rem;
}
.actualite-empty p {
    margin: 0;
    color: #65798d;
    line-height: 1.6;
}
.actualite-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 34px;
}
.actualite-pagination a,
.actualite-pagination span {
    display: inline-flex;
    min-width: 40px;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    border: 1px solid #d6e0e9;
    border-radius: 4px;
    background: #fff;
    color: #285b80;
    text-decoration: none;
    font-weight: 700;
}
.actualite-pagination .is-current {
    border-color: #1262ad;
    background: #1262ad;
    color: #fff;
}
.actualite-pagination a:hover {
    background: #eef5fb;
}
@media (max-width: 900px) {
    .actualite-list { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
}
@media (max-width: 640px) {
    .actualite-page {
        padding: 28px 15px 48px;
    }
    .actualite-search {
        align-items: stretch;
        flex-direction: column;
        padding: 15px;
    }
    .actualite-search button,
    .actualite-reset {
        width: 100%;
    }
    .actualite-list { grid-template-columns: 1fr; gap: 18px; }
    .actualite-media { height: 220px; }
    .actualite-item-body { padding: 18px 16px 8px; }
    .actualite-item-content { padding: 0 16px 20px; }
    .actualite-results-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 5px;
    }
    .actualite-item-content h3 {
        font-size: 1.1rem;
    }
}
@media (prefers-reduced-motion: reduce) {
    .actualite-link span {
        transition: none;
    }
}
</style>

<main class="actualite-page">
    <header class="actualite-hero">
        <h1>Actualités</h1>
        <p>Retrouvez les informations, annonces et publications officielles des structures.</p>
    </header>

    <form class="actualite-search" action="actualite.php" method="get" role="search">
        <div class="actualite-search-field">
            <label for="actualite-q">Mots clés</label>
            <input
                type="search"
                id="actualite-q"
                name="q"
                value="<?= htmlspecialchars($recherche, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Rechercher une actualité…"
            >
        </div>
        <button type="submit">Rechercher</button>
        <?php if ($recherche !== ''): ?>
            <a class="actualite-reset" href="actualite.php">Réinitialiser</a>
        <?php endif; ?>
    </form>

    <div class="actualite-results-heading">
        <h2><?= $recherche !== '' ? 'Résultats de recherche' : 'Toutes les actualités' ?></h2>
        <span class="actualite-count">
            <?= count($actualites) ?> résultat<?= count($actualites) > 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($actualites)): ?>
        <section class="actualite-empty" aria-live="polite">
            <h2>Aucune actualité trouvée</h2>
            <p>
                <?php if ($recherche !== ''): ?>
                    Aucun résultat ne correspond à « <?= htmlspecialchars($recherche, ENT_QUOTES, 'UTF-8') ?> ».
                    Essayez d’autres mots clés.
                <?php else: ?>
                    Les nouvelles publications des structures apparaîtront ici dès leur mise en ligne.
                <?php endif; ?>
            </p>
        </section>
    <?php else: ?>
        <section class="actualite-list" aria-label="Liste des actualités">
            <?php foreach ($actualites as $actualite): ?>
                <?php
                    $date = (string) $actualite['date_publication'];
                    $imageNom = trim((string) ($actualite['image'] ?: ''));
                ?>
                <article class="actualite-item">
                    <div class="actualite-media">
                        <?php if ($imageNom !== ''): ?>
                            <img
                                src="../../uploads/photos/<?= htmlspecialchars(basename($imageNom), ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars((string) $actualite['titre'], ENT_QUOTES, 'UTF-8') ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="actualite-no-image">Aucune image disponible</div>
                        <?php endif; ?>
                    </div>
                    <div class="actualite-item-body">
                        <div class="actualite-date">
                            <time datetime="<?= htmlspecialchars(date('Y-m-d', strtotime($date) ?: time()), ENT_QUOTES, 'UTF-8') ?>">
                                <svg xmlns="http://www.w3.org/2000/svg"
     width="17" height="17"
     viewBox="0 0 24 24"
     fill="none"
     stroke="currentColor"
     stroke-width="1.7"
     stroke-linecap="round"
     stroke-linejoin="round"
     aria-hidden="true">
    <rect x="3" y="5" width="18" height="16" rx="2"/>
    <path d="M16 3v4M8 3v4M3 11h18"/>
</svg> <?= htmlspecialchars(formaterDateFr($date), ENT_QUOTES, 'UTF-8') ?>
                            </time>
                        </div>
                    </div>
                    <div class="actualite-item-content">
                        <h3><?= htmlspecialchars((string) $actualite['titre'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <a class="actualite-link" href="actualite_detail.php?id=<?= (int) $actualite['id_actu'] ?>">
                            Lire plus <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($totalPages > 1): ?>
            <nav class="actualite-pagination" aria-label="Pagination des actualités">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&amp;q=<?= urlencode($recherche) ?>" aria-label="Page précédente">‹</a>
                <?php endif; ?>
                <?php for ($numero = 1; $numero <= $totalPages; $numero++): ?>
                    <?php if ($numero === $page): ?>
                        <span class="is-current" aria-current="page"><?= $numero ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $numero ?>&amp;q=<?= urlencode($recherche) ?>"><?= $numero ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&amp;q=<?= urlencode($recherche) ?>" aria-label="Page suivante">›</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require '../../includes/footer.php'; ?>
