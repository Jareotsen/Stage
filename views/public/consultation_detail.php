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
    $joursRestants = max(0, (int) ceil((strtotime($consultation['date_fermeture']) - time()) / 86400));
}

require '../../includes/header.php';
?>

<style>
/* Page détail d'une consultation — même langage visuel que le catalogue des structures */
.cdp { padding-bottom: 5rem; }
.cdp-hero {
  position: relative; overflow: hidden; padding: clamp(2rem, 4vw, 3.4rem) 0 5rem;
  color: #fff; background: linear-gradient(118deg, #0B1D4D 0%, #173B91 68%, #176B58 100%);
}
.cdp-hero::before, .cdp-hero::after { content: ""; position: absolute; border: 1px solid rgba(255,255,255,.13); border-radius: 50%; pointer-events: none; }
.cdp-hero::before { width: 360px; height: 360px; right: -90px; top: -190px; }
.cdp-hero::after { width: 240px; height: 240px; right: 70px; bottom: -175px; }
.cdp-hero .container { position: relative; z-index: 1; }
.cdp-retour { display: flex; width: fit-content; align-items: center; gap: .45rem; margin-bottom: 1.6rem; color: rgba(255,255,255,.75); font-size: .85rem; font-weight: 600; }
.cdp-retour:hover { color: #fff; }
.cdp-retour i { transition: transform .18s ease; }
.cdp-retour:hover i { transform: translateX(-3px); }
.cdp-kicker { display: inline-flex; align-items: center; gap: .55rem; margin-bottom: .9rem; color: #F7EACB; font-size: .75rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
.cdp-badges { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
.cdp-badge { display: inline-flex; align-items: center; gap: .4rem; max-width: 100%; padding: .3rem .8rem; border: 1px solid rgba(255,255,255,.3); border-radius: 999px; color: #fff; font-size: .76rem; font-weight: 650; }
.cdp-badge.ouverte { border-color: var(--accent); background: var(--accent); color: var(--texte); font-weight: 750; }
.cdp-badge.ouverte::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: var(--accent-tres-fonce); }
.cdp-badge.cloturee { background: rgba(255,255,255,.12); }
.cdp-hero h1 { max-width: 900px; margin: 0; color: #fff; font-family: var(--font-title); font-size: clamp(1.7rem, 3.3vw, 2.6rem); line-height: 1.18; overflow-wrap: anywhere; }
.cdp-stats { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: 1.5rem; }
.cdp-stat { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem .9rem; border: 1px solid rgba(255,255,255,.22); border-radius: 999px; background: rgba(255,255,255,.08); color: rgba(255,255,255,.9); font-size: .82rem; }
.cdp-stat i { color: #F7EACB; }
.cdp-stat strong { color: #fff; }
.cdp-stat.alerte { border-color: rgba(247,234,203,.55); background: rgba(201,151,44,.22); }

/* Grille : présentation + contributions à gauche, participation à droite */
.cdp-grille { position: relative; z-index: 2; display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 1.75rem; align-items: start; margin-top: -3rem; }
.cdp-colonne { display: flex; flex-direction: column; gap: 1.75rem; min-width: 0; }
.cdp-carte { padding: clamp(1.4rem, 3vw, 2.2rem); border: 1px solid var(--mrri-border); border-radius: 16px; background: #fff; box-shadow: 0 14px 38px rgba(11,29,77,.08); }
.cdp-titre { display: flex; align-items: center; gap: .75rem; margin: 0 0 1.25rem; color: var(--primaire-nuit); font-size: 1.3rem; }
.cdp-titre::before { content: ""; width: 4px; height: 1.2em; border-radius: 2px; background: var(--accent); box-shadow: 0 0 0 1px var(--mrri-bleu-nuit); }
.cdp-titre .cdp-compteur { margin-left: auto; padding: .2rem .65rem; border-radius: 999px; background: var(--primaire-clair); color: var(--mrri-bleu); font-family: var(--font-body); font-size: .8rem; font-weight: 750; }

/* Texte de la consultation */
.cdp-texte { color: var(--texte-secondaire); font-size: .98rem; line-height: 1.8; overflow-wrap: anywhere; }
.cdp-texte p { margin: 0 0 1rem; }
.cdp-texte h3 { margin: 1.6rem 0 .6rem; color: var(--primaire-nuit); font-family: var(--font-body); font-size: 1.02rem; font-weight: 750; }
.cdp-texte h3:first-child { margin-top: 0; }
.cdp-texte > :last-child { margin-bottom: 0; }

/* Contributions */
.cdp-vide { padding: 2.2rem 1rem; border: 1px dashed #CDD5E1; border-radius: 12px; text-align: center; color: var(--texte-leger); }
.cdp-vide i { display: block; margin-bottom: .5rem; color: var(--mrri-bleu); font-size: 1.8rem; }
.cdp-contrib { padding: 1.35rem 0; border-top: 1px solid #EEF1F5; }
.cdp-contrib:first-of-type { padding-top: 0; border-top: 0; }
.cdp-contrib-tete { display: flex; align-items: center; gap: .75rem; margin-bottom: .8rem; }
.cdp-avatar { display: flex; flex: 0 0 38px; align-items: center; justify-content: center; width: 38px; height: 38px; border: 3px solid #D7E8FB; border-radius: 50%; background: #F9FBFF; color: var(--mrri-bleu); }
.cdp-auteur { color: var(--primaire-nuit); font-size: .88rem; font-weight: 700; line-height: 1.2; }
.cdp-date { color: var(--texte-leger); font-size: .78rem; }
.cdp-contrib .cdp-texte { font-size: .94rem; }
.cdp-avis { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem 1.2rem; margin-top: 1rem; }
.cdp-score { display: flex; flex: 1 1 220px; flex-direction: column; gap: .4rem; min-width: 0; }
.cdp-score-chiffres { display: flex; gap: 1rem; font-size: .8rem; font-weight: 650; }
.cdp-score-chiffres .pour { color: var(--accent-fonce); }
.cdp-score-chiffres .contre { color: var(--danger); }
.cdp-barre { display: flex; height: 6px; overflow: hidden; border-radius: 999px; background: #EEF1F5; }
.cdp-barre .pour { background: var(--accent-fonce); }
.cdp-barre .contre { background: var(--danger); }
.cdp-vote > summary { display: inline-flex; align-items: center; gap: .45rem; padding: .5rem 1rem; border: 1px solid #D7DDE7; border-radius: 999px; background: #fff; color: var(--mrri-bleu); font-size: .82rem; font-weight: 750; list-style: none; cursor: pointer; transition: border-color .18s, background .18s; }
.cdp-vote > summary::-webkit-details-marker { display: none; }
.cdp-vote > summary:hover { border-color: var(--mrri-bleu); background: var(--primaire-clair); }
.cdp-vote[open] > summary { border-color: var(--mrri-bleu); background: var(--mrri-bleu); color: #fff; }
.cdp-vote { flex: 0 0 auto; }
.cdp-vote[open] { flex-basis: 100%; }
.cdp-vote-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-top: .9rem; padding: 1.1rem; border-radius: 12px; background: var(--arriere-plan); }
.cdp-vote-form .cdp-plein { grid-column: 1 / -1; }
.cdp-vote-boutons { display: flex; gap: .6rem; }
.cdp-btn-vote { display: inline-flex; flex: 1; align-items: center; justify-content: center; gap: .45rem; min-height: 44px; border: 1px solid; border-radius: 10px; background: #fff; font: inherit; font-size: .88rem; font-weight: 750; transition: background .18s, color .18s; }
.cdp-btn-vote.pour { border-color: var(--accent-fonce); color: var(--accent-fonce); }
.cdp-btn-vote.pour:hover { background: var(--accent-fonce); color: #fff; }
.cdp-btn-vote.contre { border-color: var(--danger); color: var(--danger); }
.cdp-btn-vote.contre:hover { background: var(--danger); color: #fff; }

/* Formulaires */
.cdp-champ { display: flex; flex-direction: column; gap: .35rem; min-width: 0; }
.cdp-champ > span, .cdp-champ > label { color: #475467; font-size: .8rem; font-weight: 700; }
.cdp-champ input, .cdp-champ select, .cdp-champ textarea { width: 100%; min-height: 44px; padding: .6rem .8rem; border: 1px solid #D7DDE7; border-radius: 10px; background: #fff; color: var(--texte); font: inherit; font-size: .9rem; }
.cdp-champ textarea { min-height: 130px; resize: vertical; line-height: 1.6; }
.cdp-champ input:focus, .cdp-champ select:focus, .cdp-champ textarea:focus { outline: 0; border-color: var(--mrri-vert); box-shadow: 0 0 0 3px rgba(15,138,75,.12); }
.cdp-consentement { display: flex; align-items: flex-start; gap: .6rem; color: var(--texte-secondaire); font-size: .8rem; line-height: 1.5; }
.cdp-consentement input { flex: 0 0 auto; width: 17px; height: 17px; margin-top: 2px; accent-color: var(--mrri-vert); }
.cdp-aide { margin: 0; color: var(--texte-leger); font-size: .76rem; line-height: 1.5; }

/* Carte « Participer » */
.cdp-participer { position: relative; overflow: hidden; }
.cdp-participer::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--mrri-vert), var(--mrri-or)); }
.cdp-participer-intro { margin: -.5rem 0 1.2rem; color: var(--texte-leger); font-size: .86rem; line-height: 1.6; }
.cdp-form { display: flex; flex-direction: column; gap: .9rem; }
.cdp-form-ligne { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
.cdp-envoyer { display: inline-flex; align-items: center; justify-content: center; gap: .55rem; min-height: 50px; padding: .7rem 1.3rem; border: 1px solid var(--mrri-bleu); border-radius: 10px; background: var(--mrri-bleu); color: #fff; font: inherit; font-weight: 750; transition: background .18s, transform .18s; }
.cdp-envoyer:hover { background: var(--mrri-bleu-dark); transform: translateY(-1px); }
.cdp-cloturee { display: flex; gap: .75rem; padding: 1rem; border-radius: 12px; background: var(--arriere-plan); color: var(--texte-secondaire); font-size: .88rem; line-height: 1.6; }
.cdp-cloturee i { color: var(--mrri-or); font-size: 1.2rem; }
.cdp .alert { border-radius: 12px; font-size: .88rem; }

/* Bouton flottant */
.cdp-flottant {
  position: fixed; right: clamp(16px, 3vw, 32px); bottom: clamp(16px, 3vw, 32px); z-index: 1030;
  display: inline-flex; align-items: center; gap: .6rem; padding: .8rem 1.2rem .8rem 1rem;
  border: 1px solid rgba(255,255,255,.15); border-radius: 999px; background: var(--primaire-nuit); color: #fff;
  font-size: .88rem; font-weight: 750; box-shadow: 0 12px 30px rgba(1,13,61,.3);
  transition: opacity .25s ease, transform .25s ease, background .18s ease;
}
.cdp-flottant:hover { background: var(--mrri-bleu); color: #fff; }
.cdp-flottant:focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }
.cdp-flottant .cdp-flottant-nb { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 .4rem; border-radius: 999px; background: var(--accent); color: var(--texte); font-size: .75rem; }
.cdp-flottant.masque { opacity: 0; transform: translateY(16px); pointer-events: none; }
#contributions { scroll-margin-top: 24px; }

@media (min-width: 992px) and (min-height: 820px) {
  .cdp-participer { position: sticky; top: 24px; }
}
@media (max-width: 991.98px) {
  .cdp-grille { grid-template-columns: 1fr; }
  /* Sur mobile : présentation, puis participation, puis contributions */
  .cdp-colonne { display: contents; }
  .cdp-presentation { order: 1; }
  .cdp-participer { order: 2; }
  .cdp-contributions { order: 3; }
}
@media (max-width: 575.98px) {
  .cdp-form-ligne, .cdp-vote-form { grid-template-columns: 1fr; }
  .cdp-flottant-texte { display: none; }
  .cdp-flottant { padding: .85rem; }
}
@media (prefers-reduced-motion: reduce) {
  .cdp-flottant, .cdp-envoyer, .cdp-retour i { transition: none; }
}
</style>

<div class="cdp">

<?php if (!$consultation): ?>

    <section class="cdp-hero">
        <div class="container">
            <a class="cdp-retour" href="consultations.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Toutes les consultations</a>
            <div class="cdp-kicker">Consultation citoyenne</div>
            <h1>Consultation introuvable</h1>
        </div>
    </section>
    <main class="container cdp-grille" style="grid-template-columns:1fr;">
        <div class="cdp-carte cdp-vide">
            <i class="bi bi-search" aria-hidden="true"></i>
            Cette consultation n'existe pas ou n'est pas disponible.
        </div>
    </main>

<?php else: ?>

    <!-- ================= EN-TÊTE ================= -->
    <section class="cdp-hero">
        <div class="container">
            <a class="cdp-retour" href="consultations.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Toutes les consultations</a>
            <div class="cdp-kicker">Consultation citoyenne</div>

            <div class="cdp-badges">
                <span class="cdp-badge">
                    <i class="bi <?= $estGenerale ? 'bi-globe2' : 'bi-building' ?>" aria-hidden="true"></i>
                    <?= $estGenerale ? 'Consultation générale' : echapper($consultation['nom_struc']) ?>
                </span>
                <span class="cdp-badge <?= $ouverte ? 'ouverte' : 'cloturee' ?>"><?= $ouverte ? 'Ouverte' : 'Clôturée' ?></span>
            </div>

            <h1><?= echapper($consultation['titre']) ?></h1>

            <div class="cdp-stats">
                <span class="cdp-stat">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                    Ouverte le <strong><?= formaterDateFr($consultation['date_ouverture']) ?></strong>
                </span>
                <?php if (!empty($consultation['date_fermeture'])): ?>
                    <span class="cdp-stat">
                        <i class="bi bi-calendar-check" aria-hidden="true"></i>
                        <?= $ouverte ? 'Clôture le' : 'Clôturée le' ?> <strong><?= formaterDateFr($consultation['date_fermeture']) ?></strong>
                    </span>
                <?php endif; ?>
                <?php if ($joursRestants !== null): ?>
                    <span class="cdp-stat <?= $joursRestants <= 3 ? 'alerte' : '' ?>">
                        <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                        <?php if ($joursRestants === 0): ?>
                            <strong>Dernier jour</strong> pour participer
                        <?php else: ?>
                            <strong><?= $joursRestants ?> jour<?= $joursRestants > 1 ? 's' : '' ?></strong> pour participer
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                <span class="cdp-stat">
                    <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                    <strong><?= $nbContributions ?></strong> contribution<?= $nbContributions > 1 ? 's' : '' ?> publiée<?= $nbContributions > 1 ? 's' : '' ?>
                </span>
            </div>
        </div>
    </section>

    <main class="container cdp-grille">

        <div class="cdp-colonne">

            <!-- ================= PRÉSENTATION ================= -->
            <article class="cdp-carte cdp-presentation">
                <h2 class="cdp-titre">Présentation</h2>
                <div class="cdp-texte"><?= $afficherTexte($consultation['contenu']) ?></div>
            </article>

            <!-- ================= CONTRIBUTIONS VALIDÉES ================= -->
            <section class="cdp-carte cdp-contributions" id="contributions" aria-labelledby="titre-contributions">
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
        <aside class="cdp-carte cdp-participer" id="deposer" aria-labelledby="titre-participer">
            <h2 class="cdp-titre" id="titre-participer">Participer</h2>

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
                <p class="cdp-participer-intro">Partagez votre avis. Votre contribution sera publiée après modération.</p>

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
                <div id="verifier-email" style="margin-top:1.5rem;">
                    <h3 class="cdp-titre" style="font-size:1.05rem;">Vérifier votre adresse email</h3>
                    <p class="cdp-participer-intro" style="margin-top:0;">Un code à 6 chiffres a été envoyé à l’adresse indiquée. Saisissez-le pour confirmer votre participation.</p>
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
        </aside>

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
