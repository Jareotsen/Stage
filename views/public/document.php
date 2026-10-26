<?php
session_start();
require '../../config/database.php';
require_once '../../includes/documents.php';

$h = static function ($valeur): string {
    return htmlspecialchars((string) ($valeur ?? ''), ENT_QUOTES, 'UTF-8');
};

// Mêmes contrôles que telecharger.php : document existant, accès autorisé, fichier présent
$document = document_charger($db, $_GET['id'] ?? null);
$chemin = null;

if ($document && document_accessible($db, $document)) {
    $chemin = document_chemin($document);
}

// Refus : même message que le document existe ou non
if ($chemin === null) {
    $document = null;
    http_response_code(404);
}

if ($document) {
    $extension = document_extension($document);
    $estPublic = ((int) $document['est_public'] === 1);
    $titre = trim($document['nom_affichage'] ?? '') ?: 'Document sans titre';
    $lienTelecharger = '../telecharger.php?id_document=' . (int) $document['id_document'];

    $taille = filesize($chemin);
    $tailleTexte = $taille >= 1048576
        ? number_format($taille / 1048576, 1, ',', ' ') . ' Mo'
        : max(1, (int) round($taille / 1024)) . ' Ko';

    $libellesTypes = [
        'pdf'  => 'PDF',
        'doc'  => 'Word 97-2003',
        'docx' => 'Word',
        'txt'  => 'Texte',
    ];

    // Structure propriétaire, pour le fil d'Ariane
    $structureStmt = $db->prepare("SELECT id_struc, nom_struc FROM structure WHERE id_struc = :id");
    $structureStmt->execute([':id' => $document['id_structure']]);
    $structure = $structureStmt->fetch(PDO::FETCH_ASSOC);

    // Contenu de l'aperçu selon le type de fichier
    $texte = null;
    $paragraphes = null;
    if ($extension === 'txt') {
        $texte = document_lire_texte($chemin);
    } elseif ($extension === 'docx') {
        $paragraphes = document_extraire_docx($chemin);
    }
}

require '../../includes/header.php';
?>

<?php if ($document): ?>

<!-- =========================================================
     EN-TÊTE : titre du document + actions
     ========================================================= -->
<section class="fiche-hero document-hero">
    <div class="container">

        <nav class="fiche-fil" aria-label="Fil d'Ariane">
            <a href="catalogue.php">Structures</a>
            <?php if ($structure): ?>
                <span aria-hidden="true">/</span>
                <a href="fiche.php?id=<?= (int) $structure['id_struc'] ?>"><?= $h($structure['nom_struc']) ?></a>
            <?php endif; ?>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Document</span>
        </nav>

        <div class="fiche-etiquettes">
            <span class="fiche-etiquette fiche-etiquette-sigle"><?= $h($libellesTypes[$extension]) ?></span>
            <span class="fiche-etiquette"><?= $h($tailleTexte) ?></span>
            <span class="fiche-etiquette">
                <i class="bi <?= $estPublic ? 'bi-globe2' : 'bi-lock' ?>" aria-hidden="true"></i>
                <?= $estPublic ? 'Public' : 'Confidentiel' ?>
            </span>
        </div>

        <h1><?= $h($titre) ?></h1>

        <div class="document-actions">
            <a class="btn btn-mrri-accent" href="<?= $h($lienTelecharger) ?>">
                <i class="bi bi-download" aria-hidden="true"></i> Télécharger
            </a>
            <?php if ($structure): ?>
                <a class="document-retour" href="fiche.php?id=<?= (int) $structure['id_struc'] ?>">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour à la fiche
                </a>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- =========================================================
     APERÇU DU CONTENU
     ========================================================= -->
<main class="container document-page">

    <?php if ($extension === 'pdf'): ?>

        <iframe
            class="document-apercu document-apercu-pdf"
            src="<?= $h($lienTelecharger) ?>&amp;apercu=1"
            title="Aperçu : <?= $h($titre) ?>"
        ></iframe>
        <p class="document-note">
            L'aperçu ne s'affiche pas ?
            <a href="<?= $h($lienTelecharger) ?>">Téléchargez le document</a>.
        </p>

    <?php elseif ($texte !== null): ?>

        <pre class="document-apercu document-apercu-texte"><?= $h($texte) ?></pre>

    <?php elseif ($paragraphes !== null): ?>

        <article class="document-apercu document-apercu-word">
            <?php foreach ($paragraphes as $paragraphe): ?>
                <?php if (trim($paragraphe['texte']) === ''): ?>
                    <br>
                <?php elseif ($paragraphe['titre']): ?>
                    <h2><?= nl2br($h($paragraphe['texte'])) ?></h2>
                <?php else: ?>
                    <p><?= nl2br($h($paragraphe['texte'])) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
        </article>
        <p class="document-note">
            Aperçu du texte uniquement : la mise en forme, les images et les tableaux
            sont visibles dans le document téléchargé.
        </p>

    <?php else: ?>

        <div class="document-apercu document-indisponible">
            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
            <p>L'aperçu n'est pas disponible pour ce document.</p>
            <a class="btn btn-mrri" href="<?= $h($lienTelecharger) ?>">
                <i class="bi bi-download" aria-hidden="true"></i> Télécharger le document
            </a>
        </div>

    <?php endif; ?>

</main>

<?php else: ?>

<main class="container document-page">
    <div class="document-apercu document-indisponible">
        <i class="bi bi-file-earmark-x" aria-hidden="true"></i>
        <p>Document introuvable.</p>
        <a class="btn btn-mrri" href="catalogue.php">Retour aux structures</a>
    </div>
</main>

<?php endif; ?>

<?php require '../../includes/footer.php'; ?>
