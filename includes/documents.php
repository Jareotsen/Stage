<?php
// Fonctions partagées par views/telecharger.php et views/public/document.php :
// un seul endroit pour les règles d'accès aux documents.

// Types de fichiers autorisés (identiques à ceux acceptés à l'upload)
const DOCUMENT_TYPES = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt'  => 'text/plain',
];

// Au-delà, on ne prévisualise pas le contenu (protection mémoire / archive piégée)
const DOCUMENT_TAILLE_APERCU_MAX = 5 * 1024 * 1024;

function document_charger(PDO $db, $id_document) {
    $id = filter_var($id_document, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        return null;
    }

    $query = $db->prepare("SELECT id_document, doc_url, nom_affichage, est_public, id_structure
                           FROM documents
                           WHERE id_document = :id_doc");
    $query->execute(['id_doc' => $id]);

    return $query->fetch(PDO::FETCH_ASSOC) ?: null;
}

function document_accessible(PDO $db, array $document) {
    // Un document public est accessible à tous.
    if ((int) $document['est_public'] === 1) {
        return true;
    }

    // Un document confidentiel exige une session authentifiée.
    $id_utilisateur = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? null;

    if ($id_utilisateur === null) {
        return false;
    }

    // Règle 1 : les agents du ministère sont autorisés.
    if ($role === 'agent_ministere') {
        return true;
    }

    // Règle 2 : un point focal doit être responsable
    // de la structure propriétaire du document.
    if ($role === 'point_focal') {
        $requete = $db->prepare(
            "SELECT COUNT(*)
             FROM structure
             WHERE id_responsable = :id_utilisateur
               AND id_struc = :id_struc"
        );
        $requete->execute([
            'id_utilisateur' => $id_utilisateur,
            'id_struc'       => $document['id_structure']
        ]);

        return (int) $requete->fetchColumn() > 0;
    }

    return false;
}

// Renvoie le chemin réel du fichier, ou null s'il est invalide / absent.
// On ne fait pas confiance au nom stocké en base.
function document_chemin(array $document) {
    $nom_fichier = (string) $document['doc_url'];

    // Le nom stocké doit être un nom généré à l'upload : hexadécimal + extension autorisée
    if (!preg_match('/^[a-f0-9]+\.(pdf|docx?|txt)$/', $nom_fichier)) {
        error_log("Document #{$document['id_document']} : nom de fichier invalide");
        return null;
    }

    $dossier = realpath(__DIR__ . '/../uploads/documents');
    $chemin = realpath(__DIR__ . '/../uploads/documents/' . $nom_fichier);

    // Le chemin résolu doit rester dans le dossier des documents (anti path traversal)
    if ($dossier === false
        || $chemin === false
        || strpos($chemin, $dossier . DIRECTORY_SEPARATOR) !== 0
        || !is_file($chemin)
        || !is_readable($chemin)) {
        error_log("Document #{$document['id_document']} : fichier absent sur le disque");
        return null;
    }

    return $chemin;
}

function document_extension(array $document) {
    return strtolower(pathinfo((string) $document['doc_url'], PATHINFO_EXTENSION));
}

// Lit un fichier texte en UTF-8 pour l'afficher (null si trop gros)
function document_lire_texte($chemin) {
    if (filesize($chemin) > DOCUMENT_TAILLE_APERCU_MAX) {
        return null;
    }

    $contenu = (string) file_get_contents($chemin);
    if (!mb_check_encoding($contenu, 'UTF-8')) {
        $contenu = mb_convert_encoding($contenu, 'UTF-8', 'Windows-1252');
    }

    return $contenu;
}

// Extrait les paragraphes d'un .docx (texte seul, sans mise en forme).
// Renvoie une liste de ['titre' => bool, 'texte' => string], ou null si illisible.
function document_extraire_docx($chemin) {
    if (!class_exists('ZipArchive')) {
        return null;
    }

    $zip = new ZipArchive();
    if ($zip->open($chemin, ZipArchive::RDONLY) !== true) {
        return null;
    }

    // Vérifier la taille décompressée avant de lire (archive piégée)
    $infos = $zip->statName('word/document.xml');
    if ($infos === false || $infos['size'] > DOCUMENT_TAILLE_APERCU_MAX * 4) {
        $zip->close();
        return null;
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false) {
        return null;
    }

    $dom = new DOMDocument();
    // LIBXML_NONET : aucun accès réseau pendant l'analyse du XML
    if (!@$dom->loadXML($xml, LIBXML_NONET)) {
        return null;
    }

    $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $paragraphes = [];

    foreach ($dom->getElementsByTagNameNS($ns, 'p') as $p) {
        $texte = '';
        foreach ($p->getElementsByTagNameNS($ns, '*') as $noeud) {
            switch ($noeud->localName) {
                case 't':
                    $texte .= $noeud->textContent;
                    break;
                case 'tab':
                    $texte .= "\t";
                    break;
                case 'br':
                    $texte .= "\n";
                    break;
            }
        }

        // Les styles « Titre1 », « Heading1 », « Title »… sont affichés comme des titres
        $style = $p->getElementsByTagNameNS($ns, 'pStyle')->item(0);
        $valeur_style = $style ? $style->getAttributeNS($ns, 'val') : '';
        $est_titre = (bool) preg_match('/^(titre|heading|title)/i', $valeur_style);

        $paragraphes[] = ['titre' => $est_titre, 'texte' => $texte];
    }

    return $paragraphes;
}
