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
        '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>'],
    'structure' => ['modifier_structure.php', 'Ma structure',
        '<path d="M3 21h18"/><path d="M9 21V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v12"/><path d="M9 9V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"/>'],
    'actualites' => ['mes_actualites.php', 'Actualités',
        '<path d="M4 4h13a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4z"/><line x1="8" y1="8" x2="15" y2="8"/><line x1="8" y1="12" x2="15" y2="12"/>'],
    'consultations' => ['mes_consultations.php', 'Consultations',
        '<rect x="4" y="3" width="16" height="18" rx="2"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="13" y2="16"/>'],
    'documents' => ['upload.php', 'Documents',
        '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>'],
];
?>
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
        <?php foreach ($liens as $cle => [$url, $libelle, $icone]): ?>
            <li>
                <a href="<?= $url ?>"
                   class="mrri-sidebar-link <?= $pageActive === $cle ? 'active' : '' ?>"
                   <?= $pageActive === $cle ? 'aria-current="page"' : '' ?>>
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icone ?></svg>
                    </span>
                    <?= $libelle ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="mrri-sidebar-bottom">
        <a href="../../deconnexion.php" class="mrri-sidebar-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Déconnexion
        </a>
    </div>
</aside>