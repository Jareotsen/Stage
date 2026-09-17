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

<style>
  .demande-hero {
    background: linear-gradient(135deg, #1B7A3D 0%, #145C2E 100%);
    color: #fff;
    border-radius: 14px;
    padding: 2rem 2.25rem;
    margin-bottom: -2.5rem;
    position: relative;
    z-index: 1;
  }
  .demande-hero-icone {
    width: 52px; height: 52px; border-radius: 12px;
    background: rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 0.9rem;
  }
  .demande-hero h1 { font-weight: 700; margin-bottom: 0.35rem; }
  .demande-hero p { color: rgba(255,255,255,0.88); margin: 0; max-width: 60ch; }

  .demande-carte {
    position: relative; z-index: 2;
    background: #fff; border-radius: 16px;
    box-shadow: 0 10px 34px rgba(0,0,0,0.08);
    padding: 2.25rem;
    margin-top: 3.5rem;
  }

  .demande-section { padding-left: 1rem; border-left: 4px solid #1B7A3D; margin-bottom: 1.75rem; }
  .demande-section-entete { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; }
  .demande-section-icone {
    width: 34px; height: 34px; border-radius: 9px;
    background: #E7F4EB; color: #1B7A3D;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .demande-section h2 { font-size: 1.05rem; font-weight: 700; margin: 0; }
  .demande-section .section-soustitre { font-size: 0.82rem; color: #8a8a80; margin: 0; }

  .demande-carte .form-control, .demande-carte .form-select {
    border-color: #e2e2e2; padding: 0.65rem 0.9rem;
  }
  .demande-carte .form-control:focus, .demande-carte .form-select:focus {
    border-color: #1B7A3D; box-shadow: 0 0 0 3px rgba(27,122,61,0.12);
  }
  .demande-carte label.form-label { font-weight: 600; font-size: 0.9rem; }

  .structures-liste {
    max-height: 220px; overflow-y: auto;
    border: 1px solid #e2e2e2; border-radius: 10px; padding: 0.75rem 1rem;
    background: #FAFAF7;
  }
  .structures-liste .form-check { padding-top: 0.3rem; padding-bottom: 0.3rem; }
  .structures-liste .form-check-input:checked { background-color: #1B7A3D; border-color: #1B7A3D; }

  .btn-envoyer-demande {
    background: #1B7A3D; color: #fff; font-weight: 600;
    padding: 0.75rem 1.5rem; border-radius: 10px; border: none;
    display: inline-flex; align-items: center; gap: 0.5rem;
  }
  .btn-envoyer-demande:hover { background: #145C2E; color: #fff; }
  /* Champs obligatoires en rouge */
.form-control.required,
.form-select.required {
  border: 2px solid #d9534f !important;
}

/* Quand c'est valide → bordure verte */
.form-control.valid,
.form-select.valid {
  border: 2px solid #1B7A3D !important;
}

/* Quand c'est invalide → bordure rouge */
.form-control.invalid,
.form-select.invalid {
  border: 2px solid #d9534f !important;
}
/* Champs obligatoires en rouge */
.form-control.required,
.form-select.required {
  border: 2px solid #d9534f !important;
}

/* Quand c'est valide → bordure verte */
.form-control.valid,
.form-select.valid {
  border: 2px solid #1B7A3D !important;
}

/* Quand c'est invalide → bordure rouge */
.form-control.invalid,
.form-select.invalid {
  border: 2px solid #d9534f !important;
}

</style>

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

<?php require '../../includes/footer.php'; ?>