<?php 
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

$resp = $db->prepare("SELECT * 
                        FROM structure 
                        WHERE id_responsable = :id_responsable");
$resp->execute([':id_responsable' => $_SESSION['user_id']]);
$structures = $resp->fetch(PDO::FETCH_ASSOC);
if (!$structures) {
    exit('Structure introuvable.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Récupération de l'id de l'actualité
    $id_actualite = (int) $_POST['id_actu'];

    // 2. Vérifier que l'actualité appartient bien à la structure du point focal
    $verifStmt = $db->prepare("
        SELECT id_actu 
        FROM actualites 
        WHERE id_actu = :id_actu 
          AND id_structure = :id_structure
    ");

    $verifStmt->execute([
        ':id_actu' => $id_actualite,
        ':id_structure' => $structures['id_struc']
    ]);

    $actuTrouvee = $verifStmt->fetch(PDO::FETCH_ASSOC);

    if (!$actuTrouvee) {
        exit('Action non autorisée.');
    }
$photosStmt = $db->prepare("SELECT Photos_actu_url FROM actualites_photos WHERE id_actualites = :id_actu");
$photosStmt->execute([':id_actu' => $id_actualite]);
$photos = $photosStmt->fetchAll(PDO::FETCH_COLUMN);
    // 3. Suppression sécurisée avec transaction
    try {
        $db->beginTransaction();

        // 3.1 Supprimer les photos associées
        $deletePhotos = $db->prepare("
            DELETE FROM actualites_photos 
            WHERE id_actualites = :id_actu
        ");
        $deletePhotos->execute([':id_actu' => $id_actualite]);

        // 3.2 Supprimer l’actualité
        $deleteActu = $db->prepare("
            DELETE FROM actualites 
            WHERE id_actu = :id_actu
        ");
        $deleteActu->execute([':id_actu' => $id_actualite]);

        // 3.3 Valider la transaction
        $db->commit();
        foreach ($photos as $nomFichier) {
    $chemin = '../../uploads/photos/' . $nomFichier;
    if (file_exists($chemin)) {
        unlink($chemin);
    }
}

    } catch (Exception $e) {
        // En cas d’erreur → rollback
        $db->rollBack();
        exit("Erreur lors de la suppression : " . $e->getMessage());
    }
    definir_flash( "Actualité supprimée avec succès.");
    header("Location: mes_actualites.php");
    exit();
}
