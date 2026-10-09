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
