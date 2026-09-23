<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

$resultat = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
$resultat->execute([':id_responsable' => $_SESSION['user_id']]);
$structure = $resultat->fetch(PDO::FETCH_ASSOC);

// Statistiques simples pour les cartes du tableau de bord
$nbActualites = 0;
$nbDocuments = 0;
if ($structure) {
    $stmtActu = $db->prepare("SELECT COUNT(*) FROM actualites WHERE id_structure = :id");
    $stmtActu->execute([':id' => $structure['id_struc']]);
    $nbActualites = (int) $stmtActu->fetchColumn();

    $stmtDoc = $db->prepare("SELECT COUNT(*) FROM documents WHERE id_structure = :id");
    $stmtDoc->execute([':id' => $structure['id_struc']]);
    $nbDocuments = (int) $stmtDoc->fetchColumn();
}

require '../../includes/header_dashboard.php';
?>

<div class="mrri-dashboard dashboard-point-focal">

    <aside class="mrri-sidebar">
        <div class="mrri-sidebar-brand">
            <img src="../../publique/image/sceau.png" alt="Armoiries du Gabon">
            <div>
                <strong>Ministère de la Réforme<br>et des Institutions</strong>
                <small>Espace point focal</small>
            </div>
        </div>

        <div class="mrri-sidebar-section">Navigation</div>
        <ul class="mrri-sidebar-nav">
            <li>
                <a href="dashboard.php" class="mrri-sidebar-link active">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    </span>
                    Tableau de bord
                </a>
            </li>
            <li>
                <a href="modifier_structure.php" class="mrri-sidebar-link">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M9 21V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v12"/><path d="M9 9V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"/></svg>
                    </span>
                    Ma structure
                </a>
            </li>
            <li>
                <a href="mes_actualites.php" class="mrri-sidebar-link">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h13a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4z"/><line x1="8" y1="8" x2="15" y2="8"/><line x1="8" y1="12" x2="15" y2="12"/></svg>
                    </span>
                    Actualités
                </a>
            </li>
            <li>
                <a href="upload.php" class="mrri-sidebar-link">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </span>
                    Documents
                </a>
            </li>
            <li>
                <a href="nouvelle_demande.php" class="mrri-sidebar-link">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    Demandes
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
                MRRI / <strong>Tableau de bord</strong>
            </div>
            <div class="mrri-user">
                <div class="mrri-user-avatar">PF</div>
                <div class="mrri-user-info">
                    <div class="mrri-user-name"><?= htmlspecialchars($structure['nom_struc'] ?? 'Point focal') ?></div>
                    <div class="mrri-user-role">Point focal</div>
                </div>
            </div>
        </header>

        <section class="mrri-content">

            <?php afficher_flash(); ?>

            <div class="mrri-page-header">
                <div>
                    <div class="mrri-breadcrumb">Espace professionnel / <span class="active">Tableau de bord</span></div>
                    <h1 class="mrri-page-title">Bienvenue, <?= htmlspecialchars($structure['nom_struc'] ?? '') ?></h1>
                    <p class="mrri-page-description">
                        Sigle : <?= htmlspecialchars($structure['sigle'] ?? '—') ?>
                        — Adresse : <?= htmlspecialchars($structure['adresse'] ?? 'Non renseignée') ?>
                    </p>
                </div>
                <a href="ajouter_actualite.php" class="mrri-btn mrri-btn-primary">+ Ajouter une actualité</a>
            </div>

            <div class="mrri-stat-grid">
                <div class="mrri-stat-card stat-vert">
                    <div class="mrri-stat-icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h13a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4z"/><line x1="8" y1="8" x2="15" y2="8"/><line x1="8" y1="12" x2="15" y2="12"/></svg>
                    </div>
                    <div class="mrri-stat-label">Actualités publiées</div>
                    <div class="mrri-stat-value"><?= $nbActualites ?></div>
                </div>
                <div class="mrri-stat-card stat-bleu">
                    <div class="mrri-stat-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                    </div>
                    <div class="mrri-stat-label">Documents partagés</div>
                    <div class="mrri-stat-value"><?= $nbDocuments ?></div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mrri-panel">
                        <div class="mrri-panel-header">
                            <h2 class="mrri-panel-title">Gérer ma structure</h2>
                        </div>
                        <div class="mrri-panel-body">
                            <div class="row row-cols-1 row-cols-md-2 g-3">
                                <div class="col">
                                    <a href="modifier_structure.php" class="mrri-panel" style="display:block; padding:16px;">
                                        <strong style="font-size:.85rem;">Modifier la structure</strong>
                                        <p class="mrri-page-description" style="margin-top:4px;">Coordonnées, mission, photos</p>
                                    </a>
                                </div>
                                <div class="col">
                                    <a href="mes_actualites.php" class="mrri-panel" style="display:block; padding:16px;">
                                        <strong style="font-size:.85rem;">Gérer mes actualités</strong>
                                        <p class="mrri-page-description" style="margin-top:4px;">Publier, consulter, supprimer</p>
                                    </a>
                                </div>
                                <div class="col">
                                    <a href="upload.php" class="mrri-panel" style="display:block; padding:16px;">
                                        <strong style="font-size:.85rem;">Uploader un document</strong>
                                        <p class="mrri-page-description" style="margin-top:4px;">Public ou confidentiel</p>
                                    </a>
                                </div>
                                <div class="col">
                                    <a href="nouvelle_demande.php" class="mrri-panel" style="display:block; padding:16px;">
                                        <strong style="font-size:.85rem;">Soumettre une demande</strong>
                                        <p class="mrri-page-description" style="margin-top:4px;">Contacter le Ministère</p>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mrri-panel">
                        <div class="mrri-panel-header">
                            <h2 class="mrri-panel-title">Ma structure</h2>
                        </div>
                        <div class="mrri-panel-body">
                            <p class="mrri-page-description">
                                <?= htmlspecialchars($structure['mission'] ?? 'Mission non renseignée.') ?>
                            </p>
                            <a href="modifier_structure.php" class="mrri-btn mrri-btn-outline mt-2">Modifier</a>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </main>
</div>

<?php require '../../includes/footer_dashboard.php'; ?>