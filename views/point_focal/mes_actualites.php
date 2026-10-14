<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';

is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

/* ---------------------------------------------
   Récupération de la structure du point focal
--------------------------------------------- */
$resp = $db->prepare(
    "SELECT *
     FROM structure
     WHERE id_responsable = :id_responsable"
);

$resp->execute([
    ':id_responsable' => $_SESSION['user_id']
]);

$structures = $resp->fetch(PDO::FETCH_ASSOC);

/* ---------------------------------------------
   Récupération des actualités de la structure
--------------------------------------------- */
$mon_actu = [];

if ($structures) {
    $stmtActu = $db->prepare(
        "SELECT a.*
         FROM actualites AS a
         WHERE a.id_structure = :id_structure
         ORDER BY a.date_publication DESC"
    );

    $stmtActu->execute([
        ':id_structure' => $structures['id_struc']
    ]);

    $mon_actu = $stmtActu->fetchAll(PDO::FETCH_ASSOC);
}

$token = generer_token_csrf();

/* ---------------------------------------------
   Page active pour la sidebar
--------------------------------------------- */
$pageActive = 'actualites';

require '../../includes/header_dashboard.php';
?>

<style>
/* =============================================
   MISE EN PAGE AVEC SIDEBAR
============================================= */
.page-avec-sidebar {
    display: flex;
    min-height: 100vh;
    background: var(--mrri-bg);
}

.contenu-principal {
    flex: 1;
    min-width: 0;

    /* Adapte cette valeur à la largeur réelle de ta sidebar */
    margin-left: 270px;

    padding: 2.5rem 2.5rem 3rem;
}

@media (max-width: 991px) {
    .contenu-principal {
        margin-left: 0;
        padding: 1.5rem 1rem 2rem;
    }
}

/* =============================================
   SECTION MES ACTUALITÉS
============================================= */
.mes-actu-section {
    padding: 0;
}

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
    color: var(--mrri-bleu-nuit);
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

    transition: all .2s ease;
}

.btn-retour:hover {
    border-color: var(--mrri-bleu);
    color: var(--mrri-bleu);
    transform: translateY(-1px);
}

/* =============================================
   LISTE DES ACTUALITÉS
============================================= */
.actu-liste {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

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

    transition:
        border-color .2s ease,
        transform .2s ease,
        box-shadow .2s ease;
}

.actu-row:hover {
    border-color: var(--mrri-bleu);
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(11, 29, 77, .07);
}

.actu-row-infos h3 {
    font-size: 1rem;
    margin: 0 0 4px;
    color: var(--mrri-bleu-nuit);
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

    transition: all .2s ease;
}

.btn-supprimer:hover {
    background: #e0433c;
    color: #fff;
    transform: translateY(-1px);
}

.actu-vide {
    text-align: center;
    padding: 3rem 1rem;

    color: var(--mrri-text-light);
    border: 1px dashed var(--mrri-border);
    border-radius: var(--radius-md);
    background: #fff;
}

@media (max-width: 575px) {
    .actu-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .actu-row form {
        width: 100%;
    }

    .btn-supprimer {
        width: 100%;
    }
}
</style>

<div class="page-avec-sidebar">

    <?php require '../../includes/sidebar_point_focal.php'; ?>

    <main class="contenu-principal">
        <section class="mes-actu-section">

            <div class="mes-actu-heading">
                <div>
                    <h1>Mes actualités</h1>
                    <p>Gérez les actualités publiées par votre structure.</p>
                </div>

                <a href="dashboard.php" class="btn-retour">
                    <svg width="16" height="16"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">
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
                                <h3>
                                    <?= htmlspecialchars($actualite['titre']) ?>
                                </h3>

                                <div class="actu-date">
                                    <?= htmlspecialchars($actualite['date_publication']) ?>
                                </div>
                            </div>

                            <form action="supprimer_actualites.php"
                                  method="post"
                                  onsubmit="return confirm('Supprimer cette actualité ?');">

                                <input type="hidden"
                                       name="id_actu"
                                       value="<?= htmlspecialchars($actualite['id_actu'], ENT_QUOTES, 'UTF-8') ?>">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= htmlspecialchars(generer_token_csrf()) ?>">

                                <button type="submit" class="btn-supprimer">
                                    Supprimer
                                </button>
                            </form>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>
    </main>

</div>

<?php require '../../includes/footer_dashboard.php'; ?>