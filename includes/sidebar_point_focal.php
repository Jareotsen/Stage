<?php
/**
 * Sidebar du point focal.
 * Usage dans une page de views/point_focal/ :
 *     <?php $pageActive = 'actualites'; require '../../includes/sidebar_point_focal.php'; ?>
 *
 * Clés possibles : dashboard, structure, actualites, consultations, documents
 *
 * @var string $pageActive
 */
$pageActive = $pageActive ?? '';

// cle => [lien, libellé, contenu de l'icône SVG]
$liens = [
    'dashboard' => ['dashboard.php', 'Tableau de bord',
        '<rect x="3" y="3" width="7" height="9" rx="1.5" fill="currentColor" fill-opacity=".16"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5" fill="currentColor" fill-opacity=".16"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>'],
    'structure' => ['modifier_structure.php', 'Ma structure',
        '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z" fill="currentColor" fill-opacity=".16"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>'],
    'actualites' => ['mes_actualites.php', 'Actualités',
        '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z" fill="currentColor" fill-opacity=".16"/>'],
    'consultations' => ['mes_consultations.php', 'Consultations',
        '<path d="M14 9a2 2 0 0 1-2 2H6l-4 4V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2z" fill="currentColor" fill-opacity=".16"/><path d="M18 9h2a2 2 0 0 1 2 2v11l-4-4h-6a2 2 0 0 1-2-2v-1"/>'],
    'documents' => ['upload.php', 'Documents',
        '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" fill="currentColor" fill-opacity=".16"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M12 12v6"/><path d="m15 15-3-3-3 3"/>'],
];
?>
<!-- Bouton du menu sur téléphone (le script est dans includes/footer_dashboard.php) -->
<button type="button" class="mrri-menu-btn" aria-controls="mrri-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
    <svg class="ouvrir" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h10"/></svg>
    <svg class="fermer" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
</button>
<div class="mrri-sidebar-overlay" hidden></div>
<aside class="mrri-sidebar" id="mrri-sidebar">
    <div class="mrri-sidebar-brand">
        <img src="../../publique/image/sceau.png" alt="Armoiries du Gabon">
        <div>
            <strong>Ministère de la Réforme<br>et des Institutions</strong>
            <small>Espace point focal</small>
        </div>
    </div>

    <div class="mrri-sidebar-section">Navigation</div>
    <ul class="mrri-sidebar-nav">
        <?php foreach ($liens as $cle => [$url, $libelle, $icone]): ?>
            <li>
                <a href="<?= $url ?>"
                   class="mrri-sidebar-link <?= $pageActive === $cle ? 'active' : '' ?>"
                   <?= $pageActive === $cle ? 'aria-current="page"' : '' ?>>
                    <span class="icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icone ?></svg>
                    </span>
                    <?= $libelle ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="mrri-sidebar-bottom">
        <a href="../../deconnexion.php" class="mrri-sidebar-logout">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
            Déconnexion
        </a>
    </div>
</aside>