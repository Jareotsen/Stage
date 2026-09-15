<?php
session_start();

function is_authenticated() {
   if (isset($_SESSION['user_id'])) {
       return true;
   } else {
       header('Location: ../views/public/connexion.php');
       exit;
   }
}

function redirection_vers_les_dashboards($role) {
    switch ($role) {
        case 'super_admin':
            header('Location: ../views/super_admin/dashboard.php');
            exit;
        case 'point_focal':
            header('Location: ../views/point_focal/dashboard.php');
            exit;
        case 'agent_ministere':
            header('Location: ../views/agent_ministere/dashboard.php');
            exit;
        default:
            echo "Rôle utilisateur inconnu.";
            exit;
    }
    
}     