<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'super_admin') {
    redirection_vers_les_dashboards($_SESSION['role']);
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

require '../../includes/header.php';
?>

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

<?php require '../../includes/footer.php'; ?>