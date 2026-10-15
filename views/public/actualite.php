<?php
session_start();
require '../../config/database.php';

$actuStmt = $db->query(
    "SELECT actualites.id_actu, actualites.titre, actualites.contenu, actualites.date_publication, structure.nom_struc
     FROM actualites
     JOIN structure ON actualites.id_structure = structure.id_struc
     ORDER BY actualites.date_publication DESC"
);
$actualites = $actuStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupère la première photo de chaque actualité
foreach ($actualites as &$actualite) {
    $photoStmt = $db->prepare(
        "SELECT Photos_actu_url FROM actualites_photos WHERE id_actualites = :id ORDER BY Position LIMIT 1"
    );
    $photoStmt->execute([':id' => $actualite['id_actu']]);
    $actualite['image'] = $photoStmt->fetchColumn(); // false si aucune photo
}
unset($actualite);

// Formate une date SQL (YYYY-MM-DD) en français, ex : "12 juin 2024"
function formaterDateFr(string $dateSql): string {
    $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $timestamp = strtotime($dateSql);
    return date('d', $timestamp) . ' ' . $mois[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

require '../../includes/header.php';
?>

<link rel="stylesheet" href="../../publique/css/theme.css">

<main class="actualite-page">
    <header class="actualite-hero">
        <div class="actualite-hero-inner">
            <h1>Dernières actualités</h1>
            <p>Les informations et annonces officielles des structures placées sous la tutelle du Ministère des Réformes et des Relations avec les Institutions.</p>
        </div>
    </header>

   

    <?php if (empty($actualites)): ?>
        <section class="actualite-empty" aria-live="polite">
            <div class="actualite-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21z"/><path d="M4 5.5V21M8 7h8M8 11h8"/></svg>
            </div>
            <h2>Aucune actualité pour le moment</h2>
            <p>Les nouvelles publications des structures apparaîtront ici dès leur mise en ligne.</p>
        </section>
    <?php else: ?>
        <section class="actualite-feature-grid" aria-label="Dernières actualités">
            <?php foreach ($actualites as $index => $actualite): ?>
                <?php
                    $cardClass = $index === 0 ? 'featured' : ($index === 1 ? 'side-large' : ($index <= 3 ? 'side-small' : 'extra'));
                    $isExtra = $index > 3;
                ?>
                <?php if ($isExtra && $index === 4): ?><div class="actualite-extra-grid" aria-label="Autres actualités"><?php endif; ?>
                <article class="actualite-card <?= $cardClass ?>">
                    <div class="actualite-media">
                        <?php if (!empty($actualite['image'])): ?>
                            <img src="../../uploads/photos/<?= htmlspecialchars($actualite['image'], ENT_QUOTES, 'UTF-8') ?>" alt="Illustration de l’actualité : <?= htmlspecialchars($actualite['titre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                        <?php else: ?>
                            <div class="actualite-no-image" aria-label="Aucune image disponible">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-7h6v7M9 10h.01M15 10h.01"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="actualite-content">
                        <div class="actualite-meta">
                            <time datetime="<?= htmlspecialchars(date('Y-m-d', strtotime($actualite['date_publication'])), ENT_QUOTES, 'UTF-8') ?>"><?= formaterDateFr($actualite['date_publication']) ?></time>
                            <span><?= htmlspecialchars($actualite['nom_struc'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <h2><?= htmlspecialchars($actualite['titre'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if ($index === 0): ?>
                            <p><?= htmlspecialchars(mb_strimwidth(strip_tags($actualite['contenu']), 0, 190, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <a class="actualite-link" href="actualite_detail.php?id=<?= (int) $actualite['id_actu'] ?>">Lire l’actualité <span aria-hidden="true">→</span></a>
                    </div>
                </article>
                <?php if ($index === count($actualites) - 1 && count($actualites) > 4): ?></div><?php endif; ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<?php require '../../includes/footer.php'; ?>