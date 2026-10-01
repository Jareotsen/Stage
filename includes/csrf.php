
<?php 
if (session_status () === PHP_SESSION_NONE){ 
session_start ();
}
function generer_token_csrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(25));
    }
    return $_SESSION['csrf_token'];
}
        //ceil() permet d'avoir une division du nombre de caractère à l'entier supérieur 
        //(substr() permet de couper l'entier supérieur)
    
       //random_bytes() génère des octets aléatoires cryptographiquement sécurisés
    
       //bin2hex() convertit les octets aléatoires en représentation hexadécimale
function verifier_token_csrf($token_recu) {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token_recu ?? '');
}