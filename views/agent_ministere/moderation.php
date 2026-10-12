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

<style>
    .mod-onglets { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
    .mod-onglet { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border: 1px solid #E4E7EC; border-radius: 999px; background: #fff; color: #667085; font-size: .85rem; font-weight: 600; text-decoration: none; }
    .mod-onglet:hover { border-color: #1B3FAE; color: #1B3FAE; }
    .mod-onglet.actif { background: #1B3FAE; border-color: #1B3FAE; color: #fff; }
    .mod-onglet .nb { background: rgba(0,0,0,.08); border-radius: 999px; padding: 1px 8px; font-size: .75rem; }
    .mod-onglet.actif .nb { background: rgba(255,255,255,.25); }

    .mod-carte { background: #fff; border: 1px solid #E4E7EC; border-radius: 10px; padding: 18px 20px; margin-bottom: 14px; }
    .mod-meta { color: #667085; font-size: .78rem; margin-bottom: 8px; }
    .mod-meta strong { color: #172033; }
    .mod-texte { line-height: 1.6; margin-bottom: 14px; white-space: pre-line; }
    .mod-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .mod-btn { padding: 8px 18px; border-radius: 7px; font-weight: 700; font-size: .82rem; border: 1px solid; background: #fff; cursor: pointer; }
    .mod-btn.valider { border-color: #0F8A4B; color: #0F8A4B; }
    .mod-btn.valider:hover { background: #0F8A4B; color: #fff; }
    .mod-btn.rejeter { border-color: #c0392b; color: #c0392b; }
    .mod-btn.rejeter:hover { background: #c0392b; color: #fff; }
    .mod-trace { color: #667085; font-size: .78rem; }
    .mod-vide { text-align: center; padding: 2.5rem 1rem; color: #667085; border: 1px dashed #E4E7EC; border-radius: 10px; }
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
                <a href="dashboard.php" class="mrri-sidebar-link">
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
                <a href="moderation.php" class="mrri-sidebar-link active">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                    </span>
                    Modération
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