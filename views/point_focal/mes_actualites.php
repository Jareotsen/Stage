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

<link rel="stylesheet" href="../../publique/css/theme.css">

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