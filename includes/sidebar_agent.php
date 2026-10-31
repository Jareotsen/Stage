<?php
/**
 * Sidebar de l'agent du ministère.
 * Usage dans une page de views/agent_ministere/ :
 *     <?php $pageActive = 'moderation'; require '../../includes/sidebar_agent.php'; ?>
 *
 * Clés possibles : structures, consultations, moderation, statistiques
 *
 * @var string $pageActive
 */
$pageActive = $pageActive ?? '';

// cle => [lien, libellé, contenu de l'icône SVG]
$liens = [
    'structures' => ['dashboard.php', 'Structures',
        '<path d="M12 2.5 20.5 7h-17z" fill="currentColor" fill-opacity=".16"/><path d="M3 21.5h18"/><path d="M4.5 18h15"/><path d="M6 18v-7.5"/><path d="M10 18v-7.5"/><path d="M14 18v-7.5"/><path d="M18 18v-7.5"/>'],
    'consultations' => ['gerer_consultation.php', 'Consultations',
        '<path d="M14 9a2 2 0 0 1-2 2H6l-4 4V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2z" fill="currentColor" fill-opacity=".16"/><path d="M18 9h2a2 2 0 0 1 2 2v11l-4-4h-6a2 2 0 0 1-2-2v-1"/>'],
    'moderation' => ['moderation.php', 'Modération',
        '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" fill="currentColor" fill-opacity=".16"/><path d="m9 12 2 2 4-4"/>'],
    'statistiques' => ['statistiques.php', 'Statistiques',
        '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 16.5 11 12l3 3 5.5-6.5V17H7z" fill="currentColor" fill-opacity=".16" stroke="none"/><path d="m7 16.5 4-4.5 3 3 5.5-6.5"/>'],
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
            <small>Espace agent du ministère</small>
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