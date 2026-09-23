<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

$resp = $db->prepare("SELECT * 
                        FROM structure 
                        WHERE id_responsable = :id_responsable");
$resp->execute([':id_responsable' => $_SESSION['user_id']]);
$structures = $resp->fetch(PDO::FETCH_ASSOC);

$mon_actu = [];
if ($structures) {
    $mon_actu = $db->prepare("SELECT *
                            FROM actualites AS a
                            JOIN structure AS b
                            ON a.id_structure = b.id_struc
                            WHERE a.id_structure = :id_structure
                            ORDER BY a.date_publication DESC");
    $mon_actu->execute([':id_structure' => $structures['id_struc']]);
    $mon_actu = $mon_actu->fetchAll(PDO::FETCH_ASSOC);
}

require '../../includes/header.php';
?>

<style>
.mes-actu-section { padding: 3.5rem 0; }

.mes-actu-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}
.mes-actu-heading h1 {
    font-size: 1.6rem;
    margin: 0;
}
.mes-actu-heading p {
    color: var(--mrri-text-light);
    font-size: .9rem;
    margin: 4px 0 0;
}

.btn-retour {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border: 1px solid var(--mrri-border);
    border-radius: 7px;
    background: #fff;
    color: var(--mrri-text);
    font-weight: 600;
    font-size: .85rem;
}
.btn-retour:hover {
    border-color: var(--mrri-bleu);
    color: var(--mrri-bleu);
}

.actu-liste { display: flex; flex-direction: column; gap: 14px; }

.actu-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    background: #fff;
    border: 1px solid var(--mrri-border);
    border-radius: var(--radius-md);
    padding: 1.1rem 1.4rem;
    flex-wrap: wrap;
}

.actu-row-infos h3 {
    font-size: 1rem;
    margin: 0 0 4px;
}
.actu-row-infos .actu-date {
    color: var(--mrri-text-light);
    font-size: .78rem;
}

.btn-supprimer {
    border: 1px solid #e0433c;
    background: #fff;
    color: #e0433c;
    font-weight: 600;
    font-size: .8rem;
    padding: 8px 16px;
    border-radius: 7px;
    flex-shrink: 0;
}
.btn-supprimer:hover {
    background: #e0433c;
    color: #fff;
}

.actu-vide {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--mrri-text-light);
    border: 1px dashed var(--mrri-border);
    border-radius: var(--radius-md);
}
</style>

<section class="mes-actu-section">
    <div class="container">

        <div class="mes-actu-heading">
            <div>
                <h1>Mes actualités</h1>
                <p>Gérez les actualités publiées par votre structure.</p>
            </div>
            <a href="dashboard.php" class="btn-retour">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                Retour au tableau de bord
            </a>
        </div>

        <?php afficher_flash(); ?>

        <?php if (empty($mon_actu)): ?>

            <div class="actu-vide">
                Aucune actualité publiée pour l'instant.
            </div>

        <?php else: ?>

            <div class="actu-liste">
                <?php foreach ($mon_actu as $actualite): ?>
                    <div class="actu-row">
                        <div class="actu-row-infos">
                            <h3><?= htmlspecialchars($actualite['titre']) ?></h3>
                            <div class="actu-date">
                                <?= htmlspecialchars($actualite['date_publication']) ?>
                            </div>
                        </div>

                        <form action="supprimer_actualites.php" method="post"
                              onsubmit="return confirm('Supprimer cette actualité ?');">
                            <input type="hidden" name="id_actu"
                                   value="<?= htmlspecialchars($actualite['id_actu'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn-supprimer">
                                Supprimer
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php require '../../includes/footer.php'; ?>