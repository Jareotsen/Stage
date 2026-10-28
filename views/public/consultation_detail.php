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
                c.statut, c.id_structure, s.nom_struc, s.sigle
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

    if ($action === 'verifier_email' && VERIFICATION_EMAIL_CITOYEN) {
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
                    "INSERT INTO citoyens (email, genre, tranche_age, secteur_activite, email_verifie_le)
                     VALUES (:email, :genre, :age, :secteur, CURRENT_TIMESTAMP)
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

        if ($valide && ($action !== 'deposer' || $erreurDepot === null) && !VERIFICATION_EMAIL_CITOYEN) {
            // Vérification désactivée : profil enregistré sans marquer l'email comme vérifié
            // (email_verifie_le reste NULL, ou garde la date d'une vérification antérieure)
            $citoyen = $db->prepare(
                "INSERT INTO citoyens (email, genre, tranche_age, secteur_activite)
                 VALUES (:email, :genre, :age, :secteur)
                 ON DUPLICATE KEY UPDATE
                    genre = VALUES(genre),
                    tranche_age = VALUES(tranche_age),
                    secteur_activite = VALUES(secteur_activite),
                    consentement_statistiques_le = CURRENT_TIMESTAMP"
            );
            $citoyen->execute([
                ':email' => $email,
                ':genre' => $genreSaisi,
                ':age' => $ageSaisi,
                ':secteur' => $secteurSaisi,
            ]);
            $executerParticipation($pending);
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
// Préremplissage du profil : email vérifié, ou email déjà utilisé dans cette session si la vérification est désactivée
$emailProfil = $_SESSION['citizen_verified_email']
    ?? (VERIFICATION_EMAIL_CITOYEN ? null : ($_SESSION['email_citoyen'] ?? null));
if (!empty($emailProfil)) {
    $profilStmt = $db->prepare(
        "SELECT genre, tranche_age, secteur_activite
         FROM citoyens WHERE email = :email"
    );
    $profilStmt->execute([':email' => $emailProfil]);
    $profilMemorise = $profilStmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
$genreAffiche = $genreSaisi !== '' ? $genreSaisi : ($profilMemorise['genre'] ?? '');
$ageAffiche = $ageSaisi !== '' ? $ageSaisi : ($profilMemorise['tranche_age'] ?? '');
$secteurAffiche = $secteurSaisi !== '' ? $secteurSaisi : ($profilMemorise['secteur_activite'] ?? '');
$consentementAffiche = $consentementSaisi || !empty($profilMemorise);

/* =========================================================
   4. PRÉPARATION DE L'AFFICHAGE
   ========================================================= */

// Texte saisi librement : on regroupe en paragraphes (les lignes vides multiples
// ne créent plus de grands blancs) et les intitulés courts numérotés deviennent des sous-titres.
$afficherTexte = static function (?string $texte): string {
    $blocs = preg_split('/\R\s*\R/u', trim((string) $texte)) ?: [];
    $html = '';
    foreach ($blocs as $bloc) {
        $bloc = trim($bloc);
        if ($bloc === '') {
            continue;
        }
        // Une première ligne courte et numérotée (« 1. Contexte », « Volet 2 : … ») devient un sous-titre
        $lignes = preg_split('/\R/u', $bloc, 2);
        $premiere = trim($lignes[0]);
        if (mb_strlen($premiere) <= 90 && preg_match('/^(\d+[.)]|[IVX]+[.)]|Volet\s+\d+)\s/iu', $premiere)) {
            $html .= '<h3>' . echapper($premiere) . '</h3>';
            $bloc = trim($lignes[1] ?? '');
            if ($bloc === '') {
                continue;
            }
        }
        $html .= '<p>' . nl2br(echapper($bloc)) . '</p>';
    }
    return $html;
};

$nbContributions = count($contributions);
$joursRestants = null;
if ($consultation && $ouverte && !empty($consultation['date_fermeture'])) {
    // Jours calendaires jusqu'à la clôture, comme sur la liste des consultations
    $joursRestants = max(0, (int) round((strtotime(date('Y-m-d', strtotime($consultation['date_fermeture']))) - strtotime(date('Y-m-d'))) / 86400));
}

require '../../includes/header.php';
?>

<style>
/* Détail d'une consultation : les composants communs sont dans publique/css/portail.css */
.cdp-hero { padding-bottom: clamp(2.6rem, 5vw, 3.6rem); }
.cdp-badges { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
.cdp-badge { display: inline-flex; align-items: center; gap: .4rem; max-width: 100%; padding: .3rem .75rem; border: 1px solid rgba(255,255,255,.35); border-radius: 2px; color: #fff; font-size: .78rem; font-weight: 600; }
.cdp-badge.ouverte { border-color: var(--acc-jaune); background: var(--acc-jaune); color: var(--acc-nuit); font-weight: 800; }
.cdp-badge.ouverte::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: var(--acc-vert-fonce); }
.cdp-badge.cloturee { background: rgba(255,255,255,.12); }
.cdp-hero h1 { max-width: 30ch; font-size: clamp(1.8rem, 3.6vw, 2.8rem); line-height: 1.15; overflow-wrap: anywhere; }

/* Repères clés : bande blanche sous l'en-tête */
.cdp-reperes { border-bottom: 1px solid var(--acc-ligne); background: #fff; }
.cdp-reperes dl { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 0; }
.cdp-reperes dl > div { display: flex; align-items: center; gap: .85rem; padding: 1.4rem 1.2rem 1.4rem 0; }
.cdp-reperes dl > div + div { padding-left: 1.2rem; border-left: 1px solid var(--acc-ligne); }
.cdp-repere-icone { display: flex; flex: 0 0 40px; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #E3F5EC; color: var(--acc-vert-fonce); font-size: 1.05rem; }
.cdp-reperes dt { color: var(--acc-gris); font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
.cdp-reperes dd { margin: .15rem 0 0; color: var(--acc-encre); font-size: .95rem; font-weight: 700; }
.cdp-reperes .alerte .cdp-repere-icone { background: #FDECEC; color: #B42318; }
.cdp-reperes .alerte dd { color: #B42318; }

/* Grille : présentation + contributions à gauche, participation à droite */
.cdp-grille { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: clamp(2rem, 4vw, 3.5rem); align-items: start; }
.cdp-colonne { display: flex; flex-direction: column; gap: clamp(2.5rem, 5vw, 3.5rem); min-width: 0; }
.cdp-titre { display: flex; align-items: center; gap: .75rem; margin: 0 0 1.3rem; padding-bottom: .7rem; border-bottom: 2px solid var(--acc-encre); color: var(--acc-encre); font-family: var(--font-title); font-size: 1.6rem; font-weight: 600; }
.cdp-titre .cdp-compteur { margin-left: auto; padding: .15rem .65rem; border-radius: 999px; background: var(--acc-fond); color: var(--acc-gris); font-family: var(--font-body); font-size: .8rem; font-weight: 800; }

/* Texte de la consultation */
.cdp-texte { color: var(--acc-encre); font-size: 1.02rem; line-height: 1.8; overflow-wrap: anywhere; }
.cdp-texte p { margin: 0 0 1rem; }
.cdp-texte h3 { margin: 1.8rem 0 .6rem; color: var(--acc-encre); font-family: var(--font-title); font-size: 1.2rem; font-weight: 600; }
.cdp-texte h3:first-child { margin-top: 0; }
.cdp-texte > :last-child { margin-bottom: 0; }

/* Contributions */
.cdp-vide { padding: 2.2rem 1rem; border: 1px dashed #CBD3DF; border-radius: 4px; color: var(--acc-gris); text-align: center; }
.cdp-vide i { display: block; margin-bottom: .5rem; color: var(--acc-bleu); font-size: 1.8rem; }
.cdp-vide a { color: var(--acc-bleu-fonce); font-weight: 700; }
.cdp-contrib { padding: 1.4rem 1.5rem; border: 1px solid var(--acc-ligne); border-radius: 4px; background: #fff; }
.cdp-contrib + .cdp-contrib { margin-top: 1rem; }
.cdp-contrib-tete { display: flex; align-items: center; gap: .75rem; margin-bottom: .8rem; }
.cdp-avatar { display: flex; flex: 0 0 38px; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%; background: #E8F0FA; color: var(--acc-bleu-fonce); }
.cdp-auteur { color: var(--acc-encre); font-size: .88rem; font-weight: 700; line-height: 1.2; }
.cdp-date { color: var(--acc-gris); font-size: .78rem; }
.cdp-contrib .cdp-texte { font-size: .95rem; }
.cdp-avis { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem 1.2rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--acc-ligne); }
.cdp-score { display: flex; flex: 1 1 220px; flex-direction: column; gap: .4rem; min-width: 0; }
.cdp-score-chiffres { display: flex; gap: 1rem; font-size: .8rem; font-weight: 700; }
.cdp-score-chiffres .pour { color: var(--acc-vert-fonce); }
.cdp-score-chiffres .contre { color: #B42318; }
.cdp-barre { display: flex; height: 6px; overflow: hidden; border-radius: 999px; background: var(--acc-fond); }
.cdp-barre .pour { background: var(--acc-vert); }
.cdp-barre .contre { background: #D92D20; }
.cdp-vote { flex: 0 0 auto; }
.cdp-vote[open] { flex-basis: 100%; }
.cdp-vote > summary { display: inline-flex; align-items: center; gap: .45rem; padding: .5rem 1rem; border: 2px solid var(--acc-encre); border-radius: 4px; background: #fff; color: var(--acc-encre); font-size: .82rem; font-weight: 700; list-style: none; cursor: pointer; transition: background .2s ease, color .2s ease; }
.cdp-vote > summary::-webkit-details-marker { display: none; }
.cdp-vote > summary:hover, .cdp-vote[open] > summary { background: var(--acc-encre); color: #fff; }
.cdp-vote-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-top: .9rem; padding: 1.1rem; border-radius: 4px; background: var(--acc-fond); }
.cdp-vote-form .cdp-plein { grid-column: 1 / -1; }
.cdp-vote-boutons { display: flex; gap: .6rem; }
.cdp-btn-vote { display: inline-flex; flex: 1; align-items: center; justify-content: center; gap: .45rem; min-height: 46px; border: 2px solid; border-radius: 4px; background: #fff; font: inherit; font-size: .88rem; font-weight: 700; transition: background .2s ease, color .2s ease; }
.cdp-btn-vote.pour { border-color: var(--acc-vert-fonce); color: var(--acc-vert-fonce); }
.cdp-btn-vote.pour:hover { background: var(--acc-vert-fonce); color: #fff; }
.cdp-btn-vote.contre { border-color: #B42318; color: #B42318; }
.cdp-btn-vote.contre:hover { background: #B42318; color: #fff; }

/* Formulaires */
.cdp-champ { display: flex; flex-direction: column; gap: .35rem; min-width: 0; }
.cdp-champ > span, .cdp-champ > label { color: var(--acc-encre); font-size: .8rem; font-weight: 700; }
.cdp-champ input, .cdp-champ select, .cdp-champ textarea { width: 100%; min-height: 46px; padding: .6rem .8rem; border: 1px solid #CBD3DF; border-radius: 4px; background: #fff; color: var(--acc-encre); font: inherit; font-size: .9rem; }
.cdp-champ textarea { min-height: 140px; resize: vertical; line-height: 1.6; }
.cdp-champ input:focus, .cdp-champ select:focus, .cdp-champ textarea:focus { outline: 0; border-color: var(--acc-bleu); box-shadow: 0 0 0 3px rgba(58,117,196,.15); }
.cdp-consentement { display: flex; align-items: flex-start; gap: .6rem; color: var(--acc-gris); font-size: .8rem; line-height: 1.5; }
.cdp-consentement input { flex: 0 0 auto; width: 17px; height: 17px; margin-top: 2px; accent-color: var(--acc-vert); }
.cdp-aide { margin: 0; color: var(--acc-gris); font-size: .76rem; line-height: 1.5; }

/* Carte « Participer » */
.cdp-participer { overflow: hidden; border: 1px solid var(--acc-ligne); border-radius: 4px; background: #fff; }
.cdp-participer-tete { padding: 1.4rem 1.5rem 1.2rem; border-top: 4px solid var(--acc-jaune); background: var(--acc-nuit); color: #fff; }
.cdp-participer-tete h2 { margin: 0 0 .35rem; color: #fff; font-family: var(--font-title); font-size: 1.4rem; font-weight: 600; }
.cdp-participer-tete p { margin: 0; color: rgba(255,255,255,.75); font-size: .86rem; line-height: 1.55; }
.cdp-participer-corps { padding: 1.4rem 1.5rem 1.5rem; }
.cdp-form { display: flex; flex-direction: column; gap: .9rem; }
.cdp-form-ligne { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
.cdp-envoyer { display: inline-flex; align-items: center; justify-content: center; gap: .55rem; min-height: 52px; padding: .7rem 1.3rem; border: 0; border-radius: 4px; background: var(--acc-jaune); color: var(--acc-nuit); font: inherit; font-weight: 800; transition: background .2s ease, color .2s ease; }
.cdp-envoyer:hover { background: var(--acc-nuit); color: #fff; }
.cdp-cloturee { display: flex; gap: .75rem; padding: 1rem; border-radius: 4px; background: var(--acc-fond); color: var(--acc-gris); font-size: .88rem; line-height: 1.6; }
.cdp-cloturee i { color: var(--acc-encre); font-size: 1.2rem; }
.cdp-verification { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--acc-ligne); }
.cdp-verification h3 { margin: 0 0 .4rem; color: var(--acc-encre); font-family: var(--font-title); font-size: 1.1rem; font-weight: 600; }
.cdp-verification > p { margin: 0 0 1rem; color: var(--acc-gris); font-size: .86rem; line-height: 1.55; }
.cdp-participer .alert, .cdp-colonne .alert { border-radius: 4px; font-size: .88rem; }

/* Bouton flottant */
.cdp-flottant {
  position: fixed; right: clamp(16px, 3vw, 32px); bottom: clamp(16px, 3vw, 32px); z-index: 1030;
  display: inline-flex; align-items: center; gap: .6rem; padding: .8rem 1.2rem .8rem 1rem;
  border-radius: 4px; background: var(--acc-nuit); color: #fff;
  font-size: .88rem; font-weight: 700; box-shadow: 0 12px 30px rgba(10,26,51,.3);
  transition: opacity .25s ease, transform .25s ease, background .2s ease;
}
.cdp-flottant:hover { background: var(--acc-bleu-fonce); color: #fff; }
.cdp-flottant:focus-visible { outline: 3px solid var(--acc-jaune); outline-offset: 3px; }
.cdp-flottant .cdp-flottant-nb { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 .4rem; border-radius: 999px; background: var(--acc-jaune); color: var(--acc-nuit); font-size: .75rem; font-weight: 800; }
.cdp-flottant.masque { opacity: 0; transform: translateY(16px); pointer-events: none; }
#contributions, #deposer { scroll-margin-top: 100px; }

@media (min-width: 992px) and (min-height: 820px) {
  .cdp-participer { position: sticky; top: 100px; }
}
@media (max-width: 991.98px) {
  .cdp-reperes dl { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .cdp-reperes dl > div:nth-child(3) { padding-left: 0; border-left: 0; }
  .cdp-reperes dl > div:nth-child(n+3) { border-top: 1px solid var(--acc-ligne); }
  .cdp-grille { grid-template-columns: 1fr; }
  /* Sur mobile : présentation, puis participation, puis contributions */
  .cdp-colonne { display: contents; }
  .cdp-presentation { order: 1; }
  .cdp-participer { order: 2; }
  .cdp-contributions { order: 3; }
}
@media (max-width: 575.98px) {
  .cdp-reperes dl { grid-template-columns: 1fr; }
  .cdp-reperes dl > div, .cdp-reperes dl > div + div { padding: 1rem 0; border-left: 0; }
  .cdp-reperes dl > div + div { border-top: 1px solid var(--acc-ligne); }
  .cdp-form-ligne, .cdp-vote-form { grid-template-columns: 1fr; }
  .cdp-contrib { padding: 1.1rem; }
  .cdp-flottant-texte { display: none; }
  .cdp-flottant { padding: .85rem; }
}
@media (prefers-reduced-motion: reduce) {
  .cdp-flottant, .cdp-envoyer { transition: none; }
}
</style>

<div class="cdp">

<?php if (!$consultation): ?>

    <header class="acc-bandeau cdp-hero">
        <img class="acc-bandeau-image" src="../../publique/image/GA3.jpeg" alt="">
        <div class="container">
            <ol class="acc-fil" aria-label="Fil d'Ariane">
                <li><a href="accueil.php">Accueil</a></li>
                <li><a href="consultations.php">Consultations</a></li>
            </ol>
            <span class="acc-surtitre">Consultation citoyenne</span>
            <h1>Consultation introuvable</h1>
        </div>
    </header>
    <main class="acc-section">
        <div class="container">
            <div class="acc-vide">
                <h2>Consultation indisponible</h2>
                <p>Cette consultation n'existe pas ou n'est pas disponible. <a href="consultations.php">Voir toutes les consultations</a></p>
            </div>
        </div>
    </main>

<?php else: ?>

    <!-- ================= EN-TÊTE ================= -->
    <header class="acc-bandeau cdp-hero">
        <img class="acc-bandeau-image" src="../../publique/image/GA3.jpeg" alt="">
        <div class="container">
            <ol class="acc-fil" aria-label="Fil d'Ariane">
                <li><a href="accueil.php">Accueil</a></li>
                <li><a href="consultations.php">Consultations</a></li>
                <li aria-current="page"><?= echapper(mb_strimwidth($consultation['titre'], 0, 50, '…')) ?></li>
            </ol>
            <span class="acc-surtitre">Consultation citoyenne</span>

            <div class="cdp-badges">
                <span class="cdp-badge <?= $ouverte ? 'ouverte' : 'cloturee' ?>"><?= $ouverte ? 'Ouverte' : 'Clôturée' ?></span>
                <span class="cdp-badge">
                    <i class="bi <?= $estGenerale ? 'bi-globe2' : 'bi-building' ?>" aria-hidden="true"></i>
                    <?= $estGenerale ? 'Consultation générale' : echapper($consultation['nom_struc']) ?>
                </span>
            </div>

            <h1><?= echapper($consultation['titre']) ?></h1>
        </div>
    </header>

    <!-- ================= REPÈRES CLÉS ================= -->
    <section class="cdp-reperes" aria-label="Calendrier et participation">
        <div class="container">
            <dl>
                <div>
                    <span class="cdp-repere-icone" aria-hidden="true"><i class="bi bi-calendar-event"></i></span>
                    <div><dt>Ouverture</dt><dd><?= formaterDateFr($consultation['date_ouverture']) ?></dd></div>
                </div>
                <div>
                    <span class="cdp-repere-icone" aria-hidden="true"><i class="bi bi-calendar-check"></i></span>
                    <div><dt><?= $ouverte ? 'Clôture' : 'Clôturée le' ?></dt><dd><?= !empty($consultation['date_fermeture']) ? formaterDateFr($consultation['date_fermeture']) : 'Non définie' ?></dd></div>
                </div>
                <div class="<?= $joursRestants !== null && $joursRestants <= 3 ? 'alerte' : '' ?>">
                    <span class="cdp-repere-icone" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
                    <div><dt>Temps restant</dt><dd>
                        <?php if (!$ouverte): ?>
                            Terminée
                        <?php elseif ($joursRestants === null): ?>
                            Sans échéance
                        <?php elseif ($joursRestants === 0): ?>
                            Dernier jour
                        <?php else: ?>
                            <?= $joursRestants ?> jour<?= $joursRestants > 1 ? 's' : '' ?>
                        <?php endif; ?>
                    </dd></div>
                </div>
                <div>
                    <span class="cdp-repere-icone" aria-hidden="true"><i class="bi bi-chat-square-text"></i></span>
                    <div><dt>Contributions</dt><dd><?= $nbContributions ?> publiée<?= $nbContributions > 1 ? 's' : '' ?></dd></div>
                </div>
            </dl>
        </div>
    </section>

    <main class="acc-section">
    <div class="container cdp-grille">

        <div class="cdp-colonne">

            <!-- ================= PRÉSENTATION ================= -->
            <article class="cdp-presentation">
                <h2 class="cdp-titre">Présentation</h2>
                <div class="cdp-texte"><?= $afficherTexte($consultation['contenu']) ?></div>
            </article>

            <!-- ================= CONTRIBUTIONS VALIDÉES ================= -->
            <section class="cdp-contributions" id="contributions" aria-labelledby="titre-contributions">
                <h2 class="cdp-titre" id="titre-contributions">
                    Contributions
                    <span class="cdp-compteur"><?= $nbContributions ?></span>
                </h2>

                <?php afficher_flash(); ?>

                <?php if (empty($contributions)): ?>
                    <div class="cdp-vide">
                        <i class="bi bi-chat-square-dots" aria-hidden="true"></i>
                        Aucune contribution publiée pour le moment.
                        <?php if ($ouverte): ?><br><a href="#deposer">Soyez le premier à participer.</a><?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php foreach ($contributions as $contribution): ?>
                    <?php
                        $nbPour = (int) $contribution['nb_pour'];
                        $nbContre = (int) $contribution['nb_contre'];
                        $nbVotes = $nbPour + $nbContre;
                        $partPour = $nbVotes > 0 ? round($nbPour * 100 / $nbVotes) : 0;
                        $idContribution = (int) $contribution['id_contribution'];
                    ?>
                    <article class="cdp-contrib">
                        <div class="cdp-contrib-tete">
                            <span class="cdp-avatar" aria-hidden="true"><i class="bi bi-person"></i></span>
                            <div>
                                <div class="cdp-auteur">Citoyen</div>
                                <div class="cdp-date"><?= formaterDateFr($contribution['date_contribution']) ?></div>
                            </div>
                        </div>

                        <div class="cdp-texte"><?= $afficherTexte($contribution['contenu']) ?></div>

                        <div class="cdp-avis">
                            <div class="cdp-score">
                                <div class="cdp-score-chiffres">
                                    <span class="pour"><i class="bi bi-hand-thumbs-up" aria-hidden="true"></i> <?= $nbPour ?> pour</span>
                                    <span class="contre"><i class="bi bi-hand-thumbs-down" aria-hidden="true"></i> <?= $nbContre ?> contre</span>
                                </div>
                                <?php if ($nbVotes > 0): ?>
                                    <div class="cdp-barre" role="img" aria-label="<?= $partPour ?> % pour, <?= 100 - $partPour ?> % contre">
                                        <span class="pour" style="width: <?= $partPour ?>%"></span>
                                        <span class="contre" style="width: <?= 100 - $partPour ?>%"></span>
                                    </div>
                                <?php else: ?>
                                    <div class="cdp-barre" aria-hidden="true"></div>
                                <?php endif; ?>
                            </div>

                            <?php if ($ouverte): ?>
                                <details class="cdp-vote">
                                    <summary><i class="bi bi-hand-index-thumb" aria-hidden="true"></i> Donner mon avis</summary>
                                    <form action="" method="post" class="cdp-vote-form">
                                        <input type="hidden" name="csrf_token" value="<?= echapper($token) ?>">
                                        <input type="hidden" name="action" value="voter">
                                        <input type="hidden" name="id_contribution" value="<?= $idContribution ?>">

                                        <label class="cdp-champ cdp-plein">
                                            <span>Votre email</span>
                                            <input type="email" name="email" autocomplete="email" required maxlength="190"
                                                   value="<?= echapper($emailSaisi !== '' ? $emailSaisi : $emailMemorise) ?>">
                                        </label>
                                        <label class="cdp-champ">
                                            <span>Genre</span>
                                            <select name="genre" required>
                                                <option value="">Sélectionner</option>
                                                <?php foreach ($genresCitoyen as $valeur => $libelle): ?>
                                                    <option value="<?= echapper($valeur) ?>" <?= $genreAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="cdp-champ">
                                            <span>Tranche d’âge</span>
                                            <select name="tranche_age" required>
                                                <option value="">Sélectionner</option>
                                                <?php foreach ($tranchesAge as $valeur => $libelle): ?>
                                                    <option value="<?= echapper($valeur) ?>" <?= $ageAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="cdp-champ cdp-plein">
                                            <span>Secteur d’activité</span>
                                            <select name="secteur_activite" required>
                                                <option value="">Sélectionner</option>
                                                <?php foreach ($secteursActivite as $valeur => $libelle): ?>
                                                    <option value="<?= echapper($valeur) ?>" <?= $secteurAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="cdp-consentement cdp-plein">
                                            <input type="checkbox" name="consentement_donnees" value="1" required <?= $consentementAffiche ? 'checked' : '' ?>>
                                            J’accepte que ces informations soient utilisées pour produire des statistiques agrégées.
                                        </label>
                                        <div class="cdp-vote-boutons cdp-plein">
                                            <button type="submit" name="valeur" value="pour" class="cdp-btn-vote pour"><i class="bi bi-hand-thumbs-up" aria-hidden="true"></i> Pour</button>
                                            <button type="submit" name="valeur" value="contre" class="cdp-btn-vote contre"><i class="bi bi-hand-thumbs-down" aria-hidden="true"></i> Contre</button>
                                        </div>
                                    </form>
                                </details>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

        </div>

        <!-- ================= PARTICIPER ================= -->
        <aside class="cdp-participer" id="deposer" aria-labelledby="titre-participer">
            <div class="cdp-participer-tete">
                <h2 id="titre-participer">Participer</h2>
                <p><?= $ouverte ? 'Partagez votre avis. Votre contribution sera publiée après modération.' : 'Les contributions sont fermées pour cette consultation.' ?></p>
            </div>
            <div class="cdp-participer-corps">

            <?php if ($erreurDepot): ?>
                <div class="alert alert-danger" role="alert"><?= echapper($erreurDepot) ?></div>
            <?php endif; ?>
            <?php if ($erreurVerification): ?>
                <div class="alert alert-info" role="status"><?= echapper($erreurVerification) ?></div>
            <?php endif; ?>

            <?php if (!$ouverte): ?>
                <div class="cdp-cloturee">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <span>Cette consultation est clôturée : il n'est plus possible de contribuer ni de voter.</span>
                </div>
            <?php else: ?>
                <form action="#deposer" method="post" class="cdp-form">
                    <input type="hidden" name="csrf_token" value="<?= echapper($token) ?>">
                    <input type="hidden" name="action" value="deposer">

                    <div class="cdp-champ">
                        <label for="email">Votre email</label>
                        <input type="email" id="email" name="email" required maxlength="190" autocomplete="email"
                               value="<?= echapper($emailSaisi !== '' ? $emailSaisi : $emailMemorise) ?>">
                        <p class="cdp-aide"><?= VERIFICATION_EMAIL_CITOYEN ? 'Un code vous sera envoyé pour vérifier cette adresse. ' : '' ?>Votre email ne sera jamais affiché publiquement.</p>
                    </div>

                    <div class="cdp-form-ligne">
                        <div class="cdp-champ">
                            <label for="genre">Genre</label>
                            <select id="genre" name="genre" required>
                                <option value="">Sélectionner</option>
                                <?php foreach ($genresCitoyen as $valeur => $libelle): ?>
                                    <option value="<?= echapper($valeur) ?>" <?= $genreAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cdp-champ">
                            <label for="tranche_age">Tranche d’âge</label>
                            <select id="tranche_age" name="tranche_age" required>
                                <option value="">Sélectionner</option>
                                <?php foreach ($tranchesAge as $valeur => $libelle): ?>
                                    <option value="<?= echapper($valeur) ?>" <?= $ageAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="cdp-champ">
                        <label for="secteur_activite">Secteur d’activité</label>
                        <select id="secteur_activite" name="secteur_activite" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($secteursActivite as $valeur => $libelle): ?>
                                <option value="<?= echapper($valeur) ?>" <?= $secteurAffiche === $valeur ? 'selected' : '' ?>><?= echapper($libelle) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="cdp-champ">
                        <label for="commentaire">Votre commentaire</label>
                        <textarea id="commentaire" name="commentaire" rows="5" required minlength="10" maxlength="2000"
                                  placeholder="Exprimez votre avis, vos propositions…"><?= echapper($commentaireSaisi) ?></textarea>
                        <p class="cdp-aide">Entre 10 et 2000 caractères.</p>
                    </div>

                    <label class="cdp-consentement" for="consentement_donnees">
                        <input type="checkbox" id="consentement_donnees" name="consentement_donnees"
                               value="1" required <?= $consentementAffiche ? 'checked' : '' ?>>
                        J’accepte que ces informations soient utilisées pour produire des statistiques agrégées.
                    </label>

                    <button type="submit" class="cdp-envoyer">
                        <i class="bi bi-send" aria-hidden="true"></i> Envoyer ma contribution
                    </button>
                </form>
            <?php endif; ?>

            <?php if (VERIFICATION_EMAIL_CITOYEN && !empty($_SESSION['pending_citizen_action'])): ?>
                <div id="verifier-email" class="cdp-verification">
                    <h3>Vérifier votre adresse email</h3>
                    <p>Un code à 6 chiffres a été envoyé à l’adresse indiquée. Saisissez-le pour confirmer votre participation.</p>
                    <form action="#verifier-email" method="post" class="cdp-form">
                        <input type="hidden" name="csrf_token" value="<?= echapper($token) ?>">
                        <input type="hidden" name="action" value="verifier_email">
                        <div class="cdp-champ">
                            <label for="code_verification">Code reçu par email</label>
                            <input type="text" id="code_verification" name="code_verification"
                                   inputmode="numeric" autocomplete="one-time-code"
                                   pattern="[0-9]{6}" maxlength="6" required>
                        </div>
                        <button type="submit" class="cdp-envoyer">Confirmer et continuer</button>
                    </form>
                </div>
            <?php endif; ?>
            </div>
        </aside>

    </div>
    </main>

    <!-- ================= BOUTON FLOTTANT ================= -->
    <a href="#contributions" class="cdp-flottant" id="cdp-flottant" aria-label="Voir les contributions (<?= $nbContributions ?>)">
        <i class="bi bi-chat-square-text" aria-hidden="true"></i>
        <span class="cdp-flottant-texte">Voir les contributions</span>
        <span class="cdp-flottant-nb" aria-hidden="true"><?= $nbContributions ?></span>
    </a>

    <script>
    (function () {
        var bouton = document.getElementById('cdp-flottant');
        var section = document.getElementById('contributions');
        if (!bouton || !section) { return; }

        var reduireMouvement = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        bouton.addEventListener('click', function (e) {
            e.preventDefault();
            section.scrollIntoView({ behavior: reduireMouvement ? 'auto' : 'smooth', block: 'start' });
            history.replaceState(null, '', '#contributions');
        });

        // Le bouton disparaît dès que la section des contributions est atteinte
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entrees) {
                var entree = entrees[0];
                var atteinte = entree.isIntersecting || entree.boundingClientRect.top < 0;
                bouton.classList.toggle('masque', atteinte);
                bouton.setAttribute('aria-hidden', atteinte ? 'true' : 'false');
                bouton.tabIndex = atteinte ? -1 : 0;
            }).observe(section);
        }
    })();
    </script>

<?php endif; ?>

</div>

<?php require '../../includes/footer.php'; ?>
