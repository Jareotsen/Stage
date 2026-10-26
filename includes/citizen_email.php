<?php

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

// Vérification des adresses email des citoyens par un code envoyé par email.
// Désactivée en attendant la validation du projet : contributions et votes sont
// enregistrés directement. Pour l'activer : configurer le SMTP (config/mail.local.php)
// puis passer cette constante à true.
const VERIFICATION_EMAIL_CITOYEN = false;

// Configuration SMTP : config/mail.local.php (non versionné, voir config/mail.local.example.php).
// Les variables d'environnement MRRI_SMTP_* sont prioritaires (utile en production).
function configuration_smtp(): array
{
    $fichier = dirname(__DIR__) . '/config/mail.local.php';
    $config = is_file($fichier) ? require $fichier : [];
    if (!is_array($config)) {
        $config = [];
    }

    $variables = [
        'host'       => 'MRRI_SMTP_HOST',
        'port'       => 'MRRI_SMTP_PORT',
        'username'   => 'MRRI_SMTP_USERNAME',
        'password'   => 'MRRI_SMTP_PASSWORD',
        'from'       => 'MRRI_SMTP_FROM',
        'from_name'  => 'MRRI_SMTP_FROM_NAME',
        'encryption' => 'MRRI_SMTP_ENCRYPTION',
    ];
    foreach ($variables as $cle => $variable) {
        $valeur = getenv($variable);
        if ($valeur !== false && $valeur !== '') {
            $config[$cle] = $valeur;
        }
    }

    return [
        'host'       => trim((string) ($config['host'] ?? '')),
        'port'       => (int) ($config['port'] ?? 587),
        'username'   => (string) ($config['username'] ?? ''),
        'password'   => (string) ($config['password'] ?? ''),
        'from'       => trim((string) ($config['from'] ?? '')),
        'from_name'  => (string) ($config['from_name'] ?? 'Consultations citoyennes MRRI'),
        // tls (STARTTLS, port 587), ssl (port 465) ou none (serveur de test local uniquement)
        'encryption' => strtolower((string) ($config['encryption'] ?? 'tls')),
    ];
}

function envoyer_code_citoyen(string $adresse, string $code): void
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Les dépendances de messagerie ne sont pas installées.');
    }
    require_once $autoload;

    $config = configuration_smtp();
    $avecAuthentification = $config['username'] !== '';

    if ($config['host'] === ''
        || $config['from'] === ''
        || !filter_var($config['from'], FILTER_VALIDATE_EMAIL)
        || !in_array($config['encryption'], ['tls', 'ssl', 'none'], true)
        || ($avecAuthentification && $config['password'] === '')
        || $config['port'] < 1 || $config['port'] > 65535) {
        error_log('Configuration SMTP incomplète : voir config/mail.local.example.php');
        throw new RuntimeException('Le service de vérification par email est momentanément indisponible.');
    }

    // Sans chiffrement, le mot de passe circulerait en clair : réservé à un serveur local
    if ($config['encryption'] === 'none'
        && !in_array($config['host'], ['localhost', '127.0.0.1', '::1'], true)) {
        error_log('Configuration SMTP refusée : encryption "none" sur un serveur distant');
        throw new RuntimeException('Le service de vérification par email est momentanément indisponible.');
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Port = $config['port'];
        $mail->Timeout = 15;
        $mail->SMTPAuth = $avecAuthentification;
        if ($avecAuthentification) {
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
        }
        if ($config['encryption'] === 'none') {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = $config['encryption'] === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($config['from'], $config['from_name']);
        $mail->addAddress($adresse);
        $mail->Subject = 'Code de vérification de votre adresse email';
        $mail->isHTML(false);
        $mail->Body = "Votre code de vérification est : {$code}\n\n"
            . "Il expire dans 10 minutes. Si vous n'avez pas demandé ce code, ignorez ce message.";
        $mail->send();
    } catch (MailException $e) {
        error_log('Envoi du code de vérification citoyen : ' . $mail->ErrorInfo);
        throw new RuntimeException('Impossible d’envoyer le code de vérification. Veuillez réessayer.');
    }
}
