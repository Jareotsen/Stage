<?php 
session_start();
require '../../config/database.php';

$consultationStmt = $db->query(
    "SELECT consultations.id_consultation, consultations.titre, consultations.contenu, consultations.date_ouverture, structure.nom_struc
     FROM consultations
     JOIN structure ON consultations.id_structure = structure.id_struc
     ORDER BY consultations.date_ouverture DESC"
);
$consultations = $consultationStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($consultations as &$consultation) {
    $photoStmt = $db->prepare(
        "SELECT Photos_consultation_url FROM consultations_photos WHERE id_consultation = :id ORDER BY Position LIMIT 1"
    );
    $photoStmt->execute([':id' => $consultation['id_consultation']]);
    $consultation['image'] = $photoStmt->fetchColumn(); // false si aucune photo
}
unset($consultation);

function formaterDateFr(string $dateSql): string {
    $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $timestamp = strtotime($dateSql);
    return date('d', $timestamp) . ' ' . $mois[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

require '../../includes/header.php';
?>
<style>
    .consultation-page { max-width: 1180px; margin: 0 auto; padding: 48px 24px 70px; color: #263746; font-family: Arial, sans-serif; }
    .consultation-page .intro { border-left: 5px solid #000000; padding-left: 18px; margin-bottom: 38px; }
    .consultation-page h1 { margin: 0 0 10px; color: #030303; font-size: 2.5rem; }
    .consultation-page .intro p { margin: 0; color: #131618; font-size: 1.05rem; }
    .consultation-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 26px; }
    .consultation-card { overflow: hidden; background: #fff; border: 1px solid #e5e9eb; border-radius: 3px; box-shadow: 0 3px 12px rgba(30,50,60,.08); }
    .consultation-card img { display: block; width: 100%; height: 190px; object-fit: cover; }
    .consultation-content { padding: 22px; }
    .consultation-meta { color: #000000; font-size: .82rem; font-weight: bold; text-transform: uppercase; letter-spacing: .04em; }
    .consultation-card h2 { margin: 10px 0; color: #141515; font-size: 1.25rem; line-height: 1.3; }
    .consultation-card p { margin: 0 0 20px; color: #121516; line-height: 1.55; }
    .consultation-link { color: #000000; font-weight: bold; text-decoration: none; }
    .consultation-link:hover { color: #080807; }
    @media (max-width: 760px) { .consultation-grid { grid-template-columns: 1fr; } .consultation-page h1 { font-size: 2rem; } }
</style>
<main class="consultation-page">
    <header class="intro">
        <h1>Consultations</h1>
        <p>Découvrez les dernières consultations publiées par les structures sous tutelle du MRRI.</p>
    </header>

    <?php if (empty($consultations)): ?>
        <p>Aucune consultation publiée pour le moment.</p>
    <?php else: ?>
        <section class="consultation-grid" aria-label="Liste des consultations">
            <?php foreach ($consultations as $consultation): ?>
                <article class="consultation-card">
                    <div class="consultation-content">
                        <h2><?= htmlspecialchars($consultation['titre']) ?></h2>
                        <p><?= htmlspecialchars($consultation['contenu']) ?></p>
                        <p class="consultation-meta">Ouverte le <?= formaterDateFr($consultation['date_ouverture']) ?> par <?= htmlspecialchars($consultation['nom_struc']) ?></p>
                        <a href="details_consultation.php?id=<?= $consultation['id_consultation'] ?>" class="consultation-link">Voir les détails</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php require '../../includes/footer.php'; ?>