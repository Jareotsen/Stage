<?php
// Fonctions partagées par consultations.php et consultation_detail.php

if (!function_exists('consultationEstOuverte')) {
    /**
     * Une consultation est ouverte si son statut est 'ouverte'
     * et que sa date de clôture (si elle existe) n'est pas dépassée.
     */
    function consultationEstOuverte(array $c): bool
    {
        if (($c['statut'] ?? '') !== 'ouverte') {
            return false;
        }
        if (!empty($c['date_cloture']) && strtotime($c['date_cloture']) < strtotime('today')) {
            return false;
        }
        return true;
    }
}

if (!function_exists('masquerEmail')) {
    /**
     * Masque l'email avant affichage public : jean.dupont@mail.com -> j***@mail.com
     * (on n'expose jamais l'adresse complète d'un citoyen).
     */
    function masquerEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2 || $parts[0] === '') {
            return '***';
        }
        return mb_substr($parts[0], 0, 1) . '***@' . $parts[1];
    }
}