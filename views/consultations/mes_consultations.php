<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
is_authenticated();

$role = $_SESSION['role'];

if ($role !== 'point_focal' && $role !== 'agent_ministere') {
    redirection_vers_les_dashboards($role);
    exit();
}

// Pour un point focal : sa propre structure (pas de choix)
$maStructure = null;
if ($role === 'point_focal') {
    $resultat = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
    $resultat->execute([':id_responsable' => $_SESSION['user_id']]);
    $maStructure = $resultat->fetch(PDO::FETCH_ASSOC);

    if (!$maStructure) {
        exit('Structure introuvable.');
    }
}
// Pour un agent du ministère : la liste de toutes les structures, pour le <select>
$toutesStructures = [];
if ($role === 'agent_ministere') {
    $toutesStructures = $db->query("SELECT id_struc, nom_struc FROM structure ORDER BY nom_struc")->fetchAll(PDO::FETCH_ASSOC);
}

$erreur = null;

$mes_consultations = [];
if ($role === 'point_focal') {
    $mes_consultations = $db->prepare("SELECT * FROM consultations WHERE id_createur = :id_createur ORDER BY date_ouverture DESC");
    $mes_consultations->execute([':id_createur' => $_SESSION['user_id']]);
    $mes_consultations = $mes_consultations->fetchAll(PDO::FETCH_ASSOC);
} elseif ($role === 'agent_ministere') {
    $mes_consultations = $db->prepare("SELECT * FROM consultations WHERE id_createur = :id_createur ORDER BY date_ouverture DESC");
    $mes_consultations->execute([':id_createur' => $_SESSION['user_id']]);
    $mes_consultations = $mes_consultations->fetchAll(PDO::FETCH_ASSOC);
}

require '../../includes/header_dashboard.php';
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
<h1>Mes consultations</h1>
<p>Liste des consultations</p>
<?php if (empty($mes_consultations)): ?>
    <p>Aucune consultation disponible.</p>
<?php else: ?>
    <?php foreach ($mes_consultations as $consultation): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($consultation['titre']) ?></h5>
                <p class="card-text"><?= htmlspecialchars($consultation['contenu']) ?></p>
                <p class="text-muted">Ouverte le <?= date('d/m/Y', strtotime($consultation['date_ouverture'])) ?></p>
            </div>
        </div>

        <form action="supprimer_consultations.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette consultation ?');">
            <input type="hidden" name="id_consultation" value="<?= $consultation['id_consultation'] ?>">
            <input type="hidden" name="csrf_token" value="<?= generer_token_csrf() ?>">
            <button type="submit" class="btn btn-danger">Supprimer</button>
        </form>
    <?php endforeach; ?>
<?php endif; ?>
<?php require '../../includes/footer_dashboard.php'; ?>
