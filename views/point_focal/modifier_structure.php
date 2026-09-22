<?php 
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

$resultat = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
$resultat->execute([':id_responsable' => $_SESSION['user_id']]);
$structures = $resultat->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse = $_POST['adresse'] ?? '';
    $directeur_general = $_POST['directeur_general'] ?? '';
    $mission = $_POST['mission'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $site_web = $_POST['site_web'] ?? '';

    $updateStmt = $db->prepare("UPDATE structure SET adresse = :adresse, directeur_general = :directeur_general, mission = :mission, email = :email, telephone = :telephone, site_web = :site_web WHERE id_responsable = :id_responsable");
    $updateStmt->execute([
        ':adresse' => $adresse, ':directeur_general' => $directeur_general, ':mission' => $mission,
        ':email' => $email, ':telephone' => $telephone, ':site_web' => $site_web,
        ':id_responsable' => $_SESSION['user_id']
    ]);

    if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
        $nombrePhotos = count($_FILES['photos']['name']);
        for ($i = 0; $i < $nombrePhotos; $i++) {
            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                $extension = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
                $nomFichierServeur = uniqid() . '.' . $extension;
                $uploadFile = '../../uploads/photos/' . basename($nomFichierServeur);
                if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $uploadFile)) {
                    $insertLogoStmt = $db->prepare("INSERT INTO structure_photos (photos_url, id_structure, Position) VALUES (:nom_fichier, :id_structure, :position)");
                    $insertLogoStmt->execute([':nom_fichier' => $nomFichierServeur, ':id_structure' => $structures['id_struc'], ':position' => $i + 1]);
                }
            }
        }
    }

    definir_flash("Structure mise à jour avec succès.");
    header("Location: dashboard.php");
    exit();
}

require '../../includes/header.php';
?>

<main class="container my-4" style="max-width: 700px;">
    <h1 class="h3">Modifier la structure</h1>
    <p><span class="badge bg-secondary-subtle text-secondary me-1"><?= htmlspecialchars($structures['sigle'] ?? '') ?></span> <?= htmlspecialchars($structures['nom_struc'] ?? '') ?></p>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="mb-3"><label class="form-label">Adresse</label><input type="text" name="adresse" class="form-control" value="<?= htmlspecialchars($structures['adresse'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Directeur général</label><input type="text" name="directeur_general" class="form-control" value="<?= htmlspecialchars($structures['directeur_general'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Mission</label><input type="text" name="mission" class="form-control" value="<?= htmlspecialchars($structures['mission'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($structures['email'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Téléphone</label><input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($structures['telephone'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">Site web</label><input type="text" name="site_web" class="form-control" value="<?= htmlspecialchars($structures['site_web'] ?? '') ?>"></div>
        <div class="mb-3">
            <label class="form-label">Photos de la structure</label>
            <input type="file" name="photos[]" accept="image/*" multiple class="form-control">
            <div class="form-text">Vous pouvez sélectionner plusieurs images en une fois.</div>
        </div>
        <button type="submit" class="btn btn-mrri">Mettre à jour</button>
    </form>
</main>

<?php require '../../includes/footer.php'; ?>