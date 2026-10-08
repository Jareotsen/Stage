<?php 
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
is_authenticated();

$role = $_SESSION['role'];

if ($role !== 'point_focal' && $role !== 'agent_ministere') {
    redirection_vers_les_dashboards($role);
    exit();
}

if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
    exit('Requête invalide.');
}

$maStructure = null;
if ($role === 'point_focal') {
    $resultat = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
    $resultat->execute([':id_responsable' => $_SESSION['user_id']]);
    $maStructure = $resultat->fetch(PDO::FETCH_ASSOC);

    if (!$maStructure) {
        exit('Structure introuvable.');
    }
}
// Pour un agent du ministère : la liste de toutes les structures, pour le <select>
$toutesStructures = [];
if ($role === 'agent_ministere') {
    $toutesStructures = $db->query("SELECT id_struc, nom_struc FROM structure ORDER BY nom_struc")->fetchAll(PDO::FETCH_ASSOC);
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  //je récupère l'id de la consultation
  $id_consultation = (int) $_POST['id_consultation'];

  $verifStmt = $db->prepare("
      SELECT id_consultation 
      FROM consultations 
      WHERE id_consultation = :id_consultation
  ");
  $verifStmt->execute([':id_consultation' => $id_consultation]);
  $consultation = $verifStmt->fetch(PDO::FETCH_ASSOC);

  if (!$consultation) {
      exit('Consultation introuvable.');
  }
  try{
    
      $db->beginTransaction();

      // Suppression de la consultation
      $deleteStmt = $db->prepare("DELETE FROM consultations WHERE id_consultation = :id_consultation");
      $deleteStmt->execute([':id_consultation' => $id_consultation]);

      $db->commit();
      definir_flash( 'Consultation supprimée avec succès.');
      header('Location: mes_consultations.php');
      exit();
  } catch (Exception $e) {
      $db->rollBack();
      exit('Erreur lors de la suppression de la consultation : ' . $e->getMessage());
  }
}