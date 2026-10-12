<?php
// Fonctions de formatage de dates, partagées entre les pages publiques.

if (!function_exists('formaterDateFr')) {
    /**
     * Formate une date SQL (YYYY-MM-DD) en français, ex : "12 juin 2026".
     * Retourne un texte de repli si la date est vide ou invalide.
     */
    function formaterDateFr(?string $dateSql): string
    {
        if (empty($dateSql)) {
            return 'date non renseignée';
        }

        $timestamp = strtotime($dateSql);
        if ($timestamp === false) {
            return 'date non renseignée';
        }

        $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        return date('d', $timestamp) . ' ' . $mois[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }
}