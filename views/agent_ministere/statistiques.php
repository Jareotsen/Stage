<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/dates.php';
require_once '../../includes/consultation_helpers.php';
require_once '../../includes/citizen_email.php';
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

    // Rien ne doit précéder le BOM, sinon Excel ne détecte plus l'UTF-8 (accents illisibles)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

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

/* =========================================================
   ACTIVITÉ DANS LE TEMPS (contributions et votes par jour, cumulés)
   ========================================================= */
$compterParJour = static function (PDO $db, string $table, string $colonne): array {
    $resultats = [];
    $stmt = $db->query("SELECT DATE({$colonne}) AS jour, COUNT(*) AS total
                        FROM {$table} WHERE {$colonne} IS NOT NULL GROUP BY jour");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $resultats[$ligne['jour']] = (int) $ligne['total'];
    }
    return $resultats;
};
$contributionsParJour = $compterParJour($db, 'contributions', 'date_contribution');
$votesParJour = $compterParJour($db, 'votes', 'date_vote');

$activite = [];
$joursActivite = array_merge(array_keys($contributionsParJour), array_keys($votesParJour));
if ($joursActivite) {
    // Du premier jour d'activité (ou d'ouverture d'une consultation) jusqu'à aujourd'hui
    $debuts = $joursActivite;
    foreach ($lignes as $l) {
        if (!empty($l['date_ouverture'])) {
            $debuts[] = substr($l['date_ouverture'], 0, 10);
        }
    }
    $jour = new DateTimeImmutable(min($debuts));
    $fin = new DateTimeImmutable(max(date('Y-m-d'), max($joursActivite)));
    $parSemaine = $jour->diff($fin)->days > 90; // au-delà de 3 mois, un point par semaine

    $cumulContributions = 0;
    $cumulVotes = 0;
    while ($jour <= $fin) {
        $cle = $jour->format('Y-m-d');
        $periode = $parSemaine ? $jour->modify('monday this week')->format('Y-m-d') : $cle;
        if (!isset($activite[$periode])) {
            $activite[$periode] = ['date' => $periode, 'contributions' => 0, 'votes' => 0];
        }
        $activite[$periode]['contributions'] += $contributionsParJour[$cle] ?? 0;
        $activite[$periode]['votes'] += $votesParJour[$cle] ?? 0;
        $jour = $jour->modify('+1 day');
    }
    foreach ($activite as &$point) {
        $cumulContributions += $point['contributions'];
        $cumulVotes += $point['votes'];
        $point['cumul_contributions'] = $cumulContributions;
        $point['cumul_votes'] = $cumulVotes;
    }
    unset($point);
    $activite = array_values($activite);
}

/* =========================================================
   MODÉRATION ET AVIS PAR CONSULTATION (mêmes chiffres que le tableau)
   ========================================================= */
$consultationsAvecContributions = array_values(array_filter($lignes, static fn($l) => (int) $l['nb_total'] > 0));
$consultationsAvecVotes = array_values(array_filter($lignes, static fn($l) => (int) $l['nb_pour'] + (int) $l['nb_contre'] > 0));
$maxContributions = max(1, ...array_map(static fn($l) => (int) $l['nb_total'], $lignes ?: [['nb_total' => 0]]));
$maxVotesCote = max(1, ...array_map(static fn($l) => max((int) $l['nb_pour'], (int) $l['nb_contre']), $lignes ?: [['nb_pour' => 0, 'nb_contre' => 0]]));
$totauxModeration = ['validees' => 0, 'attente' => 0, 'rejetees' => 0];
$totalPour = 0;
$totalContre = 0;
foreach ($lignes as $l) {
    $totauxModeration['validees'] += (int) $l['nb_validees'];
    $totauxModeration['attente'] += (int) $l['nb_attente'];
    $totauxModeration['rejetees'] += (int) $l['nb_rejetees'];
    $totalPour += (int) $l['nb_pour'];
    $totalContre += (int) $l['nb_contre'];
}

// Diagramme en barres horizontales : une barre par catégorie, longueur proportionnelle
// à l'effectif (la plus grande remplit la piste), effectif et part du total affichés en bout de barre.
// $trier : true pour les catégories sans ordre naturel (secteur), false pour les ordonnées (âge).
$rendreGraphiqueBarres = static function (string $titre, array $labels, array $valeurs, bool $trier = false): string {
    $total = array_sum($valeurs);
    if ($total === 0) {
        return '<p class="st-chart-empty">Aucune donnée pour le moment.</p>';
    }
    if ($trier) {
        arsort($valeurs);
    }

    $maximum = max($valeurs);
    $html = '<ul class="st-barres" aria-label="' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') . '">';
    foreach ($valeurs as $cle => $valeur) {
        $etiquette = htmlspecialchars($labels[$cle], ENT_QUOTES, 'UTF-8');
        $pourcentage = (int) round($valeur * 100 / $total);
        $largeur = round($valeur * 100 / $maximum, 2);
        $html .= '<li class="st-barre-ligne' . ($valeur === 0 ? ' est-vide' : '') . '"'
            . ' title="' . $etiquette . ' : ' . $valeur . ' (' . $pourcentage . ' %)">'
            . '<span class="st-barre-libelle">' . $etiquette . '</span>'
            . '<span class="st-barre-piste" aria-hidden="true"><span class="st-barre-valeur" style="width: ' . $largeur . '%"></span></span>'
            . '<span class="st-barre-chiffre"><strong>' . $valeur . '</strong> <span>' . $pourcentage . ' %</span></span>'
            . '</li>';
    }
    return $html . '</ul>';
};

require '../../includes/header_dashboard.php';
?>

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

            <section class="st-demographics" aria-labelledby="st-activite-title">
                <div class="mrri-page-header">
                    <div>
                        <h2 class="mrri-panel-title" id="st-activite-title">Activité des consultations</h2>
                        <p class="mrri-page-description">Évolution de la participation, état de la modération et avis exprimés par les votants.</p>
                    </div>
                </div>

                <!-- ===== Participation dans le temps ===== -->
                <article class="mrri-panel st-chart-card st-chart-large">
                    <div class="mrri-panel-header st-chart-header">
                        <div>
                            <h3 class="mrri-panel-title">Participation dans le temps</h3>
                            <?php $libellePeriode = !empty($activite) && $parSemaine ? 'semaine' : 'jour'; ?>
                            <p class="st-chart-sous-titre" id="st-courbe-sous-titre"
                               data-jour="Nombre de contributions et de votes par <?= $libellePeriode ?>"
                               data-cumul="Total cumulé depuis l’ouverture des consultations">Nombre de contributions et de votes par <?= $libellePeriode ?></p>
                        </div>
                        <?php if (!empty($activite)): ?>
                            <div class="st-chart-outils">
                                <ul class="st-legende" aria-label="Légende">
                                    <li><span class="st-pastille st-c-contributions st-pastille-ligne"></span>Contributions <strong><?= $totalContributions ?></strong></li>
                                    <li><span class="st-pastille st-c-votes st-pastille-ligne"></span>Votes <strong><?= $totalVotes ?></strong></li>
                                </ul>
                                <div class="st-bascule" role="group" aria-label="Mode d’affichage de la courbe">
                                    <button type="button" data-mode="jour" aria-pressed="true">Par <?= $libellePeriode ?></button>
                                    <button type="button" data-mode="cumul" aria-pressed="false">Cumulé</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mrri-panel-body">
                        <?php if (empty($activite)): ?>
                            <p class="st-chart-empty">Aucune contribution ni aucun vote pour le moment.</p>
                        <?php else: ?>
                            <div class="st-courbe" id="st-courbe-activite" tabindex="0"
                                 role="img" aria-label="Participation dans le temps : <?= $totalContributions ?> contributions et <?= $totalVotes ?> votes au total. Flèches gauche et droite pour parcourir les dates.">
                                <div class="st-infobulle" hidden></div>
                            </div>
                            <script type="application/json" id="st-donnees-activite"><?= json_encode($activite, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
                            <details class="st-donnees">
                                <summary>Voir les données</summary>
                                <div class="st-table-wrap">
                                <table class="st-table st-table-donnees">
                                    <thead>
                                        <tr>
                                            <th><?= $parSemaine ? 'Semaine du' : 'Date' ?></th>
                                            <th class="num">Contributions</th>
                                            <th class="num">Votes</th>
                                            <th class="num">Cumul contributions</th>
                                            <th class="num">Cumul votes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activite as $point): ?>
                                            <?php if ($point['contributions'] + $point['votes'] === 0) { continue; } ?>
                                            <tr>
                                                <td><?= formaterDateFr($point['date']) ?></td>
                                                <td class="num"><?= $point['contributions'] ?></td>
                                                <td class="num"><?= $point['votes'] ?></td>
                                                <td class="num"><?= $point['cumul_contributions'] ?></td>
                                                <td class="num"><?= $point['cumul_votes'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                            </details>
                        <?php endif; ?>
                    </div>
                </article>

                <div class="st-chart-grid st-chart-grid-2">

                    <!-- ===== Modération par consultation ===== -->
                    <article class="mrri-panel st-chart-card">
                        <div class="mrri-panel-header st-chart-header st-chart-header-colonne">
                            <h3 class="mrri-panel-title">Modération des contributions</h3>
                            <ul class="st-legende" aria-label="Légende">
                                <li><span class="st-pastille st-c-validee"></span><i class="bi bi-check-circle" aria-hidden="true"></i>Validées <strong><?= $totauxModeration['validees'] ?></strong></li>
                                <li><span class="st-pastille st-c-attente"></span><i class="bi bi-hourglass-split" aria-hidden="true"></i>En attente <strong><?= $totauxModeration['attente'] ?></strong></li>
                                <li><span class="st-pastille st-c-rejetee"></span><i class="bi bi-x-circle" aria-hidden="true"></i>Rejetées <strong><?= $totauxModeration['rejetees'] ?></strong></li>
                            </ul>
                        </div>
                        <div class="mrri-panel-body">
                            <?php if (empty($consultationsAvecContributions)): ?>
                                <p class="st-chart-empty">Aucune contribution pour le moment.</p>
                            <?php else: ?>
                                <ul class="st-lignes">
                                    <?php foreach ($consultationsAvecContributions as $l): ?>
                                        <?php
                                            $segments = [
                                                'validee' => ['Validées', (int) $l['nb_validees']],
                                                'attente' => ['En attente', (int) $l['nb_attente']],
                                                'rejetee' => ['Rejetées', (int) $l['nb_rejetees']],
                                            ];
                                            $titre = htmlspecialchars($l['titre'], ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <li class="st-ligne">
                                            <div class="st-ligne-tete">
                                                <span class="st-ligne-titre" title="<?= $titre ?>"><?= $titre ?></span>
                                                <span class="st-ligne-total"><strong><?= (int) $l['nb_total'] ?></strong> contribution<?= (int) $l['nb_total'] > 1 ? 's' : '' ?></span>
                                            </div>
                                            <div class="st-pile" style="width: <?= round((int) $l['nb_total'] * 100 / $maxContributions, 2) ?>%">
                                                <?php foreach ($segments as $classe => [$libelle, $nombre]): ?>
                                                    <?php if ($nombre > 0): ?>
                                                        <span class="st-segment st-c-<?= $classe ?>" style="flex-grow: <?= $nombre ?>"
                                                              title="<?= $libelle ?> : <?= $nombre ?>"></span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php $sansContribution = count($lignes) - count($consultationsAvecContributions); ?>
                                <?php if ($sansContribution > 0): ?>
                                    <p class="st-chart-note"><?= $sansContribution ?> consultation<?= $sansContribution > 1 ? 's' : '' ?> sans contribution non affichée<?= $sansContribution > 1 ? 's' : '' ?>.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </article>

                    <!-- ===== Avis des votants par consultation ===== -->
                    <article class="mrri-panel st-chart-card">
                        <div class="mrri-panel-header st-chart-header st-chart-header-colonne">
                            <h3 class="mrri-panel-title">Avis des votants</h3>
                            <ul class="st-legende" aria-label="Légende">
                                <li><span class="st-pastille st-c-contre"></span><i class="bi bi-hand-thumbs-down" aria-hidden="true"></i>Contre <strong><?= $totalContre ?></strong></li>
                                <li><span class="st-pastille st-c-pour"></span><i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>Pour <strong><?= $totalPour ?></strong></li>
                            </ul>
                        </div>
                        <div class="mrri-panel-body">
                            <?php if (empty($consultationsAvecVotes)): ?>
                                <p class="st-chart-empty">Aucun vote pour le moment.</p>
                            <?php else: ?>
                                <ul class="st-lignes">
                                    <?php foreach ($consultationsAvecVotes as $l): ?>
                                        <?php
                                            $nbPour = (int) $l['nb_pour'];
                                            $nbContre = (int) $l['nb_contre'];
                                            $pctPour = (int) round($nbPour * 100 / ($nbPour + $nbContre));
                                            $titre = htmlspecialchars($l['titre'], ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <li class="st-ligne">
                                            <div class="st-ligne-tete">
                                                <span class="st-ligne-titre" title="<?= $titre ?>"><?= $titre ?></span>
                                                <span class="st-ligne-total"><strong><?= $pctPour ?> %</strong> pour</span>
                                            </div>
                                            <div class="st-divergent" title="<?= $nbContre ?> contre · <?= $nbPour ?> pour">
                                                <span class="st-cote st-cote-contre"><span class="st-segment st-c-contre" data-nb="<?= $nbContre ?>" style="width: <?= round($nbContre * 100 / $maxVotesCote, 2) ?>%"></span></span>
                                                <span class="st-cote st-cote-pour"><span class="st-segment st-c-pour" data-nb="<?= $nbPour ?>" style="width: <?= round($nbPour * 100 / $maxVotesCote, 2) ?>%"></span></span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php $sansVote = count($lignes) - count($consultationsAvecVotes); ?>
                                <?php if ($sansVote > 0): ?>
                                    <p class="st-chart-note"><?= $sansVote ?> consultation<?= $sansVote > 1 ? 's' : '' ?> sans vote non affichée<?= $sansVote > 1 ? 's' : '' ?>.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </article>

                </div>
                <p class="st-chart-note">Le détail chiffré de chaque consultation figure dans le tableau en bas de page.</p>
            </section>

            <section class="st-demographics" aria-labelledby="st-demographics-title">
                <div class="mrri-page-header">
                    <div>
                        <h2 class="mrri-panel-title" id="st-demographics-title">Profil des participants</h2>
                        <p class="mrri-page-description">Répartition des participants ayant accepté l’utilisation statistique de leur profil. Données agrégées, aucune information individuelle.</p>
                    </div>
                </div>
                <div class="st-chart-grid">
                    <?php foreach ([
                        'genre' => ['Répartition par genre', false],
                        'age' => ['Répartition par tranche d’âge', false],
                        'secteur' => ['Répartition par secteur d’activité', true],
                    ] as $cle => [$titre, $trier]): ?>
                        <?php $effectif = array_sum($donneesDemographiques[$cle]); ?>
                        <article class="mrri-panel st-chart-card st-chart-<?= $cle ?>">
                            <div class="mrri-panel-header st-chart-header">
                                <h3 class="mrri-panel-title"><?= htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="st-chart-effectif"><?= $effectif ?> participant<?= $effectif > 1 ? 's' : '' ?></span>
                            </div>
                            <div class="mrri-panel-body">
                                <?= $rendreGraphiqueBarres($titre, $labelsDemographiques[$cle], $donneesDemographiques[$cle], $trier) ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="st-chart-note">
                    Pourcentages calculés sur l’effectif de chaque graphique.
                    <?= VERIFICATION_EMAIL_CITOYEN
                        ? 'Seuls les profils dont l’adresse email a été vérifiée sont comptés.'
                        : 'Les adresses email ne sont pas vérifiées pour le moment : un même citoyen peut apparaître sous plusieurs adresses.' ?>
                </p>
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

<script>
// Courbes « Participation dans le temps » (par jour ou cumulées) : dessinées à la largeur
// réelle du conteneur (texte net), avec repère vertical + infobulle au survol et au clavier.
(function () {
    var conteneur = document.getElementById('st-courbe-activite');
    var source = document.getElementById('st-donnees-activite');
    if (!conteneur || !source) { return; }

    var points = JSON.parse(source.textContent);
    var infobulle = conteneur.querySelector('.st-infobulle');
    var sousTitre = document.getElementById('st-courbe-sous-titre');
    var NS = 'http://www.w3.org/2000/svg';
    var series = [
        { jour: 'contributions', cumul: 'cumul_contributions', libelle: 'Contributions', classe: 'st-c-contributions', couleur: '#3a3fb0' },
        { jour: 'votes', cumul: 'cumul_votes', libelle: 'Votes', classe: 'st-c-votes', couleur: '#e8211d' }
    ];
    var mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    var mode = 'jour';
    var indexActif = null;
    var geometrie = null;

    function valeur(p, s) { return mode === 'cumul' ? p[s.cumul] : p[s.jour]; }

    function dateCourte(iso) {
        var p = iso.split('-');
        return parseInt(p[2], 10) + ' ' + mois[parseInt(p[1], 10) - 1];
    }

    // Échelle verticale : pas entier « rond » pour 5 graduations environ (max 7 -> pas 2, axe 0-8)
    function echelle(maximum) {
        var brut = Math.max(1, maximum / 5);
        var puissance = Math.pow(10, Math.floor(Math.log10(brut)));
        var etapes = [1, 2, 2.5, 5, 10];
        var pas = 10 * puissance;
        for (var i = 0; i < etapes.length; i++) {
            var candidat = etapes[i] * puissance;
            if (candidat >= brut && Number.isInteger(candidat)) { pas = candidat; break; }
        }
        return { pas: pas, maxi: Math.max(pas, Math.ceil(maximum / pas) * pas) };
    }

    function element(nom, attributs, parent) {
        var el = document.createElementNS(NS, nom);
        for (var cle in attributs) { el.setAttribute(cle, attributs[cle]); }
        if (parent) { parent.appendChild(el); }
        return el;
    }

    function dessiner() {
        var ancien = conteneur.querySelector('svg');
        if (ancien) { ancien.remove(); }

        var largeur = conteneur.clientWidth;
        var hauteur = 280;
        var marge = { haut: 14, droite: 14, bas: 32, gauche: 40 };
        var largeurTrace = largeur - marge.gauche - marge.droite;
        var hauteurTrace = hauteur - marge.haut - marge.bas;
        var basTrace = marge.haut + hauteurTrace;
        var axe = echelle(Math.max.apply(null, points.map(function (p) {
            return Math.max(valeur(p, series[0]), valeur(p, series[1]));
        })));
        var x = function (i) {
            return marge.gauche + (points.length === 1 ? largeurTrace / 2 : largeurTrace * i / (points.length - 1));
        };
        var y = function (v) { return basTrace - hauteurTrace * v / axe.maxi; };

        var svg = element('svg', { width: largeur, height: hauteur, 'aria-hidden': 'true', class: 'st-courbe-svg' });
        conteneur.insertBefore(svg, infobulle);

        // Grille horizontale légère + graduations de l'axe vertical
        for (var v = 0; v <= axe.maxi; v += axe.pas) {
            var yg = y(v);
            if (v > 0) {
                element('line', { x1: marge.gauche, x2: largeur - marge.droite, y1: yg, y2: yg, class: 'st-grille' }, svg);
            }
            element('line', { x1: marge.gauche - 5, x2: marge.gauche, y1: yg, y2: yg, class: 'st-axe' }, svg);
            var texte = element('text', { x: marge.gauche - 9, y: yg + 4, 'text-anchor': 'end', class: 'st-axe-texte' }, svg);
            texte.textContent = v;
        }

        // Axes gauche et bas
        element('line', { x1: marge.gauche, x2: marge.gauche, y1: marge.haut, y2: basTrace, class: 'st-axe' }, svg);
        element('line', { x1: marge.gauche, x2: largeur - marge.droite, y1: basTrace, y2: basTrace, class: 'st-axe' }, svg);

        // Graduations de dates : une par point si la place le permet, une date lisible tous les ~70px
        var ecart = points.length > 1 ? largeurTrace / (points.length - 1) : largeurTrace;
        var pasDates = Math.max(1, Math.ceil(70 / ecart));
        points.forEach(function (p, i) {
            var avecDate = i % pasDates === 0;
            if (!avecDate && ecart < 6) { return; }
            element('line', { x1: x(i), x2: x(i), y1: basTrace, y2: basTrace + (avecDate ? 5 : 3), class: 'st-axe' }, svg);
            if (avecDate) {
                var ancre = x(i) + 26 > largeur ? 'end' : 'middle';
                var date = element('text', { x: x(i), y: hauteur - 8, 'text-anchor': ancre, class: 'st-axe-texte' }, svg);
                date.textContent = dateCourte(p.date);
            }
        });

        // Repère vertical (survol)
        var repere = element('line', { y1: marge.haut, y2: basTrace, class: 'st-repere', visibility: 'hidden' }, svg);

        // Courbes épaisses, sans marqueurs (les points n'apparaissent qu'au survol)
        var marqueurs = series.map(function (s) {
            var chemin = points.map(function (p, i) { return (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(valeur(p, s)).toFixed(1); }).join(' ');
            // Liseré blanc sous la courbe : les croisements restent nets
            // fill/stroke posés aussi en attributs : la courbe reste correcte même sans la feuille de style
            element('path', { d: chemin, class: 'st-ligne-halo', fill: 'none', stroke: '#fff', 'stroke-width': 6 }, svg);
            element('path', { d: chemin, class: 'st-ligne-serie ' + s.classe, fill: 'none', stroke: s.couleur, 'stroke-width': 3 }, svg);
            return element('circle', { r: 5, class: 'st-point ' + s.classe, fill: s.couleur, stroke: '#fff', 'stroke-width': 2, visibility: 'hidden' }, svg);
        });

        geometrie = {
            x: x, y: y, repere: repere, marqueurs: marqueurs,
            largeurTrace: largeurTrace, gauche: marge.gauche,
            milieu: marge.haut + hauteurTrace / 2, bas: basTrace
        };
        if (indexActif !== null) { activer(indexActif); }
    }

    function activer(i) {
        indexActif = Math.max(0, Math.min(points.length - 1, i));
        var p = points[indexActif];
        var px = geometrie.x(indexActif);
        geometrie.repere.setAttribute('x1', px);
        geometrie.repere.setAttribute('x2', px);
        geometrie.repere.setAttribute('visibility', 'visible');
        series.forEach(function (s, n) {
            geometrie.marqueurs[n].setAttribute('cx', px);
            geometrie.marqueurs[n].setAttribute('cy', geometrie.y(valeur(p, s)));
            geometrie.marqueurs[n].setAttribute('visibility', 'visible');
        });

        infobulle.innerHTML = '';
        var titre = document.createElement('div');
        titre.className = 'st-infobulle-titre';
        titre.textContent = <?= json_encode(!empty($activite) && $parSemaine ? 'Semaine du ' : '') ?> + dateCourte(p.date);
        infobulle.appendChild(titre);
        series.forEach(function (s) {
            var ligne = document.createElement('div');
            ligne.className = 'st-infobulle-ligne';
            ligne.innerHTML = '<span class="st-pastille st-pastille-ligne ' + s.classe + '"></span>';
            var libelle = document.createElement('span');
            libelle.textContent = s.libelle;
            var chiffre = document.createElement('strong');
            chiffre.textContent = valeur(p, s);
            var detail = document.createElement('span');
            detail.className = 'st-infobulle-detail';
            detail.textContent = mode === 'cumul' ? '+' + p[s.jour] : 'cumul ' + p[s.cumul];
            ligne.appendChild(libelle);
            ligne.appendChild(chiffre);
            ligne.appendChild(detail);
            infobulle.appendChild(ligne);
        });
        infobulle.hidden = false;
        var largeurBulle = infobulle.offsetWidth;
        var gauche = px + 12;
        if (gauche + largeurBulle > conteneur.clientWidth) { gauche = px - largeurBulle - 12; }
        infobulle.style.left = Math.max(0, gauche) + 'px';

        // La bulle se place dans la moitié opposée aux points pour ne pas masquer les courbes
        var yPoints = series.map(function (s) { return geometrie.y(valeur(p, s)); });
        var pointsEnHaut = Math.min.apply(null, yPoints) < geometrie.milieu;
        infobulle.style.top = pointsEnHaut ? (geometrie.bas - infobulle.offsetHeight - 8) + 'px' : '8px';
    }

    function desactiver() {
        indexActif = null;
        infobulle.hidden = true;
        if (!geometrie) { return; }
        geometrie.repere.setAttribute('visibility', 'hidden');
        geometrie.marqueurs.forEach(function (m) { m.setAttribute('visibility', 'hidden'); });
    }

    // Toute la hauteur du graphique sert de zone de survol : point le plus proche en x
    conteneur.addEventListener('pointermove', function (e) {
        if (!geometrie) { return; }
        var rect = conteneur.getBoundingClientRect();
        var relatif = (e.clientX - rect.left - geometrie.gauche) / geometrie.largeurTrace;
        activer(Math.round(relatif * (points.length - 1)));
    });
    conteneur.addEventListener('pointerleave', desactiver);
    conteneur.addEventListener('blur', desactiver);
    conteneur.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            e.preventDefault();
            var depart = indexActif === null ? (e.key === 'ArrowRight' ? -1 : points.length) : indexActif;
            activer(depart + (e.key === 'ArrowRight' ? 1 : -1));
        } else if (e.key === 'Escape') {
            desactiver();
        }
    });

    // Bascule « Par jour / Cumulé »
    document.querySelectorAll('.st-bascule button').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            mode = bouton.getAttribute('data-mode');
            document.querySelectorAll('.st-bascule button').forEach(function (b) {
                b.setAttribute('aria-pressed', b === bouton ? 'true' : 'false');
            });
            if (sousTitre) { sousTitre.textContent = sousTitre.getAttribute('data-' + mode); }
            dessiner();
        });
    });

    dessiner();
    if ('ResizeObserver' in window) {
        var largeurPrecedente = conteneur.clientWidth;
        new ResizeObserver(function () {
            if (conteneur.clientWidth !== largeurPrecedente) {
                largeurPrecedente = conteneur.clientWidth;
                dessiner();
            }
        }).observe(conteneur);
    }
})();
</script>

<?php require '../../includes/footer_dashboard.php'; ?>