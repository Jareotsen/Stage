<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/csrf.php';
require_once '../../includes/verif_doc.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

$resultat = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
$resultat->execute([':id_responsable' => $_SESSION['user_id']]);
$structures = $resultat->fetch(PDO::FETCH_ASSOC);

$erreur = null;
$titre = '';
$contenu = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   //verification csrf
if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
    exit('Requête invalide.');
}
    $titre = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');

    // Contrôles côté serveur (le navigateur peut être contourné)
    if (!$structures) {
        $erreur = "Aucune structure ne vous est rattachée : contactez l'administrateur.";
    } elseif (mb_strlen($titre) < 3 || mb_strlen($titre) > 150) {
        $erreur = "Le titre doit contenir entre 3 et 150 caractères.";
    } elseif (mb_strlen($contenu) < 10) {
        $erreur = "Le contenu doit contenir au moins 10 caractères.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erreur === null) {
    $insertStmt = $db->prepare("INSERT INTO actualites (titre, contenu, id_structure, date_publication) VALUES (:titre, :contenu, :id_struc, CURDATE())");
    $insertStmt->execute([':titre' => $titre, ':contenu' => $contenu, ':id_struc' => $structures['id_struc']]);
    $id_actualite = $db->lastInsertId();

      $photosRejetees = [];
    if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
        $nombrePhotos = count($_FILES['photos']['name']);
      
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
                    $insertPhotoStmt = $db->prepare("INSERT INTO actualites_photos (photos_actu_url, id_actualites, Position) VALUES (:nom_fichier, :id_actualite, :position)");
                    $insertPhotoStmt->execute([':nom_fichier' => $nomFichierServeur, ':id_actualite' => $id_actualite, ':position' => $i + 1]);
                }
            } elseif ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
    $photosRejetees[] = $_FILES['photos']['name'][$i] . ' (trop lourd ou erreur d\'envoi)';
}
            
        }
    }

    $messageFlash = "Actualité publiée avec succès.";
    if (!empty($photosRejetees)) {
        $messageFlash .= " Cependant, les fichiers suivants ont été rejetés (format non valide ou fichier trop lourd) : " . implode(', ',$photosRejetees) . ".";
        definir_flash($messageFlash, 'warning');
    } else {
        definir_flash($messageFlash, 'success');
    }
    header("Location: dashboard.php");
    exit();
}
require '../../includes/header_dashboard.php';
?>
<?php $pageActive = 'actualites'; require '../../includes/sidebar_point_focal.php'; ?>
<main class="container my-4" style="max-width: 640px;">
    <h1 class="h3">Ajouter une actualité</h1>
    <p class="text-secondary">Pour <strong><?= htmlspecialchars($structures['nom_struc'] ?? '') ?></strong></p>
    <p><a href="mes_actualites.php" class="link-secondary">← Retour à mes actualités</a></p>

    <?php if ($erreur): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
                 <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">

        <div class="mb-3"><label class="form-label" for="titre">Titre de l'actualité</label><input type="text" id="titre" name="titre" class="form-control" minlength="3" maxlength="150" placeholder="Ex. : Lancement d'un nouveau programme" required value="<?= htmlspecialchars($titre) ?>"></div>
        <div class="mb-3"><label class="form-label" for="contenu">Contenu</label><textarea id="contenu" name="contenu" class="form-control" rows="5" minlength="10" placeholder="Détaillez l'actualité..." required><?= htmlspecialchars($contenu) ?></textarea></div>
        <div class="mb-3">
            <label class="form-label">Photos (facultatif)</label>
            <input type="file" name="photos[]" accept="image/*" multiple class="form-control">
            <div class="form-text">Vous pouvez sélectionner plusieurs images en une fois.</div>
        </div>
        <button type="submit" class="btn btn-mrri">Publier l'actualité</button>
    </form>
</main>

<?php require '../../includes/footer_dashboard.php'; ?>