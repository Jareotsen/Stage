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

<style>
	.actualite-page { max-width: 1180px; margin: 0 auto; padding: 48px 24px 70px; color: #263746; font-family: Arial, sans-serif; }
	.actualite-page .intro { border-left: 5px solid #000000; padding-left: 18px; margin-bottom: 38px; }
	.actualite-page h1 { margin: 0 0 10px; color: #030303; font-size: 2.5rem; }
	.actualite-page .intro p { margin: 0; color: #131618; font-size: 1.05rem; }
	.actualite-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 26px; }
	.actualite-card { overflow: hidden; background: #fff; border: 1px solid #e5e9eb; border-radius: 3px; box-shadow: 0 3px 12px rgba(30,50,60,.08); }
	.actualite-card img { display: block; width: 100%; height: 190px; object-fit: cover; }
	.actualite-content { padding: 22px; }
	.actualite-meta { color: #000000; font-size: .82rem; font-weight: bold; text-transform: uppercase; letter-spacing: .04em; }
	.actualite-card h2 { margin: 10px 0; color: #141515; font-size: 1.25rem; line-height: 1.3; }
	.actualite-card p { margin: 0 0 20px; color: #121516; line-height: 1.55; }
	.actualite-link { color: #000000; font-weight: bold; text-decoration: none; }
	.actualite-link:hover { color: #080807; }
	@media (max-width: 760px) { .actualite-grid { grid-template-columns: 1fr; } .actualite-page h1 { font-size: 2rem; } }
</style>

<main class="actualite-page">
	<header class="intro">
		<h1>Actualités</h1>
		<p>Découvrez les dernières nouvelles publiées par les structures sous tutelle du MRRI.</p>
	</header>

	<?php if (empty($actualites)): ?>
		<p>Aucune actualité publiée pour le moment.</p>
	<?php else: ?>
		<section class="actualite-grid" aria-label="Liste des actualités">
			<?php foreach ($actualites as $actualite): ?>
				<article class="actualite-card">
					<?php if ($actualite['image']): ?>
						<img src="../../uploads/photos/<?= htmlspecialchars($actualite['image'], ENT_QUOTES, 'UTF-8') ?>" alt="">
					<?php else: ?>
						<div style="width:100%; height:190px; background:#eef1f2; display:flex; align-items:center; justify-content:center; color:#9aa6ac; font-size:.85rem;">Pas de photo</div>
					<?php endif; ?>
					<div class="actualite-content">
						<div class="actualite-meta"><?= htmlspecialchars($actualite['nom_struc'], ENT_QUOTES, 'UTF-8') ?> · <?= formaterDateFr($actualite['date_publication']) ?></div>
						<h2><?= htmlspecialchars($actualite['titre'], ENT_QUOTES, 'UTF-8') ?></h2>
						<p><?= htmlspecialchars(mb_strimwidth($actualite['contenu'], 0, 140, '…'), ENT_QUOTES, 'UTF-8') ?></p>
						<a class="actualite-link" href="actualite_detail.php?id=<?= $actualite['id_actu'] ?>">Lire la suite&nbsp; →</a>
					</div>
				</article>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>
</main>

<?php require '../../includes/footer.php'; ?>