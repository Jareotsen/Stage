<?php
// Header minimal pour les dashboards internes (point_focal, agent_ministere, super_admin).
// Pas de navigation publique ici : la navigation est assurée par le sidebar de chaque dashboard.

// ?v=date de modification : le navigateur recharge la feuille de style dès qu'elle change
$versionCss = static fn(string $fichier): int => (int) @filemtime(__DIR__ . '/../publique/css/' . $fichier);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tableau de bord — MRRI</title>

<link href="../../publique/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../../publique/vendor/fonts/fonts.css" rel="stylesheet">
<link href="../../publique/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../publique/css/public.css?v=<?= $versionCss('public.css') ?>">
<link rel="stylesheet" href="../../publique/css/theme.css?v=<?= $versionCss('theme.css') ?>">
<link rel="stylesheet" href="../../publique/css/dashboard.css?v=<?= $versionCss('dashboard.css') ?>">
</head>
<body>