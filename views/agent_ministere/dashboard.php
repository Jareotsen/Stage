<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'agent_ministere') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

$resultat = $db->query("
    SELECT structure.id_struc, structure.nom_struc, structure.adresse, structure.directeur_general,
           categories.nom_cat, structure.sigle
    FROM structure
    JOIN categories ON structure.id_cat = categories.id_cat
");
$structures = $resultat->fetchAll(PDO::FETCH_ASSOC);

$nbStructures = count($structures);

require '../../includes/header_dashboard.php';
?>

<div class="mrri-dashboard dashboard-agent">

    <?php $pageActive = 'structures'; require '../../includes/sidebar_agent.php'; ?>

    <main class="mrri-main">
        <header class="mrri-topbar">
            <div class="mrri-topbar-title">
                MRRI / <strong>Structures</strong>
            </div>
            <div class="mrri-user">
                <div class="mrri-user-avatar">AM</div>
                <div class="mrri-user-info">
                    <div class="mrri-user-name">Agent du ministère</div>
                    <div class="mrri-user-role">Accès lecture étendue</div>
                </div>
            </div>
        </header>

        <section class="mrri-content">

            <?php afficher_flash(); ?>

            <div class="mrri-page-header">
                <div>
                    <div class="mrri-breadcrumb">Espace professionnel / <span class="active">Structures</span></div>
                    <h1 class="mrri-page-title">Vue d'ensemble des structures sous tutelle</h1>
                    <p class="mrri-page-description">Accès agent du Ministère — <?= $nbStructures ?> structure(s) enregistrée(s)</p>
                </div>
                <a href="demandes.php" class="mrri-btn mrri-btn-blue">Voir les demandes reçues</a>
            </div>

            <div class="mrri-panel">
                <div class="mrri-panel-header">
                    <h2 class="mrri-panel-title">Structures sous tutelle</h2>
                </div>
                <div class="mrri-panel-body">
                    <div class="row row-cols-1 row-cols-md-3 g-3">
                        <?php foreach ($structures as $s): ?>
                            <div class="col">
                                <div class="mrri-panel h-100" style="box-shadow:none; border-color:var(--mrri-bordure);">
                                    <div class="mrri-panel-body">
                                        <h5 style="font-size:.92rem; margin-bottom:4px;"><?= htmlspecialchars($s['nom_struc']) ?></h5>
                                        <p class="mrri-page-description" style="margin-bottom:8px;"><?= htmlspecialchars($s['sigle'] ?? '') ?></p>
                                        <span class="mrri-badge mrri-badge-bleu" style="margin-bottom:10px; display:inline-block;">
                                            <?= htmlspecialchars($s['nom_cat']) ?>
                                        </span>
                                        <p class="mrri-page-description">
                                            <?= htmlspecialchars($s['adresse'] ?? 'Adresse non renseignée') ?>
                                        </p>
                                        <a href="../public/fiche.php?id=<?= $s['id_struc'] ?>" class="mrri-btn mrri-btn-outline mt-2">
                                            Voir la fiche
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </section>
    </main>
</div>

<?php require '../../includes/footer_dashboard.php'; ?>