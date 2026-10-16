<link rel="stylesheet" href="../../publique/css/theme.css">

<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
require_once '../../includes/verif_doc.php';

is_authenticated();

// Vérifier le rôle de l'utilisateur
if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

// Récupérer la structure associée au point focal
$resultat = $db->prepare(
    "SELECT * FROM structure WHERE id_responsable = :id_responsable"
);

$resultat->execute([
    ':id_responsable' => $_SESSION['user_id']
]);

$structures = $resultat->fetch(PDO::FETCH_ASSOC);

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Vérifier le jeton CSRF
    if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Requête invalide. Veuillez réessayer.');
    }

    // 2. Vérifier que le point focal possède une structure
    if (!$structures) {
        $erreur = "Aucune structure n'est associée à votre compte.";
    } else {

        // 3. Récupérer et vérifier les données du formulaire
        $titre = trim($_POST['nom_affichage'] ?? '');
        $visibility = $_POST['visibility'] ?? 'public';
        $id_structure = $structures['id_struc'];

        if ($titre === '') {
            $erreur = "Le titre du document est obligatoire.";

        } elseif (!in_array($visibility, ['public', 'prive'], true)) {
            $erreur = "La visibilité sélectionnée est invalide.";

        } elseif (!isset($_FILES['documents'])) {
            $erreur = "Aucun fichier n'a été envoyé.";

        } elseif ($_FILES['documents']['error'] !== UPLOAD_ERR_OK) {

            // 4. Gérer les erreurs de téléversement
            switch ($_FILES['documents']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $erreur = "Le fichier dépasse la taille autorisée.";
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $erreur = "Le fichier n'a été que partiellement téléversé.";
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $erreur = "Veuillez sélectionner un fichier.";
                    break;

                default:
                    $erreur = "Une erreur est survenue lors du téléversement.";
                    break;
            }

        } else {

            // 5. Vérifier le type de fichier
            if (!fichier_est_valide(
                $_FILES['documents']['tmp_name'],
                $_FILES['documents']['name']
            )) {
                $erreur = "Type de fichier non autorisé.";

            } else {

                // 6. Préparer le nom du fichier
                $extension = strtolower(
                    pathinfo(
                        $_FILES['documents']['name'],
                        PATHINFO_EXTENSION
                    )
                );

                $nomFichierServeur = bin2hex(random_bytes(16))
                    . '.' . $extension;

                $dossierUpload = '../../uploads/documents/';

                $uploadFile = $dossierUpload . $nomFichierServeur;

                // 7. Vérifier que le dossier existe
                if (!is_dir($dossierUpload)) {
                    $erreur = "Le dossier de stockage des documents est introuvable.";

                } elseif (!is_writable($dossierUpload)) {
                    $erreur = "Le dossier de stockage n'est pas accessible en écriture.";

                } elseif (!is_uploaded_file($_FILES['documents']['tmp_name'])) {
                    $erreur = "Le fichier envoyé n'est pas valide.";

                } else {

                    // 8. Déplacer le fichier
                    if (move_uploaded_file(
                        $_FILES['documents']['tmp_name'],
                        $uploadFile
                    )) {

                        try {

                            // 9. Enregistrer le document dans la base de données
                            $insertStmt = $db->prepare(
                                "INSERT INTO documents
                                (nom_affichage, doc_url, est_public, id_structure)
                                VALUES
                                (:titre, :chemin, :visibility, :id_structure)"
                            );

                            $isPublic = ($visibility === 'public') ? 1 : 0;

                            $insertStmt->execute([
                                ':titre' => $titre,
                                ':chemin' => $nomFichierServeur,
                                ':visibility' => $isPublic,
                                ':id_structure' => $id_structure
                            ]);

                            // 10. Confirmer le succès
                            definir_flash(
                                "Le document a été téléversé avec succès."
                            );

                            header("Location: dashboard.php");
                            exit();

                        } catch (PDOException $e) {

                            // Supprimer le fichier si l'insertion SQL échoue
                            if (is_file($uploadFile)) {
                                unlink($uploadFile);
                            }

                            // Enregistrer l'erreur dans les journaux du serveur
                            error_log(
                                "Erreur SQL lors de l'ajout d'un document : "
                                . $e->getMessage()
                            );

                            $erreur = "Impossible d'enregistrer le document. Veuillez réessayer.";
                        }

                    } else {
                        $erreur = "Erreur lors du déplacement du fichier.";
                    }
                }
            }
        }
    }
}
// Générer le jeton CSRF une seule fois pour le formulaire
$token = generer_token_csrf();

require '../../includes/header_dashboard.php';
?>

<?php
$pageActive = 'documents';
require '../../includes/sidebar_point_focal.php';
?>

<main class="container my-4" style="max-width: 560px;">

    <h1 class="h3">Téléverser un document</h1>

    <p class="text-secondary">
        Pour :
        <strong>
            <?= htmlspecialchars($structures['nom_struc'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </strong>
    </p>

    <?php if (!empty($erreur)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">

        <div class="mb-3">
            <label for="nom_affichage" class="form-label">
                Titre du document
            </label>

            <input
                type="text"
                id="nom_affichage"
                name="nom_affichage"
                class="form-control"
                placeholder="Ex. : Rapport annuel 2026"
                maxlength="255"
                required
            >
        </div>

        <div class="mb-3">
            <label for="documents" class="form-label">
                Fichier
            </label>

            <input
                type="file"
                id="documents"
                name="documents"
                accept=".pdf,.doc,.docx,.txt"
                class="form-control"
                required
            >
        </div>

        <div class="mb-3">
            <label class="form-label d-block">
                Visibilité
            </label>

            <div class="form-check form-check-inline">
                <input
                    class="form-check-input"
                    type="radio"
                    name="visibility"
                    value="public"
                    id="vPublic"
                    checked
                >

                <label class="form-check-label" for="vPublic">
                    Public
                </label>
            </div>

            <div class="form-check form-check-inline">
                <input
                    class="form-check-input"
                    type="radio"
                    name="visibility"
                    value="prive"
                    id="vPrive"
                >

                <label class="form-check-label" for="vPrive">
                    Confidentiel
                </label>
            </div>
        </div>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
        >

        <button type="submit" class="btn btn-mrri">
            Téléverser
        </button>

    </form>

</main>

<?php
require '../../includes/footer_dashboard.php';
?>

