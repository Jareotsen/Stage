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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   //verification csrf
if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
    exit('Requête invalide.');
}
    $titre = $_POST['titre'] ?? '';
    $contenu = $_POST['contenu'] ?? '';
    $statut = $_POST['statut'] ?? '';
    $date_ouverture = date('Y-m-d H:i:s');
    $date_fermeture = date('Y-m-d H:i:s', strtotime($_POST['date_fermeture']));


    $insertStmt = $db->prepare("INSERT INTO consultations (titre, contenu, id_structure,id_createur,date_ouverture, date_fermeture) VALUES (:titre, :contenu, :id_struc, :id_createur, :date_ouverture, :date_fermeture)");
    $insertStmt->execute([':titre' => $titre,
     ':contenu' => $contenu,
      ':statut' => $statut,
       ':id_struc' => $structures['id_struc'],
        ':id_createur' => $_SESSION['user_id'],
         ':date_ouverture' => $date_ouverture,
          ':date_fermeture' => $date_fermeture]);
    $id_consultation = $db->lastInsertId();
  if()
}