<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/flash.php';
is_authenticated();

if ($_SESSION['role'] !== 'super_admin') {
    redirection_vers_les_dashboards($_SESSION['role']);
    exit();
}

$struc = $db->prepare("SELECT structure.nom_struc, structure.id_struc FROM structure");
$struc->execute();
$structures = $struc->fetchAll(PDO::FETCH_ASSOC);

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $structure = $_POST['structure'] ?? null;

    if ($role === 'point_focal' && !$structure) {
        $erreur = "Veuillez sélectionner une structure pour le point focal.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt1 = $db->prepare("INSERT INTO utilisateurs (username, password, role) VALUES (:username, :password, :role)");
        $stmt1->execute([':username' => $username, ':password' => $hashedPassword, ':role' => $role]);
        $nouvelId = $db->lastInsertId();

        if ($role === 'point_focal') {
            $stmt2 = $db->prepare("UPDATE structure SET id_responsable = :id WHERE id_struc = :id_struc");
            $stmt2->execute([':id' => $nouvelId, ':id_struc' => $structure]);
        }

        definir_flash("Compte créé avec succès.");
        header("Location: dashboard.php");
        exit();
    }
}

// Statistiques pour les cartes du tableau de bord
$nbStructures = count($structures);
$nbUtilisateurs = (int) $db->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$nbCategories = (int) $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();

require '../../includes/header_dashboard.php';
?>

<div class="mrri-dashboard dashboard-super-admin">

    <aside class="mrri-sidebar">
        <div class="mrri-sidebar-brand">
            <img src="../../publique/image/sceau.png" alt="Armoiries du Gabon">
            <div>
                <strong>Ministère de la Réforme<br>et des Institutions</strong>
                <small>Super administrateur</small>
            </div>
        </div>

        <div class="mrri-sidebar-section">Navigation</div>
        <ul class="mrri-sidebar-nav">
            <li>
                <a href="dashboard.php" class="mrri-sidebar-link active">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    </span>
                    Tableau de bord
                </a>
            </li>
            <li>
                <a href="gerer_compte.php" class="mrri-sidebar-link">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    Comptes utilisateurs
                </a>
            </li>
            <li>
                <a href="gerer_categories.php" class="mrri-sidebar-link">
                    <span class="icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h13a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4z"/></svg>
                    </span>
                    Catégories
                </a>
            </li>
        </ul>

        <div class="mrri-sidebar-bottom">
            <a href="../../deconnexion.php" class="mrri-sidebar-logout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Déconnexion
            </a>
        </div>
    </aside>

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
                         <svg width="64" height="64" viewBox="0 0 64 64" 
     xmlns="http://www.w3.org/2000/svg" fill="none">
  <!-- Socle -->
  <rect x="10" y="46" width="44" height="6" fill="#2F3B4C"/>

  <!-- Colonnes -->
  <rect x="14" y="26" width="6" height="20" fill="#4A5A70"/>
  <rect x="29" y="26" width="6" height="20" fill="#4A5A70"/>
  <rect x="44" y="26" width="6" height="20" fill="#4A5A70"/>

  <!-- Fronton -->
  <polygon points="32,12 8,26 56,26" fill="#2F3B4C"/>

  <!-- Balance au centre -->
  <line x1="32" y1="16" x2="32" y2="24" stroke="#D9A441" stroke-width="2"/>
  <line x1="24" y1="20" x2="40" y2="20" stroke="#D9A441" stroke-width="2"/>

  <!-- Plateaux -->
  <circle cx="24" cy="24" r="3" stroke="#D9A441" stroke-width="2" fill="white"/>
  <circle cx="40" cy="24" r="3" stroke="#D9A441" stroke-width="2" fill="white"/>
</svg>

                    </svg>
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
                                <div class="mb-3">
                                    <label class="mrri-form-label">Identifiant</label>
                                    <input type="text" name="username" class="mrri-form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="mrri-form-label">Mot de passe</label>
                                    <input type="password" name="password" class="mrri-form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="mrri-form-label">Rôle</label>
                                    <select name="role" id="role" class="mrri-form-control">
                                        <option value="point_focal">Point Focal</option>
                                        <option value="agent_ministere">Agent Ministère</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="champ_structure">
                                    <label class="mrri-form-label">Structure rattachée</label>
                                    <select name="structure" class="mrri-form-control">
                                        <?php foreach ($structures as $s): ?>
                                            <option value="<?= $s['id_struc'] ?>"><?= htmlspecialchars($s['nom_struc']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
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