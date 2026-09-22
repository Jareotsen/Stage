<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
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

$mon_actu = [];
if($structures){
    $mon_actu = $db->prepare("SELECT *
                            FROM actualites AS a
                            JOIN structure AS b
                            ON a.id_structure = b.id_struc
                            WHERE a.id_structure = :id_structure
                            ORDER BY a.date_publication DESC");
    $mon_actu->execute([ ':id_structure' => $structures['id_struc']]);
    $mon_actu = $mon_actu->fetchAll(PDO::FETCH_ASSOC);
}
if(empty($mon_actu)){
    echo"Aucune Actualité";
}
foreach($mon_actu as $actualite){
?>
<form action="supprimer_actualites.php" method="post" onsubmit="return confirm('Supprimer cette actualité ?');">
    <input type="hidden" name="id_actu" value="<?= htmlspecialchars($actualite['id_actu'], ENT_QUOTES, 'UTF-8') ?>">
    <p><?= htmlspecialchars($actualite['titre']) ?></p>
        <p><?= htmlspecialchars($actualite['date_publication']) ?></p>
    <button type="submit">Supprimer</button>
</form>
<?php
}