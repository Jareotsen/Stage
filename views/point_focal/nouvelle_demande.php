<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
is_authenticated();

if ($_SESSION['role'] !== 'point_focal') {
    redirection_vers_les_dashboards($_SESSION['role']);
}



$maStructureStmt = $db->prepare("SELECT * FROM structure WHERE id_responsable = :id_responsable");
$maStructureStmt->execute([':id_responsable' => $_SESSION['user_id']]);
$maStructure = $maStructureStmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
    exit('Requête invalide.');
}

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

require '../../includes/header_dashboard.php';
?>

<?php $pageActive = 'demandes'; require '../../includes/sidebar_point_focal.php'; ?>
<link rel="stylesheet" href="../../publique/css/theme.css">

<div class="container" style="max-width: 760px;">
  <div class="demande-hero">
    <div class="demande-hero-icone">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </div>
    <h1 class="h3">Soumettre une demande de solution</h1>
    <p>Remplissez ce formulaire pour permettre au Ministère de mieux cerner votre besoin, sans déplacement préalable.</p>
  </div>
</div>

<main class="container my-4 mb-5" style="max-width: 760px;">
 <div class="demande-carte">
  <form action="" method="post">

    <!-- SECTION : LE THÈME -->
    <div class="demande-section">
      <div class="demande-section-entete">
        <div class="demande-section-icone">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
        </div>
        <div>
          <h2>Le thème</h2>
          <p class="section-soustitre">Un intitulé clair et synthétique</p>
        </div>
      </div>

      <input type="text" name="titre_theme" class="form-control" placeholder="Ex. : Digitalisation du suivi des dossiers" required>
    </div>


    <!-- SECTION : CERNER LE BESOIN (design copié) -->
    <div class="demande-section">
      <div class="demande-section-entete">
        <div class="demande-section-icone">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        </div>
        <div>
          <h2>Cerner le besoin</h2>
          <p class="section-soustitre">Le contexte et le problème à résoudre</p>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Contexte *</label>
          <textarea name="contexte" class="form-control" rows="3" required></textarea>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Problématique *</label>
          <textarea name="problematique" class="form-control" rows="3" required></textarea>
        </div>
      </div>
    </div>


    <!-- SECTION : DÉTAILS COMPLÉMENTAIRES (design copié) -->
    <div class="demande-section">
      <div class="demande-section-entete">
        <div class="demande-section-icone">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <div>
          <h2>Détails complémentaires</h2>
          <p class="section-soustitre">Facultatif, mais utile pour bien cerner le sujet</p>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="mb-3">
            <label class="form-label">Objectifs attendus</label>
            <textarea name="objectifs" class="form-control" rows="2"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Utilisateurs concernés</label>
            <textarea name="utilisateurs_concernes" class="form-control" rows="2"></textarea>
          </div>
        </div>

        <div class="col-md-6">
          <div class="mb-3">
            <label class="form-label">Contraintes connues</label>
            <textarea name="contraintes" class="form-control" rows="2"></textarea>
          </div>

          <div class="mb-0">
            <label class="form-label">Délai souhaité</label>
            <input type="text" name="delai_souhaite" class="form-control">
          </div>
        </div>
      </div>
    </div>


    <!-- SECTION : STRUCTURES CONCERNÉES (inchangée) -->
    <div class="demande-section" style="margin-bottom: 2rem;">
      <div class="demande-section-entete">
        <div class="demande-section-icone">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
        </div>
        <div>
          <h2>Structures concernées</h2>
          <p class="section-soustitre">Votre structure est automatiquement incluse</p>
        </div>
      </div>

      <div class="structures-liste">
        <?php foreach ($toutesStructures as $s): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="structures[]" value="<?= $s['id_struc'] ?>" id="s<?= $s['id_struc'] ?>"
              <?= ($maStructure && $s['id_struc'] == $maStructure['id_struc']) ? 'checked disabled' : '' ?>>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">
            <label class="form-check-label" for="s<?= $s['id_struc'] ?>"><?= htmlspecialchars($s['nom_struc']) ?></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <button type="submit" class="btn-envoyer-demande">
      Envoyer la demande
    </button>

  </form>
</div>
  <script>
document.addEventListener("DOMContentLoaded", () => {

  const requiredFields = document.querySelectorAll(
    "input[required], textarea[required], select[required]"
  );

  requiredFields.forEach(field => {
    field.classList.add("required");

    field.addEventListener("input", () => validateField(field));
    field.addEventListener("change", () => validateField(field));
  });

  function validateField(field) {
    if (field.value.trim() === "") {
      field.classList.add("invalid");
      field.classList.remove("valid");
    } else {
      field.classList.add("valid");
      field.classList.remove("invalid");
    }
  }
});
</script>

</main>

<?php require '../../includes/footer_dashboard.php'; ?>