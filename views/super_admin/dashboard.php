<?php 
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'super_admin') {
    redirection_vers_les_dashboards($_SESSION['role']);
}

$struc = $db->prepare("SELECT structure.nom_struc, structure.id_struc FROM structure");
$struc->execute();
$structures = $struc->fetchAll(PDO::FETCH_ASSOC);

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $structure = $_POST['structure'] ?? null;

    if ($role === 'point_focal' && !$structure) {
        $erreur = "Veuillez sélectionner une structure pour le point focal.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt1 = $db->prepare("INSERT INTO utilisateurs (username, password, role) VALUES (:username, :password, :role)");
        $stmt1->execute([':username' => $username, ':password' => $hashedPassword, ':role' => $role]);
        $nouvelId = $db->lastInsertId();

        if ($role === 'point_focal') {
            $stmt2 = $db->prepare("UPDATE structure SET id_responsable = :id WHERE id_struc = :id_struc");
            $stmt2->execute([':id' => $nouvelId, ':id_struc' => $structure]);
        }

        definir_flash("Compte créé avec succès.");
        header("Location: dashboard.php");
        exit();
    }
}

require '../../includes/header.php';
?>

<main class="container my-4" style="max-width: 560px;">
    <?php afficher_flash(); ?>
    <h1 class="h3">Tableau de bord — Super administrateur</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5">Créer un compte</h2>
            <?php if ($erreur): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

            <form action="" method="post">
                <div class="mb-3"><label class="form-label">Identifiant</label><input type="text" name="username" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Mot de passe</label><input type="password" name="password" class="form-control"></div>
                <div class="mb-3">
                    <label class="form-label">Rôle</label>
                    <select name="role" id="role" class="form-select">
                        <option value="point_focal">Point Focal</option>
                        <option value="agent_ministere">Agent Ministère</option>
                    </select>
                </div>
                <div class="mb-3" id="champ_structure">
                    <label class="form-label">Structure rattachée</label>
                    <select name="structure" class="form-select">
                        <?php foreach ($structures as $s): ?>
                            <option value="<?= $s['id_struc'] ?>"><?= htmlspecialchars($s['nom_struc']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-mrri">Créer le compte</button>
            </form>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <a href="gerer_compte.php" class="btn btn-outline-success">Gérer les comptes</a>
        <a href="gerer_categories.php" class="btn btn-outline-success">Gérer les catégories</a>
    </div>
</main>

<script>
    let champStructure = document.getElementById('champ_structure');
    let role = document.getElementById('role');
    role.addEventListener('change', function() {
        champStructure.style.display = role.value === 'point_focal' ? 'block' : 'none';
    });
</script>

<?php require '../../includes/footer.php'; ?>