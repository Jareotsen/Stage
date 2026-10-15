<?php
declare(strict_types=1);

session_start();
require '../../config/database.php';
require_once '../../includes/csrf.php';
require_once '../../includes/flash.php';
require_once '../../includes/dates.php';
require_once '../../includes/consultation_helpers.php';

/* =========================================================
   1. RÉCUPÉRATION DE LA CONSULTATION
   ========================================================= */
$id = $_GET['id'] ?? '';
$consultation = null;

if ($id !== '' && ctype_digit((string) $id)) {
    $id = (int) $id;
    $stmt = $db->prepare(
        "SELECT c.id_consultation, c.titre, c.contenu, c.date_ouverture, c.date_fermeture,
                c.statut, c.id_structure, s.nom_struc
         FROM consultations c
         LEFT JOIN structure s ON c.id_structure = s.id_struc
         WHERE c.id_consultation = :id AND c.statut != 'brouillon'"
    );
    $stmt->execute([':id' => $id]);
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$ouverte = $consultation ? consultationEstOuverte($consultation) : false;
$estGenerale = $consultation ? empty($consultation['id_structure']) : true;

$erreurDepot = null;
$emailSaisi = '';
$commentaireSaisi = '';
$urlPage = 'consultation_detail.php?id=' . (int) $id;

/* =========================================================
   2. TRAITEMENT DES FORMULAIRES (POST)
   ========================================================= */
if ($consultation && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF : même patron que sur le reste du site (dépôt ET vote)
    if (!verifier_token_csrf($_POST['csrf_token'] ?? null)) {
        exit('Requête invalide.');
    }

    // Aucune participation sur une consultation clôturée
    if (!$ouverte) {
        definir_flash("Cette consultation est clôturée.", 'erreur');
        header("Location: $urlPage");
        exit();
    }

    $action = $_POST['action'] ?? '';
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $emailValide = $email !== ''
        && mb_strlen($email) <= 190
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

    /* ---------- Dépôt d'une contribution ---------- */
    if ($action === 'deposer') {
        $commentaire = trim($_POST['commentaire'] ?? '');
        $emailSaisi = $email;
        $commentaireSaisi = $commentaire;
        $longueur = mb_strlen($commentaire);

        if (!$emailValide) {
            $erreurDepot = "Veuillez saisir une adresse email valide.";
        } elseif ($longueur < 10 || $longueur > 2000) {
            $erreurDepot = "Le commentaire doit contenir entre 10 et 2000 caractères.";
        } else {
            // Statut 'en_attente' par défaut : invisible tant qu'un modérateur n'a pas validé
            $insert = $db->prepare(
                "INSERT INTO contributions (id_consultation, email_contributeur, contenu)
                 VALUES (:id_consultation, :email, :commentaire)"
            );
            $insert->execute([
                ':id_consultation' => $consultation['id_consultation'],
                ':email' => $email,
                ':commentaire' => $commentaire,
            ]);

            $_SESSION['email_citoyen'] = $email;
            definir_flash("Merci ! Votre contribution a bien été reçue. Elle sera publiée après modération.");
            header("Location: $urlPage#contributions");
            exit();
        }
    }

    /* ---------- Vote sur une contribution ---------- */
    elseif ($action === 'voter') {
        $idContribution = (int) ($_POST['id_contribution'] ?? 0);
        $valeur = $_POST['valeur'] ?? '';

        if (!$emailValide) {
            definir_flash("Veuillez saisir une adresse email valide pour voter.", 'erreur');
        } elseif (!in_array($valeur, ['pour', 'contre'], true)) {
            definir_flash("Vote invalide.", 'erreur');
        } else {
            // La contribution doit appartenir à CETTE consultation et être validée
            $verif = $db->prepare(
                "SELECT id_contribution FROM contributions
                 WHERE id_contribution = :id_contribution
                   AND id_consultation = :id_consultation
                   AND statut = 'validee'"
            );
            $verif->execute([
                ':id_contribution' => $idContribution,
                ':id_consultation' => $consultation['id_consultation'],
            ]);

            if (!$verif->fetch()) {
                definir_flash("Contribution introuvable.", 'erreur');
            } else {
                try {
                    $vote = $db->prepare(
                        "INSERT INTO votes (id_contribution, email_votant, valeur_vote)
                         VALUES (:id_contribution, :email, :valeur)"
                    );
                    $vote->execute([
                        ':id_contribution' => $idContribution,
                        ':email' => $email,
                        ':valeur' => $valeur,
                    ]);
                    $_SESSION['email_citoyen'] = $email;
                    definir_flash("Votre vote a bien été enregistré.");
                } catch (PDOException $e) {
                    // 1062 = violation de la contrainte UNIQUE (id_contribution, email)
                    if (($e->errorInfo[1] ?? null) === 1062) {
                        definir_flash("Cette adresse email a déjà voté sur cette contribution.", 'erreur');
                    } else {
                        error_log("Erreur vote : " . $e->getMessage());
                        definir_flash("Impossible d'enregistrer votre vote. Veuillez réessayer.", 'erreur');
                    }
                }
            }
        }
        header("Location: $urlPage#contributions");
        exit();
    }
}

/* =========================================================
   3. CONTRIBUTIONS VALIDÉES UNIQUEMENT (+ décompte des votes)
   L'email n'est volontairement jamais sélectionné.
   ========================================================= */
$contributions = [];
if ($consultation) {
    $contribStmt = $db->prepare(
        "SELECT c.id_contribution, c.contenu, c.date_contribution,
                (SELECT COUNT(*) FROM votes v WHERE v.id_contribution = c.id_contribution AND v.valeur_vote = 'pour')   AS nb_pour,
                (SELECT COUNT(*) FROM votes v WHERE v.id_contribution = c.id_contribution AND v.valeur_vote = 'contre') AS nb_contre
         FROM contributions c
         WHERE c.id_consultation = :id AND c.statut = 'validee'
         ORDER BY c.date_contribution DESC"
    );
    $contribStmt->execute([':id' => $consultation['id_consultation']]);
    $contributions = $contribStmt->fetchAll(PDO::FETCH_ASSOC);
}

$token = generer_token_csrf();
$emailMemorise = $_SESSION['email_citoyen'] ?? '';

require '../../includes/header.php';
?>

<link rel="stylesheet" href="../../publique/css/theme.css">

<main class="cd-page">
    <a class="cd-retour" href="consultations.php">← Retour aux consultations</a>

    <?php if (!$consultation): ?>
        <div class="cd-carte" style="text-align:center;">
            <h1>Consultation introuvable</h1>
            <p>Cette consultation n'existe pas ou n'est pas disponible.</p>
        </div>
    <?php else: ?>

        <?php afficher_flash(); ?>

        <!-- ================= CONSULTATION ================= -->
        <article class="cd-carte <?= $estGenerale ? 'generale' : 'structure' ?>">
            <span class="cd-badge <?= $estGenerale ? 'generale' : 'structure' ?>">
                <?= $estGenerale ? 'Consultation générale' : echapper($consultation['nom_struc']) ?>
            </span>
            <span class="cd-badge <?= $ouverte ? 'ouverte' : 'cloturee' ?>">
                <?= $ouverte ? 'Ouverte' : 'Clôturée' ?>
            </span>

            <h1><?= echapper($consultation['titre']) ?></h1>
            <div class="cd-meta">
                Ouverte le <?= formaterDateFr($consultation['date_ouverture']) ?>
                <?php if (!empty($consultation['date_fermeture'])): ?>
                    · <?= $ouverte ? 'Clôture le' : 'Clôturée le' ?> <?= formaterDateFr($consultation['date_fermeture']) ?>
                <?php endif; ?>
            </div>

            <div class="cd-texte"><?= nl2br(echapper($consultation['contenu'])) ?></div>
        </article>

        <!-- ================= CONTRIBUTIONS VALIDÉES ================= -->
        <section class="cd-carte" id="contributions">
            <h2 class="cd-section-titre">Contributions (<?= count($contributions) ?>)</h2>

            <?php if (empty($contributions)): ?>
                <p>Aucune contribution publiée pour le moment.</p>
            <?php endif; ?>

            <?php foreach ($contributions as $contribution): ?>
                <div class="cd-contrib">
                    <div class="cd-contrib-meta">
                        Citoyen · <?= formaterDateFr($contribution['date_contribution']) ?>
                    </div>
                    <div class="cd-contrib-texte"><?= nl2br(echapper($contribution['contenu'])) ?></div>

                    <div class="cd-vote">
                        <span class="cd-score">
                            👍 <?= (int) $contribution['nb_pour'] ?> pour · 👎 <?= (int) $contribution['nb_contre'] ?> contre
                        </span>

                        <?php if ($ouverte): ?>
                            <form action="" method="post" class="cd-vote">
                                <input type="hidden" name="csrf_token" value="<?= echapper($token) ?>">
                                <input type="hidden" name="action" value="voter">
                                <input type="hidden" name="id_contribution" value="<?= (int) $contribution['id_contribution'] ?>">
                                <input type="email" name="email" placeholder="Votre email pour voter" required
                                       value="<?= echapper($emailMemorise) ?>">
                                <button type="submit" name="valeur" value="pour" class="cd-btn-vote pour">Pour</button>
                                <button type="submit" name="valeur" value="contre" class="cd-btn-vote contre">Contre</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- ================= FORMULAIRE DE DÉPÔT ================= -->
        <section class="cd-carte" id="deposer">
            <h2 class="cd-section-titre">Déposer une contribution</h2>

            <?php if (!$ouverte): ?>
                <p>Cette consultation est clôturée : il n'est plus possible de contribuer.</p>
            <?php else: ?>
                <?php if ($erreurDepot): ?>
                    <div class="alert alert-danger"><?= echapper($erreurDepot) ?></div>
                <?php endif; ?>

                <form action="#deposer" method="post" class="cd-form">
                    <input type="hidden" name="csrf_token" value="<?= echapper($token) ?>">
                    <input type="hidden" name="action" value="deposer">

                    <label for="email">Votre email</label>
                    <input type="email" id="email" name="email" required maxlength="190"
                           value="<?= echapper($emailSaisi !== '' ? $emailSaisi : $emailMemorise) ?>">
                    <div class="cd-aide">Votre email n'est jamais affiché publiquement.</div>

                    <label for="commentaire">Votre commentaire</label>
                    <textarea id="commentaire" name="commentaire" rows="5" required minlength="10" maxlength="2000"><?= echapper($commentaireSaisi) ?></textarea>

                    <button type="submit" class="cd-form-btn">Envoyer ma contribution</button>
                    <div class="cd-aide" style="margin-top:10px;">Votre contribution sera publiée après modération.</div>
                </form>
            <?php endif; ?>
        </section>

    <?php endif; ?>
</main>

<?php require '../../includes/footer.php'; ?>