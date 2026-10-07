<?php 
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
require_once '../../includes/verif_doc.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}


$resultat = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
$resultat->execute([':id_responsable' => $_SESSION['user_id']]);
$structures = $resultat->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 

    if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
        exit('Requête invalide.');
    }

    $titre = $_POST['nom_affichage'] ?? '';
    $visibility = $_POST['visibility'] ?? 'public';
    $id_structure = $structures['id_struc'];

     $fichier_valide = fichier_est_valide($_FILES['documents']['tmp_name'], $_FILES['documents']['name']);
    if (isset($_FILES['documents']) && $_FILES['documents']['error'] === UPLOAD_ERR_OK) {

    if (!fichier_est_valide($_FILES['documents']['tmp_name'], $_FILES['documents']['name'])) {
        $erreur = "Type de fichier non autorisé.";
    } else {
        $extension = pathinfo($_FILES['documents']['name'], PATHINFO_EXTENSION);
        $nomFichierServeur = uniqid() . '.' . $extension;
        $uploadFile = '../../uploads/documents/' . basename($nomFichierServeur);

        if (move_uploaded_file($_FILES['documents']['tmp_name'], $uploadFile)) {
            $insertStmt = $db->prepare("INSERT INTO documents (nom_affichage, doc_url, est_public, id_structure) VALUES (:titre, :chemin, :visibility, :id_structure)");
            $isPublic = $visibility === 'public' ? 1 : 0;
            $insertStmt->execute([':titre' => $titre, ':chemin' => $nomFichierServeur, ':visibility' => $isPublic, ':id_structure' => $id_structure]);

            definir_flash("Le document a été téléversé avec succès.");
            header("Location: dashboard.php");
            exit();
        } else {
            $erreur = "Erreur lors du téléchargement du fichier.";
        }
     }
} else {
    $erreur = "Aucun fichier sélectionné ou erreur de téléchargement.";
}
 }
 
$token = generer_token_csrf();
require '../../includes/header_dashboard.php';
?>

<main class="container my-4" style="max-width: 560px;">
    <h1 class="h3">Uploader un document</h1>
    <p class="text-secondary">Pour <strong><?= htmlspecialchars($structures['nom_struc'] ?? '') ?></strong></p>

    <?php if (!empty($erreur)): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="mb-3"><label class="form-label">Titre du document</label><input type="text" name="nom_affichage" class="form-control" placeholder="Ex. : Rapport annuel 2026" required></div>
        <div class="mb-3"><label class="form-label">Fichier</label><input type="file" name="documents" accept=".pdf,.doc,.docx,.txt" class="form-control" required></div>
        <div class="mb-3">
            <label class="form-label d-block">Visibilité</label>
            <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="visibility" value="public" id="vPublic" checked><label class="form-check-label" for="vPublic">Public</label></div>
            <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="visibility" value="prive" id="vPrive"><label class="form-check-label" for="vPrive">Confidentiel</label></div>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">
        <button type="submit" class="btn btn-mrri">Téléverser</button>
    </form>
</main>

<?php 
 require '../../includes/footer_dashboard.php'; ?>
