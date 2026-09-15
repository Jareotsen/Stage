<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
}

$maStructureStmt = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
$maStructureStmt->execute([':id_responsable' => $_SESSION['user_id']]);
$maStructure = $maStructureStmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titreTheme = $_POST['titre_theme'] ?? '';
    $contexte = $_POST['contexte'] ?? '';
    $problematique = $_POST['problematique'] ?? '';
    $objectifs = $_POST['objectifs'] ?? '';
    $utilisateursConcernes = $_POST['utilisateurs_concernes'] ?? '';
    $contraintes = $_POST['contraintes'] ?? '';
    $delaiSouhaite = $_POST['delai_souhaite'] ?? '';
    $structuresChoisies = $_POST['structures'] ?? [];

    $insertStmt = $db->prepare("INSERT INTO demandes (titre_theme, contexte, problematique, objectifs, utilisateurs_concernes, contraintes, delai_souhaite, id_point_focal) VALUES (:titre_theme, :contexte, :problematique, :objectifs, :utilisateurs_concernes, :contraintes, :delai_souhaite, :id_point_focal)");
    $insertStmt->execute([
        ':titre_theme' => $titreTheme, ':contexte' => $contexte, ':problematique' => $problematique,
        ':objectifs' => $objectifs, ':utilisateurs_concernes' => $utilisateursConcernes,
        ':contraintes' => $contraintes, ':delai_souhaite' => $delaiSouhaite, ':id_point_focal' => $_SESSION['user_id']
    ]);
    $idDemande = $db->lastInsertId();

    if ($maStructure && !in_array((string) $maStructure['id_struc'], $structuresChoisies)) {
        $structuresChoisies[] = $maStructure['id_struc'];
    }
    $lienStmt = $db->prepare("INSERT INTO demandes_structures (id_demande, id_structure) VALUES (:id_demande, :id_structure)");
    foreach ($structuresChoisies as $idStructure) {
        $lienStmt->execute([':id_demande' => $idDemande, ':id_structure' => (int) $idStructure]);
    }

    definir_flash("Votre demande a été envoyée avec succès.");
    header("Location: dashboard.php");
    exit();
}

$toutesStructuresStmt = $db->query("SELECT id_struc, nom_struc FROM structure ORDER BY nom_struc");
$toutesStructures = $toutesStructuresStmt->fetchAll(PDO::FETCH_ASSOC);

require '../../includes/header.php';
?>

<main class="container my-4 mb-5" style="max-width: 680px;">
    <h1 class="h3">Soumettre une demande de solution</h1>
    <p class="text-secondary">Remplissez ce formulaire pour permettre au Ministère de mieux cerner votre besoin avant tout échange.</p>

    <form action="" method="post">
        <div class="mb-3"><label class="form-label">Thème / intitulé de la demande</label><input type="text" name="titre_theme" class="form-control" placeholder="Ex. : Digitalisation du suivi des dossiers" required></div>
        <div class="mb-3"><label class="form-label">Contexte</label><textarea name="contexte" class="form-control" rows="3" placeholder="Dans quel cadre ce besoin est-il apparu ?" required></textarea></div>
        <div class="mb-3"><label class="form-label">Problématique</label><textarea name="problematique" class="form-control" rows="3" placeholder="Quel problème précis souhaitez-vous résoudre ?" required></textarea></div>
        <div class="mb-3"><label class="form-label">Objectifs attendus</label><textarea name="objectifs" class="form-control" rows="2" placeholder="Que doit permettre la solution une fois en place ?"></textarea></div>
        <div class="mb-3"><label class="form-label">Utilisateurs concernés</label><textarea name="utilisateurs_concernes" class="form-control" rows="2" placeholder="Qui utilisera la solution au quotidien ?"></textarea></div>
        <div class="mb-3"><label class="form-label">Contraintes connues</label><textarea name="contraintes" class="form-control" rows="2" placeholder="Budget, délais, contraintes techniques ou organisationnelles..."></textarea></div>
        <div class="mb-3"><label class="form-label">Délai souhaité</label><input type="text" name="delai_souhaite" class="form-control" placeholder="Ex. : 3 mois, avant fin d'année..."></div>

        <div class="mb-3">
            <label class="form-label">Structures concernées</label>
            <div class="form-text mt-0">Votre structure est automatiquement incluse.</div>
            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                <?php foreach ($toutesStructures as $s): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="structures[]" value="<?= $s['id_struc'] ?>"
                            <?= ($maStructure && $s['id_struc'] == $maStructure['id_struc']) ? 'checked disabled' : '' ?>>
                        <label class="form-check-label"><?= htmlspecialchars($s['nom_struc']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-mrri">Envoyer la demande</button>
    </form>
</main>

<?php require '../../includes/footer.php'; ?>