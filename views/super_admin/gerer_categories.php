<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
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

    if ($action === 'ajouter') {
        $nomCat = trim($_POST['nom_cat'] ?? '');
        // Catégorie parente facultative : doit être une catégorie racine existante
        $parentId = filter_var($_POST['parent_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        $parentValide = true;
        if ($parentId !== null) {
            $verifParent = $db->prepare("SELECT COUNT(*) FROM categories WHERE id_cat = :id AND parent_id IS NULL");
            $verifParent->execute([':id' => $parentId]);
            $parentValide = (int) $verifParent->fetchColumn() === 1;
        }

        if ($nomCat === '') {
            $erreur = "Le nom de la catégorie ne peut pas être vide.";
        } elseif (mb_strlen($nomCat) > 150) {
            $erreur = "Le nom de la catégorie ne doit pas dépasser 150 caractères.";
        } elseif (!$parentValide) {
            $erreur = "La catégorie parente choisie est invalide.";
        } else {
            try {
                $ajouter = $db->prepare("INSERT INTO categories (nom_cat, parent_id) VALUES (:nom_cat, :parent_id)");
                $ajouter->execute([':nom_cat' => $nomCat, ':parent_id' => $parentId]);
                definir_flash("Catégorie ajoutée avec succès.");
                header("Location: gerer_categories.php");
                exit();
            } catch (PDOException $e) {
                $erreur = $e->getCode() === '23000' ? "Cette catégorie existe déjà." : "Une erreur est survenue lors de l'ajout.";
            }
        }
    } elseif ($action === 'renommer') {
        $idCat = (int) ($_POST['id_cat'] ?? 0);
        $nouveauNom = trim($_POST['nom_cat'] ?? '');

        $verifCategorie = $db->prepare("SELECT nom_cat FROM categories WHERE id_cat = :id_cat");
        $verifCategorie->execute([':id_cat' => $idCat]);
        $ancienNom = $verifCategorie->fetchColumn();

        if ($ancienNom === false) {
            $erreur = "Cette catégorie n'existe pas.";
        } elseif ($nouveauNom === '') {
            $erreur = "Le nom de la catégorie ne peut pas être vide.";
        } elseif (mb_strlen($nouveauNom) > 150) {
            $erreur = "Le nom de la catégorie ne doit pas dépasser 150 caractères.";
        } elseif ($nouveauNom === $ancienNom) {
            definir_flash("Le nom de la catégorie est inchangé.", 'warning');
            header("Location: gerer_categories.php");
            exit();
        } else {
            try {
                $renommer = $db->prepare("UPDATE categories SET nom_cat = :nom_cat WHERE id_cat = :id_cat");
                $renommer->execute([':nom_cat' => $nouveauNom, ':id_cat' => $idCat]);
                definir_flash("Catégorie « " . $ancienNom . " » renommée en « " . $nouveauNom . " ».");
                header("Location: gerer_categories.php");
                exit();
            } catch (PDOException $e) {
                // nom_cat est unique en base : deux catégories ne peuvent pas porter le même nom
                if ($e->getCode() === '23000') {
                    $erreur = "Une autre catégorie porte déjà ce nom.";
                } else {
                    error_log("Renommage catégorie : " . $e->getMessage());
                    $erreur = "Une erreur est survenue lors du renommage.";
                }
            }
        }
    } elseif ($action === 'supprimer') {
        $idCat = (int) ($_POST['id_cat'] ?? 0);
        $compter = $db->prepare("SELECT COUNT(*) AS nb FROM structure WHERE id_cat = :id_cat");
        $compter->execute([':id_cat' => $idCat]);
        $nbStructures = $compter->fetch(PDO::FETCH_ASSOC)['nb'];

        // Une catégorie parente ne peut pas disparaître tant qu'elle a des sous-catégories
        $compterEnfants = $db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = :id_cat");
        $compterEnfants->execute([':id_cat' => $idCat]);
        $nbSousCategories = (int) $compterEnfants->fetchColumn();

        if ($nbStructures > 0) {
            $erreur = "Impossible de supprimer : " . $nbStructures . " structure(s) utilisent encore cette catégorie.";
        } elseif ($nbSousCategories > 0) {
            $erreur = "Impossible de supprimer : cette catégorie contient " . $nbSousCategories . " sous-catégorie(s). Supprimez-les d'abord.";
        } else {
            try {
                $supprimer = $db->prepare("DELETE FROM categories WHERE id_cat = :id_cat");
                $supprimer->execute([':id_cat' => $idCat]);
                definir_flash("Catégorie supprimée.");
                header("Location: gerer_categories.php");
                exit();
            } catch (PDOException $e) {
                error_log("Suppression catégorie : " . $e->getMessage());
                $erreur = "Impossible de supprimer cette catégorie : elle est encore utilisée.";
            }
        }
    }
}

$categoriesStmt = $db->query("
    SELECT categories.id_cat, categories.nom_cat, categories.parent_id, parent.nom_cat AS nom_parent,
           (SELECT COUNT(*) FROM structure WHERE structure.id_cat = categories.id_cat) AS nb_structures,
           (SELECT COUNT(*) FROM categories enfant WHERE enfant.parent_id = categories.id_cat) AS nb_sous_categories
    FROM categories
    LEFT JOIN categories parent ON parent.id_cat = categories.parent_id
    ORDER BY COALESCE(parent.nom_cat, categories.nom_cat), categories.parent_id IS NOT NULL, categories.nom_cat
");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
$categoriesRacines = array_filter($categories, static fn($c) => $c['parent_id'] === null);

require '../../includes/header_dashboard.php';
?>
<?php $pageActive = 'categories'; require '../../includes/sidebar_super_admin.php'; ?>
<main class="container my-4" style="max-width: 560px;">
    <?php afficher_flash(); ?>
    <h1 class="h3">Gestion des catégories</h1>
    <p><a href="dashboard.php" class="link-secondary">← Retour au tableau de bord</a></p>

    <?php if ($erreur): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h5">Ajouter une catégorie</h2>
            <form action="" method="post">
                <input type="hidden" name="action" value="ajouter">
                <div class="mb-3">
                    <label class="form-label" for="nom_cat">Nom de la catégorie</label>
                    <input type="text" id="nom_cat" name="nom_cat" class="form-control" maxlength="150"
                           placeholder="Ex. : Autorité Administrative Indépendante" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="parent_id">Catégorie parente (facultatif)</label>
                    <select id="parent_id" name="parent_id" class="form-select">
                        <option value="">Aucune — catégorie principale</option>
                        <?php foreach ($categoriesRacines as $racine): ?>
                            <option value="<?= (int) $racine['id_cat'] ?>"><?= htmlspecialchars($racine['nom_cat']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Une sous-catégorie apparaît sous sa catégorie parente dans le menu « Structures ».</div>
                </div>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">
                <button type="submit" class="btn btn-mrri">Ajouter</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5">Catégories existantes</h2>
            <?php if (empty($categories)): ?><p class="text-secondary">Aucune catégorie pour le moment.</p><?php endif; ?>

            <?php foreach ($categories as $cat): ?>
                <div class="border-bottom py-2">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div<?= $cat['parent_id'] !== null ? ' class="ps-4"' : '' ?>>
                            <?= $cat['parent_id'] !== null ? '<span class="text-secondary" aria-hidden="true">↳ </span>' : '' ?>
                            <?= htmlspecialchars($cat['nom_cat']) ?>
                            <span class="small text-secondary">
                                <?= (int) $cat['nb_structures'] ?> structure(s)<?= (int) $cat['nb_sous_categories'] > 0 ? ' · ' . (int) $cat['nb_sous_categories'] . ' sous-catégorie(s)' : '' ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="collapse" data-bs-target="#renommer-<?= (int) $cat['id_cat'] ?>"
                                    aria-expanded="false" aria-controls="renommer-<?= (int) $cat['id_cat'] ?>">Renommer</button>
                            <form action="" method="post" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id_cat" value="<?= $cat['id_cat'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
                            </form>
                        </div>
                    </div>
                    <form action="" method="post" id="renommer-<?= (int) $cat['id_cat'] ?>" class="collapse mt-2">
                        <input type="hidden" name="action" value="renommer">
                        <input type="hidden" name="id_cat" value="<?= (int) $cat['id_cat'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">
                        <div class="d-flex gap-2">
                            <input type="text" name="nom_cat" class="form-control form-control-sm" maxlength="150" required
                                   value="<?= htmlspecialchars($cat['nom_cat']) ?>"
                                   aria-label="Nouveau nom pour la catégorie <?= htmlspecialchars($cat['nom_cat']) ?>">
                            <button type="submit" class="btn btn-mrri btn-sm">Enregistrer</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php require '../../includes/footer_dashboard.php'; ?>