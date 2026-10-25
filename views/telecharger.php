<?php
require_once '../../config/database.php';
session_start();

// je récupère l'ID du document à télécharger depuis la requête GET
$doc_id = isset($_GET['id_document']) ? (int) $_GET['id_document'] : 0;

if ($doc_id <= 0) {
    http_response_code(404); // requête incorrecte
    exit('ID de document invalide.');
}

// étape 1 : quel est ce doc ?
$query = $db->prepare("SELECT * 
                       FROM documents 
                       WHERE id_document = :id_doc");
$query->execute(['id_doc' => $doc_id]);
$document = $query->fetch(PDO::FETCH_ASSOC);

if (!$document){
    //là le doc n'est pas dans ma bdd
    http_response_code(404); 
    exit('Document introuvable.');
    }
$nom_doc = $document['nom_affichage'];
$statut_doc = (int) $document['est_public'];
$path_doc = $document['doc_url'];
// étape 2 & 3 : public ou confidentiel ? puis qui demande le doc?

$autorisation_accordee = false;

// Un document public est accessible à tous.
if ((int) $document['est_public'] === 1) {
    $autorisation_accordee = true;
} else {
    // Un document confidentiel exige une session authentifiée.
    $id_utilisateur = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? null;

    if ($id_utilisateur !== null) {

        // Règle 1 : les agents du ministère sont autorisés.
        if ($role === 'agent_ministere') {
            $autorisation_accordee = true;
        }

        // Règle 2 : un point focal doit être responsable
        // de la structure propriétaire du document.
        elseif ($role === 'point_focal') {

            $requete = $db->prepare(
                "SELECT COUNT(*)
                 FROM structure
                 WHERE id_responsable = :id_utilisateur
                   AND id_structure = :id_struc"
            );

            $requete->execute([
                'id_utilisateur' => $id_utilisateur,
                'id_structure'   => $document['id_structure']
            ]);

            $autorisation_accordee = ((int) $requete->fetchColumn() > 0);
        }
    }
}

// Refuser l'accès si aucune règle d'autorisation ne s'applique.
if (!$autorisation_accordee) {
    http_response_code(404);
    exit('Document introuvable.');
}
