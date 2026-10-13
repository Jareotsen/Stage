<?php
/**
 * Sidebar de l'agent du ministère.
 * Usage dans une page de views/agent_ministere/ :
 *     <?php $pageActive = 'moderation'; require '../../includes/sidebar_agent.php'; ?>
 *
 * Clés possibles : structures, demandes, consultations, moderation, statistiques
 *
 * @var string $pageActive
 */
$pageActive = $pageActive ?? '';

// cle => [lien, libellé, contenu de l'icône SVG]
$liens = [
    'structures' => ['dashboard.php', 'Structures',
        '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>'],
    'demandes' => ['demandes.php', 'Demandes reçues',
        '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
    'consultations' => ['gerer_consultation.php', 'Consultations',
        '<rect x="4" y="3" width="16" height="18" rx="2"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="13" y2="16"/>'],
    'moderation' => ['moderation.php', 'Modération',
        '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>'],
    'statistiques' => ['statistiques.php', 'Statistiques',
        '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
];
?>
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