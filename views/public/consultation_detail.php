<?php
declare(strict_types=1);

session_start();
require '../../config/database.php';
require_once '../../includes/csrf.php';
require_once '../../includes/flash.php';
require_once '../../includes/dates.php';
require_once '../../includes/consultation_helpers.php';
require_once '../../includes/citizen_email.php';

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
$erreurVerification = null;
$emailSaisi = '';
$commentaireSaisi = '';
$genreSaisi = '';
$ageSaisi = '';
$secteurSaisi = '';
$consentementSaisi = false;
$codeVerificationSaisi = '';
$urlPage = 'consultation_detail.php?id=' . (int) $id;
$genresCitoyen = ['femme' => 'Femme', 'homme' => 'Homme'];
$tranchesAge = [
    'moins_18' => 'Moins de 18 ans',
    '18_24' => '18 à 24 ans',
    '25_34' => '25 à 34 ans',
    '35_44' => '35 à 44 ans',
    '45_54' => '45 à 54 ans',
    '55_plus' => '55 ans et plus',
];
$secteursActivite = [
    'secteur_public' => 'Secteur public',
    'secteur_prive' => 'Secteur privé',
    'independant' => 'Travailleur indépendant',
    'etudiant' => 'Étudiant(e)',
    'sans_emploi' => 'Sans emploi',
    'autre' => 'Autre',
];

$executerParticipation = static function (array $actionCitoyen) use ($db, $urlPage): void {
    if ($actionCitoyen['action'] === 'deposer') {
        $insert = $db->prepare(
            "INSERT INTO contributions (id_consultation, email_contributeur, contenu)
             VALUES (:id_consultation, :email, :commentaire)"
        );
        $insert->execute([
            ':id_consultation' => $actionCitoyen['id_consultation'],
            ':email' => $actionCitoyen['email'],
            ':commentaire' => $actionCitoyen['commentaire'],
        ]);
        $_SESSION['email_citoyen'] = $actionCitoyen['email'];
        definir_flash("Merci ! Votre contribution a bien été reçue. Elle sera publiée après modération.");
        header("Location: $urlPage#contributions");
        exit();
    }

    $verif = $db->prepare(
        "SELECT id_contribution FROM contributions
         WHERE id_contribution = :id_contribution
           AND id_consultation = :id_consultation
           AND statut = 'validee'"
    );
    $verif->execute([
        ':id_contribution' => $actionCitoyen['id_contribution'],
        ':id_consultation' => $actionCitoyen['id_consultation'],
    ]);
    if (!$verif->fetch()) {
        definir_flash("Contribution introuvable.", 'erreur');
        header("Location: $urlPage#contributions");
        exit();
    }

    try {
        $vote = $db->prepare(
            "INSERT INTO votes (id_contribution, email_votant, valeur_vote)
             VALUES (:id_contribution, :email, :valeur)"
        );
        $vote->execute([
            ':id_contribution' => $actionCitoyen['id_contribution'],
            ':email' => $actionCitoyen['email'],
            ':valeur' => $actionCitoyen['valeur'],
        ]);
        $_SESSION['email_citoyen'] = $actionCitoyen['email'];
        definir_flash("Votre vote a bien été enregistré.");
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) === 1062) {
            definir_flash("Cette adresse email a déjà voté sur cette contribution.", 'erreur');
        } else {
            error_log("Erreur vote : " . $e->getMessage());
            definir_flash("Impossible d'enregistrer votre vote. Veuillez réessayer.", 'erreur');
        }
    }
    header("Location: $urlPage#contributions");
    exit();
};

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

    if ($action === 'verifier_email') {
        $pending = $_SESSION['pending_citizen_action'] ?? null;
        $codeVerificationSaisi = trim((string) ($_POST['code_verification'] ?? ''));
        $emailSaisi = is_array($pending) ? $pending['email'] : '';
        if (!is_array($pending)
            || !isset(
                $pending['action'],
                $pending['email'],
                $pending['id_consultation'],
                $pending['genre'],
                $pending['age'],
                $pending['secteur']
            )
            || !in_array($pending['action'], ['deposer', 'voter'], true)
            || !filter_var($pending['email'], FILTER_VALIDATE_EMAIL)
            || mb_strlen($pending['email']) > 190
            || !isset(
                $genresCitoyen[$pending['genre']],
                $tranchesAge[$pending['age']],
                $secteursActivite[$pending['secteur']]
            )
            || ($pending['action'] === 'deposer'
                && (!isset($pending['commentaire'])
                    || mb_strlen($pending['commentaire']) < 10
                    || mb_strlen($pending['commentaire']) > 2000))
            || ($pending['action'] === 'voter'
                && (!isset($pending['id_contribution'], $pending['valeur'])
                    || (int) $pending['id_contribution'] <= 0
                    || !in_array($pending['valeur'], ['pour', 'contre'], true)))
            || (int) $pending['id_consultation'] <= 0) {
            unset($_SESSION['pending_citizen_action']);
            $erreurVerification = "Code invalide. Vérifiez le code reçu par email.";
        } elseif (!preg_match('/^\d{6}$/', $codeVerificationSaisi)) {
            $erreurVerification = "Le code doit contenir 6 chiffres.";
        } else {
            $consultationPendingStmt = $db->prepare(
                "SELECT statut, date_fermeture
                 FROM consultations
                 WHERE id_consultation = :id AND statut != 'brouillon'"
            );
            $consultationPendingStmt->execute([':id' => $pending['id_consultation']]);
            $consultationPending = $consultationPendingStmt->fetch(PDO::FETCH_ASSOC);
            if (!$consultationPending || !consultationEstOuverte($consultationPending)) {
                $supprimerCode = $db->prepare("DELETE FROM verifications_citoyens WHERE email = :email");
                $supprimerCode->execute([':email' => $pending['email']]);
                unset($_SESSION['pending_citizen_action']);
                $erreurVerification = "La consultation liée à cette participation est clôturée ou indisponible.";
            } else {
            $verification = $db->prepare(
                "SELECT code_hash, expire_le, tentatives
                 FROM verifications_citoyens
                 WHERE email = :email"
            );
            $verification->execute([':email' => $pending['email']]);
            $code = $verification->fetch(PDO::FETCH_ASSOC);

            if (!$code || (int) $code['tentatives'] >= 5 || strtotime($code['expire_le']) < time()) {
                $supprimerCode = $db->prepare("DELETE FROM verifications_citoyens WHERE email = :email");
                $supprimerCode->execute([':email' => $pending['email']]);
                unset($_SESSION['pending_citizen_action']);
                $erreurVerification = "Ce code a expiré ou le nombre d'essais autorisé est dépassé. Renvoyez votre participation.";
            } elseif (!password_verify($codeVerificationSaisi, $code['code_hash'])) {
                $majTentatives = $db->prepare(
                    "UPDATE verifications_citoyens SET tentatives = tentatives + 1 WHERE email = :email"
                );
                $majTentatives->execute([':email' => $pending['email']]);
                $erreurVerification = "Code incorrect. Il vous reste "
                    . max(0, 4 - (int) $code['tentatives']) . " tentative(s).";
            } else {
                $db->beginTransaction();
                $citoyen = $db->prepare(
                    "INSERT INTO citoyens (email, genre, tranche_age, secteur_activite)
                     VALUES (:email, :genre, :age, :secteur)
                     ON DUPLICATE KEY UPDATE
                        genre = VALUES(genre),
                        tranche_age = VALUES(tranche_age),
                        secteur_activite = VALUES(secteur_activite),
                        consentement_statistiques_le = CURRENT_TIMESTAMP,
                        email_verifie_le = CURRENT_TIMESTAMP"
                );
                $citoyen->execute([
                    ':email' => $pending['email'],
                    ':genre' => $pending['genre'],
                    ':age' => $pending['age'],
                    ':secteur' => $pending['secteur'],
                ]);
                $supprimerCode = $db->prepare("DELETE FROM verifications_citoyens WHERE email = :email");
                $supprimerCode->execute([':email' => $pending['email']]);
                $db->commit();

                $_SESSION['citizen_verified_email'] = $pending['email'];
                unset($_SESSION['pending_citizen_action']);
                $executerParticipation($pending);
            }
            }
        }
    } elseif (in_array($action, ['deposer', 'voter'], true)) {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $genreSaisi = (string) ($_POST['genre'] ?? '');
        $ageSaisi = (string) ($_POST['tranche_age'] ?? '');
        $secteurSaisi = (string) ($_POST['secteur_activite'] ?? '');
        $consentementSaisi = ($_POST['consentement_donnees'] ?? '') === '1';
        $emailSaisi = $email;
        $emailValide = $email !== ''
            && mb_strlen($email) <= 190
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        $pending = [
            'action' => $action,
            'email' => $email,
            'genre' => $genreSaisi,
            'age' => $ageSaisi,
            'secteur' => $secteurSaisi,
            'id_consultation' => (int) $consultation['id_consultation'],
        ];
        $valide = $emailValide
            && isset($genresCitoyen[$genreSaisi], $tranchesAge[$ageSaisi], $secteursActivite[$secteurSaisi])
            && $consentementSaisi;

        if ($action === 'deposer') {
            $commentaireSaisi = trim((string) ($_POST['commentaire'] ?? ''));
            $pending['commentaire'] = $commentaireSaisi;
            $longueur = mb_strlen($commentaireSaisi);
            if (!$emailValide) {
                $erreurDepot = "Veuillez saisir une adresse email valide.";
            } elseif (!$valide) {
                $erreurDepot = "Veuillez renseigner vos caractéristiques et accepter leur utilisation statistique.";
            } elseif ($longueur < 10 || $longueur > 2000) {
                $erreurDepot = "Le commentaire doit contenir entre 10 et 2000 caractères.";
            }
        } else {
            $pending['id_contribution'] = (int) ($_POST['id_contribution'] ?? 0);
            $pending['valeur'] = (string) ($_POST['valeur'] ?? '');
            if (!$emailValide || !$valide || $pending['id_contribution'] <= 0
                || !in_array($pending['valeur'], ['pour', 'contre'], true)) {
                definir_flash("Veuillez renseigner vos caractéristiques, accepter leur utilisation statistique et vérifier les informations du vote.", 'erreur');
                header("Location: $urlPage#contributions");
                exit();
            }
        }

        if ($valide && ($action !== 'deposer' || $erreurDepot === null)) {
            if (($_SESSION['citizen_verified_email'] ?? null) === $email) {
                $profil = $db->prepare(
                    "SELECT genre, tranche_age, secteur_activite FROM citoyens WHERE email = :email"
                );
                $profil->execute([':email' => $email]);
                $profil = $profil->fetch(PDO::FETCH_ASSOC);
                if ($profil) {
                    $pending['genre'] = $profil['genre'];
                    $pending['age'] = $profil['tranche_age'];
                    $pending['secteur'] = $profil['secteur_activite'];
                    $executerParticipation($pending);
                }
                unset($_SESSION['citizen_verified_email']);
            }

            $delaiCode = $db->prepare(
                "SELECT TIMESTAMPDIFF(SECOND, envoye_le, CURRENT_TIMESTAMP)
                 FROM verifications_citoyens WHERE email = :email"
            );
            $delaiCode->execute([':email' => $email]);
            $secondesDepuisEnvoi = $delaiCode->fetchColumn();

            if ($secondesDepuisEnvoi !== false && (int) $secondesDepuisEnvoi < 60) {
                $_SESSION['pending_citizen_action'] = $pending;
                $erreurDepot = "Un code a déjà été envoyé. Vérifiez votre boîte email avant d’en demander un autre.";
            } else {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $db->beginTransaction();
                $enregistrerCode = $db->prepare(
                    "INSERT INTO verifications_citoyens
                        (email, genre, tranche_age, secteur_activite, code_hash, expire_le, envoye_le, tentatives)
                     VALUES
                        (:email, :genre, :age, :secteur, :code_hash,
                         DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 10 MINUTE), CURRENT_TIMESTAMP, 0)
                     ON DUPLICATE KEY UPDATE
                        genre = VALUES(genre),
                        tranche_age = VALUES(tranche_age),
                        secteur_activite = VALUES(secteur_activite),
                        code_hash = VALUES(code_hash),
                        expire_le = VALUES(expire_le),
                        envoye_le = VALUES(envoye_le),
                        tentatives = 0"
                );
                $enregistrerCode->execute([
                    ':email' => $email,
                    ':genre' => $genreSaisi,
                    ':age' => $ageSaisi,
                    ':secteur' => $secteurSaisi,
                    ':code_hash' => password_hash($code, PASSWORD_DEFAULT),
                ]);
                try {
                    envoyer_code_citoyen($email, $code);
                    $db->commit();
                    $_SESSION['pending_citizen_action'] = $pending;
                    $erreurVerification = "Un code de vérification a été envoyé à votre adresse email. Il est valable 10 minutes.";
                } catch (RuntimeException $e) {
                    $db->rollBack();
                    error_log('Vérification de l’adresse email citoyenne : ' . $e->getMessage());
                    $erreurDepot = $e->getMessage();
                }
            }
        }
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
$profilMemorise = [];
if (!empty($_SESSION['citizen_verified_email'])) {
    $profilStmt = $db->prepare(
        "SELECT genre, tranche_age, secteur_activite
         FROM citoyens WHERE email = :email"
    );
    $profilStmt->execute([':email' => $_SESSION['citizen_verified_email']]);
    $profilMemorise = $profilStmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
$genreAffiche = $genreSaisi !== '' ? $genreSaisi : ($profilMemorise['genre'] ?? '');
$ageAffiche = $ageSaisi !== '' ? $ageSaisi : ($profilMemorise['tranche_age'] ?? '');
$secteurAffiche = $secteurSaisi !== '' ? $secteurSaisi : ($profilMemorise['secteur_activite'] ?? '');
$consentementAffiche = $consentementSaisi || !empty($profilMemorise);

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

        <?php if ($erreurDepot): ?>
            <div class="alert alert-danger" role="alert"><?= echapper($erreurDepot) ?></div>
        <?php endif; ?>
        <?php if ($erreurVerification): ?>
            <div class="alert alert-info" role="status"><?= echapper($erreurVerification) ?></div>
        <?php endif; ?>

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
                                <label class="cd-vote-champ">
                                    <span>Votre email</span>
                                    <input type="email" name="email" autocomplete="email" required maxlength="190"
                                           value="<?= echapper($emailSaisi !== '' ? $emailSaisi : $emailMemorise) ?>">
                                </label>
                                <label class="cd-vote-champ">
                                    <span>Genre</span>
                                    <select name="genre" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($genresCitoyen as $valeur => $libelle): ?>
                                            <option value="<?= echapper($valeur) ?>" <?= $genreAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="cd-vote-champ">
                                    <span>Tranche d’âge</span>
                                    <select name="tranche_age" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($tranchesAge as $valeur => $libelle): ?>
                                            <option value="<?= echapper($valeur) ?>" <?= $ageAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="cd-vote-champ">
                                    <span>Secteur d’activité</span>
                                    <select name="secteur_activite" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($secteursActivite as $valeur => $libelle): ?>
                                            <option value="<?= echapper($valeur) ?>" <?= $secteurAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="cd-vote-consentement">
                                    <input type="checkbox" name="consentement_donnees" value="1" required <?= $consentementAffiche ? 'checked' : '' ?>>
                                    Utilisation statistique agrégée
                                </label>
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
                <form action="#deposer" method="post" class="cd-form">
                    <input type="hidden" name="csrf_token" value="<?= echapper($token) ?>">
                    <input type="hidden" name="action" value="deposer">

                    <label for="email">Votre email</label>
                    <input type="email" id="email" name="email" required maxlength="190" autocomplete="email"
                           value="<?= echapper($emailSaisi !== '' ? $emailSaisi : $emailMemorise) ?>">
                    <div class="cd-aide">Un code vous sera envoyé pour vérifier cette adresse. Votre email ne sera jamais affiché publiquement.</div>

                    <label for="genre">Genre</label>
                    <select id="genre" name="genre" required>
                        <option value="">Sélectionner</option>
                        <?php foreach ($genresCitoyen as $valeur => $libelle): ?>
                            <option value="<?= echapper($valeur) ?>" <?= $genreAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="tranche_age">Tranche d’âge</label>
                    <select id="tranche_age" name="tranche_age" required>
                        <option value="">Sélectionner</option>
                        <?php foreach ($tranchesAge as $valeur => $libelle): ?>
                            <option value="<?= echapper($valeur) ?>" <?= $ageAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="secteur_activite">Secteur d’activité</label>
                    <select id="secteur_activite" name="secteur_activite" required>
                        <option value="">Sélectionner</option>
                        <?php foreach ($secteursActivite as $valeur => $libelle): ?>
                            <option value="<?= echapper($valeur) ?>" <?= $secteurAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="cd-consentement" for="consentement_donnees">
                        <input type="checkbox" id="consentement_donnees" name="consentement_donnees"
                               value="1" required <?= $consentementAffiche ? 'checked' : '' ?>>
                        J’accepte que ces informations soient utilisées pour produire des statistiques agrégées.
                    </label>

                    <label for="commentaire">Votre commentaire</label>
                    <textarea id="commentaire" name="commentaire" rows="5" required minlength="10" maxlength="2000"><?= echapper($commentaireSaisi) ?></textarea>

                    <button type="submit" class="cd-form-btn">Envoyer ma contribution</button>
                    <div class="cd-aide" style="margin-top:10px;">Votre contribution sera publiée après modération.</div>
                </form>
            <?php endif; ?>
        </section>

        <?php if (!empty($_SESSION['pending_citizen_action'])): ?>
            <section class="cd-carte" id="verifier-email">
                <h2 class="cd-section-titre">Vérifier votre adresse email</h2>
                <p>Un code à 6 chiffres a été envoyé à l’adresse indiquée. Saisissez-le pour confirmer votre participation.</p>
                <form action="#verifier-email" method="post" class="cd-form">
                    <input type="hidden" name="csrf_token" value="<?= echapper($token) ?>">
                    <input type="hidden" name="action" value="verifier_email">
                    <label for="code_verification">Code reçu par email</label>
                    <input type="text" id="code_verification" name="code_verification"
                           inputmode="numeric" autocomplete="one-time-code"
                           pattern="[0-9]{6}" maxlength="6" required>
                    <button type="submit" class="cd-form-btn">Confirmer et continuer</button>
                </form>
            </section>
        <?php endif; ?>

    <?php endif; ?>
</main>

<?php require '../../includes/footer.php'; ?>