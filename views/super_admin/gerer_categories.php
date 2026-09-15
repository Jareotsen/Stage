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

    if ($action === 'ajouter') {
        $nomCat = trim($_POST['nom_cat'] ?? '');
        if ($nomCat === '') {
            $erreur = "Le nom de la catégorie ne peut pas être vide.";
        } else {
            try {
                $ajouter = $db->prepare("INSERT INTO categories (nom_cat) VALUES (:nom_cat)");
                $ajouter->execute([':nom_cat' => $nomCat]);
                definir_flash("Catégorie ajoutée avec succès.");
                header("Location: gerer_categories.php");
                exit();
            } catch (PDOException $e) {
                $erreur = $e->getCode() === '23000' ? "Cette catégorie existe déjà." : "Une erreur est survenue lors de l'ajout.";
            }
        }
    } elseif ($action === 'supprimer') {
        $idCat = $_POST['id_cat'] ?? null;
        $compter = $db->prepare("SELECT COUNT(*) AS nb FROM structure WHERE id_cat = :id_cat");
        $compter->execute([':id_cat' => $idCat]);
        $nbStructures = $compter->fetch(PDO::FETCH_ASSOC)['nb'];

        if ($nbStructures > 0) {
            $erreur = "Impossible de supprimer : " . $nbStructures . " structure(s) utilisent encore cette catégorie.";
        } else {
            $supprimer = $db->prepare("DELETE FROM categories WHERE id_cat = :id_cat");
            $supprimer->execute([':id_cat' => $idCat]);
            definir_flash("Catégorie supprimée.");
            header("Location: gerer_categories.php");
            exit();
        }
    }
}

$categoriesStmt = $db->query("
    SELECT categories.id_cat, categories.nom_cat, COUNT(structure.id_struc) AS nb_structures
    FROM categories
    LEFT JOIN structure ON structure.id_cat = categories.id_cat
    GROUP BY categories.id_cat, categories.nom_cat
    ORDER BY categories.nom_cat
");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>

<main class="container my-4" style="max-width: 560px;">
    <?php afficher_flash(); ?>
    <h1 class="h3">Gestion des catégories</h1>
    <p><a href="dashboard.php" class="link-secondary">← Retour au tableau de bord</a></p>

    <?php if ($erreur): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h5">Ajouter une catégorie</h2>
            <form action="" method="post" class="d-flex gap-2 align-items-end">
                <input type="hidden" name="action" value="ajouter">
                <div class="flex-grow-1">
                    <label class="form-label">Nom de la catégorie</label>
                    <input type="text" name="nom_cat" class="form-control" placeholder="Ex. : Autorité Administrative Indépendante" required>
                </div>
                <button type="submit" class="btn btn-mrri">Ajouter</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5">Catégories existantes</h2>
            <?php if (empty($categories)): ?><p class="text-secondary">Aucune catégorie pour le moment.</p><?php endif; ?>

            <?php foreach ($categories as $cat): ?>
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div><?= htmlspecialchars($cat['nom_cat']) ?> <span class="small text-secondary"><?= $cat['nb_structures'] ?> structure(s)</span></div>
                    <form action="" method="post" onsubmit="return confirm('Supprimer cette catégorie ?');">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_cat" value="<?= $cat['id_cat'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php require '../../includes/footer.php'; ?>