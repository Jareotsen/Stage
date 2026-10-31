<?php
declare(strict_types=1);

session_start();
require '../../config/database.php';
require_once '../../includes/dates.php';

/* Récupération de l'actualité en base à partir de l'id passé en URL */
$id = $_GET['id'] ?? '';
$actualite = null;
$photos = [];
$associees = [];
$recentes = [];

if (is_string($id) && $id !== '' && ctype_digit($id)) {
    $stmt = $db->prepare(
        "SELECT actualites.id_actu, actualites.titre, actualites.contenu, actualites.date_publication,
                actualites.id_structure, structure.nom_struc, structure.sigle
         FROM actualites
         JOIN structure ON actualites.id_structure = structure.id_struc
         WHERE actualites.id_actu = :id"
    );
    $stmt->execute([':id' => (int) $id]);
    $actualite = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($actualite) {
    $photoStmt = $db->prepare(
        "SELECT Photos_actu_url FROM actualites_photos WHERE id_actualites = :id ORDER BY Position"
    );
    $photoStmt->execute([':id' => $actualite['id_actu']]);
    $photos = $photoStmt->fetchAll(PDO::FETCH_COLUMN);

    // Requête commune : autres actualités avec leur première photo
    $selectAutres = "SELECT a.id_actu, a.titre, a.date_publication, s.nom_struc,
                            (SELECT p.Photos_actu_url FROM actualites_photos p
                              WHERE p.id_actualites = a.id_actu ORDER BY p.Position LIMIT 1) AS photo
                     FROM actualites a
                     JOIN structure s ON a.id_structure = s.id_struc";

    // Articles associés : d'abord ceux de la même structure, puis les plus récents
    $associeesStmt = $db->prepare(
        "$selectAutres
         WHERE a.id_actu <> :id
         ORDER BY (a.id_structure = :structure) DESC, a.date_publication DESC, a.id_actu DESC
         LIMIT 3"
    );
    $associeesStmt->execute([':id' => $actualite['id_actu'], ':structure' => $actualite['id_structure']]);
    $associees = $associeesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Colonne « Dernières actualités » : les plus récentes, sans doublon avec les articles associés
    $exclues = array_merge([(int) $actualite['id_actu']], array_map('intval', array_column($associees, 'id_actu')));
    $marqueurs = implode(',', array_fill(0, count($exclues), '?'));
    $recentesStmt = $db->prepare(
        "$selectAutres
         WHERE a.id_actu NOT IN ($marqueurs)
         ORDER BY a.date_publication DESC, a.id_actu DESC
         LIMIT 4"
    );
    $recentesStmt->execute($exclues);
    $recentes = $recentesStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    http_response_code(404);
}

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

/* ---------- Préparation de l'affichage ---------- */
$paragraphes = [];
$chapeau = '';
$tempsLecture = 1;

if ($actualite) {
    // Paragraphes : séparés par une ligne vide, ou par un simple retour à la ligne s'il n'y en a pas
    $texte = trim((string) $actualite['contenu']);
    $blocs = preg_split('/\R\s*\R/u', $texte) ?: [];
    if (count($blocs) <= 1) {
        $blocs = preg_split('/\R/u', $texte) ?: [];
    }
    $paragraphes = array_values(array_filter(array_map('trim', $blocs), static fn ($b) => $b !== ''));

    // Le premier paragraphe sert de chapeau quand l'article en compte plusieurs
    if (count($paragraphes) > 1) {
        $chapeau = array_shift($paragraphes);
    }

    $nombreMots = count(preg_split('/\s+/u', $texte, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    $tempsLecture = max(1, (int) ceil($nombreMots / 200));
}

$imageActualite = static fn (?string $photo): string => $photo
    ? '../../uploads/photos/' . basename($photo)
    : '../../publique/image/GA.jpeg';

require '../../includes/header.php';
?>

<style>
/* Détail d'une actualité : les composants communs sont dans publique/css/portail.css */
.art-page { padding: clamp(2rem, 4vw, 3rem) 0 clamp(3.5rem, 7vw, 5rem); background: #fff; }
.art-grille { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: clamp(2rem, 5vw, 4rem); align-items: start; }

/* Fil d'Ariane sur fond clair */
.art-fil { display: flex; flex-wrap: wrap; gap: .45rem; margin: 0 0 1.6rem; padding: 0; list-style: none; color: var(--acc-gris); font-size: .8rem; }
.art-fil li + li::before { content: "›"; margin-right: .45rem; color: #A0A8B5; }
.art-fil a { color: var(--acc-encre); font-weight: 600; }
.art-fil a:hover { color: var(--acc-vert-fonce); }

/* En-tête de l'article */
.art-rubrique { display: inline-block; margin-bottom: .9rem; padding: .25rem .6rem; border-radius: 2px; background: var(--acc-vert-fonce); color: #fff; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.art-rubrique:hover { background: var(--acc-nuit); color: #fff; }
.art-titre { margin: 0 0 1.1rem; color: var(--acc-encre); font-family: var(--font-title); font-size: clamp(1.9rem, 4vw, 2.9rem); font-weight: 600; line-height: 1.15; overflow-wrap: anywhere; }
.art-chapeau { margin: 0 0 1.5rem; color: var(--acc-encre); font-size: clamp(1.08rem, 1.6vw, 1.22rem); font-weight: 600; line-height: 1.6; }

/* Signature */
.art-signature { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.6rem; padding: .9rem 0; border-top: 1px solid var(--acc-ligne); border-bottom: 1px solid var(--acc-ligne); }
.art-auteur { display: flex; align-items: center; gap: .75rem; }
.art-avatar { display: flex; flex: 0 0 42px; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 50%; background: var(--acc-nuit); color: #fff; font-size: 1.05rem; }
.art-auteur-nom { display: block; color: var(--acc-encre); font-size: .9rem; font-weight: 700; line-height: 1.3; }
.art-auteur-nom:hover { color: var(--acc-vert-fonce); }
.art-meta { color: var(--acc-gris); font-size: .8rem; }

/* Image principale + légende */
.art-figure { margin: 0 0 2rem; }
.art-figure img { display: block; width: 100%; max-height: 520px; object-fit: cover; border-radius: 4px; background: var(--acc-fond); }
.art-figure figcaption { margin-top: .6rem; padding-left: .7rem; border-left: 3px solid var(--acc-jaune); color: var(--acc-gris); font-size: .82rem; line-height: 1.5; }

/* Corps du texte */
.art-corps { color: var(--acc-encre); font-size: 1.08rem; line-height: 1.85; text-align: justify; hyphens: auto; overflow-wrap: anywhere; }
.art-corps p { margin: 0 0 1.3rem; }
.art-lire-aussi { display: flex; gap: 1rem; align-items: center; margin: 1.8rem 0; padding: 1rem 1.1rem; border-left: 4px solid var(--acc-vert); background: var(--acc-fond); text-align: left; }
.art-lire-aussi img { flex: 0 0 96px; width: 96px; height: 68px; border-radius: 3px; object-fit: cover; }
.art-lire-aussi small { display: block; color: var(--acc-vert-fonce); font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
.art-lire-aussi strong { display: block; color: var(--acc-encre); font-family: var(--font-title); font-size: 1.02rem; font-weight: 600; line-height: 1.35; }
.art-lire-aussi:hover strong { color: var(--acc-bleu-fonce); }

/* Galerie (photos supplémentaires) */
.art-galerie { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .75rem; margin: 2rem 0 0; }
.art-galerie a { display: block; overflow: hidden; border-radius: 4px; }
.art-galerie img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; transition: transform .3s ease; }
.art-galerie a:hover img { transform: scale(1.04); }

.art-pied { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 2.5rem; padding-top: 1.2rem; border-top: 1px solid var(--acc-ligne); }

/* Colonne latérale */
.art-lateral { position: sticky; top: 100px; display: grid; gap: 1.5rem; }
.art-carte { padding: 1.4rem; border: 1px solid var(--acc-ligne); border-radius: 4px; background: #fff; }
.art-carte h2 { margin: 0 0 1rem; padding-bottom: .6rem; border-bottom: 2px solid var(--acc-encre); color: var(--acc-encre); font-size: .8rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.art-source { border: 0; background: var(--acc-nuit); color: rgba(255,255,255,.78); }
.art-source h2 { border-bottom-color: rgba(255,255,255,.25); color: #fff; }
.art-source strong { display: block; margin-bottom: .3rem; color: #fff; font-family: var(--font-title); font-size: 1.1rem; font-weight: 600; line-height: 1.3; }
.art-source p { margin: 0; font-size: .86rem; line-height: 1.55; }
.art-source a.lien { display: inline-flex; gap: .4rem; margin-top: .9rem; color: var(--acc-jaune); font-size: .86rem; font-weight: 700; }
.art-recentes { margin: 0; padding: 0; list-style: none; }
.art-recentes li + li { border-top: 1px solid var(--acc-ligne); }
.art-recentes a { display: flex; gap: .8rem; padding: .85rem 0; color: var(--acc-encre); }
.art-recentes img { flex: 0 0 72px; width: 72px; height: 54px; border-radius: 3px; object-fit: cover; }
.art-recentes strong { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; font-size: .86rem; font-weight: 600; line-height: 1.4; }
.art-recentes a:hover strong { color: var(--acc-bleu-fonce); }
.art-recentes time { display: block; margin-top: .2rem; color: var(--acc-gris); font-size: .74rem; }

/* Articles associés */
.art-associes { padding: clamp(3rem, 6vw, 4.5rem) 0; background: var(--acc-fond); }
.art-associes-grille { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; }
.art-associes .acc-actu { border: 1px solid var(--acc-ligne); }

@media (max-width: 991.98px) {
  .art-grille { grid-template-columns: 1fr; }
  .art-lateral { position: static; }
  .art-associes-grille { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575.98px) {
  .art-corps { text-align: left; }
  .art-associes-grille { grid-template-columns: 1fr; }
  .art-lire-aussi img { display: none; }
}
@media (prefers-reduced-motion: reduce) { .art-galerie img { transition: none; } }
</style>

<?php if (!$actualite): ?>

<main class="acc-section">
  <div class="container">
    <div class="acc-vide">
      <h2>Actualité introuvable</h2>
      <p>Cette actualité n'existe plus ou n'est pas disponible. <a href="actualite.php">Voir toutes les actualités</a></p>
    </div>
  </div>
</main>

<?php else: ?>

<main class="art-page">
  <div class="container art-grille">

    <article>
      <ol class="art-fil" aria-label="Fil d'Ariane">
        <li><a href="accueil.php">Accueil</a></li>
        <li><a href="actualite.php">Actualités</a></li>
        <li aria-current="page"><?= $escape($actualite['sigle'] ?: $actualite['nom_struc']) ?></li>
      </ol>

      <a class="art-rubrique" href="actualite.php?s=<?= (int) $actualite['id_structure'] ?>"><?= $escape($actualite['sigle'] ?: $actualite['nom_struc']) ?></a>
      <h1 class="art-titre"><?= $escape($actualite['titre']) ?></h1>

      <?php if ($chapeau !== ''): ?>
        <p class="art-chapeau"><?= nl2br($escape($chapeau)) ?></p>
      <?php endif; ?>

      <div class="art-signature">
        <div class="art-auteur">
          <span class="art-avatar" aria-hidden="true"><i class="bi bi-bank"></i></span>
          <div>
            <a class="art-auteur-nom" href="fiche.php?id=<?= (int) $actualite['id_structure'] ?>"><?= $escape($actualite['nom_struc']) ?></a>
            <span class="art-meta">
              <?php if (!empty($actualite['date_publication'])): ?>
                <time datetime="<?= $escape($actualite['date_publication']) ?>"><?= $escape(formaterDateFr($actualite['date_publication'])) ?></time> ·
              <?php endif; ?>
              <?= $tempsLecture ?> min de lecture
            </span>
          </div>
        </div>
      </div>

      <?php if ($photos): ?>
        <figure class="art-figure">
          <img src="<?= $escape($imageActualite($photos[0])) ?>" alt="<?= $escape($actualite['titre']) ?>">
          <figcaption>Photo : <?= $escape($actualite['nom_struc']) ?></figcaption>
        </figure>
      <?php endif; ?>

      <div class="art-corps">
        <?php foreach ($paragraphes as $i => $paragraphe): ?>
          <p><?= nl2br($escape($paragraphe)) ?></p>
          <?php if ($i === 1 && count($paragraphes) > 2 && !empty($associees)): ?>
            <?php $lireAussi = $associees[0]; ?>
            <a class="art-lire-aussi" href="actualite_detail.php?id=<?= (int) $lireAussi['id_actu'] ?>">
              <img src="<?= $escape($imageActualite($lireAussi['photo'])) ?>" alt="" loading="lazy">
              <span><small>À lire aussi</small><strong><?= $escape($lireAussi['titre']) ?></strong></span>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <?php if (count($photos) > 1): ?>
        <div class="art-galerie" aria-label="Autres photos">
          <?php foreach (array_slice($photos, 1) as $photo): ?>
            <a href="<?= $escape($imageActualite($photo)) ?>" target="_blank" rel="noopener"><img src="<?= $escape($imageActualite($photo)) ?>" alt="Photo : <?= $escape($actualite['titre']) ?>" loading="lazy"></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="art-pied">
        <a href="actualite.php" class="acc-lien-fleche"><span aria-hidden="true">←</span> Toutes les actualités</a>
        <a href="actualite.php?s=<?= (int) $actualite['id_structure'] ?>" class="acc-lien-fleche">Actualités de <?= $escape($actualite['sigle'] ?: 'cette structure') ?></a>
      </div>
    </article>

    <aside class="art-lateral" aria-label="Informations complémentaires">
      <section class="art-carte art-source" aria-labelledby="art-source-titre">
        <h2 id="art-source-titre">Publié par</h2>
        <strong><?= $escape($actualite['nom_struc']) ?></strong>
        <p>Retrouvez les missions, la composition et les coordonnées de cette structure.</p>
        <a class="lien" href="fiche.php?id=<?= (int) $actualite['id_structure'] ?>">Voir la fiche</a>
      </section>

      <?php if ($recentes): ?>
        <section class="art-carte" aria-labelledby="art-recentes-titre">
          <h2 id="art-recentes-titre">Dernières actualités</h2>
          <ul class="art-recentes">
            <?php foreach ($recentes as $r): ?>
              <li>
                <a href="actualite_detail.php?id=<?= (int) $r['id_actu'] ?>">
                  <img src="<?= $escape($imageActualite($r['photo'])) ?>" alt="" loading="lazy">
                  <span>
                    <strong><?= $escape($r['titre']) ?></strong>
                    <?php if (!empty($r['date_publication'])): ?><time datetime="<?= $escape($r['date_publication']) ?>"><?= $escape(formaterDateFr($r['date_publication'])) ?></time><?php endif; ?>
                  </span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>
    </aside>

  </div>
</main>

<?php if ($associees): ?>
  <section class="art-associes" aria-labelledby="art-associes-titre">
    <div class="container">
      <div class="acc-entete">
        <div>
          <span class="acc-surtitre">À lire également</span>
          <h2 id="art-associes-titre">Articles associés</h2>
        </div>
        <a href="actualite.php" class="acc-lien-fleche">Toutes les actualités</a>
      </div>
      <div class="art-associes-grille">
        <?php foreach ($associees as $a): ?>
          <a href="actualite_detail.php?id=<?= (int) $a['id_actu'] ?>" class="acc-actu">
            <div class="acc-actu-media"><img src="<?= $escape($imageActualite($a['photo'])) ?>" alt="" loading="lazy"></div>
            <div class="acc-actu-corps">
              <div class="acc-actu-meta">
                <span class="acc-actu-source"><?= $escape($a['nom_struc']) ?></span>
                <?php if (!empty($a['date_publication'])): ?><time class="acc-actu-date" datetime="<?= $escape($a['date_publication']) ?>"><?= $escape(formaterDateFr($a['date_publication'])) ?></time><?php endif; ?>
              </div>
              <h3><?= $escape($a['titre']) ?></h3>
              <span class="acc-fleche">Lire la suite</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php endif; ?>

<?php require '../../includes/footer.php'; ?>
