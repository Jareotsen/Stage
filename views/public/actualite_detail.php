<?php
declare(strict_types=1);

session_start();
require '../../config/database.php';

/* Récupération de l'actualité en base à partir de l'id passé en URL */
$id = $_GET['id'] ?? '';
$actualite = null;

if ($id !== '' && ctype_digit((string) $id)) {
    $stmt = $db->prepare(
        "SELECT actualites.titre, actualites.contenu, actualites.date_publication, structure.nom_struc
         FROM actualites
         JOIN structure ON actualites.id_structure = structure.id_struc
         WHERE actualites.id_actu = :id"
    );
    $stmt->execute([':id' => $id]);
    $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ligne) {
        $photoStmt = $db->prepare(
            "SELECT Photos_actu_url FROM actualites_photos WHERE id_actualites = :id ORDER BY Position LIMIT 1"
        );
        $photoStmt->execute([':id' => $id]);
        $urlPhoto = $photoStmt->fetchColumn();

        $actualite = [
            'titre' => $ligne['titre'],
            'contenu' => $ligne['contenu'],
            'image' => $urlPhoto ? '../../uploads/photos/' . $urlPhoto : '',
            'date_publication' => $ligne['date_publication'],
            'auteur' => $ligne['nom_struc'],
        ];
    }
}

$get = static function (string $key, string $default = '') use ($actualite): string {
	if (is_array($actualite) && isset($actualite[$key])) {
		return (string) $actualite[$key];
	}
	if (is_object($actualite) && isset($actualite->{$key})) {
		return (string) $actualite->{$key};
	}
	return $default;
};

$titre = $get('titre', $get('title', 'Actualité introuvable'));
$contenu = $get('contenu', $get('content', ''));
$image = $get('image', $get('image_url', ''));
$date = $get('date_publication', $get('date', ''));
$auteur = $get('auteur', $get('author', ''));

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

require '../../includes/header.php';
?>

<link rel="stylesheet" href="../../publique/css/theme.css">

<div class="actualite-detail-page">
	<main>
		<a class="retour" href="javascript:history.back()">← Retour aux actualités</a>
		<article>
			<?php if ($actualite && $image !== ''): ?>
				<img src="<?= $escape($image) ?>" alt="">
			<?php endif; ?>
			<div class="contenu">
				<?php if (!$actualite): ?>
					<div class="erreur"><h1>Actualité introuvable</h1><p>Cette actualité n'existe plus ou n'est pas disponible.</p></div>
				<?php else: ?>
					<h1><?= $escape($titre) ?></h1>
					<?php if ($date !== '' || $auteur !== ''): ?>
						<div class="meta">
							<?= $date !== '' ? $escape($date) : '' ?>
							<?= $date !== '' && $auteur !== '' ? ' · ' : '' ?>
							<?= $auteur !== '' ? 'Par ' . $escape($auteur) : '' ?>
						</div>
					<?php endif; ?>
					<div class="texte"><?= nl2br($escape($contenu)) ?></div>
				<?php endif; ?>
			</div>
		</article>
	</main>
</div>

<?php require '../../includes/footer.php'; ?>