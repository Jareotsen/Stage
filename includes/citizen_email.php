<?php

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

// Configure MRRI_SMTP_HOST, MRRI_SMTP_PORT, MRRI_SMTP_USERNAME,
// MRRI_SMTP_PASSWORD, MRRI_SMTP_FROM, MRRI_SMTP_FROM_NAME, and
// MRRI_SMTP_ENCRYPTION (tls or ssl) in the server environment.
function envoyer_code_citoyen(string $adresse, string $code): void
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Les dépendances de messagerie ne sont pas installées.');
    }
    require_once $autoload;

    $host = getenv('MRRI_SMTP_HOST') ?: '';
    $port = (int) (getenv('MRRI_SMTP_PORT') ?: 587);
    $username = getenv('MRRI_SMTP_USERNAME') ?: '';
    $password = getenv('MRRI_SMTP_PASSWORD') ?: '';
    $from = getenv('MRRI_SMTP_FROM') ?: '';
    $fromName = getenv('MRRI_SMTP_FROM_NAME') ?: 'Consultations citoyennes MRRI';
    $encryption = strtolower(getenv('MRRI_SMTP_ENCRYPTION') ?: 'tls');

    if ($host === '' || $username === '' || $password === '' || $from === ''
        || !filter_var($from, FILTER_VALIDATE_EMAIL)
        || !in_array($encryption, ['tls', 'ssl'], true)
        || $port < 1 || $port > 65535) {
        throw new RuntimeException('La configuration SMTP du service de vérification est incomplète.');
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->Port = $port;
        $mail->Timeout = 15;
        $mail->SMTPSecure = $encryption === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($from, $fromName);
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
