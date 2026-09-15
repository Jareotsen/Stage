<?php
session_start();
require '../../config/database.php';

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        switch ($user['role']) {
            case 'super_admin': header('Location: ../super_admin/dashboard.php'); exit;
            case 'point_focal': header('Location: ../point_focal/dashboard.php'); exit;
            case 'agent_ministere': header('Location: ../agent_ministere/dashboard.php'); exit;
            default: $erreur = "Rôle utilisateur inconnu.";
        }
    } else {
        $erreur = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}

require '../../includes/header.php';
?>

<main class="container my-5" style="max-width: 420px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 text-center">Connexion</h1>
            <p class="text-center text-secondary small mb-3">Espace réservé aux agents et points focaux</p>

            <?php if ($erreur): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

            <form action="connexion.php" method="post">
                <div class="mb-3">
                    <label class="form-label" for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-mrri w-100">Se connecter</button>
            </form>
        </div>
    </div>
</main>

<?php require '../../includes/footer.php'; ?>