<?php
// Envoie un code factice pour vérifier la configuration SMTP.
// Usage (ligne de commande uniquement) : php config/tester_smtp.php adresse@exemple.com

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/citizen_email.php';

$adresse = $argv[1] ?? '';
if (!filter_var($adresse, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage : php config/tester_smtp.php adresse@exemple.com\n");
    exit(1);
}

$config = configuration_smtp();
echo "Serveur : {$config['host']}:{$config['port']} ({$config['encryption']})\n";
echo "Expéditeur : {$config['from']}\n";

// Les erreurs détaillées de PHPMailer partent dans error_log : on les affiche ici
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

try {
    envoyer_code_citoyen($adresse, '123456');
    echo "OK : email envoyé à {$adresse}\n";
} catch (RuntimeException $e) {
    fwrite(STDERR, "ÉCHEC : " . $e->getMessage() . "\n");
    exit(1);
}
