<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Structures sous tutelle — Ministère des Réformes et des Relations avec les Institutions</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@500;600;700&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../public/css/style.css">

<style>
  :root { --vert-mrri: #1B7A3D; }
  .navbar-brand { font-weight: 700; }
  .btn-mrri { background-color: var(--vert-mrri); color: #fff; }
  .btn-mrri:hover { background-color: #145C2E; color: #fff; }
  .nav-link.active { color: var(--vert-mrri) !important; font-weight: 600; }
  .bandeau-drapeau { height: 5px; background: linear-gradient(90deg, #1B7A3D 0 33%, #FBCE07 33% 66%, #142A6B 66% 100%); }
</style>
</head>
<?php
$lienDashboard = '#';
$estAgentMinistere = isset($_SESSION['role']) && $_SESSION['role'] === 'agent_ministere';
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'super_admin': $lienDashboard = '../super_admin/dashboard.php'; break;
        case 'point_focal': $lienDashboard = '../point_focal/dashboard.php'; break;
        case 'agent_ministere': $lienDashboard = '../agent_ministere/dashboard.php'; break;
    }
}
?>
<div class="bandeau-drapeau"></div>
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="../../public/accueil.php">
      <img src="../../public/image/sceau.png" alt="Armoiries de la République Gabonaise" width="42" height="42">
      <span>Ministère de la Réforme et des <br> Relations avec les Institutions</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMrri">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMrri">
  <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
    <li class="nav-item"><a class="nav-link active" href="../public/accueil.php">Accueil</a></li>
    <li class="nav-item"><a class="nav-link" href="../public/catalogue.php">Structures</a></li>
    <li class="nav-item"><a class="nav-link" href="#">Actualités</a></li>
    <?php if ($estAgentMinistere): ?>
      <li class="nav-item"><a class="nav-link" href="../agent_ministere/demandes.php">Demandes</a></li>
    <?php endif; ?>
  </ul>
  <?php if (isset($_SESSION['user_id'])): ?>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= $lienDashboard ?>" class="btn btn-mrri btn-sm">Tableau de bord</a>
      <a href="../../deconnexion.php" class="btn btn-outline-secondary btn-sm">Déconnexion</a>
    </div>
  <?php endif; ?>
</div>
    </div>
  </div>
</nav>