<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/dates.php';
require_once '../../includes/consultation_helpers.php';
is_authenticated();

// Réservé à l'agent du ministère
if ($_SESSION['role'] !== 'agent_ministere') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}
/* =========================================================
   STATISTIQUES PAR CONSULTATION
   Sous-requêtes (et non des JOIN multiples) pour éviter que
   les lignes se multiplient et faussent les compteurs.
   ========================================================= */
$lignes = $db->query(
    "SELECT co.id_consultation, co.titre, co.statut, co.date_ouverture, co.date_fermeture,
            co.id_structure, s.nom_struc,
            (SELECT COUNT(*) FROM contributions c
              WHERE c.id_consultation = co.id_consultation) AS nb_total,
            (SELECT COUNT(*) FROM contributions c
              WHERE c.id_consultation = co.id_consultation AND c.statut = 'validee') AS nb_validees,
            (SELECT COUNT(*) FROM contributions c
              WHERE c.id_consultation = co.id_consultation AND c.statut = 'en_attente') AS nb_attente,
            (SELECT COUNT(*) FROM contributions c
              WHERE c.id_consultation = co.id_consultation AND c.statut = 'rejetee') AS nb_rejetees,
            (SELECT COUNT(DISTINCT c.email_contributeur) FROM contributions c
              WHERE c.id_consultation = co.id_consultation) AS nb_contributeurs,
            (SELECT COUNT(*) FROM votes v
               JOIN contributions c ON c.id_contribution = v.id_contribution
              WHERE c.id_consultation = co.id_consultation AND v.valeur_vote = 'pour') AS nb_pour,
            (SELECT COUNT(*) FROM votes v
               JOIN contributions c ON c.id_contribution = v.id_contribution
              WHERE c.id_consultation = co.id_consultation AND v.valeur_vote = 'contre') AS nb_contre
     FROM consultations co
     LEFT JOIN structure s ON s.id_struc = co.id_structure
     ORDER BY co.date_ouverture DESC"
)->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   EXPORT CSV (avant toute sortie HTML)
   ========================================================= */
if (($_GET['export'] ?? '') === 'csv') {

    // Neutralise l'injection de formules (Excel exécute une cellule qui commence par = + - @)
    $csvSur = static function ($valeur): string {
        $texte = (string) ($valeur ?? '');
        return ($texte !== '' && strpbrk($texte[0], "=+-@\t\r") !== false) ? "'" . $texte : $texte;
    };

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="statistiques_consultations_' . date('Ymd') . '.csv"');

    $sortie = fopen('php://output', 'w');
    fwrite($sortie, "\xEF\xBB\xBF"); // BOM UTF-8 : accents corrects dans Excel

    $entete = ['Titre', 'Portée', 'Statut', 'Ouverture', 'Fermeture', 'Contributions',
               'Validées', 'En attente', 'Rejetées', 'Contributeurs uniques', 'Votes pour', 'Votes contre'];
    fputcsv($sortie, $entete, ';', '"', '\\');

    foreach ($lignes as $l) {
        fputcsv($sortie, [
            $csvSur($l['titre']),
            empty($l['id_structure']) ? 'Générale' : $csvSur($l['nom_struc']),
            consultationEstOuverte($l) ? 'Ouverte' : 'Clôturée',
            formaterDateFr($l['date_ouverture']),
            formaterDateFr($l['date_fermeture']),
            $l['nb_total'], $l['nb_validees'], $l['nb_attente'], $l['nb_rejetees'],
            $l['nb_contributeurs'], $l['nb_pour'], $l['nb_contre'],
        ], ';', '"', '\\');
    }
    fclose($sortie);
    exit();
}

/* =========================================================
   INDICATEURS GLOBAUX
   ========================================================= */
$nbConsultations = count($lignes);
$nbOuvertes = 0;
$totalContributions = 0;
$totalVotes = 0;
foreach ($lignes as $l) {
    if (consultationEstOuverte($l)) {
        $nbOuvertes++;
    }
    $totalContributions += (int) $l['nb_total'];
    $totalVotes += (int) $l['nb_pour'] + (int) $l['nb_contre'];
}

// Personnes distinctes ayant contribué OU voté (UNION supprime les doublons d'email)
$nbParticipants = (int) $db->query(
    "SELECT COUNT(*) FROM (
        SELECT email_contributeur AS email FROM contributions
        UNION
        SELECT email_votant AS email FROM votes
     ) t"
)->fetchColumn();

require '../../includes/header_dashboard.php';
?>

<style>
    .st-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
    .st-kpi { background: #fff; border: 1px solid #E4E7EC; border-radius: 10px; padding: 16px 18px; }
    .st-kpi .lib { color: #667085; font-size: .78rem; font-weight: 600; }
    .st-kpi .val { font-size: 1.9rem; font-weight: 700; color: #0B1D4D; line-height: 1.2; }
    .st-table-wrap { overflow-x: auto; }
    .st-table { width: 100%; border-collapse: collapse; font-size: .84rem; background: #fff; }
    .st-table th { text-align: left; padding: 10px 12px; background: #F5F7FA; color: #667085; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #E4E7EC; white-space: nowrap; }
    .st-table td { padding: 12px; border-bottom: 1px solid #E4E7EC; vertical-align: middle; }
    .st-table td.num, .st-table th.num { text-align: right; }
    .st-badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: .7rem; font-weight: 700; }
    .st-badge.ouverte { background: #E5F5EC; color: #0B6B3A; }
    .st-badge.cloturee { background: #EEF1F2; color: #667085; }
    .st-badge.generale { background: #E8EDFB; color: #1B3FAE; }
    .st-badge.structure { background: #E5F5EC; color: #0B6B3A; }
    .st-barre { display: flex; width: 120px; height: 8px; border-radius: 4px; overflow: hidden; background: #E4E7EC; }
    .st-barre .pour { background: #0F8A4B; }
    .st-barre .contre { background: #c0392b; }
    .st-score { font-size: .75rem; color: #667085; margin-top: 3px; }
    @media (max-width: 900px) { .st-kpis { grid-template-columns: repeat(2, 1fr); } }
</style>

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
           <li>
    <a href="../consultations/créer_consultations.php"
       class="mrri-sidebar-link">
        <span class="icon">
            <svg width="17" height="17" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="12" y1="12" x2="12" y2="18"/>
                <line x1="9" y1="15" x2="15" y2="15"/>
            </svg>
        </span>
        Créer une consultation
    </a>
</li>

<li>
    <a href="../consultations/mes_consultations.php"
       class="mrri-sidebar-link">
        <span class="icon">
            <svg width="17" height="17" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <rect x="4" y="3" width="16" height="18" rx="2"/>
                <line x1="8" y1="8" x2="16" y2="8"/>
                <line x1="8" y1="12" x2="16" y2="12"/>
                <line x1="8" y1="16" x2="13" y2="16"/>
            </svg>
        </span>
        Voir mes consultations
    </a>
</li>

<li>
    <a href="moderation.php" class="mrri-sidebar-link">
        <span class="icon">
            <svg width="17" height="17" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                <polyline points="9 10 11 12 15 8"/>
            </svg>
        </span>
        Gérer les contributions
    </a>
</li>

<li>
    <a href="statistiques.php" class="mrri-sidebar-link">
        <span class="icon">
            <svg width="17" height="17" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <line x1="3" y1="21" x2="21" y2="21"/>
                <rect x="5" y="12" width="3" height="9" rx="1"/>
                <rect x="11" y="7" width="3" height="14" rx="1"/>
                <rect x="17" y="3" width="3" height="18" rx="1"/>
            </svg>
        </span>
        Statistiques
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
                MRRI / <strong>Statistiques</strong>
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
                    <div class="mrri-breadcrumb">Espace professionnel / <span class="active">Statistiques</span></div>
                    <h1 class="mrri-page-title">Statistiques des consultations</h1>
                    <p class="mrri-page-description">Participation et résultats, consultation par consultation.</p>
                </div>
                <a href="statistiques.php?export=csv" class="mrri-btn mrri-btn-primary">Exporter en CSV</a>
            </div>

            <div class="st-kpis">
                <div class="st-kpi">
                    <div class="lib">Consultations</div>
                    <div class="val"><?= $nbConsultations ?></div>
                    <div class="lib"><?= $nbOuvertes ?> ouverte(s)</div>
                </div>
                <div class="st-kpi">
                    <div class="lib">Contributions</div>
                    <div class="val"><?= $totalContributions ?></div>
                </div>
                <div class="st-kpi">
                    <div class="lib">Votes</div>
                    <div class="val"><?= $totalVotes ?></div>
                </div>
                <div class="st-kpi">
                    <div class="lib">Participants uniques</div>
                    <div class="val"><?= $nbParticipants ?></div>
                    <div class="lib">contributeurs + votants</div>
                </div>
            </div>

            <div class="mrri-panel">
                <div class="mrri-panel-header">
                    <h2 class="mrri-panel-title">Détail par consultation</h2>
                </div>
                <div class="mrri-panel-body st-table-wrap">
                    <?php if (empty($lignes)): ?>
                        <p class="mrri-page-description">Aucune consultation pour le moment.</p>
                    <?php else: ?>
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th>Consultation</th>
                                    <th>Statut</th>
                                    <th class="num">Contributions</th>
                                    <th class="num">Validées</th>
                                    <th class="num">En attente</th>
                                    <th class="num">Rejetées</th>
                                    <th class="num">Contributeurs</th>
                                    <th>Votes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lignes as $l): ?>
                                    <?php
                                        $estGenerale = empty($l['id_structure']);
                                        $nbPour = (int) $l['nb_pour'];
                                        $nbContre = (int) $l['nb_contre'];
                                        $totalVotesLigne = $nbPour + $nbContre;
                                        $pctPour = $totalVotesLigne > 0 ? round($nbPour * 100 / $totalVotesLigne) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($l['titre']) ?></strong><br>
                                            <span class="st-badge <?= $estGenerale ? 'generale' : 'structure' ?>">
                                                <?= $estGenerale ? 'Générale' : htmlspecialchars($l['nom_struc']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php $ouverte = consultationEstOuverte($l); ?>
                                            <span class="st-badge <?= $ouverte ? 'ouverte' : 'cloturee' ?>">
                                                <?= $ouverte ? 'Ouverte' : 'Clôturée' ?>
                                            </span>
                                        </td>
                                        <td class="num"><?= (int) $l['nb_total'] ?></td>
                                        <td class="num"><?= (int) $l['nb_validees'] ?></td>
                                        <td class="num"><?= (int) $l['nb_attente'] ?></td>
                                        <td class="num"><?= (int) $l['nb_rejetees'] ?></td>
                                        <td class="num"><?= (int) $l['nb_contributeurs'] ?></td>
                                        <td>
                                            <?php if ($totalVotesLigne > 0): ?>
                                                <div class="st-barre" title="<?= $pctPour ?> % pour">
                                                    <div class="pour" style="width: <?= $pctPour ?>%"></div>
                                                    <div class="contre" style="width: <?= 100 - $pctPour ?>%"></div>
                                                </div>
                                                <div class="st-score"><?= $nbPour ?> pour · <?= $nbContre ?> contre</div>
                                            <?php else: ?>
                                                <span class="st-score">Aucun vote</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </section>
    </main>
</div>

<?php require '../../includes/footer_dashboard.php'; ?>