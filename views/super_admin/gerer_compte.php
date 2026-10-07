<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
is_authenticated();

if ($_SESSION['role'] !== 'super_admin') {
    redirection_vers_les_dashboards($_SESSION['role']);
}



$erreur = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
    exit('Requête invalide.');
}
    $action = $_POST['action'] ?? '';
    $idCible = $_POST['id'] ?? null;
    $verif = $db->prepare("SELECT role FROM utilisateurs WHERE id = :id");
    $verif->execute([':id' => $idCible]);
    $compteCible = $verif->fetch(PDO::FETCH_ASSOC);
    if (!$compteCible || $compteCible['role'] === 'super_admin') {
        $erreur = "Action non autorisée sur ce compte.";
    } elseif ($action === 'supprimer') {
        $detacher = $db->prepare("UPDATE structure SET id_responsable = NULL WHERE id_responsable = :id");
        $detacher->execute([':id' => $idCible]);
        $supprimer = $db->prepare("DELETE FROM utilisateurs WHERE id = :id");
        $supprimer->execute([':id' => $idCible]);
        definir_flash("Compte supprimé.");
        header("Location: gerer_comptes.php");
        exit();
    } elseif ($action === 'modifier') {
        $nouveauRole = $_POST['role'] ?? '';
        $nouvelleStructure = $_POST['structure'] ?? null;

        $majRole = $db->prepare("UPDATE utilisateurs SET role = :role WHERE id = :id");
        $majRole->execute([':role' => $nouveauRole, ':id' => $idCible]);

        $detacher = $db->prepare("UPDATE structure SET id_responsable = NULL WHERE id_responsable = :id");
        $detacher->execute([':id' => $idCible]);

        if ($nouveauRole === 'point_focal' && $nouvelleStructure) {
            $rattacher = $db->prepare("UPDATE structure SET id_responsable = :id WHERE id_struc = :id_struc");
            $rattacher->execute([':id' => $idCible, ':id_struc' => $nouvelleStructure]);
        }
        definir_flash("Compte mis à jour.");
        header("Location: gerer_comptes.php");
        exit();
    }
}
$comptesStmt = $db->query("
    SELECT utilisateurs.id, utilisateurs.username, utilisateurs.role, structure.nom_struc, structure.id_struc
    FROM utilisateurs
    LEFT JOIN structure ON structure.id_responsable = utilisateurs.id
    WHERE utilisateurs.role != 'super_admin'
    ORDER BY utilisateurs.role, utilisateurs.username
");
$comptes = $comptesStmt->fetchAll(PDO::FETCH_ASSOC);

$structuresStmt = $db->query("SELECT id_struc, nom_struc FROM structure ORDER BY nom_struc");
$toutesLesStructures = $structuresStmt->fetchAll(PDO::FETCH_ASSOC);

require '../../includes/header_dashboard.php';
?>
<aside class="mrri-sidebar">
        <div class="mrri-sidebar-brand">
            <img src="../../publique/image/sceau.png" alt="Armoiries du Gabon">
            <div>
                <strong>Ministère de la Réforme<br>et des Institutions</strong>
                <small>Super administrateur</small>
            </div>
        </div>

        <div class="mrri-sidebar-section">Navigation</div>
        <ul class="mrri-sidebar-nav">
            <li>
                <a href="dashboard.php" class="mrri-sidebar-link active">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    </span>
                    Tableau de bord
                </a>
            </li>
            <li>
                <a href="gerer_compte.php" class="mrri-sidebar-link">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    Comptes utilisateurs
                </a>
            </li>
            <li>
                <a href="gerer_categories.php" class="mrri-sidebar-link">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h13a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4z"/></svg>
                    </span>
                    Catégories
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
<main class="container my-4" style="max-width: 780px;">
    <?php afficher_flash(); ?>
    <h1 class="h3">Gestion des comptes</h1>
    <p><a href="dashboard.php" class="link-secondary">← Retour au tableau de bord</a></p>
     
    <?php if ($erreur): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($comptes)): ?><p class="text-secondary">Aucun compte à gérer pour le moment.</p><?php endif; ?>
            
            <?php foreach ($comptes as $compte): ?>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom py-3">
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars($compte['username']) ?></div>
                        <span class="badge <?= $compte['role'] === 'point_focal' ? 'bg-secondary-subtle text-secondary' : 'bg-warning-subtle text-warning-emphasis' ?>">
                           <?= $compte['role'] === 'point_focal' ? 'Point focal' : 'Agent du Ministère' ?>
                        </span>
                        <?php if ($compte['nom_struc']): ?>
                            <span class="small text-secondary">Rattaché à : <?= htmlspecialchars($compte['nom_struc']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <form action="" method="post" class="d-flex align-items-center gap-1">
                            <input type="hidden" name="action" value="modifier">
                            <input type="hidden" name="id" value="<?= $compte['id'] ?>">
                            <select name="role" class="form-select form-select-sm" onchange="basculerStructure(<?= $compte['id'] ?>, this.value)">
                                <option value="point_focal" <?= $compte['role'] === 'point_focal' ? 'selected' : '' ?>>Point focal</option>
                                <option value="agent_ministere" <?= $compte['role'] === 'agent_ministere' ? 'selected' : '' ?>>Agent Ministère</option>
                            </select>
                            <select name="structure" id="structure-select-<?= $compte['id'] ?>" class="form-select form-select-sm" style="display: <?= $compte['role'] === 'point_focal' ? 'inline-block' : 'none' ?>;">
                                <?php foreach ($toutesLesStructures as $s): ?>
                                    <option value="<?= $s['id_struc'] ?>" <?= $s['id_struc'] == $compte['id_struc'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nom_struc']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline-success btn-sm">Enregistrer</button>
                        </form>
                         
                        <form action="" method="post" onsubmit="return confirm('Supprimer définitivement ce compte ?');">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id" value="<?= $compte['id'] ?>">
                             <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script>
function basculerStructure(id, role) {
    document.getElementById('structure-select-' + id).style.display = role === 'point_focal' ? 'inline-block' : 'none';
}
</script>

<?php require '../../includes/footer_dashboard.php'; ?>
