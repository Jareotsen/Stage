<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';

is_authenticated();

$role = $_SESSION['role'];

if (!in_array($role, ['point_focal', 'agent_ministere'], true)) {
    redirection_vers_les_dashboards($role);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !verifier_token_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Requête invalide.');
}

$id = filter_input(INPUT_POST, 'id_consultation', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('Consultation invalide.');
}

$stmt = $db->prepare("
    UPDATE consultations
    SET date_cloture_effective = NOW()
    WHERE id_consultation = :id
      AND id_createur = :id_createur
      AND date_cloture_effective IS NULL
      AND (date_fermeture IS NULL OR date_fermeture > NOW())
");

$stmt->execute([
    ':id' => $id,
    ':id_createur' => $_SESSION['user_id'],
]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    exit('Consultation introuvable, déjà clôturée ou non autorisée.');
}

definir_flash('Consultation clôturée avec succès.');
header('Location: mes_consultations.php');
exit();