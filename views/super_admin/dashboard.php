<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
require_once '../../includes/csrf.php';
is_authenticated();
$token = generer_token_csrf();
if ($_SESSION['role'] !== 'super_admin') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

$struc = $db->prepare("SELECT nom_struc, id_struc, id_responsable FROM structure ORDER BY nom_struc");
$struc->execute();
$structures = $struc->fetchAll(PDO::FETCH_ASSOC);

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
    exit('Requête invalide.');
}
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $structure = filter_var($_POST['structure'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    // Structure choisie pour un point focal : doit exister et ne pas avoir déjà de responsable
    $structureChoisie = null;
    if ($role === 'point_focal' && $structure) {
        $verifStructure = $db->prepare("SELECT id_struc, id_responsable FROM structure WHERE id_struc = :id");
        $verifStructure->execute([':id' => $structure]);
        $structureChoisie = $verifStructure->fetch(PDO::FETCH_ASSOC);
    }

    // Vérification des champs
    if ($username === '' || $password === '') {

        $erreur = "Veuillez remplir tous les champs obligatoires.";

    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,20}$/', $username)) {

        // La colonne username fait 20 caractères : au-delà, MySQL tronquerait l'identifiant
        $erreur = "L'identifiant doit contenir de 3 à 20 caractères (lettres, chiffres, point, tiret ou tiret bas).";

    } elseif (mb_strlen($password) < 8) {

        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";

    } elseif (!in_array($role, ['point_focal', 'agent_ministere'], true)) {

        // Liste blanche : on ne peut pas créer de super administrateur depuis ce formulaire
        $erreur = "Rôle invalide.";

    } elseif ($role === 'point_focal' && !$structureChoisie) {

        $erreur = "Veuillez sélectionner une structure pour le point focal.";

    } elseif ($role === 'point_focal' && !empty($structureChoisie['id_responsable'])) {

        $erreur = "Cette structure a déjà un point focal. Modifiez d'abord son rattachement dans « Gérer les comptes ».";

    } else {

        // Vérifier si le pseudo existe déjà
        $verification = $db->prepare("
            SELECT id 
            FROM utilisateurs 
            WHERE username = :username
            LIMIT 1
        ");

        $verification->execute([
            ':username' => $username
        ]);

        if ($verification->fetch()) {

            $erreur = "Ce nom d'utilisateur est déjà utilisé.";

        } else {

            try {

                // Compte + rattachement à la structure : tout ou rien
                $db->beginTransaction();

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt1 = $db->prepare("
                    INSERT INTO utilisateurs 
                    (username, password, role)
                    VALUES 
                    (:username, :password, :role)
                ");

                $stmt1->execute([
                    ':username' => $username,
                    ':password' => $hashedPassword,
                    ':role' => $role
                ]);

                $nouvelId = $db->lastInsertId();

                // Si le compte est un point focal,
                // rattacher le compte à la structure
                if ($role === 'point_focal') {

                    $stmt2 = $db->prepare("
                        UPDATE structure 
                        SET id_responsable = :id
                        WHERE id_struc = :id_struc
                    ");

                    $stmt2->execute([
                        ':id' => $nouvelId,
                        ':id_struc' => $structure
                    ]);
                }

                $db->commit();
                definir_flash("Compte créé avec succès.");

                header("Location: dashboard.php");
                exit();
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                // Détail technique uniquement dans les logs
                error_log(
                    "Erreur création compte : " . $e->getMessage()
                );
                // Message destiné à l'utilisateur
                $erreur = "Impossible de créer le compte. Veuillez réessayer.";
            }
        }
    }
}

// Statistiques pour les cartes du tableau de bord
$nbStructures = count($structures);
$nbUtilisateurs = (int) $db->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$nbCategories = (int) $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();

require '../../includes/header_dashboard.php';
?>

<div class="mrri-dashboard dashboard-super-admin">

  <?php $pageActive = 'dashboard'; require '../../includes/sidebar_super_admin.php'; ?>

    <main class="mrri-main">
        <header class="mrri-topbar">
            <div class="mrri-topbar-title">
                MRRI / <strong>Tableau de bord</strong>
            </div>
            <div class="mrri-user">
                <div class="mrri-user-avatar">SA</div>
                <div class="mrri-user-info">
                    <div class="mrri-user-name">Super administrateur</div>
                    <div class="mrri-user-role">Administration globale</div>
                </div>
            </div>
        </header>

        <section class="mrri-content">

            <?php afficher_flash(); ?>

            <div class="mrri-page-header">
                <div>
                    <div class="mrri-breadcrumb">Espace professionnel / <span class="active">Tableau de bord</span></div>
                    <h1 class="mrri-page-title">Tableau de bord — Super administrateur</h1>
                    <p class="mrri-page-description">Gestion des comptes et des catégories de la plateforme</p>
                </div>
            </div>

            <div class="mrri-stat-grid">
                <div class="mrri-stat-card stat-or">
                    <div class="mrri-stat-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div class="mrri-stat-label">Comptes utilisateurs</div>
                    <div class="mrri-stat-value"><?= $nbUtilisateurs ?></div>
                </div>
                <div class="mrri-stat-card stat-bleu">
                    <div class="mrri-stat-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="21" x2="21" y2="21"/><line x1="5" y1="21" x2="5" y2="10"/><line x1="19" y1="21" x2="19" y2="10"/><line x1="9" y1="21" x2="9" y2="10"/><line x1="15" y1="21" x2="15" y2="10"/><polygon points="12 3 21 9 3 9"/></svg>
                    </div>
                    <div class="mrri-stat-label">Structures</div>
                    <div class="mrri-stat-value"><?= $nbStructures ?></div>
                </div>
                <div class="mrri-stat-card stat-vert">
                    <div class="mrri-stat-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h13a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4z"/></svg>
                    </div>
                    <div class="mrri-stat-label">Catégories</div>
                    <div class="mrri-stat-value"><?= $nbCategories ?></div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="mrri-panel">
                        <div class="mrri-panel-header">
                            <h2 class="mrri-panel-title">Créer un compte</h2>
                        </div>
                        <div class="mrri-panel-body">

                            <?php if ($erreur): ?>
                                <div class="mrri-alert mrri-alert-danger"><?= htmlspecialchars($erreur) ?></div>
                            <?php endif; ?>

                            <form action="" method="post">
                                <?php $roleSaisi = $_POST['role'] ?? 'point_focal'; ?>
                                <div class="mb-3">
                                    <label class="mrri-form-label" for="username">Identifiant</label>
                                    <input type="text" id="username" name="username" class="mrri-form-control"
                                           required minlength="3" maxlength="20" pattern="[A-Za-z0-9._\-]{3,20}"
                                           autocomplete="off" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                                    <div class="form-text">3 à 20 caractères : lettres, chiffres, point, tiret, tiret bas.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="mrri-form-label" for="password">Mot de passe</label>
                                    <input type="password" id="password" name="password" class="mrri-form-control"
                                           required minlength="8" autocomplete="new-password">
                                    <div class="form-text">8 caractères minimum.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="mrri-form-label" for="role">Rôle</label>
                                    <select name="role" id="role" class="mrri-form-control">
                                        <option value="point_focal" <?= $roleSaisi === 'point_focal' ? 'selected' : '' ?>>Point Focal</option>
                                        <option value="agent_ministere" <?= $roleSaisi === 'agent_ministere' ? 'selected' : '' ?>>Agent Ministère</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="champ_structure" <?= $roleSaisi === 'agent_ministere' ? 'style="display:none"' : '' ?>>
                                    <label class="mrri-form-label" for="structure">Structure rattachée</label>
                                    <select name="structure" id="structure" class="mrri-form-control">
                                        <?php foreach ($structures as $s): ?>
                                            <option value="<?= (int) $s['id_struc'] ?>" <?= !empty($s['id_responsable']) ? 'disabled' : '' ?>>
                                                <?= htmlspecialchars($s['nom_struc']) ?><?= !empty($s['id_responsable']) ? ' — déjà un point focal' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_token_csrf()) ?>">

                                <button type="submit" class="mrri-btn mrri-btn-primary">Créer le compte</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="mrri-panel">
                        <div class="mrri-panel-header">
                            <h2 class="mrri-panel-title">Accès rapides</h2>
                        </div>
                        <div class="mrri-panel-body d-flex flex-column gap-2">
                            <a href="gerer_compte.php" class="mrri-btn mrri-btn-outline">Gérer les comptes</a>
                            <a href="gerer_categories.php" class="mrri-btn mrri-btn-outline">Gérer les catégories</a>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </main>
</div>

<script>
    let champStructure = document.getElementById('champ_structure');
    let role = document.getElementById('role');
    role.addEventListener('change', function() {
        champStructure.style.display = role.value === 'point_focal' ? 'block' : 'none';
    });
</script>

<?php require '../../includes/footer_dashboard.php'; ?>
