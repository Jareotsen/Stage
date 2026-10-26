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

    // Changement de son propre mot de passe : le mot de passe actuel est exigé
    if ($action === 'mon_mot_de_passe') {
        $actuel = (string) ($_POST['mot_de_passe_actuel'] ?? '');
        $nouveau = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
        $confirmation = (string) ($_POST['confirmation'] ?? '');

        $moi = $db->prepare("SELECT password FROM utilisateurs WHERE id = :id");
        $moi->execute([':id' => $_SESSION['user_id']]);
        $hashActuel = (string) $moi->fetchColumn();

        if (!password_verify($actuel, $hashActuel)) {
            definir_flash("Mot de passe actuel incorrect.", 'erreur');
        } elseif (mb_strlen($nouveau) < 8) {
            definir_flash("Le nouveau mot de passe doit contenir au moins 8 caractères.", 'erreur');
        } elseif ($nouveau !== $confirmation) {
            definir_flash("La confirmation ne correspond pas au nouveau mot de passe.", 'erreur');
        } else {
            $maj = $db->prepare("UPDATE utilisateurs SET password = :password WHERE id = :id");
            $maj->execute([':password' => password_hash($nouveau, PASSWORD_DEFAULT), ':id' => $_SESSION['user_id']]);
            definir_flash("Votre mot de passe a été modifié.");
        }
        header("Location: gerer_compte.php");
        exit();
    }

    $verif = $db->prepare("SELECT role FROM utilisateurs WHERE id = :id");
    $verif->execute([':id' => $idCible]);
    $compteCible = $verif->fetch(PDO::FETCH_ASSOC);
    if (!$compteCible || $compteCible['role'] === 'super_admin') {
        $erreur = "Action non autorisée sur ce compte.";
   } elseif ($action === 'supprimer') {
    try {
        $db->beginTransaction();

        $detacher = $db->prepare("UPDATE structure SET id_responsable = NULL WHERE id_responsable = :id");
        $detacher->execute([':id' => $idCible]);

        $supprimer = $db->prepare("DELETE FROM utilisateurs WHERE id = :id");
        $supprimer->execute([':id' => $idCible]);

        $db->commit();
        definir_flash("Compte supprimé.");
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();   // annule aussi le détachement de la structure
        }
        if (($e->errorInfo[1] ?? null) === 1451) {
            definir_flash("Suppression impossible : ce compte est lié à des demandes ou à d'autres données qui doivent être conservées.", 'erreur');
        } else {
            error_log("Suppression compte : " . $e->getMessage());
            definir_flash("Impossible de supprimer le compte. Veuillez réessayer.", 'erreur');
        }
    }
    header("Location: gerer_compte.php");
    exit();
} elseif ($action === 'modifier') {
        $nouveauRole = in_array($_POST['role'] ?? '', ['point_focal', 'agent_ministere'], true) ? $_POST['role'] : '';
        if ($nouveauRole === '') {
            exit('Rôle invalide.');
        }
        $nouvelleStructure = filter_var($_POST['structure'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        // Structure visée : doit exister ; on note son responsable actuel pour prévenir l'administrateur
        $ancienResponsable = null;
        if ($nouveauRole === 'point_focal') {
            $verifStructure = $db->prepare(
                "SELECT s.id_struc, u.username AS responsable, s.id_responsable
                 FROM structure s LEFT JOIN utilisateurs u ON u.id = s.id_responsable
                 WHERE s.id_struc = :id"
            );
            $verifStructure->execute([':id' => $nouvelleStructure ?: 0]);
            $structureVisee = $verifStructure->fetch(PDO::FETCH_ASSOC);
            if (!$structureVisee) {
                definir_flash("Choisissez une structure valide pour un point focal.", 'erreur');
                header("Location: gerer_compte.php");
                exit();
            }
            if (!empty($structureVisee['id_responsable']) && (int) $structureVisee['id_responsable'] !== (int) $idCible) {
                $ancienResponsable = $structureVisee['responsable'];
            }
        }

        // Rôle + détachement + rattachement : tout ou rien
        $db->beginTransaction();

        $majRole = $db->prepare("UPDATE utilisateurs SET role = :role WHERE id = :id");
        $majRole->execute([':role' => $nouveauRole, ':id' => $idCible]);

        $detacher = $db->prepare("UPDATE structure SET id_responsable = NULL WHERE id_responsable = :id");
        $detacher->execute([':id' => $idCible]);

        if ($nouveauRole === 'point_focal') {
            $rattacher = $db->prepare("UPDATE structure SET id_responsable = :id WHERE id_struc = :id_struc");
            $rattacher->execute([':id' => $idCible, ':id_struc' => $nouvelleStructure]);
        }

        $db->commit();

        definir_flash($ancienResponsable !== null
            ? "Compte mis à jour. Attention : « {$ancienResponsable} » n'est plus rattaché à cette structure."
            : "Compte mis à jour.", $ancienResponsable !== null ? 'warning' : 'succes');
        header("Location: gerer_compte.php");
        exit();
    } elseif ($action === 'mot_de_passe') {
        $nouveauMotDePasse = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
        if (mb_strlen($nouveauMotDePasse) < 8) {
            definir_flash("Le nouveau mot de passe doit contenir au moins 8 caractères.", 'erreur');
        } else {
            $majMotDePasse = $db->prepare("UPDATE utilisateurs SET password = :password WHERE id = :id");
            $majMotDePasse->execute([
                ':password' => password_hash($nouveauMotDePasse, PASSWORD_DEFAULT),
                ':id' => $idCible,
            ]);
            definir_flash("Mot de passe réinitialisé. Communiquez-le à l'utilisateur par un canal sûr.");
        }
        header("Location: gerer_compte.php");
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
<?php $pageActive = 'comptes'; require '../../includes/sidebar_super_admin.php'; ?>
<main class="container my-4" style="max-width: 780px;">
    <?php afficher_flash(); ?>
    <h1 class="h3">Gestion des comptes</h1>
    <p><a href="dashboard.php" class="link-secondary">← Retour au tableau de bord</a></p>
     
    <?php if ($erreur): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Mon mot de passe</h2>
            <form action="" method="post" class="row g-2 align-items-end">
                <input type="hidden" name="action" value="mon_mot_de_passe">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">
                <div class="col-md-4">
                    <label class="form-label small" for="mot_de_passe_actuel">Mot de passe actuel</label>
                    <input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel" class="form-control form-control-sm" required autocomplete="current-password">
                </div>
                <div class="col-md-3">
                    <label class="form-label small" for="nouveau_mdp">Nouveau (8 car. min.)</label>
                    <input type="password" id="nouveau_mdp" name="nouveau_mot_de_passe" class="form-control form-control-sm" minlength="8" required autocomplete="new-password">
                </div>
                <div class="col-md-3">
                    <label class="form-label small" for="confirmation">Confirmation</label>
                    <input type="password" id="confirmation" name="confirmation" class="form-control form-control-sm" minlength="8" required autocomplete="new-password">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-mrri btn-sm w-100">Modifier</button>
                </div>
            </form>
        </div>
    </div>

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
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">
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
                         
                        <form action="" method="post" class="d-flex align-items-center gap-1"
                              onsubmit="return confirm('Remplacer le mot de passe de ce compte ?');">
                            <input type="hidden" name="action" value="mot_de_passe">
                            <input type="hidden" name="id" value="<?= (int) $compte['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">
                            <input type="password" name="nouveau_mot_de_passe" class="form-control form-control-sm"
                                   placeholder="Nouveau mot de passe" minlength="8" required autocomplete="new-password"
                                   aria-label="Nouveau mot de passe pour <?= htmlspecialchars($compte['username']) ?>" style="width: 170px;">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Réinitialiser</button>
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
