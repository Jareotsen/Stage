<?php
/**
 * Sidebar du super administrateur.
 * Usage dans une page de views/super_admin/ :
 *     <?php $pageActive = 'comptes'; require '../../includes/sidebar_super_admin.php'; ?>
 *
 * Clés possibles : dashboard, comptes, categories
 *
 * @var string $pageActive
 */
$pageActive = $pageActive ?? '';

// cle => [lien, libellé, contenu de l'icône SVG]
$liens = [
    'dashboard' => ['dashboard.php', 'Tableau de bord',
        '<rect x="3" y="3" width="7" height="9" rx="1.5" fill="currentColor" fill-opacity=".16"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5" fill="currentColor" fill-opacity=".16"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>'],
    'comptes' => ['gerer_compte.php', 'Comptes utilisateurs',
        '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4" fill="currentColor" fill-opacity=".16"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
    'categories' => ['gerer_categories.php', 'Catégories',
        '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z" fill="currentColor" fill-opacity=".16"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>'],
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
            <small>Super administrateur</small>
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