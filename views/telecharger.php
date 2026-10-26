<?php
session_start();
require_once '../config/database.php';
require_once '../includes/documents.php';

// Réponse unique pour tous les refus : on ne révèle pas si le document existe
function document_introuvable() {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Document introuvable.');
}

// étape 1 : quel est ce doc ?
$document = document_charger($db, $_GET['id_document'] ?? null);

if (!$document) {
    //là le doc n'est pas dans ma bdd
    document_introuvable();
}

// étape 2 & 3 : public ou confidentiel ? puis qui demande le doc?
if (!document_accessible($db, $document)) {
    document_introuvable();
}

// étape 4 : localiser le fichier sur le disque sans faire confiance à la bdd
$chemin_fichier = document_chemin($document);

if ($chemin_fichier === null) {
    document_introuvable();
}

$extension = document_extension($document);
$est_public = ((int) $document['est_public'] === 1);

// étape 5 : construire un nom de téléchargement propre à partir du nom d'affichage

$nom_affichage = trim((string) $document['nom_affichage']);
$nom_affichage = preg_replace('/[\x00-\x1F\x7F"\\\\\/:*?<>|]+/u', '', $nom_affichage);
if ($nom_affichage === '' || $nom_affichage === null) {
    $nom_affichage = 'document';
}
$nom_telechargement = $nom_affichage . '.' . $extension;

// Version ASCII de secours pour les anciens navigateurs
$nom_ascii = preg_replace('/[^A-Za-z0-9._ -]/', '_', $nom_telechargement);

// Aperçu (affichage dans la page document.php) : seulement pour les PDF.
// Sinon, téléchargement forcé.
$apercu = isset($_GET['apercu']) && $extension === 'pdf';

// étape 6 : envoyer le fichier

// Vider les tampons de sortie pour ne pas corrompre le fichier
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Libérer la session pour ne pas bloquer les autres requêtes pendant l'envoi
session_write_close();

header('Content-Type: ' . DOCUMENT_TYPES[$extension]);
header('Content-Length: ' . filesize($chemin_fichier));
header(sprintf(
    'Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s',
    $apercu ? 'inline' : 'attachment',
    $nom_ascii,
    rawurlencode($nom_telechargement)
));
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if ($apercu) {
    // Pas de « sandbox » ici : Chrome refuse d'afficher un PDF dans un contexte sandboxé.
    // Le PDF ne peut être intégré que par les pages de ce site.
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: frame-ancestors 'self'");
} else {
    header("Content-Security-Policy: default-src 'none'; sandbox");
}

// Un document confidentiel ne doit pas rester en cache (navigateur ou proxy)
if ($est_public) {
    // session_start() ajoute des en-têtes anti-cache qu'on retire pour un document public
    header_remove('Pragma');
    header_remove('Expires');
    header('Cache-Control: public, max-age=3600');
} else {
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

readfile($chemin_fichier);
exit;
