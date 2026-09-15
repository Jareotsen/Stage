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

<style>
	.actualite-detail-page main { max-width: 850px; margin: 40px auto; padding: 0 20px; }
	.actualite-detail-page article { overflow: hidden; background: #fff; border-radius: 8px; box-shadow: 0 2px 12px #00000012; }
	.actualite-detail-page .contenu { padding: 28px; }
	.actualite-detail-page h1 { margin: 0 0 10px; font-size: 2rem; }
	.actualite-detail-page .meta { color: #68717d; font-size: .9rem; }
	.actualite-detail-page img { display: block; width: 100%; max-height: 400px; object-fit: cover; }
	.actualite-detail-page .texte { white-space: pre-line; margin-top: 25px; }
	.actualite-detail-page .retour { display: inline-block; margin-bottom: 20px; color: #2457a6; text-decoration: none; }
	.actualite-detail-page .erreur { padding: 35px; text-align: center; }
</style>

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