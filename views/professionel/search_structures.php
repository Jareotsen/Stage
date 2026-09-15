<?php
require '../../config/database.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {
    echo '';
    exit;
}

$stmt = $db->prepare("
    SELECT structure.id_struc, structure.nom_struc, structure.sigle, structure.adresse,
           structure.directeur_general, categories.nom_cat
    FROM structure
    JOIN categories ON structure.id_cat = categories.id_cat
    WHERE structure.nom_struc LIKE :q
       OR structure.sigle LIKE :q
       OR structure.adresse LIKE :q
       OR structure.directeur_general LIKE :q
       OR categories.nom_cat LIKE :q
    LIMIT 10
");
$stmt->execute(['q' => "%$q%"]);
$structures = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($structures)) {
    echo "<p class='text-muted'>Aucun résultat</p>";
    exit;
}

foreach ($structures as $s) {
    echo "
    <div class='p-3 border rounded mb-2 text-start'>
        <p class='fw-bold mb-1'>{$s['sigle']} - {$s['nom_struc']}</p>
        <p class='mb-1'>{$s['adresse']}</p>
        <p class='mb-1'>DG : {$s['directeur_general']}</p>
        <p class='small text-success'>{$s['nom_cat']}</p>
        <a href='fiche.php?id={$s['id_struc']}' class='btn btn-sm btn-outline-primary'>Voir la fiche</a>
    </div>
    ";
}
