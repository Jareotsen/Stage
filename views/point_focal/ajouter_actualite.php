<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
}

$resultat = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
$resultat->execute([':id_responsable' => $_SESSION['user_id']]);
$structures = $resultat->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'] ?? '';
    $contenu = $_POST['contenu'] ?? '';

    $insertStmt = $db->prepare("INSERT INTO actualites (titre, contenu, id_structure, date_publication) VALUES (:titre, :contenu, :id_struc, CURDATE())");
    $insertStmt->execute([':titre' => $titre, ':contenu' => $contenu, ':id_struc' => $structures['id_struc']]);
    $id_actualite = $db->lastInsertId();

    if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
        $nombrePhotos = count($_FILES['photos']['name']);
        for ($i = 0; $i < $nombrePhotos; $i++) {
            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                $extension = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
                $nomFichierServeur = uniqid() . '.' . $extension;
                $uploadFile = '../../uploads/photos/' . basename($nomFichierServeur);
                if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $uploadFile)) {
                    $insertPhotoStmt = $db->prepare("INSERT INTO actualites_photos (photos_actu_url, id_actualites, Position) VALUES (:nom_fichier, :id_actualite, :position)");
                    $insertPhotoStmt->execute([':nom_fichier' => $nomFichierServeur, ':id_actualite' => $id_actualite, ':position' => $i + 1]);
                }
            }
        }
    }

    definir_flash("Actualité publiée avec succès.");
    header("Location: dashboard.php");
    exit();
}

require '../../includes/header.php';
?>

<main class="container my-4" style="max-width: 640px;">
    <h1 class="h3">Ajouter une actualité</h1>
    <p class="text-secondary">Pour <strong><?= htmlspecialchars($structures['nom_struc'] ?? '') ?></strong></p>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="mb-3"><label class="form-label">Titre de l'actualité</label><input type="text" name="titre" class="form-control" placeholder="Ex. : Lancement d'un nouveau programme" required></div>
        <div class="mb-3"><label class="form-label">Contenu</label><textarea name="contenu" class="form-control" rows="5" placeholder="Détaillez l'actualité..." required></textarea></div>
        <div class="mb-3">
            <label class="form-label">Photos (facultatif)</label>
            <input type="file" name="photos[]" accept="image/*" multiple class="form-control">
            <div class="form-text">Vous pouvez sélectionner plusieurs images en une fois.</div>
        </div>
        <button type="submit" class="btn btn-mrri">Publier l'actualité</button>
    </form>
</main>

<?php require '../../includes/footer.php'; ?>