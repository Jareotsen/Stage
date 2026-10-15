<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
is_authenticated();

// Seul l'agent du ministère (rôle modérateur) accède à cette page
if ($_SESSION['role'] !== 'agent_ministere') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

/* =========================================================
   TRAITEMENT : valider ou rejeter une contribution
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
        exit('Requête invalide.');
    }

    // Liste blanche : on ne copie JAMAIS une valeur du formulaire dans la base
    $decisions = ['valider' => 'validee', 'rejeter' => 'rejetee'];
    $action = $_POST['action'] ?? '';
    $idContribution = (int) ($_POST['id_contribution'] ?? 0);

    if ($idContribution <= 0 || !isset($decisions[$action])) {
        definir_flash("Action invalide.", 'erreur');
    } else {
        // "AND statut = 'en_attente'" : empêche de retraiter une contribution
        // déjà décidée (double clic, ou deux modérateurs en même temps)
        $maj = $db->prepare(
            "UPDATE contributions
             SET statut = :statut, id_moderateur = :id_moderateur, date_moderation = NOW()
             WHERE id_contribution = :id AND statut = 'en_attente'"
        );
        $maj->execute([
            ':statut' => $decisions[$action],
            ':id_moderateur' => $_SESSION['user_id'],
            ':id' => $idContribution,
        ]);

        if ($maj->rowCount() === 1) {
            definir_flash($action === 'valider'
                ? "Contribution validée : elle est maintenant visible du public."
                : "Contribution rejetée.");
        } else {
            definir_flash("Cette contribution a déjà été traitée.", 'erreur');
        }
    }

    header('Location: moderation.php');
    exit();
}

/* =========================================================
   FILTRE PAR STATUT (liste blanche)
   ========================================================= */
$statuts = [
    'en_attente' => 'En attente',
    'validee'    => 'Validées',
    'rejetee'    => 'Rejetées',
];
$filtre = $_GET['statut'] ?? 'en_attente';
if (!isset($statuts[$filtre])) {
    $filtre = 'en_attente';
}

// Compteurs pour les onglets
$comptes = array_fill_keys(array_keys($statuts), 0);
foreach ($db->query("SELECT statut, COUNT(*) AS nb FROM contributions GROUP BY statut")->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
    if (isset($comptes[$ligne['statut']])) {
        $comptes[$ligne['statut']] = (int) $ligne['nb'];
    }
}

/* =========================================================
   LISTE (les plus anciennes d'abord pour la file d'attente)
   ========================================================= */
$ordre = $filtre === 'en_attente' ? 'ASC' : 'DESC';
$contribStmt = $db->prepare(
    "SELECT c.id_contribution, c.contenu, c.email_contributeur, c.statut,
            c.date_contribution, c.date_moderation,
            co.titre AS titre_consultation,
            u.username AS moderateur
     FROM contributions c
     JOIN consultations co ON co.id_consultation = c.id_consultation
     LEFT JOIN utilisateurs u ON u.id = c.id_moderateur
     WHERE c.statut = :statut
     ORDER BY c.date_contribution $ordre
     LIMIT 50"
);
$contribStmt->execute([':statut' => $filtre]);
$contributions = $contribStmt->fetchAll(PDO::FETCH_ASSOC);

$token = generer_token_csrf();

require '../../includes/header_dashboard.php';
?>

<link rel="stylesheet" href="../../publique/css/theme.css">

<div class="mrri-dashboard dashboard-agent">
<?php $pageActive = 'moderation'; require '../../includes/sidebar_agent.php'; ?>

    <main class="mrri-main">
        <header class="mrri-topbar">
            <div class="mrri-topbar-title">
                MRRI / <strong>Modération</strong>
            </div>
            <div class="mrri-user">
                <div class="mrri-user-avatar">AM</div>
                <div class="mrri-user-info">
                    <div class="mrri-user-name">Agent du ministère</div>
                    <div class="mrri-user-role">Modérateur</div>
                </div>
            </div>
        </header>

        <section class="mrri-content">

            <?php afficher_flash(); ?>

            <div class="mrri-page-header">
                <div>
                    <div class="mrri-breadcrumb">Espace professionnel / <span class="active">Modération</span></div>
                    <h1 class="mrri-page-title">Modération des contributions</h1>
                    <p class="mrri-page-description">
                        Seules les contributions validées sont visibles par les citoyens.
                    </p>
                </div>
            </div>

            <!-- Onglets de filtre -->
            <nav class="mod-onglets" aria-label="Filtrer par statut">
                <?php foreach ($statuts as $cle => $libelle): ?>
                    <a href="moderation.php?statut=<?= $cle ?>" class="mod-onglet <?= $filtre === $cle ? 'actif' : '' ?>">
                        <?= htmlspecialchars($libelle) ?>
                        <span class="nb"><?= $comptes[$cle] ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if (empty($contributions)): ?>
                <div class="mod-vide">
                    <?= $filtre === 'en_attente'
                        ? "Aucune contribution en attente de modération."
                        : "Aucune contribution dans cette catégorie." ?>
                </div>
            <?php endif; ?>

            <?php foreach ($contributions as $c): ?>
                <article class="mod-carte">
                    <div class="mod-meta">
                        Consultation : <strong><?= htmlspecialchars($c['titre_consultation']) ?></strong>
                        · <?= htmlspecialchars($c['email_contributeur']) ?>
                        · déposée le <?= htmlspecialchars($c['date_contribution']) ?>
                    </div>

                    <div class="mod-texte"><?= nl2br(htmlspecialchars($c['contenu'])) ?></div>

                    <?php if ($c['statut'] === 'en_attente'): ?>
                        <form action="" method="post" class="mod-actions"
                              onsubmit="return confirm('Confirmer cette décision ?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                            <input type="hidden" name="id_contribution" value="<?= (int) $c['id_contribution'] ?>">
                            <button type="submit" name="action" value="valider" class="mod-btn valider">✓ Valider</button>
                            <button type="submit" name="action" value="rejeter" class="mod-btn rejeter">✕ Rejeter</button>
                        </form>
                    <?php else: ?>
                        <div class="mod-trace">
                            <?= $c['statut'] === 'validee' ? 'Validée' : 'Rejetée' ?>
                            <?php if (!empty($c['moderateur'])): ?>par <strong><?= htmlspecialchars($c['moderateur']) ?></strong><?php endif; ?>
                            <?php if (!empty($c['date_moderation'])): ?>le <?= htmlspecialchars($c['date_moderation']) ?><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>

        </section>
    </main>
</div>

<?php require '../../includes/footer_dashboard.php'; ?>