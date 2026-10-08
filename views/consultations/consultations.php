<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
is_authenticated();

$role = $_SESSION['role'];

if ($role !== 'point_focal' && $role !== 'agent_ministere') {
    redirection_vers_les_dashboards($role);
    exit();
}

// Pour un point focal : sa propre structure (pas de choix)
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

    if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
        exit('Requête invalide.');
    }

    $titre = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $dateFermetureBrute = $_POST['date_fermeture'] ?? '';

    // Détermination de id_structure selon le rôle
    if ($role === 'point_focal') {
        $id_structure = $maStructure['id_struc'];
    } else {
        // agent_ministere : structure choisie dans le formulaire, ou NULL pour une consultation générale
        $id_structure = !empty($_POST['id_structure']) ? (int) $_POST['id_structure'] : null;
    }

    if ($titre === '' || $contenu === '') {
        $erreur = "Le titre et le contenu sont obligatoires.";
    } elseif ($dateFermetureBrute !== '' && strtotime($dateFermetureBrute) <= time()) {
    $erreur = "La date de clôture doit être postérieure à aujourd'hui.";
    } else {
        $date_fermeture = $dateFermetureBrute !== '' ? date('Y-m-d H:i:s', strtotime($dateFermetureBrute)) : null;

        $insertStmt = $db->prepare("
            INSERT INTO consultations (titre, contenu, id_structure, id_createur, date_fermeture)
            VALUES (:titre, :contenu, :id_structure, :id_createur, :date_fermeture)
        ");
        $insertStmt->execute([
            ':titre' => $titre,
            ':contenu' => $contenu,
            ':id_structure' => $id_structure,
            ':id_createur' => $_SESSION['user_id'],
            ':date_fermeture' => $date_fermeture,
        ]);

        definir_flash("Consultation publiée avec succès.");

        $dashboard = $role === 'point_focal' ? '../point_focal/dashboard.php' : '../agent_ministere/dashboard.php';
        header("Location: $dashboard");
        exit();
    }
}

require '../../includes/header_dashboard.php';
?>
<link rel="stylesheet" href="../../publique/vendor/bootstrap/css/bootstrap.min.css">

<main class="container my-4" style="max-width: 640px;">
    <h1 class="h3">Publier une consultation</h1>

    <?php if ($role === 'point_focal'): ?>
        <p class="text-secondary">Pour <strong><?= htmlspecialchars($maStructure['nom_struc']) ?></strong></p>
    <?php endif; ?>

    <?php if ($erreur): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">

        <div class="mb-3">
            <label class="form-label">Titre de la consultation</label>
            <input type="text" name="titre" class="form-control" placeholder="Ex. : Projet de réforme du statut des AAI" required value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Contenu / texte soumis à consultation</label>
            <textarea name="contenu" class="form-control" rows="6" required><?= htmlspecialchars($_POST['contenu'] ?? '') ?></textarea>
        </div>

        <?php if ($role === 'agent_ministere'): ?>
            <div class="mb-3">
                <label class="form-label">Portée de la consultation</label>
                <select name="id_structure" class="form-select">
                    <option value="">Consultation générale (MRRI)</option>
                    <?php foreach ($toutesStructures as $s): ?>
                        <option value="<?= $s['id_struc'] ?>"><?= htmlspecialchars($s['nom_struc']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Date de clôture (facultatif)</label>
<input type="date" name="date_fermeture" class="form-control" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">            <div class="form-text">Laisser vide pour une consultation sans date de clôture fixée à l'avance.</div>
        </div>

        <button type="submit" class="btn btn-mrri">Publier la consultation</button>
    </form>
</main>

<?php require '../../includes/footer_dashboard.php'; ?>