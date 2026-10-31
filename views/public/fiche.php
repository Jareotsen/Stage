<?php
session_start();
require '../../config/database.php';
require_once '../../includes/dates.php';

if (!isset($_GET['id'])) {
    die("Aucune structure sélectionnée.");
}
$id = (int) $_GET['id'];

$estAgent = isset($_SESSION['role']) && $_SESSION['role'] === 'agent_ministere';
$sql = $estAgent
    ? "SELECT * FROM documents WHERE id_structure = :id"
    : "SELECT * FROM documents WHERE id_structure = :id AND est_public = 1";
$doc = $db->prepare($sql);
$doc->execute([':id' => $id]);
$documents = $doc->fetchAll(PDO::FETCH_ASSOC);

$resultat = $db->prepare("SELECT * FROM structure JOIN categories ON structure.id_cat = categories.id_cat WHERE id_struc = :id");
$resultat->execute([':id' => $id]);
$structure = $resultat->fetch(PDO::FETCH_ASSOC);

if ($structure) {
    $photosStmt = $db->prepare("SELECT * FROM structure_photos WHERE id_structure = :id ORDER BY Position");
    $photosStmt->execute([':id' => $id]);
    $photosStructure = $photosStmt->fetchAll(PDO::FETCH_ASSOC);

    // Actualités de la structure, avec leur première photo (sous-requête)
    $actualitesStmt = $db->prepare("
        SELECT a.*,
               (SELECT p.Photos_actu_url
                  FROM actualites_photos p
                 WHERE p.id_actualites = a.id_actu
                 ORDER BY p.Position
                 LIMIT 1) AS photo
        FROM actualites a
        WHERE a.id_structure = :id
        ORDER BY a.date_publication DESC, a.id_actu DESC
    ");
    $actualitesStmt->execute([':id' => $id]);
    $actualites = $actualitesStmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------- Préparation de l'affichage (aucune requête supplémentaire) ---------- */
    $h = static function ($valeur): string {
        return htmlspecialchars((string) ($valeur ?? ''), ENT_QUOTES, 'UTF-8');
    };

    $nomStructure = trim($structure['nom_struc'] ?? '') ?: 'Structure sans nom';
    $nomCategorie = mb_convert_case(mb_strtolower($structure['nom_cat'] ?? 'Catégorie non définie', 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

    /*
     * Image de fond de l'en-tête :
     *  1. la première photo de la structure si c'est une vraie photo (paysage, assez large) ;
     *     un logo carré serait mal recadré, il est alors affiché dans un médaillon ;
     *  2. sinon la photo de la dernière actualité de la structure ;
     *  3. sinon une vue de Libreville.
     */
    $dossierPhotos = __DIR__ . '/../../uploads/photos/';
    $fondHero = null;
    $logoStructure = null;
    foreach ($photosStructure as $photo) {
        $taille = @getimagesize($dossierPhotos . basename($photo['Photos_url']));
        $estPaysage = $taille && $taille[0] >= 700 && $taille[0] >= $taille[1] * 1.2;
        if ($estPaysage && !$fondHero) {
            $fondHero = '../../uploads/photos/' . $photo['Photos_url'];
        } elseif (!$estPaysage && !$logoStructure) {
            $logoStructure = '../../uploads/photos/' . $photo['Photos_url'];
        }
    }
    if (!$fondHero) {
        foreach ($actualites as $actualite) {
            if (!empty($actualite['photo'])) {
                $fondHero = '../../uploads/photos/' . $actualite['photo'];
                break;
            }
        }
    }
    $fondHero = $fondHero ?: '../../publique/image/GA.jpeg';

    // La galerie montre toutes les photos sauf celle déjà utilisée en fond ou en médaillon
    $galerie = array_values(array_filter($photosStructure, static function ($p) use ($fondHero, $logoStructure) {
        $chemin = '../../uploads/photos/' . $p['Photos_url'];
        return $chemin !== $fondHero && $chemin !== $logoStructure;
    }));

    $adresse   = trim($structure['adresse'] ?? '');
    $telephone = trim($structure['telephone'] ?? '');
    $email     = trim($structure['email'] ?? '');
    $siteWeb   = trim($structure['site_web'] ?? '');
    $directeur = trim($structure['directeur_general'] ?? '');
    $mission   = trim($structure['mission'] ?? '');
    $historique     = trim($structure['historique'] ?? '');
    $composition    = trim($structure['composition'] ?? '');
    $texteFondateur = trim($structure['texte_fondateur'] ?? '');
    $fonctionDirigeant = trim($structure['fonction_dirigeant'] ?? '') ?: 'Dirigeant(e)';
    $dirigeantDepuis   = !empty($structure['dirigeant_depuis']) ? formaterDateFr($structure['dirigeant_depuis']) : null;
    $dateCreation      = !empty($structure['date_creation']) ? formaterDateFr($structure['date_creation']) : null;
    // Photo du/de la dirigeant(e) : on n'affiche qu'un fichier réellement présent dans le dossier des photos
    $photoDirigeant = basename((string) ($structure['photo_dirigeant'] ?? ''));
    $photoDirigeant = ($photoDirigeant !== '' && is_file($dossierPhotos . $photoDirigeant)) ? $photoDirigeant : null;
    // Initiales affichées quand il n'y a pas de photo (ex. « Paulette MISSAMBO » → « PM »)
    $initialesDirigeant = '';
    foreach (array_slice(preg_split('/\s+/u', $directeur, -1, PREG_SPLIT_NO_EMPTY), 0, 2) as $mot) {
        $initialesDirigeant .= mb_strtoupper(mb_substr($mot, 0, 1));
    }

    // Texte libre → paragraphes échappés (une ligne vide sépare deux paragraphes)
    $paragraphes = static function (string $texte) use ($h): string {
        $html = '';
        foreach (preg_split('/\R\s*\R/u', $texte) ?: [] as $bloc) {
            if (trim($bloc) !== '') {
                $html .= '<p>' . nl2br($h(trim($bloc))) . '</p>';
            }
        }
        return $html;
    };

    // Composition : une ligne = un membre ou un organe, « Fonction : Nom » met la fonction en évidence
    $membres = [];
    foreach (preg_split('/\R/u', $composition) ?: [] as $ligne) {
        $ligne = trim(preg_replace('/^[-•*–]\s*/u', '', trim($ligne)));
        if ($ligne === '') {
            continue;
        }
        $parties = preg_split('/\s*:\s*/u', $ligne, 2);
        $membres[] = count($parties) === 2 && $parties[0] !== '' && mb_strlen($parties[0]) <= 60
            ? ['role' => $parties[0], 'nom' => $parties[1]]
            : ['role' => null, 'nom' => $ligne];
    }

    // Sommaire de la page : seules les sections renseignées y figurent
    $sommaire = array_filter([
        'role' => 'Rôle et missions',
        'historique' => ($historique !== '' || $dateCreation || $texteFondateur !== '') ? 'Historique' : null,
        'composition' => $membres ? 'Composition' : null,
        'actualites' => 'Actualités',
    ]);

    // Liens sécurisés : on n'affiche un lien que pour un schéma http(s), une adresse email valide
    // ou un numéro composé de chiffres (empêche par exemple un lien "javascript:").
    if ($siteWeb !== '' && !preg_match('#^[a-z][a-z0-9+.\-]*:#i', $siteWeb)) {
        $siteWeb = 'https://' . $siteWeb;
    }
    $siteWebLien = ($siteWeb !== '' && preg_match('#^https?://#i', $siteWeb) && filter_var($siteWeb, FILTER_VALIDATE_URL))
        ? $siteWeb : null;
    $siteWebTexte = rtrim(preg_replace('#^https?://(www\.)?#i', '', $siteWeb), '/');
    $emailLien = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    $telLien = preg_replace('/[^0-9+]/', '', $telephone);

    $latitudeStructure = $structure['latitude'] ?? null;
    $longitudeStructure = $structure['longitude'] ?? null;
    $coordonneesValides = is_numeric($latitudeStructure)
        && is_numeric($longitudeStructure)
        && (float) $latitudeStructure >= -90
        && (float) $latitudeStructure <= 90
        && (float) $longitudeStructure >= -180
        && (float) $longitudeStructure <= 180;
}

require '../../includes/header.php';
?>

<?php if ($structure): ?>

<style>
/* Fiche structure : les composants communs sont dans publique/css/portail.css */
.fic-hero { padding-bottom: clamp(3rem, 6vw, 4.5rem); }
.fic-hero .acc-bandeau-image { opacity: .7; }
.fic-identite { display: flex; align-items: flex-end; gap: 1.6rem; }
.fic-logo { flex: 0 0 120px; width: 120px; height: 120px; padding: 12px; border-radius: 50%; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
.fic-logo img { width: 100%; height: 100%; object-fit: contain; border-radius: 50%; }
.fic-etiquettes { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
.fic-sigle { padding: .3rem .75rem; border-radius: 2px; background: var(--acc-jaune); color: var(--acc-nuit); font-size: .78rem; font-weight: 800; letter-spacing: .08em; }
.fic-cat { padding: .3rem .75rem; border: 1px solid rgba(255,255,255,.35); border-radius: 2px; color: #fff; font-size: .78rem; font-weight: 600; transition: border-color .2s ease; }
.fic-cat:hover { border-color: #fff; color: #fff; }
.fic-hero h1 { max-width: 24ch; }
.fic-directeur { margin-top: 1rem !important; }
.fic-directeur strong { color: #fff; }

/* Coordonnées : bande blanche sous l'en-tête */
.fic-coordonnees { border-bottom: 1px solid var(--acc-ligne); background: #fff; }
.fic-coordonnees dl { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 0; }
.fic-coordonnees dl > div { display: flex; gap: .85rem; padding: 1.5rem 1.2rem 1.5rem 0; }
.fic-coordonnees dl > div + div { padding-left: 1.2rem; border-left: 1px solid var(--acc-ligne); }
.fic-coord-icone { display: flex; flex: 0 0 40px; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #E3F5EC; color: var(--acc-vert-fonce); }
.fic-coordonnees dt { margin-bottom: .2rem; color: var(--acc-gris); font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
.fic-coordonnees dd { margin: 0; color: var(--acc-encre); font-size: .92rem; font-weight: 600; line-height: 1.45; overflow-wrap: anywhere; }
.fic-coordonnees dd a { color: var(--acc-bleu-fonce); }
.fic-coordonnees dd a:hover { color: var(--acc-vert-fonce); }
.fic-vide { color: var(--acc-gris); font-weight: 400; font-style: italic; }

/* Corps : contenu + colonne latérale */
.fic-corps { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: clamp(2rem, 5vw, 4rem); align-items: start; }
.fic-bloc + .fic-bloc { margin-top: clamp(2.5rem, 5vw, 3.5rem); }
.fic-bloc > h2 { margin: 0 0 1.2rem; padding-bottom: .7rem; border-bottom: 2px solid var(--acc-encre); color: var(--acc-encre); font-family: var(--font-title); font-size: 1.6rem; font-weight: 600; }
.fic-bloc { scroll-margin-top: 100px; }
.fic-texte { color: var(--acc-encre); font-size: 1.05rem; line-height: 1.8; text-align: justify; hyphens: auto; overflow-wrap: anywhere; }
.fic-texte p { margin: 0 0 1rem; }
.fic-texte p:last-child { margin-bottom: 0; }

/* Historique : repères (date de création, texte fondateur) puis récit */
.fic-reperes { display: flex; flex-wrap: wrap; gap: 1rem 2.5rem; margin: 0 0 1.4rem; padding: 1.1rem 1.3rem; border-left: 4px solid var(--acc-jaune); background: var(--acc-fond); }
.fic-reperes dt { color: var(--acc-gris); font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
.fic-reperes dd { margin: .2rem 0 0; color: var(--acc-encre); font-size: .98rem; font-weight: 700; }

/* Composition */
.fic-membres { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin: 0; padding: 0; list-style: none; }
.fic-membres li { display: flex; flex-direction: column; justify-content: center; min-height: 64px; padding: .8rem 1rem; border: 1px solid var(--acc-ligne); border-left: 3px solid var(--acc-vert); border-radius: 4px; background: #fff; overflow-wrap: anywhere; }
.fic-membres .role { color: var(--acc-vert-fonce); font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
.fic-membres .nom { color: var(--acc-encre); font-size: .95rem; font-weight: 600; line-height: 1.4; }

/* Dirigeant(e) */
.fic-carte.fic-dirigeant { padding: 0; overflow: hidden; }
.fic-dirigeant-tete { display: flex; align-items: center; gap: 1rem; padding: 1.5rem; }
.fic-dirigeant-photo { flex: 0 0 84px; width: 84px; height: 84px; border-radius: 50%; object-fit: cover; object-position: top; box-shadow: 0 0 0 3px #fff, 0 0 0 5px var(--acc-jaune); }
.fic-dirigeant-initiales { display: flex; flex: 0 0 84px; align-items: center; justify-content: center; width: 84px; height: 84px; border-radius: 50%; background: var(--acc-nuit); color: #fff; font-family: var(--font-title); font-size: 1.7rem; font-weight: 600; box-shadow: 0 0 0 3px #fff, 0 0 0 5px var(--acc-jaune); }
.fic-dirigeant-fonction { display: block; margin-bottom: .2rem; color: var(--acc-vert-fonce); font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.fic-dirigeant .fic-dirigeant-nom { margin: 0; color: var(--acc-encre); font-family: var(--font-title); font-size: 1.2rem; font-weight: 600; line-height: 1.3; overflow-wrap: anywhere; }
.fic-dirigeant-depuis { padding: .75rem 1.5rem; border-top: 1px solid var(--acc-ligne); background: var(--acc-fond); color: var(--acc-gris); font-size: .82rem; }
.fic-dirigeant-depuis strong { color: var(--acc-encre); }

/* Sommaire */
.fic-sommaire { margin: 0; padding: 0; list-style: none; }
.fic-sommaire a { display: block; padding: .5rem .75rem; border-left: 2px solid var(--acc-ligne); color: var(--acc-encre); font-size: .88rem; font-weight: 600; transition: border-color .2s ease, color .2s ease; }
.fic-sommaire a:hover { border-left-color: var(--acc-vert); color: var(--acc-vert-fonce); }
.fic-galerie { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.fic-galerie img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 4px; }
.fic-actus { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem; }
.fic-actus .acc-actu { border: 1px solid var(--acc-ligne); }

.fic-lateral { position: sticky; top: 100px; display: grid; gap: 1.5rem; }
.fic-carte { padding: 1.5rem; border: 1px solid var(--acc-ligne); border-radius: 4px; background: #fff; }
.fic-carte h2 { margin: 0 0 1rem; color: var(--acc-encre); font-size: .8rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.fic-carte p { margin: 0; color: var(--acc-gris); font-size: .88rem; line-height: 1.55; }
.fic-documents { margin: 0; padding: 0; list-style: none; }
.fic-documents li + li { border-top: 1px solid var(--acc-ligne); }
.fic-documents a { display: flex; align-items: center; gap: .75rem; padding: .8rem 0; color: var(--acc-encre); font-size: .88rem; font-weight: 600; line-height: 1.4; }
.fic-documents a:hover { color: var(--acc-bleu-fonce); }
.fic-documents svg { flex: 0 0 auto; color: var(--acc-bleu); }
.fic-documents .nom { flex: 1; min-width: 0; overflow-wrap: anywhere; }
.fic-statut { flex: 0 0 auto; padding: .15rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 800; }
.fic-statut.public { background: #E3F5EC; color: var(--acc-vert-fonce); }
.fic-statut.confidentiel { background: #FDECEC; color: #B42318; }
.fic-localisation { border: 0; background: var(--acc-nuit); }
.fic-localisation h2 { color: #fff; }
.fic-localisation p { color: rgba(255,255,255,.75); }
.fic-coords-gps { display: block; margin-top: .6rem; color: rgba(255,255,255,.55); font-family: ui-monospace, monospace; font-size: .78rem; }
.fic-bouton-carte { display: inline-flex; align-items: center; gap: .5rem; margin-top: 1.1rem; padding: .7rem 1.1rem; border-radius: 4px; background: var(--acc-jaune); color: var(--acc-nuit); font-size: .88rem; font-weight: 800; transition: background .2s ease; }
.fic-bouton-carte:hover { background: #fff; color: var(--acc-nuit); }

@media (max-width: 991.98px) {
  .fic-coordonnees dl { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .fic-coordonnees dl > div:nth-child(3) { padding-left: 0; border-left: 0; }
  .fic-coordonnees dl > div:nth-child(n+3) { border-top: 1px solid var(--acc-ligne); }
  .fic-corps { grid-template-columns: 1fr; }
  .fic-lateral { position: static; }
}
@media (max-width: 575.98px) {
  .fic-identite { flex-direction: column; align-items: flex-start; gap: 1.2rem; }
  .fic-logo { flex-basis: 88px; width: 88px; height: 88px; padding: 8px; }
  .fic-coordonnees dl { grid-template-columns: 1fr; }
  .fic-coordonnees dl > div, .fic-coordonnees dl > div + div { padding: 1.1rem 0; border-left: 0; }
  .fic-coordonnees dl > div + div { border-top: 1px solid var(--acc-ligne); }
  .fic-actus { grid-template-columns: 1fr; }
  .fic-membres { grid-template-columns: 1fr; }
  .fic-texte { text-align: left; }
}
</style>

<!-- =========================================================
     EN-TÊTE : photo de la structure en fond + identité
     ========================================================= -->
<header class="acc-bandeau fic-hero">
  <img class="acc-bandeau-image" src="<?= $h($fondHero) ?>" alt="">
  <div class="container">
    <ol class="acc-fil" aria-label="Fil d'Ariane">
      <li><a href="accueil.php">Accueil</a></li>
      <li><a href="catalogue.php">Structures</a></li>
      <li><a href="catalogue.php?cat=<?= (int) $structure['id_cat'] ?>"><?= $h($nomCategorie) ?></a></li>
      <li aria-current="page"><?= $h($structure['sigle'] ?: $nomStructure) ?></li>
    </ol>

    <div class="fic-identite">
      <?php if ($logoStructure): ?>
        <div class="fic-logo"><img src="<?= $h($logoStructure) ?>" alt="Logo de <?= $h($nomStructure) ?>"></div>
      <?php endif; ?>
      <div>
        <div class="fic-etiquettes">
          <?php if (!empty($structure['sigle'])): ?>
            <span class="fic-sigle"><?= $h($structure['sigle']) ?></span>
          <?php endif; ?>
          <a class="fic-cat" href="catalogue.php?cat=<?= (int) $structure['id_cat'] ?>"><?= $h($nomCategorie) ?></a>
        </div>
        <h1><?= $h($nomStructure) ?></h1>
        <?php if ($directeur !== ''): ?>
          <p class="fic-directeur"><?= $h($fonctionDirigeant) ?>&nbsp;: <strong><?= $h($directeur) ?></strong></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<!-- Coordonnées -->
<section class="fic-coordonnees" aria-label="Coordonnées">
  <div class="container">
    <dl>
      <div>
        <span class="fic-coord-icone" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
        <div>
          <dt>Adresse</dt>
          <dd><?= $adresse !== '' ? $h($adresse) : '<span class="fic-vide">Non renseignée</span>' ?></dd>
        </div>
      </div>
      <div>
        <span class="fic-coord-icone" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg></span>
        <div>
          <dt>Téléphone</dt>
          <dd>
            <?php if ($telephone !== '' && $telLien !== ''): ?>
              <a href="tel:<?= $h($telLien) ?>"><?= $h($telephone) ?></a>
            <?php else: ?>
              <span class="fic-vide">Non renseigné</span>
            <?php endif; ?>
          </dd>
        </div>
      </div>
      <div>
        <span class="fic-coord-icone" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg></span>
        <div>
          <dt>Email</dt>
          <dd>
            <?php if ($emailLien): ?>
              <a href="mailto:<?= $h($emailLien) ?>"><?= $h($emailLien) ?></a>
            <?php elseif ($email !== ''): ?>
              <?= $h($email) ?>
            <?php else: ?>
              <span class="fic-vide">Non renseigné</span>
            <?php endif; ?>
          </dd>
        </div>
      </div>
      <div>
        <span class="fic-coord-icone" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
        <div>
          <dt>Site web</dt>
          <dd>
            <?php if ($siteWebLien): ?>
              <a href="<?= $h($siteWebLien) ?>" target="_blank" rel="noopener noreferrer"><?= $h($siteWebTexte) ?> ↗</a>
            <?php elseif ($siteWeb !== ''): ?>
              <?= $h($siteWeb) ?>
            <?php else: ?>
              <span class="fic-vide">Non renseigné</span>
            <?php endif; ?>
          </dd>
        </div>
      </div>
    </dl>
  </div>
</section>

<!-- =========================================================
     CORPS : contenu principal + colonne latérale
     ========================================================= -->
<main class="acc-section">
  <div class="container fic-corps">

    <div>
      <section class="fic-bloc" id="role" aria-labelledby="fic-role-titre">
        <h2 id="fic-role-titre">Rôle et missions</h2>
        <?php if ($mission !== ''): ?>
          <div class="fic-texte"><?= $paragraphes($mission) ?></div>
        <?php else: ?>
          <p class="fic-vide">Le rôle de cette structure n'a pas encore été renseigné.</p>
        <?php endif; ?>
      </section>

      <?php if (isset($sommaire['historique'])): ?>
        <section class="fic-bloc" id="historique" aria-labelledby="fic-historique-titre">
          <h2 id="fic-historique-titre">Historique</h2>
          <?php if ($dateCreation || $texteFondateur !== ''): ?>
            <dl class="fic-reperes">
              <?php if ($dateCreation): ?>
                <div><dt>Création</dt><dd><?= $h($dateCreation) ?></dd></div>
              <?php endif; ?>
              <?php if ($texteFondateur !== ''): ?>
                <div><dt>Texte fondateur</dt><dd><?= $h($texteFondateur) ?></dd></div>
              <?php endif; ?>
            </dl>
          <?php endif; ?>
          <?php if ($historique !== ''): ?>
            <div class="fic-texte"><?= $paragraphes($historique) ?></div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if ($membres): ?>
        <section class="fic-bloc" id="composition" aria-labelledby="fic-composition-titre">
          <h2 id="fic-composition-titre">Composition</h2>
          <ul class="fic-membres">
            <?php foreach ($membres as $membre): ?>
              <li>
                <?php if ($membre['role'] !== null): ?><span class="role"><?= $h($membre['role']) ?></span><?php endif; ?>
                <span class="nom"><?= $h($membre['nom']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <?php if (!empty($galerie)): ?>
        <section class="fic-bloc" aria-labelledby="fic-photos-titre">
          <h2 id="fic-photos-titre">Photos</h2>
          <div class="fic-galerie">
            <?php foreach ($galerie as $photo): ?>
              <img src="../../uploads/photos/<?= $h($photo['Photos_url']) ?>" alt="Photo de <?= $h($nomStructure) ?>" loading="lazy">
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <section class="fic-bloc" id="actualites" aria-labelledby="fic-actu-titre">
        <h2 id="fic-actu-titre">Actualités</h2>
        <?php if (empty($actualites)): ?>
          <p class="fic-vide">Aucune actualité publiée pour le moment.</p>
        <?php else: ?>
          <div class="fic-actus">
            <?php foreach ($actualites as $actualite): ?>
              <a href="actualite_detail.php?id=<?= (int) $actualite['id_actu'] ?>" class="acc-actu">
                <div class="acc-actu-media">
                  <img src="<?= !empty($actualite['photo']) ? '../../uploads/photos/' . $h($actualite['photo']) : '../../publique/image/GA.jpeg' ?>" alt="" loading="lazy">
                </div>
                <div class="acc-actu-corps">
                  <div class="acc-actu-meta">
                    <time class="acc-actu-date" datetime="<?= $h(date('Y-m-d', strtotime($actualite['date_publication']) ?: time())) ?>"><?= $h(formaterDateFr($actualite['date_publication'])) ?></time>
                  </div>
                  <h3><?= $h($actualite['titre']) ?></h3>
                  <span class="acc-fleche">Lire la suite</span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>

    <aside class="fic-lateral" aria-label="Informations complémentaires">
      <?php if ($directeur !== ''): ?>
        <section class="fic-carte fic-dirigeant" aria-labelledby="fic-dirigeant-titre">
          <h2 id="fic-dirigeant-titre" class="visually-hidden">Dirigeant(e) actuel(le)</h2>
          <div class="fic-dirigeant-tete">
            <?php if ($photoDirigeant): ?>
              <img class="fic-dirigeant-photo" src="../../uploads/photos/<?= $h($photoDirigeant) ?>" alt="<?= $h($directeur) ?>">
            <?php else: ?>
              <span class="fic-dirigeant-initiales" aria-hidden="true"><?= $h($initialesDirigeant) ?></span>
            <?php endif; ?>
            <div>
              <span class="fic-dirigeant-fonction"><?= $h($fonctionDirigeant) ?></span>
              <p class="fic-dirigeant-nom"><?= $h($directeur) ?></p>
            </div>
          </div>
          <?php if ($dirigeantDepuis): ?>
            <div class="fic-dirigeant-depuis">En fonction depuis le <strong><?= $h($dirigeantDepuis) ?></strong></div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <nav class="fic-carte" aria-labelledby="fic-sommaire-titre">
        <h2 id="fic-sommaire-titre">Sur cette page</h2>
        <ul class="fic-sommaire">
          <?php foreach ($sommaire as $ancre => $libelle): ?>
            <li><a href="#<?= $h($ancre) ?>"><?= $h($libelle) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <section class="fic-carte" aria-labelledby="fic-doc-titre">
        <h2 id="fic-doc-titre">Documents<?= $estAgent ? '' : ' publics' ?></h2>
        <?php if ($documents): ?>
          <ul class="fic-documents">
            <?php foreach ($documents as $document): ?>
              <li>
                <a href="document.php?id=<?= (int) $document['id_document'] ?>">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>
                  <span class="nom"><?= $h($document['nom_affichage']) ?></span>
                  <?php if ($estAgent): ?>
                    <span class="fic-statut <?= $document['est_public'] ? 'public' : 'confidentiel' ?>"><?= $document['est_public'] ? 'Public' : 'Confidentiel' ?></span>
                  <?php endif; ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="fic-vide">Aucun document public disponible.</p>
        <?php endif; ?>
      </section>

      <section class="fic-carte fic-localisation" aria-labelledby="fic-localisation-titre">
        <h2 id="fic-localisation-titre">Localisation</h2>
        <?php if ($coordonneesValides): ?>
          <p>Consultez la position de cette structure sur la carte interactive de Libreville.</p>
          <span class="fic-coords-gps"><?= number_format((float) $latitudeStructure, 5) ?>, <?= number_format((float) $longitudeStructure, 5) ?></span>
          <a class="fic-bouton-carte" href="localisation.php?id=<?= (int) $structure['id_struc'] ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Afficher sur la carte
          </a>
        <?php else: ?>
          <p>La position géographique de cette structure n'a pas encore été renseignée.</p>
        <?php endif; ?>
      </section>
    </aside>

  </div>
</main>

<?php else: ?>
  <main class="acc-section">
    <div class="container">
      <div class="acc-vide">
        <h2>Structure introuvable</h2>
        <p>Cette structure n'existe pas ou a été retirée. <a href="catalogue.php">Retour au catalogue</a></p>
      </div>
    </div>
  </main>
<?php endif; ?>

<?php require '../../includes/footer.php'; ?>
