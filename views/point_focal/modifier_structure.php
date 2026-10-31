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

const DOSSIER_PHOTOS = __DIR__ . '/../../uploads/photos/';
const TAILLE_MAX_PHOTO_DIRIGEANT = 3 * 1024 * 1024;

// Fonctions proposées pour le/la dirigeant(e) ; une autre valeur peut être saisie librement
$fonctionsSuggerees = [
    'Président', 'Présidente', 'Premier président', 'Première présidente',
    'Directeur général', 'Directrice générale', 'Secrétaire général', 'Secrétaire générale',
    'Médiateur de la République', 'Médiatrice de la République', 'Contrôleur général', 'Contrôleuse générale',
];

/**
 * Valide une date saisie au format AAAA-MM-JJ.
 * Retourne [valeur SQL ou null si vide, message d'erreur ou null].
 */
function validerDate(string $saisie, string $libelle): array
{
    if ($saisie === '') {
        return [null, null];
    }
    $date = DateTime::createFromFormat('!Y-m-d', $saisie);
    if (!$date || $date->format('Y-m-d') !== $saisie) {
        return [null, "La date « {$libelle} » n'est pas valide."];
    }
    if ((int) $date->format('Y') < 1800 || $date > new DateTime('today')) {
        return [null, "La date « {$libelle} » doit être comprise entre 1800 et aujourd'hui."];
    }
    return [$saisie, null];
}

// Supprime une ancienne photo de dirigeant(e) : uniquement un fichier généré par cette page
function supprimerPhotoDirigeant(?string $nomFichier): void
{
    $nom = basename((string) $nomFichier);
    if ($nom !== '' && str_starts_with($nom, 'dirigeant_') && is_file(DOSSIER_PHOTOS . $nom)) {
        @unlink(DOSSIER_PHOTOS . $nom);
    }
}

$resultat = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
$resultat->execute([':id_responsable' => $_SESSION['user_id']]);
$structures = $resultat->fetch(PDO::FETCH_ASSOC);
// Nom et sigle enregistrés : leur modification exige une confirmation explicite
$nomInitial = (string) ($structures['nom_struc'] ?? '');
$sigleInitial = (string) ($structures['sigle'] ?? '');
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

    $nom_struc = trim($_POST['nom_struc'] ?? '');
    $sigle = trim($_POST['sigle'] ?? '');
    $identiteModifiee = $nom_struc !== $nomInitial || $sigle !== $sigleInitial;
    $identiteConfirmee = ($_POST['confirmer_identite'] ?? '') === '1';
    $adresse = trim($_POST['adresse'] ?? '');
    $directeur_general = trim($_POST['directeur_general'] ?? '');
    $fonction_dirigeant = trim($_POST['fonction_dirigeant'] ?? '');
    $mission = trim($_POST['mission'] ?? '');
    $historique = trim($_POST['historique'] ?? '');
    $composition = trim($_POST['composition'] ?? '');
    $texte_fondateur = trim($_POST['texte_fondateur'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $site_web = trim($_POST['site_web'] ?? '');
    $dateCreationSaisie = trim((string) ($_POST['date_creation'] ?? ''));
    $dirigeantDepuisSaisi = trim((string) ($_POST['dirigeant_depuis'] ?? ''));
    $supprimerPhotoDirigeant = ($_POST['supprimer_photo_dirigeant'] ?? '') === '1';

    // Longueurs maximales des colonnes : au-delà, MySQL tronquerait sans prévenir
    $limites = [
        'Nom de la structure' => [$nom_struc, 200], 'Sigle' => [$sigle, 30],
        'Adresse' => [$adresse, 255], 'Nom du dirigeant' => [$directeur_general, 200],
        'Fonction du dirigeant' => [$fonction_dirigeant, 100],
        'Rôle et missions' => [$mission, 5000], 'Historique' => [$historique, 5000],
        'Composition' => [$composition, 3000], 'Texte fondateur' => [$texte_fondateur, 255],
        'Email' => [$email, 100], 'Téléphone' => [$telephone, 20], 'Site web' => [$site_web, 255],
    ];
    foreach ($limites as $champ => [$valeur, $maximum]) {
        if (mb_strlen($valeur) > $maximum) {
            $erreurCoordonnees = "Le champ « {$champ} » ne doit pas dépasser {$maximum} caractères.";
            break;
        }
    }
    if ($erreurCoordonnees === null && $nom_struc === '') {
        $erreurCoordonnees = "Le nom de la structure ne peut pas être vide.";
    }
    // Contrôle serveur : la case de confirmation peut être contournée dans le navigateur
    if ($erreurCoordonnees === null && $identiteModifiee && !$identiteConfirmee) {
        $erreurCoordonnees = "Vous avez modifié le nom ou le sigle de la structure : cochez la case de confirmation pour valider ce changement.";
    }
    if ($erreurCoordonnees === null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurCoordonnees = "L'adresse email n'est pas valide.";
    }

    [$date_creation, $erreurDate] = validerDate($dateCreationSaisie, 'Date de création');
    $erreurCoordonnees ??= $erreurDate;
    [$dirigeant_depuis, $erreurDate] = validerDate($dirigeantDepuisSaisi, 'En fonction depuis');
    $erreurCoordonnees ??= $erreurDate;

    // Les valeurs saisies restent affichées en cas d'erreur
    $structures = array_merge($structures, [
        'nom_struc' => $nom_struc, 'sigle' => $sigle,
        'adresse' => $adresse, 'directeur_general' => $directeur_general,
        'fonction_dirigeant' => $fonction_dirigeant, 'mission' => $mission,
        'historique' => $historique, 'composition' => $composition,
        'texte_fondateur' => $texte_fondateur, 'date_creation' => $dateCreationSaisie,
        'dirigeant_depuis' => $dirigeantDepuisSaisi,
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

    /* ---------- Photo du/de la dirigeant(e) : contrôlée avant toute écriture en base ---------- */
    $anciennePhotoDirigeant = $structures['photo_dirigeant'] ?? null;
    $photoDirigeant = $supprimerPhotoDirigeant ? null : $anciennePhotoDirigeant;
    $fichierPhoto = $_FILES['photo_dirigeant'] ?? null;
    $nouvellePhotoTemporaire = null;

    if ($erreurCoordonnees === null && $fichierPhoto && is_array($fichierPhoto) && ($fichierPhoto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($fichierPhoto['error'] !== UPLOAD_ERR_OK || $fichierPhoto['size'] > TAILLE_MAX_PHOTO_DIRIGEANT) {
            $erreurCoordonnees = "La photo du dirigeant est trop lourde (3 Mo maximum) ou n'a pas pu être envoyée.";
        } elseif (!is_uploaded_file($fichierPhoto['tmp_name'])
            || !fichier_image_est_valide($fichierPhoto['tmp_name'], $fichierPhoto['name'])) {
            $erreurCoordonnees = "La photo du dirigeant doit être une image JPG, PNG ou GIF.";
        } else {
            // Nom aléatoire et extension en minuscules issue de la liste blanche de fichier_image_est_valide()
            $extension = strtolower(pathinfo($fichierPhoto['name'], PATHINFO_EXTENSION));
            $nouvellePhotoTemporaire = 'dirigeant_' . bin2hex(random_bytes(12)) . '.' . $extension;
            if (!move_uploaded_file($fichierPhoto['tmp_name'], DOSSIER_PHOTOS . $nouvellePhotoTemporaire)) {
                $nouvellePhotoTemporaire = null;
                $erreurCoordonnees = "La photo du dirigeant n'a pas pu être enregistrée.";
            } else {
                $photoDirigeant = $nouvellePhotoTemporaire;
            }
        }
    }

    if ($erreurCoordonnees === null) {
        $updateStmt = $db->prepare(
            "UPDATE structure
             SET nom_struc = :nom_struc, sigle = :sigle,
                 adresse = :adresse, directeur_general = :directeur_general,
                 fonction_dirigeant = :fonction_dirigeant, dirigeant_depuis = :dirigeant_depuis,
                 photo_dirigeant = :photo_dirigeant,
                 mission = :mission, historique = :historique, composition = :composition,
                 date_creation = :date_creation, texte_fondateur = :texte_fondateur,
                 email = :email, telephone = :telephone,
                 site_web = :site_web, latitude = :latitude, longitude = :longitude
             WHERE id_responsable = :id_responsable"
        );
        try {
            $updateStmt->execute([
                ':nom_struc' => $nom_struc, ':sigle' => $sigle !== '' ? $sigle : null,
                ':adresse' => $adresse, ':directeur_general' => $directeur_general,
                ':fonction_dirigeant' => $fonction_dirigeant !== '' ? $fonction_dirigeant : null,
                ':dirigeant_depuis' => $dirigeant_depuis, ':photo_dirigeant' => $photoDirigeant,
                ':mission' => $mission,
                ':historique' => $historique !== '' ? $historique : null,
                ':composition' => $composition !== '' ? $composition : null,
                ':date_creation' => $date_creation,
                ':texte_fondateur' => $texte_fondateur !== '' ? $texte_fondateur : null,
                ':email' => $email, ':telephone' => $telephone, ':site_web' => $site_web,
                ':latitude' => $latitude, ':longitude' => $longitude,
                ':id_responsable' => $_SESSION['user_id']
            ]);
        } catch (PDOException $exception) {
            // La base n'a pas été modifiée : on retire la photo qui venait d'être déposée
            supprimerPhotoDirigeant($nouvellePhotoTemporaire);
            throw $exception;
        }

        // L'ancienne photo n'est effacée qu'une fois la base à jour
        if ($anciennePhotoDirigeant !== $photoDirigeant) {
            supprimerPhotoDirigeant($anciennePhotoDirigeant);
        }

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
    if ($identiteModifiee) {
        $messageFlash .= " Le nom et le sigle officiels ont été modifiés.";
    }
    if (!empty($photosRejetees)) {
        $messageFlash .= " Cependant, les fichiers suivants ont été rejetés (format non valide) : " . implode(', ',$photosRejetees) . ".";
        definir_flash($messageFlash, 'warning');
    } else {
        definir_flash($messageFlash, 'success');
    }
    header("Location: dashboard.php");
    exit();
    }

    // Erreur de saisie : la photo déposée pendant cet envoi n'est pas conservée
    supprimerPhotoDirigeant($nouvellePhotoTemporaire);
}

$e = static fn($valeur): string => htmlspecialchars((string) ($valeur ?? ''), ENT_QUOTES, 'UTF-8');

require '../../includes/header_dashboard.php';
?>
 <?php $pageActive = 'structure'; require '../../includes/sidebar_point_focal.php'; ?>
<style>
    .ms-bloc { margin-bottom: 1.75rem; padding: 1.25rem 1.25rem .5rem; border: 1px solid var(--mrri-border, #E1E4EB); border-radius: 10px; background: #fff; }
    .ms-bloc legend { float: none; width: auto; margin-bottom: .25rem; padding: 0; font-size: 1.05rem; font-weight: 700; }
    .ms-bloc .ms-intro { margin-bottom: 1rem; color: #5F6775; font-size: .85rem; }
    .ms-photo-actuelle { display: flex; align-items: center; gap: .9rem; margin-bottom: .75rem; }
    .ms-photo-actuelle img { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; }
    .ms-confirmation { padding: .75rem 1rem; border: 1px solid #E8D9A8; border-radius: 10px; background: #FBF4E0; }
    .ms-confirmation[hidden] { display: none; }
</style>
<main class="container my-4" style="max-width: 760px;">
    <h1 class="h3">Modifier la structure</h1>
    <p><span class="badge bg-secondary-subtle text-secondary me-1"><?= $e($structures['sigle'] ?? '') ?></span> <?= $e($structures['nom_struc'] ?? '') ?></p>

    <?php if ($erreurCoordonnees !== null): ?>
        <div class="alert alert-danger" role="alert"><?= $e($erreurCoordonnees) ?></div>
    <?php endif; ?>

    <?php if (!$structures): ?>
        <div class="alert alert-warning">Aucune structure ne vous est rattachée : contactez l'administrateur.</div>
    <?php else: ?>
    <form action="" method="post" enctype="multipart/form-data" id="form-structure">

        <fieldset class="ms-bloc">
            <legend>Identité officielle</legend>
            <p class="ms-intro">Le nom et le sigle apparaissent sur l'ensemble du site. Ne les modifiez qu'en cas de changement officiel de dénomination.</p>
            <div class="row">
                <div class="col-sm-8 mb-3">
                    <label class="form-label" for="nom_struc">Nom de la structure</label>
                    <input type="text" id="nom_struc" name="nom_struc" maxlength="200" required class="form-control"
                           value="<?= $e($structures['nom_struc'] ?? '') ?>" data-initial="<?= $e($nomInitial) ?>">
                </div>
                <div class="col-sm-4 mb-3">
                    <label class="form-label" for="sigle">Sigle</label>
                    <input type="text" id="sigle" name="sigle" maxlength="30" class="form-control"
                           value="<?= $e($structures['sigle'] ?? '') ?>" data-initial="<?= $e($sigleInitial) ?>">
                </div>
            </div>
            <div class="ms-confirmation mb-3" id="bloc-confirmation-identite">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmer_identite" name="confirmer_identite" value="1">
                    <label class="form-check-label" for="confirmer_identite">
                        Je confirme la modification du nom et/ou du sigle officiels de la structure.
                    </label>
                </div>
            </div>
        </fieldset>

        <fieldset class="ms-bloc">
            <legend>Rôle et histoire</legend>
            <p class="ms-intro">Ces informations sont affichées sur la fiche publique de la structure.</p>
            <div class="mb-3">
                <label class="form-label" for="mission">Rôle et missions</label>
                <textarea id="mission" name="mission" maxlength="5000" rows="6" class="form-control"><?= $e($structures['mission'] ?? '') ?></textarea>
                <div class="form-text">5000 caractères maximum. Laissez une ligne vide entre deux paragraphes.</div>
            </div>
            <div class="row">
                <div class="col-sm-5 mb-3">
                    <label class="form-label" for="date_creation">Date de création</label>
                    <input type="date" id="date_creation" name="date_creation" class="form-control" min="1800-01-01" max="<?= date('Y-m-d') ?>" value="<?= $e($structures['date_creation'] ?? '') ?>">
                </div>
                <div class="col-sm-7 mb-3">
                    <label class="form-label" for="texte_fondateur">Texte fondateur</label>
                    <input type="text" id="texte_fondateur" name="texte_fondateur" maxlength="255" class="form-control" placeholder="Ex. : Loi n° 002/2003 du 7 mai 2003" value="<?= $e($structures['texte_fondateur'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="historique">Historique</label>
                <textarea id="historique" name="historique" maxlength="5000" rows="6" class="form-control" placeholder="Création, réformes, grandes étapes…"><?= $e($structures['historique'] ?? '') ?></textarea>
                <div class="form-text">5000 caractères maximum. Laissez une ligne vide entre deux paragraphes.</div>
            </div>
        </fieldset>

        <fieldset class="ms-bloc">
            <legend>Dirigeant(e) actuel(le)</legend>
            <div class="row">
                <div class="col-sm-7 mb-3">
                    <label class="form-label" for="directeur_general">Nom et prénom</label>
                    <input type="text" id="directeur_general" name="directeur_general" maxlength="200" class="form-control" value="<?= $e($structures['directeur_general'] ?? '') ?>">
                </div>
                <div class="col-sm-5 mb-3">
                    <label class="form-label" for="fonction_dirigeant">Fonction</label>
                    <input type="text" id="fonction_dirigeant" name="fonction_dirigeant" maxlength="100" class="form-control" list="fonctions-dirigeant" placeholder="Ex. : Présidente" value="<?= $e($structures['fonction_dirigeant'] ?? '') ?>">
                    <datalist id="fonctions-dirigeant">
                        <?php foreach ($fonctionsSuggerees as $fonction): ?><option value="<?= $e($fonction) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="dirigeant_depuis">En fonction depuis</label>
                <input type="date" id="dirigeant_depuis" name="dirigeant_depuis" class="form-control" style="max-width: 220px;" min="1800-01-01" max="<?= date('Y-m-d') ?>" value="<?= $e($structures['dirigeant_depuis'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="photo_dirigeant">Photo officielle</label>
                <?php if (!empty($structures['photo_dirigeant'])): ?>
                    <div class="ms-photo-actuelle">
                        <img src="../../uploads/photos/<?= $e(basename($structures['photo_dirigeant'])) ?>" alt="Photo actuelle du dirigeant">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="supprimer_photo_dirigeant" name="supprimer_photo_dirigeant" value="1">
                            <label class="form-check-label" for="supprimer_photo_dirigeant">Retirer la photo actuelle</label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" id="photo_dirigeant" name="photo_dirigeant" accept=".jpg,.jpeg,.png,.gif" class="form-control">
                <div class="form-text">JPG, PNG ou GIF, 3 Mo maximum. Une nouvelle photo remplace l'actuelle.</div>
            </div>
        </fieldset>

        <fieldset class="ms-bloc">
            <legend>Composition</legend>
            <div class="mb-3">
                <label class="form-label" for="composition">Membres et organes</label>
                <textarea id="composition" name="composition" maxlength="3000" rows="6" class="form-control" placeholder="Président : Nom Prénom&#10;Vice-président : Nom Prénom&#10;9 membres nommés pour 7 ans"><?= $e($structures['composition'] ?? '') ?></textarea>
                <div class="form-text">Une ligne par membre ou par organe. Écrivez « Fonction : Nom » pour mettre la fonction en évidence. 3000 caractères maximum.</div>
            </div>
        </fieldset>

        <fieldset class="ms-bloc">
            <legend>Coordonnées</legend>
            <div class="mb-3"><label class="form-label" for="adresse">Adresse</label><input type="text" id="adresse" name="adresse" maxlength="255" class="form-control" value="<?= $e($structures['adresse'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label" for="email">Email</label><input type="email" id="email" name="email" maxlength="100" class="form-control" value="<?= $e($structures['email'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label" for="telephone">Téléphone</label><input type="tel" id="telephone" name="telephone" maxlength="20" class="form-control" value="<?= $e($structures['telephone'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label" for="site_web">Site web</label><input type="text" id="site_web" name="site_web" maxlength="255" class="form-control" placeholder="https://…" value="<?= $e($structures['site_web'] ?? '') ?>"></div>
        </fieldset>

        <fieldset class="ms-bloc">
            <legend>Localisation</legend>
            <div class="mb-3">
                <label class="form-label" for="latitude">Latitude</label>
                <input type="number" id="latitude" name="latitude" class="form-control" min="-90" max="90" step="any"
                       value="<?= $e($latitudeAffichee) ?>">
                <div class="form-text">Coordonnée comprise entre -90 et 90 degrés.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="longitude">Longitude</label>
                <input type="number" id="longitude" name="longitude" class="form-control" min="-180" max="180" step="any"
                       value="<?= $e($longitudeAffichee) ?>">
                <div class="form-text">Coordonnée comprise entre -180 et 180 degrés. Laissez les deux champs vides si la position n’est pas connue.</div>
            </div>
        </fieldset>

        <fieldset class="ms-bloc">
            <legend>Photos de la structure</legend>
            <div class="mb-3">
                <input type="file" name="photos[]" accept="image/*" multiple class="form-control" aria-label="Photos de la structure">
                <div class="form-text">Vous pouvez sélectionner plusieurs images en une fois.</div>
            </div>
        </fieldset>

         <input type="hidden" name="csrf_token" value="<?= $e(generer_token_csrf()) ?>">
        <button type="submit" class="btn btn-mrri">Mettre à jour</button>
    </form>

    <script>
    // Nom et sigle : la case de confirmation n'apparaît que s'ils changent,
    // puis une dernière confirmation est demandée à l'envoi (le serveur revérifie)
    (function () {
        const formulaire = document.getElementById('form-structure');
        const nom = document.getElementById('nom_struc');
        const sigle = document.getElementById('sigle');
        const bloc = document.getElementById('bloc-confirmation-identite');
        const caseConfirmation = document.getElementById('confirmer_identite');

        const identiteModifiee = () =>
            nom.value.trim() !== nom.dataset.initial || sigle.value.trim() !== sigle.dataset.initial;

        function actualiser() {
            const modifiee = identiteModifiee();
            bloc.hidden = !modifiee;
            caseConfirmation.required = modifiee;
            if (!modifiee) {
                caseConfirmation.checked = false;
            }
        }

        nom.addEventListener('input', actualiser);
        sigle.addEventListener('input', actualiser);
        actualiser();

        formulaire.addEventListener('submit', function (evenement) {
            if (!identiteModifiee()) {
                return;
            }
            const message = 'Vous allez modifier l\'identité officielle de la structure.\n\n'
                + 'Nom actuel : ' + nom.dataset.initial + '\n'
                + 'Nouveau nom : ' + nom.value.trim() + '\n\n'
                + 'Sigle actuel : ' + (sigle.dataset.initial || '(aucun)') + '\n'
                + 'Nouveau sigle : ' + (sigle.value.trim() || '(aucun)') + '\n\n'
                + 'Ce changement sera visible sur tout le site. Confirmer ?';
            if (!window.confirm(message)) {
                evenement.preventDefault();
            }
        });
    })();
    </script>
    <?php endif; ?>
</main>

<?php require '../../includes/footer_dashboard.php'; ?>
