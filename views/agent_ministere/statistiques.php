<link rel="stylesheet" href="../../publique/css/theme.css">

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

$labelsDemographiques = [
    'genre' => ['femme' => 'Femme', 'homme' => 'Homme'],
    'age' => [
        'moins_18' => 'Moins de 18 ans',
        '18_24' => '18 à 24 ans',
        '25_34' => '25 à 34 ans',
        '35_44' => '35 à 44 ans',
        '45_54' => '45 à 54 ans',
        '55_plus' => '55 ans et plus',
    ],
    'secteur' => [
        'secteur_public' => 'Secteur public',
        'secteur_prive' => 'Secteur privé',
        'independant' => 'Travailleur indépendant',
        'etudiant' => 'Étudiant(e)',
        'sans_emploi' => 'Sans emploi',
        'autre' => 'Autre',
    ],
];
$requeteDemographique = static function (PDO $db, string $colonne, array $labels): array {
    $resultats = array_fill_keys(array_keys($labels), 0);
    $stmt = $db->query("SELECT {$colonne} AS categorie, COUNT(*) AS total FROM citoyens GROUP BY {$colonne}");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        if (array_key_exists($ligne['categorie'], $resultats)) {
            $resultats[$ligne['categorie']] = (int) $ligne['total'];
        }
    }
    return $resultats;
};
$donneesDemographiques = [
    'genre' => $requeteDemographique($db, 'genre', $labelsDemographiques['genre']),
    'age' => $requeteDemographique($db, 'tranche_age', $labelsDemographiques['age']),
    'secteur' => $requeteDemographique($db, 'secteur_activite', $labelsDemographiques['secteur']),
];

$rendreGraphiqueLigne = static function (string $titre, array $labels, array $valeurs): string {
    if (array_sum($valeurs) === 0) {
        return '<p class="st-chart-empty">Aucune adresse email vérifiée pour le moment.</p>';
    }

    $largeur = 720;
    $gauche = 54;
    $droite = 18;
    $haut = 22;
    $bas = 210;
    $maximum = max(1, (int) ceil(max($valeurs) / 4) * 4);
    $largeurTrace = $largeur - $gauche - $droite;
    $hauteurTrace = $bas - $haut;
    $points = [];
    $marqueurs = [];
    $elements = [
        '<svg class="st-chart-svg" viewBox="0 0 720 330" role="img" aria-label="' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') . '">',
        '<title>' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') . '</title>',
    ];

    for ($i = 0; $i <= 4; $i++) {
        $valeurAxe = (int) round($maximum * $i / 4);
        $y = $bas - ($hauteurTrace * $i / 4);
        $elements[] = '<line class="st-chart-gridline" x1="' . $gauche . '" y1="' . $y
            . '" x2="' . ($largeur - $droite) . '" y2="' . $y . '"></line>';
        $elements[] = '<text class="st-chart-axis" x="' . ($gauche - 10) . '" y="' . ($y + 4)
            . '" text-anchor="end">' . $valeurAxe . '</text>';
    }

    $nombre = count($valeurs);
    foreach (array_values($valeurs) as $index => $valeur) {
        $x = $nombre === 1
            ? $gauche + $largeurTrace / 2
            : $gauche + ($largeurTrace * $index / ($nombre - 1));
        $y = $bas - ($hauteurTrace * $valeur / $maximum);
        $points[] = round($x, 2) . ',' . round($y, 2);
        $etiquette = htmlspecialchars($labels[array_keys($valeurs)[$index]], ENT_QUOTES, 'UTF-8');
        $marqueurs[] = '<circle class="st-chart-point" cx="' . $x . '" cy="' . $y . '" r="4"></circle>';
        $marqueurs[] = '<text class="st-chart-value" x="' . $x . '" y="' . max($haut + 12, $y - 10)
            . '" text-anchor="middle">' . (int) $valeur . '</text>';
        $marqueurs[] = '<text class="st-chart-category" x="' . $x . '" y="238" transform="rotate(-38 '
            . $x . ' 238)" text-anchor="start">' . $etiquette . '</text>';
    }

    $elements[] = '<polyline class="st-chart-line" points="' . implode(' ', $points) . '"></polyline>';
    array_push($elements, ...$marqueurs);
    $elements[] = '</svg>';
    return implode('', $elements);
};

require '../../includes/header_dashboard.php';
?>

<link rel="stylesheet" href="../../publique/css/theme.css">

<div class="mrri-dashboard dashboard-agent">

    <?php $pageActive = 'statistiques'; require '../../includes/sidebar_agent.php'; ?>

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

            <section class="st-demographics" aria-labelledby="st-demographics-title">
                <div class="mrri-page-header">
                    <div>
                        <h2 class="mrri-panel-title" id="st-demographics-title">Profil des participants vérifiés</h2>
                        <p class="mrri-page-description">Répartition des adresses email vérifiées, tous les graphiques sont agrégés.</p>
                    </div>
                </div>
                <div class="st-chart-grid">
                    <?php foreach ([
                        'age' => 'Répartition par tranche d’âge',
                        'genre' => 'Répartition par genre',
                        'secteur' => 'Répartition par secteur d’activité',
                    ] as $cle => $titre): ?>
                        <article class="mrri-panel st-chart-card">
                            <div class="mrri-panel-header">
                                <h3 class="mrri-panel-title"><?= htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') ?></h3>
                            </div>
                            <div class="mrri-panel-body">
                                <?= $rendreGraphiqueLigne($titre, $labelsDemographiques[$cle], $donneesDemographiques[$cle]) ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="st-chart-note">Les données démographiques ne sont enregistrées qu’après validation du code envoyé par email.</p>
            </section>

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