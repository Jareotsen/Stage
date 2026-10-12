<?php
session_start();
require '../../config/database.php';
require_once '../../includes/dates.php';
require_once '../../includes/consultation_helpers.php';

/* =========================================================
   CONSULTATIONS OUVERTES UNIQUEMENT
   (statut 'ouverte' et date de clôture non dépassée)
   ========================================================= */
$conditionOuverte = "c.statut = 'ouverte' AND (c.date_fermeture IS NULL OR c.date_fermeture >= NOW())";

$parPage = 9;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

$compteStmt = $db->query("SELECT COUNT(*) FROM consultations c WHERE $conditionOuverte");
$totalConsultations = (int) $compteStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalConsultations / $parPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $parPage;

// LEFT JOIN : une consultation générale n'a pas de structure (id_structure NULL)
$consultationStmt = $db->query(
    "SELECT c.id_consultation, c.titre, c.contenu, c.date_ouverture, c.date_fermeture,
            c.id_structure, s.nom_struc
     FROM consultations c
     LEFT JOIN structure s ON c.id_structure = s.id_struc
     WHERE $conditionOuverte
     ORDER BY c.date_ouverture DESC
     LIMIT $parPage OFFSET $offset"
);
$consultations = $consultationStmt->fetchAll(PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>

<style>
    .consultation-page { max-width: 1180px; margin: 0 auto; padding: 48px 24px 70px; color: #263746; }
    .consultation-page .intro { border-left: 5px solid var(--mrri-or, #C9972C); padding-left: 18px; margin-bottom: 24px; }
    .consultation-page h1 { margin: 0 0 10px; font-size: 2.5rem; }
    .consultation-page .intro p { margin: 0; color: #131618; font-size: 1.05rem; }

    .consultation-legende { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 28px; font-size: .8rem; color: #4a5563; }
    .legende-item { display: inline-flex; align-items: center; gap: 6px; }
    .legende-pastille { width: 12px; height: 12px; border-radius: 3px; }
    .legende-pastille.generale { background: var(--mrri-bleu, #1B3FAE); }
    .legende-pastille.structure { background: var(--mrri-vert, #0F8A4B); }

    .consultation-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 26px; }
    .consultation-card { display: flex; flex-direction: column; overflow: hidden; background: #fff; border: 1px solid #e5e9eb; border-radius: 6px; box-shadow: 0 3px 12px rgba(30,50,60,.08); }
    .consultation-card.generale { border-top: 5px solid var(--mrri-bleu, #1B3FAE); }
    .consultation-card.structure { border-top: 5px solid var(--mrri-vert, #0F8A4B); }
    .consultation-content { display: flex; flex-direction: column; flex-grow: 1; padding: 22px; }

    .consultation-badge { align-self: flex-start; padding: 4px 10px; border-radius: 999px; font-size: .7rem; font-weight: 700; margin-bottom: 10px; }
    .consultation-badge.generale { background: #E8EDFB; color: var(--mrri-bleu, #1B3FAE); }
    .consultation-badge.structure { background: #E5F5EC; color: var(--mrri-vert-dark, #0B6B3A); }

    .consultation-card h2 { margin: 0 0 10px; font-size: 1.25rem; line-height: 1.3; }
    .consultation-card p.extrait { margin: 0 0 16px; color: #121516; line-height: 1.55; flex-grow: 1; }
    .consultation-meta { margin: 0 0 4px; color: #4a5563; font-size: .8rem; line-height: 1.5; }
    .consultation-meta strong { color: #141515; }
    .consultation-link { display: inline-block; margin-top: 14px; padding-top: 14px; border-top: 1px solid #e5e9eb; color: var(--mrri-bleu, #1B3FAE); font-weight: 700; font-size: .88rem; }
    .consultation-link:hover { color: var(--mrri-bleu-dark, #142F86); }

    @media (max-width: 960px) { .consultation-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .consultation-grid { grid-template-columns: 1fr; } .consultation-page h1 { font-size: 2rem; } }
</style>

<main class="consultation-page">
    <header class="intro">
        <h1>Consultations</h1>
        <p>Participez aux consultations ouvertes par le Ministère et par les Institutions constitutionnelles ou les A.A.I.</p>
    </header>

    <div class="consultation-legende" aria-label="Légende">
        <span class="legende-item"><span class="legende-pastille generale"></span> Consultation générale</span>
        <span class="legende-item"><span class="legende-pastille structure"></span> Consultation liée à une structure</span>
    </div>

    <?php if (empty($consultations)): ?>
        <p>Aucune consultation ouverte pour le moment.</p>
    <?php else: ?>
        <section class="consultation-grid" aria-label="Liste des consultations ouvertes">
            <?php foreach ($consultations as $consultation): ?>
                <?php $estGenerale = empty($consultation['id_structure']); ?>
                <article class="consultation-card <?= $estGenerale ? 'generale' : 'structure' ?>">
                    <div class="consultation-content">
                        <span class="consultation-badge <?= $estGenerale ? 'generale' : 'structure' ?>">
                            <?= $estGenerale ? 'Consultation générale' : echapper($consultation['nom_struc']) ?>
                        </span>

                        <h2><?= echapper($consultation['titre']) ?></h2>

                        <p class="extrait">
                            <?= echapper(mb_strimwidth($consultation['contenu'] ?? '', 0, 140, '…')) ?>
                        </p>

                        <p class="consultation-meta">
                            Ouverte le <strong><?= formaterDateFr($consultation['date_ouverture']) ?></strong>
                        </p>
                        <?php if (!empty($consultation['date_fermeture'])): ?>
                            <p class="consultation-meta">
                                Jusqu'au <strong><?= formaterDateFr($consultation['date_fermeture']) ?></strong>
                            </p>
                        <?php endif; ?>

                        <a href="consultation_detail.php?id=<?= (int) $consultation['id_consultation'] ?>" class="consultation-link">
                            Participer →
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-5" aria-label="Pagination des consultations">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">Précédent</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Suivant</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require '../../includes/footer.php'; ?>