<?php
function fichier_est_valide($cheminTemporaire, $nomOrigininal) {
    $extension = strtolower(pathinfo($nomOrigininal, PATHINFO_EXTENSION));

    $types_autorises = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt'  => 'text/plain',
    ];

    if (!extension_loaded('fileinfo')) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $cheminTemporaire);

    if (!isset($types_autorises[$extension])) {
        return false;
    }

    if ($types_autorises[$extension] !== $realMimeType) {
        return false;
    }

    return true;
}