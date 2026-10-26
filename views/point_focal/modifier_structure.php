<?php 
require_once '../../includes/auth.php';
require_once '../../config/database.php';
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
$erreurCoordonnees = null;
$latitudeAffichee = $structures['latitude'] ?? '';
$longitudeAffichee = $structures['longitude'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
    exit('Requête invalide.');
}
    // Sans structure rattachée, rien à mettre à jour (évite des photos orphelines)
    if (!$structures) {
        definir_flash("Aucune structure ne vous est rattachée : contactez l'administrateur.", 'erreur');
        header("Location: dashboard.php");
        exit();
    }

    $adresse = trim($_POST['adresse'] ?? '');
    $directeur_general = trim($_POST['directeur_general'] ?? '');
    $mission = trim($_POST['mission'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $site_web = trim($_POST['site_web'] ?? '');

    // Longueurs maximales des colonnes : au-delà, MySQL tronquerait sans prévenir
    $limites = [
        'Adresse' => [$adresse, 255], 'Directeur général' => [$directeur_general, 200],
        'Mission' => [$mission, 1000], 'Email' => [$email, 100],
        'Téléphone' => [$telephone, 20], 'Site web' => [$site_web, 255],
    ];
    foreach ($limites as $champ => [$valeur, $maximum]) {
        if (mb_strlen($valeur) > $maximum) {
            $erreurCoordonnees = "Le champ « {$champ} » ne doit pas dépasser {$maximum} caractères.";
            break;
        }
    }
    if ($erreurCoordonnees === null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurCoordonnees = "L'adresse email n'est pas valide.";
    }
    // Les valeurs saisies restent affichées en cas d'erreur
    $structures = array_merge($structures, [
        'adresse' => $adresse, 'directeur_general' => $directeur_general, 'mission' => $mission,
        'email' => $email, 'telephone' => $telephone, 'site_web' => $site_web,
    ]);
    $latitudeSaisie = trim((string) ($_POST['latitude'] ?? ''));
    $longitudeSaisie = trim((string) ($_POST['longitude'] ?? ''));
    $latitudeAffichee = $latitudeSaisie;
    $longitudeAffichee = $longitudeSaisie;
    $latitude = null;
    $longitude = null;

    if ($erreurCoordonnees === null && ($latitudeSaisie !== '' || $longitudeSaisie !== '')) {
        $latitudeValide = $latitudeSaisie !== '' && filter_var($latitudeSaisie, FILTER_VALIDATE_FLOAT) !== false;
        $longitudeValide = $longitudeSaisie !== '' && filter_var($longitudeSaisie, FILTER_VALIDATE_FLOAT) !== false;
        if (!$latitudeValide || !$longitudeValide
            || (float) $latitudeSaisie < -90 || (float) $latitudeSaisie > 90
            || (float) $longitudeSaisie < -180 || (float) $longitudeSaisie > 180) {
            $erreurCoordonnees = "Saisissez une latitude entre -90 et 90 et une longitude entre -180 et 180, ou laissez les deux champs vides.";
        } else {
            $latitude = (float) $latitudeSaisie;
            $longitude = (float) $longitudeSaisie;
        }
    }

    if ($erreurCoordonnees === null) {
        $updateStmt = $db->prepare(
            "UPDATE structure
             SET adresse = :adresse, directeur_general = :directeur_general,
                 mission = :mission, email = :email, telephone = :telephone,
                 site_web = :site_web, latitude = :latitude, longitude = :longitude
             WHERE id_responsable = :id_responsable"
        );
        $updateStmt->execute([
            ':adresse' => $adresse, ':directeur_general' => $directeur_general, ':mission' => $mission,
            ':email' => $email, ':telephone' => $telephone, ':site_web' => $site_web,
            ':latitude' => $latitude, ':longitude' => $longitude,
            ':id_responsable' => $_SESSION['user_id']
        ]);
     
     $photosRejetees = [];
    if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
        $nombrePhotos = count($_FILES['photos']['name']);
        // Les nouvelles photos se placent après celles déjà en ligne
        $positionStmt = $db->prepare("SELECT COALESCE(MAX(Position), 0) FROM structure_photos WHERE id_structure = :id");
        $positionStmt->execute([':id' => $structures['id_struc']]);
        $positionSuivante = (int) $positionStmt->fetchColumn() + 1;
        for ($i = 0; $i < $nombrePhotos; $i++) {
            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                $extension = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
                $nomFichierServeur = uniqid() . '.' . $extension;
                $uploadFile = '../../uploads/photos/' . basename($nomFichierServeur);

                  if (!fichier_image_est_valide($_FILES['photos']['tmp_name'][$i], $_FILES['photos']['name'][$i])) {
                $photosRejetees[] = $_FILES['photos']['name'][$i];
                continue;
                }
                if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $uploadFile)) {
                    $insertLogoStmt = $db->prepare("INSERT INTO structure_photos (photos_url, id_structure, Position) VALUES (:nom_fichier, :id_structure, :position)");
                    $insertLogoStmt->execute([':nom_fichier' => $nomFichierServeur, ':id_structure' => $structures['id_struc'], ':position' => $positionSuivante++]);
                }
            } elseif ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $photosRejetees[] = $_FILES['photos']['name'][$i] . " (trop lourd ou erreur d'envoi)";
            }
        }
    }

     $messageFlash = "Structure mise à jour avec succès.";
    if (!empty($photosRejetees)) {
        $messageFlash .= " Cependant, les fichiers suivants ont été rejetés (format non valide) : " . implode(', ',$photosRejetees) . ".";
        definir_flash($messageFlash, 'warning');
    } else {
        definir_flash($messageFlash, 'success');
    }
    header("Location: dashboard.php");
    exit();
    }
}


require '../../includes/header_dashboard.php';
?>
 <?php $pageActive = 'structure'; require '../../includes/sidebar_point_focal.php'; ?> 
<main class="container my-4" style="max-width: 700px;">
    <h1 class="h3">Modifier la structure</h1>
    <p><span class="badge bg-secondary-subtle text-secondary me-1"><?= htmlspecialchars($structures['sigle'] ?? '') ?></span> <?= htmlspecialchars($structures['nom_struc'] ?? '') ?></p>

    <?php if ($erreurCoordonnees !== null): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreurCoordonnees, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$structures): ?>
        <div class="alert alert-warning">Aucune structure ne vous est rattachée : contactez l'administrateur.</div>
    <?php else: ?>
    <form action="" method="post" enctype="multipart/form-data">
        <div class="mb-3"><label class="form-label" for="adresse">Adresse</label><input type="text" id="adresse" name="adresse" maxlength="255" class="form-control" value="<?= htmlspecialchars($structures['adresse'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label" for="directeur_general">Directeur général</label><input type="text" id="directeur_general" name="directeur_general" maxlength="200" class="form-control" value="<?= htmlspecialchars($structures['directeur_general'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label" for="mission">Mission</label><textarea id="mission" name="mission" maxlength="1000" rows="4" class="form-control"><?= htmlspecialchars($structures['mission'] ?? '') ?></textarea><div class="form-text">1000 caractères maximum.</div></div>
        <div class="mb-3"><label class="form-label" for="email">Email</label><input type="email" id="email" name="email" maxlength="100" class="form-control" value="<?= htmlspecialchars($structures['email'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label" for="telephone">Téléphone</label><input type="tel" id="telephone" name="telephone" maxlength="20" class="form-control" value="<?= htmlspecialchars($structures['telephone'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label" for="site_web">Site web</label><input type="text" id="site_web" name="site_web" maxlength="255" class="form-control" placeholder="https://…" value="<?= htmlspecialchars($structures['site_web'] ?? '') ?>"></div>
        <div class="mb-3">
            <label class="form-label" for="latitude">Latitude</label>
            <input type="number" id="latitude" name="latitude" class="form-control" min="-90" max="90" step="any"
                   value="<?= htmlspecialchars($latitudeAffichee, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Coordonnée comprise entre -90 et 90 degrés.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="longitude">Longitude</label>
            <input type="number" id="longitude" name="longitude" class="form-control" min="-180" max="180" step="any"
                   value="<?= htmlspecialchars($longitudeAffichee, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text">Coordonnée comprise entre -180 et 180 degrés. Laissez les deux champs vides si la position n’est pas connue.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Photos de la structure</label>
            <input type="file" name="photos[]" accept="image/*" multiple class="form-control">
            <div class="form-text">Vous pouvez sélectionner plusieurs images en une fois.</div>
        </div>
         <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">
        <button type="submit" class="btn btn-mrri">Mettre à jour</button>
    </form>
    <?php endif; ?>
</main>

<?php require '../../includes/footer_dashboard.php'; ?>
