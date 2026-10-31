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

  <?php $pageActive = 'dashboard'; require '../../includes/sidebar_point_focal.php'; ?>

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
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z" fill="currentColor" fill-opacity=".16"/></svg>
                    </div>
                    <div class="mrri-stat-label">Actualités publiées</div>
                    <div class="mrri-stat-value"><?= $nbActualites ?></div>
                </div>
                <div class="mrri-stat-card stat-bleu">
                    <div class="mrri-stat-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l4 4v10a2 2 0 0 1-2 2Z" fill="currentColor" fill-opacity=".16"/><path d="M20 7h-3a2 2 0 0 1-2-2V2"/><path d="M3 7.6v12.8A1.6 1.6 0 0 0 4.6 22h9.8"/></svg>
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
    <a href="../point_focal/mes_consultations.php" class="mrri-panel" style="display:block; padding:16px;">
        <strong style="font-size:.85rem;">Créer une consultation</strong>
        <p class="mrri-page-description" style="margin-top:4px;">Création de consultations</p>
    </a>
</div>

<div class="col">
    <a href="../point_focal/mes_consultations.php" class="mrri-panel" style="display:block; padding:16px;">
        <strong style="font-size:.85rem;">Voir mes consultations</strong>
        <p class="mrri-page-description" style="margin-top:4px;">Suivi des consultations planifiées</p>
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
