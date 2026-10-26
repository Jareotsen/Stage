<?php 
session_start();
require '../../config/database.php';
require_once '../../includes/dates.php';

if (!isset($_GET['id'])) {
    die("Aucune structure sélectionnée.");
}
$id = $_GET['id'];

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

    $actualitesStmt = $db->prepare("SELECT * FROM actualites WHERE id_structure = :id ORDER BY date_publication DESC");
    $actualitesStmt->execute([':id' => $id]);
    $actualites = $actualitesStmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------- Préparation de l'affichage (aucune requête supplémentaire) ---------- */
    $h = static function ($valeur): string {
        return htmlspecialchars((string) ($valeur ?? ''), ENT_QUOTES, 'UTF-8');
    };

    $nomStructure = trim($structure['nom_struc'] ?? '') ?: 'Structure sans nom';
    $photoPrincipale = !empty($photosStructure) ? $photosStructure[0]['Photos_url'] : null;
    $autresPhotos = count($photosStructure) > 1 ? $photosStructure : [];

    $adresse   = trim($structure['adresse'] ?? '');
    $telephone = trim($structure['telephone'] ?? '');
    $email     = trim($structure['email'] ?? '');
    $siteWeb   = trim($structure['site_web'] ?? '');
    $directeur = trim($structure['directeur_general'] ?? '');
    $mission   = trim($structure['mission'] ?? '');

    // Liens sécurisés : on n'affiche un lien que pour un schéma http(s), une adresse email valide
    // ou un numéro composé de chiffres (empêche par exemple un lien "javascript:").
    if ($siteWeb !== '' && !preg_match('#^[a-z][a-z0-9+.\-]*:#i', $siteWeb)) {
        $siteWeb = 'https://' . $siteWeb;
    }
    $siteWebLien = ($siteWeb !== '' && preg_match('#^https?://#i', $siteWeb) && filter_var($siteWeb, FILTER_VALIDATE_URL))
        ? $siteWeb : null;
    $siteWebTexte = preg_replace('#^https?://#i', '', $siteWeb);
    $emailLien = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    $telLien = preg_replace('/[^0-9+]/', '', $telephone);
}

require '../../includes/header.php';
?>

<?php if ($structure): ?>

<!-- =========================================================
     EN-TÊTE DE LA FICHE : identité + coordonnées
     ========================================================= -->
<section class="fiche-hero">
    <div class="container">

        <nav class="fiche-fil" aria-label="Fil d'Ariane">
            <a href="catalogue.php">Structures</a>
            <span aria-hidden="true">/</span>
            <a href="catalogue.php?cat=<?= (int) $structure['id_cat'] ?>"><?= $h($structure['nom_cat'] ?? '') ?></a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= $h($nomStructure) ?></span>
        </nav>

        <div class="fiche-hero-grille">
            <div class="fiche-hero-texte">
                <div class="fiche-etiquettes">
                    <?php if (!empty($structure['sigle'])): ?>
                        <span class="fiche-etiquette fiche-etiquette-sigle"><?= $h($structure['sigle']) ?></span>
                    <?php endif; ?>
                    <span class="fiche-etiquette"><?= $h($structure['nom_cat'] ?? 'Catégorie non définie') ?></span>
                </div>

                <h1><?= $h($nomStructure) ?></h1>

                <?php if ($directeur !== ''): ?>
                    <p class="fiche-directeur">Directeur général&nbsp;: <strong><?= $h($directeur) ?></strong></p>
                <?php endif; ?>
            </div>

            <div class="fiche-hero-media">
                <?php if ($photoPrincipale): ?>
                    <img src="../../uploads/photos/<?= $h($photoPrincipale) ?>"
                         alt="Photo de <?= $h($nomStructure) ?>">
                <?php else: ?>
                    <div class="fiche-hero-vide" aria-hidden="true">
                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="21" x2="21" y2="21"/><line x1="5" y1="21" x2="5" y2="10"/><line x1="19" y1="21" x2="19" y2="10"/><line x1="9" y1="21" x2="9" y2="10"/><line x1="15" y1="21" x2="15" y2="10"/><polygon points="12 3 21 9 3 9"/></svg>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coordonnées : une ligne unique, sans carte -->
        <dl class="fiche-coordonnees">
            <div>
                <dt>Adresse</dt>
                <dd><?= $adresse !== '' ? $h($adresse) : '<span class="fiche-vide">Non renseignée</span>' ?></dd>
            </div>
            <div>
                <dt>Téléphone</dt>
                <dd>
                    <?php if ($telephone !== '' && $telLien !== ''): ?>
                        <a href="tel:<?= $h($telLien) ?>"><?= $h($telephone) ?></a>
                    <?php else: ?>
                        <span class="fiche-vide">Non renseigné</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd>
                    <?php if ($emailLien): ?>
                        <a href="mailto:<?= $h($emailLien) ?>"><?= $h($emailLien) ?></a>
                    <?php elseif ($email !== ''): ?>
                        <?= $h($email) ?>
                    <?php else: ?>
                        <span class="fiche-vide">Non renseigné</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Site web</dt>
                <dd>
                    <?php if ($siteWebLien): ?>
                        <a href="<?= $h($siteWebLien) ?>" target="_blank" rel="noopener noreferrer"><?= $h($siteWebTexte) ?></a>
                    <?php elseif ($siteWeb !== ''): ?>
                        <?= $h($siteWeb) ?>
                    <?php else: ?>
                        <span class="fiche-vide">Non renseigné</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>

    </div>
</section>

<!-- =========================================================
     CORPS : contenu principal + colonne latérale
     ========================================================= -->
<div class="container fiche-corps">

    <div class="fiche-principal">

        <section class="fiche-section" aria-labelledby="fiche-mission-titre">
            <h2 id="fiche-mission-titre">Mission</h2>
            <?php if ($mission !== ''): ?>
                <p class="fiche-mission"><?= nl2br($h($mission)) ?></p>
            <?php else: ?>
                <p class="fiche-vide">La mission de cette structure n'a pas encore été renseignée.</p>
            <?php endif; ?>
        </section>

        <?php if (!empty($autresPhotos)): ?>
            <section class="fiche-section" aria-labelledby="fiche-photos-titre">
                <h2 id="fiche-photos-titre">Photos</h2>
                <div class="fiche-galerie">
                    <?php foreach ($autresPhotos as $photo): ?>
                        <img src="../../uploads/photos/<?= $h($photo['Photos_url']) ?>"
                             alt="Photo de <?= $h($nomStructure) ?>" loading="lazy">
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="fiche-section" aria-labelledby="fiche-actu-titre">
            <h2 id="fiche-actu-titre">Actualités</h2>

            <?php if (empty($actualites)): ?>
                <p class="fiche-vide">Aucune actualité publiée pour le moment.</p>
            <?php else: ?>
                <ul class="fiche-actualites">
                    <?php foreach ($actualites as $actualite): ?>
                        <?php
                            $photosActuStmt = $db->prepare("SELECT * FROM actualites_photos WHERE id_actualites = :id ORDER BY Position");
                            $photosActuStmt->execute([':id' => $actualite['id_actu']]);
                            $photosActualite = $photosActuStmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <li class="fiche-actualite">
                            <?php if ($photosActualite): ?>
                                <a class="fiche-actualite-vignette" href="actualite_detail.php?id=<?= (int) $actualite['id_actu'] ?>" tabindex="-1" aria-hidden="true">
                                    <img src="../../uploads/photos/<?= $h($photosActualite[0]['Photos_actu_url']) ?>" alt="" loading="lazy">
                                </a>
                            <?php endif; ?>
                            <div class="fiche-actualite-texte">
                                <time datetime="<?= $h($actualite['date_publication']) ?>"><?= $h(formaterDateFr($actualite['date_publication'])) ?></time>
                                <h3>
                                    <a href="actualite_detail.php?id=<?= (int) $actualite['id_actu'] ?>"><?= $h($actualite['titre']) ?></a>
                                </h3>
                                <p><?= $h(mb_strimwidth($actualite['contenu'] ?? '', 0, 220, '…')) ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

    </div>

    <aside class="fiche-lateral" aria-label="Informations complémentaires">

        <section class="fiche-bloc" aria-labelledby="fiche-doc-titre">
            <h2 id="fiche-doc-titre">Documents<?= $estAgent ? '' : ' publics' ?></h2>

            <?php if ($documents): ?>
                <ul class="fiche-documents">
                    <?php foreach ($documents as $document): ?>
                        <li>
                            <a href="document.php?id=<?= (int) $document['id_document'] ?>">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span><?= $h($document['nom_affichage']) ?></span>
                            </a>
                            <?php if ($estAgent): ?>
                                <span class="fiche-statut <?= $document['est_public'] ? 'public' : 'confidentiel' ?>">
                                    <?= $document['est_public'] ? 'Public' : 'Confidentiel' ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="fiche-vide">Aucun document public disponible.</p>
            <?php endif; ?>
        </section>

        <section class="fiche-section fiche-localisation" aria-labelledby="fiche-localisation-titre">
            <h2 id="fiche-localisation-titre">Localisation</h2>
            <?php
                $latitudeStructure = $structure['latitude'] ?? null;
                $longitudeStructure = $structure['longitude'] ?? null;
                $coordonneesValides = is_numeric($latitudeStructure)
                    && is_numeric($longitudeStructure)
                    && (float) $latitudeStructure >= -90
                    && (float) $latitudeStructure <= 90
                    && (float) $longitudeStructure >= -180
                    && (float) $longitudeStructure <= 180;
            ?>
            <?php if ($coordonneesValides): ?>
                <p>Consultez la position géographique de cette structure sur la carte interactive.</p>
                <a class="localisation-bouton" href="localisation.php?id=<?= (int) $structure['id_struc'] ?>">
                    <i class="bi bi-geo-alt" aria-hidden="true"></i> Afficher sur la carte
                </a>
            <?php else: ?>
                <p class="fiche-vide">La position géographique de cette structure n’a pas encore été renseignée.</p>
            <?php endif; ?>
        </section>

    </aside>

</div>

<?php else: ?>
    <div class="container my-5"><div class="alert alert-light border text-center">Structure introuvable.</div></div>
<?php endif; ?>

<?php require '../../includes/footer.php'; ?>