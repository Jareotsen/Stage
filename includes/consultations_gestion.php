<?php
// Logique de gestion des consultations, partagée par l'agent du ministère
// (consultations générales) et le point focal (consultations de sa structure).

if (!function_exists('creer_consultation')) {
    /**
     * Crée une consultation ouverte.
     * $idStructure = null  -> consultation générale (créée par le ministère)
     * Retourne un message d'erreur, ou null si tout s'est bien passé.
     */
    function creer_consultation(PDO $db, string $titre, string $contenu, string $dateFermeture, ?int $idStructure, int $idCreateur): ?string
    {
        $titre = trim($titre);
        $contenu = trim($contenu);

        if (mb_strlen($titre) < 3 || mb_strlen($titre) > 255) {
            return "Le titre doit contenir entre 3 et 255 caractères.";
        }
        if (mb_strlen($contenu) < 10) {
            return "Le contenu doit contenir au moins 10 caractères.";
        }

        // Date de fermeture facultative : format AAAA-MM-JJ, pas dans le passé
        $fermeture = null;
        if ($dateFermeture !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $dateFermeture);
            if (!$d || $d->format('Y-m-d') !== $dateFermeture) {
                return "La date de fermeture est invalide.";
            }
            if ($dateFermeture < date('Y-m-d')) {
                return "La date de fermeture ne peut pas être dans le passé.";
            }
            $fermeture = $dateFermeture . ' 23:59:59'; // ouverte jusqu'à la fin de ce jour
        }

        $insert = $db->prepare(
            "INSERT INTO consultations (id_structure, id_createur, titre, contenu, date_fermeture, statut)
             VALUES (:id_structure, :id_createur, :titre, :contenu, :date_fermeture, 'ouverte')"
        );
        $insert->execute([
            ':id_structure' => $idStructure,
            ':id_createur' => $idCreateur,
            ':titre' => $titre,
            ':contenu' => $contenu,
            ':date_fermeture' => $fermeture,
        ]);

        return null;
    }
}

if (!function_exists('fermer_consultation')) {
    /**
     * Clôture une consultation encore ouverte.
     * $idStructure = null  -> pas de restriction (agent du ministère)
     * $idStructure = 12    -> uniquement si la consultation appartient à la structure 12
     *                         (un point focal ne peut pas fermer celle d'un autre)
     */
    function fermer_consultation(PDO $db, int $idConsultation, ?int $idStructure): bool
    {
        $sql = "UPDATE consultations
                SET statut = 'fermee', date_fermeture = NOW()
                WHERE id_consultation = :id AND statut = 'ouverte'";
        $params = [':id' => $idConsultation];

        if ($idStructure !== null) {
            $sql .= " AND id_structure = :id_structure";
            $params[':id_structure'] = $idStructure;
        }

        $maj = $db->prepare($sql);
        $maj->execute($params);

        return $maj->rowCount() === 1;
    }
}