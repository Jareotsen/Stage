<?php
session_start();
require '../../config/database.php';
require_once '../../includes/dates.php';

/**
 * Recherche et filtre par structure, tous deux facultatifs.
 */
$recherche = trim((string) ($_GET['q'] ?? ''));
$sourceFiltre = (int) ($_GET['s'] ?? 0);

// Pagination : 9 actualités par page (3 rangées de 3).
$parPage = 9;
$page = max(1, (int) ($_GET['page'] ?? 1));

$baseSql = " FROM actualites
             JOIN structure ON actualites.id_structure = structure.id_struc";

$conditions = [];
$params = [];
if ($recherche !== '') {
    $conditions[] = "(actualites.titre LIKE :q
                      OR actualites.contenu LIKE :q
                      OR structure.nom_struc LIKE :q)";
    $params[':q'] = '%' . $recherche . '%';
}
if ($sourceFiltre) {
    $conditions[] = "actualites.id_structure = :s";
    $params[':s'] = $sourceFiltre;
}
if ($conditions) {
    $baseSql .= " WHERE " . implode(' AND ', $conditions);
}

// Nombre total de résultats pour calculer la pagination.
$countStmt = $db->prepare("SELECT COUNT(*)" . $baseSql);
$countStmt->execute($params);
$totalActualites = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalActualites / $parPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $parPage;

// La première photo de chaque actualité est récupérée dans la même requête.
$sql = "SELECT actualites.id_actu,
               actualites.titre,
               actualites.contenu,
               actualites.date_publication,
               structure.nom_struc,
               (SELECT p.Photos_actu_url
                  FROM actualites_photos p
                 WHERE p.id_actualites = actualites.id_actu
                 ORDER BY p.Position
                 LIMIT 1) AS image" . $baseSql . "
        ORDER BY actualites.date_publication DESC, actualites.id_actu DESC
        LIMIT :limite OFFSET :debut";

$actuStmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $actuStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$actuStmt->bindValue(':limite', $parPage, PDO::PARAM_INT);
$actuStmt->bindValue(':debut', $offset, PDO::PARAM_INT);
$actuStmt->execute();
$actualites = $actuStmt->fetchAll(PDO::FETCH_ASSOC);

// Structures ayant publié au moins une actualité (liste du filtre « Source »)
$sources = $db->query("
    SELECT DISTINCT structure.id_struc, structure.nom_struc
    FROM actualites
    JOIN structure ON actualites.id_structure = structure.id_struc
    ORDER BY structure.nom_struc
")->fetchAll(PDO::FETCH_ASSOC);

// En page 1 sans filtre, la plus récente est mise en avant au-dessus de la grille.
$aLaUne = ($page === 1 && $recherche === '' && !$sourceFiltre && $actualites)
    ? array_shift($actualites)
    : null;

function h(?string $valeur): string {
    return htmlspecialchars($valeur ?? '', ENT_QUOTES, 'UTF-8');
}

// Conserve la recherche et le filtre lors de la navigation entre les pages.
function lienActualites(array $modifications = []): string {
    $params = [
        'q' => trim((string) ($_GET['q'] ?? '')),
        's' => (int) ($_GET['s'] ?? 0),
        'page' => max(1, (int) ($_GET['page'] ?? 1)),
    ];
    foreach ($modifications as $cle => $valeur) {
        $params[$cle] = $valeur;
    }
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== 0 && $v !== null);
    return 'actualite.php' . ($params ? '?' . http_build_query($params) : '');
}

function imageActualite(array $a): string {
    $nom = trim((string) ($a['image'] ?? ''));
    return $nom !== ''
        ? '../../uploads/photos/' . h(basename($nom))
        : '../../publique/image/GA.jpeg';
}

require '../../includes/header.php';
?>

<style>
/* Styles propres aux actualités : les composants communs sont dans publique/css/portail.css */
.act-recherche { max-width: 640px; margin-top: 1.8rem; }
.act-recherche .acc-champ input { border-color: #fff; }
.act-recherche .acc-champ button { background: var(--acc-jaune); color: var(--acc-nuit); }
.act-recherche .acc-champ button:hover { background: #fff; }

.act-une { margin-bottom: clamp(2.5rem, 5vw, 3.5rem); }
.act-une .acc-actu { display: grid; grid-template-columns: minmax(0, 7fr) minmax(0, 5fr); border: 1px solid var(--acc-ligne); }
.act-une .acc-actu-media { aspect-ratio: auto; min-height: 380px; }
.act-une .acc-actu-corps { justify-content: center; padding: clamp(1.6rem, 3vw, 2.6rem); }
.act-une .acc-actu-etiquette { align-self: flex-start; margin-bottom: 1rem; padding: .3rem .7rem; border-radius: 2px; background: var(--acc-jaune); color: var(--acc-nuit); font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
.act-une .acc-actu h3 { font-size: clamp(1.5rem, 2.6vw, 2.1rem); line-height: 1.2; -webkit-line-clamp: 4; }
.act-une .acc-actu-extrait { -webkit-line-clamp: 4; font-size: .98rem; }

.act-grille { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.75rem; }
.act-grille .acc-actu { border: 1px solid var(--acc-ligne); }
.act-barre-source select { max-width: 320px; }

@media (max-width: 991.98px) {
  .act-une .acc-actu { grid-template-columns: 1fr; }
  .act-une .acc-actu-media { min-height: 0; aspect-ratio: 16 / 9; }
  .act-grille { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575.98px) {
  .act-grille { grid-template-columns: 1fr; }
  .act-barre-source select { max-width: 70vw; }
}
</style>

<header class="acc-bandeau">
  <img class="acc-bandeau-image" src="../../publique/image/GA1.jpe" alt="">
  <div class="container">
    <ol class="acc-fil" aria-label="Fil d'Ariane">
      <li><a href="accueil.php">Accueil</a></li>
      <li aria-current="page">Actualités</li>
    </ol>
    <span class="acc-surtitre">Salle de presse</span>
    <h1>Actualités</h1>
    <p>Informations, annonces et publications officielles des structures sous tutelle du Ministère.</p>

    <form class="act-recherche" action="actualite.php" method="get" role="search">
      <div class="acc-champ">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <label for="actualite-q" class="visually-hidden">Rechercher une actualité</label>
        <input type="search" id="actualite-q" name="q" value="<?= h($recherche) ?>" placeholder="Rechercher une actualité…">
        <?php if ($sourceFiltre): ?><input type="hidden" name="s" value="<?= $sourceFiltre ?>"><?php endif; ?>
        <button type="submit">Rechercher</button>
      </div>
    </form>
  </div>
</header>

<main class="acc-section">
  <div class="container">

    <?php if ($aLaUne): ?>
      <section class="act-une" aria-label="Actualité à la une">
        <a href="actualite_detail.php?id=<?= (int) $aLaUne['id_actu'] ?>" class="acc-actu une">
          <div class="acc-actu-media"><img src="<?= imageActualite($aLaUne) ?>" alt=""></div>
          <div class="acc-actu-corps">
            <span class="acc-actu-etiquette">À la une</span>
            <div class="acc-actu-meta">
              <span class="acc-actu-source"><?= h($aLaUne['nom_struc']) ?></span>
              <time class="acc-actu-date" datetime="<?= h(date('Y-m-d', strtotime($aLaUne['date_publication']) ?: time())) ?>"><?= h(formaterDateFr($aLaUne['date_publication'])) ?></time>
            </div>
            <h3><?= h($aLaUne['titre']) ?></h3>
            <?php if (!empty($aLaUne['contenu'])): ?>
              <p class="acc-actu-extrait"><?= h(mb_strimwidth(strip_tags($aLaUne['contenu']), 0, 260, '…')) ?></p>
            <?php endif; ?>
            <span class="acc-fleche">Lire l'actualité <span aria-hidden="true">→</span></span>
          </div>
        </a>
      </section>
    <?php endif; ?>

    <div class="acc-barre">
      <div>
        <strong><?= $totalActualites ?></strong> actualité<?= $totalActualites > 1 ? 's' : '' ?>
        <?php if ($recherche !== ''): ?>
          <span class="acc-filtre-actif">« <?= h($recherche) ?> »
            <a href="<?= h(lienActualites(['q' => '', 'page' => 1])) ?>" aria-label="Retirer la recherche">×</a></span>
        <?php endif; ?>
      </div>
      <?php if (count($sources) > 1): ?>
        <form action="actualite.php" method="get" class="act-barre-source">
          <?php if ($recherche !== ''): ?><input type="hidden" name="q" value="<?= h($recherche) ?>"><?php endif; ?>
          <label>Source
            <select name="s" onchange="this.form.submit()">
              <option value="">Toutes les structures</option>
              <?php foreach ($sources as $source): ?>
                <option value="<?= (int) $source['id_struc'] ?>" <?= $sourceFiltre === (int) $source['id_struc'] ? 'selected' : '' ?>><?= h($source['nom_struc']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </form>
      <?php endif; ?>
    </div>

    <?php if (!$aLaUne && empty($actualites)): ?>
      <div class="acc-vide" aria-live="polite">
        <h2>Aucune actualité trouvée</h2>
        <p>
          <?php if ($recherche !== '' || $sourceFiltre): ?>
            Aucun résultat ne correspond à vos critères. Essayez d'autres mots clés ou une autre source.
          <?php else: ?>
            Les nouvelles publications des structures apparaîtront ici dès leur mise en ligne.
          <?php endif; ?>
        </p>
      </div>
    <?php else: ?>
      <?php if ($actualites): ?>
        <section class="act-grille" aria-label="Liste des actualités">
          <?php foreach ($actualites as $actualite): ?>
            <a href="actualite_detail.php?id=<?= (int) $actualite['id_actu'] ?>" class="acc-actu">
              <div class="acc-actu-media"><img src="<?= imageActualite($actualite) ?>" alt="" loading="lazy"></div>
              <div class="acc-actu-corps">
                <div class="acc-actu-meta">
                  <span class="acc-actu-source"><?= h($actualite['nom_struc']) ?></span>
                  <time class="acc-actu-date" datetime="<?= h(date('Y-m-d', strtotime($actualite['date_publication']) ?: time())) ?>"><?= h(formaterDateFr($actualite['date_publication'])) ?></time>
                </div>
                <h3><?= h($actualite['titre']) ?></h3>
                <span class="acc-fleche">Lire la suite <span aria-hidden="true">→</span></span>
              </div>
            </a>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <?php if ($totalPages > 1): ?>
        <nav class="acc-pagination" aria-label="Pagination des actualités">
          <a href="<?= h(lienActualites(['page' => max(1, $page - 1)])) ?>" class="<?= $page <= 1 ? 'desactive' : '' ?>" aria-label="Page précédente">‹</a>
          <?php for ($numero = 1; $numero <= $totalPages; $numero++): ?>
            <?php if ($numero === $page): ?>
              <span class="actif" aria-current="page"><?= $numero ?></span>
            <?php else: ?>
              <a href="<?= h(lienActualites(['page' => $numero])) ?>"><?= $numero ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <a href="<?= h(lienActualites(['page' => min($totalPages, $page + 1)])) ?>" class="<?= $page >= $totalPages ? 'desactive' : '' ?>" aria-label="Page suivante">›</a>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</main>

<?php require '../../includes/footer.php'; ?>
