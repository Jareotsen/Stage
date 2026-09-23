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

    <aside class="mrri-sidebar">
        <div class="mrri-sidebar-brand">
            <img src="../../publique/image/sceau.png" alt="Armoiries du Gabon">
            <div>
                <strong>Ministère de la Réforme<br>et des Institutions</strong>
                <small>Espace agent du ministère</small>
            </div>
        </div>

        <div class="mrri-sidebar-section">Navigation</div>
        <ul class="mrri-sidebar-nav">
            <li>
                <a href="dashboard.php" class="mrri-sidebar-link active">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    </span>
                    Structures
                </a>
            </li>
            <li>
                <a href="demandes.php" class="mrri-sidebar-link">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    Demandes reçues
                </a>
            </li>
        </ul>

        <div class="mrri-sidebar-bottom">
            <a href="../../deconnexion.php" class="mrri-sidebar-logout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Déconnexion
            </a>
        </div>
    </aside>

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